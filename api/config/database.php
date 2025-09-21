<?php

/**
 * Configuración de Base de Datos
 * Canadian Sistemas API
 */

// Función simple para cargar .env
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Cargar variables del .env
loadEnv(__DIR__ . '/../.env');

class Database {
    // Configuración de conexión desde .env
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    private $charset = 'utf8mb4';
    
    private $pdo;
    
    public function __construct() {
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->db_name = $_ENV['DB_NAME'] ?? 'canadyan_sistemas';
        $this->username = $_ENV['DB_USERNAME'] ?? 'root';
        $this->password = $_ENV['DB_PASSWORD'] ?? '';
        $this->port = $_ENV['DB_PORT'] ?? 3306;
    }
    
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
            $error_details = [
                'error_code' => $exception->getCode(),
                'error_message' => $exception->getMessage(),
                'connection_string' => "mysql:host={$this->host};port={$this->port};dbname={$this->db_name}"
            ];
            error_log("Error de conexión: " . $exception->getMessage());
            throw new Exception("Error de conexión a la base de datos: " . $exception->getMessage() . " (Code: " . $exception->getCode() . ")");
        }
        
        return $this->pdo;
    }
    
    /**
     * Probar conexión
     */
    public function testConnection() {
        try {
            // Debug: mostrar configuración cargada
            $debug_info = [
                'env_loaded' => [
                    'DB_HOST' => $_ENV['DB_HOST'] ?? 'NOT_SET',
                    'DB_NAME' => $_ENV['DB_NAME'] ?? 'NOT_SET', 
                    'DB_USERNAME' => $_ENV['DB_USERNAME'] ?? 'NOT_SET',
                    'DB_PASSWORD' => !empty($_ENV['DB_PASSWORD']) ? '***SET***' : 'NOT_SET',
                    'DB_PORT' => $_ENV['DB_PORT'] ?? 'NOT_SET'
                ],
                'config_used' => [
                    'host' => $this->host,
                    'db_name' => $this->db_name,
                    'username' => $this->username,
                    'password' => !empty($this->password) ? '***SET***' : 'EMPTY',
                    'port' => $this->port
                ]
            ];
            
            $pdo = $this->getConnection();
            return [
                'success' => true,
                'message' => 'Conexión exitosa a la base de datos',
                'host' => $this->host,
                'database' => $this->db_name,
                'debug' => $debug_info
            ];
        } catch(Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'debug' => $debug_info ?? [
                    'env_loaded' => [
                        'DB_HOST' => $_ENV['DB_HOST'] ?? 'NOT_SET',
                        'DB_NAME' => $_ENV['DB_NAME'] ?? 'NOT_SET', 
                        'DB_USERNAME' => $_ENV['DB_USERNAME'] ?? 'NOT_SET',
                        'DB_PASSWORD' => !empty($_ENV['DB_PASSWORD']) ? '***SET***' : 'NOT_SET',
                        'DB_PORT' => $_ENV['DB_PORT'] ?? 'NOT_SET'
                    ],
                    'config_used' => [
                        'host' => $this->host ?? 'NOT_SET',
                        'db_name' => $this->db_name ?? 'NOT_SET',
                        'username' => $this->username ?? 'NOT_SET',
                        'password' => !empty($this->password) ? '***SET***' : 'EMPTY',
                        'port' => $this->port ?? 'NOT_SET'
                    ]
                ]
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