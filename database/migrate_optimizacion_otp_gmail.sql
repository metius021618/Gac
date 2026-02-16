-- ============================================
-- Migración: Optimización OTP vía Gmail (event-driven)
-- Ejecutar en orden. Ver OPTIMIZACION_OTP_GMAIL_BD.md para explicación completa.
-- ============================================

-- USE pocoavbb_gac;  -- descomentar si aplica

-- 1) Nuevos campos en codes
ALTER TABLE codes
  ADD COLUMN email_date DATETIME NULL COMMENT 'Fecha del correo (desde header o internalDate)',
  ADD COLUMN gmail_message_id VARCHAR(255) NULL COMMENT 'ID mensaje Gmail (evita duplicados)',
  ADD COLUMN is_current TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=solo el último OTP por cuenta+plataforma',
  ADD COLUMN expires_at DATETIME NULL COMMENT 'Caducidad opcional del código';

-- 2) Índices para consulta y unicidad
CREATE UNIQUE INDEX idx_gmail_message_id ON codes (gmail_message_id);
CREATE INDEX idx_codes_current_lookup ON codes (email_account_id, platform_id, is_current);

-- 3) Settings: valor inicial para historyId (la app hará UPDATE)
-- Si tu settings usa columna "name":
INSERT IGNORE INTO settings (name, value, type, description) VALUES ('gmail_last_history_id', '', 'string', 'Último historyId de Gmail para lectura incremental');
-- Si tu settings usa columna "key" en lugar de "name", usa:
-- INSERT IGNORE INTO settings (`key`, `value`, description, category) VALUES ('gmail_last_history_id', '', 'Último historyId Gmail', 'gmail');
