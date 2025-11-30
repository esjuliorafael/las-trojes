<?php
include_once 'includes/header.php';
include_once '../config/database.php';
include_once '../models/Producto.php';

$database = new Database();
$db = $database->getConnection();
$producto_model = new Producto($db);

$mensaje = '';
$tipo_mensaje = '';

// --- MODO EDICIÓN ---
$modo_edicion = false;
$prod_editar = null;
$titulo_form = "Nuevo Producto";
$btn_texto = "Publicar Producto";
$btn_class = "btn-primary";
$btn_icon = "fa-plus-circle";

if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $prod_editar = $producto_model->leerUno($id);
    if ($prod_editar) {
        $modo_edicion = true;
        $titulo_form = "Editar: " . htmlspecialchars($prod_editar['nombre']);
        $btn_texto = "Guardar Cambios";
        $btn_class = "btn-warning";
        $btn_icon = "fa-save";
    }
}

// --- ELIMINAR ---
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    if ($producto_model->eliminar($id)) {
        echo "<script>window.location.href='tienda.php?msg=Producto eliminado&type=success';</script>";
        exit;
    }
}

// --- PROCESAR FORMULARIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    // Asignar datos al modelo
    $producto_model->id = $id;
    $producto_model->tipo = sanitizar($_POST['tipo']);
    $producto_model->nombre = sanitizar($_POST['nombre']);
    $producto_model->descripcion = sanitizar($_POST['descripcion'], true);
    $producto_model->precio = sanitizar_float($_POST['precio']);
    
    // Campos Articulo
    $producto_model->stock = isset($_POST['stock']) ? sanitizar_int($_POST['stock']) : 1;
    
    // Campos Ave
    $producto_model->anillo = isset($_POST['anillo']) ? sanitizar($_POST['anillo']) : null;
    $producto_model->edad = isset($_POST['edad']) ? sanitizar($_POST['edad']) : null;
    $producto_model->proposito = isset($_POST['proposito']) ? sanitizar($_POST['proposito']) : null;
    $producto_model->estado_venta = isset($_POST['estado_venta']) ? sanitizar($_POST['estado_venta']) : 'disponible';

    // Manejo de Portada
    $exito_portada = true;
    if (isset($_FILES['portada']) && $_FILES['portada']['error'] === UPLOAD_ERR_OK) {
        $ruta_portada = $producto_model->subirPortada($_FILES['portada']);
        if ($ruta_portada) {
            $producto_model->portada = $ruta_portada;
        } else {
            $exito_portada = false;
        }
    }

    if ($exito_portada) {
        $resultado = ($id > 0) ? $producto_model->actualizar() : $producto_model->crear();
        
        if ($resultado) {
            // Subir galería extra si existe
            if (isset($_FILES['galeria'])) {
                $pid = ($id > 0) ? $id : $producto_model->id;
                $producto_model->agregarGaleria($pid, $_FILES['galeria']);
            }
            $mensaje = ($id > 0) ? "Producto actualizado" : "Producto creado correctamente";
            $tipo_mensaje = "success";
            if (!$modo_edicion) $_POST = []; // Limpiar form si es nuevo
        } else {
            $mensaje = "Error al guardar en base de datos";
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = "Error al subir la imagen de portada";
        $tipo_mensaje = "error";
    }
}

$productos = $producto_model->leerTodos();
?>

