<?php
session_start();
include_once '../config/database.php';
include_once '../models/Usuario.php';

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $usuario = new Usuario($db);
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if ($usuario->login($username, $password)) {
        $_SESSION['usuario_id'] = $usuario->id;
        $_SESSION['usuario_nombre'] = $usuario->nombre;
        $_SESSION['usuario_username'] = $usuario->username;
        $_SESSION['logged_in'] = true;
        
        // Si seleccionó "Recordarme", extender la sesión
        if ($remember) {
            $_SESSION['remember_me'] = true;
        }
        
        header('Location: index.php');
        exit;
    } else {
        $error = "Usuario o contraseña incorrectos";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rancho Las Trojes</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Lora:wght@600&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/ca7b3ded48.js" crossorigin="anonymous"></script>
    
    <style>
        :root {
            --white: #ffffff;
            --off-white: #f3efeb;
            --off-white-light: #f9f7f5;
            --brown: #8b5e3c;
            --text-color: #575757;
            --black-blue: #121820;
            --divider-color: #e0e0e0;
            --success: #10b981;
            --error: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, var(--off-white) 0%, var(--off-white-light) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-container {
            background: var(--white);
            border-radius: 1.5rem;
            box-shadow: 0 20px 60px rgba(139, 94, 60, 0.15);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
            display: flex;
            min-height: 600px;
        }

        .login-left {
            flex: 1;
            background: linear-gradient(135deg, var(--brown) 0%, var(--black-blue) 100%);
            color: var(--white);
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-left::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml;utf8,<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="smallGrid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23smallGrid)"/></svg>');
            opacity: 0.3;
        }

        .login-logo {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 1;
        }

        .login-logo i {
            font-size: 3rem;
            color: var(--white);
        }

        .login-title {
            font-family: 'Lora', serif;
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }

        .login-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
            position: relative;
            z-index: 1;
        }

        .login-right {
            flex: 1;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-form-title {
            font-size: 2rem;
            font-weight: 600;
            color: var(--black-blue);
            margin-bottom: 0.5rem;
        }

        .login-form-subtitle {
            color: var(--text-color);
            margin-bottom: 2.5rem;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--black-blue);
            font-size: 0.9rem;
        }

        .form-input-container {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid var(--divider-color);
            border-radius: 0.75rem;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            background: var(--off-white-light);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--brown);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(139, 94, 60, 0.1);
        }

        .form-input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-color);
            font-size: 1rem;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-color);
            cursor: pointer;
            font-size: 1rem;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--brown);
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--brown);
        }

        .remember-me label {
            color: var(--text-color);
            font-size: 0.9rem;
            cursor: pointer;
        }

        .forgot-password {
            color: var(--brown);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .forgot-password:hover {
            color: var(--black-blue);
        }

        .login-button {
            width: 100%;
            padding: 1rem;
            background: var(--brown);
            color: var(--white);
            border: none;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .login-button:hover {
            background: var(--black-blue);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(139, 94, 60, 0.3);
        }

        .login-button:disabled {
            background: var(--divider-color);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid var(--white);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 0.5rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error);
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--error);
            font-size: 0.9rem;
            display: none;
        }

        .success-message {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--success);
            font-size: 0.9rem;
            display: none;
        }

        .login-footer {
            text-align: center;
            margin-top: 2rem;
            color: var(--text-color);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                margin: 1rem;
                min-height: auto;
            }

            .login-left {
                padding: 2rem;
                min-height: 250px;
            }

            .login-logo {
                width: 80px;
                height: 80px;
            }

            .login-logo i {
                font-size: 2rem;
            }

            .login-title {
                font-size: 1.8rem;
            }

            .login-right {
                padding: 2rem;
            }

            .login-form-title {
                font-size: 1.5rem;
            }

            .remember-forgot {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 1rem;
            }

            .login-container {
                margin: 0;
            }

            .login-left, .login-right {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="login-logo">
                <i class="fas fa-horse-head"></i>
            </div>
            <h1 class="login-title">Rancho Las Trojes</h1>
            <p class="login-subtitle">
                Sistema de administración para gestionar el contenido de tu sitio web de manera profesional.
            </p>
        </div>
        
        <div class="login-right">
            <h2 class="login-form-title">Iniciar Sesión</h2>
            <p class="login-form-subtitle">Accede a tu panel de administración</p>
            
            <?php if ($error): ?>
            <div class="error-message" id="errorMessage" style="display: block;">
                <?php echo $error; ?>
            </div>
            <?php else: ?>
            <div class="error-message" id="errorMessage"></div>
            <?php endif; ?>
            
            <div class="success-message" id="successMessage"></div>
            
            <form id="loginForm" method="POST" action="">
                <div class="form-group">
                    <label for="username" class="form-label">Usuario</label>
                    <div class="form-input-container">
                        <i class="fas fa-user form-input-icon"></i>
                        <input type="text" id="username" name="username" class="form-input" 
                               placeholder="Ingresa tu usuario" required 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="form-input-container">
                        <i class="fas fa-lock form-input-icon"></i>
                        <input type="password" id="password" name="password" class="form-input" 
                               placeholder="Ingresa tu contraseña" required>
                        <i class="fas fa-eye password-toggle" id="passwordToggle"></i>
                    </div>
                </div>
                
                <div class="remember-forgot">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember" 
                               <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                        <label for="remember">Recordarme</label>
                    </div>
                    <a href="#" class="forgot-password">¿Olvidaste tu contraseña?</a>
                </div>
                
                <button type="submit" class="login-button" id="loginButton">
                    <div class="loading-spinner" id="loadingSpinner"></div>
                    <span id="buttonText">Iniciar Sesión</span>
                </button>
            </form>
            
            <div class="login-footer">
                <p>&copy; 2025 Rancho Las Trojes - Sistema Administrativo</p>
            </div>
        </div>
    </div>

    <script>
        class LoginManager {
            constructor() {
                this.form = document.getElementById('loginForm');
                this.usernameInput = document.getElementById('username');
                this.passwordInput = document.getElementById('password');
                this.rememberCheckbox = document.getElementById('remember');
                this.loginButton = document.getElementById('loginButton');
                this.loadingSpinner = document.getElementById('loadingSpinner');
                this.buttonText = document.getElementById('buttonText');
                this.passwordToggle = document.getElementById('passwordToggle');
                this.errorMessage = document.getElementById('errorMessage');
                this.successMessage = document.getElementById('successMessage');
                
                this.init();
            }

            init() {
                this.bindEvents();
                this.checkRememberedUser();
            }

            bindEvents() {
                // El formulario ya se envía tradicionalmente por PHP
                // Mantenemos la validación frontend
                this.form.addEventListener('submit', (e) => this.handleSubmit(e));
                this.passwordToggle.addEventListener('click', () => this.togglePassword());
                
                // Clear errors when typing
                this.usernameInput.addEventListener('input', () => this.clearMessages());
                this.passwordInput.addEventListener('input', () => this.clearMessages());
            }

            togglePassword() {
                const type = this.passwordInput.type === 'password' ? 'text' : 'password';
                this.passwordInput.type = type;
                
                this.passwordToggle.className = type === 'password' 
                    ? 'fas fa-eye password-toggle' 
                    : 'fas fa-eye-slash password-toggle';
            }

            showLoading(show) {
                this.loginButton.disabled = show;
                this.loadingSpinner.style.display = show ? 'inline-block' : 'none';
                this.buttonText.textContent = show ? 'Iniciando sesión...' : 'Iniciar Sesión';
            }

            showError(message) {
                this.errorMessage.textContent = message;
                this.errorMessage.style.display = 'block';
                this.successMessage.style.display = 'none';
            }

            showSuccess(message) {
                this.successMessage.textContent = message;
                this.successMessage.style.display = 'block';
                this.errorMessage.style.display = 'none';
            }

            clearMessages() {
                this.errorMessage.style.display = 'none';
                this.successMessage.style.display = 'none';
            }

            checkRememberedUser() {
                // Podemos implementar cookies para "Recordarme" más adelante
                // Por ahora usamos session PHP estándar
            }

            handleSubmit(e) {
                const username = this.usernameInput.value.trim();
                const password = this.passwordInput.value;
                
                if (!username || !password) {
                    e.preventDefault();
                    this.showError('Por favor completa todos los campos');
                    return;
                }

                // Mostrar loading state
                this.showLoading(true);
                
                // El formulario se enviará tradicionalmente
                // El loading se quitará cuando la página recargue
            }
        }

        // Initialize login manager when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new LoginManager();
        });
    </script>
</body>
</html>