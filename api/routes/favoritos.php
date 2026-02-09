<?php
/**
 * Rutas para Favoritos
 * Canadian Sistemas API
 */

// === CONFIGURACIÓN DE CORS ===
header('Content-Type: application/json; charset=utf-8');

$allowedOrigins = [
    'http://localhost:3000',
    'http://127.0.0.1:3000',
    'https://canadian.com.ar',
    'https://www.canadian.com.ar',
    'https://canadian.com.ar/canadian-sistemas',
    'https://www.canadian.com.ar/canadian-sistemas',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
} else {
    header("Access-Control-Allow-Origin: *");
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");

// Manejo de preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Max-Age: 86400');
    http_response_code(204);
    exit;
}

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/../controllers/FavoritosController.php';
require_once __DIR__ . '/../utils/Response.php';

try {
    $action = $_GET['action'] ?? '';

    if (empty($action)) {
        Response::error('Acción no especificada', 400, [
            'available_actions' => [
                'add'      => 'POST - Agregar producto a favoritos',
                'remove'   => 'DELETE - Eliminar producto de favoritos',
                'list'     => 'GET - Listar favoritos completos',
                'list-ids' => 'GET - Listar solo IDs de favoritos',
                'check'    => 'GET - Verificar si un producto es favorito'
            ]
        ]);
        exit();
    }

    $controller = new FavoritosController();

    switch ($action) {

        case 'add':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Método no permitido. Use POST.', 405);
                break;
            }
            $controller->add();
            break;

        case 'remove':
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                Response::error('Método no permitido. Use DELETE.', 405);
                break;
            }
            $controller->remove();
            break;

        case 'list':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            $controller->list();
            break;

        case 'list-ids':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            $controller->listIds();
            break;

        case 'check':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            $controller->check();
            break;

        default:
            Response::error('Acción no válida', 400, [
                'available_actions' => [
                    'add'      => 'POST /api/routes/favoritos.php?action=add',
                    'remove'   => 'DELETE /api/routes/favoritos.php?action=remove&producto_id=X',
                    'list'     => 'GET /api/routes/favoritos.php?action=list',
                    'list-ids' => 'GET /api/routes/favoritos.php?action=list-ids',
                    'check'    => 'GET /api/routes/favoritos.php?action=check&producto_id=X'
                ]
            ]);
            break;
    }

} catch (Exception $e) {
    error_log("Error en favoritos.php: " . $e->getMessage());
    Response::error('Error interno del servidor', 500);
}
