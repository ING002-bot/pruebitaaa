-- Agregar campos necesarios para el sistema de importación y notificaciones
ALTER TABLE paquetes 
ADD COLUMN archivo_importacion VARCHAR(255) AFTER codigo_savar,
ADD COLUMN fecha_limite_entrega DATETIME AFTER fecha_asignacion,
ADD COLUMN alerta_enviada BOOLEAN DEFAULT FALSE AFTER fecha_limite_entrega,
ADD COLUMN notificacion_whatsapp_enviada BOOLEAN DEFAULT FALSE AFTER alerta_enviada;

-- Crear tabla para almacenar archivos de importación
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
    FOREIGN KEY (procesado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_fecha (fecha_importacion),
    INDEX idx_estado (estado)
) ENGINE=InnoDB;

-- Crear tabla para log de notificaciones WhatsApp
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
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE CASCADE,
    INDEX idx_paquete (paquete_id),
    INDEX idx_estado (estado),
    INDEX idx_fecha (fecha_envio)
) ENGINE=InnoDB;

-- Crear tabla para alertas de tiempo
CREATE TABLE IF NOT EXISTS alertas_entrega (
    id INT PRIMARY KEY AUTO_INCREMENT,
    paquete_id INT NOT NULL,
    repartidor_id INT NOT NULL,
    tipo_alerta ENUM('24_horas', 'vencido') NOT NULL,
    mensaje TEXT NOT NULL,
    leida BOOLEAN DEFAULT FALSE,
    fecha_alerta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE CASCADE,
    FOREIGN KEY (repartidor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_paquete (paquete_id),
    INDEX idx_repartidor (repartidor_id),
    INDEX idx_leida (leida)
) ENGINE=InnoDB;
