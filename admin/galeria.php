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

// --- CONFIGURACIÓN MODO EDICIÓN ---
$modo_edicion = false;
$medio_editar = null;
$titulo_formulario = "Subir Nuevo Medio";
$texto_boton = "Subir Medio";
$clase_boton = "btn-primary";
$icono_boton = "fa-upload";

// 1. DETECTAR SI ESTAMOS EDITANDO (Vía GET)
if (isset($_GET['editar'])) {
    $id_editar = (int)$_GET['editar'];
    $resultado = $medio_model->obtenerPorId($id_editar);
    
    if ($resultado) {
        $modo_edicion = true;
        $medio_editar = $resultado;
        
        // Ajustar interfaz para edición
        $titulo_formulario = "Editando: " . htmlspecialchars($resultado['titulo']);
        $texto_boton = "Actualizar Medio";
        $clase_boton = "btn-warning";
        $icono_boton = "fa-save";
    } else {
        $mensaje = "El medio solicitado no existe.";
        $tipo_mensaje = "error";
    }
}

// 2. PROCESAR ELIMINACIÓN
if (isset($_GET['eliminar'])) {
    $medio_id = (int)$_GET['eliminar'];
    if ($medio_model->eliminar($medio_id)) {
        $mensaje = 'Medio eliminado correctamente';
        $tipo_mensaje = 'success';
    } else {
        $mensaje = 'Error al eliminar el medio';
        $tipo_mensaje = 'error';
    }
    // Redirección para limpiar la URL
    echo "<script>window.location.href='galeria.php?mensaje=" . urlencode($mensaje) . "&tipo=" . $tipo_mensaje . "';</script>";
    exit;
}

// Mensajes de URL (post-redirección)
if (isset($_GET['mensaje'])) {
    $mensaje = $_GET['mensaje'];
    $tipo_mensaje = $_GET['tipo'] ?? 'info';
}

