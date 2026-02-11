-- Migration: Add foreign keys with ON DELETE CASCADE to producto_imagenes and producto_atributos
-- Date: 2026-02-09
-- Description: Estas tablas no tenían FK definida. Se agrega para mantener integridad referencial
--              y permitir eliminación en cascada al borrar un producto.
--              Antes de agregar las FK, se limpian registros huérfanos que apunten a productos inexistentes.

-- 1. Limpiar registros huérfanos en producto_imagenes (si los hay)
DELETE FROM producto_imagenes
WHERE producto_id NOT IN (SELECT id FROM productos);

-- 2. Limpiar registros huérfanos en producto_atributos (si los hay)
DELETE FROM producto_atributos
WHERE producto_id NOT IN (SELECT id FROM productos);

-- 3. Agregar FK a producto_imagenes
ALTER TABLE producto_imagenes
  ADD CONSTRAINT fk_producto_imagenes_producto
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE;

-- 4. Agregar FK a producto_atributos (para producto_id)
ALTER TABLE producto_atributos
  ADD CONSTRAINT fk_producto_atributos_producto
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE;

-- 5. Agregar FK de atributo_id en producto_atributos (también faltaba)
DELETE FROM producto_atributos
WHERE atributo_id NOT IN (SELECT id FROM atributos);

ALTER TABLE producto_atributos
  ADD CONSTRAINT fk_producto_atributos_atributo
  FOREIGN KEY (atributo_id) REFERENCES atributos(id) ON DELETE CASCADE;

-- Verify
SHOW CREATE TABLE producto_imagenes;
SHOW CREATE TABLE producto_atributos;
