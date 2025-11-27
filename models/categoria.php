<?php
class Categoria {
    private $conn;
    private $table_name = "categorias";

    public $id;
    public $nombre;
    public $icono;
    public $fecha_creacion;
    public $activo;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodas() {
        $query = "SELECT c.*, COUNT(m.id) as cantidad_medios 
                  FROM " . $this->table_name . " c 
                  LEFT JOIN medios m ON c.id = m.categoria_id AND m.activo = 1 
                  WHERE c.activo = 1 
                  GROUP BY c.id 
                  ORDER BY c.nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $categorias_arr = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $categoria_item = array(
                "id" => $row['id'],
                "nombre" => $row['nombre'],
                "icono" => $row['icono'],
                "cantidad_medios" => $row['cantidad_medios'],
                // GENERAMOS EL SLUG AQUÍ PARA QUE COINCIDA EXACTAMENTE CON LOS MEDIOS
                "slug" => $this->generarSlug($row['nombre'])
            );
            array_push($categorias_arr, $categoria_item);
        }
        return $categorias_arr;
    }

    // Función helper para generar slugs limpios (Igual que en Medio.php)
    private function generarSlug($nombre) {
        if (empty($nombre)) return 'sin-categoria';
        
        // 1. Convertir a minúsculas
        $slug = mb_strtolower($nombre, 'UTF-8');
        
        // 2. Reemplazar caracteres acentuados
        $buscar = array('á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü');
        $reemplazar = array('a', 'e', 'i', 'o', 'u', 'n', 'u');
        $slug = str_replace($buscar, $reemplazar, $slug);
        
        // 3. Reemplazar espacios y caracteres no alfanuméricos por guiones
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        
        // 4. Eliminar guiones al inicio y final
        $slug = trim($slug, '-');
        
        return $slug;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " (nombre, icono) VALUES (:nombre, :icono)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":icono", $this->icono);
        
        return $stmt->execute();
    }

    public function actualizar() {
        $query = "UPDATE " . $this->table_name . " SET nombre = :nombre, icono = :icono WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":icono", $this->icono);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }

    public function eliminar() {
        $query = "UPDATE " . $this->table_name . " SET activo = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }

    public function contarTotal() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE activo = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
}
?>