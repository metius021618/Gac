-- Tabla para personalización de vistas por rol (qué secciones puede ver cada rol)
CREATE TABLE IF NOT EXISTS role_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    view_key VARCHAR(80) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_role_view (role_id, view_key),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    INDEX idx_role_id (role_id),
    INDEX idx_view_key (view_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
