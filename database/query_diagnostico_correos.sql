-- ============================================================
-- QUERIES DE DIAGNÓSTICO: Ver qué correos hay en la BD
-- ============================================================

USE pocoavbb_gac;

-- ============================================================
-- 1. Ver TODOS los correos recientes (últimos 20)
-- ============================================================
SELECT 
    c.id,
    c.code,
    c.email_from AS 'De',
    c.subject AS 'Asunto',
    c.received_at AS 'Fecha',
    c.recipient_email AS 'Email Usuario',
    p.display_name AS 'Plataforma',
    CASE 
        WHEN c.email_body IS NULL OR c.email_body = '' 
            THEN '❌ Sin cuerpo' 
        ELSE CONCAT('✓ ', LENGTH(c.email_body), ' chars') 
    END AS 'Tiene Cuerpo'
FROM codes c
INNER JOIN platforms p ON c.platform_id = p.id
ORDER BY c.received_at DESC
LIMIT 20;

-- ============================================================
-- 2. Ver correos que SÍ tienen email_body
-- ============================================================
SELECT 
    c.id,
    c.code,
    c.email_from AS 'De',
    c.subject AS 'Asunto',
    c.received_at AS 'Fecha',
    c.recipient_email AS 'Email Usuario',
    p.display_name AS 'Plataforma',
    LEFT(c.email_body, 100) AS 'Preview Cuerpo'
FROM codes c
INNER JOIN platforms p ON c.platform_id = p.id
WHERE c.email_body IS NOT NULL 
  AND c.email_body != ''
ORDER BY c.received_at DESC
LIMIT 10;

-- ============================================================
-- 3. Ver correos que NO tienen recipient_email (antiguos)
-- ============================================================
SELECT 
    c.id,
    c.code,
    c.email_from AS 'De',
    c.subject AS 'Asunto',
    c.received_at AS 'Fecha',
    c.recipient_email AS 'Email Usuario',
    p.display_name AS 'Plataforma'
FROM codes c
INNER JOIN platforms p ON c.platform_id = p.id
WHERE c.recipient_email IS NULL 
   OR c.recipient_email = ''
ORDER BY c.received_at DESC
LIMIT 10;

-- ============================================================
-- 4. Buscar correos por REMITENTE (más fácil de encontrar)
-- ============================================================
SELECT 
    c.id,
    c.code,
    c.email_from AS 'De',
    c.subject AS 'Asunto',
    c.received_at AS 'Fecha',
    c.recipient_email AS 'Email Usuario',
    p.display_name AS 'Plataforma',
    c.email_body AS 'Cuerpo Completo'
FROM codes c
INNER JOIN platforms p ON c.platform_id = p.id
WHERE c.email_from LIKE '%disneyplus%'  -- Cambia esto
ORDER BY c.received_at DESC
LIMIT 5;

-- ============================================================
-- 5. Buscar correos por ASUNTO
-- ============================================================
SELECT 
    c.id,
    c.code,
    c.email_from AS 'De',
    c.subject AS 'Asunto',
    c.received_at AS 'Fecha',
    c.recipient_email AS 'Email Usuario',
    p.display_name AS 'Plataforma',
    c.email_body AS 'Cuerpo Completo'
FROM codes c
INNER JOIN platforms p ON c.platform_id = p.id
WHERE c.subject LIKE '%código%'  -- Cambia esto
ORDER BY c.received_at DESC
LIMIT 5;

-- ============================================================
-- 6. Ver estadísticas generales
-- ============================================================
SELECT 
    COUNT(*) AS 'Total Correos',
    COUNT(CASE WHEN email_body IS NOT NULL AND email_body != '' THEN 1 END) AS 'Con Cuerpo',
    COUNT(CASE WHEN recipient_email IS NOT NULL AND recipient_email != '' THEN 1 END) AS 'Con Email Usuario',
    COUNT(CASE WHEN email_body IS NULL OR email_body = '' THEN 1 END) AS 'Sin Cuerpo'
FROM codes;

-- ============================================================
-- 7. Ver correos más recientes CON email_body (para probar)
-- ============================================================
SELECT 
    c.id,
    c.code,
    c.email_from AS 'De',
    c.subject AS 'Asunto',
    DATE_FORMAT(c.received_at, '%d/%m/%Y %H:%i') AS 'Fecha',
    c.recipient_email AS 'Email Usuario',
    p.display_name AS 'Plataforma',
    LENGTH(c.email_body) AS 'Tamaño Cuerpo',
    c.email_body AS 'Cuerpo Completo'
FROM codes c
INNER JOIN platforms p ON c.platform_id = p.id
WHERE c.email_body IS NOT NULL 
  AND c.email_body != ''
ORDER BY c.received_at DESC
LIMIT 1;
