<?php
/**
 * Controlador Contacto
 * Canadian Sistemas API
 */

require_once __DIR__ . '/../models/MensajeContacto.php';
require_once __DIR__ . '/../services/EmailService.php';
require_once __DIR__ . '/../utils/Response.php';

class ContactoController {
    
    /**
     * Enviar mensaje de contacto
     * POST /api/routes/contacto.php?action=send
     */
    public function enviar() {
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
            
            // Validar campos requeridos
            $errores = $this->validarDatos($input);
            if (!empty($errores)) {
                Response::error('Errores de validación', 400, ['errores' => $errores]);
                return;
            }
            
            // Preparar datos para el modelo
            $mensajeData = [
                'nombre_apellido' => trim($input['nombreApellido']),
                'cuit' => trim($input['cuit']),
                'correo_electronico' => trim($input['correoElectronico']),
                'celular' => trim($input['celular'] ?? ''),
                'localidad' => trim($input['localidad'] ?? ''),
                'razon_social_empresa' => trim($input['razonSocialEmpresa']),
                'mensaje' => trim($input['mensaje'])
            ];
            
            // 1. Guardar en base de datos
            $mensajeContacto = new MensajeContacto();
            $mensajeContacto->nombre_apellido = $mensajeData['nombre_apellido'];
            $mensajeContacto->cuit = $mensajeData['cuit'];
            $mensajeContacto->correo_electronico = $mensajeData['correo_electronico'];
            $mensajeContacto->celular = $mensajeData['celular'];
            $mensajeContacto->localidad = $mensajeData['localidad'];
            $mensajeContacto->razon_social_empresa = $mensajeData['razon_social_empresa'];
            $mensajeContacto->mensaje = $mensajeData['mensaje'];
            
            if (!$mensajeContacto->create()) {
                Response::error('Error al guardar el mensaje en la base de datos', 500);
                return;
            }
            
            // 2. Enviar emails
            $emailService = new EmailService();
            
            // Enviar notificación al admin
            $emailAdminEnviado = $emailService->sendContactNotification($mensajeData);
            
            // Enviar confirmación al cliente
            $emailClienteEnviado = $emailService->sendContactConfirmation($mensajeData);
            
            // Preparar respuesta
            $respuesta = [
                'mensaje_id' => $mensajeContacto->id,
                'guardado_bd' => true,
                'email_admin_enviado' => $emailAdminEnviado,
                'email_cliente_enviado' => $emailClienteEnviado
            ];
            
            // Si al menos uno de los emails falló, notificar
            if (!$emailAdminEnviado || !$emailClienteEnviado) {
                $warnings = [];
                if (!$emailAdminEnviado) {
                    $warnings[] = 'No se pudo enviar la notificación al administrador';
                }
                if (!$emailClienteEnviado) {
                    $warnings[] = 'No se pudo enviar el email de confirmación al cliente';
                }
                
                Response::success(
                    'Mensaje guardado pero hubo problemas con el envío de emails', 
                    200, 
                    array_merge($respuesta, ['advertencias' => $warnings])
                );
                return;
            }
            
            // Todo exitoso
            Response::success(
                'Mensaje enviado exitosamente. Te contactaremos pronto.', 
                201, 
                $respuesta
            );
            
        } catch (Exception $e) {
            error_log("Error en ContactoController::enviar: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Listar mensajes de contacto (Solo Admin)
     * GET /api/routes/contacto.php?action=list
     */
    public function listar() {
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
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            
            // Validar parámetros
            if ($page < 1) $page = 1;
            if ($limit < 1 || $limit > 100) $limit = 10;
            
            $offset = ($page - 1) * $limit;
            
            $mensajeContacto = new MensajeContacto();
            $mensajes = $mensajeContacto->readAll($limit, $offset);
            $total = $mensajeContacto->countTotal();
            $totalPages = ceil($total / $limit);
            
            Response::success('Mensajes obtenidos exitosamente', 200, [
                'mensajes' => $mensajes,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => $totalPages,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Error en ContactoController::listar: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Obtener mensaje por ID (Solo Admin)
     * GET /api/routes/contacto.php?action=get&id=1
     */
    public function obtener() {
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
            
            // Obtener ID
            $id = $_GET['id'] ?? '';
            
            if (empty($id) || !is_numeric($id)) {
                Response::error('ID requerido y debe ser numérico', 400);
                return;
            }
            
            $mensajeContacto = new MensajeContacto();
            $mensajeContacto->id = intval($id);
            
            $mensaje = $mensajeContacto->readOne();
            
            if ($mensaje) {
                Response::success('Mensaje obtenido exitosamente', 200, [
                    'mensaje' => $mensaje
                ]);
            } else {
                Response::error('Mensaje no encontrado', 404);
            }
            
        } catch (Exception $e) {
            error_log("Error en ContactoController::obtener: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Validar datos del formulario de contacto
     */
    private function validarDatos($datos) {
        $errores = [];
        
        // Nombre y Apellido
        if (empty($datos['nombreApellido'])) {
            $errores[] = 'El nombre y apellido es requerido';
        }
        
        // CUIT
        if (empty($datos['cuit'])) {
            $errores[] = 'El CUIT es requerido';
        } elseif (!$this->validarFormatoCUIT($datos['cuit'])) {
            $errores[] = 'El formato del CUIT no es válido';
        }
        
        // Email
        if (empty($datos['correoElectronico'])) {
            $errores[] = 'El correo electrónico es requerido';
        } elseif (!filter_var($datos['correoElectronico'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo electrónico no tiene un formato válido';
        }
        
        // Razón Social
        if (empty($datos['razonSocialEmpresa'])) {
            $errores[] = 'La razón social de la empresa es requerida';
        }
        
        // Mensaje
        if (empty($datos['mensaje'])) {
            $errores[] = 'El mensaje es requerido';
        } elseif (strlen($datos['mensaje']) < 10) {
            $errores[] = 'El mensaje debe tener al menos 10 caracteres';
        }
        
        // Validar que tenga al menos celular o email (en teoría siempre tendrá email por validación anterior)
        if (empty($datos['celular']) && empty($datos['correoElectronico'])) {
            $errores[] = 'Debe proporcionar al menos un método de contacto (celular o email)';
        }
        
        return $errores;
    }
    
    /**
     * Validar formato de CUIT (solo formato, sin verificar dígito)
     */
    private function validarFormatoCUIT($cuit) {
        // Remover todo lo que no sean números
        $cuit = preg_replace('/[^0-9]/', '', $cuit);

        // Verificar que tenga exactamente 11 dígitos
        return strlen($cuit) === 11;
    }
      
    /**
     * Verificar si el usuario actual es administrador
     */
    private function isAdmin() {
        // Iniciar sesión si no está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            session_start();
        }

        $user_type = $_SESSION['user_type'] ?? null;
        return $user_type == 2; // Tipo 2 = Admin
    }
}
?>