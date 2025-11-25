-- ====================================================================
-- HERMES EXPRESS LOGISTIC - Instalación Completa de Base de Datos
-- ====================================================================
-- Archivo consolidado que incluye:
-- 1. Schema principal
-- 2. Caja chica
-- 3. Zonas y tarifas
-- 4. Sistema de importación y notificaciones
-- 5. Actualizaciones de tablas
-- 6. Datos de prueba
-- ====================================================================

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS hermes_express CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hermes_express;

-- ====================================================================
-- PARTE 1: TABLAS PRINCIPALES
-- ====================================================================

-- Tabla de usuarios con roles
CREATE TABLE IF NOT EXISTS usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    telefono VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'asistente', 'repartidor') NOT NULL,
    foto_perfil VARCHAR(255) DEFAULT 'default.png',
    estado ENUM('activo', 'inactivo', 'suspendido') DEFAULT 'activo',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_rol (rol)
) ENGINE=InnoDB;

-- Tabla de paquetes
CREATE TABLE IF NOT EXISTS paquetes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo_seguimiento VARCHAR(100) UNIQUE NOT NULL,
    codigo_savar VARCHAR(100),
    archivo_importacion VARCHAR(255),
    destinatario_nombre VARCHAR(150) NOT NULL,
    destinatario_telefono VARCHAR(20),
    destinatario_email VARCHAR(150),
    direccion_completa TEXT NOT NULL,
    direccion_latitud DECIMAL(10, 8),
    direccion_longitud DECIMAL(11, 8),
    ciudad VARCHAR(100),
    provincia VARCHAR(100),
    zona_tarifa_id INT,
    codigo_postal VARCHAR(20),
    peso DECIMAL(8, 2),
    dimensiones VARCHAR(50),
    descripcion TEXT,
    valor_declarado DECIMAL(10, 2),
    costo_envio DECIMAL(10, 2),
    estado ENUM('pendiente', 'en_ruta', 'entregado', 'rezagado', 'devuelto', 'cancelado') DEFAULT 'pendiente',
    prioridad ENUM('normal', 'urgente', 'express') DEFAULT 'normal',
    repartidor_id INT,
    fecha_recepcion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_asignacion TIMESTAMP NULL,
    fecha_limite_entrega DATETIME,
    alerta_enviada BOOLEAN DEFAULT FALSE,
    notificacion_whatsapp_enviada BOOLEAN DEFAULT FALSE,
    fecha_entrega TIMESTAMP NULL,
    intentos_entrega INT DEFAULT 0,
    notas TEXT,
    INDEX idx_codigo (codigo_seguimiento),
    INDEX idx_estado (estado),
    INDEX idx_repartidor (repartidor_id),
    INDEX idx_fecha_entrega (fecha_entrega)
) ENGINE=InnoDB;

-- Tabla de rutas
CREATE TABLE IF NOT EXISTS rutas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    zona VARCHAR(50),
    ubicaciones TEXT,
    descripcion TEXT COMMENT 'Detalles adicionales de la ruta',
    repartidor_id INT,
    fecha_ruta DATE NOT NULL,
    hora_inicio TIME,
    hora_fin TIME,
    estado ENUM('planificada', 'en_progreso', 'completada', 'cancelada') DEFAULT 'planificada',
    total_paquetes INT DEFAULT 0,
    paquetes_entregados INT DEFAULT 0,
    distancia_total DECIMAL(10, 2),
    tiempo_estimado INT,
    creado_por INT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fecha (fecha_ruta),
    INDEX idx_repartidor (repartidor_id)
) ENGINE=InnoDB;

-- Tabla de paquetes en rutas
CREATE TABLE IF NOT EXISTS ruta_paquetes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ruta_id INT NOT NULL,
    paquete_id INT NOT NULL,
    orden_entrega INT,
    estado ENUM('pendiente', 'entregado', 'fallido') DEFAULT 'pendiente',
    INDEX idx_ruta (ruta_id),
    INDEX idx_paquete (paquete_id)
) ENGINE=InnoDB;

-- Tabla de entregas (con fotos y detalles)
CREATE TABLE IF NOT EXISTS entregas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    paquete_id INT NOT NULL,
    ruta_id INT,
    repartidor_id INT NOT NULL,
    fecha_entrega TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    receptor_nombre VARCHAR(150),
    receptor_dni VARCHAR(20),
    receptor_firma VARCHAR(255),
    foto_entrega VARCHAR(255),
    foto_adicional_1 VARCHAR(255),
    foto_adicional_2 VARCHAR(255),
    latitud_entrega DECIMAL(10, 8),
    longitud_entrega DECIMAL(11, 8),
    tipo_entrega ENUM('exitosa', 'parcial', 'rechazada', 'no_encontrado') NOT NULL,
    observaciones TEXT,
    tiempo_entrega INT COMMENT 'Tiempo en minutos',
    INDEX idx_paquete (paquete_id),
    INDEX idx_fecha (fecha_entrega)
) ENGINE=InnoDB;

