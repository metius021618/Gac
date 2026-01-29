-- ============================================
-- Query para insertar usuario admin
-- Usuario: admin
-- Contraseña: admin1234@
-- ============================================

USE pocoavbb_gac;

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
