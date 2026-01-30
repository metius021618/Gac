-- ============================================================
-- GAC - Dar acceso de prueba para la consulta de códigos
-- ============================================================
-- Ejecuta este SQL en tu BD para que el correo pueda usar
-- la página "Consulta tu Código" sin el mensaje de acceso denegado.
-- ============================================================

USE pocoavbb_gac;

-- Opción 1: cineclub017 (correo de prueba)
-- En la consulta usa: Email = cineclub017@pocoyoni.com, Usuario = cineclub017

INSERT INTO user_access (email, password, platform_id, enabled)
SELECT 
    'cineclub017@pocoyoni.com',
    'cineclub017',
    p.id,
    1
FROM platforms p
WHERE p.name = 'netflix' AND p.enabled = 1
LIMIT 1
ON DUPLICATE KEY UPDATE 
    password = VALUES(password),
    enabled = 1,
    updated_at = NOW();

-- Opción 2: Si prefieres otro correo/usuario, descomenta y cambia:
/*
INSERT INTO user_access (email, password, platform_id, enabled)
SELECT 
    'TU_CORREO@pocoyoni.com',   -- ⬅️ tu correo
    'TU_USUARIO',               -- ⬅️ clave de acceso (lo que pones en "Usuario" en la consulta)
    p.id,
    1
FROM platforms p
WHERE p.name = 'netflix' AND p.enabled = 1
LIMIT 1
ON DUPLICATE KEY UPDATE 
    password = VALUES(password),
    enabled = 1,
    updated_at = NOW();
*/

-- Dar acceso para varias plataformas al mismo correo (opcional)
INSERT INTO user_access (email, password, platform_id, enabled)
SELECT 
    'cineclub017@pocoyoni.com',
    'cineclub017',
    p.id,
    1
FROM platforms p
WHERE p.name IN ('netflix', 'disney', 'prime', 'spotify', 'crunchyroll', 'paramount', 'chatgpt')
  AND p.enabled = 1
ON DUPLICATE KEY UPDATE 
    password = VALUES(password),
    enabled = 1,
    updated_at = NOW();

-- Ver qué accesos quedaron
SELECT ua.id, ua.email, ua.password AS usuario_acceso, p.display_name AS plataforma, ua.enabled
FROM user_access ua
JOIN platforms p ON p.id = ua.platform_id
WHERE ua.email = 'cineclub017@pocoyoni.com'
ORDER BY p.display_name;
