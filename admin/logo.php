<?php
include_once 'includes/header.php';
include_once '../config/database.php';
include_once '../models/Logo.php';

$database = new Database();
$db = $database->getConnection();
$logo = new Logo($db);

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo'])) {
    $archivo = $_FILES['logo'];
    
    if ($archivo['error'] === UPLOAD_ERR_OK) {
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $tipos_permitidos = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($extension, $tipos_permitidos)) {
            if ($logo->subirLogo($archivo['tmp_name'], $archivo['name'])) {
                $mensaje = 'Logo actualizado correctamente';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error al subir el logo';
                $tipo_mensaje = 'error';
            }
        } else {
            $mensaje = 'Tipo de archivo no permitido';
            $tipo_mensaje = 'error';
        }
    } else {
        $mensaje = 'Error al subir el archivo';
        $tipo_mensaje = 'error';
    }
}

$logo_actual = $logo->obtenerLogoActivo();
?>
<div class="logo-management">
    <div class="page-header">
        <h1>Gestión de Logo</h1>
        <p>Actualiza el logo del sitio web</p>
    </div>

    <?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $tipo_mensaje; ?>">
        <?php echo $mensaje; ?>
    </div>
    <?php endif; ?>

    <div class="management-grid">
        <div class="logo-card">
            <div class="current-logo">
                <h3 class="section-title">Logo Actual</h3>
                <div class="logo-preview">
                    <img src="../<?php echo $logo_actual; ?>" alt="Logo Actual">
                </div>
            </div>
        </div>

        <div class="logo-card">
            <div class="logo-upload">
                <h3 class="section-title"s>Subir Nuevo Logo</h3>
                <form method="POST" enctype="multipart/form-data" class="logo-form">      
                    <div class="form-group">
                        <label for="logo" class="form-label">Subir Nuevo Logo *</label>
                        <input type="file" id="logo" name="logo" class="form-control" 
                            accept=".jpg,.jpeg,.png,.gif,.webp" required>
                        <div class="form-text">Formatos permitidos: JPG, PNG, GIF, WebP. Tamaño máximo: 5MB</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Subir Logo
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include_once 'includes/footer.php'; ?>