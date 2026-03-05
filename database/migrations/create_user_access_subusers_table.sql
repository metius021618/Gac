-- ============================================
-- Tabla de Subusuarios de Acceso
-- Cada fila representa un "usuario adicional" asociado
-- a un registro de user_access concreto (correo + plataforma).
-- Estos subusuarios podrán consultar códigos igual que
-- el usuario principal.
-- ============================================

CREATE TABLE IF NOT EXISTS user_access_subusers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_access_id INT NOT NULL,
    username VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_access_id) REFERENCES user_access(id) ON DELETE CASCADE,
    INDEX idx_user_access_id (user_access_id),
    UNIQUE KEY ux_user_access_username (user_access_id, username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

