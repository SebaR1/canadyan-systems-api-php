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
            session_start();
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['user_email'] = $userData['correo_electronico'];
            $_SESSION['user_type'] = $userData['tipo_usuario_id'];
            
            // Remover contraseña de la respuesta
            unset($userData['password']);
            
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
            
            // Crear instancia del modelo
            $usuario = new Usuario();
            $usuario->id = $user_id;
            
            // Verificar que el usuario existe
            if (!$usuario->readOne()) {
                Response::error('Usuario no encontrado', 404);
                return;
            }
            
            // Asignar nuevos valores
            $usuario->nombre = $input['nombre'] ?? $usuario->nombre;
            $usuario->apellido = $input['apellido'] ?? $usuario->apellido;
            $usuario->razon_social_empresa = $input['razonSocialEmpresa'] ?? $usuario->razon_social_empresa;
            $usuario->celular = $input['celular'] ?? $usuario->celular;
            $usuario->ciudad = $input['ciudad'] ?? $usuario->ciudad;
            $usuario->direccion = $input['direccion'] ?? $usuario->direccion;
            $usuario->provincia = $input['provincia'] ?? $usuario->provincia;
            $usuario->cod_imagen = $input['imagen'] ?? $usuario->cod_imagen;
            
            // Actualizar usuario
            if ($usuario->update()) {
                $userData = $usuario->readOne();
                Response::success('Perfil actualizado exitosamente', 200, ['usuario' => $userData]);
            } else {
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
            session_start();
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
     * Obtener ID del usuario autenticado
     */
    private function getAuthenticatedUserId() {
        session_start();
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
}
?>