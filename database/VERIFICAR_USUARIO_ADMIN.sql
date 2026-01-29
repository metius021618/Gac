-- ============================================
-- Verificar usuario admin en la base de datos
-- ============================================

USE pocoavbb_gac;

-- Verificar si el usuario admin existe
SELECT id, username, email, active, role_id, created_at 
FROM users 
WHERE username = 'admin' OR email LIKE '%admin%';

-- Verificar si existe el rol de administrador
SELECT * FROM roles WHERE id = 1 OR name = 'admin';

-- Verificar estructura de la tabla users
DESCRIBE users;

-- Si el usuario no existe, crearlo
-- (Asegúrate de que el rol con id=1 exista primero)
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

-- Verificar después de insertar
SELECT id, username, email, active, role_id, 
       LENGTH(password) as password_length,
       LEFT(password, 10) as password_preview
FROM users 
WHERE username = 'admin';
