-- ============================================
-- Diagnóstico de tabla settings
-- ============================================

USE pocoavbb_gac;

-- 1. Verificar si la tabla existe
SELECT 'Verificando si la tabla existe...' AS paso;
SHOW TABLES LIKE 'settings';

-- 2. Si existe, ver su estructura
SELECT 'Estructura actual de la tabla (si existe):' AS paso;
DESCRIBE settings;

-- 3. Ver todas las columnas
SELECT 'Columnas de la tabla:' AS paso;
SHOW COLUMNS FROM settings;

-- 4. Si la tabla existe pero tiene estructura incorrecta, eliminarla primero
-- (CUIDADO: Esto eliminará datos si existen)
-- DROP TABLE IF EXISTS settings;

-- 5. Crear la tabla con la estructura correcta
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Clave de configuración',
    `value` TEXT NOT NULL COMMENT 'Valor de configuración',
    description TEXT COMMENT 'Descripción de la configuración',
    category VARCHAR(50) DEFAULT 'general' COMMENT 'Categoría de la configuración',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (`key`),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- 6. Verificar que se creó correctamente
SELECT 'Estructura después de crear/verificar:' AS paso;
DESCRIBE settings;

-- 7. Insertar configuración inicial
INSERT INTO settings (`key`, `value`, description, category) 
VALUES ('session_timeout_hours', '1', 'Tiempo en horas que se mantiene activa la sesión del usuario', 'session')
ON DUPLICATE KEY UPDATE `value` = '1', updated_at = NOW();

-- 8. Verificar datos
SELECT 'Datos en la tabla:' AS paso;
SELECT * FROM settings;
