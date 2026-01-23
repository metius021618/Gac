-- ============================================
-- ACTUALIZAR CONTRASEÑAS DESDE BD ANTIGUA
-- ============================================
-- Este script actualiza el provider_config de las cuentas de email
-- con las contraseñas de la base de datos antigua

USE pocoavbb_gac;

-- Actualizar provider_config con las contraseñas de la BD antigua
UPDATE pocoavbb_gac.email_accounts ea
INNER JOIN pocoavbb_codes544shd.usuarios_correos uc ON ea.email = uc.email
SET ea.provider_config = JSON_SET(
    ea.provider_config,
    '$.imap_password', uc.password
)
WHERE ea.type = 'imap'
  AND uc.password IS NOT NULL
  AND uc.password != '';

-- Verificar resultado
SELECT 
    ea.id,
    ea.email,
    JSON_EXTRACT(ea.provider_config, '$.imap_server') as imap_server,
    JSON_EXTRACT(ea.provider_config, '$.imap_user') as imap_user,
    CASE 
        WHEN JSON_EXTRACT(ea.provider_config, '$.imap_password') IS NOT NULL 
         AND JSON_UNQUOTE(JSON_EXTRACT(ea.provider_config, '$.imap_password')) != '' 
        THEN 'Contraseña configurada'
        ELSE 'Sin contraseña'
    END as password_status,
    LENGTH(JSON_UNQUOTE(JSON_EXTRACT(ea.provider_config, '$.imap_password'))) as password_length
FROM pocoavbb_gac.email_accounts ea
WHERE ea.type = 'imap'
LIMIT 10;

-- Contar cuántas cuentas tienen contraseña configurada
SELECT 
    COUNT(*) as total_cuentas,
    SUM(CASE 
        WHEN JSON_EXTRACT(provider_config, '$.imap_password') IS NOT NULL 
         AND JSON_UNQUOTE(JSON_EXTRACT(provider_config, '$.imap_password')) != '' 
        THEN 1 
        ELSE 0 
    END) as cuentas_con_password,
    SUM(CASE 
        WHEN JSON_EXTRACT(provider_config, '$.imap_password') IS NULL 
         OR JSON_UNQUOTE(JSON_EXTRACT(provider_config, '$.imap_password')) = '' 
        THEN 1 
        ELSE 0 
    END) as cuentas_sin_password
FROM pocoavbb_gac.email_accounts
WHERE type = 'imap';
