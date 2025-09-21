<?php
/**
 * Rutas para Usuarios
 * Canadian Sistemas API
 * 
 * Endpoints disponibles:
 * POST /api/routes/usuarios.php?action=register
 * POST /api/routes/usuarios.php?action=login
 * GET  /api/routes/usuarios.php?action=profile
 * PUT  /api/routes/usuarios.php?action=update-profile
 * POST /api/routes/usuarios.php?action=change-password
 * POST /api/routes/usuarios.php?action=logout
 * GET  /api/routes/usuarios.php?action=verify-email&token=xyz
 */

// Headers CORS y configuración inicial
header('Content-Type: application/json; charset=utf-8');

// CORS headers para permitir credenciales desde localhost:3000
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin === 'http://localhost:3000' || $origin === 'http://127.0.0.1:3000') {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: http://localhost:3000');
}       

// ESTAS LÍNEAS FALTABAN:
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true'); // ← LA MÁS IMPORTANTE

// CONFIGURACIÓN DE SESIONES:
if (session_status() === PHP_SESSION_NONE) {
    // Configurar cookies de sesión para desarrollo con CORS

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,      // En desarrollo HTTP
        'httponly' => true,
        'samesite' => 'Lax' // Cambiar a 'None' en producción con HTTP
    ]);


ini_set('session.cookie_samesite', 'Lax');   // ✅
    ini_set('session.cookie_secure', '0'); // En producción cambiar a '1' (requiere HTTPS)
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_path', '/'); // AGREGAR ESTA LÍNEA
    
    session_start();
}

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ENDPOINT DE DEBUG (opcional, para verificar sesión)
if (isset($_GET['action']) && $_GET['action'] === 'debug-session') {
    $debug_info = [
        'session_status' => session_status(),
        'session_id' => session_id(),
        'session_data' => $_SESSION ?? [],
        'cookies' => $_COOKIE ?? [],
        'user_id' => $_SESSION['user_id'] ?? 'NO_SET',
        'user_email' => $_SESSION['user_email'] ?? 'NO_SET'
    ];
    
    echo json_encode($debug_info, JSON_PRETTY_PRINT);
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'test-write-session') {
    // Limpiar y escribir sesión de prueba
    $_SESSION = [];
    $_SESSION['test_data'] = 'SESION_FUNCIONA';
    $_SESSION['timestamp'] = time();
    $_SESSION['session_id'] = session_id();
    
    // Forzar escritura de sesión
    session_write_close();
    session_start();
    
    echo json_encode([
        'message' => 'Sesión escrita',
        'session_id' => session_id(),
        'session_data' => $_SESSION,
        'cookie_params' => session_get_cookie_params(),
        'save_path' => session_save_path()
    ], JSON_PRETTY_PRINT);
    exit();
}

// Incluir dependencias
require_once __DIR__ . '/../controllers/UsuarioController.php';
require_once __DIR__ . '/../utils/Response.php';

