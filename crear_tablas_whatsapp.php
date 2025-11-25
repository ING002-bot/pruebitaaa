<?php
/**
 * Script para crear/verificar tablas de WhatsApp
 * Ejecutar una sola vez desde: http://tudominio/crear_tablas_whatsapp.php
 */

require_once 'config/config.php';

// Verificar que sea el admin quien ejecute
if (!isLoggedIn() || $_SESSION['rol'] !== 'admin') {
    die('❌ Acceso denegado. Solo administradores pueden ejecutar este script.');
}

$db = Database::getInstance()->getConnection();
$resultado = [];

// 1. Crear tabla notificaciones_whatsapp
$sql_notificaciones = "
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
";

if ($db->query($sql_notificaciones)) {
    $resultado[] = ['exito' => true, 'mensaje' => 'Tabla notificaciones_whatsapp creada/verificada'];
} else {
    $resultado[] = ['exito' => false, 'error' => $db->error];
}

// 2. Agregar columnas a paquetes
$columnas_paquetes = [
    'notificacion_whatsapp_enviada' => "ALTER TABLE `paquetes` ADD COLUMN IF NOT EXISTS `notificacion_whatsapp_enviada` TINYINT DEFAULT 0",
    'fecha_notificacion_whatsapp' => "ALTER TABLE `paquetes` ADD COLUMN IF NOT EXISTS `fecha_notificacion_whatsapp` TIMESTAMP NULL"
];

foreach ($columnas_paquetes as $nombre => $sql) {
    if ($db->query($sql)) {
        $resultado[] = ['exito' => true, 'mensaje' => "Columna $nombre agregada a paquetes"];
    } else {
        $resultado[] = ['exito' => false, 'error' => $db->error];
    }
}

// 3. Crear tabla alertas_entrega
$sql_alertas = "
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
";

if ($db->query($sql_alertas)) {
    $resultado[] = ['exito' => true, 'mensaje' => 'Tabla alertas_entrega creada/verificada'];
} else {
    $resultado[] = ['exito' => false, 'error' => $db->error];
}

// 4. Crear tabla logs_whatsapp
$sql_logs = "
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
";

if ($db->query($sql_logs)) {
    $resultado[] = ['exito' => true, 'mensaje' => 'Tabla logs_whatsapp creada/verificada'];
} else {
    $resultado[] = ['exito' => false, 'error' => $db->error];
}

// Mostrar resultados
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Tablas WhatsApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light p-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-database"></i> Crear Tablas para Notificaciones WhatsApp</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <strong>Resultado de la instalación:</strong>
                        </div>
                        
                        <div class="list-group">
                            <?php foreach ($resultado as $item): ?>
                                <div class="list-group-item">
                                    <?php if ($item['exito']): ?>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-check-circle text-success fs-5 me-2"></i>
                                            <span><?php echo $item['mensaje']; ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-exclamation-circle text-warning fs-5 me-2"></i>
                                            <span><strong>Error:</strong> <?php echo $item['error']; ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-4">
                            <p class="text-muted">Las tablas han sido creadas exitosamente y el sistema está listo para enviar notificaciones por WhatsApp.</p>
                            
                            <div class="alert alert-warning mt-3">
                                <h6><i class="bi bi-lightbulb"></i> Próximos pasos:</h6>
                                <ol>
                                    <li>Si deseas usar <strong>WhatsApp real</strong>, configura una de estas opciones:
                                        <ul>
                                            <li><strong>Twilio:</strong> Crea una cuenta en <a href="https://www.twilio.com" target="_blank">twilio.com</a></li>
                                            <li><strong>WhatsApp Cloud API:</strong> Integra desde <a href="https://www.whatsapp.com/business" target="_blank">WhatsApp Business</a></li>
                                        </ul>
                                    </li>
                                    <li>Por ahora, los mensajes se <strong>simulan</strong> y se registran en los logs</li>
                                    <li>Configura las constantes en <code>config/config.php</code></li>
                                </ol>
                            </div>
                            
                            <a href="admin/paquetes.php" class="btn btn-primary mt-3">
                                <i class="bi bi-arrow-left"></i> Volver a Paquetes
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
