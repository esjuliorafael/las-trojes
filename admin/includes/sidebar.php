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
            <div class="menu-group-title">Tienda</div>
            <a href="tienda.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'tienda.php' ? 'active' : ''; ?>">
                <i class="fas fa-store"></i>
                Productos
            </a>
            <a href="ordenes.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'categorias.php' ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i>
                Órdenes
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
            <a href="envios.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'envios.php' ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i>
                Envíos
            </a>
        </div>
    </div>
</nav>