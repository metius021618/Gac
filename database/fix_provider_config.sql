-- ============================================
-- GAC - Script para Corregir provider_config
-- Actualiza los campos incorrectos a los correctos
-- ============================================

USE pocoavbb_gac;

-- Actualizar provider_config para usar los nombres correctos
-- Cambiar de: password, host, port, encryption
-- A: imap_password, imap_server, imap_port, imap_encryption, imap_user

UPDATE email_accounts
SET provider_config = JSON_OBJECT(
    'imap_server', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(provider_config, '$.host')), 'imap.gmail.com'),
    'imap_port', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(provider_config, '$.port')), 993),
    'imap_encryption', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(provider_config, '$.encryption')), 'ssl'),
    'imap_user', email,
    'imap_password', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(provider_config, '$.password')), '')
)
WHERE type = 'imap'
  AND (
    JSON_EXTRACT(provider_config, '$.host') IS NOT NULL
    OR JSON_EXTRACT(provider_config, '$.password') IS NOT NULL
  );

-- Verificar resultado
SELECT 
    id,
    email,
    JSON_EXTRACT(provider_config, '$.imap_server') as imap_server,
    JSON_EXTRACT(provider_config, '$.imap_user') as imap_user,
    CASE 
        WHEN JSON_EXTRACT(provider_config, '$.imap_password') IS NOT NULL THEN 'Configurado'
        ELSE 'Sin contraseña'
    END as password_status
FROM email_accounts
WHERE type = 'imap'
LIMIT 5;
