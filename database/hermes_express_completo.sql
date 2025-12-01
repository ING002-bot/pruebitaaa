-- ====================================================================
-- HERMES EXPRESS LOGISTIC - Base de Datos Completa
-- ====================================================================
-- Archivo consolidado que incluye toda la estructura y datos
-- Para importar: mysql -u root -p < hermes_express_completo.sql
-- ====================================================================

-- Crear y usar base de datos
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
    distrito VARCHAR(100),
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
    ultimo_pago_id INT DEFAULT NULL COMMENT 'ID del último pago que incluye este paquete',
    INDEX idx_codigo (codigo_seguimiento),
    INDEX idx_estado (estado),
    INDEX idx_repartidor (repartidor_id),
    INDEX idx_fecha_entrega (fecha_entrega),
    INDEX idx_ultimo_pago (ultimo_pago_id)
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
    metodo_pago ENUM('efectivo', 'transferencia', 'cheque', 'yape', 'plin') DEFAULT 'efectivo',
    metodo_pago_real ENUM('efectivo', 'transferencia', 'cheque', 'yape', 'plin') NULL,
    registrado_por INT,
    notas TEXT,
    notas_pago TEXT,
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
    categoria ENUM('combustible', 'mantenimiento', 'salarios', 'administracion', 'Pagos a Personal', 'otros') NOT NULL,
    descripcion VARCHAR(200),
    concepto VARCHAR(200) NULL,
    monto DECIMAL(10, 2) NOT NULL,
    numero_comprobante VARCHAR(100),
    comprobante_archivo VARCHAR(255),
    fecha_gasto DATE NOT NULL,
    estado ENUM('pendiente', 'confirmado', 'cancelado') DEFAULT 'confirmado',
    registrado_por INT,
    notas TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fecha (fecha_gasto),
    INDEX idx_categoria (categoria),
    INDEX idx_estado (estado),
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
    tipo ENUM('info', 'alerta', 'urgente', 'sistema', 'pago') DEFAULT 'info',
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
    costo_cliente DECIMAL(10, 2) NOT NULL DEFAULT 0 COMMENT 'Precio que se cobra al cliente',
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_categoria (categoria),
    INDEX idx_activo (activo),
    INDEX idx_costo_cliente (costo_cliente),
    INDEX idx_nombre_zona (nombre_zona),
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