-- Tabla de paquetes rezagados
CREATE TABLE IF NOT EXISTS paquetes_rezagados (
    id INT PRIMARY KEY AUTO_INCREMENT,
    paquete_id INT NOT NULL,
    motivo ENUM('direccion_incorrecta', 'destinatario_ausente', 'rechazo', 'zona_peligrosa', 'otros') NOT NULL,
    descripcion_motivo TEXT,
    fecha_rezago TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    intentos_realizados INT DEFAULT 1,
    proximo_intento DATE,
    solucionado BOOLEAN DEFAULT FALSE,
    fecha_solucion TIMESTAMP NULL,
    INDEX idx_paquete (paquete_id),
    INDEX idx_solucionado (solucionado)
) ENGINE=InnoDB;

-- Tabla de pagos a repartidores
CREATE TABLE IF NOT EXISTS pagos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    repartidor_id INT NOT NULL,
    concepto VARCHAR(200),
    periodo VARCHAR(100),
    monto DECIMAL(10, 2),
    periodo_inicio DATE NULL,
    periodo_fin DATE NULL,
    total_paquetes INT DEFAULT 0,
    monto_por_paquete DECIMAL(10, 2),
    bonificaciones DECIMAL(10, 2) DEFAULT 0,
    deducciones DECIMAL(10, 2) DEFAULT 0,
    total_pagar DECIMAL(10, 2) NULL,
    estado ENUM('pendiente', 'pagado', 'cancelado') DEFAULT 'pendiente',
    fecha_pago TIMESTAMP NULL,
    metodo_pago ENUM('efectivo', 'transferencia', 'cheque'),
    registrado_por INT,
    notas TEXT,
    generado_por INT,
    fecha_generacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_repartidor (repartidor_id),
    INDEX idx_estado (estado),
    INDEX idx_fecha_pago (fecha_pago),
    INDEX idx_registrado_por (registrado_por)
) ENGINE=InnoDB;

-- Tabla de ingresos de la empresa
CREATE TABLE IF NOT EXISTS ingresos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipo ENUM('envio', 'servicio_adicional', 'recargo', 'otros') NOT NULL,
    concepto VARCHAR(200) NOT NULL,
    monto DECIMAL(10, 2) NOT NULL,
    paquete_id INT,
    fecha_ingreso TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    registrado_por INT,
    notas TEXT,
    INDEX idx_fecha (fecha_ingreso),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB;

-- Tabla de gastos de la empresa
CREATE TABLE IF NOT EXISTS gastos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    categoria ENUM('combustible', 'mantenimiento', 'salarios', 'administracion', 'otros') NOT NULL,
    descripcion VARCHAR(200),
    concepto VARCHAR(200) NULL,
    monto DECIMAL(10, 2) NOT NULL,
    numero_comprobante VARCHAR(100),
    comprobante_archivo VARCHAR(255),
    fecha_gasto DATE NOT NULL,
    registrado_por INT,
    notas TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fecha (fecha_gasto),
    INDEX idx_categoria (categoria),
    INDEX idx_numero_comprobante (numero_comprobante)
) ENGINE=InnoDB;

-- Tabla de seguimiento de ubicación en tiempo real
CREATE TABLE IF NOT EXISTS ubicaciones_tiempo_real (
    id INT PRIMARY KEY AUTO_INCREMENT,
    repartidor_id INT NOT NULL,
    ruta_id INT,
    latitud DECIMAL(10, 8) NOT NULL,
    longitud DECIMAL(11, 8) NOT NULL,
    precision_metros DECIMAL(8, 2),
    velocidad DECIMAL(6, 2),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_repartidor_timestamp (repartidor_id, timestamp),
    INDEX idx_ruta (ruta_id)
) ENGINE=InnoDB;

-- Tabla de notificaciones
CREATE TABLE IF NOT EXISTS notificaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    tipo ENUM('info', 'alerta', 'urgente', 'sistema') DEFAULT 'info',
    titulo VARCHAR(200) NOT NULL,
    mensaje TEXT NOT NULL,
    leida BOOLEAN DEFAULT FALSE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario_leida (usuario_id, leida),
    INDEX idx_fecha (fecha_creacion)
) ENGINE=InnoDB;

