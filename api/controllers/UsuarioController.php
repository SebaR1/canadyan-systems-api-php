<?php
/**
 * Controlador Usuario
 * Canadian Sistemas API
 */

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class UsuarioController {
    
    /**
     * Registrar nuevo usuario
     * POST /api/routes/usuarios.php
     */
    public function register() {
        try {
            // Verificar que sea POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Método no permitido', 405);
                return;
            }
            
            // Obtener datos JSON
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                Response::error('Datos JSON inválidos', 400);
                return;
            }
            
            // Crear instancia del modelo
            $usuario = new Usuario();
            
            // Asignar valores
            $usuario->nombre = $input['nombre'] ?? '';
            $usuario->apellido = $input['apellido'] ?? '';
            $usuario->razon_social_empresa = $input['razonSocialEmpresa'] ?? '';
            $usuario->cuit = $input['cuit'] ?? '';
            $usuario->correo_electronico = $input['correoElectronico'] ?? '';
            $usuario->celular = $input['celular'] ?? '';
            $usuario->ciudad = $input['ciudad'] ?? '';
            $usuario->direccion = $input['direccion'] ?? '';
            $usuario->provincia = $input['provincia'] ?? '';
            $usuario->cod_imagen = $input['imagen'] ?? null;
            $usuario->password = $input['password'] ?? '';
            $usuario->tipo_usuario_id = 1; // Cliente por defecto
            
            // Validar datos
            $errors = $usuario->validate();
            if (!empty($errors)) {
                Response::error('Errores de validación', 400, ['errors' => $errors]);
                return;
            }
            
            // Verificar si el email ya existe
            if ($usuario->findByEmail($usuario->correo_electronico)) {
                Response::error('El correo electrónico ya está registrado', 409);
                return;
            }
            
            // Verificar si el CUIT ya existe
            if ($usuario->findByCuit($usuario->cuit)) {
                Response::error('El CUIT ya está registrado', 409);
                return;
            }
            
            // Crear usuario
            if ($usuario->create()) {
                // Obtener datos del usuario creado (sin contraseña)
                $usuario_data = $usuario->readOne();
                unset($usuario_data['password']);
                
                Response::success('Usuario registrado exitosamente', 201, [
                    'usuario' => $usuario_data,
                    'message' => 'Registro completado. Por favor verifica tu email.'
                ]);
            } else {
                Response::error('Error al crear el usuario', 500);
            }
            
        } catch (Exception $e) {
            error_log("Error en register: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Login de usuario
     * POST /api/routes/usuarios.php
     */
    public function login() {
        try {
            // Verificar que sea POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Método no permitido', 405);
                return;
            }
            
            // Obtener datos JSON
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                Response::error('Datos JSON inválidos', 400);
                return;
            }
            
            $email = $input['email'] ?? '';
            $password = $input['password'] ?? '';
            
            // Validar campos requeridos
            if (empty($email) || empty($password)) {
                Response::error('Email y contraseña son requeridos', 400);
                return;
            }
            
            // Buscar usuario
            $usuario = new Usuario();
            $userData = $usuario->findByEmail($email);
            
            if (!$userData) {
                Response::error('Credenciales inválidas', 401);
                return;
            }
            
            // Verificar contraseña
            if (!$usuario->verifyPassword($password, $userData['password'])) {
                Response::error('Credenciales inválidas', 401);
                return;
            }
            
            // Generar token simple (en producción usar JWT)
            $token = $this->generateToken($userData['id']);
            
            // Guardar sesión
            $this->startSessionWithCORS();

            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['user_email'] = $userData['correo_electronico'];
            $_SESSION['user_type'] = $userData['tipo_usuario_id'];
            
            // Remover contraseña de la respuesta
            unset($userData['password']);

            error_log("DEBUG LOGIN - Session ID: " . session_id());
            error_log("DEBUG LOGIN - Session data: " . print_r($_SESSION, true));
            error_log("DEBUG LOGIN - Session status: " . session_status());
                        
            Response::success('Login exitoso', 200, [
                'usuario' => $userData,
                'token' => $token,
                'session_id' => session_id()
            ]);
            
        } catch (Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Obtener perfil del usuario autenticado
     * GET /api/routes/usuarios.php
     */
    public function profile() {
        try {
            // Verificar que sea GET
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido', 405);
                return;
            }
            
            // Verificar autenticación
            $user_id = $this->getAuthenticatedUserId();
            if (!$user_id) {
                Response::error('No autorizado', 401);
                return;
            }
            
            // Obtener datos del usuario
            $usuario = new Usuario();
            $usuario->id = $user_id;
            $userData = $usuario->readOne();
            
            if (!$userData) {
                Response::error('Usuario no encontrado', 404);
                return;
            }
            
            Response::success('Perfil obtenido', 200, ['usuario' => $userData]);
            
        } catch (Exception $e) {
            error_log("Error en profile: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Actualizar perfil del usuario
     * PUT /api/routes/usuarios.php
     */
    public function updateProfile() {
        try {
            // Verificar que sea PUT
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                Response::error('Método no permitido', 405);
                return;
            }
            
            // Verificar autenticación
            $authenticated_user_id = $this->getAuthenticatedUserId();
            if (!$authenticated_user_id) {
                Response::error('No autorizado', 401);
                return;
            }

            // Obtener datos JSON
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                Response::error('Datos JSON inválidos', 400);
                return;
            }

            // Si es admin y viene un ID en la URL, editar ese usuario. Si no, editar el propio.
            $target_user_id = $authenticated_user_id;
            if ($this->isAdmin() && isset($_GET['id'])) {
                $target_user_id = $_GET['id'];
            }

            // LOG: Ver qué datos llegaron
            error_log("========== UPDATE PROFILE DEBUG ==========");
            error_log("Authenticated User ID: " . $authenticated_user_id);
            error_log("Target User ID: " . $target_user_id);
            error_log("Is Admin: " . ($this->isAdmin() ? 'YES' : 'NO'));
            error_log("Input data: " . json_encode($input));

            // Crear instancia del modelo
            $usuario = new Usuario();
            $usuario->id = $target_user_id;

            // Verificar que el usuario existe
            if (!$usuario->readOne()) {
                Response::error('Usuario no encontrado', 404);
                return;
            }

            // LOG: Ver datos actuales del usuario
            error_log("Current CUIT: " . $usuario->cuit);
            error_log("Current Email: " . $usuario->correo_electronico);
            error_log("Current Type: " . $usuario->tipo_usuario_id);
            
            // Asignar nuevos valores
            $usuario->nombre = $input['nombre'] ?? $usuario->nombre;
            $usuario->apellido = $input['apellido'] ?? $usuario->apellido;
            $usuario->razon_social_empresa = $input['razonSocialEmpresa'] ?? $usuario->razon_social_empresa;
            $usuario->celular = $input['celular'] ?? $usuario->celular;
            $usuario->ciudad = $input['ciudad'] ?? $usuario->ciudad;
            $usuario->direccion = $input['direccion'] ?? $usuario->direccion;
            $usuario->provincia = $input['provincia'] ?? $usuario->provincia;
            $usuario->cod_imagen = $input['imagen'] ?? $usuario->cod_imagen;

            // Si es admin, permitir editar campos bloqueados
            if ($this->isAdmin()) {
                error_log(">>> ADMIN DETECTED - Processing admin fields");

                // CUIT - validar que no exista para otro usuario
                if (isset($input['cuit'])) {
                    error_log("CUIT in input: " . $input['cuit']);
                    if ($input['cuit'] !== $usuario->cuit) {
                        error_log("CUIT is different, checking if exists for other users...");
                        $existingUser = $usuario->findByCuit($input['cuit']);
                        if ($existingUser && $existingUser['id'] != $target_user_id) {
                            error_log("CUIT already exists for user ID: " . $existingUser['id']);
                            Response::error('El CUIT ya está registrado para otro usuario', 409);
                            return;
                        }
                        error_log("CUIT is valid, updating to: " . $input['cuit']);
                        $usuario->cuit = $input['cuit'];
                    } else {
                        error_log("CUIT is the same, no change needed");
                    }
                } else {
                    error_log("CUIT not present in input");
                }

                // Email - validar que no exista para otro usuario
                if (isset($input['correoElectronico'])) {
                    error_log("Email in input: " . $input['correoElectronico']);
                    if ($input['correoElectronico'] !== $usuario->correo_electronico) {
                        error_log("Email is different, checking if exists for other users...");
                        $existingUser = $usuario->findByEmail($input['correoElectronico']);
                        if ($existingUser && $existingUser['id'] != $target_user_id) {
                            error_log("Email already exists for user ID: " . $existingUser['id']);
                            Response::error('El correo electrónico ya está registrado para otro usuario', 409);
                            return;
                        }
                        error_log("Email is valid, updating to: " . $input['correoElectronico']);
                        $usuario->correo_electronico = $input['correoElectronico'];
                    } else {
                        error_log("Email is the same, no change needed");
                    }
                } else {
                    error_log("Email not present in input");
                }

                // Tipo de usuario
                if (isset($input['tipoUsuarioId'])) {
                    error_log("User Type in input: " . $input['tipoUsuarioId']);
                    $usuario->tipo_usuario_id = intval($input['tipoUsuarioId']);
                    error_log("User Type updated to: " . $usuario->tipo_usuario_id);
                } else {
                    error_log("User Type not present in input");
                }
            } else {
                error_log(">>> NOT ADMIN - Skipping admin fields");
            }

            // LOG: Ver valores antes de update
            error_log("BEFORE UPDATE:");
            error_log("  CUIT: " . $usuario->cuit);
            error_log("  Email: " . $usuario->correo_electronico);
            error_log("  Type: " . $usuario->tipo_usuario_id);

            // Actualizar usuario
            if ($usuario->update()) {
                error_log("UPDATE SUCCESS");
                $userData = $usuario->readOne();
                error_log("AFTER READONE:");
                error_log("  CUIT: " . $userData['cuit']);
                error_log("  Email: " . $userData['correo_electronico']);
                error_log("  Type: " . $userData['tipo_usuario_id']);
                error_log("==========================================");
                Response::success('Perfil actualizado exitosamente', 200, ['usuario' => $userData]);
            } else {
                error_log("UPDATE FAILED");
                error_log("==========================================");
                Response::error('Error al actualizar el perfil', 500);
            }
            
        } catch (Exception $e) {
            error_log("Error en updateProfile: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Cambiar contraseña
     * POST /api/routes/usuarios.php
     */
    public function changePassword() {
        try {
            // Verificar que sea POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Método no permitido', 405);
                return;
            }
            
            // Verificar autenticación
            $user_id = $this->getAuthenticatedUserId();
            if (!$user_id) {
                Response::error('No autorizado', 401);
                return;
            }
            
            // Obtener datos JSON
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                Response::error('Datos JSON inválidos', 400);
                return;
            }
            
            $current_password = $input['currentPassword'] ?? '';
            $new_password = $input['newPassword'] ?? '';
            
            // Validar campos
            if (empty($current_password) || empty($new_password)) {
                Response::error('Contraseña actual y nueva son requeridas', 400);
                return;
            }
            
            if (strlen($new_password) < 6) {
                Response::error('La nueva contraseña debe tener al menos 6 caracteres', 400);
                return;
            }
            
            // Verificar usuario y contraseña actual
            $usuario = new Usuario();
            $userData = $usuario->findByEmail($_SESSION['user_email']);
            
            if (!$usuario->verifyPassword($current_password, $userData['password'])) {
                Response::error('Contraseña actual incorrecta', 400);
                return;
            }
            
            // Cambiar contraseña
            $usuario->id = $user_id;
            if ($usuario->changePassword($new_password)) {
                Response::success('Contraseña cambiada exitosamente', 200);
            } else {
                Response::error('Error al cambiar la contraseña', 500);
            }
            
        } catch (Exception $e) {
            error_log("Error en changePassword: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Logout
     * POST /api/routes/usuarios.php
     */
    public function logout() {
        try {
            $this->startSessionWithCORS();
            
            // Limpiar datos de sesión
            $_SESSION = array();
            
            // Eliminar la cookie de sesión del navegador
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            
            // Destruir la sesión
            session_destroy();
            
            Response::success('Logout exitoso', 200);
            
        } catch (Exception $e) {
            error_log("Error en logout: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Verificar email
     * POST /api/routes/usuarios.php
     */
    public function verifyEmail() {
        try {
            // Obtener token del query string
            $token = $_GET['token'] ?? '';
            
            if (empty($token)) {
                Response::error('Token de verificación requerido', 400);
                return;
            }
            
            // Decodificar token (implementar lógica según tu sistema)
            $user_id = $this->decodeToken($token);
            
            if (!$user_id) {
                Response::error('Token inválido o expirado', 400);
                return;
            }
            
            // Verificar email
            $usuario = new Usuario();
            $usuario->id = $user_id;
            
            if ($usuario->verifyEmail()) {
                Response::success('Email verificado exitosamente', 200);
            } else {
                Response::error('Error al verificar email', 500);
            }
            
        } catch (Exception $e) {
            error_log("Error en verifyEmail: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Listar todos los usuarios (Solo Admin)
     * GET /api/routes/usuarios.php?action=list-all
     */
    public function listAll() {
        try {
            // Verificar que sea GET
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido', 405);
                return;
            }
            
            // Verificar que sea admin
            if (!$this->isAdmin()) {
                Response::error('Acceso denegado. Solo administradores', 403);
                return;
            }
            
            // Obtener parámetros de paginación
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 10;
            $search = $_GET['search'] ?? '';
            
            $usuario = new Usuario();
            $usuarios = $usuario->readAll($page, $limit, $search);
            
            Response::success('Usuarios obtenidos', 200, [
                'usuarios' => $usuarios['data'],
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $usuarios['total'],
                    'pages' => ceil($usuarios['total'] / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Error en listAll: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Crear usuario desde panel de admin
     * POST /api/routes/usuarios.php?action=admin-create
     */
    public function adminCreate() {
        try {
            // Verificar que sea POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Método no permitido', 405);
                return;
            }

            // Verificar que sea admin
            if (!$this->isAdmin()) {
                Response::error('Acceso denegado. Solo administradores', 403);
                return;
            }

            // Obtener datos JSON
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                Response::error('Datos JSON inválidos', 400);
                return;
            }

            // Crear instancia del modelo
            $usuario = new Usuario();

            // Contraseña predefinida
            $generated_password = 'password123';

            // Asignar valores
            $usuario->nombre = $input['nombre'] ?? '';
            $usuario->apellido = $input['apellido'] ?? '';
            $usuario->razon_social_empresa = $input['razonSocialEmpresa'] ?? '';
            $usuario->cuit = $input['cuit'] ?? '';
            $usuario->correo_electronico = $input['correoElectronico'] ?? '';
            $usuario->celular = $input['celular'] ?? '';
            $usuario->ciudad = $input['ciudad'] ?? '';
            $usuario->direccion = $input['direccion'] ?? '';
            $usuario->provincia = $input['provincia'] ?? '';
            $usuario->cod_imagen = $input['imagen'] ?? null;
            $usuario->password = $generated_password;
            $usuario->tipo_usuario_id = $input['tipoUsuario'] ?? 1; // Admin puede asignar tipo

            // Validar datos
            $errors = $usuario->validate();
            if (!empty($errors)) {
                Response::error('Errores de validación', 400, ['errors' => $errors]);
                return;
            }

            // Verificar si el email ya existe
            if ($usuario->findByEmail($usuario->correo_electronico)) {
                Response::error('El correo electrónico ya está registrado', 409);
                return;
            }

            // Verificar si el CUIT ya existe
            if ($usuario->findByCuit($usuario->cuit)) {
                Response::error('El CUIT ya está registrado', 409);
                return;
            }

            // Crear usuario
            if ($usuario->create()) {
                // Obtener datos del usuario creado (sin contraseña)
                $usuario_data = $usuario->readOne();
                unset($usuario_data['password']);

                Response::success('Usuario creado exitosamente por administrador', 201, [
                    'usuario' => $usuario_data,
                    'generated_password' => $generated_password
                ]);
            } else {
                Response::error('Error al crear el usuario', 500);
            }

        } catch (Exception $e) {
            error_log("Error en adminCreate: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Obtener estadísticas de usuarios (Solo Admin)
     * GET /api/routes/usuarios.php?action=stats
     */
    public function getStats() {
        try {
            // Verificar que sea GET
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido', 405);
                return;
            }

            // Verificar que sea admin
            if (!$this->isAdmin()) {
                Response::error('Acceso denegado. Solo administradores', 403);
                return;
            }

            $usuario = new Usuario();
            $stats = $usuario->getStats();

            Response::success('Estadísticas obtenidas', 200, ['stats' => $stats]);

        } catch (Exception $e) {
            error_log("Error en getStats: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    /**
     * Eliminar usuario (soft delete) - Solo Admin
     * DELETE /api/routes/usuarios.php?action=delete&id=X
     */
    public function deleteUser() {
        try {
            // Verificar que sea DELETE
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                Response::error('Método no permitido', 405);
                return;
            }

            // Verificar que sea admin
            if (!$this->isAdmin()) {
                Response::error('Acceso denegado. Solo administradores', 403);
                return;
            }

            // Obtener ID del usuario a eliminar
            $id = $_GET['id'] ?? null;
            if (!$id) {
                Response::error('ID de usuario requerido', 400);
                return;
            }

            // Verificar que el usuario existe
            $usuario = new Usuario();
            $usuario->id = $id;

            if (!$usuario->readOne()) {
                Response::error('Usuario no encontrado', 404);
                return;
            }

            // Prevenir auto-eliminación
            $current_user_id = $this->getAuthenticatedUserId();
            if ($id == $current_user_id) {
                Response::error('No puedes eliminar tu propia cuenta', 400);
                return;
            }

            // Eliminar usuario (soft delete)
            if ($usuario->delete()) {
                Response::success('Usuario eliminado exitosamente', 200);
            } else {
                Response::error('Error al eliminar usuario', 500);
            }

        } catch (Exception $e) {
            error_log("Error en deleteUser: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Generar contraseña segura automáticamente
     */
    private function generateSecurePassword($length = 12) {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*';
        $all = $uppercase . $lowercase . $numbers . $special;

        $password = '';
        // Asegurar que tenga al menos uno de cada tipo
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];

        // Completar el resto
        for ($i = 4; $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        // Mezclar los caracteres
        return str_shuffle($password);
    }

    /**
     * Verificar si el usuario actual es administrador
     */
    private function isAdmin() {
        $this->startSessionWithCORS();

        $user_type = $_SESSION['user_type'] ?? null;
        return $user_type == 2; // Asumiendo que tipo 2 = Admin
    }
    
    /**
     * Verificar si el usuario actual está autenticado
     */
    private function isAuthenticated() {
        $this->startSessionWithCORS();

        return isset($_SESSION['user_id']);
    }
    
    /**
     * Obtener ID del usuario autenticado
     */
    private function getAuthenticatedUserId() {
        $this->startSessionWithCORS();

        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Generar token simple (reemplazar por JWT en producción)
     */
    private function generateToken($user_id) {
        return base64_encode($user_id . ':' . time() . ':' . bin2hex(random_bytes(16)));
    }
    
    /**
     * Decodificar token simple
     */
    private function decodeToken($token) {
        $decoded = base64_decode($token);
        $parts = explode(':', $decoded);
        
        if (count($parts) >= 2) {
            return $parts[0]; // user_id
        }
        
        return false;
    }

    /**
     * Configurar sesión con parámetros CORS consistentes
     */
    private function startSessionWithCORS() {
        if (session_status() === PHP_SESSION_NONE) {
            // Configurar cookies ANTES de session_start()
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => true, // Producción HTTPS
                'httponly' => true,
                'samesite' => 'Lax' // Cambiar a 'Strict' si es necesario
            ]);
            
            session_start();
        }
    }
}
?>