-- Crear tabla para logs de WhatsApp
CREATE TABLE IF NOT EXISTS logs_whatsapp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paquete_id INT,
    usuario_id INT,
    tipo_evento VARCHAR(100) NOT NULL COMMENT 'intento_envio, fallo, reintento, exito',
    detalles LONGTEXT,
    fecha_evento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_paquete_id (paquete_id),
    KEY idx_usuario_id (usuario_id),
    KEY idx_tipo_evento (tipo_evento),
    KEY idx_fecha_evento (fecha_evento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- PARTE 5: CLAVES FORÁNEAS
-- ====================================================================

-- Eliminar claves foráneas existentes si existen (evitar duplicados)
SET FOREIGN_KEY_CHECKS = 0;

-- Paquetes
ALTER TABLE paquetes DROP FOREIGN KEY IF EXISTS fk_paquetes_repartidor;
ALTER TABLE paquetes DROP FOREIGN KEY IF EXISTS fk_paquetes_zona;
ALTER TABLE paquetes DROP FOREIGN KEY IF EXISTS fk_paquetes_ultimo_pago;

ALTER TABLE paquetes 
    ADD CONSTRAINT fk_paquetes_repartidor 
    FOREIGN KEY (repartidor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_paquetes_zona 
    FOREIGN KEY (zona_tarifa_id) REFERENCES zonas_tarifas(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_paquetes_ultimo_pago
    FOREIGN KEY (ultimo_pago_id) REFERENCES pagos(id) ON DELETE SET NULL;

-- Rutas
ALTER TABLE rutas DROP FOREIGN KEY IF EXISTS fk_rutas_repartidor;
ALTER TABLE rutas DROP FOREIGN KEY IF EXISTS fk_rutas_creado_por;
ALTER TABLE rutas
    ADD CONSTRAINT fk_rutas_repartidor 
    FOREIGN KEY (repartidor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_rutas_creado_por 
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL;

-- Ruta paquetes
ALTER TABLE ruta_paquetes DROP FOREIGN KEY IF EXISTS fk_ruta_paquetes_ruta;
ALTER TABLE ruta_paquetes DROP FOREIGN KEY IF EXISTS fk_ruta_paquetes_paquete;
ALTER TABLE ruta_paquetes
    ADD CONSTRAINT fk_ruta_paquetes_ruta 
    FOREIGN KEY (ruta_id) REFERENCES rutas(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_ruta_paquetes_paquete 
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE CASCADE;

-- Entregas
ALTER TABLE entregas DROP FOREIGN KEY IF EXISTS fk_entregas_paquete;
ALTER TABLE entregas DROP FOREIGN KEY IF EXISTS fk_entregas_ruta;
ALTER TABLE entregas DROP FOREIGN KEY IF EXISTS fk_entregas_repartidor;
ALTER TABLE entregas
    ADD CONSTRAINT fk_entregas_paquete 
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_entregas_ruta 
    FOREIGN KEY (ruta_id) REFERENCES rutas(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_entregas_repartidor 
    FOREIGN KEY (repartidor_id) REFERENCES usuarios(id) ON DELETE CASCADE;

-- Rezagados
ALTER TABLE paquetes_rezagados DROP FOREIGN KEY IF EXISTS fk_rezagados_paquete;
ALTER TABLE paquetes_rezagados
    ADD CONSTRAINT fk_rezagados_paquete 
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE CASCADE;

-- Pagos
ALTER TABLE pagos DROP FOREIGN KEY IF EXISTS fk_pagos_repartidor;
ALTER TABLE pagos DROP FOREIGN KEY IF EXISTS fk_pagos_generado_por;
ALTER TABLE pagos DROP FOREIGN KEY IF EXISTS fk_pagos_registrado_por;
ALTER TABLE pagos
    ADD CONSTRAINT fk_pagos_repartidor 
    FOREIGN KEY (repartidor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_pagos_generado_por 
    FOREIGN KEY (generado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_pagos_registrado_por 
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE SET NULL;

-- Ingresos
ALTER TABLE ingresos DROP FOREIGN KEY IF EXISTS fk_ingresos_paquete;
ALTER TABLE ingresos DROP FOREIGN KEY IF EXISTS fk_ingresos_registrado_por;
ALTER TABLE ingresos
    ADD CONSTRAINT fk_ingresos_paquete 
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_ingresos_registrado_por 
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE SET NULL;

-- Gastos
ALTER TABLE gastos DROP FOREIGN KEY IF EXISTS fk_gastos_registrado_por;
ALTER TABLE gastos
    ADD CONSTRAINT fk_gastos_registrado_por 
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE SET NULL;

-- Ubicaciones
ALTER TABLE ubicaciones_tiempo_real DROP FOREIGN KEY IF EXISTS fk_ubicaciones_repartidor;
ALTER TABLE ubicaciones_tiempo_real DROP FOREIGN KEY IF EXISTS fk_ubicaciones_ruta;
ALTER TABLE ubicaciones_tiempo_real
    ADD CONSTRAINT fk_ubicaciones_repartidor 
    FOREIGN KEY (repartidor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_ubicaciones_ruta 
    FOREIGN KEY (ruta_id) REFERENCES rutas(id) ON DELETE SET NULL;

-- Notificaciones
ALTER TABLE notificaciones DROP FOREIGN KEY IF EXISTS fk_notificaciones_usuario;
ALTER TABLE notificaciones
    ADD CONSTRAINT fk_notificaciones_usuario 
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE;

-- Logs
ALTER TABLE logs_sistema DROP FOREIGN KEY IF EXISTS fk_logs_usuario;
ALTER TABLE logs_sistema
    ADD CONSTRAINT fk_logs_usuario 
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL;

-- Importaciones SAVAR
ALTER TABLE importaciones_savar DROP FOREIGN KEY IF EXISTS fk_importaciones_procesado_por;
ALTER TABLE importaciones_savar
    ADD CONSTRAINT fk_importaciones_procesado_por 
    FOREIGN KEY (procesado_por) REFERENCES usuarios(id) ON DELETE SET NULL;

-- Caja chica
ALTER TABLE caja_chica DROP FOREIGN KEY IF EXISTS fk_caja_asignado_por;
ALTER TABLE caja_chica DROP FOREIGN KEY IF EXISTS fk_caja_asignado_a;
ALTER TABLE caja_chica DROP FOREIGN KEY IF EXISTS fk_caja_registrado_por;
ALTER TABLE caja_chica DROP FOREIGN KEY IF EXISTS fk_caja_asignacion_padre;
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
ALTER TABLE importaciones_archivos DROP FOREIGN KEY IF EXISTS fk_import_archivos_procesado;
ALTER TABLE importaciones_archivos
    ADD CONSTRAINT fk_import_archivos_procesado 
    FOREIGN KEY (procesado_por) REFERENCES usuarios(id) ON DELETE SET NULL;

-- Notificaciones WhatsApp
ALTER TABLE notificaciones_whatsapp DROP FOREIGN KEY IF EXISTS fk_whatsapp_paquete;
ALTER TABLE notificaciones_whatsapp
    ADD CONSTRAINT fk_whatsapp_paquete 
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE CASCADE;

-- Alertas de entrega
ALTER TABLE alertas_entrega DROP FOREIGN KEY IF EXISTS fk_alertas_paquete;
ALTER TABLE alertas_entrega DROP FOREIGN KEY IF EXISTS fk_alertas_repartidor;
ALTER TABLE alertas_entrega
    ADD CONSTRAINT fk_alertas_paquete 
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_alertas_repartidor 
    FOREIGN KEY (repartidor_id) REFERENCES usuarios(id) ON DELETE CASCADE;

-- Logs WhatsApp
ALTER TABLE logs_whatsapp DROP FOREIGN KEY IF EXISTS fk_logs_whatsapp_paquete;
ALTER TABLE logs_whatsapp DROP FOREIGN KEY IF EXISTS fk_logs_whatsapp_usuario;
ALTER TABLE logs_whatsapp
    ADD CONSTRAINT fk_logs_whatsapp_paquete
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_logs_whatsapp_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL;

-- Reactivar verificación de claves foráneas
SET FOREIGN_KEY_CHECKS = 1;

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

-- Insertar SOLO usuario administrador (los demás se crean desde el admin)
INSERT INTO usuarios (nombre, apellido, email, password, rol) 
VALUES ('Admin', 'Sistema', 'admin@hermesexpress.com', '$2y$10$V2f2YeuO9OhmSFQKadjK3uR5ioR9ewevhi8mbg/tnslcJTlINQ7py', 'admin')
ON DUPLICATE KEY UPDATE email = email;
-- Password: password123
-- NOTA: Todos los demás usuarios (asistentes, repartidores) se crean desde el panel de administración

-- Insertar zonas con tarifas correctas para repartidores
INSERT INTO zonas_tarifas (categoria, nombre_zona, tipo_envio, tarifa_repartidor, costo_cliente, activo) VALUES

-- URBANO (Cobras S/ 3.00 - Pagas según tabla repartidores)
('URBANO', 'Chiclayo', 'Paquete', 1.50, 3.00, 1),
('URBANO', 'Leonardo Ortiz', 'Paquete', 1.80, 3.00, 1),
('URBANO', 'La Victoria', 'Paquete', 1.50, 3.00, 1),
('URBANO', 'Santa Victoria', 'Paquete', 1.50, 3.00, 1),

-- PUEBLOS (Pagas S/ 3.00 - Cobras según nueva tabla)
('PUEBLOS', 'Lambayeque', 'Paquete', 3.00, 5.00, 1),
('PUEBLOS', 'Mochumi', 'Paquete', 3.00, 8.00, 1),
('PUEBLOS', 'Tucume', 'Paquete', 3.00, 8.00, 1),
('PUEBLOS', 'Illimo', 'Paquete', 3.00, 8.00, 1),
('PUEBLOS', 'Nueva Arica', 'Paquete', 3.00, 8.00, 1),
('PUEBLOS', 'Jayanca', 'Paquete', 3.00, 8.00, 1),
('PUEBLOS', 'Pacora', 'Paquete', 3.00, 8.00, 1),
('PUEBLOS', 'Morrope', 'Paquete', 3.00, 8.00, 1),
('PUEBLOS', 'Motupe', 'Paquete', 3.00, 8.00, 1),
('PUEBLOS', 'Olmos', 'Paquete', 3.00, 8.00, 1),
('PUEBLOS', 'Salas', 'Paquete', 3.00, 8.00, 1),

-- PLAYAS (Pagas S/ 2.00-2.50 - Cobras según nueva tabla)
('PLAYAS', 'San Jose', 'Paquete', 2.00, 5.00, 1),
('PLAYAS', 'Santa Rosa', 'Paquete', 2.00, 5.00, 1),
('PLAYAS', 'Pimentel', 'Paquete', 2.00, 5.00, 1),
('PLAYAS', 'Reque', 'Paquete', 2.50, 5.00, 1),
('PLAYAS', 'Monsefu', 'Paquete', 2.50, 5.00, 1),
('PLAYAS', 'Eten', 'Paquete', 2.50, 8.00, 1),
('PLAYAS', 'Puerto Eten', 'Paquete', 2.50, 8.00, 1),

-- COOPERATIVAS (Pagas S/ 2.00 - Cobras según nueva tabla)
('COOPERATIVAS', 'Pomalca', 'Paquete', 2.00, 5.00, 1),
('COOPERATIVAS', 'Tuman', 'Paquete', 2.00, 8.00, 1),
('COOPERATIVAS', 'Patapo', 'Paquete', 2.00, 8.00, 1),
('COOPERATIVAS', 'Pucala', 'Paquete', 2.00, 8.00, 1),
('COOPERATIVAS', 'Sartur', 'Paquete', 2.00, 8.00, 1),
('COOPERATIVAS', 'Chongoyape', 'Paquete', 2.00, 8.00, 1),

-- EXCOPERATIVAS (Pagas S/ 2.00 - Cobras S/ 8.00)
('EXCOPERATIVAS', 'Ucupe', 'Paquete', 2.00, 8.00, 1),
('EXCOPERATIVAS', 'Mocupe', 'Paquete', 2.00, 8.00, 1),
('EXCOPERATIVAS', 'Zaña', 'Paquete', 2.00, 8.00, 1),
('EXCOPERATIVAS', 'Cayalti', 'Paquete', 2.00, 8.00, 1),
('EXCOPERATIVAS', 'Oyotun', 'Paquete', 2.00, 8.00, 1),
('EXCOPERATIVAS', 'Lagunas', 'Paquete', 2.00, 8.00, 1),

-- FERREÑAFE (Pagas S/ 2.50 - Cobras S/ 8.00)
('FERREÑAFE', 'Ferreñafe', 'Paquete', 2.50, 8.00, 1),
('FERREÑAFE', 'Picsi', 'Paquete', 2.50, 8.00, 1),
('FERREÑAFE', 'Pitipo', 'Paquete', 2.50, 8.00, 1),
('FERREÑAFE', 'Motupillo', 'Paquete', 2.50, 8.00, 1),
('FERREÑAFE', 'Pueblo Nuevo', 'Paquete', 2.50, 8.00, 1)

ON DUPLICATE KEY UPDATE 
    tarifa_repartidor = VALUES(tarifa_repartidor),
    costo_cliente = VALUES(costo_cliente),
    fecha_actualizacion = CURRENT_TIMESTAMP;

-- Paquetes de prueba removidos para instalación limpia

-- ====================================================================
-- FINALIZACIÓN
-- ====================================================================

-- ====================================================================
-- PARTE 8: FIJAR TARIFAS DEFINITIVAS (NO MODIFICAR ESTOS VALORES)
-- ====================================================================
-- IMPORTANTE: Estas tarifas son FIJAS y no deben cambiar en el sistema
-- Los valores de tarifa_repartidor y costo_cliente están predeterminados

-- Actualizar tarifas finales por zona (VALORES FIJOS)
-- URBANO - TÚ COBRAS S/ 3.00
UPDATE zonas_tarifas SET tarifa_repartidor = 1.50, costo_cliente = 3.00 WHERE nombre_zona = 'Chiclayo';
UPDATE zonas_tarifas SET tarifa_repartidor = 1.80, costo_cliente = 3.00 WHERE nombre_zona = 'Leonardo Ortiz';
UPDATE zonas_tarifas SET tarifa_repartidor = 1.50, costo_cliente = 3.00 WHERE nombre_zona = 'La Victoria';
UPDATE zonas_tarifas SET tarifa_repartidor = 1.50, costo_cliente = 3.00 WHERE nombre_zona = 'Santa Victoria';

-- PUEBLOS - TÚ PAGAS S/ 3.00 (FIJO)
UPDATE zonas_tarifas SET tarifa_repartidor = 3.00, costo_cliente = 5.00 WHERE nombre_zona = 'Lambayeque';
UPDATE zonas_tarifas SET tarifa_repartidor = 3.00, costo_cliente = 8.00 WHERE nombre_zona = 'Mochumi';
UPDATE zonas_tarifas SET tarifa_repartidor = 3.00, costo_cliente = 8.00 WHERE nombre_zona = 'Tucume';
UPDATE zonas_tarifas SET tarifa_repartidor = 3.00, costo_cliente = 8.00 WHERE nombre_zona = 'Illimo';
UPDATE zonas_tarifas SET tarifa_repartidor = 3.00, costo_cliente = 8.00 WHERE nombre_zona = 'Nueva Arica';
UPDATE zonas_tarifas SET tarifa_repartidor = 3.00, costo_cliente = 8.00 WHERE nombre_zona = 'Jayanca';
UPDATE zonas_tarifas SET tarifa_repartidor = 3.00, costo_cliente = 8.00 WHERE nombre_zona = 'Pacora';
UPDATE zonas_tarifas SET tarifa_repartidor = 3.00, costo_cliente = 8.00 WHERE nombre_zona = 'Morrope';
UPDATE zonas_tarifas SET tarifa_repartidor = 3.00, costo_cliente = 8.00 WHERE nombre_zona = 'Motupe';
UPDATE zonas_tarifas SET tarifa_repartidor = 3.00, costo_cliente = 8.00 WHERE nombre_zona = 'Olmos';
UPDATE zonas_tarifas SET tarifa_repartidor = 3.00, costo_cliente = 8.00 WHERE nombre_zona = 'Salas';

-- PLAYAS - TÚ PAGAS S/ 2.00-2.50 (FIJO)
UPDATE zonas_tarifas SET tarifa_repartidor = 2.00, costo_cliente = 5.00 WHERE nombre_zona = 'San Jose';
UPDATE zonas_tarifas SET tarifa_repartidor = 2.00, costo_cliente = 5.00 WHERE nombre_zona = 'Santa Rosa';
UPDATE zonas_tarifas SET tarifa_repartidor = 2.00, costo_cliente = 5.00 WHERE nombre_zona = 'Pimentel';
UPDATE zonas_tarifas SET tarifa_repartidor = 2.50, costo_cliente = 5.00 WHERE nombre_zona = 'Reque';
UPDATE zonas_tarifas SET tarifa_repartidor = 2.50, costo_cliente = 5.00 WHERE nombre_zona = 'Monsefu';
UPDATE zonas_tarifas SET tarifa_repartidor = 2.50, costo_cliente = 8.00 WHERE nombre_zona = 'Eten';
UPDATE zonas_tarifas SET tarifa_repartidor = 2.50, costo_cliente = 8.00 WHERE nombre_zona = 'Puerto Eten';

-- COOPERATIVAS - TÚ PAGAS S/ 2.00 (FIJO)
UPDATE zonas_tarifas SET tarifa_repartidor = 2.00, costo_cliente = 5.00 WHERE nombre_zona = 'Pomalca';
UPDATE zonas_tarifas SET tarifa_repartidor = 2.00, costo_cliente = 8.00 WHERE nombre_zona = 'Tuman';
UPDATE zonas_tarifas SET tarifa_repartidor = 2.00, costo_cliente = 8.00 WHERE nombre_zona = 'Patapo';
UPDATE zonas_tarifas SET tarifa_repartidor = 2.00, costo_cliente = 8.00 WHERE nombre_zona = 'Pucala';
UPDATE zonas_tarifas SET tarifa_repartidor = 2.00, costo_cliente = 8.00 WHERE nombre_zona = 'Sartur';
UPDATE zonas_tarifas SET tarifa_repartidor = 2.00, costo_cliente = 8.00 WHERE nombre_zona = 'Chongoyape';

-- EXCOPERATIVAS - TÚ PAGAS S/ 2.00 (FIJO)
UPDATE zonas_tarifas SET tarifa_repartidor = 2.00, costo_cliente = 8.00 WHERE nombre_zona = 'Ucupe';
UPDATE zonas_tarifas SET tarifa_repartidor = 2.00, costo_cliente = 8.00 WHERE nombre_zona = 'Mocupe';
UPDATE zonas_tarifas SET tarifa_repartidor = 2.00, costo_cliente = 8.00 WHERE nombre_zona = 'Zaña';
UPDATE zonas_tarifas SET tarifa_repartidor = 2.00, costo_cliente = 8.00 WHERE nombre_zona = 'Cayalti';
UPDATE zonas_tarifas SET tarifa_repartidor = 2.00, costo_cliente = 8.00 WHERE nombre_zona = 'Oyotun';
UPDATE zonas_tarifas SET tarifa_repartidor = 2.00, costo_cliente = 8.00 WHERE nombre_zona = 'Lagunas';

-- FERREÑAFE - TÚ PAGAS S/ 2.50 (FIJO)
UPDATE zonas_tarifas SET tarifa_repartidor = 2.50, costo_cliente = 8.00 WHERE nombre_zona = 'Ferreñafe';
UPDATE zonas_tarifas SET tarifa_repartidor = 2.50, costo_cliente = 8.00 WHERE nombre_zona = 'Picsi';
UPDATE zonas_tarifas SET tarifa_repartidor = 2.50, costo_cliente = 8.00 WHERE nombre_zona = 'Pitipo';
UPDATE zonas_tarifas SET tarifa_repartidor = 2.50, costo_cliente = 8.00 WHERE nombre_zona = 'Motupillo';
UPDATE zonas_tarifas SET tarifa_repartidor = 2.50, costo_cliente = 8.00 WHERE nombre_zona = 'Pueblo Nuevo';

-- Insertar zonas faltantes si no existen (por seguridad)
INSERT INTO zonas_tarifas (categoria, nombre_zona, tipo_envio, tarifa_repartidor, costo_cliente, activo) 
SELECT 'URBANO', 'Chiclayo', 'Paquete', 1.50, 3.00, 1
WHERE NOT EXISTS (SELECT 1 FROM zonas_tarifas WHERE nombre_zona = 'Chiclayo');

INSERT INTO zonas_tarifas (categoria, nombre_zona, tipo_envio, tarifa_repartidor, costo_cliente, activo) 
SELECT 'URBANO', 'Leonardo Ortiz', 'Paquete', 1.80, 3.00, 1
WHERE NOT EXISTS (SELECT 1 FROM zonas_tarifas WHERE nombre_zona = 'Leonardo Ortiz');

-- ====================================================================
-- VERIFICACIÓN FINAL
-- ====================================================================

SELECT 'Base de datos HERMES_EXPRESS instalada exitosamente con TARIFAS FIJAS' as mensaje;
SELECT COUNT(*) as total_tablas FROM information_schema.tables WHERE table_schema = 'hermes_express';
SELECT COUNT(*) as total_zonas FROM zonas_tarifas WHERE activo = 1;
SELECT COUNT(*) as total_usuarios FROM usuarios;

-- Mostrar tarifas finales FIJAS por categoría
SELECT 
    categoria,
    COUNT(*) as cantidad_zonas,
    MIN(costo_cliente) as precio_min_cliente,
    MAX(costo_cliente) as precio_max_cliente,
    MIN(tarifa_repartidor) as pago_min_repartidor,
    MAX(tarifa_repartidor) as pago_max_repartidor
FROM zonas_tarifas 
WHERE activo = 1 
GROUP BY categoria 
ORDER BY categoria;

-- Verificar que todas las tarifas están correctas
SELECT 
    categoria,
    nombre_zona,
    CONCAT('Cobras: S/ ', FORMAT(costo_cliente, 2)) as ingresos,
    CONCAT('Pagas: S/ ', FORMAT(tarifa_repartidor, 2)) as gastos,
    CONCAT('Ganas: S/ ', FORMAT(costo_cliente - tarifa_repartidor, 2)) as ganancia_neta
FROM zonas_tarifas 
WHERE activo = 1
ORDER BY 
    FIELD(categoria, 'URBANO', 'PUEBLOS', 'PLAYAS', 'COOPERATIVAS', 'EXCOPERATIVAS', 'FERREÑAFE'),
    nombre_zona;