-- Tabla de logs del sistema
CREATE TABLE IF NOT EXISTS logs_sistema (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT,
    accion VARCHAR(100) NOT NULL,
    tabla_afectada VARCHAR(50),
    registro_id INT,
    detalles TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha (fecha_accion)
) ENGINE=InnoDB;

-- Tabla de datos importados desde SAVAR
CREATE TABLE IF NOT EXISTS importaciones_savar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    datos_json LONGTEXT NOT NULL,
    total_registros INT,
    registros_procesados INT DEFAULT 0,
    registros_fallidos INT DEFAULT 0,
    estado ENUM('pendiente', 'procesando', 'completado', 'error') DEFAULT 'pendiente',
    fecha_importacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    procesado_por INT,
    errores TEXT,
    INDEX idx_fecha (fecha_importacion)
) ENGINE=InnoDB;

-- ====================================================================
-- PARTE 2: CAJA CHICA
-- ====================================================================

-- Tabla de caja chica
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
    INDEX idx_tipo (tipo),
    INDEX idx_asignado_a (asignado_a),
    INDEX idx_fecha (fecha_operacion)
) ENGINE=InnoDB;

-- ====================================================================
-- PARTE 3: ZONAS Y TARIFAS
-- ====================================================================

-- Tabla de zonas y tarifas
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
) ENGINE=InnoDB;

-- ====================================================================
-- PARTE 4: SISTEMA DE IMPORTACIÓN Y NOTIFICACIONES
-- ====================================================================

-- Tabla para archivos de importación
CREATE TABLE IF NOT EXISTS importaciones_archivos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    total_registros INT DEFAULT 0,
    registros_importados INT DEFAULT 0,
    registros_fallidos INT DEFAULT 0,
    estado ENUM('pendiente', 'procesando', 'completado', 'error') DEFAULT 'pendiente',
    fecha_importacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    procesado_por INT,
    observaciones TEXT,
    INDEX idx_fecha (fecha_importacion),
    INDEX idx_estado (estado)
) ENGINE=InnoDB;

-- Tabla para notificaciones WhatsApp
CREATE TABLE IF NOT EXISTS notificaciones_whatsapp (
    id INT PRIMARY KEY AUTO_INCREMENT,
    paquete_id INT NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    mensaje TEXT NOT NULL,
    tipo ENUM('asignacion', 'alerta', 'entrega', 'rezago') NOT NULL,
    estado ENUM('pendiente', 'enviado', 'fallido') DEFAULT 'pendiente',
    respuesta_api TEXT,
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    intentos INT DEFAULT 0,
    INDEX idx_paquete (paquete_id),
    INDEX idx_estado (estado),
    INDEX idx_fecha (fecha_envio)
) ENGINE=InnoDB;

-- Tabla para alertas de entrega
CREATE TABLE IF NOT EXISTS alertas_entrega (
    id INT PRIMARY KEY AUTO_INCREMENT,
    paquete_id INT NOT NULL,
    repartidor_id INT NOT NULL,
    tipo_alerta ENUM('24_horas', 'vencido') NOT NULL,
    mensaje TEXT NOT NULL,
    leida BOOLEAN DEFAULT FALSE,
    fecha_alerta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_paquete (paquete_id),
    INDEX idx_repartidor (repartidor_id),
    INDEX idx_leida (leida)
) ENGINE=InnoDB;

-- ====================================================================
-- PARTE 5: CLAVES FORÁNEAS
-- ====================================================================

