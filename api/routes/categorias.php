<?php
/**
 * Rutas API - Categorías
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
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");

// Manejo de preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Max-Age: 86400');
    http_response_code(204);
    exit;
}

// Incluir dependencias
require_once __DIR__ . '/../controllers/CategoriaController.php';
require_once __DIR__ . '/../utils/Response.php';

try {
    $controller = new CategoriaController();
    $action = $_GET['action'] ?? '';

    switch ($action) {

        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Método no permitido. Use POST.', 405);
                break;
            }
            $controller->create();
            break;

        case 'list':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            $controller->list();
            break;

        case 'tree':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            $controller->tree();
            break;

        case 'get':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            $controller->get();
            break;

        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                Response::error('Método no permitido. Use PUT.', 405);
                break;
            }
            $controller->update();
            break;

        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                Response::error('Método no permitido. Use DELETE.', 405);
                break;
            }
            $controller->delete();
            break;

        case 'children':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            $controller->children();
            break;

        case 'stats':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            $controller->stats();
            break;

        case 'test':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }

            Response::success('API de categorías funcionando correctamente', 200, [
                'version' => '1.0.0',
                'server_time' => date('Y-m-d H:i:s'),
                'endpoints' => [
                    'create' => 'POST ?action=create',
                    'list' => 'GET ?action=list',
                    'tree' => 'GET ?action=tree',
                    'get' => 'GET ?action=get&id=1',
                    'update' => 'PUT ?action=update&id=1',
                    'delete' => 'DELETE ?action=delete&id=1',
                    'children' => 'GET ?action=children&id=1',
                    'stats' => 'GET ?action=stats'
                ]
            ]);
            break;

        default:
            Response::error('Acción no válida', 400, [
                'available_actions' => [
                    'create' => 'POST - Crear nueva categoría',
                    'list' => 'GET - Listar todas las categorías',
                    'tree' => 'GET - Obtener árbol jerárquico',
                    'get' => 'GET - Obtener categoría por ID',
                    'update' => 'PUT - Actualizar categoría',
                    'delete' => 'DELETE - Eliminar categoría',
                    'children' => 'GET - Obtener subcategorías',
                    'stats' => 'GET - Estadísticas (admin)',
                    'test' => 'GET - Probar API'
                ],
                'usage' => 'Agregue ?action=nombre_accion a la URL',
                'example' => '/api/routes/categorias.php?action=list'
            ]);
            break;
    }

} catch (Exception $e) {
    error_log("Error en categorias.php: " . $e->getMessage());
    Response::error('Error interno del servidor', 500, [
        'error_id' => uniqid('CAT_ERROR_'),
        'timestamp' => date('Y-m-d H:i:s'),
        'suggestion' => 'Revise los logs del servidor para más detalles'
    ]);
}

// Log de acceso
$logData = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'] ?? '',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'timestamp' => date('Y-m-d H:i:s')
];
error_log("Categorias API Access: " . json_encode($logData));
