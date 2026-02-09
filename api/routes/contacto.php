<?php
/**
 * Rutas API - Contacto
 * Canadian Sistemas API
 */

// === CONFIGURACIÓN DE CORS ===
header('Content-Type: application/json; charset=utf-8');

// Lista de orígenes permitidos
$allowedOrigins = [
    'http://localhost:3000',
    'http://127.0.0.1:3000',
    'https://canadian.com.ar',
    'https://www.canadian.com.ar',
    'https://canadian.com.ar/canadian-sistemas',
    'https://www.canadian.com.ar/canadian-sistemas',
];

// Detectar origen
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
} else {
    header("Access-Control-Allow-Origin: *");
}

// Permitir métodos y cabeceras necesarias
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");

// Manejo de preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Max-Age: 86400');
    http_response_code(204);
    exit;
}

// Incluir dependencias
require_once __DIR__ . '/../controllers/ContactoController.php';
require_once __DIR__ . '/../utils/Response.php';

try {
    // Crear instancia del controlador
    $controller = new ContactoController();
    
    // Obtener acción del query string
    $action = $_GET['action'] ?? '';
    
    // Enrutamiento basado en la acción
    switch ($action) {
        
        /**
         * Enviar mensaje de contacto
         * POST /api/routes/contacto.php?action=send
         * 
         * Body JSON:
         * {
         *   "nombreApellido": "Juan Pérez",
         *   "cuit": "20-12345678-9",
         *   "correoElectronico": "juan@example.com",
         *   "celular": "11-1234-5678",
         *   "localidad": "Buenos Aires",
         *   "razonSocialEmpresa": "Empresa SRL",
         *   "mensaje": "Consulta sobre productos..."
         * }
         */
        case 'send':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Método no permitido. Use POST.', 405);
                break;
            }
            $controller->enviar();
            break;
            
        /**
         * Listar mensajes de contacto (Solo Admin)
         * GET /api/routes/contacto.php?action=list&page=1&limit=10
         */
        case 'list':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            $controller->listar();
            break;
            
        /**
         * Obtener mensaje por ID (Solo Admin)
         * GET /api/routes/contacto.php?action=get&id=1
         */
        case 'get':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            $controller->obtener();
            break;
            
        /**
         * Endpoint de prueba
         * GET /api/routes/contacto.php?action=test
         */
        case 'test':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            
            Response::success('API de contacto funcionando correctamente', 200, [
                'version' => '1.0.0',
                'server_time' => date('Y-m-d H:i:s'),
                'endpoints' => [
                    'send' => 'POST ?action=send',
                    'list' => 'GET ?action=list (admin)',
                    'get' => 'GET ?action=get&id=X (admin)'
                ],
                'examples' => [
                    'send' => [
                        'method' => 'POST',
                        'url' => '/api/routes/contacto.php?action=send',
                        'body' => [
                            'nombreApellido' => 'Juan Pérez',
                            'cuit' => '20-12345678-9',
                            'correoElectronico' => 'juan@example.com',
                            'celular' => '11-1234-5678',
                            'localidad' => 'Buenos Aires',
                            'razonSocialEmpresa' => 'Empresa SRL',
                            'mensaje' => 'Consulta sobre productos...'
                        ]
                    ]
                ]
            ]);
            break;
            
        /**
         * Acción por defecto - mostrar ayuda
         */
        default:
            Response::error('Acción no válida', 400, [
                'available_actions' => [
                    'send' => 'POST - Enviar mensaje de contacto',
                    'list' => 'GET - Listar mensajes (admin)',
                    'get' => 'GET - Obtener mensaje por ID (admin)',
                    'test' => 'GET - Probar API'
                ],
                'usage' => 'Agregue ?action=nombre_accion a la URL',
                'example' => '/api/routes/contacto.php?action=send'
            ]);
            break;
    }
    
} catch (Exception $e) {
    // Log del error
    error_log("Error en contacto.php: " . $e->getMessage());
    
    // Respuesta de error
    Response::error('Error interno del servidor', 500, [
        'error_id' => uniqid('CONTACTO_ERROR_'),
        'timestamp' => date('Y-m-d H:i:s'),
        'suggestion' => 'Revise los logs del servidor para más detalles'
    ]);
}

// Log de acceso (opcional)
$logData = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'] ?? '',
    'action' => $_GET['action'] ?? 'none',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'timestamp' => date('Y-m-d H:i:s')
];

error_log("Contacto API Access: " . json_encode($logData));
?>