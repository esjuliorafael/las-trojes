<?php
include_once 'config/database.php';
include_once 'models/Producto.php';
$db = (new Database())->getConnection();
$producto = new Producto($db);
$lista = $producto->leerTodos();
?>
<h1>Enlaces de Prueba</h1>
<ul>
    <?php foreach($lista as $p): ?>
        <li>
            <a href="producto.php?id=<?php echo $p['id']; ?>">
                Probar ID <?php echo $p['id']; ?>: <?php echo $p['nombre']; ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>