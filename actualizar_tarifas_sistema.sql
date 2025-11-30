-- ====================================================================
-- ACTUALIZACIÓN DE TARIFAS SEGÚN NUEVA LISTA DE PRECIOS
-- ====================================================================

-- Eliminar todas las tarifas existentes
DELETE FROM zonas_tarifas WHERE 1=1;

-- Insertar nuevas tarifas según la lista proporcionada
INSERT INTO zonas_tarifas (categoria, nombre_zona, tipo_envio, tarifa_repartidor, costo_cliente, activo) VALUES

-- URBANO (S/ 3.00)
('URBANO', 'Chiclayo', 'Paquete', 2.50, 3.00, 1),
('URBANO', 'Leonardo Ortiz', 'Paquete', 2.50, 3.00, 1),
('URBANO', 'La Victoria', 'Paquete', 2.50, 3.00, 1),
('URBANO', 'Santa Victoria', 'Paquete', 2.50, 3.00, 1),

-- PUEBLOS (S/ 5.00 - S/ 8.00)
('PUEBLOS', 'Lambayeque', 'Paquete', 4.00, 5.00, 1),
('PUEBLOS', 'Mochumi', 'Paquete', 6.00, 8.00, 1),
('PUEBLOS', 'Tucume', 'Paquete', 6.00, 8.00, 1),
('PUEBLOS', 'Illimo', 'Paquete', 6.00, 8.00, 1),
('PUEBLOS', 'Nueva Arica', 'Paquete', 6.00, 8.00, 1),
('PUEBLOS', 'Jayanca', 'Paquete', 6.00, 8.00, 1),
('PUEBLOS', 'Pacora', 'Paquete', 6.00, 8.00, 1),
('PUEBLOS', 'Morrope', 'Paquete', 6.00, 8.00, 1),
('PUEBLOS', 'Motupe', 'Paquete', 6.00, 8.00, 1),
('PUEBLOS', 'Olmos', 'Paquete', 6.00, 8.00, 1),
('PUEBLOS', 'Salas', 'Paquete', 6.00, 8.00, 1),

-- PLAYAS (S/ 5.00 - S/ 8.00)
('PLAYAS', 'San Jose', 'Paquete', 4.00, 5.00, 1),
('PLAYAS', 'Santa Rosa', 'Paquete', 4.00, 5.00, 1),
('PLAYAS', 'Pimentel', 'Paquete', 4.00, 5.00, 1),
('PLAYAS', 'Reque', 'Paquete', 4.00, 5.00, 1),
('PLAYAS', 'Monsefu', 'Paquete', 4.00, 5.00, 1),
('PLAYAS', 'Eten', 'Paquete', 6.00, 8.00, 1),
('PLAYAS', 'Puerto Eten', 'Paquete', 6.00, 8.00, 1),

-- COOPERATIVAS (S/ 5.00 - S/ 8.00)
('COOPERATIVAS', 'Pomalca', 'Paquete', 4.00, 5.00, 1),
('COOPERATIVAS', 'Tuman', 'Paquete', 6.00, 8.00, 1),
('COOPERATIVAS', 'Patapo', 'Paquete', 6.00, 8.00, 1),
('COOPERATIVAS', 'Pucala', 'Paquete', 6.00, 8.00, 1),
('COOPERATIVAS', 'Sartur', 'Paquete', 6.00, 8.00, 1),
('COOPERATIVAS', 'Chongoyape', 'Paquete', 6.00, 8.00, 1),

-- EXCOPERATIVAS (S/ 8.00)
('EXCOPERATIVAS', 'Ucupe', 'Paquete', 6.00, 8.00, 1),
('EXCOPERATIVAS', 'Mocupe', 'Paquete', 6.00, 8.00, 1),
('EXCOPERATIVAS', 'Zaña', 'Paquete', 6.00, 8.00, 1),
('EXCOPERATIVAS', 'Cayalti', 'Paquete', 6.00, 8.00, 1),
('EXCOPERATIVAS', 'Oyotun', 'Paquete', 6.00, 8.00, 1),
('EXCOPERATIVAS', 'Lagunas', 'Paquete', 6.00, 8.00, 1),

-- FERREÑAFE (S/ 8.00)
('FERREÑAFE', 'Ferreñafe', 'Paquete', 6.00, 8.00, 1),
('FERREÑAFE', 'Picsi', 'Paquete', 6.00, 8.00, 1),
('FERREÑAFE', 'Pitipo', 'Paquete', 6.00, 8.00, 1),
('FERREÑAFE', 'Motupillo', 'Paquete', 6.00, 8.00, 1),
('FERREÑAFE', 'Pueblo Nuevo', 'Paquete', 6.00, 8.00, 1);

-- Agregar columna costo_cliente si no existe
ALTER TABLE zonas_tarifas ADD COLUMN IF NOT EXISTS costo_cliente DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Precio que se cobra al cliente';

-- Actualizar índices
ALTER TABLE zonas_tarifas ADD INDEX IF NOT EXISTS idx_costo_cliente (costo_cliente);

-- Verificar la inserción
SELECT 
    categoria,
    COUNT(*) as cantidad_zonas,
    MIN(costo_cliente) as precio_min,
    MAX(costo_cliente) as precio_max
FROM zonas_tarifas 
WHERE activo = 1 
GROUP BY categoria 
ORDER BY categoria;