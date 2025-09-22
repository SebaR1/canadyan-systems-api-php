<?php
/**
 * Modelo Categoria
 * Canadian Sistemas API
 */

require_once __DIR__ . '/../config/database.php';

class Categoria {
    private $conn;
    private $table_name = "categorias";
    
    // Propiedades del objeto
    public $id;
    public $nombre;
    public $slug;
    public $parent_id;
    
    // Constructor
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Crear nueva categoría
     */
    public function create() {
        try {
            // Limpiar datos
            $this->nombre = htmlspecialchars(strip_tags($this->nombre));
            $this->parent_id = !empty($this->parent_id) ? $this->parent_id : null;
            
            // Generar slug automáticamente
            $this->slug = $this->generateSlug($this->nombre);
            
            // Verificar que el slug no exista
            if ($this->slugExists($this->slug)) {
                // Agregar número al slug para hacerlo único
                $counter = 1;
                $originalSlug = $this->slug;
                while ($this->slugExists($this->slug)) {
                    $this->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
            
            $query = "INSERT INTO " . $this->table_name . " 
                      SET nombre=:nombre, slug=:slug, parent_id=:parent_id";
            
            $stmt = $this->conn->prepare($query);
            
            // Bind parámetros
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':slug', $this->slug);
            $stmt->bindParam(':parent_id', $this->parent_id);
            
            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }
            
            error_log("Error al crear categoría: No se pudo ejecutar la query");
            return false;
            
        } catch (PDOException $e) {
            error_log("Error PDO al crear categoría: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error general al crear categoría: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Leer todas las categorías (lista plana)
     */
    public function readAll() {
        $query = "SELECT id, nombre, slug, parent_id 
                  FROM " . $this->table_name . " 
                  ORDER BY nombre ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener categoría por ID
     */
    public function readOne() {
        $query = "SELECT id, nombre, slug, parent_id 
                  FROM " . $this->table_name . " 
                  WHERE id = ? 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->nombre = $row['nombre'];
            $this->slug = $row['slug'];
            $this->parent_id = $row['parent_id'];
            return true;
        }
        
        return false;
    }
    
    /**
     * Obtener árbol de categorías (estructura jerárquica)
     */
    public function getTree() {
        $query = "SELECT id, nombre, slug, parent_id 
                  FROM " . $this->table_name . " 
                  ORDER BY parent_id, nombre";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->buildTree($categories);
    }
    
    /**
     * Construir estructura jerárquica
     */
    private function buildTree($categories, $parentId = null, $processed = []) {
        $tree = [];
        
        foreach ($categories as $category) {
            // Prevenir bucles infinitos
            if (in_array($category['id'], $processed)) {
                continue;
            }
            
            if ($category['parent_id'] == $parentId) {
                $processed[] = $category['id'];
                $category['children'] = $this->buildTree($categories, $category['id'], $processed);
                $tree[] = $category;
            }
        }
        
        return $tree;
    }
    
    /**
     * Actualizar categoría
     */
    public function update() {
        try {
            // Limpiar datos
            $this->nombre = htmlspecialchars(strip_tags($this->nombre));
            $this->parent_id = !empty($this->parent_id) ? $this->parent_id : null;
            $this->id = htmlspecialchars(strip_tags($this->id));
            
            // Generar nuevo slug si cambió el nombre
            $this->slug = $this->generateSlug($this->nombre);
            
            // Verificar que el slug no exista (excluyendo el actual)
            if ($this->slugExists($this->slug, $this->id)) {
                // Agregar número al slug para hacerlo único
                $counter = 1;
                $originalSlug = $this->slug;
                while ($this->slugExists($this->slug, $this->id)) {
                    $this->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
            
            $query = "UPDATE " . $this->table_name . " 
                      SET nombre = :nombre, 
                          slug = :slug, 
                          parent_id = :parent_id 
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            
            // Bind parámetros
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':slug', $this->slug);
            $stmt->bindParam(':parent_id', $this->parent_id);
            $stmt->bindParam(':id', $this->id);
            
            if ($stmt->execute()) {
                return true;
            }
            
            error_log("Error al actualizar categoría: No se pudo ejecutar la query");
            return false;
            
        } catch (PDOException $e) {
            error_log("Error PDO al actualizar categoría: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error general al actualizar categoría: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Eliminar categoría
     */
    public function delete() {
        try {
            // Verificar que no tenga productos asociados
            $checkProducts = "SELECT COUNT(*) as count FROM productos WHERE categoria_id = ?";
            $stmt = $this->conn->prepare($checkProducts);
            $stmt->bindParam(1, $this->id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                error_log("Error al eliminar categoría ID {$this->id}: Tiene productos asociados");
                return false; // No se puede eliminar, tiene productos
            }
            
            // Verificar que no tenga subcategorías
            $checkChildren = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE parent_id = ?";
            $stmt = $this->conn->prepare($checkChildren);
            $stmt->bindParam(1, $this->id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                error_log("Error al eliminar categoría ID {$this->id}: Tiene subcategorías");
                return false; // No se puede eliminar, tiene subcategorías
            }
            
            // Eliminar categoría
            $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->id);
            
            if ($stmt->execute()) {
                return true;
            }
            
            error_log("Error al eliminar categoría ID {$this->id}: No se pudo ejecutar la query");
            return false;
            
        } catch (PDOException $e) {
            error_log("Error PDO al eliminar categoría ID {$this->id}: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error general al eliminar categoría ID {$this->id}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener categorías hijas
     */
    public function getChildren() {
        $query = "SELECT id, nombre, slug, parent_id 
                  FROM " . $this->table_name . " 
                  WHERE parent_id = ? 
                  ORDER BY nombre";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Generar slug desde nombre
     */
    private function generateSlug($nombre) {
        // Convertir a minúsculas
        $slug = strtolower(trim($nombre));
        
        // Reemplazar caracteres especiales
        $slug = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'],
            ['a', 'e', 'i', 'o', 'u', 'n', 'u'],
            $slug
        );
        
        // Reemplazar espacios y caracteres no alfanuméricos con guiones
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        
        // Eliminar guiones duplicados
        $slug = preg_replace('/-+/', '-', $slug);
        
        // Eliminar guiones al inicio y final
        return trim($slug, '-');
    }
    
    /**
     * Verificar si slug existe
     */
    public function slugExists($slug, $excludeId = null) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE slug = ?";
        
        if ($excludeId) {
            $query .= " AND id != ?";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $slug);
        
        if ($excludeId) {
            $stmt->bindParam(2, $excludeId);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    }
}
