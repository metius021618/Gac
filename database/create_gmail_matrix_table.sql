-- ============================================
-- GAC - Tabla cuenta Gmail matriz (única fuente de verdad)
-- Una sola fila: indica qué cuenta de email_accounts es la matriz.
-- No se usa user_access ni provider_config.is_master para la matriz.
-- ============================================

-- Si tu BD tiene nombre fijo, descomenta y ajusta:
-- USE pocoavbb_gac;

CREATE TABLE IF NOT EXISTS gmail_matrix (
    id INT PRIMARY KEY DEFAULT 1,
    email_account_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (email_account_id) REFERENCES email_accounts(id) ON DELETE CASCADE,
    INDEX idx_email_account_id (email_account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Comentario: solo debe existir una fila (id=1). Al cambiar cuenta matriz
-- se hace UPDATE gmail_matrix SET email_account_id = :nuevo_id WHERE id = 1.
-- Si la tabla está vacía, el primer "set" hace INSERT (id=1, email_account_id=...).

-- Opcional: si ya tenías una cuenta Gmail conectada (con token), asignarla como matriz inicial
-- (ejecutar solo una vez después de crear la tabla)
-- INSERT INTO gmail_matrix (id, email_account_id, created_at, updated_at)
-- SELECT 1, id, NOW(), NOW() FROM email_accounts
-- WHERE type = 'gmail' AND enabled = 1
--   AND oauth_refresh_token IS NOT NULL AND TRIM(oauth_refresh_token) != ''
-- ORDER BY created_at ASC LIMIT 1
-- ON DUPLICATE KEY UPDATE email_account_id = VALUES(email_account_id), updated_at = NOW();