-- Paquetes
ALTER TABLE paquetes 
    ADD CONSTRAINT fk_paquetes_repartidor 
    FOREIGN KEY (repartidor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_paquetes_zona 
    FOREIGN KEY (zona_tarifa_id) REFERENCES zonas_tarifas(id) ON DELETE SET NULL;

-- Rutas
ALTER TABLE rutas
    ADD CONSTRAINT fk_rutas_repartidor 
    FOREIGN KEY (repartidor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_rutas_creado_por 
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL;

-- Ruta paquetes
ALTER TABLE ruta_paquetes
    ADD CONSTRAINT fk_ruta_paquetes_ruta 
    FOREIGN KEY (ruta_id) REFERENCES rutas(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_ruta_paquetes_paquete 
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE CASCADE;

-- Entregas
ALTER TABLE entregas
    ADD CONSTRAINT fk_entregas_paquete 
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_entregas_ruta 
    FOREIGN KEY (ruta_id) REFERENCES rutas(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_entregas_repartidor 
    FOREIGN KEY (repartidor_id) REFERENCES usuarios(id) ON DELETE CASCADE;

-- Rezagados
ALTER TABLE paquetes_rezagados
    ADD CONSTRAINT fk_rezagados_paquete 
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE CASCADE;

-- Pagos
ALTER TABLE pagos
    ADD CONSTRAINT fk_pagos_repartidor 
    FOREIGN KEY (repartidor_id) REFERENCES usuarios(id) ON DELETE CASCADE;

ALTER TABLE pagos
    ADD CONSTRAINT fk_pagos_generado_por 
    FOREIGN KEY (generado_por) REFERENCES usuarios(id) ON DELETE SET NULL;
    
ALTER TABLE pagos
    ADD CONSTRAINT fk_pagos_registrado_por 
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE SET NULL;

-- Ingresos
ALTER TABLE ingresos
    ADD CONSTRAINT fk_ingresos_paquete 
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_ingresos_registrado_por 
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE SET NULL;

-- Gastos
ALTER TABLE gastos
    ADD CONSTRAINT fk_gastos_registrado_por 
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE SET NULL;

-- Ubicaciones
ALTER TABLE ubicaciones_tiempo_real
    ADD CONSTRAINT fk_ubicaciones_repartidor 
    FOREIGN KEY (repartidor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_ubicaciones_ruta 
    FOREIGN KEY (ruta_id) REFERENCES rutas(id) ON DELETE SET NULL;

-- Notificaciones
ALTER TABLE notificaciones
    ADD CONSTRAINT fk_notificaciones_usuario 
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE;

-- Logs
ALTER TABLE logs_sistema
    ADD CONSTRAINT fk_logs_usuario 
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL;

-- Importaciones SAVAR
ALTER TABLE importaciones_savar
    ADD CONSTRAINT fk_importaciones_procesado_por 
    FOREIGN KEY (procesado_por) REFERENCES usuarios(id) ON DELETE SET NULL;

-- Caja chica
ALTER TABLE caja_chica
    ADD CONSTRAINT fk_caja_asignado_por 
    FOREIGN KEY (asignado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_caja_asignado_a 
    FOREIGN KEY (asignado_a) REFERENCES usuarios(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_caja_registrado_por 
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_caja_asignacion_padre 
    FOREIGN KEY (asignacion_padre_id) REFERENCES caja_chica(id) ON DELETE SET NULL;

-- Importaciones archivos
ALTER TABLE importaciones_archivos
    ADD CONSTRAINT fk_import_archivos_procesado 
    FOREIGN KEY (procesado_por) REFERENCES usuarios(id) ON DELETE SET NULL;

-- Notificaciones WhatsApp
ALTER TABLE notificaciones_whatsapp
    ADD CONSTRAINT fk_whatsapp_paquete 
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE CASCADE;

-- Alertas de entrega
ALTER TABLE alertas_entrega
    ADD CONSTRAINT fk_alertas_paquete 
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_alertas_repartidor 
    FOREIGN KEY (repartidor_id) REFERENCES usuarios(id) ON DELETE CASCADE;

-- ====================================================================
-- PARTE 6: VISTAS
-- ====================================================================

-- Vista para saldo actual por asistente
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

-- ====================================================================
-- PARTE 7: DATOS INICIALES
-- ====================================================================

-- Insertar usuario administrador por defecto
INSERT INTO usuarios (nombre, apellido, email, password, rol) 
VALUES ('Admin', 'Sistema', 'admin@hermesexpress.com', '$2y$10$V2f2YeuO9OhmSFQKadjK3uR5ioR9ewevhi8mbg/tnslcJTlINQ7py', 'admin')
ON DUPLICATE KEY UPDATE email = email;
-- Password: password123

-- Insertar datos de ejemplo para desarrollo
INSERT INTO usuarios (nombre, apellido, email, telefono, password, rol) VALUES
('María', 'González', 'asistente@hermesexpress.com', '555-0101', '$2y$10$V2f2YeuO9OhmSFQKadjK3uR5ioR9ewevhi8mbg/tnslcJTlINQ7py', 'asistente'),
('Carlos', 'Rodríguez', 'carlos.r@hermesexpress.com', '555-0102', '$2y$10$V2f2YeuO9OhmSFQKadjK3uR5ioR9ewevhi8mbg/tnslcJTlINQ7py', 'repartidor'),
('Juan', 'Pérez', 'juan.p@hermesexpress.com', '555-0103', '$2y$10$V2f2YeuO9OhmSFQKadjK3uR5ioR9ewevhi8mbg/tnslcJTlINQ7py', 'repartidor')
ON DUPLICATE KEY UPDATE email = email;

-- Insertar zonas predefinidas
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

-- ====================================================================
-- INSTALACIÓN COMPLETADA
-- ====================================================================

SELECT 'Base de datos HERMES EXPRESS instalada exitosamente' as mensaje;
SELECT COUNT(*) as total_tablas FROM information_schema.tables WHERE table_schema = 'hermes_express';
