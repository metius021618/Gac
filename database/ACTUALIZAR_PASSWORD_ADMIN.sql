-- ============================================
-- Actualizar contraseña del usuario admin
-- Contraseña: admin1234@
-- ============================================

USE pocoavbb_gac;

-- Actualizar contraseña del usuario admin
-- Hash bcrypt de: admin1234@
UPDATE users 
SET password = '$2y$10$KlnYTLIt5dVbRfVR96Tyo.jBu6H2x28RwLzjZdPmaV4cVlFMGXhyy',
    updated_at = NOW()
WHERE username = 'admin';

-- Verificar que se actualizó correctamente
SELECT id, username, email, active, role_id,
       LENGTH(password) as password_length,
       LEFT(password, 20) as password_preview
FROM users 
WHERE username = 'admin';
