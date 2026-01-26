-- ============================================================
-- QUERY: Ver correo completo como se muestra en el MODAL
-- ============================================================
-- Esta query muestra exactamente lo que vería el usuario
-- ============================================================

USE pocoavbb_gac;

-- ============================================================
-- Ver correo completo de un usuario específico CON email_body
-- ============================================================
SELECT 
    -- Información que aparece en el MODAL
    c.email_from AS 'De (Remitente)',
    c.subject AS 'Asunto',
    DATE_FORMAT(c.received_at, '%d de %M de %Y a las %H:%i') AS 'Fecha',
    p.display_name AS 'Plataforma',
    c.email_body AS 'Cuerpo del Correo (HTML/Texto)',
    
    -- Información adicional (para referencia)
    c.code AS 'Código Extraído',
    c.recipient_email AS 'Email del Usuario que Consulta',
    c.received_at AS 'Fecha Original',
    LENGTH(c.email_body) AS 'Tamaño del Cuerpo (caracteres)'
    
FROM codes c
INNER JOIN platforms p ON c.platform_id = p.id
WHERE c.recipient_email = 'cineclub017@pocoyoni.com'  -- ⬅️ Cambia por el email que quieras
  AND c.email_body IS NOT NULL 
  AND c.email_body != ''
ORDER BY c.received_at DESC
LIMIT 1;

-- ============================================================
-- EJEMPLO: Ver el correo más reciente CON email_body
-- (sin importar el usuario, solo para ver cómo se ve)
-- ============================================================
/*
SELECT 
    c.email_from AS 'De',
    c.subject AS 'Asunto',
    DATE_FORMAT(c.received_at, '%d/%m/%Y %H:%i') AS 'Fecha',
    p.display_name AS 'Plataforma',
    c.recipient_email AS 'Email Usuario',
    c.email_body AS 'Cuerpo Completo del Email'
FROM codes c
INNER JOIN platforms p ON c.platform_id = p.id
WHERE c.email_body IS NOT NULL 
  AND c.email_body != ''
  AND LENGTH(c.email_body) > 1000  -- Al menos 1000 caracteres
ORDER BY c.received_at DESC
LIMIT 1;
*/

-- ============================================================
-- Ver correo completo de los usuarios que SÍ tienen email_body
-- ============================================================
/*
SELECT 
    c.id,
    c.email_from AS 'De',
    c.subject AS 'Asunto',
    DATE_FORMAT(c.received_at, '%d/%m/%Y %H:%i') AS 'Fecha',
    c.recipient_email AS 'Email Usuario',
    p.display_name AS 'Plataforma',
    LENGTH(c.email_body) AS 'Tamaño',
    c.email_body AS 'Cuerpo Completo'
FROM codes c
INNER JOIN platforms p ON c.platform_id = p.id
WHERE c.email_body IS NOT NULL 
  AND c.email_body != ''
ORDER BY c.received_at DESC
LIMIT 5;
*/
