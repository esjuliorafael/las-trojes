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
            // Lógica para determinar la miniatura
            $thumbnail = null;
            if ($row['tipo'] === 'video') {
                $thumb_path = str_replace('/videos/', '/videos/thumbs/', str_replace('.mp4', '.jpg', $row['ruta_archivo']));
                $thumbnail = $thumb_path; 
            } else {
                $thumbnail = $row['ruta_archivo']; 
            }

            // Generar slug de categoría para filtrado consistente
            $categoria_slug = $this->generarSlugCategoria($row['categoria_nombre']);

            $medio_item = array(
                // Campos API
                "id" => $row['id'],
                "type" => $row['tipo'],
                "url" => $row['ruta_archivo'],
                "thumbnail" => $thumbnail,
                "title" => $row['titulo'],
                "description" => $row['descripcion'],
                "category" => $categoria_slug, // Usamos el slug generado dinámicamente
                "category_name" => $row['categoria_nombre'], // Enviamos también el nombre real para mostrar
                "likes" => (int)$row['likes'],
                "isLiked" => (bool)$row['is_liked'],
                "date" => $row['fecha_media'],
                "location" => $row['ubicacion'],
                // Campos Admin
                "titulo" => $row['titulo'],
                "descripcion" => $row['descripcion'],
                "tipo" => $row['tipo'],
                "ruta_archivo" => $row['ruta_archivo'],
                "categoria_nombre" => $row['categoria_nombre'],
                "fecha_media" => $row['fecha_media']
            );
            
            if ($row['tipo'] == 'video') {
                $medio_item['videoUrl'] = $row['ruta_archivo'];
            }
            
            array_push($medios_arr, $medio_item);
        }
        return $medios_arr;
    }

    // NUEVO MÉTODO: Genera un slug limpio a partir del nombre (Ej: "Lotes" -> "lotes", "Gallos Finos" -> "gallos-finos")
    private function generarSlugCategoria($nombre) {
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

    // ... (Resto de métodos: subirMedio, actualizar, eliminar, etc. SE MANTIENEN IGUAL) ...
    
    public function subirMedio($archivo_temporal, $nombre_archivo, $tipo, $thumbnail_base64 = null) {
        $uuid = uniqid();
        $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
        $nuevo_nombre = $uuid . '.' . $extension;
        
        $carpeta_tipo = ($tipo == 'video') ? "videos" : "fotos";
        $ruta_db = "assets/uploads/" . $carpeta_tipo . "/" . $nuevo_nombre; 
        $ruta_destino_filesystem = "../" . $ruta_db; 
        
        $carpeta_destino = dirname($ruta_destino_filesystem);
        if (!is_dir($carpeta_destino)) {
            mkdir($carpeta_destino, 0755, true);
        }
        
        if ($tipo == 'video' && $thumbnail_base64) {
            $this->guardarMiniatura($thumbnail_base64, $uuid);
        }

        if (move_uploaded_file($archivo_temporal, $ruta_destino_filesystem)) {
            $query = "INSERT INTO " . $this->table_name . " 
                     (titulo, descripcion, tipo, ruta_archivo, categoria_id, ubicacion, fecha_media) 
                     VALUES (:titulo, :descripcion, :tipo, :ruta_archivo, :categoria_id, :ubicacion, :fecha_media)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":titulo", $this->titulo);
            $stmt->bindParam(":descripcion", $this->descripcion);
            $stmt->bindParam(":tipo", $this->tipo);
            $stmt->bindParam(":ruta_archivo", $ruta_db); 
            $stmt->bindParam(":categoria_id", $this->categoria_id);
            $stmt->bindParam(":ubicacion", $this->ubicacion);
            $stmt->bindParam(":fecha_media", $this->fecha_media);
            
            return $stmt->execute();
        }
        return false;
    }

    public function actualizar($id, $titulo, $descripcion, $tipo, $categoria_id, $ubicacion, $fecha_media, $archivo_temporal = null, $nombre_archivo = null, $thumbnail_base64 = null) {
        $query = "UPDATE " . $this->table_name . " 
                  SET titulo = :titulo, 
                      descripcion = :descripcion, 
                      tipo = :tipo, 
                      categoria_id = :categoria_id, 
                      ubicacion = :ubicacion, 
                      fecha_media = :fecha_media";
        
        $ruta_db = null;
        if ($archivo_temporal && $nombre_archivo) {
            $uuid = uniqid();
            $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
            $nuevo_nombre = $uuid . '.' . $extension;
            
            $carpeta_tipo = ($tipo == 'video') ? "videos" : "fotos";
            $ruta_db = "assets/uploads/" . $carpeta_tipo . "/" . $nuevo_nombre;
            $ruta_destino_filesystem = "../" . $ruta_db;
            
            $carpeta_destino = dirname($ruta_destino_filesystem);
            if (!is_dir($carpeta_destino)) {
                mkdir($carpeta_destino, 0755, true);
            }

            if ($tipo == 'video' && $thumbnail_base64) {
                $this->guardarMiniatura($thumbnail_base64, $uuid);
            }

            if (move_uploaded_file($archivo_temporal, $ruta_destino_filesystem)) {
                $query .= ", ruta_archivo = :ruta_archivo";
            } else {
                return false;
            }
        }

        $query .= " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":titulo", $titulo);
        $stmt->bindParam(":descripcion", $descripcion);
        $stmt->bindParam(":tipo", $tipo);
        $stmt->bindParam(":categoria_id", $categoria_id);
        $stmt->bindParam(":ubicacion", $ubicacion);
        $stmt->bindParam(":fecha_media", $fecha_media);
        $stmt->bindParam(":id", $id);

        if ($ruta_db) {
            $stmt->bindParam(":ruta_archivo", $ruta_db);
        }
        
        return $stmt->execute();
    }

    private function guardarMiniatura($base64_string, $uuid) {
        $parts = explode(',', $base64_string);
        if (count($parts) == 2) {
            $data = base64_decode($parts[1]);
            $thumbs_dir = "../assets/uploads/videos/thumbs/";
            if (!is_dir($thumbs_dir)) {
                mkdir($thumbs_dir, 0755, true);
            }
            $thumb_path = $thumbs_dir . $uuid . ".jpg";
            file_put_contents($thumb_path, $data);
        }
    }

    public function actualizarLikes($id, $nuevos_likes, $is_liked) {
        $query = "UPDATE " . $this->table_name . " SET likes = :likes, is_liked = :is_liked WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":likes", $nuevos_likes);
        $stmt->bindParam(":is_liked", $is_liked, PDO::PARAM_BOOL);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    public function eliminar($id) {
        try {
            $medio_data = $this->obtenerPorId($id);
            if (!$medio_data) return false;

            $this->conn->beginTransaction();

            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $resultado = $stmt->execute();

            if ($resultado) {
                $ruta_archivo = "../" . $medio_data['ruta_archivo']; 
                if (file_exists($ruta_archivo) && is_file($ruta_archivo)) {
                    unlink($ruta_archivo);
                }
                if ($medio_data['tipo'] == 'video') {
                    $thumb_path = str_replace('/videos/', '/videos/thumbs/', str_replace('.mp4', '.jpg', $ruta_archivo));
                    if (file_exists($thumb_path) && is_file($thumb_path)) {
                        unlink($thumb_path);
                    }
                }
                $this->conn->commit();
                return true;
            } else {
                $this->conn->rollBack();
                return false;
            }
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        if ($stmt->rowCount() > 0) return $stmt->fetch(PDO::FETCH_ASSOC);
        return false;
    }
    
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
             $medio_item = $row;
             $medio_item['url'] = $row['ruta_archivo'];
            array_push($medios_arr, $medio_item);
        }
        return $medios_arr;
    }
}
?>