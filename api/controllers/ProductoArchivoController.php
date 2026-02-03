<?php
/**
 * Controlador ProductoArchivo
 * Gestiona las operaciones con archivos descargables de productos
 */

require_once __DIR__ . '/../models/ProductoArchivo.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../utils/Response.php';

class ProductoArchivoController {

    private $uploadDir = __DIR__ . '/../../uploads/productos/archivos/';
    private $uploadUrl = 'uploads/productos/archivos/';
    private $maxFileSize = 10485760; // 10MB en bytes
    private $maxArchivos = 15;

    /**
     * Constructor - Crear directorio si no existe
     */
    public function __construct() {
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Subir archivo(s)
     * POST /api/routes/producto_archivos.php?action=upload
     *
     * Recibe:
     * - producto_id (POST)
     * - archivos (FILES) - Uno o múltiples archivos
     * - nombres_personalizados (POST, opcional) - Array JSON con nombres personalizados
     */
    public function upload() {
        try {
            // Verificar que sea POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Método no permitido', 405);
                return;
            }

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
            if (!isset($_FILES['archivos']) || empty($_FILES['archivos']['name'][0])) {
                Response::error('No se recibieron archivos', 400);
                return;
            }

            // Obtener nombres personalizados si existen
            $nombresPersonalizados = [];
            if (isset($_POST['nombres_personalizados'])) {
                $nombresPersonalizados = json_decode($_POST['nombres_personalizados'], true) ?? [];
            }

            // Verificar límite de archivos
            $archivoModel = new ProductoArchivo();
            $totalActual = $archivoModel->contarArchivos($producto_id);
            $nuevosArchivos = count($_FILES['archivos']['name']);

            if (($totalActual + $nuevosArchivos) > $this->maxArchivos) {
                Response::error("Máximo {$this->maxArchivos} archivos por producto. Actualmente tienes {$totalActual}.", 400);
                return;
            }

            // Procesar cada archivo
            $archivosSubidos = [];
            $errores = [];

            for ($i = 0; $i < count($_FILES['archivos']['name']); $i++) {
                // Verificar que no hubo error en la subida
                if ($_FILES['archivos']['error'][$i] !== UPLOAD_ERR_OK) {
                    $errores[] = "Error al subir {$_FILES['archivos']['name'][$i]}";
                    continue;
                }

                $file = [
                    'name' => $_FILES['archivos']['name'][$i],
                    'type' => $_FILES['archivos']['type'][$i],
                    'tmp_name' => $_FILES['archivos']['tmp_name'][$i],
                    'error' => $_FILES['archivos']['error'][$i],
                    'size' => $_FILES['archivos']['size'][$i]
                ];

                // Validar archivo
                $validacion = $this->validateFile($file);

                if (!$validacion['valid']) {
                    $errores[] = $validacion['error'];
                    continue;
                }

                // Generar nombre único
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $nombreLimpio = $this->sanitizeFilename(pathinfo($file['name'], PATHINFO_FILENAME));
                $nombreArchivo = time() . '_' . uniqid() . '_' . $nombreLimpio . '.' . $extension;
                $rutaCompleta = $this->uploadDir . $nombreArchivo;

                // Mover archivo
                if (!move_uploaded_file($file['tmp_name'], $rutaCompleta)) {
                    $errores[] = "Error al guardar {$file['name']}";
                    continue;
                }

                // Guardar en base de datos
                $archivoModel = new ProductoArchivo();
                $archivoModel->producto_id = $producto_id;
                $archivoModel->nombre_original = $file['name'];
                $archivoModel->nombre_personalizado = $nombresPersonalizados[$i] ?? null;
                $archivoModel->url = $this->uploadUrl . $nombreArchivo;
                $archivoModel->tipo_archivo = $extension;
                $archivoModel->tamanio_bytes = $file['size'];

                if ($archivoModel->create()) {
                    $archivosSubidos[] = [
                        'id' => $archivoModel->id,
                        'nombre_original' => $archivoModel->nombre_original,
                        'nombre_personalizado' => $archivoModel->nombre_personalizado,
                        'url' => $archivoModel->url,
                        'tipo_archivo' => $archivoModel->tipo_archivo,
                        'tamanio_bytes' => $archivoModel->tamanio_bytes,
                        'orden' => $archivoModel->orden
                    ];
                } else {
                    // Si falla guardar en BD, eliminar archivo
                    unlink($rutaCompleta);
                    $errores[] = "Error al guardar {$file['name']} en base de datos";
                }
            }

            // Responder
            if (count($archivosSubidos) > 0) {
                Response::success('Archivos subidos exitosamente', 201, [
                    'archivos' => $archivosSubidos,
                    'total_subidos' => count($archivosSubidos),
                    'errores' => $errores
                ]);
            } else {
                Response::error('No se pudo subir ningún archivo', 500, [
                    'errores' => $errores
                ]);
            }

        } catch (Exception $e) {
            error_log("Error en ProductoArchivoController::upload: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    /**
     * Listar archivos de un producto
     * GET /api/routes/producto_archivos.php?action=list&producto_id=1
     */
    public function list() {
        try {
            if (!isset($_GET['producto_id']) || !is_numeric($_GET['producto_id'])) {
                Response::error('producto_id requerido y debe ser numérico', 400);
                return;
            }

            $producto_id = intval($_GET['producto_id']);

            $archivoModel = new ProductoArchivo();
            $archivos = $archivoModel->getByProducto($producto_id);

            Response::success('Archivos obtenidos exitosamente', 200, [
                'archivos' => $archivos,
                'total' => count($archivos)
            ]);

        } catch (Exception $e) {
            error_log("Error en ProductoArchivoController::list: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    /**
     * Descargar archivo
     * GET /api/routes/producto_archivos.php?action=download&id=1
     * Fuerza la descarga del archivo
     */
    public function download() {
        try {
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                Response::error('ID de archivo requerido', 400);
                return;
            }

            $id = intval($_GET['id']);

            $archivoModel = new ProductoArchivo();
            $archivoModel->id = $id;

            // Obtener información del archivo
            if (!$archivoModel->readOne()) {
                Response::error('Archivo no encontrado', 404);
                return;
            }

            $rutaArchivo = __DIR__ . '/../../' . $archivoModel->url;

            if (!file_exists($rutaArchivo)) {
                Response::error('Archivo físico no encontrado', 404);
                return;
            }

            // Determinar nombre para descarga (personalizado o original)
            $nombreDescarga = $archivoModel->nombre_personalizado ?? $archivoModel->nombre_original;

            // Determinar tipo MIME
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $rutaArchivo);
            finfo_close($finfo);

            // Headers para forzar descarga
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
            header('Content-Length: ' . filesize($rutaArchivo));
            header('Cache-Control: must-revalidate');
            header('Pragma: public');

            // Limpiar buffer de salida
            ob_clean();
            flush();

            // Enviar archivo
            readfile($rutaArchivo);
            exit;

        } catch (Exception $e) {
            error_log("Error en ProductoArchivoController::download: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    /**
     * Actualizar nombre personalizado
     * PATCH /api/routes/producto_archivos.php?action=update
     * Body: { "id": 1, "nombre_personalizado": "Nuevo nombre" }
     */
    public function update() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
                Response::error('Método no permitido', 405);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input || !isset($input['id'])) {
                Response::error('Datos inválidos', 400);
                return;
            }

            $id = intval($input['id']);
            $nombrePersonalizado = $input['nombre_personalizado'] ?? null;

            $archivoModel = new ProductoArchivo();
            $archivoModel->id = $id;

            if (!$archivoModel->readOne()) {
                Response::error('Archivo no encontrado', 404);
                return;
            }

            $archivoModel->nombre_personalizado = $nombrePersonalizado;

            if ($archivoModel->update()) {
                Response::success('Nombre actualizado exitosamente', 200);
            } else {
                Response::error('Error al actualizar el nombre', 500);
            }

        } catch (Exception $e) {
            error_log("Error en ProductoArchivoController::update: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    /**
     * Eliminar archivo
     * DELETE /api/routes/producto_archivos.php?action=delete&id=1
     */
    public function delete() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                Response::error('Método no permitido', 405);
                return;
            }

            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                Response::error('ID de archivo requerido', 400);
                return;
            }

            $id = intval($_GET['id']);

            $archivoModel = new ProductoArchivo();
            $archivoModel->id = $id;

            // Obtener información del archivo
            if (!$archivoModel->readOne()) {
                Response::error('Archivo no encontrado', 404);
                return;
            }

            $url = $archivoModel->url;

            // Eliminar de base de datos
            if ($archivoModel->delete()) {
                // Eliminar archivo físico
                $rutaArchivo = __DIR__ . '/../../' . $url;
                if (file_exists($rutaArchivo)) {
                    unlink($rutaArchivo);
                }

                Response::success('Archivo eliminado exitosamente', 200);
            } else {
                Response::error('Error al eliminar el archivo', 500);
            }

        } catch (Exception $e) {
            error_log("Error en ProductoArchivoController::delete: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    /**
     * Reordenar archivos
     * PATCH /api/routes/producto_archivos.php?action=reorder
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

            $archivoModel = new ProductoArchivo();

            if ($archivoModel->updateOrden($input['ordenes'])) {
                Response::success('Orden actualizado exitosamente', 200);
            } else {
                Response::error('Error al actualizar el orden', 500);
            }

        } catch (Exception $e) {
            error_log("Error en ProductoArchivoController::reorder: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    // ============= MÉTODOS PRIVADOS =============

    /**
     * Validar archivo
     */
    private function validateFile($file) {
        $archivoModel = new ProductoArchivo();

        // Validar tamaño
        if ($file['size'] > $this->maxFileSize) {
            return [
                'valid' => false,
                'error' => "El archivo {$file['name']} excede el tamaño máximo de " .
                          ($this->maxFileSize / 1048576) . "MB"
            ];
        }

        // Validar extensión
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!$archivoModel->validarTipoArchivo($extension)) {
            return [
                'valid' => false,
                'error' => "Tipo de archivo no permitido: {$extension}. Tipos permitidos: " .
                          implode(', ', $archivoModel->getTiposPermitidos())
            ];
        }

        // Validar tipo MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $mimeTypesPermitidos = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/rtf',
            'application/rtf',
            'image/jpeg',
            'image/png',
            'image/gif'
        ];

        if (!in_array($mimeType, $mimeTypesPermitidos)) {
            return [
                'valid' => false,
                'error' => "Tipo MIME no permitido para {$file['name']}: {$mimeType}"
            ];
        }

        return ['valid' => true];
    }

    /**
     * Limpiar nombre de archivo
     */
    private function sanitizeFilename($filename) {
        // Reemplazar espacios y caracteres especiales
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        // Limitar longitud
        $filename = substr($filename, 0, 100);
        return $filename;
    }
}
