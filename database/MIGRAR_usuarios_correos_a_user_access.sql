-- ============================================
-- Migrar usuarios_correos (pocoavbb_codes544shd) → user_access (pocoavbb_gac)
-- Estructura origen: id, email, password, created_at, plataforma (ej. Netflix, Disney)
-- Estructura destino: email, password, platform_id, enabled
-- ============================================
-- Ejecutar en MySQL donde existan ambas bases de datos.
-- ============================================

USE pocoavbb_gac;

-- Insertar en pocoavbb_gac.user_access (NUEVA BD), leyendo desde pocoavbb_codes544shd.usuarios_correos (BD antigua)
-- Mapeo: plataforma (nombre) → platform_id de pocoavbb_gac.platforms
INSERT IGNORE INTO pocoavbb_gac.user_access (email, password, platform_id, enabled)
SELECT
    LOWER(TRIM(uc.email)) AS email,
    TRIM(uc.password) AS password,
    p.id AS platform_id,
    1 AS enabled
FROM pocoavbb_codes544shd.usuarios_correos uc
INNER JOIN pocoavbb_gac.platforms p ON (
    (LOWER(TRIM(uc.plataforma)) = LOWER(p.name))
    OR (LOWER(TRIM(uc.plataforma)) = LOWER(p.display_name))
    OR (LOWER(TRIM(uc.plataforma)) IN ('disney', 'disney+') AND LOWER(p.name) = 'disney')
    OR (LOWER(TRIM(uc.plataforma)) IN ('paramount', 'paramount+') AND LOWER(p.name) = 'paramount')
    OR (LOWER(TRIM(uc.plataforma)) LIKE '%amazon%' AND LOWER(p.name) = 'prime')
    OR (LOWER(TRIM(uc.plataforma)) LIKE '%prime%' AND LOWER(p.name) = 'prime')
)
WHERE p.enabled = 1;

-- Si hay plataformas en usuarios_correos que no coinciden (ej. nombre con typo),
-- se pueden revisar con:
-- SELECT DISTINCT uc.plataforma
-- FROM pocoavbb_codes544shd.usuarios_correos uc
-- LEFT JOIN pocoavbb_gac.platforms p ON LOWER(TRIM(uc.plataforma)) = LOWER(p.name) OR LOWER(TRIM(uc.plataforma)) = LOWER(p.display_name)
-- WHERE p.id IS NULL;

-- Verificar cuántos se insertaron
SELECT COUNT(*) AS total_user_access FROM pocoavbb_gac.user_access;
SELECT COUNT(*) AS total_usuarios_correos FROM pocoavbb_codes544shd.usuarios_correos;
