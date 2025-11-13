<aside class="admin-sidebar">
    <nav>
        <ul class="sidebar-nav">
            <li>
                <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="logo.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'logo.php' ? 'active' : ''; ?>">
                    <i class="fas fa-image"></i>
                    <span>Gestión de Logo</span>
                </a>
            </li>
            <li>
                <a href="categorias.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'categorias.php' ? 'active' : ''; ?>">
                    <i class="fas fa-folder"></i>
                    <span>Categorías</span>
                </a>
            </li>
            <li>
                <a href="galeria.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'galeria.php' ? 'active' : ''; ?>">
                    <i class="fas fa-images"></i>
                    <span>Galería</span>
                </a>
            </li>
            <li>
                <a href="usuarios.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Usuarios</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>