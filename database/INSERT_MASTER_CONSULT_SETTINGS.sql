-- ============================================
-- Acceso maestro a consulta de códigos (solo administradores)
-- ============================================

USE pocoavbb_gac;

INSERT INTO settings (name, value, type, description)
VALUES
    ('master_consult_enabled', '0', 'string', '1=habilitar acceso maestro en Consulta tu código (solo admin logueado)'),
    ('master_consult_username', '', 'string', 'Usuario/clave que el admin escribe en Consulta tu código para ver el último código de cualquier cuenta')
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    updated_at = NOW();

SELECT name, value, description FROM settings WHERE name IN ('master_consult_enabled', 'master_consult_username');
