<?php
// admin/api/ordenes.php
include_once 'common.php';
include_once '../../models/Orden.php';

$orden = new Orden($db);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $ordenes = $orden->obtenerTodas();
    // Tu modelo devuelve un array plano, vamos a enriquecerlo si es necesario
    echo json_encode($ordenes);
}

if ($method === 'POST') {
    // Para acciones como cancelar
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (isset($data['accion']) && $data['accion'] === 'cancelar' && isset($data['id'])) {
        if ($orden->cancelarOrden($data['id'])) {
            jsonResponse(true, "Orden cancelada y stock restaurado");
        } else {
            jsonResponse(false, "No se pudo cancelar la orden");
        }
    }
}
?>