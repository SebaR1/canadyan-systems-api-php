<?php
/**
 * Controlador Categoria
 * Canadian Sistemas API
 */

require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../utils/Response.php';

class CategoriaController {
    
    /**
     * Crear nueva categoría
     * POST /api/routes/categorias.php?action=create
     */
    public function create() {
        try {
            // Verificar que sea admin
            if (!$this->isAdmin()) {
                Response::error('Acceso denegado. Se requieren permisos de administrador.', 403);
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
            
            // Crear instancia del modelo
            $categoria = new Categoria();
            
            // Asignar valores
            $categoria->nombre = $input['nombre'];
            $categoria->parent_id = $input['parent_id'] ?? null;
            
            // Validar parent_id si se proporciona
            if ($categoria->parent_id) {
                $parentCategoria = new Categoria();
                $parentCategoria->id = $categoria->parent_id;
                if (!$parentCategoria->readOne()) {
                    Response::error('La categoría padre no existe', 400);
                    return;
                }
            }
            
            // Crear categoría
            if ($categoria->create()) {
                Response::success('Categoría creada exitosamente', 201, [
                    'categoria' => [
                        'id' => $categoria->id,
                        'nombre' => $categoria->nombre,
                        'slug' => $categoria->slug,
                        'parent_id' => $categoria->parent_id
                    ]
                ]);
            } else {
                Response::error('Error al crear la categoría', 500);
            }
            
        } catch (Exception $e) {
            error_log("Error en CategoriaController::create: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Listar todas las categorías (lista plana)
     * GET /api/routes/categorias.php?action=list
     */
    public function list() {
        try {
            $categoria = new Categoria();
            $categorias = $categoria->readAll();
            
            Response::success('Categorías obtenidas exitosamente', 200, [
                'categorias' => $categorias,
                'total' => count($categorias)
            ]);
            
        } catch (Exception $e) {
            error_log("Error en CategoriaController::list: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Obtener árbol de categorías
     * GET /api/routes/categorias.php?action=tree
     */
    public function tree() {
        try {
            $categoria = new Categoria();
            $tree = $categoria->getTree();
            
            Response::success('Árbol de categorías obtenido exitosamente', 200, [
                'tree' => $tree
            ]);
            
        } catch (Exception $e) {
            error_log("Error en CategoriaController::tree: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Obtener categoría por ID
     * GET /api/routes/categorias.php?action=get&id=1
     */
    public function get() {
        try {
            // Obtener ID del query string
            $id = $_GET['id'] ?? '';
            
            if (empty($id) || !is_numeric($id)) {
                Response::error('ID de categoría requerido y debe ser numérico', 400);
                return;
            }
            
            $categoria = new Categoria();
            $categoria->id = intval($id);
            
            if ($categoria->readOne()) {
                // Obtener hijos si los tiene
                $hijos = $categoria->getChildren();
                
                Response::success('Categoría obtenida exitosamente', 200, [
                    'categoria' => [
                        'id' => $categoria->id,
                        'nombre' => $categoria->nombre,
                        'slug' => $categoria->slug,
                        'parent_id' => $categoria->parent_id,
                        'hijos' => $hijos,
                        'tiene_hijos' => count($hijos) > 0
                    ]
                ]);
            } else {
                Response::error('Categoría no encontrada', 404);
            }
            
        } catch (Exception $e) {
            error_log("Error en CategoriaController::get: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Actualizar categoría
     * PUT /api/routes/categorias.php?action=update&id=1
     */
    public function update() {
        try {
            // Verificar que sea admin
            if (!$this->isAdmin()) {
                Response::error('Acceso denegado. Se requieren permisos de administrador.', 403);
                return;
            }

            // Obtener ID del query string
            $id = $_GET['id'] ?? '';
            
            if (empty($id) || !is_numeric($id)) {
                Response::error('ID de categoría requerido y debe ser numérico', 400);
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
            
            $categoria = new Categoria();
            $categoria->id = intval($id);
            
            // Verificar que la categoría existe
            if (!$categoria->readOne()) {
                Response::error('Categoría no encontrada', 404);
                return;
            }
            
            // Asignar nuevos valores
            $categoria->nombre = $input['nombre'];
            $categoria->parent_id = $input['parent_id'] ?? null;
            
            // Validar que no se esté intentando hacer padre de sí misma
            if ($categoria->parent_id == $categoria->id) {
                Response::error('Una categoría no puede ser padre de sí misma', 400);
                return;
            }
            
            // Validar parent_id si se proporciona
            if ($categoria->parent_id) {
                $parentCategoria = new Categoria();
                $parentCategoria->id = $categoria->parent_id;
                if (!$parentCategoria->readOne()) {
                    Response::error('La categoría padre no existe', 400);
                    return;
                }
            }
            
            // Actualizar categoría
            if ($categoria->update()) {
                Response::success('Categoría actualizada exitosamente', 200, [
                    'categoria' => [
                        'id' => $categoria->id,
                        'nombre' => $categoria->nombre,
                        'slug' => $categoria->slug,
                        'parent_id' => $categoria->parent_id
                    ]
                ]);
            } else {
                Response::error('Error al actualizar la categoría', 500);
            }
            
        } catch (Exception $e) {
            error_log("Error en CategoriaController::update: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Eliminar categoría
     * DELETE /api/routes/categorias.php?action=delete&id=1
     */
    public function delete() {
        try {
            // Verificar que sea admin
            if (!$this->isAdmin()) {
                Response::error('Acceso denegado. Se requieren permisos de administrador.', 403);
                return;
            }

            // Obtener ID del query string
            $id = $_GET['id'] ?? '';
            
            if (empty($id) || !is_numeric($id)) {
                Response::error('ID de categoría requerido y debe ser numérico', 400);
                return;
            }
            
            $categoria = new Categoria();
            $categoria->id = intval($id);
            
            // Verificar que la categoría existe
            if (!$categoria->readOne()) {
                Response::error('Categoría no encontrada', 404);
                return;
            }
            
            // Intentar eliminar
            if ($categoria->delete()) {
                Response::success('Categoría eliminada exitosamente', 200);
            } else {
                Response::error('No se puede eliminar la categoría. Verifique que no tenga productos asociados o subcategorías', 409);
            }
            
        } catch (Exception $e) {
            error_log("Error en CategoriaController::delete: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Obtener subcategorías de una categoría
     * GET /api/routes/categorias.php?action=children&id=1
     */
    public function children() {
        try {
            // Obtener ID del query string
            $id = $_GET['id'] ?? '';
            
            if (empty($id) || !is_numeric($id)) {
                Response::error('ID de categoría requerido y debe ser numérico', 400);
                return;
            }
            
            $categoria = new Categoria();
            $categoria->id = intval($id);
            
            // Verificar que la categoría existe
            if (!$categoria->readOne()) {
                Response::error('Categoría no encontrada', 404);
                return;
            }
            
            // Obtener hijos
            $hijos = $categoria->getChildren();
            
            Response::success('Subcategorías obtenidas exitosamente', 200, [
                'categoria_padre' => [
                    'id' => $categoria->id,
                    'nombre' => $categoria->nombre
                ],
                'subcategorias' => $hijos,
                'total' => count($hijos)
            ]);
            
        } catch (Exception $e) {
            error_log("Error en CategoriaController::children: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Iniciar sesión con configuración CORS
     */
    private function startSessionWithCORS() {
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
    }

    /**
     * Verificar que el usuario sea admin
     */
    private function isAdmin() {
        $this->startSessionWithCORS();
        $user_type = $_SESSION['user_type'] ?? null;
        return $user_type == 2; // Tipo 2 = Admin
    }
    
    /**
     * Obtener estadísticas de categorías (Solo Admin)
     * GET /api/routes/categorias.php?action=stats
     */
    public function stats() {
        try {
            // Verificar que sea admin
            if (!$this->isAdmin()) {
                Response::error('Acceso denegado. Solo administradores', 403);
                return;
            }
            
            // TODO: Implementar estadísticas más detalladas
            $categoria = new Categoria();
            $todasCategorias = $categoria->readAll();
            
            $stats = [
                'total_categorias' => count($todasCategorias),
                'categorias_padre' => 0,
                'categorias_hija' => 0
            ];
            
            foreach ($todasCategorias as $cat) {
                if ($cat['parent_id'] === null) {
                    $stats['categorias_padre']++;
                } else {
                    $stats['categorias_hija']++;
                }
            }
            
            Response::success('Estadísticas obtenidas exitosamente', 200, [
                'estadisticas' => $stats
            ]);
            
        } catch (Exception $e) {
            error_log("Error en CategoriaController::stats: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
}
?>