<?php
include_once 'includes/header.php';
include_once '../config/database.php';
include_once '../models/Medio.php';
include_once '../models/Categoria.php';

$database = new Database();
$db = $database->getConnection();
$medio_model = new Medio($db);
$categoria_model = new Categoria($db);

$mensaje = '';
$tipo_mensaje = '';

// PROCESAR ELIMINACIÓN DE MEDIO
if (isset($_GET['eliminar'])) {
    $medio_id = (int)$_GET['eliminar'];
    
    if ($medio_model->eliminar($medio_id)) {
        $mensaje = 'Medio eliminado correctamente (archivo y registro eliminados)';
        $tipo_mensaje = 'success';
    } else {
        $mensaje = 'Error al eliminar el medio';
        $tipo_mensaje = 'error';
    }
    
    // Redirigir para evitar re-envío del formulario
    header('Location: galeria.php?mensaje=' . urlencode($mensaje) . '&tipo=' . $tipo_mensaje);
    exit;
}

// Mostrar mensajes si vienen por URL
if (isset($_GET['mensaje']) && isset($_GET['tipo'])) {
    $mensaje = $_GET['mensaje'];
    $tipo_mensaje = $_GET['tipo'];
}

// PROCESAR SUBIDA DE NUEVO MEDIO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    // Sanitizar todos los datos de entrada
    $medio_model->titulo = sanitizar($_POST['titulo'] ?? '');
    $medio_model->descripcion = sanitizar($_POST['descripcion'] ?? '');
    $medio_model->tipo = sanitizar($_POST['tipo'] ?? '');
    $medio_model->categoria_id = sanitizar_int($_POST['categoria_id'] ?? 0);
    $medio_model->ubicacion = sanitizar($_POST['ubicacion'] ?? '');
    $medio_model->fecha_media = $_POST['fecha_media'] ?? date('Y-m-d');
    
    $archivo = $_FILES['archivo'];
    
    // Validaciones básicas
    if (empty($medio_model->titulo) || empty($medio_model->tipo) || $medio_model->categoria_id == 0) {
        $mensaje = 'Por favor completa todos los campos obligatorios';
        $tipo_mensaje = 'error';
    } elseif ($archivo['error'] !== UPLOAD_ERR_OK) {
        $mensaje = 'Error al subir el archivo: ' . $archivo['error'];
        $tipo_mensaje = 'error';
    } else {
        // Validar tipo de archivo
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        
        if ($medio_model->tipo === 'video') {
            $tipos_permitidos = ['mp4', 'mov', 'avi'];
            $mensaje_error = 'Solo se permiten videos MP4, MOV, AVI';
        } else {
            $tipos_permitidos = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $mensaje_error = 'Solo se permiten imágenes JPG, PNG, GIF, WebP';
        }
        
        if (in_array($extension, $tipos_permitidos)) {
            if ($medio_model->subirMedio($archivo['tmp_name'], $archivo['name'], $medio_model->tipo)) {
                $mensaje = 'Medio subido correctamente';
                $tipo_mensaje = 'success';
                
                // Limpiar el formulario
                $_POST = array();
            } else {
                $mensaje = 'Error al guardar el medio en la base de datos';
                $tipo_mensaje = 'error';
            }
        } else {
            $mensaje = $mensaje_error;
            $tipo_mensaje = 'error';
        }
    }
}

