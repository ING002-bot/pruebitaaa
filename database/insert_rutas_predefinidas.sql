-- Insertar rutas predefinidas con zonas y ubicaciones (una ruta por zona con todas sus ubicaciones)
-- Configurar UTF-8 para caracteres especiales
SET NAMES utf8mb4;

-- RUTA URBANO (todas las ubicaciones urbanas)
INSERT INTO rutas (nombre, zona, ubicaciones, descripcion, fecha_ruta, estado, creado_por) VALUES
('URBANO', 'URBANO', 'Chiclayo, Leonardo Ortiz, La Victoria, Santa Victoria', 'Cobertura completa zona urbana', CURDATE(), 'planificada', 1);

-- RUTA PUEBLOS (todos los pueblos)
INSERT INTO rutas (nombre, zona, ubicaciones, descripcion, fecha_ruta, estado, creado_por) VALUES
('PUEBLOS', 'PUEBLOS', 'Lambayeque, Mochumi, Túcume, Íllimo, Nueva Arica, Jayanca, Púcara, Mórrope, Motupe, Olmos, Salas', 'Cobertura completa de pueblos', CURDATE(), 'planificada', 1);

-- RUTA PLAYAS (todas las playas)
INSERT INTO rutas (nombre, zona, ubicaciones, descripcion, fecha_ruta, estado, creado_por) VALUES
('PLAYAS', 'PLAYAS', 'San José, Santa Rosa, Pimentel, Reque, Monsefú, Eten, Puerto Eten', 'Cobertura completa zona de playas', CURDATE(), 'planificada', 1);

-- RUTA COOPERATIVAS (todas las cooperativas)
INSERT INTO rutas (nombre, zona, ubicaciones, descripcion, fecha_ruta, estado, creado_por) VALUES
('COOPERATIVAS', 'COOPERATIVAS', 'Pomalca, Tumán, Pátapo, Pucalá, Saltur, Chongoyape', 'Cobertura completa de cooperativas', CURDATE(), 'planificada', 1);

-- RUTA EXCOOPERATIVAS (todas las ex-cooperativas)
INSERT INTO rutas (nombre, zona, ubicaciones, descripcion, fecha_ruta, estado, creado_por) VALUES
('EXCOOPERATIVAS', 'EXCOOPERATIVAS', 'Ucupe, Mocupe, Zaña, Cayaltí, Oyotún, Lagunas', 'Cobertura completa de ex-cooperativas', CURDATE(), 'planificada', 1);

-- RUTA FERREÑAFE (todas las ubicaciones de Ferreñafe)
INSERT INTO rutas (nombre, zona, ubicaciones, descripcion, fecha_ruta, estado, creado_por) VALUES
('FERREÑAFE', 'FERREÑAFE', 'Ferreñafe, Picsi, Pítipo, Motupillo, Pueblo Nuevo', 'Cobertura completa de Ferreñafe', CURDATE(), 'planificada', 1);
