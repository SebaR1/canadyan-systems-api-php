<?php
/**
 * Rutas para ProductoArchivo
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

require_once __DIR__ . '/../controllers/ProductoArchivoController.php';
require_once __DIR__ . '/../utils/Response.php';

try {
    // Obtener acción del query string
    $action = $_GET['action'] ?? '';

    if (empty($action)) {
        Response::error('Acción no especificada', 400, [
            'available_actions' => [
                'upload' => 'POST - Subir archivo(s)',
                'list' => 'GET - Listar archivos de un producto',
                'download' => 'GET - Descargar archivo',
                'update' => 'PATCH - Actualizar nombre personalizado',
                'delete' => 'DELETE - Eliminar archivo',
                'reorder' => 'PATCH - Reordenar archivos'
            ],
            'usage' => 'Agregue ?action=nombre_accion a la URL',
            'example' => '/api/routes/producto_archivos.php?action=upload'
        ]);
        exit();
    }

    // Crear instancia del controlador
    $controller = new ProductoArchivoController();

    // Rutear según la acción
    switch ($action) {

        case 'upload':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Método no permitido. Use POST.', 405);
                break;
            }
            $controller->upload();
            break;

        case 'list':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            $controller->list();
            break;

        case 'download':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            $controller->download();
            break;

        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
                Response::error('Método no permitido. Use PATCH.', 405);
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

        case 'reorder':
            if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
                Response::error('Método no permitido. Use PATCH.', 405);
                break;
            }
            $controller->reorder();
            break;

        default:
            Response::error('Acción no válida', 400, [
                'available_actions' => [
                    'upload' => 'POST - Subir archivo(s) de producto',
                    'list' => 'GET - Listar archivos de un producto (producto_id)',
                    'download' => 'GET - Descargar archivo por ID',
                    'update' => 'PATCH - Actualizar nombre personalizado',
                    'delete' => 'DELETE - Eliminar archivo por ID',
                    'reorder' => 'PATCH - Reordenar archivos'
                ],
                'usage' => 'Agregue ?action=nombre_accion a la URL',
                'examples' => [
                    'upload' => 'POST /api/routes/producto_archivos.php?action=upload',
                    'list' => 'GET /api/routes/producto_archivos.php?action=list&producto_id=1',
                    'download' => 'GET /api/routes/producto_archivos.php?action=download&id=1',
                    'update' => 'PATCH /api/routes/producto_archivos.php?action=update',
                    'delete' => 'DELETE /api/routes/producto_archivos.php?action=delete&id=1',
                    'reorder' => 'PATCH /api/routes/producto_archivos.php?action=reorder'
                ]
            ]);
            break;
    }

} catch (Exception $e) {
    error_log("Error en producto_archivos.php: " . $e->getMessage());
    Response::error('Error interno del servidor', 500, [
        'message' => $e->getMessage()
    ]);
}
