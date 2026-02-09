<?php
/**
 * Controlador Producto
 * Canadian Sistemas API
 */

require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../utils/Response.php';

class ProductoController {
    
    /**
     * Crear nuevo producto
     * POST /api/routes/productos.php?action=create
     */
    public function create() {
        try {
            // Obtener datos JSON
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                Response::error('Datos JSON inválidos', 400);
                return;
            }
            
            // Validar campos requeridos
            if (empty($input['nombre'])) {
                Response::error('El nombre es requerido', 400);
                return;
            }
            
            if (empty($input['categoria_id']) || !is_numeric($input['categoria_id'])) {
                Response::error('La categoría es requerida y debe ser numérica', 400);
                return;
            }
            
            // Crear instancia del modelo
            $producto = new Producto();
            
            // Asignar valores
            $producto->nombre = $input['nombre'];
            $producto->descripcion = $input['descripcion'] ?? '';
            $producto->precio = !empty($input['precio']) ? floatval($input['precio']) : null;
            $producto->stock = isset($input['stock']) ? intval($input['stock']) : 0;
            $producto->categoria_id = intval($input['categoria_id']);
            $producto->sku = $input['sku'] ?? null;
            $producto->activo = isset($input['activo']) ? (bool)$input['activo'] : true;
            
            // Validaciones adicionales
            if ($producto->stock < 0) {
                Response::error('El stock no puede ser negativo', 400);
                return;
            }
            
            if ($producto->precio !== null && $producto->precio < 0) {
                Response::error('El precio no puede ser negativo', 400);
                return;
            }
            
            // Crear producto
            if ($producto->create()) {
                // Obtener el producto completo con información de categoría
                $productoCompleto = $producto->readOne();
                
                Response::success('Producto creado exitosamente', 201, [
                    'producto' => $productoCompleto
                ]);
            } else {
                Response::error('Error al crear el producto', 500);
            }
            
        } catch (Exception $e) {
            error_log("Error en ProductoController::create: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Listar todos los productos con paginación
     * GET /api/routes/productos.php?action=list&page=1&limit=10&active_only=1
     */
    public function list() {
        try {
            // Aceptar tanto page como offset
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            $activeOnly = isset($_GET['active_only']) ? (bool)$_GET['active_only'] : true;
            
            // Validar limit
            if ($limit < 1 || $limit > 100) $limit = 10;
            
            // Determinar offset: usar directamente si está presente, sino calcular desde page
            if (isset($_GET['offset'])) {
                $offset = intval($_GET['offset']);
                if ($offset < 0) $offset = 0;
                $page = floor($offset / $limit) + 1;
            } else {
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                if ($page < 1) $page = 1;
                $offset = ($page - 1) * $limit;
            }
            
            $producto = new Producto();
            $productos = $producto->readAll($limit, $offset, $activeOnly);
            $total = $producto->countTotal($activeOnly);
            $totalPages = ceil($total / $limit);
            
            Response::success('Productos obtenidos exitosamente', 200, [
                'productos' => $productos,
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
            error_log("Error en ProductoController::list: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    /**
     * Listar todos los productos para admin con paginación Y BÚSQUEDA Y FILTROS
     * GET /api/routes/productos.php?action=list-admin&page=1&limit=10&search=termino&categoria_id=1&activo=1&destacado=1&tiene_imagen=1
     */
    public function listAdmin() {
        try {
            // Verificar que sea admin
            if (!$this->isAdmin()) {
                Response::error('Acceso denegado. Solo administradores', 403);
                return;
            }
            
            // Parámetros de paginación
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            
            // PARÁMETROS DE FILTROS EXISTENTES
            $categoria_id = isset($_GET['categoria_id']) && is_numeric($_GET['categoria_id']) 
                ? intval($_GET['categoria_id']) 
                : null;
                
            $activo = isset($_GET['activo']) && $_GET['activo'] !== '' 
                ? intval($_GET['activo']) 
                : null;

            $destacado = isset($_GET['destacado']) && $_GET['destacado'] !== '' 
                ? intval($_GET['destacado']) 
                : null;
                
            $tiene_imagen = isset($_GET['tiene_imagen']) && $_GET['tiene_imagen'] !== '' 
                ? intval($_GET['tiene_imagen']) 
                : null;

            // Validar parámetros
            if ($page < 1) $page = 1;
            if ($limit < 1 || $limit > 100) $limit = 10;

            $offset = ($page - 1) * $limit;

            // DEBUG: Log extendido para diagnosticar
            error_log("🔍 PRODUCTO ADMIN FILTERS - Parámetros recibidos:");
            error_log("  - search: '" . $search . "'");
            error_log("  - categoria_id: " . ($categoria_id ?? 'null'));
            error_log("  - activo: " . ($activo !== null ? $activo : 'null'));
            error_log("  - destacado: " . ($destacado !== null ? $destacado : 'null'));
            error_log("  - tiene_imagen: " . ($tiene_imagen !== null ? $tiene_imagen : 'null'));
            error_log("  - page: " . $page);
            error_log("  - limit: " . $limit);

            $producto = new Producto();

            //Construir array de filtros con los nuevos filtros
            $filtros = [
                'search' => $search,
                'categoria_id' => $categoria_id,
                'activo' => $activo,
                'destacado' => $destacado,
                'tiene_imagen' => $tiene_imagen
            ];

            // USAR LOS MÉTODOS readAllFiltered y countFiltered
            $productos = $producto->readAllFiltered($limit, $offset, $filtros);
            $total = $producto->countFiltered($filtros);

            error_log("🔍 PRODUCTO ADMIN FILTERS - Resultados encontrados: " . count($productos));
            error_log("🔍 PRODUCTO ADMIN FILTERS - Total: " . $total);
            
            $totalPages = ceil($total / $limit);
            
            Response::success('Productos obtenidos exitosamente', 200, [
                'productos' => $productos,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => $totalPages,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1,
                    'search' => $search
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Error en ProductoController::listAdmin: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Obtener producto por ID
     * GET /api/routes/productos.php?action=get&id=1
     */
    public function get() {
        try {
            // Obtener ID del query string
            $id = $_GET['id'] ?? '';
            
            if (empty($id) || !is_numeric($id)) {
                Response::error('ID de producto requerido y debe ser numérico', 400);
                return;
            }
            
            $producto = new Producto();
            $producto->id = intval($id);
            
            $productoData = $producto->readOne();
            
            if ($productoData) {
                Response::success('Producto obtenido exitosamente', 200, [
                    'producto' => $productoData
                ]);
            } else {
                Response::error('Producto no encontrado', 404);
            }
            
        } catch (Exception $e) {
            error_log("Error en ProductoController::get: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Actualizar producto
     * PUT /api/routes/productos.php?action=update&id=1
     */
    public function update() {
        try {
            // Obtener ID del query string
            $id = $_GET['id'] ?? '';
            
            if (empty($id) || !is_numeric($id)) {
                Response::error('ID de producto requerido y debe ser numérico', 400);
                return;
            }
            
            // Obtener datos JSON
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                Response::error('Datos JSON inválidos', 400);
                return;
            }
            
            $producto = new Producto();
            $producto->id = intval($id);
            
            // Verificar que el producto existe
            if (!$producto->readOne()) {
                Response::error('Producto no encontrado', 404);
                return;
            }
            
            // Asignar nuevos valores (mantener los existentes si no se proporcionan)
            $producto->nombre = $input['nombre'] ?? $producto->nombre;
            $producto->descripcion = $input['descripcion'] ?? $producto->descripcion;
            $producto->categoria_id = isset($input['categoria_id']) ? intval($input['categoria_id']) : $producto->categoria_id;
            $producto->sku = isset($input['sku']) ? $input['sku'] : $producto->sku;
            $producto->activo = isset($input['activo']) ? (bool)$input['activo'] : $producto->activo;
            
            // Manejar precio (puede ser null)
            if (isset($input['precio'])) {
                $producto->precio = !empty($input['precio']) ? floatval($input['precio']) : null;
            }
            
            // Manejar stock
            if (isset($input['stock'])) {
                $producto->stock = intval($input['stock']);
            }
            
            // Validaciones
            if (empty($producto->nombre)) {
                Response::error('El nombre es requerido', 400);
                return;
            }
            
            if ($producto->stock < 0) {
                Response::error('El stock no puede ser negativo', 400);
                return;
            }
            
            if ($producto->precio !== null && $producto->precio < 0) {
                Response::error('El precio no puede ser negativo', 400);
                return;
            }
            
            // Actualizar producto
            if ($producto->update()) {
                // Obtener el producto actualizado completo
                $productoActualizado = $producto->readOne();
                
                Response::success('Producto actualizado exitosamente', 200, [
                    'producto' => $productoActualizado
                ]);
            } else {
                Response::error('Error al actualizar el producto', 500);
            }
            
        } catch (Exception $e) {
            error_log("Error en ProductoController::update: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Eliminar producto (soft delete)
     * DELETE /api/routes/productos.php?action=delete&id=1
     */
    public function delete() {
        try {
            // Obtener ID del query string
            $id = $_GET['id'] ?? '';
            
            if (empty($id) || !is_numeric($id)) {
                Response::error('ID de producto requerido y debe ser numérico', 400);
                return;
            }
            
            $producto = new Producto();
            $producto->id = intval($id);
            
            // Verificar que el producto existe
            if (!$producto->readOne()) {
                Response::error('Producto no encontrado', 404);
                return;
            }
            
            // Eliminar producto (soft delete)
            if ($producto->delete()) {
                Response::success('Producto eliminado exitosamente', 200);
            } else {
                Response::error('Error al eliminar el producto', 500);
            }
            
        } catch (Exception $e) {
            error_log("Error en ProductoController::delete: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Buscar productos
     * GET /api/routes/productos.php?action=search&q=termino&page=1&limit=10
     */
    public function search() {
        try {
            // Obtener parámetros
            $searchTerm = $_GET['q'] ?? '';
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            
            if (empty($searchTerm)) {
                Response::error('Término de búsqueda requerido', 400);
                return;
            }
            
            // Validar limit
            if ($limit < 1 || $limit > 100) $limit = 10;
            
            // ✅ MODIFICADO: Aceptar tanto page como offset
            if (isset($_GET['offset'])) {
                $offset = intval($_GET['offset']);
                if ($offset < 0) $offset = 0;
                $page = floor($offset / $limit) + 1;
            } else {
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                if ($page < 1) $page = 1;
                $offset = ($page - 1) * $limit;
            }
            
            $producto = new Producto();
            $productos = $producto->search($searchTerm, $limit, $offset);
            
            Response::success('Búsqueda realizada exitosamente', 200, [
                'productos' => $productos,
                'search_term' => $searchTerm,
                'total_results' => count($productos),
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Error en ProductoController::search: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Obtener productos por categoría
     * GET /api/routes/productos.php?action=by-category&categoria_id=1&page=1&limit=10
     */
    public function byCategory() {
        try {
            // Obtener parámetros
            $categoriaId = $_GET['categoria_id'] ?? '';
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            
            if (empty($categoriaId) || !is_numeric($categoriaId)) {
                Response::error('ID de categoría requerido y debe ser numérico', 400);
                return;
            }
            
            // Validar limit
            if ($limit < 1 || $limit > 100) $limit = 10;
            
            // ✅ MODIFICADO: Aceptar tanto page como offset
            if (isset($_GET['offset'])) {
                $offset = intval($_GET['offset']);
                if ($offset < 0) $offset = 0;
                $page = floor($offset / $limit) + 1;
            } else {
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                if ($page < 1) $page = 1;
                $offset = ($page - 1) * $limit;
            }
            
            // Verificar que la categoría existe
            $categoria = new Categoria();
            $categoria->id = intval($categoriaId);
            if (!$categoria->readOne()) {
                Response::error('Categoría no encontrada', 404);
                return;
            }
            
            $producto = new Producto();
            $productos = $producto->getByCategory($categoriaId, $limit, $offset);
            
            Response::success('Productos por categoría obtenidos exitosamente', 200, [
                'productos' => $productos,
                'categoria' => [
                    'id' => $categoria->id,
                    'nombre' => $categoria->nombre
                ],
                'total_results' => count($productos),
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Error en ProductoController::byCategory: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Actualizar solo el stock de un producto
     * PATCH /api/routes/productos.php?action=update-stock&id=1
     */
    public function updateStock() {
        try {
            // Obtener ID del query string
            $id = $_GET['id'] ?? '';
            
            if (empty($id) || !is_numeric($id)) {
                Response::error('ID de producto requerido y debe ser numérico', 400);
                return;
            }
            
            // Obtener datos JSON
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['stock'])) {
                Response::error('Stock requerido en el body JSON', 400);
                return;
            }
            
            $newStock = intval($input['stock']);
            
            if ($newStock < 0) {
                Response::error('El stock no puede ser negativo', 400);
                return;
            }
            
            $producto = new Producto();
            $producto->id = intval($id);
            
            // Verificar que el producto existe
            if (!$producto->readOne()) {
                Response::error('Producto no encontrado', 404);
                return;
            }
            
            // Actualizar stock
            if ($producto->updateStock($newStock)) {
                Response::success('Stock actualizado exitosamente', 200, [
                    'producto_id' => $producto->id,
                    'stock_anterior' => $input['stock_anterior'] ?? 'N/A',
                    'stock_nuevo' => $newStock
                ]);
            } else {
                Response::error('Error al actualizar el stock', 500);
            }
            
        } catch (Exception $e) {
            error_log("Error en ProductoController::updateStock: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Cambiar estado activo/inactivo
     * PATCH /api/routes/productos.php?action=toggle&id=1
     */
    public function toggle() {
        try {
            // Obtener ID del query string
            $id = $_GET['id'] ?? '';
            
            if (empty($id) || !is_numeric($id)) {
                Response::error('ID de producto requerido y debe ser numérico', 400);
                return;
            }
            
            $producto = new Producto();
            $producto->id = intval($id);
            
            // Obtener estado actual
            $estadoAnterior = $producto->readOne();
            if (!$estadoAnterior) {
                Response::error('Producto no encontrado', 404);
                return;
            }
            
            // Cambiar estado
            if ($producto->toggleActive()) {
                Response::success('Estado del producto cambiado exitosamente', 200, [
                    'producto_id' => $producto->id,
                    'estado_anterior' => (bool)$estadoAnterior['activo'],
                    'estado_nuevo' => (bool)$producto->activo
                ]);
            } else {
                Response::error('Error al cambiar el estado del producto', 500);
            }
            
        } catch (Exception $e) {
            error_log("Error en ProductoController::toggle: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    /**
     * Obtener productos destacados
     * GET /api/routes/productos.php?action=featured&limit=6
     */
    public function featured() {
        try {
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 6;
            
            // Validar limit
            if ($limit < 1 || $limit > 20) $limit = 6;
            
            $producto = new Producto();
            $productos = $producto->getFeatured($limit);
            
            Response::success('Productos destacados obtenidos exitosamente', 200, [
                'productos' => $productos,
                'total' => count($productos)
            ]);
            
        } catch (Exception $e) {
            error_log("Error en ProductoController::featured: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    /**
     * Cambiar estado destacado
     * PATCH /api/routes/productos.php?action=toggle-featured&id=1
     */
    public function toggleFeatured() {
        try {
            // Verificar que sea admin
            if (!$this->isAdmin()) {
                Response::error('Acceso denegado. Solo administradores', 403);
                return;
            }
            
            $id = $_GET['id'] ?? '';
            
            if (empty($id) || !is_numeric($id)) {
                Response::error('ID de producto requerido y debe ser numérico', 400);
                return;
            }
            
            $producto = new Producto();
            $producto->id = intval($id);
            
            // Obtener estado actual
            $estadoAnterior = $producto->readOne();
            if (!$estadoAnterior) {
                Response::error('Producto no encontrado', 404);
                return;
            }
            
            // Cambiar estado destacado
            if ($producto->toggleFeatured()) {
                Response::success('Estado destacado cambiado exitosamente', 200, [
                    'producto_id' => $producto->id,
                    'destacado_anterior' => (bool)$estadoAnterior['destacado'],
                    'destacado_nuevo' => (bool)$producto->destacado
                ]);
            } else {
                Response::error('Error al cambiar el estado destacado', 500);
            }
            
        } catch (Exception $e) {
            error_log("Error en ProductoController::toggleFeatured: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Obtener estadísticas de productos (Solo Admin)
     * GET /api/routes/productos.php?action=stats
     */
    public function stats() {
        try {
            // Verificar que sea admin
            if (!$this->isAdmin()) {
                Response::error('Acceso denegado. Solo administradores', 403);
                return;
            }
            
            $producto = new Producto();
            $totalActivos = $producto->countTotal(true);
            $totalInactivos = $producto->countTotal(false) - $totalActivos;
            $totalGeneral = $producto->countTotal(false);
            
            // TODO: Agregar más estadísticas (productos sin stock, valor total inventario, etc.)
            
            $stats = [
                'total_productos' => $totalGeneral,
                'productos_activos' => $totalActivos,
                'productos_inactivos' => $totalInactivos,
                'productos_sin_stock' => 0, // TODO: implementar
                'valor_total_inventario' => 0 // TODO: implementar
            ];
            
            Response::success('Estadísticas obtenidas exitosamente', 200, [
                'estadisticas' => $stats
            ]);
            
        } catch (Exception $e) {
            error_log("Error en ProductoController::stats: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
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
                'secure' => true, // Producción HTTPS
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