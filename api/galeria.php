<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/Logo.php';
include_once '../models/Categoria.php';
include_once '../models/Medio.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Obtener logo activo
    $logo = new Logo($db);
    $logo_activo = $logo->obtenerLogoActivo();
    
    // Obtener categorías
    $categoria = new Categoria($db);
    $categorias = $categoria->obtenerTodas();
    
    // Obtener medios
    $medio = new Medio($db);
    $medios = $medio->obtenerTodos();
    
    // Preparar respuesta
    $response = array(
        "logo" => $logo_activo,
        "categorias" => $categorias,
        "medios" => $medios
    );
    
    echo json_encode($response);
}
?>