<?php
/**
 * Controlador ProductoImagen
 * Gestiona las operaciones con imágenes de productos
 */

require_once __DIR__ . '/../models/ProductoImagen.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/ImageValidator.php';

class ProductoImagenController {
    
    private $uploadDir = __DIR__ . '/../../uploads/productos/';
    private $uploadUrl = 'uploads/productos/';
    private $maxFileSize = 5242880; // 5MB en bytes
    private $maxImagenes = 10;
    
    /**
     * Constructor - Crear directorio si no existe
     */
    public function __construct() {
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Subir imagen(es)
     * POST /api/routes/producto_imagenes.php?action=upload
     * 
     * Recibe:
     * - producto_id (POST)
     * - tipo (POST) - 'principal', 'galeria', 'esquema'
     * - imagen (FILES) - Una o múltiples imágenes
     */
    public function upload() {
        try {
            // Verificar que sea POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Método no permitido', 405);
                return;
            }
            
            // DEBUG: Ver qué está llegando
            error_log("POST data: " . print_r($_POST, true));
            error_log("FILES data: " . print_r($_FILES, true));
            
            // Validar producto_id
            if (!isset($_POST['producto_id']) || !is_numeric($_POST['producto_id'])) {
                Response::error('producto_id requerido y debe ser numérico', 400);
                return;
            }
            
            $producto_id = intval($_POST['producto_id']);
            
            // Verificar que el producto existe
            $producto = new Producto();
            $producto->id = $producto_id;
            if (!$producto->readOne()) {
                Response::error('Producto no encontrado', 404);
                return;
            }
            
            // Validar que se subieron archivos
            if (!isset($_FILES['imagenes']) || empty($_FILES['imagenes']['name'][0])) {
                Response::error('No se recibieron imágenes', 400);
                return;
            }
            
            // Obtener tipo (por defecto 'galeria')
            $tipo = $_POST['tipo'] ?? 'galeria';
            if (!in_array($tipo, ['principal', 'galeria', 'esquema'])) {
                Response::error('Tipo de imagen inválido', 400);
                return;
            }
            
            // Verificar límite de imágenes
            $imagenModel = new ProductoImagen();
            $totalActual = $imagenModel->contarImagenes($producto_id);
            $nuevasImagenes = count($_FILES['imagenes']['name']);
            
            if (($totalActual + $nuevasImagenes) > $this->maxImagenes) {
                Response::error("Máximo {$this->maxImagenes} imágenes por producto", 400);
                return;
            }
            
            // Procesar cada imagen
            $imagenesSubidas = [];
            $errores = [];
            
            for ($i = 0; $i < count($_FILES['imagenes']['name']); $i++) {
                // Verificar que no hubo error en la subida
                if ($_FILES['imagenes']['error'][$i] !== UPLOAD_ERR_OK) {
                    $errores[] = "Error al subir {$_FILES['imagenes']['name'][$i]}";
                    continue;
                }
                
                $file = [
                    'name' => $_FILES['imagenes']['name'][$i],
                    'type' => $_FILES['imagenes']['type'][$i],
                    'tmp_name' => $_FILES['imagenes']['tmp_name'][$i],
                    'error' => $_FILES['imagenes']['error'][$i],
                    'size' => $_FILES['imagenes']['size'][$i]
                ];
                
                // Validar imagen
                $validator = new ImageValidator();
                $validacion = $validator->validate($file, $this->maxFileSize);
                
                if (!$validacion['valid']) {
                    $errores[] = $validacion['error'];
                    continue;
                }
                
                // Generar nombre único
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $nombreArchivo = time() . '_' . uniqid() . '_' . $this->sanitizeFilename($file['name']);
                $rutaCompleta = $this->uploadDir . $nombreArchivo;
                
                // Verificar si GD está disponible
                if (extension_loaded('gd')) {
                    // Redimensionar si es necesario
                    $imagenProcesada = $this->processImage($file['tmp_name'], $validacion['dimensions']);
                    
                    if (!$imagenProcesada) {
                        $errores[] = "Error al procesar {$file['name']}";
                        continue;
                    }
                    
                    // Guardar imagen procesada
                    if (!$this->saveImage($imagenProcesada, $rutaCompleta, $extension)) {
                        $errores[] = "Error al guardar {$file['name']}";
                        continue;
                    }
                    
                    // Liberar memoria
                    imagedestroy($imagenProcesada);
                } else {
                    // GD no disponible, guardar imagen directamente sin procesar
                    if (!move_uploaded_file($file['tmp_name'], $rutaCompleta)) {
                        $errores[] = "Error al guardar {$file['name']}";
                        continue;
                    }
                }
                
                // Guardar en base de datos
                $imagenModel = new ProductoImagen();
                $imagenModel->producto_id = $producto_id;
                $imagenModel->url = $this->uploadUrl . $nombreArchivo;
                
                // La primera imagen es principal si no hay ninguna
                if ($i === 0 && $totalActual === 0) {
                    $imagenModel->tipo = 'principal';
                } else {
                    $imagenModel->tipo = ($i === 0 && $tipo === 'principal') ? 'principal' : 'galeria';
                }
                
                $imagenModel->alt_text = $producto->nombre;
                
                if ($imagenModel->create()) {
                    $imagenesSubidas[] = [
                        'id' => $imagenModel->id,
                        'url' => $imagenModel->url,
                        'tipo' => $imagenModel->tipo,
                        'orden' => $imagenModel->orden
                    ];
                } else {
                    // Si falla guardar en BD, eliminar archivo
                    unlink($rutaCompleta);
                    $errores[] = "Error al guardar {$file['name']} en base de datos";
                }
            }
            
            // Responder
            if (count($imagenesSubidas) > 0) {
                Response::success('Imágenes subidas exitosamente', 201, [
                    'imagenes' => $imagenesSubidas,
                    'total_subidas' => count($imagenesSubidas),
                    'errores' => $errores
                ]);
            } else {
                Response::error('No se pudo subir ninguna imagen', 500, [
                    'errores' => $errores
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Error en ProductoImagenController::upload: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Listar imágenes de un producto
     * GET /api/routes/producto_imagenes.php?action=list&producto_id=1
     */
    public function list() {
        try {
            if (!isset($_GET['producto_id']) || !is_numeric($_GET['producto_id'])) {
                Response::error('producto_id requerido y debe ser numérico', 400);
                return;
            }
            
            $producto_id = intval($_GET['producto_id']);
            
            $imagenModel = new ProductoImagen();
            $imagenes = $imagenModel->getByProducto($producto_id);
            
            Response::success('Imágenes obtenidas exitosamente', 200, [
                'imagenes' => $imagenes,
                'total' => count($imagenes)
            ]);
            
        } catch (Exception $e) {
            error_log("Error en ProductoImagenController::list: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Eliminar imagen
     * DELETE /api/routes/producto_imagenes.php?action=delete&id=1
     */
    public function delete() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                Response::error('Método no permitido', 405);
                return;
            }
            
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                Response::error('ID de imagen requerido', 400);
                return;
            }
            
            $id = intval($_GET['id']);
            
            $imagenModel = new ProductoImagen();
            $imagenModel->id = $id;
            
            // Obtener información de la imagen
            if (!$imagenModel->readOne()) {
                Response::error('Imagen no encontrada', 404);
                return;
            }
            
            $url = $imagenModel->url;
            
            // Eliminar de base de datos
            if ($imagenModel->delete()) {
                // Eliminar archivo físico
                $rutaArchivo = __DIR__ . '/../../public/' . $url;
                if (file_exists($rutaArchivo)) {
                    unlink($rutaArchivo);
                }
                
                Response::success('Imagen eliminada exitosamente', 200);
            } else {
                Response::error('Error al eliminar la imagen', 500);
            }
            
        } catch (Exception $e) {
            error_log("Error en ProductoImagenController::delete: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Cambiar imagen principal
     * PATCH /api/routes/producto_imagenes.php?action=set-principal&id=1
     */
    public function setPrincipal() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
                Response::error('Método no permitido', 405);
                return;
            }
            
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                Response::error('ID de imagen requerido', 400);
                return;
            }
            
            $id = intval($_GET['id']);
            
            $imagenModel = new ProductoImagen();
            $imagenModel->id = $id;
            
            if (!$imagenModel->readOne()) {
                Response::error('Imagen no encontrada', 404);
                return;
            }
            
            if ($imagenModel->setPrincipal()) {
                Response::success('Imagen principal actualizada', 200);
            } else {
                Response::error('Error al actualizar imagen principal', 500);
            }
            
        } catch (Exception $e) {
            error_log("Error en ProductoImagenController::setPrincipal: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Reordenar imágenes
     * PATCH /api/routes/producto_imagenes.php?action=reorder
     * Body: { "ordenes": [{ "id": 1, "orden": 0 }, { "id": 2, "orden": 1 }] }
     */
    public function reorder() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
                Response::error('Método no permitido', 405);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['ordenes'])) {
                Response::error('Datos inválidos', 400);
                return;
            }
            
            $imagenModel = new ProductoImagen();
            
            if ($imagenModel->updateOrden($input['ordenes'])) {
                Response::success('Orden actualizado exitosamente', 200);
            } else {
                Response::error('Error al actualizar el orden', 500);
            }
            
        } catch (Exception $e) {
            error_log("Error en ProductoImagenController::reorder: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    // ============= MÉTODOS PRIVADOS =============
    
    /**
     * Procesar imagen (redimensionar si excede 2000px)
     */
    private function processImage($tmpPath, $dimensions) {
        try {
            $width = $dimensions['width'];
            $height = $dimensions['height'];
            $maxSize = 2000;
            
            // Cargar imagen según tipo
            $imageInfo = getimagesize($tmpPath);
            $mimeType = $imageInfo['mime'];
            
            switch ($mimeType) {
                case 'image/jpeg':
                    $source = imagecreatefromjpeg($tmpPath);
                    break;
                case 'image/png':
                    $source = imagecreatefrompng($tmpPath);
                    break;
                case 'image/gif':
                    $source = imagecreatefromgif($tmpPath);
                    break;
                default:
                    return false;
            }
            
            // Si la imagen es menor a 2000px, no redimensionar
            if ($width <= $maxSize && $height <= $maxSize) {
                return $source;
            }
            
            // Calcular nuevas dimensiones manteniendo proporción
            if ($width > $height) {
                $newWidth = $maxSize;
                $newHeight = intval(($height / $width) * $maxSize);
            } else {
                $newHeight = $maxSize;
                $newWidth = intval(($width / $height) * $maxSize);
            }
            
            // Crear imagen redimensionada
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preservar transparencia para PNG
            if ($mimeType === 'image/png') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
            }
            
            // Redimensionar
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            
            // Liberar memoria de la imagen original
            imagedestroy($source);
            
            return $resized;
            
        } catch (Exception $e) {
            error_log("Error al procesar imagen: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Guardar imagen en disco
     */
    private function saveImage($image, $path, $extension) {
        try {
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    return imagejpeg($image, $path, 90);
                case 'png':
                    return imagepng($image, $path, 8);
                case 'gif':
                    return imagegif($image, $path);
                default:
                    return false;
            }
        } catch (Exception $e) {
            error_log("Error al guardar imagen: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Sanitizar nombre de archivo
     */
    private function sanitizeFilename($filename) {
        // Obtener solo el nombre sin extensión
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        
        // Limpiar caracteres especiales
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
        $name = substr($name, 0, 50); // Limitar longitud
        
        return $name . '.' . $extension;
    }
}