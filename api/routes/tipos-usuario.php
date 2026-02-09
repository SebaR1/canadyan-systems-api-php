<?php
/**
 * Rutas para Tipos de Usuario
 * Canadian Sistemas API
 *
 * Endpoints disponibles:
 * GET /api/routes/tipos-usuario.php?action=list
 */

// === CONFIGURACIÓN DE CORS ===
header('Content-Type: application/json; charset=utf-8');

// Lista blanca de orígenes permitidos
$allowedOrigins = [
    'http://localhost:3000',
    'http://127.0.0.1:3000',
    'https://canadian.com.ar',
    'https://www.canadian.com.ar',
    'https://canadian.com.ar/canadian-sistemas',
    'https://www.canadian.com.ar/canadian-sistemas',
];

// Detectar origen de la solicitud
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Si el origen está permitido, habilitar CORS
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
} else {
    header("Access-Control-Allow-Origin: null");
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir dependencias
require_once __DIR__ . '/../controllers/TipoUsuarioController.php';
require_once __DIR__ . '/../utils/Response.php';

try {
    // Crear instancia del controlador
    $controller = new TipoUsuarioController();

    // Obtener acción del query string
    $action = $_GET['action'] ?? '';

    // Enrutamiento basado en la acción
    switch ($action) {

        /**
         * Listar todos los tipos de usuario
         * GET /api/routes/tipos-usuario.php?action=list
         */
        case 'list':
            $controller->listAll();
            break;

        /**
         * Acción no encontrada
         */
        default:
            Response::error('Acción no válida', 400, [
                'available_actions' => ['list'],
                'usage' => 'Agregue ?action=nombre_accion a la URL'
            ]);
            break;
    }

} catch (Exception $e) {
    // Log del error
    error_log("Error en tipos-usuario.php: " . $e->getMessage());

    // Respuesta de error genérica
    Response::serverError('Error interno del servidor');
}
?>
