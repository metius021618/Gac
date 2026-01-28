-- ============================================
-- QUERY SIMPLE: Migrar email_subjects
-- De: pocoavbb_codes544shd.email_subjects
-- A:   pocoavbb_gac.email_subjects
-- ============================================

USE pocoavbb_gac;

-- INSERTAR datos migrando platform (VARCHAR) → platform_id (INT)
-- NOTA: Usa COLLATE para evitar errores de mezcla de cotejamientos
INSERT INTO email_subjects (platform_id, subject_line, active, created_at)
SELECT DISTINCT
    p.id as platform_id,
    es.subject_line,
    COALESCE(es.active, 1) as active,
    NOW() as created_at
FROM pocoavbb_codes544shd.email_subjects es
INNER JOIN platforms p ON (
    -- Mapeo de nombres de plataformas (con COLLATE para evitar errores)
    (LOWER(TRIM(es.platform) COLLATE utf8mb4_unicode_ci) = 'netflix' COLLATE utf8mb4_unicode_ci AND LOWER(p.name) = 'netflix')
    OR (LOWER(TRIM(es.platform) COLLATE utf8mb4_unicode_ci) IN ('disney', 'disney+') AND LOWER(p.name) = 'disney')
    OR ((LOWER(TRIM(es.platform) COLLATE utf8mb4_unicode_ci) LIKE '%amazon%' OR LOWER(TRIM(es.platform) COLLATE utf8mb4_unicode_ci) LIKE '%prime%') AND LOWER(p.name) = 'prime')
    OR (LOWER(TRIM(es.platform) COLLATE utf8mb4_unicode_ci) = 'crunchyroll' COLLATE utf8mb4_unicode_ci AND LOWER(p.name) = 'crunchyroll')
    OR (LOWER(TRIM(es.platform) COLLATE utf8mb4_unicode_ci) = 'chatgpt' COLLATE utf8mb4_unicode_ci AND LOWER(p.name) = 'chatgpt')
    OR (LOWER(TRIM(es.platform) COLLATE utf8mb4_unicode_ci) IN ('paramount', 'paramount+') AND LOWER(p.name) = 'paramount')
    OR (LOWER(TRIM(es.platform) COLLATE utf8mb4_unicode_ci) = 'spotify' COLLATE utf8mb4_unicode_ci AND LOWER(p.name) = 'spotify')
    OR (LOWER(TRIM(es.platform) COLLATE utf8mb4_unicode_ci) = 'canva' COLLATE utf8mb4_unicode_ci AND LOWER(p.name) = 'canva')
    OR (LOWER(TRIM(es.platform) COLLATE utf8mb4_unicode_ci) IN ('hbo max', 'max') AND LOWER(p.name) = 'max')
    OR (LOWER(TRIM(es.platform) COLLATE utf8mb4_unicode_ci) = LOWER(TRIM(p.display_name) COLLATE utf8mb4_unicode_ci))
)
WHERE es.active = 1
  AND p.id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM email_subjects es2 
      WHERE es2.platform_id = p.id 
      AND LOWER(TRIM(es2.subject_line) COLLATE utf8mb4_unicode_ci) = LOWER(TRIM(es.subject_line) COLLATE utf8mb4_unicode_ci)
  );
