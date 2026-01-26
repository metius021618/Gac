-- ============================================================
-- EJEMPLO: Ver correo completo de un usuario específico
-- ============================================================
-- 
-- Este es un ejemplo real usando el email que mencionaste antes
-- Puedes copiar y pegar esta query directamente
-- ============================================================

USE pocoavbb_gac;

-- Ejemplo 1: Ver el correo más reciente de jarri4caballero@pocoyoni.com
SELECT 
    c.id,
    c.code,
    c.email_from AS 'De (Remitente)',
    c.subject AS 'Asunto',
    c.received_at AS 'Fecha de Recepción',
    p.display_name AS 'Plataforma',
    c.recipient_email AS 'Email del Usuario',
    CASE 
        WHEN c.email_body IS NULL OR c.email_body = '' 
            THEN '❌ Sin contenido' 
        ELSE CONCAT('✓ Contenido disponible (', LENGTH(c.email_body), ' caracteres)') 
    END AS 'Estado del Contenido',
    c.email_body AS 'Cuerpo del Correo Completo',
    TIMESTAMPDIFF(MINUTE, c.received_at, NOW()) AS 'Minutos desde recepción'
FROM codes c
INNER JOIN platforms p ON c.platform_id = p.id
WHERE c.recipient_email = 'jarri4caballero@pocoyoni.com'
ORDER BY c.received_at DESC
LIMIT 1;

-- ============================================================
-- Ejemplo 2: Ver los últimos 5 correos de un usuario
-- ============================================================
/*
SELECT 
    c.id,
    c.code,
    c.email_from AS 'De',
    c.subject AS 'Asunto',
    DATE_FORMAT(c.received_at, '%d/%m/%Y %H:%i') AS 'Fecha',
    p.display_name AS 'Plataforma',
    CASE 
        WHEN c.email_body IS NULL OR c.email_body = '' 
            THEN 'Sin contenido' 
        ELSE CONCAT(LENGTH(c.email_body), ' caracteres') 
    END AS 'Contenido',
    LEFT(c.email_body, 200) AS 'Preview del Cuerpo'
FROM codes c
INNER JOIN platforms p ON c.platform_id = p.id
WHERE c.recipient_email = 'jarri4caballero@pocoyoni.com'
ORDER BY c.received_at DESC
LIMIT 5;
*/

-- ============================================================
-- Ejemplo 3: Ver correo específico de Disney+ para un usuario
-- ============================================================
/*
SELECT 
    c.id,
    c.code,
    c.email_from AS 'Remitente',
    c.subject AS 'Asunto',
    c.received_at AS 'Fecha',
    c.email_body AS 'Cuerpo Completo del Email'
FROM codes c
INNER JOIN platforms p ON c.platform_id = p.id
WHERE c.recipient_email = 'jarri4caballero@pocoyoni.com'
  AND p.name = 'disney'
  AND c.email_body IS NOT NULL
  AND c.email_body != ''
ORDER BY c.received_at DESC
LIMIT 1;
*/
