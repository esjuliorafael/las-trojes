<?php
include_once 'includes/header.php';
include_once '../config/database.php';
include_once '../models/usuario.php';

$database = new Database();
$db = $database->getConnection();
$usuario = new Usuario($db);

$mensaje = '';
$tipo_mensaje = '';

// Crear nuevo usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_usuario'])) {
    $username = sanitizar($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $nombre = sanitizar($_POST['nombre'] ?? '');
    $email = sanitizar_email($_POST['email'] ?? '');
    
    // Validaciones
    if (empty($username) || empty($password) || empty($nombre) || empty($email)) {
        $mensaje = 'Todos los campos son obligatorios';
        $tipo_mensaje = 'error';
    } elseif ($password !== $confirm_password) {
        $mensaje = 'Las contraseñas no coinciden';
        $tipo_mensaje = 'error';
    } elseif (strlen($password) < 6) {
        $mensaje = 'La contraseña debe tener al menos 6 caracteres';
        $tipo_mensaje = 'error';
    } elseif ($email === false) {
        $mensaje = 'El email no es válido';
        $tipo_mensaje = 'error';
    } else {
        if ($usuario->crearUsuario($username, $password, $nombre, $email)) {
            $mensaje = 'Usuario creado correctamente';
            $tipo_mensaje = 'success';
            $_POST = array();
        } else {
            $mensaje = 'Error al crear el usuario. El nombre de usuario o email ya existen.';
            $tipo_mensaje = 'error';
        }
    }
}

// Cambiar contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_password'])) {
    $usuario_id = (int)$_POST['usuario_id'];
    $nueva_password = $_POST['nueva_password'];
    $confirmar_password = $_POST['confirmar_password'];
    
    if ($nueva_password !== $confirmar_password) {
        $mensaje = 'Las contraseñas no coinciden';
        $tipo_mensaje = 'error';
    } elseif (strlen($nueva_password) < 6) {
        $mensaje = 'La contraseña debe tener al menos 6 caracteres';
        $tipo_mensaje = 'error';
    } else {
        if ($usuario->cambiarPassword($usuario_id, $nueva_password)) {
            $mensaje = 'Contraseña actualizada correctamente';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al actualizar la contraseña';
            $tipo_mensaje = 'error';
        }
    }
}

// Desactivar usuario
if (isset($_GET['desactivar'])) {
    $usuario_id = (int)$_GET['desactivar'];
    if ($usuario->desactivar($usuario_id)) {
        $mensaje = 'Usuario desactivado correctamente';
        $tipo_mensaje = 'success';
    } else {
        $mensaje = 'Error al desactivar el usuario';
        $tipo_mensaje = 'error';
    }
}

// Activar usuario
if (isset($_GET['activar'])) {
    $usuario_id = (int)$_GET['activar'];
    if ($usuario->activar($usuario_id)) {
        $mensaje = 'Usuario activado correctamente';
        $tipo_mensaje = 'success';
    } else {
        $mensaje = 'Error al activar el usuario';
        $tipo_mensaje = 'error';
    }
}

// Eliminar usuario
if (isset($_GET['eliminar'])) {
    $usuario_id = (int)$_GET['eliminar'];
    if ($usuario->eliminar($usuario_id)) {
        $mensaje = 'Usuario eliminado correctamente';
        $tipo_mensaje = 'success';
    } else {
        $mensaje = 'Error al eliminar el usuario';
        $tipo_mensaje = 'error';
    }
}

