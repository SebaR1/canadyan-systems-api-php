<?php
/**
 * Rutas API - Categorías
 * Canadian Sistemas API
 */

// Headers CORS y configuración
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost:3000'); // Cambiar por tu dominio en producción
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');


// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    exit();
}

// Incluir dependencias
require_once __DIR__ . '/../controllers/CategoriaController.php';
require_once __DIR__ . '/../utils/Response.php';

try {
    // Crear instancia del controlador
    $controller = new CategoriaController();
    
    // Obtener acción del query string
    $action = $_GET['action'] ?? '';
    
    // Enrutamiento basado en la acción
    switch ($action) {
        
        /**
         * Crear nueva categoría
         * POST /api/routes/categorias.php?action=create
         * 
         * Body JSON:
         * {
         *   "nombre": "Conectividad",
         *   "parent_id": null
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
         * Listar todas las categorías (lista plana)
         * GET /api/routes/categorias.php?action=list
         */
        case 'list':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            $controller->list();
            break;
            
        /**
         * Obtener árbol de categorías
         * GET /api/routes/categorias.php?action=tree
         */
        case 'tree':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            $controller->tree();
            break;
            
        /**
         * Obtener categoría por ID
         * GET /api/routes/categorias.php?action=get&id=1
         */
        case 'get':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            $controller->get();
            break;
            
        /**
         * Actualizar categoría
         * PUT /api/routes/categorias.php?action=update&id=1
         * 
         * Body JSON:
         * {
         *   "nombre": "Conectividad Actualizada",
         *   "parent_id": 2
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
         * Eliminar categoría
         * DELETE /api/routes/categorias.php?action=delete&id=1
         */
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                Response::error('Método no permitido. Use DELETE.', 405);
                break;
            }
            $controller->delete();
            break;
            
        /**
         * Obtener subcategorías
         * GET /api/routes/categorias.php?action=children&id=1
         */
        case 'children':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            $controller->children();
            break;
            
        /**
         * Obtener estadísticas (Solo Admin)
         * GET /api/routes/categorias.php?action=stats
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
         * GET /api/routes/categorias.php?action=test
         */
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
                ],
                'examples' => [
                    'create' => [
                        'method' => 'POST',
                        'url' => '/api/routes/categorias.php?action=create',
                        'body' => [
                            'nombre' => 'Conectividad',
                            'parent_id' => null
                        ]
                    ],
                    'list' => [
                        'method' => 'GET',
                        'url' => '/api/routes/categorias.php?action=list'
                    ],
                    'tree' => [
                        'method' => 'GET',
                        'url' => '/api/routes/categorias.php?action=tree'
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
    // Log del error
    error_log("Error en categorias.php: " . $e->getMessage());
    
    // Respuesta de error
    Response::error('Error interno del servidor', 500, [
        'error_id' => uniqid('CAT_ERROR_'),
        'timestamp' => date('Y-m-d H:i:s'),
        'suggestion' => 'Revise los logs del servidor para más detalles'
    ]);
}

// Log de acceso (opcional)
$logData = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'] ?? '',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'timestamp' => date('Y-m-d H:i:s')
];

error_log("Categorias API Access: " . json_encode($logData));
?>