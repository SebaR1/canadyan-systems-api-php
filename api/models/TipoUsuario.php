<?php
/**
 * Modelo TipoUsuario
 * Canadian Sistemas API
 */

require_once __DIR__ . '/../config/database.php';

class TipoUsuario {
    private $conn;
    private $table_name = "tipos_usuario";

    // Propiedades
    public $id;
    public $nombre;
    public $descripcion;

    /**
     * Constructor
     */
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Obtener todos los tipos de usuario
     */
    public function readAll() {
        try {
            $query = "SELECT id, nombre, descripcion
                      FROM " . $this->table_name . "
                      ORDER BY id ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error PDO al obtener tipos de usuario: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener un tipo de usuario por ID
     */
    public function readOne() {
        try {
            $query = "SELECT id, nombre, descripcion
                      FROM " . $this->table_name . "
                      WHERE id = :id
                      LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error PDO al obtener tipo de usuario: " . $e->getMessage());
            return null;
        }
    }
}
?>
