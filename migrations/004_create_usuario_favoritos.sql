-- Migration: Create usuario_favoritos table
-- Date: 2026-02-04
-- Description: Tabla de favoritos por usuario. UNIQUE en (usuario_id, producto_id) evita duplicados.

CREATE TABLE IF NOT EXISTS usuario_favoritos (
  id INT PRIMARY KEY AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  producto_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
  UNIQUE INDEX idx_usuario_producto (usuario_id, producto_id),
  INDEX idx_usuario (usuario_id),
  INDEX idx_producto (producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verify the change
DESCRIBE usuario_favoritos;
