<?php
include_once 'includes/header.php';
include_once '../config/database.php';
include_once '../models/categoria.php';

$database = new Database();
$db = $database->getConnection();
$categoria = new Categoria($db);

$mensaje = '';
$tipo_mensaje = '';

// Crear nueva categoría
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_categoria'])) {
    $nombre = sanitizar($_POST['nombre'] ?? '');
    $icono = sanitizar($_POST['icono'] ?? '');
    
    if (empty($nombre)) {
        $mensaje = 'El nombre de la categoría es obligatorio';
        $tipo_mensaje = 'error';
    } else {
        $categoria->nombre = $nombre;
        $categoria->icono = $icono;
        
        if ($categoria->crear()) {
            $mensaje = 'Categoría creada correctamente';
            $tipo_mensaje = 'success';
            
            // Limpiar el formulario
            $_POST = array();
        } else {
            $mensaje = 'Error al crear la categoría';
            $tipo_mensaje = 'error';
        }
    }
}

// Eliminar categoría
if (isset($_GET['eliminar'])) {
    $categoria->id = (int)$_GET['eliminar'];
    if ($categoria->eliminar()) {
        $mensaje = 'Categoría eliminada correctamente';
        $tipo_mensaje = 'success';
    } else {
        $mensaje = 'Error al eliminar la categoría';
        $tipo_mensaje = 'error';
    }
}

$categorias = $categoria->obtenerTodas();
?>
<div class="categories-management">
    <div class="page-header">
        <h1>Gestión de Categorías</h1>
        <p>Administra las categorías de la galería</p>
    </div>
    
    <?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $tipo_mensaje; ?>">
        <?php echo $mensaje; ?>
    </div>
    <?php endif; ?>

    <div class="management-grid">
        <div class="form-section">
            <div class="section-card">
                <h3 class="section-title">Crear Nueva Categoría</h3>
                <form method="POST" class="category-form">
                    <input type="hidden" name="crear_categoria" value="1">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nombre" class="form-label">Nombre de la categoría *</label>
                            <input type="text" id="nombre" name="nombre" class="form-control" 
                                placeholder="Ej: Gallos de Combate" required
                                value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="icono" class="form-label">Ícono (Font Awesome)</label>
                            <input type="text" id="icono" name="icono" class="form-control" 
                                placeholder="fas fa-star"
                                value="<?php echo isset($_POST['icono']) ? htmlspecialchars($_POST['icono']) : ''; ?>">
                            <div class="form-text">Ejemplo: fas fa-star, fas fa-calendar, fas fa-home</div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Crear Categoría
                    </button>
                </form>
            </div>
        </div>

        <div class="list-section">
            <div class="section-card">
                <h3 class="section-title">Categorías Existentes</h3>
                
                <div class="categories-list">
                    <?php foreach ($categorias as $cat): ?>
                    <div class="category-item">
                        <div class="category-info">
                            <div class="category-icon">
                                <i class="<?php echo $cat['icono'] ?: 'fas fa-folder'; ?>"></i>
                            </div>
                            <div class="category-details">
                                <h4><?php echo $cat['nombre']; ?></h4>
                                <span class="media-count"><?php echo $cat['cantidad_medios']; ?> medios</span>
                            </div>
                        </div>
                        <div class="category-actions">
                            <a href="categorias.php?editar=<?php echo $cat['id']; ?>" class="btn btn-sm btn-secondary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="categorias.php?eliminar=<?php echo $cat['id']; ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('¿Estás seguro de eliminar esta categoría?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include_once 'includes/footer.php'; ?>