<?php
/**
 * Modelo ProductoArchivo
 * Gestiona los archivos descargables de productos (PDFs, documentos, imágenes informativas)
 */

require_once __DIR__ . '/../config/database.php';

class ProductoArchivo {
    private $conn;
    private $table_name = "producto_archivos";

    // Propiedades
    public $id;
    public $producto_id;
    public $nombre_original;
    public $nombre_personalizado;
    public $url;
    public $tipo_archivo;
    public $tamanio_bytes;
    public $orden;
    public $created_at;
    public $updated_at;

    // Validaciones
    private $tipos_permitidos = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif'];
    private $tamanio_maximo = 10485760; // 10MB en bytes
    private $max_archivos_por_producto = 15;

    /**
     * Constructor
     */
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Crear nuevo archivo
     */
    public function create() {
        try {
            // Si no se especifica orden, obtener el siguiente
            if (!isset($this->orden)) {
                $this->orden = $this->obtenerSiguienteOrden();
            }

            $query = "INSERT INTO " . $this->table_name . "
                      (producto_id, nombre_original, nombre_personalizado, url, tipo_archivo, tamanio_bytes, orden)
                      VALUES
                      (:producto_id, :nombre_original, :nombre_personalizado, :url, :tipo_archivo, :tamanio_bytes, :orden)";

            $stmt = $this->conn->prepare($query);

            // Limpiar datos
            $this->producto_id = intval($this->producto_id);
            $this->nombre_original = htmlspecialchars(strip_tags($this->nombre_original));
            $this->nombre_personalizado = $this->nombre_personalizado ? htmlspecialchars(strip_tags($this->nombre_personalizado)) : null;
            $this->url = htmlspecialchars(strip_tags($this->url));
            $this->tipo_archivo = strtolower(htmlspecialchars(strip_tags($this->tipo_archivo)));
            $this->tamanio_bytes = intval($this->tamanio_bytes);
            $this->orden = intval($this->orden);

            // Bind parámetros
            $stmt->bindParam(':producto_id', $this->producto_id);
            $stmt->bindParam(':nombre_original', $this->nombre_original);
            $stmt->bindParam(':nombre_personalizado', $this->nombre_personalizado);
            $stmt->bindParam(':url', $this->url);
            $stmt->bindParam(':tipo_archivo', $this->tipo_archivo);
            $stmt->bindParam(':tamanio_bytes', $this->tamanio_bytes);
            $stmt->bindParam(':orden', $this->orden);

            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }

            return false;

        } catch (PDOException $e) {
            error_log("Error PDO al crear archivo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener todos los archivos de un producto
     */
    public function getByProducto($producto_id) {
        try {
            $query = "SELECT id, producto_id, nombre_original, nombre_personalizado, url,
                             tipo_archivo, tamanio_bytes, orden, created_at, updated_at
                      FROM " . $this->table_name . "
                      WHERE producto_id = :producto_id
                      ORDER BY orden ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':producto_id', $producto_id);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error PDO al obtener archivos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener un archivo por ID
     */
    public function readOne() {
        try {
            $query = "SELECT id, producto_id, nombre_original, nombre_personalizado, url,
                             tipo_archivo, tamanio_bytes, orden, created_at, updated_at
                      FROM " . $this->table_name . "
                      WHERE id = :id
                      LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $this->producto_id = $row['producto_id'];
                $this->nombre_original = $row['nombre_original'];
                $this->nombre_personalizado = $row['nombre_personalizado'];
                $this->url = $row['url'];
                $this->tipo_archivo = $row['tipo_archivo'];
                $this->tamanio_bytes = $row['tamanio_bytes'];
                $this->orden = $row['orden'];
                $this->created_at = $row['created_at'];
                $this->updated_at = $row['updated_at'];
                return true;
            }

            return false;

        } catch (PDOException $e) {
            error_log("Error PDO al leer archivo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar nombre personalizado del archivo
     */
    public function update() {
        try {
            $query = "UPDATE " . $this->table_name . "
                      SET nombre_personalizado = :nombre_personalizado
                      WHERE id = :id";

            $stmt = $this->conn->prepare($query);

            // Limpiar datos
            $this->nombre_personalizado = $this->nombre_personalizado ? htmlspecialchars(strip_tags($this->nombre_personalizado)) : null;

            $stmt->bindParam(':nombre_personalizado', $this->nombre_personalizado);
            $stmt->bindParam(':id', $this->id);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error PDO al actualizar archivo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar archivo
     */
    public function delete() {
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error PDO al eliminar archivo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar orden de los archivos
     */
    public function updateOrden($nuevasOrdenes) {
        try {
            $this->conn->beginTransaction();

            foreach ($nuevasOrdenes as $item) {
                $query = "UPDATE " . $this->table_name . "
                          SET orden = :orden
                          WHERE id = :id";

                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':orden', $item['orden']);
                $stmt->bindParam(':id', $item['id']);
                $stmt->execute();
            }

            $this->conn->commit();
            return true;

        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error PDO al actualizar orden: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar todos los archivos de un producto
     */
    public function deleteByProducto($producto_id) {
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE producto_id = :producto_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':producto_id', $producto_id);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error PDO al eliminar archivos del producto: " . $e->getMessage());
            return false;
        }
    }

    // ============= MÉTODOS DE VALIDACIÓN =============

    /**
     * Validar tipo de archivo
     */
    public function validarTipoArchivo($extension) {
        return in_array(strtolower($extension), $this->tipos_permitidos);
    }

    /**
     * Validar tamaño de archivo
     */
    public function validarTamanio($tamanio) {
        return $tamanio <= $this->tamanio_maximo;
    }

    /**
     * Verificar si se puede agregar más archivos al producto
     */
    public function puedeAgregarArchivos($producto_id) {
        $total = $this->contarArchivos($producto_id);
        return $total < $this->max_archivos_por_producto;
    }

    /**
     * Obtener tipos permitidos
     */
    public function getTiposPermitidos() {
        return $this->tipos_permitidos;
    }

    /**
     * Obtener tamaño máximo en MB
     */
    public function getTamanioMaximoMB() {
        return $this->tamanio_maximo / 1048576;
    }

    // ============= MÉTODOS PRIVADOS =============

    /**
     * Obtener siguiente número de orden
     */
    private function obtenerSiguienteOrden() {
        try {
            $query = "SELECT COALESCE(MAX(orden), -1) + 1 as siguiente_orden
                      FROM " . $this->table_name . "
                      WHERE producto_id = :producto_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':producto_id', $this->producto_id);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['siguiente_orden'];

        } catch (PDOException $e) {
            error_log("Error al obtener siguiente orden: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Contar archivos de un producto
     */
    public function contarArchivos($producto_id) {
        try {
            $query = "SELECT COUNT(*) as total
                      FROM " . $this->table_name . "
                      WHERE producto_id = :producto_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':producto_id', $producto_id);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return intval($row['total']);

        } catch (PDOException $e) {
            error_log("Error al contar archivos: " . $e->getMessage());
            return 0;
        }
    }
}
