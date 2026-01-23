-- ============================================
-- CONFIGURAR CUENTA MAESTRA IMAP
-- ============================================
-- Este script configura el sistema para usar solo
-- la cuenta maestra streaming@pocoyoni.com

USE pocoavbb_gac;

-- 1. Eliminar todas las cuentas individuales (opcional, puedes comentar esto)
-- DELETE FROM email_accounts WHERE type = 'imap' AND email != 'streaming@pocoyoni.com';

-- 2. Insertar o actualizar la cuenta maestra
INSERT INTO email_accounts (email, type, provider_config, enabled, created_at, updated_at)
VALUES (
    'streaming@pocoyoni.com',
    'imap',
    JSON_OBJECT(
        'imap_server', 'premium211.web-hosting.com',
        'imap_port', 993,
        'imap_encryption', 'ssl',
        'imap_user', 'streaming@pocoyoni.com',
        'imap_password', 'D3b+Vln0tj0Q',
        'is_master', true,
        'filter_by_recipient', true
    ),
    1,
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    provider_config = JSON_OBJECT(
        'imap_server', 'premium211.web-hosting.com',
        'imap_port', 993,
        'imap_encryption', 'ssl',
        'imap_user', 'streaming@pocoyoni.com',
        'imap_password', 'D3b+Vln0tj0Q',
        'is_master', true,
        'filter_by_recipient', true
    ),
    enabled = 1,
    updated_at = NOW();

-- 3. Verificar configuración
SELECT 
    id,
    email,
    type,
    JSON_EXTRACT(provider_config, '$.imap_server') as imap_server,
    JSON_EXTRACT(provider_config, '$.is_master') as is_master,
    JSON_EXTRACT(provider_config, '$.filter_by_recipient') as filter_by_recipient,
    enabled
FROM email_accounts
WHERE email = 'streaming@pocoyoni.com';
