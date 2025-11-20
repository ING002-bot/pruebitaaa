-- HERMES EXPRESS LOGISTIC - Sistema de Gestión de Paquetería
-- Base de datos completa

CREATE DATABASE IF NOT EXISTS hermes_express CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hermes_express;

-- Tabla de usuarios con roles
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    telefono VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'asistente', 'repartidor') NOT NULL,
    foto_perfil VARCHAR(255) DEFAULT 'default-avatar.svg',
    estado ENUM('activo', 'inactivo', 'suspendido') DEFAULT 'activo',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_rol (rol)
) ENGINE=InnoDB;

-- Tabla de paquetes
CREATE TABLE paquetes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo_seguimiento VARCHAR(100) UNIQUE NOT NULL,
    codigo_savar VARCHAR(100),
    destinatario_nombre VARCHAR(150) NOT NULL,
    destinatario_telefono VARCHAR(20),
    destinatario_email VARCHAR(150),
    direccion_completa TEXT NOT NULL,
    direccion_latitud DECIMAL(10, 8),
    direccion_longitud DECIMAL(11, 8),
    ciudad VARCHAR(100),
    provincia VARCHAR(100),
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
    fecha_entrega TIMESTAMP NULL,
    intentos_entrega INT DEFAULT 0,
    notas TEXT,
    FOREIGN KEY (repartidor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_codigo (codigo_seguimiento),
    INDEX idx_estado (estado),
    INDEX idx_repartidor (repartidor_id),
    INDEX idx_fecha_entrega (fecha_entrega)
) ENGINE=InnoDB;

-- Tabla de rutas
CREATE TABLE rutas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
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
    FOREIGN KEY (repartidor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_fecha (fecha_ruta),
    INDEX idx_repartidor (repartidor_id)
) ENGINE=InnoDB;

-- Tabla de paquetes en rutas
CREATE TABLE ruta_paquetes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ruta_id INT NOT NULL,
    paquete_id INT NOT NULL,
    orden_entrega INT,
    estado ENUM('pendiente', 'entregado', 'fallido') DEFAULT 'pendiente',
    FOREIGN KEY (ruta_id) REFERENCES rutas(id) ON DELETE CASCADE,
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE CASCADE,
    INDEX idx_ruta (ruta_id),
    INDEX idx_paquete (paquete_id)
) ENGINE=InnoDB;

