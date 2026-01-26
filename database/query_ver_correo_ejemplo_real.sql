-- ============================================================
-- EJEMPLO REAL: Ver correo completo como se muestra al usuario
-- ============================================================
-- Basado en los correos que SÍ tienen email_body
-- ============================================================

USE pocoavbb_gac;

-- Ejemplo 1: Ver correo de cineclub017@pocoyoni.com (ID 47)
SELECT 
    c.email_from AS 'De',
    c.subject AS 'Asunto',
    DATE_FORMAT(c.received_at, '%d de %M de %Y a las %H:%i') AS 'Fecha',
    p.display_name AS 'Plataforma',
    c.email_body AS 'Cuerpo Completo del Email'
FROM codes c
INNER JOIN platforms p ON c.platform_id = p.id
WHERE c.id = 47;  -- Este correo tiene email_body

-- ============================================================
-- Ejemplo 2: Ver correo de mariano1ronceros@pocoyoni.com (ID 46)
-- ============================================================
/*
SELECT 
    c.email_from AS 'De',
    c.subject AS 'Asunto',
    DATE_FORMAT(c.received_at, '%d/%m/%Y %H:%i') AS 'Fecha',
    p.display_name AS 'Plataforma',
    c.email_body AS 'Cuerpo Completo'
FROM codes c
INNER JOIN platforms p ON c.platform_id = p.id
WHERE c.id = 46;
*/

-- ============================================================
-- Ejemplo 3: Ver correo de loana1malu@pocoyoni.com (ID 45)
-- ============================================================
/*
SELECT 
    c.email_from AS 'De',
    c.subject AS 'Asunto',
    DATE_FORMAT(c.received_at, '%d/%m/%Y %H:%i') AS 'Fecha',
    p.display_name AS 'Plataforma',
    c.email_body AS 'Cuerpo Completo'
FROM codes c
INNER JOIN platforms p ON c.platform_id = p.id
WHERE c.id = 45;
*/

-- ============================================================
-- Ver TODOS los correos que tienen email_body (para elegir uno)
-- ============================================================
/*
SELECT 
    c.id,
    c.recipient_email AS 'Email Usuario',
    c.email_from AS 'De',
    c.subject AS 'Asunto',
    DATE_FORMAT(c.received_at, '%d/%m/%Y %H:%i') AS 'Fecha',
    p.display_name AS 'Plataforma',
    CONCAT(LENGTH(c.email_body), ' caracteres') AS 'Tamaño'
FROM codes c
INNER JOIN platforms p ON c.platform_id = p.id
WHERE c.email_body IS NOT NULL 
  AND c.email_body != ''
ORDER BY c.received_at DESC;
*/
