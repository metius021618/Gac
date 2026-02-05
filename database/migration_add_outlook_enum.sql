-- Migración: añadir tipo 'outlook' a email_accounts y origin 'outlook' a codes
-- Ejecutar en la base de datos operativa (ej. pocoavbb_gac) si ya tenías la tabla sin outlook.

-- 1) Permitir type = 'outlook' en email_accounts
ALTER TABLE email_accounts
  MODIFY COLUMN type ENUM('imap', 'gmail', 'outlook') NOT NULL;

-- 2) Permitir origin = 'outlook' en codes (para correos leídos por el script Outlook)
ALTER TABLE codes
  MODIFY COLUMN origin ENUM('imap', 'gmail', 'outlook') NOT NULL;

-- 3) Marcar la fila correcta como Outlook (la que tiene el correo normalizado)
UPDATE email_accounts
SET type = 'outlook', enabled = 1
WHERE email = 'apipocoyoni@outlook.com';

-- 4) Eliminar el duplicado con el correo largo de Microsoft (guest)
DELETE FROM email_accounts
WHERE email LIKE '%#ext#%' OR email LIKE '%#EXT#%';
