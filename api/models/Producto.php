<?php
/**
 * Modelo Producto - CORREGIDO para conversión de tipos
 * Canadian Sistemas API
 */

require_once __DIR__ . '/../config/database.php';

class Producto {
    private $conn;
    private $table_name = "productos";
    
    // Propiedades del objeto
    public $id;
    public $nombre;
    public $descripcion;
    public $precio;
    public $stock;
    public $categoria_id;
    public $sku;
    public $activo;
    public $created_at;
    public $updated_at;
    
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
     * Leer todos los productos (con paginación opcional)
     */
    public function readAll($limit = null, $offset = 0, $activeOnly = true) {
        try {
            $whereClause = $activeOnly ? "WHERE p.activo = 1" : "";
            
            $query = "SELECT p.id, p.nombre, p.descripcion, p.precio, p.stock, 
                            p.categoria_id, p.sku, p.activo, p.created_at, p.updated_at,
                            c.nombre as categoria_nombre,
                            pi.url as imagen_principal_url
                    FROM " . $this->table_name . " p
                    LEFT JOIN categorias c ON p.categoria_id = c.id
                    LEFT JOIN (
                        SELECT producto_id, url 
                        FROM producto_imagenes 
                        WHERE tipo = 'principal' 
                            OR id IN (
                                SELECT MIN(id) 
                                FROM producto_imagenes 
                                GROUP BY producto_id
                            )
                        GROUP BY producto_id
                    ) pi ON p.id = pi.producto_id
                    {$whereClause}
                    ORDER BY p.created_at DESC";
            
            if ($limit) {
                $query .= " LIMIT :limit OFFSET :offset";
            }
            
            $stmt = $this->conn->prepare($query);
            
            if ($limit) {
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // CORRECCIÓN: Procesar datos para asegurar tipos correctos
            return $this->processProductData($productos);
            
        } catch (PDOException $e) {
            error_log("Error PDO al leer productos: " . $e->getMessage());
            return [];
        } catch (Exception $e) {
            error_log("Error general al leer productos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Leer productos con filtros avanzados (para admin)
     * Soporta filtros de búsqueda, categoría y estado
     */
    public function readAllFiltered($limit = null, $offset = 0, $filters = []) {
        try {
            // Extraer filtros
            $search = $filters['search'] ?? '';
            $categoria_id = $filters['categoria_id'] ?? null;
            $activo = $filters['activo'] ?? null; // null = todos, 1 = activos, 0 = inactivos
            
            // Construir WHERE clause dinámicamente
            $whereConditions = [];
            $params = [];
            
            // Filtro de búsqueda
            if (!empty($search)) {
                $searchPattern = '%' . htmlspecialchars(strip_tags($search)) . '%';
                $whereConditions[] = "(p.nombre LIKE ? OR p.descripcion LIKE ?)";
                $params[] = $searchPattern;
                $params[] = $searchPattern;
            }
            
            // Filtro de categoría
            if ($categoria_id !== null && is_numeric($categoria_id)) {
                // Obtener IDs de subcategorías si existen
                $querySubcats = "SELECT id FROM categorias WHERE parent_id = ?";
                $stmtSubcats = $this->conn->prepare($querySubcats);
                $stmtSubcats->bindValue(1, intval($categoria_id));
                $stmtSubcats->execute();
                $subcategorias = $stmtSubcats->fetchAll(PDO::FETCH_COLUMN);
                
                if (!empty($subcategorias)) {
                    // Tiene subcategorías - incluir padre + hijos
                    $idsIncluir = array_merge([intval($categoria_id)], array_map('intval', $subcategorias));
                    $placeholders = implode(',', array_fill(0, count($idsIncluir), '?'));
                    $whereConditions[] = "p.categoria_id IN ($placeholders)";
                    foreach ($idsIncluir as $id) {
                        $params[] = $id;
                    }
                } else {
                    // No tiene subcategorías - solo el ID dado
                    $whereConditions[] = "p.categoria_id = ?";
                    $params[] = intval($categoria_id);
                }
            }
            
            // Filtro de estado
            if ($activo !== null) {
                $whereConditions[] = "p.activo = ?";
                $params[] = intval($activo);
            }
            
            // Construir query
            $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
            
            $query = "SELECT p.id, p.nombre, p.descripcion, p.precio, p.stock, 
                            p.categoria_id, p.sku, p.activo, p.created_at, p.updated_at,
                            c.nombre as categoria_nombre,
                            pi.url as imagen_principal_url
                    FROM " . $this->table_name . " p
                    LEFT JOIN categorias c ON p.categoria_id = c.id
                    LEFT JOIN (
                        SELECT producto_id, url 
                        FROM producto_imagenes 
                        WHERE tipo = 'principal' 
                            OR id IN (
                                SELECT MIN(id) 
                                FROM producto_imagenes 
                                GROUP BY producto_id
                            )
                        GROUP BY producto_id
                    ) pi ON p.id = pi.producto_id
                    {$whereClause}
                    ORDER BY p.created_at DESC";
            
            if ($limit) {
                $query .= " LIMIT ? OFFSET ?";
                $params[] = intval($limit);
                $params[] = intval($offset);
            }
            
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            foreach ($params as $index => $value) {
                $stmt->bindValue($index + 1, $value);
            }
            
            $stmt->execute();
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->processProductData($productos);
            
        } catch (PDOException $e) {
            error_log("Error PDO en readAllFiltered: " . $e->getMessage());
            error_log("Query: " . ($query ?? 'N/A'));
            error_log("Params: " . print_r($params ?? [], true));
            return [];
        } catch (Exception $e) {
            error_log("Error general en readAllFiltered: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar productos con filtros (para paginación en admin)
     */
    public function countFiltered($filters = []) {
        try {
            // Extraer filtros
            $search = $filters['search'] ?? '';
            $categoria_id = $filters['categoria_id'] ?? null;
            $activo = $filters['activo'] ?? null;
            
            // Construir WHERE clause
            $whereConditions = [];
            $params = [];
            
            if (!empty($search)) {
                $searchPattern = '%' . htmlspecialchars(strip_tags($search)) . '%';
                $whereConditions[] = "(nombre LIKE ? OR descripcion LIKE ?)";
                $params[] = $searchPattern;
                $params[] = $searchPattern;
            }

            // Filtro de categoría
            if ($categoria_id !== null && is_numeric($categoria_id)) {
                // Obtener IDs de subcategorías si existen
                $querySubcats = "SELECT id FROM categorias WHERE parent_id = ?";
                $stmtSubcats = $this->conn->prepare($querySubcats);
                $stmtSubcats->bindValue(1, intval($categoria_id));
                $stmtSubcats->execute();
                $subcategorias = $stmtSubcats->fetchAll(PDO::FETCH_COLUMN);
                
                if (!empty($subcategorias)) {
                    // Tiene subcategorías - incluir padre + hijos
                    $idsIncluir = array_merge([intval($categoria_id)], array_map('intval', $subcategorias));
                    $placeholders = implode(',', array_fill(0, count($idsIncluir), '?'));
                    $whereConditions[] = "p.categoria_id IN ($placeholders)";
                    foreach ($idsIncluir as $id) {
                        $params[] = $id;
                    }
                } else {
                    // No tiene subcategorías - solo el ID dado
                    $whereConditions[] = "p.categoria_id = ?";
                    $params[] = intval($categoria_id);
                }
            }
            
            if ($activo !== null) {
                $whereConditions[] = "activo = ?";
                $params[] = intval($activo);
            }
            
            $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
            
            $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " " . $whereClause;
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $index => $value) {
                $stmt->bindValue($index + 1, $value);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return intval($result['total']);
            
        } catch (PDOException $e) {
            error_log("Error PDO en countFiltered: " . $e->getMessage());
            return 0;
        } catch (Exception $e) {
            error_log("Error general en countFiltered: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener producto por ID
     */
    public function readOne() {
        try {
            $query = "SELECT p.id, p.nombre, p.descripcion, p.precio, p.stock, 
                             p.categoria_id, p.sku, p.activo, p.created_at, p.updated_at,
                             c.nombre as categoria_nombre
                      FROM " . $this->table_name . " p
                      LEFT JOIN categorias c ON p.categoria_id = c.id
                      WHERE p.id = ? 
                      LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->id);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                // CORRECCIÓN: Procesar datos para asegurar tipos correctos
                $processedRow = $this->processSingleProduct($row);
                
                // Actualizar propiedades del objeto
                $this->nombre = $processedRow['nombre'];
                $this->descripcion = $processedRow['descripcion'];
                $this->precio = $processedRow['precio'];
                $this->stock = $processedRow['stock'];
                $this->categoria_id = $processedRow['categoria_id'];
                $this->sku = $processedRow['sku'];
                $this->activo = $processedRow['activo'];
                $this->created_at = $processedRow['created_at'];
                $this->updated_at = $processedRow['updated_at'];
                
                return $processedRow;
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Error PDO al leer producto ID {$this->id}: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error general al leer producto ID {$this->id}: " . $e->getMessage());
            return false;
        }
    }
    
public function getByCategory($categoryId, $limit = null, $offset = 0) {
    try {
        $query = "SELECT p.id, p.nombre, p.descripcion, p.precio, p.stock, 
                        p.categoria_id, p.sku, p.activo, p.created_at, p.updated_at,
                        c.nombre as categoria_nombre,
                        pi.url as imagen_principal_url
                FROM " . $this->table_name . " p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                LEFT JOIN (
                    SELECT producto_id, url 
                    FROM producto_imagenes 
                    WHERE tipo = 'principal' 
                        OR id IN (
                            SELECT MIN(id) 
                            FROM producto_imagenes 
                            GROUP BY producto_id
                        )
                    GROUP BY producto_id
                ) pi ON p.id = pi.producto_id
                WHERE p.categoria_id = ? AND p.activo = 1
                ORDER BY p.nombre ASC";
        
        $params = [$categoryId];
        
        if ($limit) {
            $query .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error PDO al obtener productos por categoría {$categoryId}: " . $e->getMessage());
        return [];
    } catch (Exception $e) {
        error_log("Error general al obtener productos por categoría {$categoryId}: " . $e->getMessage());
        return [];
    }
}

    /**
     * Buscar productos por nombre o descripción - CORREGIDO
     */
    public function search($searchTerm, $limit = null, $offset = 0) {
        try {
            // Preparar término de búsqueda
            $searchPattern = '%' . htmlspecialchars(strip_tags($searchTerm)) . '%';
            
            $query = "SELECT p.id, p.nombre, p.descripcion, p.precio, p.stock, 
                            p.categoria_id, p.sku, p.activo, p.created_at, p.updated_at,
                            c.nombre as categoria_nombre,
                            pi.url as imagen_principal_url
                    FROM " . $this->table_name . " p
                    LEFT JOIN categorias c ON p.categoria_id = c.id
                    LEFT JOIN (
                        SELECT producto_id, url 
                        FROM producto_imagenes 
                        WHERE tipo = 'principal' 
                        OR id IN (
                            SELECT MIN(id) 
                            FROM producto_imagenes 
                            GROUP BY producto_id
                        )
                        GROUP BY producto_id
                    ) pi ON p.id = pi.producto_id
                    WHERE (p.nombre LIKE ? OR p.descripcion LIKE ?) 
                    AND p.activo = 1
                    ORDER BY p.nombre ASC";
            
            // Agregar LIMIT y OFFSET si se especifican
            if ($limit) {
                $query .= " LIMIT ? OFFSET ?";
            }
            
            $stmt = $this->conn->prepare($query);
            
            // Usar bindValue() en lugar de bindParam()
            $stmt->bindValue(1, $searchPattern, PDO::PARAM_STR);
            $stmt->bindValue(2, $searchPattern, PDO::PARAM_STR);
            
            if ($limit) {
                $stmt->bindValue(3, $limit, PDO::PARAM_INT);
                $stmt->bindValue(4, $offset, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Procesar datos para asegurar tipos correctos
            return $this->processProductData($productos);
            
        } catch (PDOException $e) {
            error_log("Error PDO al buscar productos: " . $e->getMessage());
            error_log("Query: " . ($query ?? 'N/A'));
            return [];
        } catch (Exception $e) {
            error_log("Error general al buscar productos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Contar total de productos para paginación - MÉTODO FALTANTE AGREGADO
     */
    public function countTotal($activeOnly = true) {
        try {
            $whereClause = $activeOnly ? "WHERE activo = 1" : "";
            
            $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " " . $whereClause;
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return intval($result['total']);
            
        } catch (PDOException $e) {
            error_log("Error PDO al contar productos: " . $e->getMessage());
            return 0;
        } catch (Exception $e) {
            error_log("Error general al contar productos: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Crear nuevo producto
     */
    public function create() {
        try {
            // Limpiar datos
            $this->nombre = htmlspecialchars(strip_tags($this->nombre));
            $this->descripcion = htmlspecialchars(strip_tags($this->descripcion ?? ''));
            $this->precio = !empty($this->precio) ? floatval($this->precio) : null;
            $this->stock = intval($this->stock ?? 0);
            $this->categoria_id = intval($this->categoria_id);
            $this->sku = !empty($this->sku) ? htmlspecialchars(strip_tags($this->sku)) : null;
            $this->activo = !empty($this->activo) ? 1 : 1; // Default true
            
            // Validar que la categoría existe
            if (!$this->categoryExists($this->categoria_id)) {
                error_log("Error al crear producto: La categoría ID {$this->categoria_id} no existe");
                return false;
            }
            
            // Validar SKU único si se proporciona
            if ($this->sku && $this->skuExists($this->sku)) {
                error_log("Error al crear producto: El SKU {$this->sku} ya existe");
                return false;
            }
            
            $query = "INSERT INTO " . $this->table_name . " 
                      SET nombre=:nombre, 
                          descripcion=:descripcion, 
                          precio=:precio, 
                          stock=:stock, 
                          categoria_id=:categoria_id, 
                          sku=:sku, 
                          activo=:activo";
            
            $stmt = $this->conn->prepare($query);
            
            // Bind parámetros
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':descripcion', $this->descripcion);
            $stmt->bindParam(':precio', $this->precio);
            $stmt->bindParam(':stock', $this->stock);
            $stmt->bindParam(':categoria_id', $this->categoria_id);
            $stmt->bindParam(':sku', $this->sku);
            $stmt->bindParam(':activo', $this->activo);
            
            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }
            
            error_log("Error al crear producto: No se pudo ejecutar la query");
            return false;
            
        } catch (PDOException $e) {
            error_log("Error PDO al crear producto: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error general al crear producto: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualizar producto
     */
    public function update() {
        try {
            // Limpiar datos
            $this->nombre = htmlspecialchars(strip_tags($this->nombre));
            $this->descripcion = htmlspecialchars(strip_tags($this->descripcion ?? ''));
            $this->precio = !empty($this->precio) ? floatval($this->precio) : null;
            $this->stock = intval($this->stock ?? 0);
            $this->categoria_id = intval($this->categoria_id);
            $this->sku = !empty($this->sku) ? htmlspecialchars(strip_tags($this->sku)) : null;
            $this->activo = !empty($this->activo) ? 1 : 0;
            $this->id = intval($this->id);
            
            // Validar que la categoría existe
            if (!$this->categoryExists($this->categoria_id)) {
                error_log("Error al actualizar producto: La categoría ID {$this->categoria_id} no existe");
                return false;
            }
            
            // Validar SKU único si se proporciona (excluyendo el actual)
            if ($this->sku && $this->skuExists($this->sku, $this->id)) {
                error_log("Error al actualizar producto: El SKU {$this->sku} ya existe en otro producto");
                return false;
            }
            
            $query = "UPDATE " . $this->table_name . " 
                      SET nombre = :nombre, 
                          descripcion = :descripcion, 
                          precio = :precio, 
                          stock = :stock, 
                          categoria_id = :categoria_id, 
                          sku = :sku, 
                          activo = :activo
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            
            // Bind parámetros
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':descripcion', $this->descripcion);
            $stmt->bindParam(':precio', $this->precio);
            $stmt->bindParam(':stock', $this->stock);
            $stmt->bindParam(':categoria_id', $this->categoria_id);
            $stmt->bindParam(':sku', $this->sku);
            $stmt->bindParam(':activo', $this->activo);
            $stmt->bindParam(':id', $this->id);
            
            if ($stmt->execute()) {
                return true;
            }
            
            error_log("Error al actualizar producto ID {$this->id}: No se pudo ejecutar la query");
            return false;
            
        } catch (PDOException $e) {
            error_log("Error PDO al actualizar producto ID {$this->id}: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error general al actualizar producto ID {$this->id}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Eliminar producto (soft delete - cambiar activo a 0)
     */
    public function delete() {
        try {
            $query = "UPDATE " . $this->table_name . " SET activo = 0 WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->id);
            
            if ($stmt->execute()) {
                return true;
            }
            
            error_log("Error al eliminar producto ID {$this->id}: No se pudo ejecutar la query");
            return false;
            
        } catch (PDOException $e) {
            error_log("Error PDO al eliminar producto ID {$this->id}: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error general al eliminar producto ID {$this->id}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualizar solo el stock
     */
    public function updateStock($newStock) {
        try {
            $newStock = intval($newStock);
            
            if ($newStock < 0) {
                error_log("Error al actualizar stock: Stock no puede ser negativo");
                return false;
            }
            
            $query = "UPDATE " . $this->table_name . " SET stock = :stock WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':stock', $newStock);
            $stmt->bindParam(':id', $this->id);
            
            if ($stmt->execute()) {
                $this->stock = $newStock;
                return true;
            }
            
            error_log("Error al actualizar stock del producto ID {$this->id}: No se pudo ejecutar la query");
            return false;
            
        } catch (PDOException $e) {
            error_log("Error PDO al actualizar stock del producto ID {$this->id}: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error general al actualizar stock del producto ID {$this->id}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar si una categoría existe
     */
    private function categoryExists($categoria_id) {
        try {
            $query = "SELECT COUNT(*) as count FROM categorias WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $categoria_id);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
            
        } catch (PDOException $e) {
            error_log("Error PDO al verificar categoría: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar si un SKU existe (excluyendo el producto actual si se proporciona)
     */
    private function skuExists($sku, $excludeId = null) {
        try {
            $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE sku = ?";
            $params = [$sku];
            
            if ($excludeId !== null) {
                $query .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
            
        } catch (PDOException $e) {
            error_log("Error PDO al verificar SKU: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cambiar estado activo/inactivo del producto (toggle)
     */
    public function toggleActive() {
        try {
            // Primero obtener el estado actual
            $query = "SELECT activo FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                error_log("Error al toggle producto: Producto ID {$this->id} no encontrado");
                return false;
            }
            
            // Invertir el estado actual
            $nuevoEstado = $result['activo'] == 1 ? 0 : 1;
            
            // Actualizar en la base de datos
            $updateQuery = "UPDATE " . $this->table_name . " SET activo = :activo WHERE id = :id";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':activo', $nuevoEstado);
            $updateStmt->bindParam(':id', $this->id);
            
            if ($updateStmt->execute()) {
                $this->activo = $nuevoEstado;
                return true;
            }
            
            error_log("Error al hacer toggle del producto ID {$this->id}: No se pudo ejecutar la query");
            return false;
            
        } catch (PDOException $e) {
            error_log("Error PDO al hacer toggle del producto ID {$this->id}: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error general al hacer toggle del producto ID {$this->id}: " . $e->getMessage());
            return false;
        }
    }

}
?>