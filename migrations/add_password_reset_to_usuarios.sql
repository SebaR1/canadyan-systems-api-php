-- Migration: Add password reset columns to usuarios table
-- Date: 2026-02-04
-- Description: Adds token and expiration columns for forgot-password flow

ALTER TABLE usuarios
ADD COLUMN password_reset_token VARCHAR(255) NULL DEFAULT NULL AFTER password,
ADD COLUMN password_reset_expires_at DATETIME NULL DEFAULT NULL AFTER password_reset_token;

-- Verify the change
DESCRIBE usuarios;
