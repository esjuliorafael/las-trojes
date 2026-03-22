<?php
/* -------------------------------------------------------------------------- */
/* CONFIGURACIÓN Y DEPENDENCIAS                                               */
/* -------------------------------------------------------------------------- */
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/database.php';
include_once '../models/Envio.php';
include_once '../models/Orden.php';
include_once '../models/Configuracion.php';

$database = new Database();
$db = $database->getConnection();

$envio = new Envio($db);
$orden = new Orden($db);
$confModel = new Configuracion($db);

// CORRECCIÓN: Construir el arreglo $config usando obtenerTodas()
$stmtConfig = $confModel->obtenerTodas();
$config = [];
while ($row = $stmtConfig->fetch(PDO::FETCH_ASSOC)) {
    $config[$row['clave']] = $row['valor'];
}

$data = json_decode(file_get_contents("php://input"), true);
$accion = isset($_GET['accion']) ? $_GET['accion'] : '';

// 1. CONSTANTE DE DOMINIO
$DOMAIN_URL = "https://rancholastrojes.com.mx/";
$IMG_PLACEHOLDER = "https://rancholastrojes.com.mx/assets/images/logo.png"; 

/* -------------------------------------------------------------------------- */
/* LÓGICA DE LA API                                                           */
/* -------------------------------------------------------------------------- */

