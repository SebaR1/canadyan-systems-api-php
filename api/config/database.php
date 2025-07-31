<?php

/**
 * Configuración de Base de Datos
 * Canadian Sistemas API
 */

class Database {
    // Configuración de conexión
    private $host = 'mysql.ferozo.com';
    private $db_name = 'tu_base_datos';
    private $username = 'tu_usuario';
    private $password = 'tu_contraseña';
    private $port = 3306;
    private $charset = 'utf8mb4';
    
    private $pdo;
    
    /**
     * Obtener conexión PDO
     */
    public function getConnection() {
        $this->pdo = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $exception) {
            error_log("Error de conexión: " . $exception->getMessage());
            throw new Exception("Error de conexión a la base de datos");
        }
        
        return $this->pdo;
    }
    
    /**
     * Probar conexión
     */
    public function testConnection() {
        try {
            $pdo = $this->getConnection();
            return [
                'success' => true,
                'message' => 'Conexión exitosa a la base de datos'
            ];
        } catch(Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}

/**
 * Función helper para obtener conexión rápidamente
 */
function getDatabase() {
    $database = new Database();
    return $database->getConnection();
}
?>