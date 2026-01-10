<?php
/**
 * Modelo ProductoImagen
 * Gestiona las imágenes de productos
 */

require_once __DIR__ . '/../config/database.php';

class ProductoImagen {
    private $conn;
    private $table_name = "producto_imagenes";
    
    // Propiedades
    public $id;
    public $producto_id;
    public $url;
    public $tipo; // 'principal', 'galeria', 'esquema'
    public $orden;
    public $alt_text;
    
    /**
     * Constructor
     */
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Crear nueva imagen
     */
    public function create() {
        try {
            // Verificar si es la primera imagen del producto
            if ($this->tipo === 'principal') {
                // Cambiar cualquier imagen principal existente a galería
                $this->cambiarPrincipalAGaleria();
            }
            
            // Si no se especifica orden, obtener el siguiente
            if (!isset($this->orden)) {
                $this->orden = $this->obtenerSiguienteOrden();
            }
            
            $query = "INSERT INTO " . $this->table_name . " 
                      (producto_id, url, tipo, orden, alt_text) 
                      VALUES 
                      (:producto_id, :url, :tipo, :orden, :alt_text)";
            
            $stmt = $this->conn->prepare($query);
            
            // Limpiar datos
            $this->producto_id = intval($this->producto_id);
            $this->url = htmlspecialchars(strip_tags($this->url));
            $this->tipo = htmlspecialchars(strip_tags($this->tipo));
            $this->orden = intval($this->orden);
            $this->alt_text = htmlspecialchars(strip_tags($this->alt_text ?? ''));
            
            // Bind parámetros
            $stmt->bindParam(':producto_id', $this->producto_id);
            $stmt->bindParam(':url', $this->url);
            $stmt->bindParam(':tipo', $this->tipo);
            $stmt->bindParam(':orden', $this->orden);
            $stmt->bindParam(':alt_text', $this->alt_text);
            
            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Error PDO al crear imagen: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener todas las imágenes de un producto
     */
    public function getByProducto($producto_id) {
        try {
            $query = "SELECT id, producto_id, url, tipo, orden, alt_text 
                      FROM " . $this->table_name . " 
                      WHERE producto_id = :producto_id 
                      ORDER BY 
                        CASE 
                          WHEN tipo = 'principal' THEN 0
                          ELSE 1
                        END,
                        orden ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':producto_id', $producto_id);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error PDO al obtener imágenes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener imagen principal de un producto
     */
    public function getPrincipal($producto_id) {
        try {
            $query = "SELECT id, producto_id, url, tipo, orden, alt_text 
                      FROM " . $this->table_name . " 
                      WHERE producto_id = :producto_id 
                      AND tipo = 'principal' 
                      LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':producto_id', $producto_id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error PDO al obtener imagen principal: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtener una imagen por ID
     */
    public function readOne() {
        try {
            $query = "SELECT id, producto_id, url, tipo, orden, alt_text 
                      FROM " . $this->table_name . " 
                      WHERE id = :id 
                      LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                $this->producto_id = $row['producto_id'];
                $this->url = $row['url'];
                $this->tipo = $row['tipo'];
                $this->orden = $row['orden'];
                $this->alt_text = $row['alt_text'];
                return true;
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Error PDO al leer imagen: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Eliminar imagen
     */
    public function delete() {
        try {
            // Si la imagen a eliminar es principal, promover otra
            if ($this->tipo === 'principal') {
                $this->promoverNuevaPrincipal();
            }
            
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Error PDO al eliminar imagen: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cambiar imagen a principal
     */
    public function setPrincipal() {
        try {
            // Primero cambiar la actual principal a galería
            $this->cambiarPrincipalAGaleria();
            
            // Luego cambiar esta imagen a principal
            $query = "UPDATE " . $this->table_name . " 
                      SET tipo = 'principal', orden = 0 
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Error PDO al cambiar principal: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualizar orden de las imágenes
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
     * Eliminar todas las imágenes de un producto
     */
    public function deleteByProducto($producto_id) {
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE producto_id = :producto_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':producto_id', $producto_id);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Error PDO al eliminar imágenes del producto: " . $e->getMessage());
            return false;
        }
    }
    
    // ============= MÉTODOS PRIVADOS =============
    
    /**
     * Cambiar imagen principal actual a galería
     */
    private function cambiarPrincipalAGaleria() {
        try {
            $query = "UPDATE " . $this->table_name . " 
                      SET tipo = 'galeria' 
                      WHERE producto_id = :producto_id 
                      AND tipo = 'principal'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':producto_id', $this->producto_id);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Error al cambiar principal a galería: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Promover siguiente imagen a principal cuando se elimina la actual
     */
    private function promoverNuevaPrincipal() {
        try {
            // Obtener la siguiente imagen en orden
            $query = "SELECT id FROM " . $this->table_name . " 
                      WHERE producto_id = :producto_id 
                      AND id != :current_id 
                      ORDER BY orden ASC 
                      LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':producto_id', $this->producto_id);
            $stmt->bindParam(':current_id', $this->id);
            $stmt->execute();
            
            $siguiente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($siguiente) {
                // Promover a principal
                $query = "UPDATE " . $this->table_name . " 
                          SET tipo = 'principal', orden = 0 
                          WHERE id = :id";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':id', $siguiente['id']);
                $stmt->execute();
            }
            
        } catch (PDOException $e) {
            error_log("Error al promover nueva principal: " . $e->getMessage());
        }
    }
    
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
     * Contar imágenes de un producto
     */
    public function contarImagenes($producto_id) {
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
            error_log("Error al contar imágenes: " . $e->getMessage());
            return 0;
        }
    }
}