if ($accion == 'crear_orden') {
    
    $cliente = $data['cliente'];
    $carrito = $data['carrito'];
    
    // Variables de control
    $subtotal = 0;
    $tiene_articulos = false;
    $tiene_aves = false;
    $count_aves = 0;
    
    $lista_productos_txt = "";  // Para WhatsApp
    $lista_productos_html = ""; // Para Email

    // Consulta preparada para obtener la ruta REAL de la imagen
    $stmtImagen = $db->prepare("SELECT portada FROM productos WHERE id = :id LIMIT 1");

    foreach ($carrito as $item) {
        $total_item = $item['precio'] * $item['cantidad'];
        $subtotal += $total_item;
        
        if ($item['tipo'] == 'articulo') $tiene_articulos = true;
        if ($item['tipo'] == 'ave') {
            $tiene_aves = true;
            $count_aves += (int)$item['cantidad'];
        }

        // --- A. Obtener Ruta de BD ---
        $ruta_db = "";
        try {
            $stmtImagen->execute([':id' => $item['id']]);
            $row = $stmtImagen->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['portada'])) {
                $ruta_db = $row['portada']; // Ej: assets/uploads/tienda/portadas/foto.jpg
            }
        } catch (Exception $e) { }

        // --- B. Construir URL Absoluta Correcta ---
        if (!empty($ruta_db)) {
            // Si la ruta en BD ya es absoluta (http...), la usamos tal cual.
            // Si es relativa (assets/...), le pegamos el dominio.
            if (strpos($ruta_db, 'http') === 0) {
                $img_src = $ruta_db;
            } else {
                // CORRECCIÓN AQUÍ: Concatenación directa Dominio + Ruta BD
                $img_src = $DOMAIN_URL . ltrim($ruta_db, '/');
            }
        } else {
            $img_src = $IMG_PLACEHOLDER;
        }

        // --- C. WhatsApp (Texto) ---
        $lista_productos_txt .= "- " . $item['nombre'] . " ($" . number_format($item['precio'], 2) . ")";
        if ($item['cantidad'] > 1) $lista_productos_txt .= " x" . $item['cantidad'];
        $lista_productos_txt .= "\n";

        // --- D. Email (HTML con la URL corregida) ---
        $lista_productos_html .= "
        <table width='100%' cellpadding='0' cellspacing='0' border='0' style='margin-bottom: 12px; border-bottom: 1px solid #f3f4f6; padding-bottom: 12px;'>
            <tr>
                <td width='70' valign='top' style='padding-right: 15px;'>
                    <img src='{$img_src}' alt='Prod' width='60' height='60' style='display: block; border-radius: 8px; object-fit: cover; border: 1px solid #e5e7eb; background-color: #ffffff;'>
                </td>
                <td valign='middle'>
                    <div style='font-family: \"Plus Jakarta Sans\", sans-serif; font-size: 14px; font-weight: 700; color: #1a1a1a; line-height: 1.2; margin-bottom: 4px;'>
                        {$item['nombre']}
                    </div>
                    <div style='font-family: monospace; font-size: 13px; color: #6b7280;'>
                        $" . number_format($item['precio'], 2) . " <span style='color:#9ca3af; font-size:11px;'>(x{$item['cantidad']})</span>
                    </div>
                </td>
            </tr>
        </table>";
    }

    // --- CÁLCULO EXACTO DE ENVÍO (Backend) ---
    $stmtZona = $db->prepare("SELECT tipo_zona FROM zonas_envio WHERE estado = :estado LIMIT 1");
    $stmtZona->execute([':estado' => $cliente['estado']]);
    $zonaDb = $stmtZona->fetch(PDO::FETCH_ASSOC);
    $tipo_zona = $zonaDb ? $zonaDb['tipo_zona'] : 'normal';

    $costo_envio = 0;

    // Regla 1: Cada ave cobra un envío independiente
    if ($tiene_aves && $count_aves > 0) {
        if (isset($config['envio_gratis_aves']) && $config['envio_gratis_aves'] == '1') {
            $costo_envio += 0;
        } else {
            $costo_base_ave = ($tipo_zona === 'extendida') ? ($config['envio_costo_extendida'] ?? 0) : ($config['envio_costo_normal'] ?? 0);
            $costo_envio += ($costo_base_ave * $count_aves);
        }
    }

    // Regla 2: Artículos cobran una tarifa plana única
    if ($tiene_articulos) {
        if (isset($config['envio_gratis_articulos']) && $config['envio_gratis_articulos'] == '1') {
            $costo_envio += 0;
        } else {
            $costo_envio += ($config['envio_costo_base_articulos'] ?? 0);
        }
    }

    $total = $subtotal + $costo_envio;

    $costos_finales = [
        'subtotal' => $subtotal,
        'envio'    => $costo_envio,
        'total'    => $total
    ];

    // 2. Crear Orden
    $resultado = $orden->crear($cliente, $carrito, $costos_finales);

    if ($resultado['success']) {
        $orden_id = $resultado['orden_id'];

        // --- WhatsApp Link ---
        $wa_link = "";
        if (isset($config['whatsapp_activo']) && $config['whatsapp_activo'] == '1') {
            $numero_admin = $config['whatsapp_numero'] ?? '';
            $plantilla    = $config['whatsapp_plantilla'] ?? "Pedido #{id_orden} Total: ${total}";

            $mensaje = str_replace(
                ['{nombre_cliente}', '{id_orden}', '{total}', '{lista_productos}'],
                [$cliente['nombre'], $orden_id, number_format($total, 2), $lista_productos_txt],
                $plantilla
            );

            if (!empty($config['banco_nombre'])) {
                $mensaje .= "\n\n*Datos de Pago:*\n";
                $mensaje .= "Banco: " . $config['banco_nombre'] . "\n";
                $mensaje .= "Cuenta: " . $config['banco_cuenta'] . "\n";
                $mensaje .= "Beneficiario: " . $config['banco_beneficiario'];
            }
            $wa_link = "https://wa.me/" . $numero_admin . "?text=" . urlencode($mensaje);
        } else {
            $wa_link = "index.php?pedido_exitoso=true&oid=" . $orden_id;
        }

        // --- Notificación Email ---
        if (isset($config['email_notificaciones_activo']) && $config['email_notificaciones_activo'] == '1') {
            $to = $config['email_admin'] ?? '';
            $remitente = $config['email_remitente'] ?? 'notif@rancholastrojes.com.mx';
            
            if (!empty($to)) {
                $asunto = "Nueva Venta #" . $orden_id . " - Las Trojes";
                
                $cuerpo_html = "
                <!DOCTYPE html>
                <html lang='es'>
                    <head>
                        <meta charset='UTF-8'>
                        <style>
                            @import url('https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap');

                            body { margin: 0; padding: 1.25rem 0; background-color: #f3efeb !important; font-family: 'Plus Jakarta Sans', Arial, sans-serif; }
                            .container { max-width: 512px; margin: 0 auto; background-color: #ffffff !important; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); }
                            
                            .header { padding: 2.25rem 1.75rem 1.75rem; text-align: center; }
                            .header-logo { width: 100px; height: auto; border-radius: 50%; }
                            .header-subtitle { margin-top: 15px; font-size: 15px; color: #6b7280; }
                            .footer { padding: 20px; border-top: 1px solid #e5e7eb; background-color: #f9fafb; font-size: 12px; color: #9ca3af; text-align: center; }
                            
                            .content { padding: 0 40px 40px; color: #4b5563; }
                            .section-title { margin-bottom: 1.75rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e5e7eb; font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 700; font-size: 1rem; color: #1a1a1a; }
                            .lora-italic { font-family: 'Lora', serif; font-weight: 600; font-style: italic; color: #8b5e3c; }
                            
                            .data-row { margin-bottom: 1rem; }
                            .label { display: block; margin-bottom: 0.25rem; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; }
                            .value { font-weight: 500; font-size: 1rem; color: #1f2937; }
                            
                            .product-list { margin-top: 2rem; }
                            .total-box { margin-top: 2rem; padding: 1.25rem; background-color: #f9f7f5 !important; border-radius: 0.75rem; }
                            
                            .table-totals { width: 100%; border-collapse: collapse; border: 0; }
                            .td-label { padding-bottom: 8px; font-size: 12px; color: #6b7280; }
                            .td-value { padding-bottom: 8px; font-size: 12px; color: #6b7280; text-align: right; }
                            .td-divider { border-top: 1px solid #e5e7eb; height: 1px; padding: 0; }
                            .td-total-label { padding-top: 10px; font-size: 18px; font-weight: 700; color: #1a1a1a; }
                            .td-total-value { padding-top: 10px; font-size: 18px; font-weight: 700; color: #1a1a1a; text-align: right; }

                            .btn-wrapper { text-align: center; margin-top: 20px; }
                            .btn-admin { display: inline-block; padding: 12px 24px; background-color: #1a1a1a; border-radius: 8px; color: #ffffff; font-weight: 600; font-size: 14px; text-decoration: none; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <img src='https://rancholastrojes.com.mx/assets/images/logo.png' alt='Rancho Las Trojes' class='header-logo'>
                                <p class='header-subtitle'>Nueva orden de compra recibida</p>
                            </div>

                            <div class='content'>
                                <div class='section-title'>
                                    Datos <span class='lora-italic'>del Cliente</span>
                                </div>
                                <div class='data-row'>
                                    <span class='label'>Cliente</span>
                                    <div class='value'>{$cliente['nombre']}</div>
                                </div>
                                <div class='data-row'>
                                    <span class='label'>Contacto</span>
                                    <div class='value'>{$cliente['telefono']}</div>
                                </div>
                                <div class='data-row'>
                                    <span class='label'>Destino</span>
                                    <div class='value'>{$cliente['estado']}</div>
                                </div>

                                <div class='section-title' style='margin-top: 40px;'>
                                    Resumen <span class='lora-italic'>del Pedido</span>
                                </div>
                                
                                <div class='product-list'>
                                    {$lista_productos_html}
                                </div>

                                <div class='total-box'>
                                    <table class='table-totals' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td class='td-label'>Subtotal</td>
                                            <td class='td-value'>$" . number_format($subtotal, 2) . "</td>
                                        </tr>
                                        <tr>
                                            <td class='td-label'>Envío</td>
                                            <td class='td-value'>$" . number_format($costo_envio, 2) . "</td>
                                        </tr>
                                        <tr>
                                            <td colspan='2' class='td-divider'></td>
                                        </tr>
                                        <tr>
                                            <td class='td-total-label'>Total</td>
                                            <td class='td-total-value'>$" . number_format($total, 2) . "</td>
                                        </tr>
                                    </table>
                                </div>

                                <div class='btn-wrapper'>
                                    <a href='https://rancholastrojes.com.mx/admin' class='btn-admin'>Gestionar en Panel</a>
                                </div>
                            </div>

                            <div class='footer'>
                                Orden #{$orden_id} • Generado automáticamente.<br>
                                © " . date('Y') . " Rancho Las Trojes
                            </div>
                        </div>
                    </body>
                </html>
                ";
                
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= "From: " . $remitente . "\r\n";
                $headers .= "Reply-To: " . $remitente . "\r\n";

                @mail($to, $asunto, $cuerpo_html, $headers);
            }
        }

        echo json_encode([
            'success'       => true, 
            'orden_id'      => $orden_id,
            'whatsapp_link' => $wa_link
        ]);

    } else {
        echo json_encode([
            'success' => false, 
            'message' => $resultado['message'] 
        ]);
    }
}
?>