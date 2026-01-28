-- ============================================
-- GAC - Migración Simple: email_subjects
-- Migra datos de pocoavbb_codes544shd.email_subjects → pocoavbb_gac.email_subjects
-- ============================================

USE pocoavbb_gac;

-- Paso 1: Asegurar que la tabla existe
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

-- Paso 2: Migrar datos desde BD antigua
INSERT INTO email_subjects (platform_id, subject_line, active, created_at)
SELECT DISTINCT
    p.id as platform_id,
    es.subject_line,
    COALESCE(es.active, 1) as active,
    NOW() as created_at
FROM pocoavbb_codes544shd.email_subjects es
INNER JOIN platforms p ON (
    -- Mapeo de nombres de plataformas antiguos a IDs
    (LOWER(TRIM(es.platform)) = 'netflix' AND LOWER(p.name) = 'netflix')
    OR (LOWER(TRIM(es.platform)) IN ('disney', 'disney+') AND LOWER(p.name) = 'disney')
    OR ((LOWER(TRIM(es.platform)) LIKE '%amazon%' OR LOWER(TRIM(es.platform)) LIKE '%prime%') AND LOWER(p.name) = 'prime')
    OR (LOWER(TRIM(es.platform)) = 'crunchyroll' AND LOWER(p.name) = 'crunchyroll')
    OR (LOWER(TRIM(es.platform)) = 'chatgpt' AND LOWER(p.name) = 'chatgpt')
    OR (LOWER(TRIM(es.platform)) IN ('paramount', 'paramount+') AND LOWER(p.name) = 'paramount')
    OR (LOWER(TRIM(es.platform)) = 'spotify' AND LOWER(p.name) = 'spotify')
    OR (LOWER(TRIM(es.platform)) = 'canva' AND LOWER(p.name) = 'canva')
    OR (LOWER(TRIM(es.platform)) IN ('hbo max', 'max') AND LOWER(p.name) = 'max')
    -- Fallback: comparar con display_name
    OR (LOWER(TRIM(es.platform)) = LOWER(TRIM(p.display_name)))
)
WHERE es.active = 1
  AND p.id IS NOT NULL
  AND NOT EXISTS (
      -- Evitar duplicados
      SELECT 1 FROM email_subjects es2 
      WHERE es2.platform_id = p.id 
      AND LOWER(TRIM(es2.subject_line)) = LOWER(TRIM(es.subject_line))
  );

-- Paso 3: Verificar migración
SELECT 
    'Total migrado' as descripcion,
    COUNT(*) as cantidad
FROM email_subjects
WHERE active = 1;

-- Ver algunos registros migrados
SELECT 
    es.id,
    p.display_name as plataforma,
    es.subject_line as asunto,
    es.active,
    es.created_at
FROM email_subjects es
JOIN platforms p ON es.platform_id = p.id
ORDER BY es.id DESC
LIMIT 10;