// Obtener datos para mostrar
$medios = $medio_model->obtenerTodos();
$categorias = $categoria_model->obtenerTodas();
?>
<div class="gallery-management">
    <div class="page-header">
        <h1>Gestión de Galería</h1>
        <p>Administra los medios de la galería</p>
    </div>

    <?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $tipo_mensaje; ?>">
        <?php echo $mensaje; ?>
    </div>
    <?php endif; ?>

    <div class="management-tabs">
        <div class="tabs">
            <button class="tab-button active" data-tab="subir">Subir Medio</button>
            <button class="tab-button" data-tab="listar">Medios Existentes</button>
        </div>

        <div class="tab-content active" id="subir">
            <div class="upload-form-container">
                <form method="POST" enctype="multipart/form-data" class="media-form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="titulo" class="form-label">Título *</label>
                            <input type="text" id="titulo" name="titulo" class="form-control" placeholder="Ingresa el título del medio" required
                                   value="<?php echo isset($_POST['titulo']) ? htmlspecialchars($_POST['titulo']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="tipo" class="form-label">Tipo de medio *</label>
                            <select id="tipo" name="tipo" class="form-control select" required>
                                <option value="">Seleccionar tipo...</option>
                                <option value="foto" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] === 'foto') ? 'selected' : ''; ?>>Foto</option>
                                <option value="video" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] === 'video') ? 'selected' : ''; ?>>Video</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="categoria_id" class="form-label">Categoría *</label>
                            <select id="categoria_id" name="categoria_id" class="form-control select" required>
                                <option value="">Seleccionar categoría...</option>
                                <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo (isset($_POST['categoria_id']) && $_POST['categoria_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="fecha_media" class="form-label">Fecha del medio *</label>
                            <input type="date" id="fecha_media" name="fecha_media" class="form-control" required
                                   value="<?php echo isset($_POST['fecha_media']) ? $_POST['fecha_media'] : date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea id="descripcion" name="descripcion" class="form-control textarea" 
                                  placeholder="Describe el contenido del medio..." rows="3"><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="ubicacion" class="form-label">Ubicación</label>
                        <input type="text" id="ubicacion" name="ubicacion" class="form-control" 
                               placeholder="Ej: Rancho Las Trojes, Morelia"
                               value="<?php echo isset($_POST['ubicacion']) ? htmlspecialchars($_POST['ubicacion']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="archivo" class="form-label">Archivo *</label>
                        <input type="file" id="archivo" name="archivo" class="form-control" required 
                               accept="<?php echo (isset($_POST['tipo']) && $_POST['tipo'] === 'video') ? '.mp4,.mov,.avi' : '.jpg,.jpeg,.png,.gif,.webp'; ?>">
                        <div class="form-text" id="file-help">
                            <?php echo (isset($_POST['tipo']) && $_POST['tipo'] === 'video') ? 
                                'Formatos permitidos: MP4, MOV, AVI. Máx: 50MB' : 
                                'Formatos permitidos: JPG, PNG, GIF, WebP. Máx: 50MB'; ?>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Subir Medio
                    </button>
                </form>
            </div>
        </div>

        <div class="tab-content" id="listar">
            <div class="list-section">
                <div class="section-card">
                    <h3>Medios Existentes</h3>
                    
                    <?php if (empty($medios)): ?>
                        <div class="empty-state">
                            <i class="fas fa-images"></i>
                            <p>No hay medios en la galería</p>
                            <a href="galeria.php" class="btn btn-primary" style="margin-top: 1rem;">
                                <i class="fas fa-plus"></i> Agregar Primer Medio
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="media-grid-admin">
                            <?php foreach ($medios as $med): ?>
                            <div class="media-item-admin">
                                <div class="media-preview">
                                    <?php if ($med['tipo'] === 'foto'): ?>
                                        <img src="<?php echo $med['ruta_archivo']; ?>" alt="<?php echo htmlspecialchars($med['titulo']); ?>">
                                    <?php else: ?>
                                        <div class="video-preview">
                                            <i class="fas fa-play-circle" style="font-size: 3rem;"></i>
                                            <span>Video</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="media-info-admin">
                                    <h4><?php echo htmlspecialchars($med['titulo']); ?></h4>
                                    <p class="media-desc"><?php echo htmlspecialchars($med['descripcion']); ?></p>
                                    <div class="media-meta">
                                        <span class="media-type"><?php echo ucfirst($med['tipo']); ?></span>
                                        <span class="media-category"><?php echo htmlspecialchars($med['categoria_nombre']); ?></span>
                                        <span class="media-date"><?php echo date('d/m/Y', strtotime($med['fecha_media'])); ?></span>
                                    </div>
                                </div>
                                
                                <div class="media-actions">
                                    <span class="likes-count"><?php echo $med['likes']; ?> ❤️</span>
                                    <div class="action-buttons">
                                        <a href="galeria.php?editar=<?php echo $med['id']; ?>" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="galeria.php?eliminar=<?php echo $med['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('¿Estás seguro de ELIMINAR PERMANENTEMENTE este medio? Esta acción no se puede deshacer y eliminará tanto el registro como el archivo físico.')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Navegación por pestañas
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Actualizar botones activos
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Actualizar contenido visible
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    // Validación de tipo de archivo según selección
    const tipoSelect = document.getElementById('tipo');
    const archivoInput = document.getElementById('archivo');
    const fileHelp = document.getElementById('file-help');
    
    function actualizarValidacionArchivo() {
        const tipo = tipoSelect.value;
        if (tipo === 'video') {
            archivoInput.setAttribute('accept', '.mp4,.mov,.avi');
            fileHelp.textContent = 'Formatos permitidos: MP4, MOV, AVI. Máx: 50MB';
        } else {
            archivoInput.setAttribute('accept', '.jpg,.jpeg,.png,.gif,.webp');
            fileHelp.textContent = 'Formatos permitidos: JPG, PNG, GIF, WebP. Máx: 50MB';
        }
    }
    
    tipoSelect.addEventListener('change', actualizarValidacionArchivo);
    actualizarValidacionArchivo(); // Inicializar
    
    // Mobile menu toggle
    const mobileToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 1024) {
            if (!sidebar.contains(event.target) && !mobileToggle.contains(event.target)) {
                sidebar.classList.remove('open');
            }
        }
    });
});
</script>
<?php include_once 'includes/footer.php'; ?>