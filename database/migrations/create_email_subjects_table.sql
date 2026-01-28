-- GAC - Migración: Crear tabla email_subjects
-- Tabla para almacenar asuntos de correo por plataforma

USE pocoavbb_gac;

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
