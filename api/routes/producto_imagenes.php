<?php
/**
 * Rutas para ProductoImagen
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

require_once __DIR__ . '/../controllers/ProductoImagenController.php';
require_once __DIR__ . '/../utils/Response.php';

try {
    // Obtener acción del query string
    $action = $_GET['action'] ?? '';
    
    if (empty($action)) {
        Response::error('Acción no especificada', 400, [
            'available_actions' => [
                'upload' => 'POST - Subir imagen(es)',
                'list' => 'GET - Listar imágenes de un producto',
                'delete' => 'DELETE - Eliminar imagen',
                'set-principal' => 'PATCH - Cambiar imagen principal',
                'reorder' => 'PATCH - Reordenar imágenes'
            ],
            'usage' => 'Agregue ?action=nombre_accion a la URL',
            'example' => '/api/routes/producto_imagenes.php?action=upload'
        ]);
        exit();
    }
    
    // Crear instancia del controlador
    $controller = new ProductoImagenController();
    
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
        
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                Response::error('Método no permitido. Use DELETE.', 405);
                break;
            }
            $controller->delete();
            break;
        
        case 'set-principal':
            if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
                Response::error('Método no permitido. Use PATCH.', 405);
                break;
            }
            $controller->setPrincipal();
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
                    'upload' => 'POST - Subir imagen(es) de producto',
                    'list' => 'GET - Listar imágenes de un producto (producto_id)',
                    'delete' => 'DELETE - Eliminar imagen por ID',
                    'set-principal' => 'PATCH - Cambiar imagen principal (id)',
                    'reorder' => 'PATCH - Reordenar imágenes'
                ],
                'usage' => 'Agregue ?action=nombre_accion a la URL',
                'examples' => [
                    'upload' => 'POST /api/routes/producto_imagenes.php?action=upload',
                    'list' => 'GET /api/routes/producto_imagenes.php?action=list&producto_id=1',
                    'delete' => 'DELETE /api/routes/producto_imagenes.php?action=delete&id=1',
                    'set-principal' => 'PATCH /api/routes/producto_imagenes.php?action=set-principal&id=1',
                    'reorder' => 'PATCH /api/routes/producto_imagenes.php?action=reorder'
                ]
            ]);
            break;
    }
    
} catch (Exception $e) {
    error_log("Error en producto_imagenes.php: " . $e->getMessage());
    Response::error('Error interno del servidor', 500, [
        'message' => $e->getMessage()
    ]);
}