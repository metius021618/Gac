-- ============================================
-- GAC - Seed de Usuario Administrador
-- ============================================
-- Este script inserta el usuario administrador inicial
-- Usuario: admin
-- Contraseña: admin123

USE pocoavbb_gac;

-- Insertar usuario administrador
-- La contraseña 'admin123' está encriptada con bcrypt
INSERT INTO users (username, email, password, role_id, active) VALUES
('admin', 'admin@gac.pocoyoni.com', '$2y$10$zbhZnDqoS7OKq0vnhD1CTu7KvXR/.9BLCWt5xyVDAfqdF2tzWTlWO', 1, 1);

-- Nota: La contraseña encriptada corresponde a 'admin123'
-- Para generar una nueva contraseña encriptada, usar:
-- SELECT PASSWORD('tu_contraseña') en MySQL 5.7 o anterior
-- O usar PHP: password_hash('tu_contraseña', PASSWORD_BCRYPT)
