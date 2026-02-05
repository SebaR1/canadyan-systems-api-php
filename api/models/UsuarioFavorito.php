<?php
/**
 * Modelo UsuarioFavorito
 * Canadian Sistemas API
 */

require_once __DIR__ . '/../config/database.php';

class UsuarioFavorito {
    private $conn;
    private $table_name = "usuario_favoritos";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Agregar producto a favoritos del usuario.
     * INSERT IGNORE maneja silenciosamente el caso de duplicado (UNIQUE constraint).
     */
    public function add($usuarioId, $productoId) {
        $query = "INSERT IGNORE INTO " . $this->table_name . "
                  (usuario_id, producto_id) VALUES (:usuario_id, :producto_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindParam(':producto_id', $productoId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Remover producto de favoritos del usuario
     */
    public function remove($usuarioId, $productoId) {
        $query = "DELETE FROM " . $this->table_name . "
                  WHERE usuario_id = :usuario_id AND producto_id = :producto_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindParam(':producto_id', $productoId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Verificar si un producto es favorito del usuario
     */
    public function isFavorito($usuarioId, $productoId) {
        $query = "SELECT COUNT(*) as cnt FROM " . $this->table_name . "
                  WHERE usuario_id = :usuario_id AND producto_id = :producto_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindParam(':producto_id', $productoId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['cnt'] > 0;
    }

    /**
     * Obtener todos los favoritos de un usuario con datos completos del producto
     */
    public function getByUsuario($usuarioId) {
        $query = "SELECT p.id, p.nombre, p.descripcion, p.precio, p.stock,
                         p.categoria_id, p.sku, p.activo, p.destacado, p.created_at, p.updated_at,
                         c.nombre as categoria_nombre,
                         pi.url as imagen_principal_url,
                         uf.created_at as favorito_desde
                  FROM " . $this->table_name . " uf
                  INNER JOIN productos p ON uf.producto_id = p.id AND p.activo = 1
                  LEFT JOIN categorias c ON p.categoria_id = c.id
                  LEFT JOIN (
                      SELECT producto_id, url
                      FROM producto_imagenes
                      WHERE tipo = 'principal'
                          OR id IN (
                              SELECT MIN(id)
                              FROM producto_imagenes
                              GROUP BY producto_id
                          )
                      GROUP BY producto_id
                  ) pi ON p.id = pi.producto_id
                  WHERE uf.usuario_id = :usuario_id
                  ORDER BY uf.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener solo los IDs de productos favoritos del usuario (liviano, para Catalogo)
     */
    public function getIdsByUsuario($usuarioId) {
        $query = "SELECT producto_id FROM " . $this->table_name . "
                  WHERE usuario_id = :usuario_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        return array_map('intval', $rows);
    }
}
?>
