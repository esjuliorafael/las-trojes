<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

include_once '../config/database.php';
include_once '../models/Producto.php';

$database = new Database();
$db = $database->getConnection();
$producto = new Producto($db);

// Obtener filtro por tipo si existe (?tipo=ave o ?tipo=articulo)
$tipo_filtro = isset($_GET['tipo']) ? $_GET['tipo'] : null;

// Obtener productos
$productos_raw = $producto->leerTodos($tipo_filtro);
$productos_response = array();

foreach ($productos_raw as $prod) {
    // Obtener galería para cada producto
    $galeria_raw = $producto->obtenerGaleria($prod['id']);
    $galeria_urls = array();
    
    foreach($galeria_raw as $img) {
        $galeria_urls[] = array(
            "id" => $img['id'],
            "url" => $img['ruta_archivo'],
            "tipo" => $img['tipo_archivo']
        );
    }

    $item = array(
        "id" => $prod['id'],
        "tipo" => $prod['tipo'],
        "nombre" => $prod['nombre'],
        "descripcion" => $prod['descripcion'],
        "precio" => (float)$prod['precio'],
        "portada" => $prod['portada'],
        "stock" => (int)$prod['stock'],
        "activo" => (bool)$prod['activo'],
        "galeria" => $galeria_urls
    );

    // Agregar campos específicos si es Ave
    if ($prod['tipo'] === 'ave') {
        $item['detalles_ave'] = array(
            "anillo" => $prod['anillo'],
            "edad" => $prod['edad'],
            "proposito" => $prod['proposito'],
            "estado" => $prod['estado_venta'] // disponible, vendido, reservado
        );
    }

    array_push($productos_response, $item);
}

echo json_encode(array("count" => count($productos_response), "productos" => $productos_response));
?>