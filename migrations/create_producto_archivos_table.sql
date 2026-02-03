-- Migration: Create producto_archivos table
-- Date: 2026-02-02
-- Description: Creates table for storing downloadable files associated with products (PDFs, documents, images)

CREATE TABLE IF NOT EXISTS producto_archivos (
  id INT PRIMARY KEY AUTO_INCREMENT,
  producto_id INT NOT NULL,
  nombre_original VARCHAR(255) NOT NULL COMMENT 'Nombre original del archivo subido',
  nombre_personalizado VARCHAR(255) DEFAULT NULL COMMENT 'Nombre personalizado visible al usuario (opcional)',
  url VARCHAR(500) NOT NULL COMMENT 'Ruta relativa: uploads/productos/archivos/...',
  tipo_archivo VARCHAR(50) NOT NULL COMMENT 'Extensión del archivo: pdf, docx, xlsx, jpg, png, etc.',
  tamanio_bytes INT NOT NULL COMMENT 'Tamaño del archivo en bytes',
  orden INT DEFAULT 0 COMMENT 'Orden para drag & drop',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
  INDEX idx_producto_orden (producto_id, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verify the change
DESCRIBE producto_archivos;
