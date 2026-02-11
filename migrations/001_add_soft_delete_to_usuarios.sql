-- Migration: Add soft delete to usuarios table
-- Date: 2026-01-11
-- Description: Adds deleted_at column for soft delete functionality

ALTER TABLE usuarios
ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL AFTER updated_at,
ADD INDEX idx_deleted_at (deleted_at);

-- Verify the change
DESCRIBE usuarios;
