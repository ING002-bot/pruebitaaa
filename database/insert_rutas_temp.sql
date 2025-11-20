SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

INSERT INTO rutas (nombre, zona, ubicaciones, descripcion, fecha_ruta, estado, creado_por) VALUES
('URBANO', 'URBANO', 'Chiclayo, Leonardo Ortiz, La Victoria, Santa Victoria', 'Cobertura completa zona urbana', CURDATE(), 'planificada', 1),
('PUEBLOS', 'PUEBLOS', 'Lambayeque, Mochumi, Túcume, Íllimo, Nueva Arica, Jayanca, Púcara, Mórrope, Motupe, Olmos, Salas', 'Cobertura completa de pueblos', CURDATE(), 'planificada', 1),
('PLAYAS', 'PLAYAS', 'San José, Santa Rosa, Pimentel, Reque, Monsefú, Eten, Puerto Eten', 'Cobertura completa zona de playas', CURDATE(), 'planificada', 1),
('COOPERATIVAS', 'COOPERATIVAS', 'Pomalca, Tumán, Pátapo, Pucalá, Saltur, Chongoyape', 'Cobertura completa de cooperativas', CURDATE(), 'planificada', 1),
('EXCOOPERATIVAS', 'EXCOOPERATIVAS', 'Ucupe, Mocupe, Zaña, Cayaltí, Oyotún, Lagunas', 'Cobertura completa de ex-cooperativas', CURDATE(), 'planificada', 1),
('FERREÑAFE', 'FERREÑAFE', 'Ferreñafe, Picsi, Pítipo, Motupillo, Pueblo Nuevo', 'Cobertura completa de Ferreñafe', CURDATE(), 'planificada', 1);
