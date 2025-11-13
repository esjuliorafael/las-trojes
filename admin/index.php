<?php
include_once 'includes/header.php';
include_once '../config/database.php';
include_once '../models/medio.php';
include_once '../models/categoria.php';
include_once '../models/usuario.php';

$database = new Database();
$db = $database->getConnection();

$medio = new Medio($db);
$categoria = new Categoria($db);
$usuario = new Usuario($db);

// Estadísticas
$total_medios = $medio->contarTotal();
$total_categorias = $categoria->contarTotal();
$total_usuarios = $usuario->contarTotal();
$medios_recientes = $medio->obtenerRecientes(5);
?>
<div class="dashboard-overview">
    <div class="stats-grid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-images" style="color: var(--brown);"></i>
                    Medios en Galería
                </h3>
            </div>
            <div style="text-align: center; padding: 2rem;">
                <div style="font-size: 3rem; font-weight: 700; color: var(--brown);"><?php echo $total_medios; ?></div>
                <p style="color: var(--text-color); margin-top: 0.5rem;">Total de fotos y videos</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-folder" style="color: var(--brown);"></i>
                    Categorías
                </h3>
            </div>
            <div style="text-align: center; padding: 2rem;">
                <div style="font-size: 3rem; font-weight: 700; color: var(--brown);"><?php echo $total_categorias; ?></div>
                <p style="color: var(--text-color); margin-top: 0.5rem;">Categorías activas</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-users" style="color: var(--brown);"></i>
                    Usuarios
                </h3>
            </div>
            <div style="text-align: center; padding: 2rem;">
                <div style="font-size: 3rem; font-weight: 700; color: var(--brown);"><?php echo $total_usuarios; ?></div>
                <p style="color: var(--text-color); margin-top: 0.5rem;">Usuarios del sistema</p>
            </div>
        </div>
    </div>

    <div class="form-grid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-clock" style="color: var(--brown);"></i>
                    Medios Recientes
                </h3>
                <a href="galeria.php" class="btn btn-secondary">Ver Todos</a>
            </div>
            
            <?php if (empty($medios_recientes)): ?>
                <div class="empty-state">
                    <i class="fas fa-images"></i>
                    <p>No hay medios en la galería</p>
                    <a href="galeria.php" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-plus"></i> Agregar Primer Medio
                    </a>
                </div>
            <?php else: ?>
                <div class="posts-grid">
                    <?php foreach ($medios_recientes as $medio_item): ?>
                    <div class="post-card">
                        <?php if ($medio_item['tipo'] === 'foto'): ?>
                            <img src="<?php echo $medio_item['ruta_archivo']; ?>" alt="<?php echo htmlspecialchars($medio_item['titulo']); ?>" class="post-image">
                        <?php else: ?>
                            <div style="width: 100%; height: 200px; background: linear-gradient(135deg, var(--brown), var(--black-blue)); display: flex; align-items: center; justify-content: center; color: white;">
                                <i class="fas fa-play-circle" style="font-size: 3rem;"></i>
                            </div>
                        <?php endif; ?>
                        <div class="post-content">
                            <h4 class="post-title"><?php echo htmlspecialchars($medio_item['titulo']); ?></h4>
                            <div class="post-meta">
                                <div><i class="fas fa-tag"></i> <?php echo htmlspecialchars($medio_item['categoria_nombre'] ?? 'Sin categoría'); ?></div>
                                <?php if ($medio_item['fecha_media']): ?>
                                    <div><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($medio_item['fecha_media'])); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="post-actions">
                                <a href="galeria.php?editar=<?php echo $medio_item['id']; ?>" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="galeria.php?eliminar=<?php echo $medio_item['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este medio?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-bar" style="color: var(--brown);"></i>
                    Acciones Rápidas
                </h3>
            </div>
            <div style="display: flex; flex-direction: column; gap: 1rem; padding: 1rem;">
                <a href="galeria.php" class="btn btn-primary" style="justify-content: flex-start;">
                    <i class="fas fa-plus"></i> Agregar Nuevo Medio
                </a>
                <a href="categorias.php" class="btn btn-secondary" style="justify-content: flex-start;">
                    <i class="fas fa-tags"></i> Gestionar Categorías
                </a>
                <a href="logo.php" class="btn btn-secondary" style="justify-content: flex-start;">
                    <i class="fas fa-image"></i> Cambiar Logo
                </a>
                <a href="usuarios.php" class="btn btn-secondary" style="justify-content: flex-start;">
                    <i class="fas fa-user-plus"></i> Gestionar Usuarios
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
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