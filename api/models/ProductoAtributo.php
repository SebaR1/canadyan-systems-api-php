<?php
/**
 * Modelo ProductoAtributo
 * Canadian Sistemas API
 */

require_once __DIR__ . '/../config/database.php';

class ProductoAtributo {
    private $conn;
    private $table_name = "producto_atributos";
    
    // Propiedades del objeto
    public $producto_id;
    public $atributo_id;
    public $valor;
    
    // Constructor
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Crear nueva relación producto-atributo
     */
    public function create() {
        try {
            // Limpiar datos
            $this->producto_id = intval($this->producto_id);
            $this->atributo_id = intval($this->atributo_id);
            $this->valor = htmlspecialchars(strip_tags($this->valor));
            
            // Verificar que el producto existe
            if (!$this->productoExists($this->producto_id)) {
                error_log("Error al crear producto_atributo: El producto ID {$this->producto_id} no existe");
                return false;
            }
            
            // Verificar que el atributo existe
            if (!$this->atributoExists($this->atributo_id)) {
                error_log("Error al crear producto_atributo: El atributo ID {$this->atributo_id} no existe");
                return false;
            }
            
            // Si ya existe, actualizar el valor
            if ($this->exists($this->producto_id, $this->atributo_id)) {
                return $this->updateValue($this->producto_id, $this->atributo_id, $this->valor);
            }
            
            $query = "INSERT INTO " . $this->table_name . " 
                      SET producto_id=:producto_id, atributo_id=:atributo_id, valor=:valor";
            
            $stmt = $this->conn->prepare($query);
            
            // Bind parámetros
            $stmt->bindParam(':producto_id', $this->producto_id);
            $stmt->bindParam(':atributo_id', $this->atributo_id);
            $stmt->bindParam(':valor', $this->valor);
            
            if ($stmt->execute()) {
                return true;
            }
            
            error_log("Error al crear producto_atributo: No se pudo ejecutar la query");
            return false;
            
        } catch (PDOException $e) {
            error_log("Error PDO al crear producto_atributo: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error general al crear producto_atributo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener atributos de un producto
     */
    public function getByProducto($producto_id) {
        try {
            $query = "SELECT 
                        pa.producto_id,
                        pa.atributo_id,
                        pa.valor,
                        a.nombre as atributo_nombre,
                        a.tipo as atributo_tipo
                      FROM " . $this->table_name . " pa
                      INNER JOIN atributos a ON pa.atributo_id = a.id
                      WHERE pa.producto_id = ?
                      ORDER BY a.nombre";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $producto_id);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error PDO al obtener atributos del producto {$producto_id}: " . $e->getMessage());
            return [];
        } catch (Exception $e) {
            error_log("Error general al obtener atributos del producto {$producto_id}: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener productos que tienen un atributo con determinado valor
     */
    public function getProductosByAtributoValor($atributo_id, $valor) {
        try {
            $query = "SELECT 
                        pa.producto_id,
                        p.nombre as producto_nombre,
                        pa.valor
                      FROM " . $this->table_name . " pa
                      INNER JOIN productos p ON pa.producto_id = p.id
                      WHERE pa.atributo_id = ? AND pa.valor = ? AND p.activo = 1
                      ORDER BY p.nombre";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $atributo_id);
            $stmt->bindParam(2, $valor);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error PDO al obtener productos por atributo-valor: " . $e->getMessage());
            return [];
        } catch (Exception $e) {
            error_log("Error general al obtener productos por atributo-valor: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Actualizar valor de un atributo de producto
     */
    public function updateValue($producto_id, $atributo_id, $nuevo_valor) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                      SET valor = :valor 
                      WHERE producto_id = :producto_id AND atributo_id = :atributo_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':valor', $nuevo_valor);
            $stmt->bindParam(':producto_id', $producto_id);
            $stmt->bindParam(':atributo_id', $atributo_id);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Error PDO al actualizar producto_atributo: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error general al actualizar producto_atributo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Eliminar atributo de un producto
     */
    public function delete() {
        try {
            $query = "DELETE FROM " . $this->table_name . " 
                      WHERE producto_id = ? AND atributo_id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->producto_id);
            $stmt->bindParam(2, $this->atributo_id);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Error PDO al eliminar producto_atributo: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error general al eliminar producto_atributo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Eliminar todos los atributos de un producto
     */
    public function deleteByProducto($producto_id) {
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE producto_id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $producto_id);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Error PDO al eliminar atributos del producto {$producto_id}: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error general al eliminar atributos del producto {$producto_id}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Guardar múltiples atributos de un producto (reemplaza todos)
     */
    public function saveProductoAtributos($producto_id, $atributos) {
        try {
            // Iniciar transacción
            $this->conn->beginTransaction();
            
            // Eliminar atributos existentes
            $this->deleteByProducto($producto_id);
            
            // Insertar nuevos atributos
            foreach ($atributos as $atributo) {
                if (empty($atributo['valor'])) continue; // Saltar valores vacíos
                
                $this->producto_id = $producto_id;
                $this->atributo_id = $atributo['atributo_id'];
                $this->valor = $atributo['valor'];
                
                if (!$this->create()) {
                    $this->conn->rollback();
                    return false;
                }
            }
            
            // Confirmar transacción
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error al guardar atributos del producto {$producto_id}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar si existe la relación producto-atributo
     */
    private function exists($producto_id, $atributo_id) {
        try {
            $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                      WHERE producto_id = ? AND atributo_id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $producto_id);
            $stmt->bindParam(2, $atributo_id);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
            
        } catch (PDOException $e) {
            error_log("Error PDO al verificar existencia producto_atributo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar si el producto existe
     */
    private function productoExists($producto_id) {
        try {
            $query = "SELECT COUNT(*) as count FROM productos WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $producto_id);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
            
        } catch (PDOException $e) {
            error_log("Error PDO al verificar producto: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar si el atributo existe
     */
    private function atributoExists($atributo_id) {
        try {
            $query = "SELECT COUNT(*) as count FROM atributos WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $atributo_id);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
            
        } catch (PDOException $e) {
            error_log("Error PDO al verificar atributo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Filtrar productos por múltiples atributos
     */
    public function filtrarProductosPorAtributos($filtros, $limit = null, $offset = 0) {
        try {
            if (empty($filtros)) {
                return [];
            }
            
            // Construir query dinámicamente
            $whereClauses = [];
            $params = [];
            $paramIndex = 1;
            
            foreach ($filtros as $atributo_id => $valores) {
                if (!is_array($valores) || empty($valores)) continue;
                
                $placeholders = [];
                foreach ($valores as $valor) {
                    $placeholders[] = "?";
                    $params[$paramIndex] = $valor;
                    $paramIndex++;
                }
                
                $placeholdersStr = implode(',', $placeholders);
                $whereClauses[] = "(pa{$atributo_id}.atributo_id = {$atributo_id} AND pa{$atributo_id}.valor IN ({$placeholdersStr}))";
            }
            
            if (empty($whereClauses)) {
                return [];
            }
            
            // Construir JOINs dinámicamente
            $joins = [];
            $groupBy = [];
            foreach (array_keys($filtros) as $atributo_id) {
                $joins[] = "INNER JOIN " . $this->table_name . " pa{$atributo_id} ON p.id = pa{$atributo_id}.producto_id";
                $groupBy[] = "pa{$atributo_id}.producto_id";
            }
            
            $joinsStr = implode(' ', $joins);
            $whereStr = implode(' AND ', $whereClauses);
            
            $query = "SELECT DISTINCT p.id, p.nombre, p.descripcion, p.precio, p.stock, 
                             p.categoria_id, p.sku, p.activo, p.created_at, p.updated_at,
                             c.nombre as categoria_nombre
                      FROM productos p
                      LEFT JOIN categorias c ON p.categoria_id = c.id
                      {$joinsStr}
                      WHERE p.activo = 1 AND {$whereStr}
                      GROUP BY p.id
                      HAVING COUNT(DISTINCT CASE ";
            
            // Agregar HAVING para que coincida con TODOS los filtros
            $havingParts = [];
            foreach (array_keys($filtros) as $atributo_id) {
                $havingParts[] = "WHEN pa{$atributo_id}.atributo_id = {$atributo_id} THEN pa{$atributo_id}.atributo_id END";
            }
            
            $query .= implode(' ', $havingParts) . ") = " . count($filtros) . "
                      ORDER BY p.nombre ASC";
            
            if ($limit) {
                $query .= " LIMIT {$limit} OFFSET {$offset}";
            }
            
            $stmt = $this->conn->prepare($query);
            
            // Bind parámetros
            foreach ($params as $index => $value) {
                $stmt->bindValue($index, $value);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error PDO al filtrar productos por atributos: " . $e->getMessage());
            return [];
        } catch (Exception $e) {
            error_log("Error general al filtrar productos por atributos: " . $e->getMessage());
            return [];
        }
    }
}
