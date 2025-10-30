<?php
/**
 * Modelo Atributo
 * Canadian Sistemas API
 */

require_once __DIR__ . '/../config/database.php';

class Atributo {
    private $conn;
    private $table_name = "atributos";
    
    // Propiedades del objeto
    public $id;
    public $nombre;
    public $tipo;
    public $created_at;
    public $updated_at;
    
    // Constructor
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Crear nuevo atributo
     */
    public function create() {
        try {
            // Limpiar datos
            $this->nombre = htmlspecialchars(strip_tags($this->nombre));
            $this->tipo = htmlspecialchars(strip_tags($this->tipo));
            
            // Validar tipo
            $tiposPermitidos = ['text', 'select', 'number', 'boolean'];
            if (!in_array($this->tipo, $tiposPermitidos)) {
                error_log("Error al crear atributo: Tipo '{$this->tipo}' no válido");
                return false;
            }
            
            $query = "INSERT INTO " . $this->table_name . " 
                      SET nombre=:nombre, tipo=:tipo";
            
            $stmt = $this->conn->prepare($query);
            
            // Bind parámetros
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':tipo', $this->tipo);
            
            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }
            
            error_log("Error al crear atributo: No se pudo ejecutar la query");
            return false;
            
        } catch (PDOException $e) {
            error_log("Error PDO al crear atributo: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error general al crear atributo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Leer todos los atributos - CON CONTEO DE PRODUCTOS
     */
    public function readAll() {
        try {
            $query = "SELECT 
                        a.id, 
                        a.nombre, 
                        a.tipo, 
                        a.created_at, 
                        a.updated_at,
                        COUNT(DISTINCT pa.producto_id) as productos_count
                    FROM " . $this->table_name . " a
                    LEFT JOIN producto_atributos pa ON a.id = pa.atributo_id
                    GROUP BY a.id, a.nombre, a.tipo, a.created_at, a.updated_at
                    ORDER BY a.nombre ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convertir productos_count a integer
            foreach ($result as &$row) {
                $row['productos_count'] = intval($row['productos_count']);
            }
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Error PDO al leer atributos: " . $e->getMessage());
            return [];
        } catch (Exception $e) {
            error_log("Error general al leer atributos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener atributo por ID
     */
    public function readOne() {
        try {
            $query = "SELECT id, nombre, tipo, created_at, updated_at
                      FROM " . $this->table_name . " 
                      WHERE id = ? 
                      LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->id);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                $this->nombre = $row['nombre'];
                $this->tipo = $row['tipo'];
                $this->created_at = $row['created_at'];
                $this->updated_at = $row['updated_at'];
                return $row;
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Error PDO al leer atributo ID {$this->id}: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error general al leer atributo ID {$this->id}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualizar atributo
     */
    public function update() {
        try {
            // Limpiar datos
            $this->nombre = htmlspecialchars(strip_tags($this->nombre));
            $this->tipo = htmlspecialchars(strip_tags($this->tipo));
            $this->id = intval($this->id);
            
            // Validar tipo
            $tiposPermitidos = ['text', 'select', 'number', 'boolean'];
            if (!in_array($this->tipo, $tiposPermitidos)) {
                error_log("Error al actualizar atributo: Tipo '{$this->tipo}' no válido");
                return false;
            }
            
            $query = "UPDATE " . $this->table_name . " 
                      SET nombre = :nombre, 
                          tipo = :tipo 
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            
            // Bind parámetros
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':tipo', $this->tipo);
            $stmt->bindParam(':id', $this->id);
            
            if ($stmt->execute()) {
                return true;
            }
            
            error_log("Error al actualizar atributo ID {$this->id}: No se pudo ejecutar la query");
            return false;
            
        } catch (PDOException $e) {
            error_log("Error PDO al actualizar atributo ID {$this->id}: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error general al actualizar atributo ID {$this->id}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Eliminar atributo
     */
    public function delete() {
        try {
            // Verificar que no tenga productos asociados
            $checkProducts = "SELECT COUNT(*) as count FROM producto_atributos WHERE atributo_id = ?";
            $stmt = $this->conn->prepare($checkProducts);
            $stmt->bindParam(1, $this->id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                error_log("Error al eliminar atributo ID {$this->id}: Tiene productos asociados");
                return false; // No se puede eliminar, tiene productos
            }
            
            // Eliminar atributo
            $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->id);
            
            if ($stmt->execute()) {
                return true;
            }
            
            error_log("Error al eliminar atributo ID {$this->id}: No se pudo ejecutar la query");
            return false;
            
        } catch (PDOException $e) {
            error_log("Error PDO al eliminar atributo ID {$this->id}: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error general al eliminar atributo ID {$this->id}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener valores únicos de un atributo (para filtros)
     */
    public function getValoresUnicos() {
        try {
            $query = "SELECT DISTINCT valor 
                      FROM producto_atributos pa
                      INNER JOIN productos p ON pa.producto_id = p.id
                      WHERE pa.atributo_id = ? AND p.activo = 1 AND pa.valor IS NOT NULL AND pa.valor != ''
                      ORDER BY valor ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->id);
            $stmt->execute();
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Devolver array simple de valores
            return array_column($result, 'valor');
            
        } catch (PDOException $e) {
            error_log("Error PDO al obtener valores únicos del atributo ID {$this->id}: " . $e->getMessage());
            return [];
        } catch (Exception $e) {
            error_log("Error general al obtener valores únicos del atributo ID {$this->id}: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener todos los atributos con sus valores únicos (para filtros del catálogo)
     * Si se proporciona categoria_id, filtrar solo productos de esa categoría (incluyendo subcategorías)
     */
    public function getAtributosConValores($categoria_id = null) {
        try {
            // Query base
            $query = "SELECT DISTINCT 
                        a.id, 
                        a.nombre, 
                        a.tipo,
                        pa.valor
                    FROM " . $this->table_name . " a
                    INNER JOIN producto_atributos pa ON a.id = pa.atributo_id
                    INNER JOIN productos p ON pa.producto_id = p.id";
            
            // Si hay categoria_id, agregar JOIN con categorías y filtrar
            if ($categoria_id !== null) {
                $query .= " INNER JOIN categorias c ON p.categoria_id = c.id";
            }
            
            $query .= " WHERE p.activo = 1 AND pa.valor IS NOT NULL AND pa.valor != ''";
            
            // Filtrar por categoría (incluyendo subcategorías)
            if ($categoria_id !== null) {
                $query .= " AND (c.id = :categoria_id1 OR c.parent_id = :categoria_id2)";
            }
            
            $query .= " ORDER BY a.nombre, pa.valor";
            
            $stmt = $this->conn->prepare($query);
            
            if ($categoria_id !== null) {
                $stmt->bindParam(':categoria_id1', $categoria_id, PDO::PARAM_INT);
                $stmt->bindParam(':categoria_id2', $categoria_id, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Agrupar valores por atributo
            $atributos = [];
            foreach ($result as $row) {
                $atributo_id = $row['id'];
                
                if (!isset($atributos[$atributo_id])) {
                    $atributos[$atributo_id] = [
                        'id' => $row['id'],
                        'nombre' => $row['nombre'],
                        'tipo' => $row['tipo'],
                        'valores' => []
                    ];
                }
                
                if (!in_array($row['valor'], $atributos[$atributo_id]['valores'])) {
                    $atributos[$atributo_id]['valores'][] = $row['valor'];
                }
            }
            
            // Convertir a array indexado
            return array_values($atributos);
            
        } catch (PDOException $e) {
            error_log("Error PDO al obtener atributos con valores: " . $e->getMessage());
            return [];
        } catch (Exception $e) {
            error_log("Error general al obtener atributos con valores: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Verificar si un atributo existe por nombre
     */
    public function existsByName($nombre) {
        try {
            $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE nombre = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $nombre);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
            
        } catch (PDOException $e) {
            error_log("Error PDO al verificar atributo por nombre: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener atributos con valores basándose en productos ya filtrados (FILTROS DINÁMICOS)
     */
    public function getAtributosDinamicos($categoria_id = null, $filtros_aplicados = []) {
        try {
            $params = [];
            
            // Query base - obtener productos que cumplen con filtros existentes
            $productosQuery = "SELECT DISTINCT p.id FROM productos p 
                            LEFT JOIN categorias c ON p.categoria_id = c.id 
                            WHERE p.activo = 1";
            
            // Aplicar filtro por categoría si existe
            if ($categoria_id !== null) {
                $productosQuery .= " AND (p.categoria_id = ? OR c.parent_id = ?)";
                $params[] = $categoria_id;
                $params[] = $categoria_id;
            }
            
            // Aplicar filtros existentes si existen
            if (!empty($filtros_aplicados)) {
                $whereConditions = [];
                
                foreach ($filtros_aplicados as $atributo_id => $valores) {
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
                        $whereConditions[] = "(pa_filter.atributo_id = {$atributo_id} AND pa_filter.valor IN ({$placeholdersStr}))";
                    }
                }
                
                if (!empty($whereConditions)) {
                    $whereClause = implode(' OR ', $whereConditions);
                    
                    $productosQuery .= " AND p.id IN (
                        SELECT DISTINCT pa_filter.producto_id 
                        FROM producto_atributos pa_filter 
                        WHERE ({$whereClause})";
                    
                    // Si hay múltiples atributos, todos deben coincidir
                    if (count($filtros_aplicados) > 1) {
                        $productosQuery .= " GROUP BY pa_filter.producto_id 
                                            HAVING COUNT(DISTINCT pa_filter.atributo_id) >= " . count($filtros_aplicados);
                    }
                    
                    $productosQuery .= ")";
                }
            }

            // Ahora obtener los atributos disponibles para esos productos filtrados
            $query = "SELECT DISTINCT 
                        a.id, 
                        a.nombre, 
                        a.tipo,
                        pa.valor
                    FROM " . $this->table_name . " a
                    INNER JOIN producto_atributos pa ON a.id = pa.atributo_id
                    WHERE pa.producto_id IN ({$productosQuery})
                    AND pa.valor IS NOT NULL 
                    AND pa.valor != ''
                    ORDER BY a.nombre, pa.valor";

            error_log("FILTROS DINÁMICOS - Query: " . $query);
            error_log("FILTROS DINÁMICOS - Params: " . print_r($params, true));
            error_log("FILTROS DINÁMICOS - Filtros aplicados: " . print_r($filtros_aplicados, true));

            

            
            $stmt = $this->conn->prepare($query);
            
            // Bind todos los parámetros (duplicados para la subconsulta)
            foreach ($params as $index => $value) {
                $stmt->bindValue($index + 1, $value);
            }
            
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Agrupar valores por atributo
            $atributos = [];
            foreach ($result as $row) {
                $atributo_id = $row['id'];
                
                if (!isset($atributos[$atributo_id])) {
                    $atributos[$atributo_id] = [
                        'id' => $row['id'],
                        'nombre' => $row['nombre'],
                        'tipo' => $row['tipo'],
                        'valores' => []
                    ];
                }
                
                if (!in_array($row['valor'], $atributos[$atributo_id]['valores'])) {
                    $atributos[$atributo_id]['valores'][] = $row['valor'];
                }
            }
            
            // Convertir a array indexado
            return array_values($atributos);
            
        } catch (PDOException $e) {
            error_log("Error PDO al obtener atributos dinámicos: " . $e->getMessage());
            return [];
        } catch (Exception $e) {
            error_log("Error general al obtener atributos dinámicos: " . $e->getMessage());
            return [];
        }
    }
}