-- ============================================
-- Insertar configuración de tiempo de sesión
-- Adaptado a la estructura existente de la tabla settings
-- ============================================

USE pocoavbb_gac;

-- Insertar o actualizar configuración de tiempo de sesión
INSERT INTO settings (name, value, type, description) 
VALUES (
    'session_timeout_hours', 
    '1', 
    'string',
    'Tiempo en horas que se mantiene activa la sesión del usuario'
)
ON DUPLICATE KEY UPDATE 
    value = '1',
    description = 'Tiempo en horas que se mantiene activa la sesión del usuario',
    updated_at = NOW();

-- Verificar inserción
SELECT * FROM settings WHERE name = 'session_timeout_hours';
