<?php
class Medio {
    private $conn;
    private $table_name = "medios";

    public $id;
    public $titulo;
    public $descripcion;
    public $tipo;
    public $ruta_archivo;
    public $categoria_id;
    public $ubicacion;
    public $fecha_media;
    public $likes;
    public $is_liked;
    public $fecha_creacion;
    public $activo;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        $query = "SELECT m.*, c.nombre as categoria_nombre 
                  FROM " . $this->table_name . " m 
                  LEFT JOIN categorias c ON m.categoria_id = c.id 
                  WHERE m.activo = 1 
                  ORDER BY m.fecha_creacion DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $medios_arr = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Mapear a formato compatible con el frontend
            $medio_item = array(
                "id" => $row['id'],
                "type" => $row['tipo'],
                "url" => $row['ruta_archivo'],
                "title" => $row['titulo'],
                "description" => $row['descripcion'],
                "category" => $this->mapearCategoria($row['categoria_nombre']),
                "likes" => (int)$row['likes'],
                "isLiked" => (bool)$row['is_liked'],
                "date" => $row['fecha_media'],
                "location" => $row['ubicacion'],
                // Campos adicionales para el admin
                "tipo" => $row['tipo'],
                "ruta_archivo" => $row['ruta_archivo'],
                "categoria_nombre" => $row['categoria_nombre'],
                "fecha_media" => $row['fecha_media']
            );
            
            // Agregar videoUrl si es video
            if ($row['tipo'] == 'video') {
                $medio_item['videoUrl'] = $row['ruta_archivo'];
            }
            
            array_push($medios_arr, $medio_item);
        }
        return $medios_arr;
    }

    private function mapearCategoria($nombre_categoria) {
        $mapeo = array(
            'Gallos' => 'gallos',
            'Eventos' => 'eventos',
            'Criadero' => 'criadero',
            'Competencias' => 'competencias',
            'Clientes' => 'clientes'
        );
        return isset($mapeo[$nombre_categoria]) ? $mapeo[$nombre_categoria] : 'todo';
    }

    public function subirMedio($archivo_temporal, $nombre_archivo, $tipo) {
        // Generar UUID único para el nombre del archivo
        $uuid = uniqid();
        $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
        $nuevo_nombre = $uuid . '.' . $extension;
        
        // Determinar carpeta destino según el tipo
        $carpeta_destino = ($tipo == 'video') ? "../assets/uploads/videos/" : "../assets/uploads/fotos/";
        $ruta_destino = $carpeta_destino . $nuevo_nombre;
        
        // Crear directorio si no existe
        if (!is_dir($carpeta_destino)) {
            mkdir($carpeta_destino, 0755, true);
        }
        
        // Mover archivo
        if (move_uploaded_file($archivo_temporal, $ruta_destino)) {
            // Guardar en BD
            $query = "INSERT INTO " . $this->table_name . " 
                     (titulo, descripcion, tipo, ruta_archivo, categoria_id, ubicacion, fecha_media) 
                     VALUES (:titulo, :descripcion, :tipo, :ruta_archivo, :categoria_id, :ubicacion, :fecha_media)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":titulo", $this->titulo);
            $stmt->bindParam(":descripcion", $this->descripcion);
            $stmt->bindParam(":tipo", $this->tipo);
            $stmt->bindParam(":ruta_archivo", $ruta_destino);
            $stmt->bindParam(":categoria_id", $this->categoria_id);
            $stmt->bindParam(":ubicacion", $this->ubicacion);
            $stmt->bindParam(":fecha_media", $this->fecha_media);
            
            return $stmt->execute();
        }
        return false;
    }

    public function actualizarLikes($id, $nuevos_likes, $is_liked) {
        $query = "UPDATE " . $this->table_name . " SET likes = :likes, is_liked = :is_liked WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":likes", $nuevos_likes);
        $stmt->bindParam(":is_liked", $is_liked, PDO::PARAM_BOOL);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }

    /**
     * ELIMINACIÓN FÍSICA - Elimina tanto el registro como el archivo físico
     */
    public function eliminar($id) {
        try {
            // Primero obtener información del medio para eliminar el archivo físico
            $medio_data = $this->obtenerPorId($id);
            
            if (!$medio_data) {
                return false;
            }

            // Iniciar transacción para asegurar consistencia
            $this->conn->beginTransaction();

            // 1. Eliminar el registro de la base de datos
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $resultado = $stmt->execute();

            if ($resultado) {
                // 2. Eliminar el archivo físico del servidor
                $ruta_archivo = $medio_data['ruta_archivo'];
                
                // Verificar si el archivo existe y eliminarlo
                if (file_exists($ruta_archivo) && is_file($ruta_archivo)) {
                    if (!unlink($ruta_archivo)) {
                        throw new Exception("No se pudo eliminar el archivo físico: " . $ruta_archivo);
                    }
                }

                // Confirmar la transacción
                $this->conn->commit();
                return true;
            } else {
                $this->conn->rollBack();
                return false;
            }

        } catch (Exception $e) {
            // Revertir la transacción en caso de error
            $this->conn->rollBack();
            error_log("Error al eliminar medio ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener un medio por su ID
     */
    public function obtenerPorId($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * Eliminación lógica (soft delete) - alternativa
     */
    public function desactivar($id) {
        $query = "UPDATE " . $this->table_name . " SET activo = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    public function contarTotal() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE activo = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    public function obtenerRecientes($limite = 5) {
        $query = "SELECT m.*, c.nombre as categoria_nombre 
                  FROM " . $this->table_name . " m 
                  LEFT JOIN categorias c ON m.categoria_id = c.id 
                  WHERE m.activo = 1 
                  ORDER BY m.fecha_creacion DESC 
                  LIMIT :limite";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limite", $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        $medios_arr = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($medios_arr, $row);
        }
        return $medios_arr;
    }
}
?>