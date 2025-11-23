<?php
/**
 * Script Cron - Verificar Alertas de Entrega
 * 
 * Este script debe ejecutarse cada hora para verificar paquetes
 * que están a 24 horas o menos de su fecha límite de entrega.
 * 
 * Configuración en crontab (Linux):
 * 0 * * * * php /ruta/completa/cron/verificar_alertas_entrega.php
 * 
 * Configuración en Programador de Tareas (Windows):
 * Programa: C:\xampp\php\php.exe
 * Argumentos: C:\xampp\htdocs\pruebitaaa\cron\verificar_alertas_entrega.php
 * Periodicidad: Cada hora
 */

// Configurar zona horaria
date_default_timezone_set('America/La_Paz');

// Incluir archivos necesarios
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/whatsapp_helper.php';

// Función para registrar en log
function logMensaje($mensaje) {
    $log_file = dirname(__FILE__) . '/alertas_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $mensaje\n", FILE_APPEND);
}

try {
    logMensaje("=== Inicio de verificación de alertas ===");
    
    $db = Database::getInstance()->getConnection();
    $whatsapp = new WhatsAppNotificaciones();
    
    // Buscar paquetes que:
    // 1. Estén en estado 'en_ruta'
    // 2. Tengan fecha límite dentro de las próximas 24 horas
    // 3. No se haya enviado alerta aún
    $query = "
        SELECT 
            p.id,
            p.codigo_seguimiento,
            p.fecha_limite_entrega,
            p.repartidor_id,
            p.destinatario_nombre,
            p.destinatario_telefono,
            u.nombre as repartidor_nombre,
            u.telefono as repartidor_telefono,
            TIMESTAMPDIFF(HOUR, NOW(), p.fecha_limite_entrega) as horas_restantes
        FROM paquetes p
        INNER JOIN usuarios u ON p.repartidor_id = u.id
        WHERE p.estado = 'en_ruta'
        AND p.fecha_limite_entrega IS NOT NULL
        AND p.fecha_limite_entrega > NOW()
        AND p.fecha_limite_entrega <= DATE_ADD(NOW(), INTERVAL 24 HOUR)
        AND (p.alerta_enviada = 0 OR p.alerta_enviada IS NULL)
    ";
    
    $result = $db->query($query);
    
    if (!$result) {
        throw new Exception("Error en consulta: " . $db->error);
    }
    
    $paquetes_procesados = 0;
    $alertas_enviadas = 0;
    $errores = 0;
    
    logMensaje("Paquetes encontrados: " . $result->num_rows);
    
    while ($paquete = $result->fetch_assoc()) {
        $paquetes_procesados++;
        
        try {
            logMensaje("Procesando paquete ID: {$paquete['id']}, Código: {$paquete['codigo_seguimiento']}");
            logMensaje("Horas restantes: {$paquete['horas_restantes']}");
            
            // Enviar alerta WhatsApp al repartidor
            $alerta_enviada = $whatsapp->enviarAlerta24Horas($paquete['id'], $paquete['repartidor_id']);
            
            if ($alerta_enviada) {
                // Marcar alerta como enviada
                $update_stmt = $db->prepare("UPDATE paquetes SET alerta_enviada = 1 WHERE id = ?");
                $update_stmt->bind_param("i", $paquete['id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Registrar en tabla de alertas
                $insert_stmt = $db->prepare("
                    INSERT INTO alertas_entrega 
                    (paquete_id, repartidor_id, fecha_limite, horas_restantes, estado)
                    VALUES (?, ?, ?, ?, 'enviada')
                ");
                $insert_stmt->bind_param(
                    "iisi",
                    $paquete['id'],
                    $paquete['repartidor_id'],
                    $paquete['fecha_limite_entrega'],
                    $paquete['horas_restantes']
                );
                $insert_stmt->execute();
                $insert_stmt->close();
                
                // Crear notificación en el sistema
                $notif_stmt = $db->prepare("
                    INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje)
                    VALUES (?, 'warning', ?, ?)
                ");
                $titulo = '⚠️ Alerta de Entrega - 24 Horas';
                $mensaje = sprintf(
                    "El paquete %s debe ser entregado antes del %s (%d horas restantes). Destinatario: %s",
                    $paquete['codigo_seguimiento'],
                    date('d/m/Y H:i', strtotime($paquete['fecha_limite_entrega'])),
                    $paquete['horas_restantes'],
                    $paquete['destinatario_nombre']
                );
                $notif_stmt->bind_param("iss", $paquete['repartidor_id'], $titulo, $mensaje);
                $notif_stmt->execute();
                $notif_stmt->close();
                
                $alertas_enviadas++;
                logMensaje("✓ Alerta enviada correctamente para paquete {$paquete['codigo_seguimiento']}");
            } else {
                logMensaje("✗ No se pudo enviar alerta para paquete {$paquete['codigo_seguimiento']}");
                $errores++;
            }
            
        } catch (Exception $e) {
            logMensaje("✗ Error procesando paquete {$paquete['id']}: " . $e->getMessage());
            $errores++;
        }
    }
    
    logMensaje("=== Resumen de verificación ===");
    logMensaje("Paquetes procesados: $paquetes_procesados");
    logMensaje("Alertas enviadas: $alertas_enviadas");
    logMensaje("Errores: $errores");
    logMensaje("=== Fin de verificación ===\n");
    
} catch (Exception $e) {
    logMensaje("ERROR CRÍTICO: " . $e->getMessage());
    logMensaje("=== Fin con errores ===\n");
}
