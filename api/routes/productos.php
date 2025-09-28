<?php
/**
 * Rutas API - Productos
 * Canadian Sistemas API
 */

// Headers CORS y configuración
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost:3000'); // Cambiar por tu dominio en producción
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');


// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    exit();
}

// Incluir dependencias
require_once __DIR__ . '/../controllers/ProductoController.php';
require_once __DIR__ . '/../utils/Response.php';

try {
    // Crear instancia del controlador
    $controller = new ProductoController();
    
    // Obtener acción del query string
    $action = $_GET['action'] ?? '';
    
    // Enrutamiento basado en la acción
    switch ($action) {
        
        /**
         * Crear nuevo producto
         * POST /api/routes/productos.php?action=create
         * 
         * Body JSON:
         * {
         *   "nombre": "Router WiFi 6",
         *   "descripcion": "Router de alta velocidad",
         *   "precio": 25000.50,
         *   "stock": 10,
         *   "categoria_id": 1,
         *   "sku": "RTR-WIFI6-001",
         *   "activo": true
         * }
         */
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Método no permitido. Use POST.', 405);
                break;
            }
            $controller->create();
            break;
            
        /**
         * Listar productos con paginación
         * GET /api/routes/productos.php?action=list&page=1&limit=10&active_only=1
         */
        case 'list':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            $controller->list();
            break;
            
        /**
         * Obtener producto por ID
         * GET /api/routes/productos.php?action=get&id=1
         */
        case 'get':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            $controller->get();
            break;
            
        /**
         * Actualizar producto
         * PUT /api/routes/productos.php?action=update&id=1
         * 
         * Body JSON (todos los campos son opcionales):
         * {
         *   "nombre": "Router WiFi 6 Pro",
         *   "descripcion": "Router profesional",
         *   "precio": 35000,
         *   "stock": 5,
         *   "categoria_id": 2,
         *   "sku": "RTR-WIFI6-PRO",
         *   "activo": false
         * }
         */
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                Response::error('Método no permitido. Use PUT.', 405);
                break;
            }
            $controller->update();
            break;
            
        /**
         * Eliminar producto (soft delete)
         * DELETE /api/routes/productos.php?action=delete&id=1
         */
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                Response::error('Método no permitido. Use DELETE.', 405);
                break;
            }
            $controller->delete();
            break;
            
        /**
         * Buscar productos
         * GET /api/routes/productos.php?action=search&q=router&page=1&limit=10
         */
        case 'search':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            $controller->search();
            break;
            
        /**
         * Obtener productos por categoría
         * GET /api/routes/productos.php?action=by-category&categoria_id=1&page=1&limit=10
         */
        case 'by-category':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            $controller->byCategory();
            break;
            
        /**
         * Actualizar solo stock
         * PATCH /api/routes/productos.php?action=update-stock&id=1
         * 
         * Body JSON:
         * {
         *   "stock": 25
         * }
         */
        case 'update-stock':
            if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
                Response::error('Método no permitido. Use PATCH.', 405);
                break;
            }
            $controller->updateStock();
            break;
            
        /**
         * Cambiar estado activo/inactivo
         * PATCH /api/routes/productos.php?action=toggle&id=1
         */
        case 'toggle':
            if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
                Response::error('Método no permitido. Use PATCH.', 405);
                break;
            }
            $controller->toggle();
            break;
            
        /**
         * Obtener estadísticas (Solo Admin)
         * GET /api/routes/productos.php?action=stats
         */
        case 'stats':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            $controller->stats();
            break;
            
        /**
         * Endpoint de prueba
         * GET /api/routes/productos.php?action=test
         */
        case 'test':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            
            Response::success('API de productos funcionando correctamente', 200, [
                'version' => '1.0.0',
                'server_time' => date('Y-m-d H:i:s'),
                'endpoints' => [
                    'create' => 'POST ?action=create',
                    'list' => 'GET ?action=list&page=1&limit=10',
                    'get' => 'GET ?action=get&id=1',
                    'update' => 'PUT ?action=update&id=1',
                    'delete' => 'DELETE ?action=delete&id=1',
                    'search' => 'GET ?action=search&q=termino',
                    'by-category' => 'GET ?action=by-category&categoria_id=1',
                    'update-stock' => 'PATCH ?action=update-stock&id=1',
                    'toggle' => 'PATCH ?action=toggle&id=1',
                    'stats' => 'GET ?action=stats'
                ],
                'examples' => [
                    'create' => [
                        'method' => 'POST',
                        'url' => '/api/routes/productos.php?action=create',
                        'body' => [
                            'nombre' => 'Router WiFi 6',
                            'descripcion' => 'Router de alta velocidad',
                            'precio' => 25000.50,
                            'stock' => 10,
                            'categoria_id' => 1,
                            'sku' => 'RTR-WIFI6-001',
                            'activo' => true
                        ]
                    ],
                    'list' => [
                        'method' => 'GET',
                        'url' => '/api/routes/productos.php?action=list&page=1&limit=10'
                    ],
                    'search' => [
                        'method' => 'GET',
                        'url' => '/api/routes/productos.php?action=search&q=router&page=1&limit=5'
                    ],
                    'update-stock' => [
                        'method' => 'PATCH',
                        'url' => '/api/routes/productos.php?action=update-stock&id=1',
                        'body' => [
                            'stock' => 25
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
                    'create' => 'POST - Crear nuevo producto',
                    'list' => 'GET - Listar productos (con paginación)',
                    'get' => 'GET - Obtener producto por ID',
                    'update' => 'PUT - Actualizar producto',
                    'delete' => 'DELETE - Eliminar producto (soft delete)',
                    'search' => 'GET - Buscar productos',
                    'by-category' => 'GET - Productos por categoría',
                    'update-stock' => 'PATCH - Actualizar solo stock',
                    'toggle' => 'PATCH - Cambiar estado activo/inactivo',
                    'stats' => 'GET - Estadísticas (admin)',
                    'test' => 'GET - Probar API'
                ],
                'usage' => 'Agregue ?action=nombre_accion a la URL',
                'pagination' => 'Use page=N&limit=M para paginación',
                'examples' => [
                    'list' => '/api/routes/productos.php?action=list&page=1&limit=5',
                    'search' => '/api/routes/productos.php?action=search&q=router',
                    'by-category' => '/api/routes/productos.php?action=by-category&categoria_id=1'
                ]
            ]);
            break;
    }
    
} catch (Exception $e) {
    // Log del error
    error_log("Error en productos.php: " . $e->getMessage());
    
    // Respuesta de error
    Response::error('Error interno del servidor', 500, [
        'error_id' => uniqid('PROD_ERROR_'),
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

error_log("Productos API Access: " . json_encode($logData));
?>