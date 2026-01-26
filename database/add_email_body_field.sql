-- ============================================
-- AGREGAR CAMPO email_body A TABLA codes
-- ============================================
-- Este campo guarda el cuerpo completo del email (HTML o texto)
-- para poder mostrarlo al usuario en un modal

USE pocoavbb_gac;

-- Agregar campo email_body
ALTER TABLE codes 
ADD COLUMN email_body TEXT NULL AFTER subject;

-- Para la base de datos local también
USE gac_local;

ALTER TABLE codes 
ADD COLUMN email_body TEXT NULL AFTER subject;
