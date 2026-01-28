-- GAC - Migración: Migrar datos de email_subjects desde BD antigua
-- Convierte la estructura antigua (platform VARCHAR) a la nueva (platform_id INT)

USE pocoavbb_gac;

-- Primero, asegurarse de que la tabla existe con la nueva estructura
CREATE TABLE IF NOT EXISTS email_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    platform_id INT NOT NULL,
    subject_line VARCHAR(500) NOT NULL COMMENT 'Asunto del correo electrónico',
    active TINYINT(1) DEFAULT 1 COMMENT '1 = activo, 0 = eliminado (soft delete)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (platform_id) REFERENCES platforms(id) ON DELETE CASCADE,
    INDEX idx_platform_id (platform_id),
    INDEX idx_active (active),
    INDEX idx_subject_line (subject_line(255)),
    UNIQUE KEY unique_platform_subject (platform_id, subject_line(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Migrar datos desde la BD antigua (si existe)
-- Mapeo de nombres de plataformas antiguos a IDs de la tabla platforms
INSERT INTO email_subjects (platform_id, subject_line, active, created_at)
SELECT DISTINCT
    p.id as platform_id,
    es.subject_line,
    COALESCE(es.active, 1) as active,
    NOW() as created_at
FROM pocoavbb_codes544shd.email_subjects es
INNER JOIN platforms p ON (
    (LOWER(es.platform) = 'netflix' AND LOWER(p.name) = 'netflix')
    OR (LOWER(es.platform) IN ('disney', 'disney+') AND LOWER(p.name) = 'disney')
    OR ((LOWER(es.platform) LIKE '%amazon%' OR LOWER(es.platform) LIKE '%prime%') AND LOWER(p.name) = 'prime')
    OR (LOWER(es.platform) = 'crunchyroll' AND LOWER(p.name) = 'crunchyroll')
    OR (LOWER(es.platform) = 'chatgpt' AND LOWER(p.name) = 'chatgpt')
    OR (LOWER(es.platform) IN ('paramount', 'paramount+') AND LOWER(p.name) = 'paramount')
    OR (LOWER(es.platform) = 'spotify' AND LOWER(p.name) = 'spotify')
    OR (LOWER(es.platform) = 'canva' AND LOWER(p.name) = 'canva')
    OR (LOWER(es.platform) IN ('hbo max', 'max') AND LOWER(p.name) = 'max')
    OR (LOWER(TRIM(es.platform)) = LOWER(TRIM(p.display_name)))
)
WHERE es.active = 1
  AND p.id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM email_subjects es2 
      WHERE es2.platform_id = p.id 
      AND LOWER(TRIM(es2.subject_line)) = LOWER(TRIM(es.subject_line))
  )
ON DUPLICATE KEY UPDATE 
    updated_at = NOW();
