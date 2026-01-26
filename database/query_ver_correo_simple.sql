-- ============================================================
-- QUERY SIMPLE: Ver correo completo como se muestra al usuario
-- ============================================================
-- 
-- INSTRUCCIONES:
-- 1. Reemplaza 'TU_EMAIL_AQUI@pocoyoni.com' con el email del usuario
-- 2. Ejecuta la query
-- 3. Verás exactamente lo que aparecería en el modal
-- ============================================================

USE pocoavbb_gac;

SELECT 
    -- Información que aparece en el modal
    c.email_from AS 'De (Remitente)',
    c.subject AS 'Asunto',
    DATE_FORMAT(c.received_at, '%d de %M de %Y a las %H:%i') AS 'Fecha',
    p.display_name AS 'Plataforma',
    c.email_body AS 'Cuerpo del Correo (HTML/Texto)',
    
    -- Información adicional (para referencia)
    c.code AS 'Código Extraído',
    c.recipient_email AS 'Email del Usuario',
    CASE 
        WHEN c.email_body IS NULL OR c.email_body = '' 
            THEN '❌ Sin contenido' 
        ELSE CONCAT('✓ ', LENGTH(c.email_body), ' caracteres') 
    END AS 'Estado'
    
FROM codes c
INNER JOIN platforms p ON c.platform_id = p.id
WHERE c.recipient_email = 'TU_EMAIL_AQUI@pocoyoni.com'  -- ⬅️ CAMBIA ESTO
ORDER BY c.received_at DESC
LIMIT 1;

-- ============================================================
-- EJEMPLO REAL (descomenta y usa):
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
WHERE c.recipient_email = 'jarri4caballero@pocoyoni.com'
ORDER BY c.received_at DESC
LIMIT 1;
*/