$usuarios = $usuario->obtenerTodos();
?>
<div class="users-management">
    <div class="page-header">
        <h1>Gestión de Usuarios</h1>
        <p>Administra los usuarios del sistema</p>
    </div>

    <?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $tipo_mensaje; ?>">
        <?php echo $mensaje; ?>
    </div>
    <?php endif; ?>

    <div class="management-tabs">
        <div class="tabs">
            <button class="tab-button active" data-tab="crear">Crear Usuario</button>
            <button class="tab-button" data-tab="listar">Usuarios Existentes</button>
            <button class="tab-button" data-tab="cambiar-password">Cambiar Contraseña</button>
        </div>

        <div class="tab-content active" id="crear">
            <div class="form-section">
                <div class="section-card">
                    <h3>Crear Nuevo Usuario</h3>
                    <form method="POST" class="user-form">
                        <input type="hidden" name="crear_usuario" value="1">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="username" class="form-label">Nombre de Usuario *</label>
                                <input type="text" id="username" name="username" class="form-control" 
                                    pattern="[a-zA-Z0-9_]+" title="Solo letras, números y guiones bajos"
                                    placeholder="Ej: juan_perez" required
                                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                <div class="form-text">Caracteres permitidos: letras, números y _</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="nombre" class="form-label">Nombre Completo *</label>
                                <input type="text" id="nombre" name="nombre" class="form-control" 
                                    placeholder="Ej: Juan Pérez García" required
                                    value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                    placeholder="Ej: juan@rancholastrojes.com" required
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="password" class="form-label">Contraseña *</label>
                                <input type="password" id="password" name="password" class="form-control" 
                                    placeholder="Mínimo 6 caracteres" required minlength="6">
                                <div class="form-text">Mínimo 6 caracteres</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password" class="form-label">Confirmar Contraseña *</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                    placeholder="Repite la contraseña" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Crear Usuario
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="tab-content" id="listar">
            <div class="list-section">
                <div class="section-card">
                    <h3>Usuarios del Sistema</h3>
                    
                    <?php if (empty($usuarios)): ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <p>No hay usuarios registrados en el sistema</p>
                        </div>
                    <?php else: ?>
                    <div class="users-list">
                        <?php foreach ($usuarios as $user): ?>
                        <div class="user-item <?php echo !$user['activo'] ? 'inactive' : ''; ?>">
                            <div class="user-avatar-large">
                                <?php echo strtoupper(substr($user['nombre'], 0, 1)); ?>
                            </div>
                            
                            <div class="user-details">
                                <h4><?php echo htmlspecialchars($user['nombre']); ?></h4>
                                <div class="user-meta">
                                    <span class="username">@<?php echo htmlspecialchars($user['username']); ?></span>
                                    <span class="user-email"><?php echo htmlspecialchars($user['email']); ?></span>
                                    <span class="user-date">Creado: <?php echo date('d/m/Y', strtotime($user['fecha_creacion'])); ?></span>
                                </div>
                            </div>
                            
                            <div class="user-status">
                                <span class="status-badge <?php echo $user['activo'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $user['activo'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </div>
                            
                            <div class="user-actions">
                                <?php if ($user['activo']): ?>
                                    <?php if ($user['id'] != $_SESSION['usuario_id']): ?>
                                        <a href="usuarios.php?desactivar=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-warning"
                                           onclick="return confirm('¿Estás seguro de desactivar este usuario?')">
                                            <i class="fas fa-user-slash"></i> Desactivar
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="usuarios.php?activar=<?php echo $user['id']; ?>" 
                                       class="btn btn-sm btn-success">
                                        <i class="fas fa-user-check"></i> Activar
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($user['id'] != $_SESSION['usuario_id']): ?>
                                    <a href="usuarios.php?eliminar=<?php echo $user['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.')">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </a>
                                <?php else: ?>
                                    <span class="current-user-badge">Tú</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="tab-content" id="cambiar-password">
            <div class="form-section">
                <div class="section-card">
                    <h3>Cambiar Contraseña</h3>
                    <form method="POST" class="password-form">
                        <input type="hidden" name="cambiar_password" value="1">
                        
                        <div class="form-group">
                            <label for="usuario_id" class="form-label">Seleccionar Usuario *</label>
                            <select id="usuario_id" name="usuario_id" class="form-control select" required>
                                <option value="">Selecciona un usuario...</option>
                                <?php foreach ($usuarios as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['nombre']); ?> (<?php echo htmlspecialchars($user['username']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="nueva_password" class="form-label">Nueva Contraseña *</label>
                                <input type="password" id="nueva_password" name="nueva_password" class="form-control" 
                                    placeholder="Mínimo 6 caracteres" required minlength="6">
                                <div class="form-text">Mínimo 6 caracteres</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirmar_password" class="form-label">Confirmar Contraseña *</label>
                                <input type="password" id="confirmar_password" name="confirmar_password" class="form-control" 
                                    placeholder="Repite la nueva contraseña" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-key"></i> Cambiar Contraseña
                        </button>
                    </form>
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
    
    // Validación de contraseñas en tiempo real para crear usuario
    const userForm = document.querySelector('.user-form');
    if (userForm) {
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        
        function validatePasswords() {
            if (password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Las contraseñas no coinciden');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }
        
        password.addEventListener('input', validatePasswords);
        confirmPassword.addEventListener('input', validatePasswords);
    }
    
    // Validación para cambiar contraseña
    const changePasswordForm = document.querySelector('.password-form');
    if (changePasswordForm) {
        const nuevaPassword = document.getElementById('nueva_password');
        const confirmarPassword = document.getElementById('confirmar_password');
        
        function validateChangePasswords() {
            if (nuevaPassword.value !== confirmarPassword.value) {
                confirmarPassword.setCustomValidity('Las contraseñas no coinciden');
            } else {
                confirmarPassword.setCustomValidity('');
            }
        }
        
        nuevaPassword.addEventListener('input', validateChangePasswords);
        confirmarPassword.addEventListener('input', validateChangePasswords);
    }
    
    // Mostrar/ocultar contraseñas
    document.querySelectorAll('input[type="password"]').forEach(input => {
        const toggle = document.createElement('span');
        toggle.className = 'password-toggle-form';
        toggle.innerHTML = '<i class="fas fa-eye"></i>';
        toggle.style.cssText = 'position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #666; z-index: 10;';
        
        input.parentNode.style.position = 'relative';
        input.parentNode.appendChild(toggle);
        
        toggle.addEventListener('click', function() {
            const type = input.type === 'password' ? 'text' : 'password';
            input.type = type;
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    });
});
</script>
<?php include_once 'includes/footer.php'; ?>