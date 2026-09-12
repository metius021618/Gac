-- Asuntos especiales: cuerpo + acción; flag en codes; permiso en user_access
-- Ejecutar vía: python3 scripts/migrate_asuntos_especiales.py

ALTER TABLE email_subjects
  ADD COLUMN body_match TEXT NULL COMMENT 'Cuerpo exacto/normalizado para asuntos especiales' AFTER category,
  ADD COLUMN special_action VARCHAR(16) NULL COMMENT 'leer | no_leer (solo category especial_*)' AFTER body_match;

ALTER TABLE codes
  ADD COLUMN is_special TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = asunto especial (solo usuarios con permiso)' AFTER status,
  ADD INDEX idx_codes_is_special (is_special);

ALTER TABLE user_access
  ADD COLUMN can_view_special TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = puede consultar códigos de asuntos especiales' AFTER enabled,
  ADD INDEX idx_user_access_can_view_special (can_view_special);

-- Permitir mismo asunto en distintas categorías / cuerpos especiales
ALTER TABLE email_subjects DROP INDEX unique_platform_subject;
