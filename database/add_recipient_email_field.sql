-- ============================================
-- AGREGAR CAMPO recipient_email A TABLA codes
-- ============================================
-- Este campo guarda el email del destinatario del correo
-- para poder filtrar códigos por usuario

USE pocoavbb_gac;

-- Agregar campo recipient_email
ALTER TABLE codes 
ADD COLUMN recipient_email VARCHAR(255) NULL AFTER consumed_by_username;

-- Agregar índice para búsquedas rápidas
ALTER TABLE codes 
ADD INDEX idx_recipient_email (recipient_email);

-- Agregar índice compuesto para búsquedas por plataforma y destinatario
ALTER TABLE codes 
ADD INDEX idx_platform_recipient (platform_id, recipient_email, status);
