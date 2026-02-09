<?php
/**
 * Clase Response
 * Manejo estandarizado de respuestas JSON
 * Canadian Sistemas API
 */

class Response {
    
    /**
     * Enviar respuesta de éxito
     */
    public static function success($message = 'Operación exitosa', $status_code = 200, $data = []) {
        self::sendResponse([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ], $status_code);
    }
    
    /**
     * Enviar respuesta de error
     */
    public static function error($message = 'Error en la operación', $status_code = 400, $data = []) {
        self::sendResponse([
            'success' => false,
            'message' => $message,
            'error' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ], $status_code);
    }
    
    /**
     * Enviar respuesta con datos específicos
     */
    public static function data($data = [], $message = 'Datos obtenidos', $status_code = 200) {
        self::sendResponse([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'count' => is_array($data) ? count($data) : 1,
            'timestamp' => date('Y-m-d H:i:s')
        ], $status_code);
    }
    
    /**
     * Enviar respuesta de validación con errores
     */
    public static function validation($errors = [], $message = 'Errores de validación') {
        self::sendResponse([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('Y-m-d H:i:s')
        ], 422);
    }
    
    /**
     * Enviar respuesta de no autorizado
     */
    public static function unauthorized($message = 'No autorizado') {
        self::sendResponse([
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ], 401);
    }
    
    /**
     * Enviar respuesta de no encontrado
     */
    public static function notFound($message = 'Recurso no encontrado') {
        self::sendResponse([
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ], 404);
    }
    
    /**
     * Enviar respuesta de conflicto (duplicado)
     */
    public static function conflict($message = 'Conflicto en los datos') {
        self::sendResponse([
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ], 409);
    }
    
    /**
     * Enviar respuesta de error interno del servidor
     */
    public static function serverError($message = 'Error interno del servidor') {
        self::sendResponse([
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ], 500);
    }
    
    /**
     * Enviar respuesta personalizada
     */
    public static function custom($response_data, $status_code = 200) {
        self::sendResponse($response_data, $status_code);
    }
    
    /**
     * Función principal para enviar respuesta
     */
    private static function sendResponse($data, $status_code) {
        // Establecer headers
        header('Content-Type: application/json; charset=utf-8');
/*         header('Access-Control-Allow-Origin: *'); // Cambiar por tu dominio en producción
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
 */        
        // Establecer código de estado HTTP
        http_response_code($status_code);
        
        // Enviar respuesta JSON
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        // Terminar ejecución
        exit();
    }
    
    /**
     * Manejar preflight requests (OPTIONS)
     */
    public static function handlePreflight() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
/*             header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
 */            header('Access-Control-Max-Age: 86400');
            http_response_code(200);
            exit();
        }
    }
    
    /**
     * Respuesta paginada
     */
    public static function paginated($data, $total, $page, $per_page, $message = 'Datos obtenidos') {
        $total_pages = ceil($total / $per_page);
        
        self::sendResponse([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'total' => (int)$total,
                'per_page' => (int)$per_page,
                'current_page' => (int)$page,
                'total_pages' => (int)$total_pages,
                'has_next' => $page < $total_pages,
                'has_prev' => $page > 1
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ], 200);
    }
}
?>