-- ============================================
-- GAC - Migración SIMPLE: Agregar campos de tracking
-- ============================================
-- Versión simplificada sin verificación de existencia
-- Ejecuta cada comando por separado en phpMyAdmin

USE pocoavbb_gac;

-- Paso 1: Agregar campo consumed_by_email
ALTER TABLE codes 
ADD COLUMN consumed_by_email VARCHAR(255) NULL AFTER consumed_by;

-- Paso 2: Agregar campo consumed_by_username  
ALTER TABLE codes 
ADD COLUMN consumed_by_username VARCHAR(255) NULL AFTER consumed_by_email;

-- Paso 3: Agregar índice
ALTER TABLE codes 
ADD INDEX idx_consumed_by_email (consumed_by_email);

-- Si algún comando da error porque el campo ya existe, simplemente ignóralo y continúa con el siguiente
