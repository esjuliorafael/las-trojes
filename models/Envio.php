<?php
class Envio {
    private $conn;
    private $table_config = "configuracion_envios";
    private $table_zonas = "zonas_envio";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerConfiguracion() {
        $query = "SELECT * FROM " . $this->table_config . " WHERE id = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizarConfiguracion($articulos, $gratis_aves, $zona_normal, $zona_extendida) {
        $query = "UPDATE " . $this->table_config . " 
                  SET costo_base_articulos = :art, envio_gratis_aves = :aves, 
                      costo_zona_normal = :zn, costo_zona_extendida = :ze 
                  WHERE id = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":art", $articulos);
        $stmt->bindParam(":aves", $gratis_aves);
        $stmt->bindParam(":zn", $zona_normal);
        $stmt->bindParam(":ze", $zona_extendida);
        return $stmt->execute();
    }

    public function obtenerZonas() {
        $query = "SELECT * FROM " . $this->table_zonas . " ORDER BY estado ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarZona($id, $tipo_zona) {
        $query = "UPDATE " . $this->table_zonas . " SET tipo_zona = :tipo WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":tipo", $tipo_zona);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    // Calcula costo para el checkout
    public function calcularCostoEnvio($estado_nombre, $tiene_articulos, $tiene_aves) {
        $config = $this->obtenerConfiguracion();
        $total_envio = 0;

        // 1. Costo Artículos
        if ($tiene_articulos) {
            $total_envio += $config['costo_base_articulos'];
        }

        // 2. Costo Aves
        if ($tiene_aves && !$config['envio_gratis_aves']) {
            // Buscar zona del estado
            $query = "SELECT tipo_zona FROM " . $this->table_zonas . " WHERE estado = :estado LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":estado", $estado_nombre);
            $stmt->execute();
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['tipo_zona'] == 'extendida') {
                    $total_envio += $config['costo_zona_extendida'];
                } else {
                    $total_envio += $config['costo_zona_normal'];
                }
            } else {
                // Estado no encontrado, cobramos normal por defecto
                $total_envio += $config['costo_zona_normal'];
            }
        }

        return $total_envio;
    }
}
?>