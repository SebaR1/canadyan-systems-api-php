<?php
/**
 * Modelo ProductoAtributo - CORREGIDO
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
     * Procesar datos de producto para asegurar tipos correctos
     */
    private function processProductData($data) {
        if (!$data) return $data;
        
        // Si es un array de productos
        if (isset($data[0])) {
            return array_map([$this, 'processSingleProduct'], $data);
        }
        
        // Si es un solo producto
        return $this->processSingleProduct($data);
    }
    
    /**
     * Procesar un solo producto para asegurar tipos correctos
     */
    private function processSingleProduct($producto) {
        if (!$producto) return $producto;
        
        // Convertir precio a float o null
        if (isset($producto['precio'])) {
            $producto['precio'] = !empty($producto['precio']) ? floatval($producto['precio']) : null;
        }
        
        // Asegurar que stock sea integer
        if (isset($producto['stock'])) {
            $producto['stock'] = intval($producto['stock']);
        }
        
        // Asegurar que activo sea boolean (1/0)
        if (isset($producto['activo'])) {
            $producto['activo'] = intval($producto['activo']);
        }
        
        // Asegurar IDs como integers
        if (isset($producto['id'])) {
            $producto['id'] = intval($producto['id']);
        }
        
        if (isset($producto['categoria_id'])) {
            $producto['categoria_id'] = intval($producto['categoria_id']);
        }
        
        return $producto;
    }
    
    /**
     * Filtrar productos por múltiples atributos CON FILTRO POR CATEGORÍA
     */
    public function filtrarProductosPorAtributos($filtros, $limit = null, $offset = 0, $categoria_id = null) {
        try {
            if (empty($filtros)) {
                return $this->getProductosBasicos($limit, $offset, $categoria_id);
            }
            
            // Preparar condiciones y parámetros
            $whereConditions = [];
            $params = [];
            
            // Construir condiciones para cada atributo
            foreach ($filtros as $atributo_id => $valores) {
                if (!is_array($valores) || empty($valores)) continue;
                
                $atributo_id = intval($atributo_id);
                $placeholders = [];
                
                foreach ($valores as $valor) {
                    if (empty($valor)) continue;
                    $placeholders[] = "?";
                    $params[] = trim($valor);
                }
                
                if (!empty($placeholders)) {
                    $placeholdersStr = implode(',', $placeholders);
                    $whereConditions[] = "(pa_sub.atributo_id = {$atributo_id} AND pa_sub.valor IN ({$placeholdersStr}))";
                }
            }
            
            if (empty($whereConditions)) {
                return $this->getProductosBasicos($limit, $offset, $categoria_id);
            }
            
            // Query principal SIMPLIFICADA
            $query = "SELECT DISTINCT 
                        p.id, p.nombre, p.descripcion, p.precio, p.stock, 
                        p.categoria_id, p.sku, p.activo, p.created_at, p.updated_at,
                        c.nombre as categoria_nombre
                    FROM productos p
                    LEFT JOIN categorias c ON p.categoria_id = c.id
                    WHERE p.activo = 1";
            
            $finalParams = [];
            
            // Filtro por categoría
            if ($categoria_id !== null) {
                $query .= " AND (p.categoria_id = ? OR c.parent_id = ?)";
                $finalParams[] = $categoria_id;
                $finalParams[] = $categoria_id;
            }
            
            // Subconsulta para filtro AND - CORREGIDA
            $whereClause = implode(' OR ', $whereConditions);
            
            $query .= " AND p.id IN (
                SELECT pa_sub.producto_id 
                FROM " . $this->table_name . " pa_sub
                WHERE ({$whereClause})
                GROUP BY pa_sub.producto_id 
                HAVING COUNT(DISTINCT pa_sub.atributo_id) = " . count($filtros) . "
            )";
            
            // Agregar parámetros de filtros
            foreach ($params as $param) {
                $finalParams[] = $param;
            }
            
            $query .= " ORDER BY p.nombre ASC";
            
            if ($limit) {
                $query .= " LIMIT {$limit} OFFSET {$offset}";
            }
            
            $stmt = $this->conn->prepare($query);
            
            // Bind parámetros
            foreach ($finalParams as $index => $value) {
                $stmt->bindValue($index + 1, $value);
            }
            
            $stmt->execute();
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->processProductData($productos);
            
        } catch (PDOException $e) {
            error_log("Error PDO al filtrar productos por atributos: " . $e->getMessage());
            error_log("Query: " . ($query ?? 'N/A'));
            error_log("Params: " . print_r($finalParams ?? [], true));
            return [];
        } catch (Exception $e) {
            error_log("Error general al filtrar productos por atributos: " . $e->getMessage());
            return [];
        }
    }    
    /**
     * Obtener productos básicos (sin filtros) CON FILTRO POR CATEGORÍA
     */
    private function getProductosBasicos($limit = null, $offset = 0, $categoria_id = null) {
        try {
            $query = "SELECT DISTINCT 
                        p.id, p.nombre, p.descripcion, p.precio, p.stock, 
                        p.categoria_id, p.sku, p.activo, p.created_at, p.updated_at,
                        c.nombre as categoria_nombre
                    FROM productos p
                    LEFT JOIN categorias c ON p.categoria_id = c.id
                    WHERE p.activo = 1";
            
            $params = [];
            
            // AGREGAR FILTRO POR CATEGORÍA
            if ($categoria_id !== null) {
                $query .= " AND (p.categoria_id = ? OR c.parent_id = ?)";
                $params[] = $categoria_id;
                $params[] = $categoria_id;
            }
            
            $query .= " ORDER BY p.nombre ASC";
            
            if ($limit) {
                $query .= " LIMIT {$limit} OFFSET {$offset}";
            }
            
            $stmt = $this->conn->prepare($query);
            
            // Bind parámetros si los hay
            foreach ($params as $index => $value) {
                $stmt->bindValue($index + 1, $value);
            }
            
            $stmt->execute();
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->processProductData($productos);
            
        } catch (PDOException $e) {
            error_log("Error PDO al obtener productos básicos: " . $e->getMessage());
            return [];
        } catch (Exception $e) {
            error_log("Error general al obtener productos básicos: " . $e->getMessage());
            return [];
        }
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
            
            if ($stmt->execute()) {
                return true;
            }
            
            error_log("Error al actualizar producto_atributo: No se pudo ejecutar la query");
            return false;
            
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
    public function delete($producto_id, $atributo_id) {
        try {
            $query = "DELETE FROM " . $this->table_name . " 
                      WHERE producto_id = ? AND atributo_id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $producto_id);
            $stmt->bindParam(2, $atributo_id);
            
            if ($stmt->execute()) {
                return true;
            }
            
            error_log("Error al eliminar producto_atributo: No se pudo ejecutar la query");
            return false;
            
        } catch (PDOException $e) {
            error_log("Error PDO al eliminar producto_atributo: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error general al eliminar producto_atributo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar si existe una relación producto-atributo
     */
    public function exists($producto_id, $atributo_id) {
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
            error_log("Error PDO al verificar existencia de producto_atributo: " . $e->getMessage());
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
     * Guardar múltiples atributos de un producto
     * Elimina los anteriores y guarda los nuevos
     */
    public function saveProductoAtributos($producto_id, $atributos) {
        try {
            // Validar producto_id
            if (empty($producto_id) || !is_numeric($producto_id)) {
                error_log("Error en saveProductoAtributos: producto_id inválido");
                return false;
            }
            
            $producto_id = intval($producto_id);
            
            // Verificar que el producto existe
            if (!$this->productoExists($producto_id)) {
                error_log("Error en saveProductoAtributos: El producto ID {$producto_id} no existe");
                return false;
            }
            
            // Iniciar transacción
            $this->conn->beginTransaction();
            
            // 1. Eliminar atributos anteriores del producto
            $deleteQuery = "DELETE FROM " . $this->table_name . " WHERE producto_id = ?";
            $deleteStmt = $this->conn->prepare($deleteQuery);
            $deleteStmt->bindParam(1, $producto_id);
            $deleteStmt->execute();
            
            // 2. Insertar nuevos atributos
            if (!empty($atributos) && is_array($atributos)) {
                $insertQuery = "INSERT INTO " . $this->table_name . " 
                            (producto_id, atributo_id, valor) 
                            VALUES (?, ?, ?)";
                $insertStmt = $this->conn->prepare($insertQuery);
                
                foreach ($atributos as $atributo) {
                    // Validar estructura del atributo
                    if (!isset($atributo['atributo_id']) || !isset($atributo['valor'])) {
                        continue; // Saltar atributos mal formados
                    }
                    
                    $atributo_id = intval($atributo['atributo_id']);
                    $valor = htmlspecialchars(strip_tags($atributo['valor']));
                    
                    // Saltar si el valor está vacío
                    if (empty(trim($valor))) {
                        continue;
                    }
                    
                    // Verificar que el atributo existe
                    if (!$this->atributoExists($atributo_id)) {
                        error_log("Advertencia: Atributo ID {$atributo_id} no existe, saltando...");
                        continue;
                    }
                    
                    $insertStmt->bindParam(1, $producto_id);
                    $insertStmt->bindParam(2, $atributo_id);
                    $insertStmt->bindParam(3, $valor);
                    $insertStmt->execute();
                }
            }
            
            // Confirmar transacción
            $this->conn->commit();
            return true;
            
        } catch (PDOException $e) {
            // Revertir en caso de error
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Error PDO en saveProductoAtributos: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            // Revertir en caso de error
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Error general en saveProductoAtributos: " . $e->getMessage());
            return false;
        }
    }
}


?>