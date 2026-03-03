-- Columna para mostrar en Correos Registrados el admin que hizo la última modificación
ALTER TABLE user_access ADD COLUMN updated_by_username VARCHAR(255) NULL AFTER updated_at;
