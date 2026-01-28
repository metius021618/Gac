-- ============================================
-- GAC - Tabla de Configuración del Sistema
-- ============================================

USE pocoavbb_gac;

CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    `value` TEXT NOT NULL,
    description TEXT,
    category VARCHAR(50) DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (`key`),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Insertar configuración inicial de tiempo de sesión (1 hora por defecto)
INSERT INTO settings (`key`, `value`, description, category) 
VALUES ('session_timeout_hours', '1', 'Tiempo en horas que se mantiene activa la sesión del usuario', 'session')
ON DUPLICATE KEY UPDATE `value` = '1';
