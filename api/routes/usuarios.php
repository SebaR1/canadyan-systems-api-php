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
header('Access-Control-Allow-Origin: *'); // Cambiar por tu dominio en producción
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
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
                    'test'
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