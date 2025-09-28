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
     * Leer todos los atributos
     */
    public function readAll() {
        try {
            $query = "SELECT id, nombre, tipo, created_at, updated_at
                      FROM " . $this->table_name . " 
                      ORDER BY nombre ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
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
                $query .= " AND (c.id = :categoria_id OR c.parent_id = :categoria_id)";
            }
            
            $query .= " ORDER BY a.nombre, pa.valor";
            
            $stmt = $this->conn->prepare($query);
            
            if ($categoria_id !== null) {
                $stmt->bindParam(':categoria_id', $categoria_id, PDO::PARAM_INT);
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
}