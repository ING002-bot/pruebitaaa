-- Crear tabla de caja chica
CREATE TABLE IF NOT EXISTS caja_chica (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipo ENUM('asignacion', 'gasto', 'devolucion') NOT NULL,
    monto DECIMAL(10, 2) NOT NULL,
    concepto TEXT NOT NULL,
    descripcion TEXT,
    foto_comprobante VARCHAR(255),
    asignado_por INT COMMENT 'ID del admin que asigna',
    asignado_a INT COMMENT 'ID del asistente que recibe',
    registrado_por INT NOT NULL COMMENT 'ID del usuario que registra',
    asignacion_padre_id INT COMMENT 'ID de la asignación original para gastos',
    fecha_operacion DATETIME NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asignado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (asignado_a) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (asignacion_padre_id) REFERENCES caja_chica(id) ON DELETE SET NULL,
    INDEX idx_tipo (tipo),
    INDEX idx_asignado_a (asignado_a),
    INDEX idx_fecha (fecha_operacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear vista para saldo actual por asistente
CREATE OR REPLACE VIEW saldo_caja_chica AS
SELECT 
    asignado_a as asistente_id,
    u.nombre,
    u.apellido,
    COALESCE(SUM(CASE WHEN tipo = 'asignacion' THEN monto ELSE 0 END), 0) as total_asignado,
    COALESCE(SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END), 0) as total_gastado,
    COALESCE(SUM(CASE WHEN tipo = 'devolucion' THEN monto ELSE 0 END), 0) as total_devuelto,
    (
        COALESCE(SUM(CASE WHEN tipo = 'asignacion' THEN monto ELSE 0 END), 0) - 
        COALESCE(SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END), 0) - 
        COALESCE(SUM(CASE WHEN tipo = 'devolucion' THEN monto ELSE 0 END), 0)
    ) as saldo_actual
FROM caja_chica cc
INNER JOIN usuarios u ON cc.asignado_a = u.id
GROUP BY asignado_a, u.nombre, u.apellido;

-- Crear directorio para comprobantes (se debe crear manualmente)
-- mkdir uploads/caja_chica/
