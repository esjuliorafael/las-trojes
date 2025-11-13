<?php
class Logo {
    private $conn;
    private $table_name = "logos";

    public $id;
    public $ruta_archivo;
    public $fecha_creacion;
    public $activo;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerLogoActivo() {
        $query = "SELECT ruta_archivo FROM " . $this->table_name . " WHERE activo = 1 ORDER BY fecha_creacion DESC LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['ruta_archivo'];
        }
        return "assets/images/logo.png";
    }

    public function subirLogo($archivo_temporal, $nombre_archivo) {
        $extension = pathinfo($nombre_archivo, PATHINFO_EXTENSION);
        $nuevo_nombre = uniqid() . '.' . $extension;
        
        $ruta_db = "assets/uploads/logo/" . $nuevo_nombre; 
        $ruta_destino_filesystem = "../" . $ruta_db; 
        
        $query_desactivar = "UPDATE " . $this->table_name . " SET activo = 0";
        $stmt_desactivar = $this->conn->prepare($query_desactivar);
        $stmt_desactivar->execute();
        
        if (move_uploaded_file($archivo_temporal, $ruta_destino_filesystem)) {
            $query = "INSERT INTO " . $this->table_name . " (ruta_archivo) VALUES (:ruta_archivo)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":ruta_archivo", $ruta_db);
            
            return $stmt->execute();
        }
        return false;
    }
}
?>