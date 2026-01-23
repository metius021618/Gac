-- ============================================
-- GAC - Script de Migración desde Base de Datos Antigua
-- Migra datos de pocoavbb_codes544shd a pocoavbb_gac
-- ============================================

USE pocoavbb_gac;

-- ============================================
-- 1. MIGRAR EMAIL_SUBJECTS → SETTINGS
-- ============================================

-- Netflix (máximo 4 asuntos)
SET @row_number = 0;
INSERT IGNORE INTO pocoavbb_gac.settings (name, value, type, description)
SELECT 
    CONCAT('NETFLIX_', @row_number := @row_number + 1) as name,
    subject_line as value,
    'string' as type,
    CONCAT('Asunto para emails de Netflix') as description
FROM pocoavbb_codes544shd.email_subjects
WHERE platform = 'Netflix' AND active = 1
ORDER BY id
LIMIT 4
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- Disney (máximo 4 asuntos)
SET @row_number = 0;
INSERT IGNORE INTO pocoavbb_gac.settings (name, value, type, description)
SELECT 
    CONCAT('DISNEY_', @row_number := @row_number + 1) as name,
    subject_line as value,
    'string' as type,
    CONCAT('Asunto para emails de Disney+') as description
FROM pocoavbb_codes544shd.email_subjects
WHERE platform = 'Disney' AND active = 1
ORDER BY id
LIMIT 4
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- Amazon Prime Video (máximo 4 asuntos)
SET @row_number = 0;
INSERT IGNORE INTO pocoavbb_gac.settings (name, value, type, description)
SELECT 
    CONCAT('PRIME_', @row_number := @row_number + 1) as name,
    subject_line as value,
    'string' as type,
    CONCAT('Asunto para emails de Amazon Prime Video') as description
FROM pocoavbb_codes544shd.email_subjects
WHERE platform = 'Amazon Prime Video' AND active = 1
ORDER BY id
LIMIT 4
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- Paramount (máximo 4 asuntos)
SET @row_number = 0;
INSERT IGNORE INTO pocoavbb_gac.settings (name, value, type, description)
SELECT 
    CONCAT('PARAMOUNT_', @row_number := @row_number + 1) as name,
    subject_line as value,
    'string' as type,
    CONCAT('Asunto para emails de Paramount+') as description
FROM pocoavbb_codes544shd.email_subjects
WHERE platform = 'Paramount' AND active = 1
ORDER BY id
LIMIT 4
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- Crunchyroll (máximo 4 asuntos)
SET @row_number = 0;
INSERT IGNORE INTO pocoavbb_gac.settings (name, value, type, description)
SELECT 
    CONCAT('CRUNCHYROLL_', @row_number := @row_number + 1) as name,
    subject_line as value,
    'string' as type,
    CONCAT('Asunto para emails de Crunchyroll') as description
FROM pocoavbb_codes544shd.email_subjects
WHERE platform = 'Crunchyroll' AND active = 1
ORDER BY id
LIMIT 4
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- ChatGPT (máximo 4 asuntos)
SET @row_number = 0;
INSERT IGNORE INTO pocoavbb_gac.settings (name, value, type, description)
SELECT 
    CONCAT('CHATGPT_', @row_number := @row_number + 1) as name,
    subject_line as value,
    'string' as type,
    CONCAT('Asunto para emails de ChatGPT') as description
FROM pocoavbb_codes544shd.email_subjects
WHERE platform = 'ChatGpt' AND active = 1
ORDER BY id
LIMIT 4
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- HBO Max / Max (máximo 4 asuntos)
SET @row_number = 0;
INSERT IGNORE INTO pocoavbb_gac.settings (name, value, type, description)
SELECT 
    CONCAT('MAX_', @row_number := @row_number + 1) as name,
    subject_line as value,
    'string' as type,
    CONCAT('Asunto para emails de HBO Max/Max') as description
FROM pocoavbb_codes544shd.email_subjects
WHERE platform IN ('HBO Max', 'Max') AND active = 1
ORDER BY id
LIMIT 4
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- ============================================
-- 2. MIGRAR USUARIOS_CORREOS → EMAIL_ACCOUNTS
-- ============================================

-- Primero necesitamos crear las cuentas de email
-- Nota: La configuración IMAP se guarda en provider_config como JSON
-- IMPORTANTE: Ajusta imap_server según tu proveedor (imap.gmail.com, imap.mail.yahoo.com, etc.)
INSERT IGNORE INTO pocoavbb_gac.email_accounts (email, type, provider_config, enabled, created_at)
SELECT DISTINCT
    email,
    'imap' as type,
    JSON_OBJECT(
        'imap_server', 'imap.gmail.com', -- Ajustar según tu proveedor de email
        'imap_port', 993,
        'imap_encryption', 'ssl',
        'imap_user', email, -- El usuario es el mismo email
        'imap_password', password
    ) as provider_config,
    1 as enabled,
    created_at
FROM pocoavbb_codes544shd.usuarios_correos
WHERE NOT EXISTS (
    SELECT 1 FROM pocoavbb_gac.email_accounts ea WHERE ea.email = usuarios_correos.email
);

-- ============================================
-- 3. VERIFICACIÓN Y REPORTE
-- ============================================

-- Contar registros migrados
SELECT 'Settings migrados' as tipo, COUNT(*) as cantidad FROM pocoavbb_gac.settings WHERE name LIKE '%_1' OR name LIKE '%_2' OR name LIKE '%_3' OR name LIKE '%_4';
SELECT 'Cuentas de email migradas' as tipo, COUNT(*) as cantidad FROM pocoavbb_gac.email_accounts;

-- ============================================
-- NOTAS IMPORTANTES:
-- ============================================
-- 1. Este script asume que la BD antigua se llama: pocoavbb_codes544shd
-- 2. Ajusta el nombre de la BD antigua si es diferente
-- 3. Los asuntos se migran con numeración automática (_1, _2, _3, _4)
-- 4. Las contraseñas de email se guardan en provider_config como JSON
-- 5. Ajusta la configuración IMAP (host, port) según tu proveedor
-- 6. Solo se migran asuntos activos (active = 1)
-- 7. Solo se migran correos únicos (sin duplicados)
-- ============================================
