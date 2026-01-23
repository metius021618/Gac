-- ============================================
-- GAC - Migración: Agregar campos de tracking de consumo
-- ============================================
-- Este script agrega campos para guardar el email y username
-- del usuario que consulta un código
--
-- IMPORTANTE: Ejecuta cada ALTER TABLE por separado.
-- Si un campo ya existe, MySQL mostrará un error pero puedes continuar.

USE pocoavbb_gac;

-- Agregar campo consumed_by_email
-- Si ya existe, verás un error pero puedes continuar con el siguiente
ALTER TABLE codes 
ADD COLUMN consumed_by_email VARCHAR(255) NULL AFTER consumed_by;

-- Agregar campo consumed_by_username
-- Si ya existe, verás un error pero puedes continuar
ALTER TABLE codes 
ADD COLUMN consumed_by_username VARCHAR(255) NULL AFTER consumed_by_email;

-- Agregar índice para búsquedas por email
-- Si ya existe, verás un error pero puedes continuar
ALTER TABLE codes 
ADD INDEX idx_consumed_by_email (consumed_by_email);

-- Verificar que los campos se agregaron correctamente
-- (Este SELECT puede fallar si no tienes permisos, pero los campos ya estarán creados)
SELECT 
    'consumed_by_email' as campo,
    COUNT(*) as existe
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'codes'
  AND COLUMN_NAME = 'consumed_by_email'
UNION ALL
SELECT 
    'consumed_by_username' as campo,
    COUNT(*) as existe
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'codes'
  AND COLUMN_NAME = 'consumed_by_username';
