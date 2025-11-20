-- Insertar rutas predefinidas con zonas y ubicaciones (una ruta por zona con todas sus ubicaciones)

-- RUTA URBANO (todas las ubicaciones urbanas)
INSERT INTO rutas (nombre, zona, ubicaciones, descripcion, fecha_ruta, estado, creado_por) VALUES
('URBANO', 'URBANO', 'Chiclayo, Leonardo Ortiz, La Victoria, Santa Victoria', 'Cobertura completa zona urbana', CURDATE(), 'planificada', 1);

-- RUTA PUEBLOS (todos los pueblos)
INSERT INTO rutas (nombre, zona, ubicaciones, descripcion, fecha_ruta, estado, creado_por) VALUES
('PUEBLOS', 'PUEBLOS', 'Lambayeque, Mochumi, Tucume, Illimo, Nueva Arica, Jayanca, Pucara, Morrope, Motupe, Olmos, Salas', 'Cobertura completa de pueblos', CURDATE(), 'planificada', 1);

-- RUTA PLAYAS (todas las playas)
INSERT INTO rutas (nombre, zona, ubicaciones, descripcion, fecha_ruta, estado, creado_por) VALUES
('PLAYAS', 'PLAYAS', 'San Jose, Santa Rosa, Pimentel, Reque, Monsefu, Eten, Puerto Eten', 'Cobertura completa zona de playas', CURDATE(), 'planificada', 1);

-- RUTA COOPERATIVAS (todas las cooperativas)
INSERT INTO rutas (nombre, zona, ubicaciones, descripcion, fecha_ruta, estado, creado_por) VALUES
('COOPERATIVAS', 'COOPERATIVAS', 'Pomalca, Tuman, Patapo, Pucala, Saltur, Chongoyape', 'Cobertura completa de cooperativas', CURDATE(), 'planificada', 1);

-- RUTA EXCOOPERATIVAS (todas las ex-cooperativas)
INSERT INTO rutas (nombre, zona, ubicaciones, descripcion, fecha_ruta, estado, creado_por) VALUES
('EXCOOPERATIVAS', 'EXCOOPERATIVAS', 'Ucupe, Mocupe, Zaña, Cayalti, Oyotun, Lagunas', 'Cobertura completa de ex-cooperativas', CURDATE(), 'planificada', 1);

-- RUTA FERREÑAFE (todas las ubicaciones de Ferreñafe)
INSERT INTO rutas (nombre, zona, ubicaciones, descripcion, fecha_ruta, estado, creado_por) VALUES
('FERREÑAFE', 'FERREÑAFE', 'Ferreñafe, Picsi, Pitipo, Motupillo, Pueblo Nuevo', 'Cobertura completa de Ferreñafe', CURDATE(), 'planificada', 1);
