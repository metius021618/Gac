-- GAC: categoría de asuntos (general | modo_viaje)
-- Ejecutar una vez en la BD operativa.

ALTER TABLE email_subjects
  ADD COLUMN category VARCHAR(32) NOT NULL DEFAULT 'general'
    COMMENT 'general | modo_viaje'
    AFTER subject_line;

ALTER TABLE email_subjects
  ADD INDEX idx_email_subjects_category (category);
