<?php
/**
 * Router para PHP Built-in Server - CON DEBUG
 * Sirve archivos estáticos y rutas de la API
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// DEBUG: Log de la petición
error_log("========================================");
error_log("URI solicitada: " . $uri);
error_log("REQUEST_URI completa: " . $_SERVER['REQUEST_URI']);

// Servir archivos desde uploads/ (alias de public/uploads/)
if (preg_match('/^\/uploads\//', $uri)) {
    $file = __DIR__ . '/public' . $uri;
    // Normalizar barras para Windows
    $file = str_replace('/', DIRECTORY_SEPARATOR, $file);
    
    error_log("Buscando archivo en: " . $file);
    error_log("¿Existe el archivo? " . (file_exists($file) ? 'SÍ' : 'NO'));
    error_log("¿Es directorio? " . (is_dir($file) ? 'SÍ' : 'NO'));
    
    if (file_exists($file) && !is_dir($file)) {
        error_log("✅ Sirviendo archivo: " . $file);
        
        // Determinar el tipo MIME
        $mimeTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml'
        ];
        
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';
        
        // Enviar headers y contenido
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    } else {
        error_log("❌ Archivo no encontrado o es directorio");
        // Listar contenido del directorio para debug
        $dir = dirname($file);
        if (is_dir($dir)) {
            error_log("📁 Contenido del directorio " . $dir . ":");
            $files = scandir($dir);
            foreach ($files as $f) {
                if ($f !== '.' && $f !== '..') {
                    error_log("  - " . $f);
                }
            }
        } else {
            error_log("📁 El directorio NO existe: " . $dir);
        }
    }
}

// Servir archivos estáticos desde public/
if (preg_match('/^\/public\//', $uri)) {
    $file = __DIR__ . $uri;
    // Normalizar barras para Windows
    $file = str_replace('/', DIRECTORY_SEPARATOR, $file);
    
    error_log("Buscando archivo público en: " . $file);
    if (file_exists($file) && !is_dir($file)) {
        error_log("✅ Sirviendo archivo público: " . $file);
        
        // Determinar el tipo MIME
        $mimeTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml'
        ];
        
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';
        
        // Enviar headers y contenido
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}

// Para TODAS las otras rutas, dejar que PHP las maneje normalmente
error_log("Dejando que PHP maneje la ruta normalmente");
return false;