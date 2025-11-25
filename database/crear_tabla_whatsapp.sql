/**
 * Script para crear la tabla notificaciones_whatsapp
 * Ejecutar en la base de datos del sistema
 */

-- Crear tabla para registrar notificaciones de WhatsApp
CREATE TABLE IF NOT EXISTS `notificaciones_whatsapp` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `paquete_id` INT NOT NULL,
  `telefono` VARCHAR(20) NOT NULL,
  `mensaje` LONGTEXT,
  `tipo` VARCHAR(50) NOT NULL COMMENT 'asignacion, alerta_24h, entrega_exitosa, problema_entrega, etc',
  `estado` VARCHAR(50) NOT NULL DEFAULT 'pendiente' COMMENT 'pendiente, enviado, fallido',
  `respuesta_api` VARCHAR(255),
  `intentos` INT DEFAULT 1,
  `fecha_envio` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  KEY `idx_paquete_id` (`paquete_id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha_envio` (`fecha_envio`),
  UNIQUE KEY `uq_paquete_tipo` (`paquete_id`, `tipo`),
  CONSTRAINT `fk_notif_whatsapp_paquete` FOREIGN KEY (`paquete_id`) 
    REFERENCES `paquetes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar columnas a la tabla paquetes si no existen
ALTER TABLE `paquetes` ADD COLUMN IF NOT EXISTS `notificacion_whatsapp_enviada` TINYINT DEFAULT 0;
ALTER TABLE `paquetes` ADD COLUMN IF NOT EXISTS `fecha_notificacion_whatsapp` TIMESTAMP NULL;

-- Crear tabla para alertas de entrega
CREATE TABLE IF NOT EXISTS `alertas_entrega` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `paquete_id` INT NOT NULL,
  `repartidor_id` INT NOT NULL,
  `tipo_alerta` VARCHAR(50) NOT NULL COMMENT '24_horas, vencida, etc',
  `mensaje` LONGTEXT,
  `estado` VARCHAR(50) DEFAULT 'enviada',
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  KEY `idx_paquete_id` (`paquete_id`),
  KEY `idx_repartidor_id` (`repartidor_id`),
  KEY `idx_tipo_alerta` (`tipo_alerta`),
  CONSTRAINT `fk_alerta_paquete` FOREIGN KEY (`paquete_id`) 
    REFERENCES `paquetes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_alerta_repartidor` FOREIGN KEY (`repartidor_id`) 
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla para registrar logs de intentos de envío
CREATE TABLE IF NOT EXISTS `logs_whatsapp` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `paquete_id` INT,
  `usuario_id` INT,
  `tipo_evento` VARCHAR(100) NOT NULL COMMENT 'intento_envio, fallo, reintento, exito',
  `detalles` LONGTEXT,
  `fecha_evento` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  KEY `idx_paquete_id` (`paquete_id`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_tipo_evento` (`tipo_evento`),
  KEY `idx_fecha_evento` (`fecha_evento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
