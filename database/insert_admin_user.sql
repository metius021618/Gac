-- ============================================
-- GAC - Insertar Usuario Administrador
-- ============================================
-- Usuario: admin
-- Contraseña: admin1234@
-- Email: admin@gac.pocoyoni.com

USE pocoavbb_gac;

-- Verificar que exista el rol de administrador (id=1)
-- Si no existe, crearlo primero
INSERT INTO roles (id, name, display_name, description) 
VALUES (1, 'admin', 'Administrador', 'Rol de administrador con todos los permisos')
ON DUPLICATE KEY UPDATE name = 'admin';

-- Insertar usuario administrador
-- La contraseña 'admin1234@' está encriptada con bcrypt
INSERT INTO users (username, email, password, role_id, active) 
VALUES (
    'admin', 
    'admin@gac.pocoyoni.com', 
    '$2y$10$KlnYTLIt5dVbRfVR96Tyo.jBu6H2x28RwLzjZdPmaV4cVlFMGXhyy', 
    1, 
    1
)
ON DUPLICATE KEY UPDATE 
    password = '$2y$10$KlnYTLIt5dVbRfVR96Tyo.jBu6H2x28RwLzjZdPmaV4cVlFMGXhyy',
    active = 1,
    updated_at = NOW();

-- Verificar inserción
SELECT id, username, email, role_id, active, created_at 
FROM users 
WHERE username = 'admin';
