<?php
class Producto {
    private $conn;
    private $table_name = "productos";
    private $table_gallery = "productos_galeria";

    public $id;
    public $tipo;
    public $nombre;
    public $descripcion;
    public $precio;
    public $portada;
    public $stock;
    public $anillo;
    public $edad;
    public $proposito;
    public $estado_venta;
    public $activo;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function leerTodos($filtro_tipo = null) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE activo = 1";

        if ($filtro_tipo) {
            $query .= " AND tipo = :tipo";
        }

        $query .= " ORDER BY fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);

        if ($filtro_tipo) {
            $stmt->bindParam(":tipo", $filtro_tipo);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function leerUno($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        $producto = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($producto) {
            // Obtener galería asociada
            $producto['galeria'] = $this->obtenerGaleria($id);
        }

        return $producto;
    }

    public function obtenerGaleria($producto_id) {
        $query = "SELECT * FROM " . $this->table_gallery . " WHERE producto_id = :pid";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":pid", $producto_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- NUEVO MÉTODO PARA PRODUCTOS RELACIONADOS ---
    public function leerRelacionados($tipo, $exclude_id, $limit = 4) {
        // Selecciona productos del mismo tipo, excluyendo el actual, activos y ordena aleatoriamente
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE tipo = :tipo 
                  AND id != :exclude_id 
                  AND activo = 1 
                  ORDER BY RAND() 
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":tipo", $tipo);
        $stmt->bindParam(":exclude_id", $exclude_id);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (tipo, nombre, descripcion, precio, portada, stock, anillo, edad, proposito, estado_venta) 
                  VALUES 
                  (:tipo, :nombre, :descripcion, :precio, :portada, :stock, :anillo, :edad, :proposito, :estado_venta)";

        $stmt = $this->conn->prepare($query);

        // Lógica de Stock para Aves
        if ($this->tipo === 'ave') {
            $this->stock = ($this->estado_venta === 'disponible') ? 1 : 0;
        }

        $stmt->bindParam(":tipo", $this->tipo);
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":precio", $this->precio);
        $stmt->bindParam(":portada", $this->portada);
        $stmt->bindParam(":stock", $this->stock);
        $stmt->bindParam(":anillo", $this->anillo);
        $stmt->bindParam(":edad", $this->edad);
        $stmt->bindParam(":proposito", $this->proposito);
        $stmt->bindParam(":estado_venta", $this->estado_venta);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function actualizar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nombre=:nombre, descripcion=:descripcion, precio=:precio, 
                      stock=:stock, anillo=:anillo, edad=:edad, proposito=:proposito, estado_venta=:estado_venta";

        if (!empty($this->portada)) {
            $query .= ", portada=:portada";
        }

        $query .= " WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Lógica de Stock para Aves en actualización
        if ($this->tipo === 'ave') {
            $this->stock = ($this->estado_venta === 'disponible') ? 1 : 0;
        }

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":precio", $this->precio);
        $stmt->bindParam(":stock", $this->stock);
        $stmt->bindParam(":anillo", $this->anillo);
        $stmt->bindParam(":edad", $this->edad);
        $stmt->bindParam(":proposito", $this->proposito);
        $stmt->bindParam(":estado_venta", $this->estado_venta);
        $stmt->bindParam(":id", $this->id);

        if (!empty($this->portada)) {
            $stmt->bindParam(":portada", $this->portada);
        }

        return $stmt->execute();
    }

    public function eliminar($id) {
        // Soft delete para mantener historial
        $query = "UPDATE " . $this->table_name . " SET activo = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    // --- MANEJO DE ARCHIVOS ---

    public function subirPortada($archivo) {
        return $this->procesarSubida($archivo, 'tienda/portadas');
    }

    public function agregarGaleria($producto_id, $archivos) {
        $uploaded = 0;
        foreach ($archivos['tmp_name'] as $key => $tmp_name) {
            if ($archivos['error'][$key] === UPLOAD_ERR_OK) {
                $file_array = [
                    'name' => $archivos['name'][$key],
                    'tmp_name' => $tmp_name,
                    'error' => 0
                ];

                $ruta = $this->procesarSubida($file_array, 'tienda/galeria');

                if ($ruta) {
                    $query = "INSERT INTO " . $this->table_gallery . " (producto_id, ruta_archivo) VALUES (:pid, :ruta)";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(":pid", $producto_id);
                    $stmt->bindParam(":ruta", $ruta);
                    if ($stmt->execute()) $uploaded++;
                }
            }
        }
        return $uploaded;
    }

    private function procesarSubida($archivo, $subcarpeta) {
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $uuid = uniqid();
        $nuevo_nombre = $uuid . '.' . $extension;

        $ruta_db = "assets/uploads/" . $subcarpeta . "/" . $nuevo_nombre;
        $ruta_fisica = "../" . $ruta_db;

        $directorio = dirname($ruta_fisica);
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        if (move_uploaded_file($archivo['tmp_name'], $ruta_fisica)) {
            return $ruta_db;
        }
        return false;
    }
}
?>