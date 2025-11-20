-- Insertar rutas predefinidas con zonas y ubicaciones

-- RUTAS URBANAS
INSERT INTO rutas (nombre, zona, ubicaciones, descripcion, fecha_ruta, estado, creado_por) VALUES
('URBANO - Chiclayo Centro', 'URBANO', 'Chiclayo', 'Ruta urbana principal de Chiclayo', CURDATE(), 'planificada', 1),
('URBANO - Leonardo Ortiz', 'URBANO', 'Leonardo Ortiz', 'Zona Leonardo Ortiz', CURDATE(), 'planificada', 1),
('URBANO - La Victoria', 'URBANO', 'La Victoria', 'Distrito La Victoria', CURDATE(), 'planificada', 1),
('URBANO - Santa Victoria', 'URBANO', 'Santa Victoria', 'Zona Santa Victoria', CURDATE(), 'planificada', 1);

-- RUTAS PUEBLOS
INSERT INTO rutas (nombre, zona, ubicaciones, descripcion, fecha_ruta, estado, creado_por) VALUES
('PUEBLOS - Norte 1', 'PUEBLOS', 'Lambayeque, Mochumi, Tucume', 'Ruta norte de pueblos', CURDATE(), 'planificada', 1),
('PUEBLOS - Norte 2', 'PUEBLOS', 'Illimo, Nueva Arica, Jayanca', 'Ruta norte secundaria', CURDATE(), 'planificada', 1),
('PUEBLOS - Centro', 'PUEBLOS', 'Pucara, Morrope', 'Ruta central de pueblos', CURDATE(), 'planificada', 1),
('PUEBLOS - Sur', 'PUEBLOS', 'Motupe, Olmos, Salas', 'Ruta sur de pueblos', CURDATE(), 'planificada', 1);

-- RUTAS PLAYAS
INSERT INTO rutas (nombre, zona, ubicaciones, descripcion, fecha_ruta, estado, creado_por) VALUES
('PLAYAS - Norte', 'PLAYAS', 'San Jose, Santa Rosa, Pimentel', 'Zona de playas norte', CURDATE(), 'planificada', 1),
('PLAYAS - Centro', 'PLAYAS', 'Reque, Monsefu', 'Zona de playas centro', CURDATE(), 'planificada', 1),
('PLAYAS - Sur', 'PLAYAS', 'Eten, Puerto Eten', 'Zona de playas sur', CURDATE(), 'planificada', 1);

-- RUTAS COOPERATIVAS
INSERT INTO rutas (nombre, zona, ubicaciones, descripcion, fecha_ruta, estado, creado_por) VALUES
('COOPERATIVAS - Norte', 'COOPERATIVAS', 'Pomalca, Tuman, Patapo', 'Cooperativas zona norte', CURDATE(), 'planificada', 1),
('COOPERATIVAS - Sur', 'COOPERATIVAS', 'Pucala, Saltur, Chongoyape', 'Cooperativas zona sur', CURDATE(), 'planificada', 1);

-- RUTAS EXCOOPERATIVAS
INSERT INTO rutas (nombre, zona, ubicaciones, descripcion, fecha_ruta, estado, creado_por) VALUES
('EXCOOPERATIVAS - Norte', 'EXCOOPERATIVAS', 'Ucupe, Mocupe, Zaña', 'Ex-cooperativas norte', CURDATE(), 'planificada', 1),
('EXCOOPERATIVAS - Sur', 'EXCOOPERATIVAS', 'Cayalti, Oyotun, Lagunas', 'Ex-cooperativas sur', CURDATE(), 'planificada', 1);

-- RUTAS FERREÑAFE
INSERT INTO rutas (nombre, zona, ubicaciones, descripcion, fecha_ruta, estado, creado_por) VALUES
('FERREÑAFE - Principal', 'FERREÑAFE', 'Ferreñafe, Picsi, Pitipo', 'Ruta principal Ferreñafe', CURDATE(), 'planificada', 1),
('FERREÑAFE - Secundaria', 'FERREÑAFE', 'Motupillo, Pueblo Nuevo', 'Ruta secundaria Ferreñafe', CURDATE(), 'planificada', 1);
