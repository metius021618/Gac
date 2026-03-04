-- Tabla revendedores (análisis dashboard)
CREATE TABLE IF NOT EXISTS analisis_revendedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    foto_url VARCHAR(500) NULL,
    telefono VARCHAR(50) NULL,
    estado TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Tabla ventas (análisis dashboard; plataforma_id referencia platforms)
CREATE TABLE IF NOT EXISTS analisis_ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plataforma_id INT NOT NULL,
    revendedor_id INT NOT NULL,
    fecha_venta DATE NOT NULL,
    precio_venta DECIMAL(12,2) NOT NULL DEFAULT 0,
    cliente_final VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plataforma_id) REFERENCES platforms(id) ON DELETE CASCADE,
    FOREIGN KEY (revendedor_id) REFERENCES analisis_revendedores(id) ON DELETE CASCADE,
    INDEX idx_fecha (fecha_venta),
    INDEX idx_plataforma (plataforma_id),
    INDEX idx_revendedor (revendedor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