<div class="store-management">
    <div class="page-header">
        <h1>Gestión de Tienda</h1>
        <p>Administra artículos y aves en venta</p>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-<?php echo $_GET['type'] ?? 'info'; ?>">
            <?php echo htmlspecialchars($_GET['msg']); ?>
        </div>
    <?php endif; ?>
    <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?>"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <div class="management-tabs">
        <div class="tabs">
            <button class="tab-button <?php echo $modo_edicion ? '' : 'active'; ?>" data-tab="listar">Listado de Productos</button>
            <button class="tab-button <?php echo $modo_edicion ? 'active' : ''; ?>" data-tab="crear"><?php echo $titulo_form; ?></button>
        </div>

        <div class="tab-content <?php echo $modo_edicion ? '' : 'active'; ?>" id="listar">
            <div class="section-card">
                <h3 class="section-title">Inventario Actual</h3>
                <?php if (empty($productos)): ?>
                    <div class="empty-state"><i class="fas fa-box-open"></i><p>No hay productos registrados</p></div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Img</th>
                                    <th>Producto</th>
                                    <th>Tipo</th>
                                    <th>Precio</th>
                                    <th>Estado/Stock</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos as $p): ?>
                                <tr>
                                    <td>
                                        <?php if ($p['portada']): ?>
                                            <img src="../<?php echo $p['portada']; ?>" style="width:50px; height:50px; object-fit:cover; border-radius:8px;">
                                        <?php else: ?>
                                            <i class="fas fa-image text-muted"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($p['nombre']); ?></strong>
                                        <?php if($p['tipo'] == 'ave' && $p['anillo']): ?>
                                            <br><small class="text-muted">Anillo: <?php echo $p['anillo']; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($p['tipo'] == 'ave'): ?>
                                            <span class="badge" style="background:#e3f2fd; color:#0d47a1; padding:4px 8px; border-radius:4px;"><i class="fas fa-feather"></i> Ave</span>
                                        <?php else: ?>
                                            <span class="badge" style="background:#f3e5f5; color:#4a148c; padding:4px 8px; border-radius:4px;"><i class="fas fa-box"></i> Artículo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>$<?php echo number_format($p['precio'], 2); ?></td>
                                    <td>
                                        <?php if($p['tipo'] == 'ave'): ?>
                                            <?php 
                                                $clase_estado = 'success';
                                                if($p['estado_venta'] == 'vendido') $clase_estado = 'error';
                                                if($p['estado_venta'] == 'reservado') $clase_estado = 'warning';
                                            ?>
                                            <span class="status-badge status-<?php echo $p['estado_venta'] == 'vendido' ? 'inactive' : 'active'; ?>">
                                                <?php echo ucfirst($p['estado_venta']); ?>
                                            </span>
                                        <?php else: ?>
                                            Stock: <strong><?php echo $p['stock']; ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="tienda.php?editar=<?php echo $p['id']; ?>" class="btn btn-sm btn-secondary"><i class="fas fa-edit"></i></a>
                                            <a href="tienda.php?eliminar=<?php echo $p['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar producto?')"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-content <?php echo $modo_edicion ? 'active' : ''; ?>" id="crear">
            <div class="upload-form-container">
                <form method="POST" enctype="multipart/form-data" class="media-form">
                    <input type="hidden" name="id" value="<?php echo $modo_edicion ? $prod_editar['id'] : 0; ?>">
                    
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="tipo" class="form-label">Tipo de Producto *</label>
                            <select id="tipo" name="tipo" class="form-control select" required <?php echo $modo_edicion ? 'style="pointer-events:none; background:#eee;"' : ''; ?>>
                                <option value="articulo" <?php echo ($modo_edicion && $prod_editar['tipo'] == 'articulo') ? 'selected' : ''; ?>>Artículo General (Gorras, Navajas, etc.)</option>
                                <option value="ave" <?php echo ($modo_edicion && $prod_editar['tipo'] == 'ave') ? 'selected' : ''; ?>>Ave (Gallo, Gallina, Pollo)</option>
                            </select>
                            <?php if($modo_edicion): ?><input type="hidden" name="tipo" value="<?php echo $prod_editar['tipo']; ?>"><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nombre del Producto *</label>
                            <input type="text" name="nombre" class="form-control" required value="<?php echo $modo_edicion ? htmlspecialchars($prod_editar['nombre']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Precio ($) *</label>
                            <input type="number" step="0.01" name="precio" class="form-control" required value="<?php echo $modo_edicion ? $prod_editar['precio'] : ''; ?>">
                        </div>
                    </div>

                    <div id="campos-ave" class="form-grid hidden">
                        <div class="form-group">
                            <label class="form-label">Número de Anillo</label>
                            <input type="text" name="anillo" class="form-control" value="<?php echo $modo_edicion ? htmlspecialchars($prod_editar['anillo']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Edad / Etapa</label>
                            <select name="edad" class="form-control select">
                                <option value="">Seleccionar...</option>
                                <?php 
                                // Opciones actualizadas según requerimiento
                                $edades = ['Pollo', 'Polla', 'Gallo', 'Gallina']; 
                                foreach($edades as $e): ?>
                                    <option value="<?php echo $e; ?>" <?php echo ($modo_edicion && $prod_editar['edad'] == $e) ? 'selected' : ''; ?>><?php echo $e; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Propósito</label>
                            <select name="proposito" class="form-control select">
                                <option value="Combate" <?php echo ($modo_edicion && $prod_editar['proposito'] == 'Combate') ? 'selected' : ''; ?>>Combate</option>
                                <option value="Cría" <?php echo ($modo_edicion && $prod_editar['proposito'] == 'Cría') ? 'selected' : ''; ?>>Cría</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Estado de Venta</label>
                            <select name="estado_venta" class="form-control select">
                                <option value="disponible" <?php echo ($modo_edicion && $prod_editar['estado_venta'] == 'disponible') ? 'selected' : ''; ?>>Disponible</option>
                                <option value="reservado" <?php echo ($modo_edicion && $prod_editar['estado_venta'] == 'reservado') ? 'selected' : ''; ?>>Reservado</option>
                                <option value="vendido" <?php echo ($modo_edicion && $prod_editar['estado_venta'] == 'vendido') ? 'selected' : ''; ?>>Vendido</option>
                            </select>
                        </div>
                    </div>

                    <div id="campos-articulo" class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Stock Disponibles</label>
                            <input type="number" name="stock" class="form-control" value="<?php echo $modo_edicion ? $prod_editar['stock'] : '1'; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Descripción Detallada</label>
                        <textarea name="descripcion" class="form-control textarea" rows="4"><?php echo $modo_edicion ? htmlspecialchars($prod_editar['descripcion']) : ''; ?></textarea>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Foto de Portada <?php echo $modo_edicion ? '(Dejar vacío para mantener)' : '*'; ?></label>
                            <?php if($modo_edicion && $prod_editar['portada']): ?>
                                <img src="../<?php echo $prod_editar['portada']; ?>" style="height:100px; margin-bottom:10px; border-radius:8px;">
                            <?php endif; ?>
                            <input type="file" name="portada" class="form-control" accept="image/*" <?php echo $modo_edicion ? '' : 'required'; ?>>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Galería Adicional (Múltiples)</label>
                            <input type="file" name="galeria[]" class="form-control" accept="image/*" multiple>
                            <div class="form-text">Puedes seleccionar varias fotos a la vez.</div>
                        </div>
                    </div>

                    <button type="submit" class="btn <?php echo $btn_class; ?>">
                        <i class="fas <?php echo $btn_icon; ?>"></i> <?php echo $btn_texto; ?>
                    </button>
                    <?php if($modo_edicion): ?>
                        <a href="tienda.php" class="btn btn-secondary">Cancelar</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manejo de Tabs
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            button.classList.add('active');
            document.getElementById(button.dataset.tab).classList.add('active');
        });
    });

    // Lógica Ave vs Artículo
    const tipoSelect = document.getElementById('tipo');
    const camposAve = document.getElementById('campos-ave');
    const camposArticulo = document.getElementById('campos-articulo');

    function toggleCampos() {
        if (tipoSelect.value === 'ave') {
            camposAve.classList.remove('hidden');
            camposArticulo.classList.add('hidden');
        } else {
            camposAve.classList.add('hidden');
            camposArticulo.classList.remove('hidden');
        }
    }

    tipoSelect.addEventListener('change', toggleCampos);
    toggleCampos(); // Ejecutar al inicio
});
</script>
<?php include_once 'includes/footer.php'; ?>