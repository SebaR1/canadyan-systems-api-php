<?php
/**
 * Canadian Sistemas API
 * Punto de entrada principal
 * 
 * Este archivo proporciona información general sobre la API
 * y documenta todas las rutas disponibles.
 * 
 * @version 1.0.0
 * @author Canadian Sistemas
 */

// Headers CORS y configuración
header('Content-Type: application/json; charset=utf-8');

// CORS headers para permitir credenciales desde localhost:3000
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin === 'http://localhost:3000' || $origin === 'http://127.0.0.1:3000') {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: http://localhost:3000');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Información de la API
$api_info = [
    'api' => 'Canadian Sistemas API',
    'version' => '1.0.0',
    'status' => 'active',
    'message' => 'API funcionando correctamente',
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => $_SERVER['SERVER_NAME'] ?? 'localhost',
    
    // Documentación de rutas disponibles
    'routes' => [
        'usuarios' => [
            'file' => '/api/routes/usuarios.php',
            'description' => 'Gestión de usuarios y autenticación',
            'actions' => [
                'register' => 'POST - Registrar nuevo usuario',
                'login' => 'POST - Iniciar sesión',
                'profile' => 'GET - Obtener perfil del usuario',
                'update-profile' => 'PUT - Actualizar perfil',
                'change-password' => 'POST - Cambiar contraseña',
                'logout' => 'POST - Cerrar sesión',
                'verify-email' => 'GET - Verificar email'
            ]
        ],
        'productos' => [
            'file' => '/api/routes/productos.php',
            'description' => 'Gestión del catálogo de productos',
            'actions' => [
                'list' => 'GET - Listar productos',
                'get' => 'GET - Obtener producto específico',
                'create' => 'POST - Crear nuevo producto',
                'update' => 'PUT - Actualizar producto',
                'delete' => 'DELETE - Eliminar producto',
                'search' => 'GET - Buscar productos'
            ]
        ],
        'categorias' => [
            'file' => '/api/routes/categorias.php',
            'description' => 'Gestión de categorías de productos',
            'actions' => [
                'list' => 'GET - Listar categorías',
                'get' => 'GET - Obtener categoría específica',
                'create' => 'POST - Crear nueva categoría',
                'update' => 'PUT - Actualizar categoría',
                'delete' => 'DELETE - Eliminar categoría'
            ]
        ],
        'contacto' => [
            'file' => '/api/routes/contacto.php',
            'description' => 'Gestión de formularios de contacto',
            'actions' => [
                'send' => 'POST - Enviar mensaje de contacto',
                'list' => 'GET - Listar mensajes (admin)',
                'get' => 'GET - Obtener mensaje específico (admin)',
                'mark-read' => 'PUT - Marcar como leído (admin)'
            ]
        ]
    ],
    
    // Información de uso
    'usage' => [
        'base_url' => 'https://' . ($_SERVER['SERVER_NAME'] ?? 'tudominio.com') . '/api/routes/',
        'format' => 'JSON',
        'authentication' => 'Session-based para rutas protegidas',
        'cors' => 'Habilitado para desarrollo (configurar para producción)',
        'examples' => [
            'register' => 'POST /api/routes/usuarios.php?action=register',
            'login' => 'POST /api/routes/usuarios.php?action=login',
            'products' => 'GET /api/routes/productos.php?action=list',
            'contact' => 'POST /api/routes/contacto.php?action=send'
        ]
    ],
    
    // Estado del sistema
    'system' => [
        'php_version' => PHP_VERSION,
        'server_time' => date('Y-m-d H:i:s T'),
        'timezone' => date_default_timezone_get(),
        'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB'
    ]
];

// Verificar si se solicita información específica
$info_type = $_GET['info'] ?? 'all';

switch ($info_type) {
    case 'routes':
        echo json_encode(['routes' => $api_info['routes']], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;
        
    case 'usage':
        echo json_encode(['usage' => $api_info['usage']], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;
        
    case 'system':
        echo json_encode(['system' => $api_info['system']], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;
        
    case 'health':
        // Health check simple
        $health = [
            'status' => 'healthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'uptime' => 'API funcionando correctamente'
        ];
        
        // Verificar conexión a base de datos si está disponible
        try {
            require_once __DIR__ . '/config/database.php';
            $db = new Database();
            $connection_test = $db->testConnection();
            $health['database'] = $connection_test;
        } catch (Exception $e) {
            $health['database'] = [
                'success' => false,
                'message' => 'Error al conectar con la base de datos'
            ];
        }
        
        echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;
        
    default:
        // Información completa
        echo json_encode($api_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;
}

// Log de acceso (opcional)
error_log("API Access: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI'] . " from " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

?>