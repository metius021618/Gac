-- ============================================
-- QUERY SIMPLE: Migrar email_subjects (CORREGIDA)
-- De: pocoavbb_codes544shd.email_subjects
-- A:   pocoavbb_gac.email_subjects
-- 
-- CORRECCIÓN: Usa BINARY o CAST para evitar errores de collation
-- ============================================

USE pocoavbb_gac;

-- INSERTAR datos migrando platform (VARCHAR) → platform_id (INT)
INSERT INTO email_subjects (platform_id, subject_line, active, created_at)
SELECT DISTINCT
    p.id as platform_id,
    es.subject_line,
    COALESCE(es.active, 1) as active,
    NOW() as created_at
FROM pocoavbb_codes544shd.email_subjects es
INNER JOIN platforms p ON (
    -- Mapeo de nombres de plataformas (usando BINARY para comparación sin collation)
    (BINARY LOWER(TRIM(es.platform)) = BINARY 'netflix' AND LOWER(p.name) = 'netflix')
    OR (BINARY LOWER(TRIM(es.platform)) IN (BINARY 'disney', BINARY 'disney+') AND LOWER(p.name) = 'disney')
    OR ((BINARY LOWER(TRIM(es.platform)) LIKE BINARY '%amazon%' OR BINARY LOWER(TRIM(es.platform)) LIKE BINARY '%prime%') AND LOWER(p.name) = 'prime')
    OR (BINARY LOWER(TRIM(es.platform)) = BINARY 'crunchyroll' AND LOWER(p.name) = 'crunchyroll')
    OR (BINARY LOWER(TRIM(es.platform)) = BINARY 'chatgpt' AND LOWER(p.name) = 'chatgpt')
    OR (BINARY LOWER(TRIM(es.platform)) IN (BINARY 'paramount', BINARY 'paramount+') AND LOWER(p.name) = 'paramount')
    OR (BINARY LOWER(TRIM(es.platform)) = BINARY 'spotify' AND LOWER(p.name) = 'spotify')
    OR (BINARY LOWER(TRIM(es.platform)) = BINARY 'canva' AND LOWER(p.name) = 'canva')
    OR (BINARY LOWER(TRIM(es.platform)) IN (BINARY 'hbo max', BINARY 'max') AND LOWER(p.name) = 'max')
    OR (BINARY LOWER(TRIM(es.platform)) = BINARY LOWER(TRIM(p.display_name)))
)
WHERE es.active = 1
  AND p.id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM email_subjects es2 
      WHERE es2.platform_id = p.id 
      AND BINARY LOWER(TRIM(es2.subject_line)) = BINARY LOWER(TRIM(es.subject_line))
  );