try {
    // Crear instancia del controlador
    $controller = new UsuarioController();
    
    // Obtener acción del query string
    $action = $_GET['action'] ?? '';
    
    // Enrutamiento basado en la acción
    switch ($action) {
        
        /**
         * Registro de nuevo usuario
         * POST /api/routes/usuarios.php?action=register
         * 
         * Body JSON:
         * {
         *   "nombre": "Juan",
         *   "apellido": "Pérez",
         *   "razonSocialEmpresa": "Empresa SRL",
         *   "cuit": "20-12345678-9",
         *   "correoElectronico": "juan@empresa.com",
         *   "celular": "11-1234-5678",
         *   "ciudad": "Buenos Aires",
         *   "direccion": "Av. Corrientes 1234",
         *   "provincia": "Buenos Aires",
         *   "imagen": "codigo_imagen.jpg",
         *   "password": "mi_password"
         * }
         */
        case 'register':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Método no permitido. Use POST.', 405);
                break;
            }
            $controller->register();
            break;
            
        /**
         * Login de usuario
         * POST /api/routes/usuarios.php?action=login
         * 
         * Body JSON:
         * {
         *   "email": "juan@empresa.com",
         *   "password": "mi_password"
         * }
         */
        case 'login':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Método no permitido. Use POST.', 405);
                break;
            }
            $controller->login();
            break;
            
        /**
         * Obtener perfil del usuario autenticado
         * GET /api/routes/usuarios.php?action=profile
         * 
         * Requiere: Sesión activa
         */
        case 'profile':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            $controller->profile();
            break;
            
        /**
         * Actualizar perfil del usuario
         * PUT /api/routes/usuarios.php?action=update-profile
         * 
         * Body JSON:
         * {
         *   "nombre": "Juan Carlos",
         *   "apellido": "Pérez",
         *   "razonSocialEmpresa": "Nueva Empresa SRL",
         *   "celular": "11-9876-5432",
         *   "ciudad": "CABA",
         *   "direccion": "Av. Santa Fe 4567",
         *   "provincia": "CABA",
         *   "imagen": "nueva_imagen.jpg"
         * }
         * 
         * Requiere: Sesión activa
         */
        case 'update-profile':
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                Response::error('Método no permitido. Use PUT.', 405);
                break;
            }
            $controller->updateProfile();
            break;
            
        /**
         * Cambiar contraseña
         * POST /api/routes/usuarios.php?action=change-password
         * 
         * Body JSON:
         * {
         *   "currentPassword": "password_actual",
         *   "newPassword": "nuevo_password"
         * }
         * 
         * Requiere: Sesión activa
         */
        case 'change-password':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Método no permitido. Use POST.', 405);
                break;
            }
            $controller->changePassword();
            break;
            
        /**
         * Logout
         * POST /api/routes/usuarios.php?action=logout
         * 
         * Requiere: Sesión activa
         */
        case 'logout':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Método no permitido. Use POST.', 405);
                break;
            }
            $controller->logout();
            break;
            
        /**
         * Verificar email
         * GET /api/routes/usuarios.php?action=verify-email&token=abc123
         */
        case 'verify-email':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            $controller->verifyEmail();
            break;
            
        /**
         * Endpoint de prueba para verificar que la API funciona
         * GET /api/routes/usuarios.php?action=test
         */
        case 'test':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            
            Response::success('API de usuarios funcionando correctamente', 200, [
                'version' => '1.0.0',
                'server_time' => date('Y-m-d H:i:s'),
                'endpoints' => [
                    'register' => 'POST ?action=register',
                    'login' => 'POST ?action=login',
                    'profile' => 'GET ?action=profile',
                    'update-profile' => 'PUT ?action=update-profile',
                    'change-password' => 'POST ?action=change-password',
                    'logout' => 'POST ?action=logout',
                    'verify-email' => 'GET ?action=verify-email&token=xyz'
                ]
            ]);
            break;
            
        /**
         * Listar todos los usuarios (Solo Admin)
         * GET /api/routes/usuarios.php?action=list-all
         * 
         * Query params:
         * - page: número de página (default: 1)
         * - limit: usuarios por página (default: 10)
         * - search: término de búsqueda
         */
        case 'list-all':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            $controller->listAll();
            break;
            
        /**
         * Crear usuario desde panel de admin
         * POST /api/routes/usuarios.php?action=admin-create
         * 
         * Body JSON:
         * {
         *   "nombre": "Juan",
         *   "apellido": "Pérez",
         *   "razonSocialEmpresa": "Empresa SRL",
         *   "cuit": "20-12345678-9",
         *   "correoElectronico": "juan@empresa.com",
         *   "celular": "11-1234-5678",
         *   "ciudad": "Buenos Aires",
         *   "direccion": "Av. Corrientes 1234",
         *   "provincia": "Buenos Aires",
         *   "imagen": "codigo_imagen.jpg",
         *   "password": "mi_password",
         *   "tipoUsuario": 1
         * }
         */
        case 'admin-create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Método no permitido. Use POST.', 405);
                break;
            }
            $controller->adminCreate();
            break;
            
        /**
         * Obtener estadísticas de usuarios (Solo Admin)
         * GET /api/routes/usuarios.php?action=stats
         */
        case 'stats':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido. Use GET.', 405);
                break;
            }
            $controller->getStats();
            break;

        // AGREGAR ESTE CASO AL SWITCH:
        case 'debug-profile':
            $debug_info = [
                'method' => $_SERVER['REQUEST_METHOD'],
                'session_status' => session_status(),
                'session_id' => session_id(),
                'session_data' => $_SESSION ?? [],
                'cookies_received' => $_COOKIE ?? [],
                'user_id' => $_SESSION['user_id'] ?? 'NOT_SET',
                'user_email' => $_SESSION['user_email'] ?? 'NOT_SET',
                'headers' => getallheaders(),
                'cookie_params' => session_get_cookie_params(),
                'ini_settings' => [
                    'samesite' => ini_get('session.cookie_samesite'),
                    'secure' => ini_get('session.cookie_secure'),
                    'httponly' => ini_get('session.cookie_httponly')
                ]
            ];
            
            echo json_encode([
                'success' => true,
                'message' => 'Debug info',
                'data' => $debug_info
            ], JSON_PRETTY_PRINT);
            exit();
            break;

            
        /**
         * Acción no encontrada
         */

        
        default:
            Response::error('Acción no válida', 400, [
                'available_actions' => [
                    'register',
                    'login', 
                    'profile',
                    'update-profile',
                    'change-password',
                    'logout',
                    'verify-email',
                    'test',
                    'list-all',
                    'admin-create',
                    'stats'
                ],
                'usage' => 'Agregue ?action=nombre_accion a la URL'
            ]);
            break;
    }
    
} catch (Exception $e) {
    // Log del error
    error_log("Error en usuarios.php: " . $e->getMessage());
    
    // Respuesta de error genérica
    Response::serverError('Error interno del servidor');
}

/**
 * Ejemplos de uso desde JavaScript (fetch):
 * 
 * // Registro
 * fetch('/api/routes/usuarios.php?action=register', {
 *     method: 'POST',
 *     headers: { 'Content-Type': 'application/json' },
 *     body: JSON.stringify({
 *         nombre: 'Juan',
 *         apellido: 'Pérez',
 *         razonSocialEmpresa: 'Mi Empresa',
 *         cuit: '20-12345678-9',
 *         correoElectronico: 'juan@empresa.com',
 *         password: 'mi_password'
 *     })
 * });
 * 
 * // Login
 * fetch('/api/routes/usuarios.php?action=login', {
 *     method: 'POST',
 *     headers: { 'Content-Type': 'application/json' },
 *     body: JSON.stringify({
 *         email: 'juan@empresa.com',
 *         password: 'mi_password'
 *     })
 * });
 * 
 * // Obtener perfil
 * fetch('/api/routes/usuarios.php?action=profile');
 * 
 * // Test de API
 * fetch('/api/routes/usuarios.php?action=test');
 */
?>