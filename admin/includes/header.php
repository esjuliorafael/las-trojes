<?php
session_start();

// Incluir funciones helper
require_once 'functions.php';

// Verificar sesión
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Rancho Las Trojes</title>
    <link rel="stylesheet" href="./css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Lora:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="dashboard-container">
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-horse-head"></i>
            </div>
            <h2 class="sidebar-title">Dashboard</h2>
            <p class="sidebar-subtitle">Rancho Las Trojes</p>
        </div>
        
        <div class="sidebar-menu">
            <div class="menu-group">
                <div class="menu-group-title">Principal</div>
                <a href="index.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    Resumen
                </a>
            </div>
            
            <div class="menu-group">
                <div class="menu-group-title">Galería</div>
                <a href="galeria.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'galeria.php' ? 'active' : ''; ?>">
                    <i class="fas fa-images"></i>
                    Publicaciones
                </a>
                <a href="categorias.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'categorias.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tags"></i>
                    Categorías
                </a>
            </div>
            
            <div class="menu-group">
                <div class="menu-group-title">Diseño</div>
                <a href="logo.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'logo.php' ? 'active' : ''; ?>">
                    <i class="fas fa-image"></i>
                    Logo
                </a>
            </div>
            
            <div class="menu-group">
                <div class="menu-group-title">Sistema</div>
                <a href="usuarios.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    Usuarios
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <div class="content-header">
            <h1 class="page-title">
                <?php
                $titles = [
                    'index.php' => 'Resumen',
                    'galeria.php' => 'Publicaciones de Galería',
                    'categorias.php' => 'Categorías de Galería',
                    'logo.php' => 'Logo Principal',
                    'usuarios.php' => 'Usuarios del Sistema'
                ];
                echo $titles[basename($_SERVER['PHP_SELF'])] ?? 'Dashboard';
                ?>
            </h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?php 
                    $nombre = $_SESSION['usuario_nombre'] ?? 'Admin';
                    echo strtoupper(substr($nombre, 0, 1)); 
                    ?>
                </div>
                <div>
                    <div style="font-weight: 600;"><?php echo htmlspecialchars($nombre); ?></div>
                    <div style="color: var(--text-color); font-size: 0.875rem;">Administrador</div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Salir
                </a>
            </div>
        </div>