-- Tabla de entregas (con fotos y detalles)
CREATE TABLE entregas (
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
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE CASCADE,
    FOREIGN KEY (ruta_id) REFERENCES rutas(id) ON DELETE SET NULL,
    FOREIGN KEY (repartidor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_paquete (paquete_id),
    INDEX idx_fecha (fecha_entrega)
) ENGINE=InnoDB;

-- Tabla de paquetes rezagados
CREATE TABLE paquetes_rezagados (
    id INT PRIMARY KEY AUTO_INCREMENT,
    paquete_id INT NOT NULL,
    motivo ENUM('direccion_incorrecta', 'destinatario_ausente', 'rechazo', 'zona_peligrosa', 'otros') NOT NULL,
    descripcion_motivo TEXT,
    fecha_rezago TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    intentos_realizados INT DEFAULT 1,
    proximo_intento DATE,
    solucionado BOOLEAN DEFAULT FALSE,
    fecha_solucion TIMESTAMP NULL,
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE CASCADE,
    INDEX idx_paquete (paquete_id),
    INDEX idx_solucionado (solucionado)
) ENGINE=InnoDB;

-- Tabla de pagos a repartidores
CREATE TABLE pagos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    repartidor_id INT NOT NULL,
    periodo_inicio DATE NOT NULL,
    periodo_fin DATE NOT NULL,
    total_paquetes INT DEFAULT 0,
    monto_por_paquete DECIMAL(10, 2),
    bonificaciones DECIMAL(10, 2) DEFAULT 0,
    deducciones DECIMAL(10, 2) DEFAULT 0,
    total_pagar DECIMAL(10, 2) NOT NULL,
    estado ENUM('pendiente', 'pagado', 'cancelado') DEFAULT 'pendiente',
    fecha_pago TIMESTAMP NULL,
    metodo_pago ENUM('efectivo', 'transferencia', 'cheque'),
    notas TEXT,
    generado_por INT,
    fecha_generacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (repartidor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (generado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_repartidor (repartidor_id),
    INDEX idx_estado (estado)
) ENGINE=InnoDB;

-- Tabla de ingresos de la empresa
CREATE TABLE ingresos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipo ENUM('envio', 'servicio_adicional', 'recargo', 'otros') NOT NULL,
    concepto VARCHAR(200) NOT NULL,
    monto DECIMAL(10, 2) NOT NULL,
    paquete_id INT,
    fecha_ingreso TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    registrado_por INT,
    notas TEXT,
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE SET NULL,
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_fecha (fecha_ingreso),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB;

-- Tabla de gastos de la empresa
CREATE TABLE gastos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    categoria ENUM('combustible', 'mantenimiento', 'salarios', 'administracion', 'otros') NOT NULL,
    concepto VARCHAR(200) NOT NULL,
    monto DECIMAL(10, 2) NOT NULL,
    fecha_gasto DATE NOT NULL,
    registrado_por INT,
    notas TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_fecha (fecha_gasto),
    INDEX idx_categoria (categoria)
) ENGINE=InnoDB;

-- Tabla de seguimiento de ubicación en tiempo real
CREATE TABLE ubicaciones_tiempo_real (
    id INT PRIMARY KEY AUTO_INCREMENT,
    repartidor_id INT NOT NULL,
    ruta_id INT,
    latitud DECIMAL(10, 8) NOT NULL,
    longitud DECIMAL(11, 8) NOT NULL,
    precision_metros DECIMAL(8, 2),
    velocidad DECIMAL(6, 2),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (repartidor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (ruta_id) REFERENCES rutas(id) ON DELETE SET NULL,
    INDEX idx_repartidor_timestamp (repartidor_id, timestamp),
    INDEX idx_ruta (ruta_id)
) ENGINE=InnoDB;

-- Tabla de notificaciones
CREATE TABLE notificaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    tipo ENUM('info', 'alerta', 'urgente', 'sistema') DEFAULT 'info',
    titulo VARCHAR(200) NOT NULL,
    mensaje TEXT NOT NULL,
    leida BOOLEAN DEFAULT FALSE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_leida (usuario_id, leida),
    INDEX idx_fecha (fecha_creacion)
) ENGINE=InnoDB;

-- Tabla de logs del sistema
CREATE TABLE logs_sistema (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT,
    accion VARCHAR(100) NOT NULL,
    tabla_afectada VARCHAR(50),
    registro_id INT,
    detalles TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha (fecha_accion)
) ENGINE=InnoDB;

-- Tabla de datos importados desde SAVAR
CREATE TABLE importaciones_savar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    datos_json LONGTEXT NOT NULL,
    total_registros INT,
    registros_procesados INT DEFAULT 0,
    registros_fallidos INT DEFAULT 0,
    estado ENUM('pendiente', 'procesando', 'completado', 'error') DEFAULT 'pendiente',
    fecha_importacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    procesado_por INT,
    errores TEXT,
    FOREIGN KEY (procesado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_fecha (fecha_importacion)
) ENGINE=InnoDB;

-- Insertar usuario administrador por defecto
INSERT INTO usuarios (nombre, apellido, email, password, rol) 
VALUES ('Admin', 'Sistema', 'admin@hermesexpress.com', '$2y$10$V2f2YeuO9OhmSFQKadjK3uR5ioR9ewevhi8mbg/tnslcJTlINQ7py', 'admin');
-- Password: password123

-- Insertar datos de ejemplo para desarrollo
INSERT INTO usuarios (nombre, apellido, email, telefono, password, rol) VALUES
('María', 'González', 'asistente@hermesexpress.com', '555-0101', '$2y$10$V2f2YeuO9OhmSFQKadjK3uR5ioR9ewevhi8mbg/tnslcJTlINQ7py', 'asistente'),
('Carlos', 'Rodríguez', 'carlos.r@hermesexpress.com', '555-0102', '$2y$10$V2f2YeuO9OhmSFQKadjK3uR5ioR9ewevhi8mbg/tnslcJTlINQ7py', 'repartidor'),
('Juan', 'Pérez', 'juan.p@hermesexpress.com', '555-0103', '$2y$10$V2f2YeuO9OhmSFQKadjK3uR5ioR9ewevhi8mbg/tnslcJTlINQ7py', 'repartidor');
