-- Crear tabla de zonas y tarifas
CREATE TABLE IF NOT EXISTS zonas_tarifas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    categoria VARCHAR(50) NOT NULL COMMENT 'URBANO, PUEBLOS, PLAYAS, COOPERATIVAS, EXCOPERATIVAS, FERREÑAFE',
    nombre_zona VARCHAR(100) NOT NULL,
    tipo_envio VARCHAR(50) DEFAULT 'Paquete',
    tarifa_repartidor DECIMAL(10, 2) NOT NULL COMMENT 'Monto que recibe el repartidor por entrega',
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_categoria (categoria),
    INDEX idx_activo (activo),
    UNIQUE KEY unique_zona (categoria, nombre_zona)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar zonas predefinidas según la imagen
INSERT INTO zonas_tarifas (categoria, nombre_zona, tipo_envio, tarifa_repartidor) VALUES
-- URBANO
('URBANO', 'Chiclayo', 'Paquete', 1.00),
('URBANO', 'Leonardo Ortiz', 'Paquete', 1.00),
('URBANO', 'La Victoria', 'Paquete', 1.00),
('URBANO', 'Santa Victoria', 'Paquete', 1.00),

-- PUEBLOS
('PUEBLOS', 'Lambayeque', 'Paquete', 3.00),
('PUEBLOS', 'Mochumi', 'Paquete', 5.00),
('PUEBLOS', 'Tucume', 'Paquete', 5.00),
('PUEBLOS', 'Illimo', 'Paquete', 5.00),
('PUEBLOS', 'Nueva Arica', 'Paquete', 5.00),
('PUEBLOS', 'Jayanca', 'Paquete', 5.00),
('PUEBLOS', 'Pacora', 'Paquete', 5.00),
('PUEBLOS', 'Morrope', 'Paquete', 5.00),
('PUEBLOS', 'Motupe', 'Paquete', 5.00),
('PUEBLOS', 'Olmos', 'Paquete', 5.00),
('PUEBLOS', 'Salas', 'Paquete', 5.00),

-- PLAYAS
('PLAYAS', 'San Jose', 'Paquete', 3.00),
('PLAYAS', 'Santa Rosa', 'Paquete', 3.00),
('PLAYAS', 'Pimentel', 'Paquete', 3.00),
('PLAYAS', 'Reque', 'Paquete', 3.00),
('PLAYAS', 'Monsefu', 'Paquete', 3.00),
('PLAYAS', 'Eten', 'Paquete', 5.00),
('PLAYAS', 'Puerto Eten', 'Paquete', 5.00),

-- COOPERATIVAS
('COOPERATIVAS', 'Pomalca', 'Paquete', 3.00),
('COOPERATIVAS', 'Tuman', 'Paquete', 5.00),
('COOPERATIVAS', 'Patapo', 'Paquete', 5.00),
('COOPERATIVAS', 'Pucala', 'Paquete', 5.00),
('COOPERATIVAS', 'Sartur', 'Paquete', 5.00),
('COOPERATIVAS', 'Chongoyape', 'Paquete', 5.00),

-- EXCOPERATIVAS
('EXCOPERATIVAS', 'Ucupe', 'Paquete', 5.00),
('EXCOPERATIVAS', 'Mocupe', 'Paquete', 5.00),
('EXCOPERATIVAS', 'Zaña', 'Paquete', 5.00),
('EXCOPERATIVAS', 'Cayalti', 'Paquete', 5.00),
('EXCOPERATIVAS', 'Oyotun', 'Paquete', 5.00),
('EXCOPERATIVAS', 'Lagunas', 'Paquete', 5.00),

-- FERREÑAFE
('FERREÑAFE', 'Ferreñafe', 'Paquete', 5.00),
('FERREÑAFE', 'Picsi', 'Paquete', 5.00),
('FERREÑAFE', 'Pitipo', 'Paquete', 5.00),
('FERREÑAFE', 'Motupillo', 'Paquete', 5.00),
('FERREÑAFE', 'Pueblo Nuevo', 'Paquete', 5.00)
ON DUPLICATE KEY UPDATE 
    tarifa_repartidor = VALUES(tarifa_repartidor),
    fecha_actualizacion = CURRENT_TIMESTAMP;

-- Agregar columna zona_tarifa_id a la tabla paquetes (si no existe)
ALTER TABLE paquetes 
ADD COLUMN IF NOT EXISTS zona_tarifa_id INT NULL AFTER ciudad,
ADD FOREIGN KEY (zona_tarifa_id) REFERENCES zonas_tarifas(id) ON DELETE SET NULL;
