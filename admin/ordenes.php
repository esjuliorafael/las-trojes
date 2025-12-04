<?php
include_once 'includes/header.php';
include_once '../config/database.php';
include_once '../models/Orden.php';

$db = (new Database())->getConnection();
$ordenModel = new Orden($db);

if (isset($_GET['cancelar'])) {
    if ($ordenModel->cancelarOrden($_GET['cancelar'])) {
        echo "<script>alert('Orden cancelada y stock revertido'); window.location='ordenes.php';</script>";
    }
}

$ordenes = $ordenModel->obtenerTodas();
?>

<div class="page-header">
    <h1>Órdenes de Compra</h1>
</div>

<div class="card">
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Estado</th>
                    <th>Total</th>
                    <th>Fecha</th>
                    <th>Estatus</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($ordenes as $o): ?>
                <tr>
                    <td>#<?php echo $o['id']; ?></td>
                    <td>
                        <?php echo $o['cliente_nombre']; ?><br>
                        <small><?php echo $o['cliente_telefono']; ?></small>
                    </td>
                    <td><?php echo $o['estado_envio']; ?></td>
                    <td>$<?php echo number_format($o['total'], 2); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($o['fecha_creacion'])); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $o['estatus'] == 'pendiente' ? 'warning' : ($o['estatus'] == 'cancelado' ? 'danger' : 'success'); ?>">
                            <?php echo ucfirst($o['estatus']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if($o['estatus'] != 'cancelado'): ?>
                        <a href="ordenes.php?cancelar=<?php echo $o['id']; ?>" 
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('¿Cancelar orden? Esto devolverá el stock y liberará las aves.')">
                            <i class="fas fa-undo"></i> Cancelar
                        </a>
                        <?php endif; ?>
                        </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include_once 'includes/footer.php'; ?>