-- Rol Comprador para el sistema GAC (si no existe)
INSERT INTO roles (name, display_name, description)
SELECT 'comprador', 'Comprador', 'Rol de comprador: acceso a consulta de códigos'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name = 'comprador' LIMIT 1);
