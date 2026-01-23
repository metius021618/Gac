-- ============================================
-- ACTUALIZAR SERVIDOR IMAP A SERVIDOR LOCAL
-- ============================================
-- Este script actualiza el provider_config para usar el servidor
-- IMAP local del hosting (premium211.web-hosting.com) en lugar
-- de imap.gmail.com

USE pocoavbb_gac;

-- Actualizar provider_config para usar servidor local
UPDATE email_accounts
SET provider_config = JSON_SET(
    provider_config,
    '$.imap_server', 'premium211.web-hosting.com'
)
WHERE type = 'imap'
  AND JSON_EXTRACT(provider_config, '$.imap_server') = 'imap.gmail.com';

-- Verificar resultado
SELECT 
    id,
    email,
    JSON_EXTRACT(provider_config, '$.imap_server') as imap_server,
    JSON_EXTRACT(provider_config, '$.imap_port') as imap_port,
    JSON_EXTRACT(provider_config, '$.imap_user') as imap_user,
    CASE 
        WHEN JSON_EXTRACT(provider_config, '$.imap_password') IS NOT NULL 
         AND JSON_UNQUOTE(JSON_EXTRACT(provider_config, '$.imap_password')) != '' 
        THEN 'Contraseña configurada'
        ELSE 'Sin contraseña'
    END as password_status
FROM email_accounts
WHERE type = 'imap'
LIMIT 10;

-- Contar cuántas cuentas fueron actualizadas
SELECT 
    COUNT(*) as total_cuentas,
    SUM(CASE 
        WHEN JSON_EXTRACT(provider_config, '$.imap_server') = 'premium211.web-hosting.com' 
        THEN 1 
        ELSE 0 
    END) as usando_servidor_local,
    SUM(CASE 
        WHEN JSON_EXTRACT(provider_config, '$.imap_server') = 'imap.gmail.com' 
        THEN 1 
        ELSE 0 
    END) as usando_gmail
FROM email_accounts
WHERE type = 'imap';
