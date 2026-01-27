-- ============================================
-- Tabla de Accesos de Usuario
-- Almacena las credenciales (correo, contraseña/usuario IMAP, plataforma)
-- que permiten a los usuarios consultar códigos
-- ============================================

USE pocoavbb_gac;

CREATE TABLE IF NOT EXISTS user_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL COMMENT 'Usuario IMAP o contraseña',
    platform_id INT NOT NULL,
    enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (platform_id) REFERENCES platforms(id) ON DELETE CASCADE,
    INDEX idx_email (email),
    INDEX idx_platform_id (platform_id),
    INDEX idx_enabled (enabled),
    UNIQUE KEY unique_email_platform (email, platform_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
