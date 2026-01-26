-- ============================================================
-- QUERY FLEXIBLE: Buscar correo sin importar el campo
-- ============================================================
-- Esta query busca correos de diferentes formas
-- ============================================================

USE pocoavbb_gac;

-- ============================================================
-- OPCIÓN 1: Buscar por cualquier parte del email del usuario
-- ============================================================
SELECT 
    c.id,
    c.code,
    c.email_from AS 'De',
    c.subject AS 'Asunto',
    DATE_FORMAT(c.received_at, '%d/%m/%Y %H:%i') AS 'Fecha',
    c.recipient_email AS 'Email Usuario',
    p.display_name AS 'Plataforma',
    CASE 
        WHEN c.email_body IS NULL OR c.email_body = '' 
            THEN 'Sin cuerpo' 
        ELSE CONCAT(LENGTH(c.email_body), ' caracteres') 
    END AS 'Cuerpo',
    c.email_body AS 'Cuerpo Completo'
FROM codes c
INNER JOIN platforms p ON c.platform_id = p.id
WHERE c.recipient_email LIKE '%caballero%'  -- ⬅️ Cambia esto por parte del email
   OR c.recipient_email LIKE '%jairo%'      -- O busca por nombre
ORDER BY c.received_at DESC
LIMIT 10;

-- ============================================================
-- OPCIÓN 2: Ver el correo más reciente que tenga email_body
-- (sin importar el usuario)
-- ============================================================
SELECT 
    c.id,
    c.code,
    c.email_from AS 'De',
    c.subject AS 'Asunto',
    DATE_FORMAT(c.received_at, '%d/%m/%Y %H:%i') AS 'Fecha',
    c.recipient_email AS 'Email Usuario',
    p.display_name AS 'Plataforma',
    c.email_body AS 'Cuerpo Completo del Email'
FROM codes c
INNER JOIN platforms p ON c.platform_id = p.id
WHERE c.email_body IS NOT NULL 
  AND c.email_body != ''
  AND LENGTH(c.email_body) > 50  -- Al menos 50 caracteres
ORDER BY c.received_at DESC
LIMIT 1;

-- ============================================================
-- OPCIÓN 3: Buscar por código específico
-- ============================================================
/*
SELECT 
    c.id,
    c.code,
    c.email_from AS 'De',
    c.subject AS 'Asunto',
    DATE_FORMAT(c.received_at, '%d/%m/%Y %H:%i') AS 'Fecha',
    c.recipient_email AS 'Email Usuario',
    p.display_name AS 'Plataforma',
    c.email_body AS 'Cuerpo Completo'
FROM codes c
INNER JOIN platforms p ON c.platform_id = p.id
WHERE c.code = '707070'  -- ⬅️ Cambia por el código que buscas
ORDER BY c.received_at DESC
LIMIT 1;
*/
