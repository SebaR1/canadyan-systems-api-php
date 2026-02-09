<?php
/**
 * Modelo MensajeContacto
 * Canadian Sistemas API
 */

require_once __DIR__ . '/../config/database.php';

class MensajeContacto {
    private $conn;
    private $table_name = "mensajes_contacto";
    
    // Propiedades del objeto
    public $id;
    public $nombre_apellido;
    public $cuit;
    public $correo_electronico;
    public $celular;
    public $localidad;
    public $razon_social_empresa;
    public $mensaje;
    public $created_at;
    
    // Constructor
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Crear nuevo mensaje de contacto
     */
    public function create() {
        try {
            // Limpiar datos
            $this->nombre_apellido = htmlspecialchars(strip_tags($this->nombre_apellido));
            $this->cuit = htmlspecialchars(strip_tags($this->cuit));
            $this->correo_electronico = htmlspecialchars(strip_tags($this->correo_electronico));
            $this->celular = htmlspecialchars(strip_tags($this->celular ?? ''));
            $this->localidad = htmlspecialchars(strip_tags($this->localidad ?? ''));
            $this->razon_social_empresa = htmlspecialchars(strip_tags($this->razon_social_empresa));
            $this->mensaje = htmlspecialchars(strip_tags($this->mensaje));
            
            $query = "INSERT INTO " . $this->table_name . " 
                      SET nombre_apellido = :nombre_apellido,
                          cuit = :cuit,
                          correo_electronico = :correo_electronico,
                          celular = :celular,
                          localidad = :localidad,
                          razon_social_empresa = :razon_social_empresa,
                          mensaje = :mensaje";
            
            $stmt = $this->conn->prepare($query);
            
            // Bind parámetros
            $stmt->bindValue(':nombre_apellido', $this->nombre_apellido);
            $stmt->bindValue(':cuit', $this->cuit);
            $stmt->bindValue(':correo_electronico', $this->correo_electronico);
            $stmt->bindValue(':celular', $this->celular);
            $stmt->bindValue(':localidad', $this->localidad);
            $stmt->bindValue(':razon_social_empresa', $this->razon_social_empresa);
            $stmt->bindValue(':mensaje', $this->mensaje);
            
            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }
            
            error_log("Error al crear mensaje de contacto: No se pudo ejecutar la query");
            return false;
            
        } catch (PDOException $e) {
            error_log("Error PDO al crear mensaje de contacto: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error general al crear mensaje de contacto: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Leer todos los mensajes
     */
    public function readAll($limit = null, $offset = 0) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                      ORDER BY created_at DESC";
            
            if ($limit) {
                $query .= " LIMIT :limit OFFSET :offset";
            }
            
            $stmt = $this->conn->prepare($query);
            
            if ($limit) {
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error PDO al leer mensajes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener mensaje por ID
     */
    public function readOne() {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(1, $this->id);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                $this->nombre_apellido = $row['nombre_apellido'];
                $this->cuit = $row['cuit'];
                $this->correo_electronico = $row['correo_electronico'];
                $this->celular = $row['celular'];
                $this->localidad = $row['localidad'];
                $this->razon_social_empresa = $row['razon_social_empresa'];
                $this->mensaje = $row['mensaje'];
                $this->created_at = $row['created_at'];
                
                return $row;
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Error PDO al leer mensaje ID {$this->id}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Contar total de mensajes
     */
    public function countTotal() {
        try {
            $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['total'];
        } catch (PDOException $e) {
            error_log("Error PDO al contar mensajes: " . $e->getMessage());
            return 0;
        }
    }
}
?>