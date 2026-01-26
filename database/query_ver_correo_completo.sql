-- ============================================================
-- Query para ver un correo completo como se mostraría en la consulta
-- ============================================================
-- 
-- INSTRUCCIONES:
-- 1. Reemplaza 'EMAIL_DEL_USUARIO' con el email que el usuario ingresaría
--    Ejemplo: 'jarri4caballero@pocoyoni.com'
-- 
-- 2. Opcionalmente, puedes filtrar por plataforma agregando:
--    AND p.name = 'disney'  (o 'netflix', 'prime', etc.)
--
-- 3. Ejecuta la query para ver el correo completo
-- ============================================================

USE pocoavbb_gac;

SELECT 
    -- Información del código/correo
    c.id AS codigo_id,
    c.code AS codigo,
    c.email_from AS remitente,
    c.subject AS asunto,
    c.received_at AS fecha_recepcion,
    c.recipient_email AS email_usuario,
    c.status AS estado,
    
    -- Información de la plataforma
    p.name AS plataforma_slug,
    p.display_name AS plataforma_nombre,
    
    -- Cuerpo del email
    c.email_body AS cuerpo_del_correo,
    
    -- Información adicional
    c.created_at AS fecha_creacion,
    c.consumed_at AS fecha_consumido,
    c.consumed_by_username AS consumido_por_usuario,
    
    -- Tiempo transcurrido
    TIMESTAMPDIFF(MINUTE, c.received_at, NOW()) AS minutos_desde_recepcion,
    CASE 
        WHEN TIMESTAMPDIFF(MINUTE, c.received_at, NOW()) < 60 
            THEN CONCAT(TIMESTAMPDIFF(MINUTE, c.received_at, NOW()), ' minuto(s)')
        WHEN TIMESTAMPDIFF(MINUTE, c.received_at, NOW()) < 1440 
            THEN CONCAT(FLOOR(TIMESTAMPDIFF(MINUTE, c.received_at, NOW()) / 60), ' hora(s)')
        ELSE CONCAT(FLOOR(TIMESTAMPDIFF(MINUTE, c.received_at, NOW()) / 1440), ' día(s)')
    END AS tiempo_transcurrido

FROM codes c
INNER JOIN platforms p ON c.platform_id = p.id
WHERE c.recipient_email = 'EMAIL_DEL_USUARIO'  -- ⬅️ CAMBIA ESTO
  AND (c.email_body IS NOT NULL AND c.email_body != '')  -- Solo correos con cuerpo
ORDER BY c.received_at DESC
LIMIT 10;

-- ============================================================
-- QUERY ALTERNATIVA: Ver todos los correos de un usuario
-- (incluyendo los que no tienen email_body)
-- ============================================================
/*
SELECT 
    c.id,
    c.code,
    c.email_from AS remitente,
    c.subject AS asunto,
    c.received_at AS fecha,
    p.display_name AS plataforma,
    CASE 
        WHEN c.email_body IS NULL OR c.email_body = '' 
            THEN '❌ Sin contenido' 
        ELSE '✓ Con contenido' 
    END AS tiene_cuerpo,
    LENGTH(c.email_body) AS longitud_cuerpo,
    LEFT(c.email_body, 100) AS preview_cuerpo
FROM codes c
INNER JOIN platforms p ON c.platform_id = p.id
WHERE c.recipient_email = 'EMAIL_DEL_USUARIO'  -- ⬅️ CAMBIA ESTO
ORDER BY c.received_at DESC
LIMIT 20;
*/

-- ============================================================
-- QUERY PARA VER EL CORREO MÁS RECIENTE DE UN USUARIO
-- ============================================================
/*
SELECT 
    c.id,
    c.code,
    c.email_from AS remitente,
    c.subject AS asunto,
    c.received_at AS fecha_recepcion,
    p.display_name AS plataforma,
    c.email_body AS cuerpo_completo,
    CASE 
        WHEN c.email_body IS NULL OR c.email_body = '' 
            THEN 'Sin contenido disponible' 
        ELSE CONCAT('Contenido disponible (', LENGTH(c.email_body), ' caracteres)') 
    END AS estado_contenido
FROM codes c
INNER JOIN platforms p ON c.platform_id = p.id
WHERE c.recipient_email = 'EMAIL_DEL_USUARIO'  -- ⬅️ CAMBIA ESTO
ORDER BY c.received_at DESC
LIMIT 1;
*/
