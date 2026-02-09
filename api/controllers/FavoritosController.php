<?php
/**
 * Controlador Favoritos
 * Canadian Sistemas API
 */

require_once __DIR__ . '/../models/UsuarioFavorito.php';
require_once __DIR__ . '/../utils/Response.php';

class FavoritosController {

    private function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    private function requireAuth() {
        if (!$this->getUserId()) {
            Response::error('Se requiere autenticación', 401);
            return false;
        }
        return true;
    }

    /**
     * Agregar producto a favoritos
     * POST /api/routes/favoritos.php?action=add
     * Body: { "producto_id": 5 }
     */
    public function add() {
        if (!$this->requireAuth()) return;

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['producto_id']) || !is_numeric($input['producto_id'])) {
                Response::error('producto_id es requerido y debe ser numérico', 400);
                return;
            }

            $productoId = intval($input['producto_id']);
            $usuarioId  = $this->getUserId();

            // Verificar que el producto existe y está activo
            $queryProducto = "SELECT id FROM productos WHERE id = :id AND activo = 1";
            $database = new \Database();
            $conn = $database->getConnection();
            $stmt = $conn->prepare($queryProducto);
            $stmt->bindParam(':id', $productoId, PDO::PARAM_INT);
            $stmt->execute();
            if (!$stmt->fetch()) {
                Response::error('Producto no encontrado', 404);
                return;
            }

            $model = new UsuarioFavorito();
            $model->add($usuarioId, $productoId);

            Response::success('Producto agregado a favoritos', 200, [
                'producto_id' => $productoId
            ]);

        } catch (Exception $e) {
            error_log("Error en FavoritosController::add: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    /**
     * Eliminar producto de favoritos
     * DELETE /api/routes/favoritos.php?action=remove&producto_id=5
     */
    public function remove() {
        if (!$this->requireAuth()) return;

        try {
            $productoId = isset($_GET['producto_id']) && is_numeric($_GET['producto_id'])
                ? intval($_GET['producto_id'])
                : null;

            if ($productoId === null) {
                Response::error('producto_id es requerido', 400);
                return;
            }

            $model = new UsuarioFavorito();
            $model->remove($this->getUserId(), $productoId);

            Response::success('Producto eliminado de favoritos', 200);

        } catch (Exception $e) {
            error_log("Error en FavoritosController::remove: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    /**
     * Listar todos los favoritos del usuario con datos completos del producto
     * GET /api/routes/favoritos.php?action=list
     */
    public function list() {
        if (!$this->requireAuth()) return;

        try {
            $model = new UsuarioFavorito();
            $productos = $model->getByUsuario($this->getUserId());

            // Procesar tipos
            $productos = array_map(function($p) {
                if (isset($p['precio']))    $p['precio']    = $p['precio'] !== null ? floatval($p['precio']) : null;
                if (isset($p['stock']))     $p['stock']     = intval($p['stock']);
                if (isset($p['id']))        $p['id']        = intval($p['id']);
                if (isset($p['destacado'])) $p['destacado'] = intval($p['destacado']);
                if (isset($p['activo']))    $p['activo']    = intval($p['activo']);
                return $p;
            }, $productos);

            Response::success('Favoritos obtenidos exitosamente', 200, [
                'productos' => $productos,
                'total' => count($productos)
            ]);

        } catch (Exception $e) {
            error_log("Error en FavoritosController::list: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    /**
     * Listar solo los IDs de productos favoritos (liviano para Catalogo)
     * GET /api/routes/favoritos.php?action=list-ids
     */
    public function listIds() {
        if (!$this->requireAuth()) return;

        try {
            $model = new UsuarioFavorito();
            $ids = $model->getIdsByUsuario($this->getUserId());

            Response::success('IDs de favoritos obtenidos', 200, [
                'ids' => $ids
            ]);

        } catch (Exception $e) {
            error_log("Error en FavoritosController::listIds: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }

    /**
     * Verificar si un producto es favorito del usuario actual
     * GET /api/routes/favoritos.php?action=check&producto_id=5
     */
    public function check() {
        if (!$this->requireAuth()) return;

        try {
            $productoId = isset($_GET['producto_id']) && is_numeric($_GET['producto_id'])
                ? intval($_GET['producto_id'])
                : null;

            if ($productoId === null) {
                Response::error('producto_id es requerido', 400);
                return;
            }

            $model = new UsuarioFavorito();
            $esFavorito = $model->isFavorito($this->getUserId(), $productoId);

            Response::success('Verificación completada', 200, [
                'es_favorito' => $esFavorito,
                'producto_id' => $productoId
            ]);

        } catch (Exception $e) {
            error_log("Error en FavoritosController::check: " . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
}
?>
