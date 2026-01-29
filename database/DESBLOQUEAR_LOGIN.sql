-- ============================================
-- Desbloquear intentos de login
-- ============================================
-- Los intentos de login se guardan en la tabla de sesiones de PHP
-- Esta consulta limpia las sesiones que contienen intentos fallidos

USE pocoavbb_gac;

-- Si usas almacenamiento de sesiones en base de datos (tabla sessions)
-- Eliminar sesiones con intentos de login bloqueados
DELETE FROM sessions 
WHERE payload LIKE '%login_attempts_%' 
   OR payload LIKE '%lockout_until%';

-- Verificar sesiones restantes
SELECT COUNT(*) as sesiones_restantes FROM sessions;

-- NOTA: Si las sesiones se guardan en archivos (por defecto en PHP),
-- necesitarás limpiar los archivos de sesión manualmente o usar el script PHP
-- que se encuentra en: public/clear_login_attempts.php
