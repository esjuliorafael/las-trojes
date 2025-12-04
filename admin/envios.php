<?php
include_once 'includes/header.php';
include_once '../config/database.php';
include_once '../models/Envio.php';

$db = (new Database())->getConnection();
$envioModel = new Envio($db);
$mensaje = '';

// Procesar Actualización Config
if (isset($_POST['update_config'])) {
    $envioModel->actualizarConfiguracion(
        $_POST['costo_base'], 
        isset($_POST['envio_gratis_aves']) ? 1 : 0, 
        $_POST['zona_normal'], 
        $_POST['zona_extendida']
    );
    $mensaje = "Configuración guardada.";
}

// Procesar cambio de Zona (AJAX o POST simple)
if (isset($_POST['update_zona'])) {
    $envioModel->actualizarZona($_POST['zona_id'], $_POST['tipo_zona']);
}

$config = $envioModel->obtenerConfiguracion();
$zonas = $envioModel->obtenerZonas();
?>

<div class="page-header">
    <h1>Configuración de Envíos</h1>
</div>

<?php if($mensaje): ?><div class="alert alert-success"><?php echo $mensaje; ?></div><?php endif; ?>

<div class="management-grid">
    <div class="card">
        <h3 class="section-title">Costos Base</h3>
        <form method="POST">
            <input type="hidden" name="update_config" value="1">
            <div class="form-group">
                <label class="form-label">Envío Artículos (Flat Rate)</label>
                <input type="number" name="costo_base" class="form-control" value="<?php echo $config['costo_base_articulos']; ?>">
            </div>
            <div class="form-check">
                <input type="checkbox" name="envio_gratis_aves" id="ega" class="form-check-input" <?php echo $config['envio_gratis_aves'] ? 'checked' : ''; ?>>
                <label for="ega" class="form-check-label">Envío Gratis en Aves</label>
            </div>
            <hr>
            <div class="form-group">
                <label class="form-label">Costo Zona Normal (Aves)</label>
                <input type="number" name="zona_normal" class="form-control" value="<?php echo $config['costo_zona_normal']; ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Costo Zona Extendida (Aves)</label>
                <input type="number" name="zona_extendida" class="form-control" value="<?php echo $config['costo_zona_extendida']; ?>">
            </div>
            <button type="submit" class="btn btn-primary">Guardar Costos</button>
        </form>
    </div>

    <div class="card">
        <h3 class="section-title">Zonas por Estado</h3>
        <div class="table-container" style="max-height: 500px; overflow-y:auto;">
            <table class="table">
                <thead><tr><th>Estado</th><th>Zona</th><th>Acción</th></tr></thead>
                <tbody>
                    <?php foreach($zonas as $z): ?>
                    <tr>
                        <td><?php echo $z['estado']; ?></td>
                        <td>
                            <form method="POST" style="display:flex; gap:10px;">
                                <input type="hidden" name="update_zona" value="1">
                                <input type="hidden" name="zona_id" value="<?php echo $z['id']; ?>">
                                <select name="tipo_zona" class="form-control select" style="padding: 5px;">
                                    <option value="normal" <?php echo $z['tipo_zona'] == 'normal' ? 'selected' : ''; ?>>Normal</option>
                                    <option value="extendida" <?php echo $z['tipo_zona'] == 'extendida' ? 'selected' : ''; ?>>Extendida</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-secondary"><i class="fas fa-save"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include_once 'includes/footer.php'; ?>