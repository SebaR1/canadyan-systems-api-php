<?php
/**
 * Rutas API - Atributos
 * Canadian Sistemas API
 */

// Headers CORS y configuración
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

header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Max-Age: 86400');
    http_response_code(204);
    exit;
}

// Incluir dependencias
require_once __DIR__ . '/../controllers/AtributoController.php';
require_once __DIR__ . '/../utils/Response.php';

try {
    // Crear instancia del controlador
    $controller = new AtributoController();
    
    // Obtener acción del query string
    $action = $_GET['action'] ?? '';
    
    // Enrutamiento basado en la acción
    switch ($action) {
        
        /**
         * Crear nuevo atributo
         * POST /api/routes/atributos.php?action=create
         * 
         * Body JSON:
         * {
         *   "nombre": "Marca",
         *   "tipo": "select"
         * }
         */
        case 'create':
            $controller->create();
            break;
            
        /**
         * Listar todos los atributos
         * GET /api/routes/atributos.php?action=list
         */
        case 'list':
            $controller->list();
            break;
            
        /**
         * Obtener atributo por ID
         * GET /api/routes/atributos.php?action=get&id=1
         */
        case 'get':
            $controller->get();
            break;
            
        /**
         * Actualizar atributo
         * PUT /api/routes/atributos.php?action=update&id=1
         * 
         * Body JSON:
         * {
         *   "nombre": "Marca Actualizada",
         *   "tipo": "text"
         * }
         */
        case 'update':
            $controller->update();
            break;
            
        /**
         * Eliminar atributo
         * DELETE /api/routes/atributos.php?action=delete&id=1
         */
        case 'delete':
            $controller->delete();
            break;
            
        /**
         * Obtener atributos con valores para filtros del catálogo
         * GET /api/routes/atributos.php?action=filters
         */
        case 'filters':
            $controller->getFilters();
            break;
            
        /**
         * Asignar atributos a un producto
         * POST /api/routes/atributos.php?action=assign-product
         * 
         * Body JSON:
         * {
         *   "producto_id": 1,
         *   "atributos": [
         *     {"atributo_id": 1, "valor": "Kingwell"},
         *     {"atributo_id": 2, "valor": "10km"}
         *   ]
         * }
         */
        case 'assign-product':
            $controller->assignToProduct();
            break;
            
        /**
         * Obtener atributos de un producto
         * GET /api/routes/atributos.php?action=by-product&producto_id=1
         */
        case 'by-product':
            $controller->getByProduct();
            break;
            
        /**
         * Filtrar productos por atributos
         * POST /api/routes/atributos.php?action=filter-products
         * 
         * Body JSON:
         * {
         *   "filtros": {
         *     "1": ["Kingwell", "Hikvision"],
         *     "2": ["10km", "20km"]
         *   },
         *   "page": 1,
         *   "limit": 10
         * }
         */
        case 'filter-products':
            $controller->filterProducts();
            break;

        /**
         * Obtener filtros dinámicos basados en filtros aplicados
         * POST /api/routes/atributos.php?action=dynamic-filters
         */
        case 'dynamic-filters':
            $controller->getDynamicFilters();
            break;
            
        /**
         * Endpoint de prueba
         * GET /api/routes/atributos.php?action=test
         */
        case 'test':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            
            Response::success('API de atributos funcionando correctamente', 200, [
                'version' => '1.0.0',
                'server_time' => date('Y-m-d H:i:s'),
                'endpoints' => [
                    'create' => 'POST ?action=create',
                    'list' => 'GET ?action=list',
                    'get' => 'GET ?action=get&id=1',
                    'update' => 'PUT ?action=update&id=1',
                    'delete' => 'DELETE ?action=delete&id=1',
                    'filters' => 'GET ?action=filters',
                    'assign-product' => 'POST ?action=assign-product',
                    'by-product' => 'GET ?action=by-product&producto_id=1',
                    'filter-products' => 'POST ?action=filter-products'
                ],
                'examples' => [
                    'create' => [
                        'method' => 'POST',
                        'url' => '/api/routes/atributos.php?action=create',
                        'body' => [
                            'nombre' => 'Marca',
                            'tipo' => 'select'
                        ]
                    ],
                    'filters' => [
                        'method' => 'GET',
                        'url' => '/api/routes/atributos.php?action=filters',
                        'description' => 'Obtiene todos los atributos con sus valores únicos para usar como filtros'
                    ],
                    'assign-product' => [
                        'method' => 'POST',
                        'url' => '/api/routes/atributos.php?action=assign-product',
                        'body' => [
                            'producto_id' => 1,
                            'atributos' => [
                                ['atributo_id' => 1, 'valor' => 'Kingwell'],
                                ['atributo_id' => 2, 'valor' => '10km']
                            ]
                        ]
                    ],
                    'filter-products' => [
                        'method' => 'POST',
                        'url' => '/api/routes/atributos.php?action=filter-products',
                        'body' => [
                            'filtros' => [
                                '1' => ['Kingwell', 'Hikvision'],
                                '2' => ['10km', '20km']
                            ],
                            'page' => 1,
                            'limit' => 10
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
                    'create' => 'POST - Crear nuevo atributo',
                    'list' => 'GET - Listar todos los atributos',
                    'get' => 'GET - Obtener atributo por ID',
                    'update' => 'PUT - Actualizar atributo',
                    'delete' => 'DELETE - Eliminar atributo',
                    'filters' => 'GET - Obtener filtros para catálogo',
                    'assign-product' => 'POST - Asignar atributos a producto',
                    'by-product' => 'GET - Obtener atributos de un producto',
                    'filter-products' => 'POST - Filtrar productos por atributos',
                    'test' => 'GET - Probar API'
                ],
                'usage' => 'Agregue ?action=nombre_accion a la URL',
                'key_endpoints' => [
                    'filters' => 'Para obtener filtros dinámicos del catálogo',
                    'filter-products' => 'Para filtrar productos según atributos seleccionados',
                    'assign-product' => 'Para asignar/actualizar atributos de un producto'
                ]
            ]);
            break;
    }
    
} catch (Exception $e) {
    // Log del error
    error_log("Error en atributos.php: " . $e->getMessage());
    
    // Respuesta de error
    Response::error('Error interno del servidor', 500, [
        'error_id' => uniqid('ATTR_ERROR_'),
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
    'timestamp' => date('Y-m-d H:i:s')
];

error_log("Atributos API Access: " . json_encode($logData));
