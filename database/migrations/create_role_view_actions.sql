-- Tabla para acciones permitidas por vista y rol (role_view_actions)
CREATE TABLE IF NOT EXISTS role_view_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    view_key VARCHAR(80) NOT NULL,
    action VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_role_view_action (role_id, view_key, action),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    INDEX idx_role_id (role_id),
    INDEX idx_view_action (view_key, action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
