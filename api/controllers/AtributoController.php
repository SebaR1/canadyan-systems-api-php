<?php
/**
 * Controlador Atributo
 * Canadian Sistemas API
 */

require_once __DIR__ . '/../models/Atributo.php';
require_once __DIR__ . '/../models/ProductoAtributo.php';
require_once __DIR__ . '/../utils/Response.php';

class AtributoController {
    
    /**
     * Crear nuevo atributo
     * POST /api/routes/atributos.php?action=create
     */
    public function create() {
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
            if (empty($input['nombre'])) {
                Response::error('El nombre es requerido', 400);
                return;
            }
            
            if (empty($input['tipo'])) {
                Response::error('El tipo es requerido', 400);
                return;
            }
            
            // Crear instancia del modelo
            $atributo = new Atributo();
            
            // Verificar que no exista un atributo con el mismo nombre
            if ($atributo->existsByName($input['nombre'])) {
                Response::error('Ya existe un atributo con ese nombre', 409);
                return;
            }
            
            // Asignar valores
            $atributo->nombre = $input['nombre'];
            $atributo->tipo = $input['tipo'];
            
            // Crear atributo
            if ($atributo->create()) {
                Response::success('Atributo creado exitosamente', 201, [
                    'atributo' => [
                        'id' => $atributo->id,
                        'nombre' => $atributo->nombre,
                        'tipo' => $atributo->tipo
                    ]
                ]);
            } else {
                Response::error('Error al crear el atributo', 500);
            }
            
        } catch (Exception $e) {
            error_log("Error en AtributoController::create: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Listar todos los atributos
     * GET /api/routes/atributos.php?action=list
     */
    public function list() {
        try {
            // Verificar que sea GET
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido', 405);
                return;
            }
            
            $atributo = new Atributo();
            $atributos = $atributo->readAll();
            
            Response::success('Atributos obtenidos exitosamente', 200, [
                'atributos' => $atributos,
                'total' => count($atributos)
            ]);
            
        } catch (Exception $e) {
            error_log("Error en AtributoController::list: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Obtener atributo por ID
     * GET /api/routes/atributos.php?action=get&id=1
     */
    public function get() {
        try {
            // Verificar que sea GET
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido', 405);
                return;
            }
            
            // Obtener ID del query string
            $id = $_GET['id'] ?? '';
            
            if (empty($id) || !is_numeric($id)) {
                Response::error('ID de atributo requerido y debe ser numérico', 400);
                return;
            }
            
            $atributo = new Atributo();
            $atributo->id = intval($id);
            
            $atributoData = $atributo->readOne();
            
            if ($atributoData) {
                // Obtener valores únicos del atributo
                $valoresUnicos = $atributo->getValoresUnicos();
                
                $atributoData['valores_unicos'] = $valoresUnicos;
                
                Response::success('Atributo obtenido exitosamente', 200, [
                    'atributo' => $atributoData
                ]);
            } else {
                Response::error('Atributo no encontrado', 404);
            }
            
        } catch (Exception $e) {
            error_log("Error en AtributoController::get: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Actualizar atributo
     * PUT /api/routes/atributos.php?action=update&id=1
     */
    public function update() {
        try {
            // Verificar que sea PUT
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                Response::error('Método no permitido', 405);
                return;
            }
            
            // Obtener ID del query string
            $id = $_GET['id'] ?? '';
            
            if (empty($id) || !is_numeric($id)) {
                Response::error('ID de atributo requerido y debe ser numérico', 400);
                return;
            }
            
            // Obtener datos JSON
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                Response::error('Datos JSON inválidos', 400);
                return;
            }
            
            $atributo = new Atributo();
            $atributo->id = intval($id);
            
            // Verificar que el atributo existe
            if (!$atributo->readOne()) {
                Response::error('Atributo no encontrado', 404);
                return;
            }
            
            // Asignar nuevos valores
            $atributo->nombre = $input['nombre'] ?? $atributo->nombre;
            $atributo->tipo = $input['tipo'] ?? $atributo->tipo;
            
            // Validar campos requeridos
            if (empty($atributo->nombre)) {
                Response::error('El nombre es requerido', 400);
                return;
            }
            
            if (empty($atributo->tipo)) {
                Response::error('El tipo es requerido', 400);
                return;
            }
            
            // Actualizar atributo
            if ($atributo->update()) {
                $atributoActualizado = $atributo->readOne();
                
                Response::success('Atributo actualizado exitosamente', 200, [
                    'atributo' => $atributoActualizado
                ]);
            } else {
                Response::error('Error al actualizar el atributo', 500);
            }
            
        } catch (Exception $e) {
            error_log("Error en AtributoController::update: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Eliminar atributo
     * DELETE /api/routes/atributos.php?action=delete&id=1
     */
    public function delete() {
        try {
            // Verificar que sea DELETE
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                Response::error('Método no permitido', 405);
                return;
            }
            
            // Obtener ID del query string
            $id = $_GET['id'] ?? '';
            
            if (empty($id) || !is_numeric($id)) {
                Response::error('ID de atributo requerido y debe ser numérico', 400);
                return;
            }
            
            $atributo = new Atributo();
            $atributo->id = intval($id);
            
            // Verificar que el atributo existe
            if (!$atributo->readOne()) {
                Response::error('Atributo no encontrado', 404);
                return;
            }
            
            // Eliminar atributo
            if ($atributo->delete()) {
                Response::success('Atributo eliminado exitosamente', 200);
            } else {
                Response::error('No se puede eliminar el atributo. Verifique que no tenga productos asociados', 409);
            }
            
        } catch (Exception $e) {
            error_log("Error en AtributoController::delete: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Obtener atributos con valores para filtros del catálogo
     * GET /api/routes/atributos.php?action=filters&categoria_id=1 (opcional)
     */
    public function getFilters() {
        try {
            // Verificar que sea GET
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido', 405);
                return;
            }
            
            // Obtener categoria_id opcional del query string
            $categoria_id = isset($_GET['categoria_id']) && is_numeric($_GET['categoria_id']) ? intval($_GET['categoria_id']) : null;
            
            $atributo = new Atributo();
            $filtros = $atributo->getAtributosConValores($categoria_id);
            
            Response::success('Filtros obtenidos exitosamente', 200, [
                'filtros' => $filtros,
                'categoria_id' => $categoria_id
            ]);
            
        } catch (Exception $e) {
            error_log("Error en AtributoController::getFilters: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Asignar atributos a un producto
     * POST /api/routes/atributos.php?action=assign-product
     */
    public function assignToProduct() {
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
            if (empty($input['producto_id']) || !is_numeric($input['producto_id'])) {
                Response::error('ID de producto requerido y debe ser numérico', 400);
                return;
            }
            
            if (empty($input['atributos']) || !is_array($input['atributos'])) {
                Response::error('Array de atributos requerido', 400);
                return;
            }
            
            $productoAtributo = new ProductoAtributo();
            
            // Guardar atributos del producto
            if ($productoAtributo->saveProductoAtributos($input['producto_id'], $input['atributos'])) {
                Response::success('Atributos asignados exitosamente', 200, [
                    'producto_id' => $input['producto_id'],
                    'atributos_count' => count($input['atributos'])
                ]);
            } else {
                Response::error('Error al asignar atributos al producto', 500);
            }
            
        } catch (Exception $e) {
            error_log("Error en AtributoController::assignToProduct: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Obtener atributos de un producto
     * GET /api/routes/atributos.php?action=by-product&producto_id=1
     */
    public function getByProduct() {
        try {
            // Verificar que sea GET
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido', 405);
                return;
            }
            
            // Obtener producto_id del query string
            $producto_id = $_GET['producto_id'] ?? '';
            
            if (empty($producto_id) || !is_numeric($producto_id)) {
                Response::error('ID de producto requerido y debe ser numérico', 400);
                return;
            }
            
            $productoAtributo = new ProductoAtributo();
            $atributos = $productoAtributo->getByProducto(intval($producto_id));
            
            Response::success('Atributos del producto obtenidos exitosamente', 200, [
                'producto_id' => intval($producto_id),
                'atributos' => $atributos
            ]);
            
        } catch (Exception $e) {
            error_log("Error en AtributoController::getByProduct: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Filtrar productos por atributos
     * POST /api/routes/atributos.php?action=filter-products
     */
    public function filterProducts() {
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
            
            // Obtener parámetros
            $filtros = $input['filtros'] ?? [];
            $page = isset($input['page']) ? intval($input['page']) : 1;
            $limit = isset($input['limit']) ? intval($input['limit']) : 10;
            $categoria_id = isset($input['categoria_id']) ? intval($input['categoria_id']) : null; // ← NUEVO PARÁMETRO
            
            // Validar parámetros
            if ($page < 1) $page = 1;
            if ($limit < 1 || $limit > 100) $limit = 10;
            
            $offset = ($page - 1) * $limit;
            
            $productoAtributo = new ProductoAtributo();
            $productos = $productoAtributo->filtrarProductosPorAtributos($filtros, $limit, $offset, $categoria_id); // ← PASAR CATEGORÍA
            
            Response::success('Productos filtrados exitosamente', 200, [
                'productos' => $productos,
                'filtros_aplicados' => $filtros,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total_results' => count($productos)
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Error en AtributoController::filterProducts: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    /**
     * Obtener filtros dinámicos basados en filtros ya aplicados
     * POST /api/routes/atributos.php?action=dynamic-filters
     */
    public function getDynamicFilters() {
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
            
            // Obtener parámetros
            $categoria_id = isset($input['categoria_id']) ? intval($input['categoria_id']) : null;
            $filtros_aplicados = $input['filtros_aplicados'] ?? [];
            
            $atributo = new Atributo();
            $filtros = $atributo->getAtributosDinamicos($categoria_id, $filtros_aplicados);
            
            Response::success('Filtros dinámicos obtenidos exitosamente', 200, [
                'filtros' => $filtros,
                'categoria_id' => $categoria_id,
                'filtros_aplicados' => $filtros_aplicados
            ]);
            
        } catch (Exception $e) {
            error_log("Error en AtributoController::getDynamicFilters: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
}

