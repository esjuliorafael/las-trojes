<?php
/* -------------------------------------------------------------------------- */
/* CONFIGURACIÓN                               */
/* -------------------------------------------------------------------------- */
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/database.php';
include_once '../models/Envio.php';
include_once '../models/Orden.php';

$database = new Database();
$db = $database->getConnection();

$envio = new Envio($db);
$orden = new Orden($db);

// Obtener datos del cuerpo de la solicitud
$data = json_decode(file_get_contents("php://input"), true);
$accion = isset($_GET['accion']) ? $_GET['accion'] : '';

/* -------------------------------------------------------------------------- */
/* ACCIONES API                                */
/* -------------------------------------------------------------------------- */

// --- CASO 1: CALCULAR COSTO DE ENVÍO ---
if ($accion == 'calcular_envio') {
    
    // Input esperado: { estado: "Jalisco", tiene_articulos: true, tiene_aves: false }
    $estado  = $data['estado'] ?? '';
    $has_art = $data['tiene_articulos'] ?? false;
    $has_ave = $data['tiene_aves'] ?? false;
    
    $costo = $envio->calcularCostoEnvio($estado, $has_art, $has_ave);
    
    echo json_encode(['costo_envio' => $costo]);

// --- CASO 2: CREAR ORDEN DE COMPRA ---
} elseif ($accion == 'crear_orden') {
    
    // Input esperado: { cliente: {...}, carrito: [...], costos: {...} }
    $cliente = $data['cliente'];
    $carrito = $data['carrito'];
    
    // 1. Recalcular costos en servidor por seguridad
    // (Esto evita manipulación de precios desde el frontend)
    $subtotal = 0;
    $tiene_articulos = false;
    $tiene_aves = false;

    foreach ($carrito as $item) {
        $subtotal += $item['precio'] * $item['cantidad'];
        
        if ($item['tipo'] == 'articulo') $tiene_articulos = true;
        if ($item['tipo'] == 'ave')      $tiene_aves = true;
    }

    // Calcular Envío usando la configuración de BD
    $costo_envio = $envio->calcularCostoEnvio($cliente['estado'], $tiene_articulos, $tiene_aves);
    $total = $subtotal + $costo_envio;

    $costos_finales = [
        'subtotal' => $subtotal,
        'envio'    => $costo_envio,
        'total'    => $total
    ];

    // 2. Intentar Crear la Orden (Validando Stock en DB)
    $resultado = $orden->crear($cliente, $carrito, $costos_finales);

    if ($resultado['success']) {
        // ÉXITO: Orden creada y stock descontado
        $orden_id = $resultado['orden_id'];

        // Generar mensaje para WhatsApp con formato limpio
        $msg  = "Hola, he realizado el pedido #$orden_id en Rancho Las Trojes.%0A";
        $msg .= "Total a pagar: $" . number_format($total, 2) . "%0A";
        $msg .= "Detalles:%0A";
        
        foreach ($carrito as $item) {
            $msg .= "- " . $item['nombre'] . " ($" . $item['precio'] . ")%0A";
        }
        
        $msg .= "%0AEspero instrucciones de pago.";

        echo json_encode([
            'success'       => true, 
            'orden_id'      => $orden_id,
            'whatsapp_link' => "https://wa.me/524432020019?text=" . $msg
        ]);

    } else {
        // ERROR: Stock insuficiente o ave vendida
        echo json_encode([
            'success' => false, 
            'message' => $resultado['message'] 
        ]);
    }
}
?>