// 3. PROCESAR FORMULARIO (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_medio = isset($_POST['id_medio']) ? (int)$_POST['id_medio'] : 0;
    
    // Recoger y limpiar datos
    $titulo = sanitizar($_POST['titulo'] ?? '');
    $descripcion = sanitizar($_POST['descripcion'] ?? '');
    $tipo = sanitizar($_POST['tipo'] ?? '');
    $categoria_id = sanitizar_int($_POST['categoria_id'] ?? 0);
    $ubicacion = sanitizar($_POST['ubicacion'] ?? '');
    $fecha_media = $_POST['fecha_media'] ?? date('Y-m-d');
    
    // Datos de miniatura generada por JS (si es video)
    $video_thumbnail = isset($_POST['video_thumbnail']) ? $_POST['video_thumbnail'] : null;
    
    // Archivo (opcional en edición)
    $archivo = isset($_FILES['archivo']) ? $_FILES['archivo'] : null;
    $hay_archivo = ($archivo && $archivo['error'] === UPLOAD_ERR_OK);

    // Validaciones
    if (empty($titulo) || empty($tipo) || $categoria_id == 0) {
        $mensaje = 'Por favor completa Título, Tipo y Categoría.';
        $tipo_mensaje = 'error';
        if ($id_medio > 0) $modo_edicion = true; // Mantener modo edición si hay error
    } 
    elseif ($id_medio == 0 && !$hay_archivo) {
        $mensaje = 'Debes subir un archivo para crear un nuevo medio.';
        $tipo_mensaje = 'error';
    } 
    else {
        // Validar extensión si hay archivo
        $ext_valida = true;
        if ($hay_archivo) {
            $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
            $permitidos = ($tipo === 'video') ? ['mp4', 'mov', 'avi'] : ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($ext, $permitidos)) {
                $ext_valida = false;
                $mensaje = 'Formato de archivo no válido para ' . $tipo;
                $tipo_mensaje = 'error';
                if ($id_medio > 0) $modo_edicion = true;
            }
        }

        if ($ext_valida) {
            $exito = false;
            
            if ($id_medio > 0) {
                // --- ACTUALIZAR ---
                $tmp = $hay_archivo ? $archivo['tmp_name'] : null;
                $name = $hay_archivo ? $archivo['name'] : null;
                
                // Pasamos también la miniatura si se generó una nueva
                $exito = $medio_model->actualizar($id_medio, $titulo, $descripcion, $tipo, $categoria_id, $ubicacion, $fecha_media, $tmp, $name, $video_thumbnail);
                $msg_ok = "Medio actualizado correctamente";
            } else {
                // --- CREAR ---
                $medio_model->titulo = $titulo;
                $medio_model->descripcion = $descripcion;
                $medio_model->tipo = $tipo;
                $medio_model->categoria_id = $categoria_id;
                $medio_model->ubicacion = $ubicacion;
                $medio_model->fecha_media = $fecha_media;
                
                // Pasamos la miniatura generada al modelo
                $exito = $medio_model->subirMedio($archivo['tmp_name'], $archivo['name'], $tipo, $video_thumbnail);
                $msg_ok = "Medio subido correctamente";
            }

            if ($exito) {
                echo "<script>window.location.href='galeria.php?mensaje=" . urlencode($msg_ok) . "&tipo=success';</script>";
                exit;
            } else {
                $mensaje = 'Error al guardar en la base de datos.';
                $tipo_mensaje = 'error';
                if ($id_medio > 0) $modo_edicion = true;
            }
        }
    }
}

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
            <!-- Pestaña inteligente: Cambia nombre y se activa si editamos -->
            <button class="tab-button <?php echo $modo_edicion ? 'active' : 'active'; ?>" data-tab="subir">
                <?php echo $modo_edicion ? 'Editar Medio' : 'Subir Medio'; ?>
            </button>
            <button class="tab-button" data-tab="listar">Medios Existentes</button>
        </div>

        <!-- PESTAÑA 1: FORMULARIO (CREAR / EDITAR) -->
        <div class="tab-content active" id="subir">
            <div class="upload-form-container">
                <div class="section-actions">
                    <h3 class="section-title"><?php echo $titulo_formulario; ?></h3>
                    <?php if ($modo_edicion): ?>
                        <a href="galeria.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    <?php endif; ?>
                </div>

                <form method="POST" enctype="multipart/form-data" class="media-form" id="mediaForm">
                    <!-- ID OCULTO: Crucial para que sepa cuál actualizar -->
                    <input type="hidden" name="id_medio" value="<?php echo $modo_edicion ? $medio_editar['id'] : 0; ?>">
                    
                    <!-- INPUT HIDDEN PARA LA MINIATURA GENERADA (BASE64) -->
                    <input type="hidden" name="video_thumbnail" id="video_thumbnail">

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="titulo" class="form-label">Título *</label>
                            <input type="text" id="titulo" name="titulo" class="form-control" 
                                   placeholder="Ej: Gallo Giro" required
                                   value="<?php echo $modo_edicion ? htmlspecialchars($medio_editar['titulo']) : (isset($_POST['titulo']) ? htmlspecialchars($_POST['titulo']) : ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="tipo" class="form-label">Tipo *</label>
                            <select id="tipo" name="tipo" class="form-control select" required>
                                <option value="">Seleccionar...</option>
                                <option value="foto" <?php echo ($modo_edicion && $medio_editar['tipo'] == 'foto') ? 'selected' : ''; ?>>Foto</option>
                                <option value="video" <?php echo ($modo_edicion && $medio_editar['tipo'] == 'video') ? 'selected' : ''; ?>>Video</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="categoria_id" class="form-label">Categoría *</label>
                            <select id="categoria_id" name="categoria_id" class="form-control select" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo ($modo_edicion && $medio_editar['categoria_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="fecha_media" class="form-label">Fecha *</label>
                            <input type="date" id="fecha_media" name="fecha_media" class="form-control" required
                                   value="<?php echo $modo_edicion ? $medio_editar['fecha_media'] : date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea id="descripcion" name="descripcion" class="form-control textarea" rows="3" 
                                  placeholder="Detalles del medio..."><?php echo $modo_edicion ? htmlspecialchars($medio_editar['descripcion']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="ubicacion" class="form-label">Ubicación</label>
                        <input type="text" id="ubicacion" name="ubicacion" class="form-control" 
                               placeholder="Ej: Rancho Las Trojes"
                               value="<?php echo $modo_edicion ? htmlspecialchars($medio_editar['ubicacion']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="archivo" class="form-label">
                            <?php echo $modo_edicion ? 'Reemplazar Archivo (Opcional)' : 'Archivo *'; ?>
                        </label>

                        <!-- VISTA PREVIA EN MODO EDICIÓN -->
                        <?php if ($modo_edicion): ?>
                            <div style="margin-bottom:1rem; padding:10px; background:#f9f9f9; border-radius:8px; display:inline-block;">
                                <small style="display:block; margin-bottom:5px; color:#666;">Archivo actual:</small>
                                <?php if ($medio_editar['tipo'] == 'foto'): ?>
                                    <img src="../<?php echo $medio_editar['ruta_archivo']; ?>" style="height:80px; object-fit:cover; border-radius:4px;">
                                <?php else: ?>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <!-- Intentar mostrar la miniatura si existe -->
                                        <?php 
                                            $thumb_path = str_replace('/videos/', '/videos/thumbs/', str_replace('.mp4', '.jpg', $medio_editar['ruta_archivo']));
                                            // Verificamos visualmente asumiendo la convención, si no, fallback al icono
                                        ?>
                                        <img src="../<?php echo $thumb_path; ?>" style="height:80px; object-fit:cover; border-radius:4px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                                        <div style="display:none; align-items:center; gap:10px;">
                                            <i class="fas fa-video" style="font-size:2rem; color:var(--brown);"></i>
                                            <span>Video cargado</span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <input type="file" id="archivo" name="archivo" class="form-control" 
                               <?php echo $modo_edicion ? '' : 'required'; ?>
                               accept=".jpg,.jpeg,.png,.gif,.webp,.mp4,.mov,.avi">
                        <div class="form-text" id="file-help">JPG, PNG, MP4, MOV. Máx: 50MB</div>
                        
                        <!-- Contenedor oculto para procesar miniatura de video -->
                        <div id="video-processor" style="display:none;">
                            <video id="video-temp" style="width:300px; margin-top:10px;" controls muted></video>
                            <p id="video-msg" style="font-size:0.8rem; color:var(--brown);">Generando miniatura...</p>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn <?php echo $clase_boton; ?>" id="submit-btn">
                        <i class="fas <?php echo $icono_boton; ?>"></i> <?php echo $texto_boton; ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- PESTAÑA 2: LISTADO -->
        <div class="tab-content" id="listar">
            <div class="list-section">
                <div class="section-card">
                    <h3 class="section-title">Medios Existentes</h3>
                    <?php if (empty($medios)): ?>
                        <div class="empty-state">
                            <i class="fas fa-images"></i>
                            <p>No hay medios en la galería</p>
                        </div>
                    <?php else: ?>
                        <div class="media-grid-admin">
                            <?php foreach ($medios as $med): ?>
                            <div class="media-item-admin">
                                <div class="media-preview">
                                    <?php if ($med['tipo'] === 'foto'): ?>
                                        <img src="../<?php echo $med['ruta_archivo']; ?>" alt="<?php echo htmlspecialchars($med['titulo'] ?? ''); ?>">
                                    <?php else: ?>
                                        <!-- INTELIGENCIA: Usar la miniatura si es video -->
                                        <?php 
                                            $thumbnail = isset($med['thumbnail']) && !empty($med['thumbnail']) 
                                                ? "../" . $med['thumbnail'] 
                                                : null; 
                                        ?>
                                        <?php if ($thumbnail): ?>
                                             <img src="<?php echo $thumbnail; ?>" alt="Miniatura de video">
                                             <div class="video-overlay-icon" style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); color:white; text-shadow:0 2px 4px rgba(0,0,0,0.5);">
                                                <i class="fas fa-play-circle" style="font-size: 3rem;"></i>
                                             </div>
                                        <?php else: ?>
                                            <div class="video-preview">
                                                <i class="fas fa-play-circle" style="font-size: 3rem;"></i>
                                                <span>Video</span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="media-info-admin">
                                    <h4><?php echo htmlspecialchars($med['titulo'] ?? 'Sin título'); ?></h4>
                                    <p class="media-desc">
                                        <?php 
                                        $desc = $med['descripcion'] ?? '';
                                        echo htmlspecialchars(substr($desc, 0, 80)) . (strlen($desc) > 80 ? '...' : ''); 
                                        ?>
                                    </p>
                                    <div class="media-meta">
                                        <span class="media-type"><?php echo ucfirst($med['tipo']); ?></span>
                                        <span class="media-category"><?php echo htmlspecialchars($med['categoria_nombre'] ?? 'Sin categoría'); ?></span>
                                    </div>
                                </div>
                                
                                <div class="media-actions">
                                    <span class="likes-count">
                                        <?php echo $med['likes']; ?> <i class="fas fa-heart" style="color:#ff3040;"></i>
                                    </span>
                                    <div class="action-buttons">
                                        <!-- BOTÓN EDITAR -->
                                        <a href="galeria.php?editar=<?php echo $med['id']; ?>" class="btn btn-sm btn-secondary" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <!-- BOTÓN ELIMINAR -->
                                        <a href="galeria.php?eliminar=<?php echo $med['id']; ?>" 
                                           class="btn btn-sm btn-danger" title="Eliminar"
                                           onclick="return confirm('¿Estás seguro? Esto borrará el archivo y los datos permanentemente.')">
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
    // Lógica de pestañas
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    function switchTab(tabId) {
        tabButtons.forEach(btn => {
            btn.classList.remove('active');
            if(btn.dataset.tab === tabId) btn.classList.add('active');
        });
        tabContents.forEach(content => {
            content.classList.remove('active');
            if(content.id === tabId) content.classList.add('active');
        });
    }

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            switchTab(this.dataset.tab);
        });
    });

    // FORZAR PESTAÑA 'SUBIR' SI ESTAMOS EDITANDO
    <?php if ($modo_edicion): ?>
        switchTab('subir');
        const formContainer = document.querySelector('.upload-form-container');
        if(formContainer) formContainer.scrollIntoView({ behavior: 'smooth' });
    <?php endif; ?>
    
    // LOGICA DE MINIATURAS DE VIDEO
    const tipoSelect = document.getElementById('tipo');
    const archivoInput = document.getElementById('archivo');
    const fileHelp = document.getElementById('file-help');
    const videoProcessor = document.getElementById('video-processor');
    const videoTemp = document.getElementById('video-temp');
    const videoMsg = document.getElementById('video-msg');
    const hiddenThumbInput = document.getElementById('video_thumbnail');
    
    if(tipoSelect && archivoInput) {
        // Cambio dinámico de tipos de archivo permitidos
        tipoSelect.addEventListener('change', function() {
            if (this.value === 'video') {
                archivoInput.setAttribute('accept', '.mp4,.mov,.avi');
                fileHelp.textContent = 'MP4, MOV, AVI. Máx: 50MB. Se generará una miniatura automáticamente.';
                videoProcessor.style.display = 'block';
            } else {
                archivoInput.setAttribute('accept', '.jpg,.jpeg,.png,.gif,.webp');
                fileHelp.textContent = 'JPG, PNG, GIF, WebP. Máx: 50MB';
                videoProcessor.style.display = 'none';
                hiddenThumbInput.value = ''; // Limpiar si cambiamos a foto
            }
        });

        // Generación de miniatura al seleccionar video
        archivoInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file && file.type.startsWith('video/')) {
                videoMsg.textContent = "Generando miniatura...";
                videoMsg.style.color = "var(--brown)";
                
                const fileURL = URL.createObjectURL(file);
                videoTemp.src = fileURL;
                videoTemp.style.display = 'block';
                
                // Capturar frame al segundo 1.0
                videoTemp.currentTime = 1.0;
                
                videoTemp.onloadeddata = function() {
                    videoTemp.currentTime = 1.0; 
                };

                videoTemp.onseeked = function() {
                    // Crear canvas para dibujar el frame
                    const canvas = document.createElement('canvas');
                    canvas.width = videoTemp.videoWidth;
                    canvas.height = videoTemp.videoHeight;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(videoTemp, 0, 0, canvas.width, canvas.height);
                    
                    // Convertir a base64 (JPEG calidad 0.7)
                    const dataURL = canvas.toDataURL('image/jpeg', 0.7);
                    hiddenThumbInput.value = dataURL;
                    
                    videoMsg.textContent = "¡Miniatura generada correctamente!";
                    videoMsg.style.color = "var(--success)";
                    
                    // Limpiar memoria
                    // URL.revokeObjectURL(fileURL); // Opcional, cuidado si el usuario quiere previsualizar
                };
                
                videoTemp.onerror = function() {
                    videoMsg.textContent = "No se pudo generar la miniatura automáticamente.";
                    videoMsg.style.color = "var(--error)";
                };
            }
        });
    }
});
</script>
<?php include_once 'includes/footer.php'; ?>