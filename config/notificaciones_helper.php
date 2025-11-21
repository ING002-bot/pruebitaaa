<?php
/**
 * Helper para crear notificaciones del sistema
 */

/**
 * Crear una notificación para un usuario
 * 
 * @param int $usuario_id ID del usuario destinatario
 * @param string $tipo Tipo de notificación: 'info', 'alerta', 'urgente', 'sistema'
 * @param string $titulo Título de la notificación
 * @param string $mensaje Mensaje descriptivo
 * @return bool Éxito de la operación
 */
function crearNotificacion($usuario_id, $tipo, $titulo, $mensaje) {
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje)
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->bind_param("isss", $usuario_id, $tipo, $titulo, $mensaje);
        return $stmt->execute();
        
    } catch (Exception $e) {
        error_log("Error al crear notificación: " . $e->getMessage());
        return false;
    }
}

/**
 * Crear notificación para todos los usuarios de un rol específico
 * 
 * @param string $rol Rol de los usuarios: 'admin', 'repartidor', 'asistente'
 * @param string $tipo Tipo de notificación
 * @param string $titulo Título de la notificación
 * @param string $mensaje Mensaje descriptivo
 * @return int Número de notificaciones creadas
 */
function crearNotificacionPorRol($rol, $tipo, $titulo, $mensaje) {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Obtener usuarios del rol especificado
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE rol = ? AND estado = 'activo'");
        $stmt->bind_param("s", $rol);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuarios = [];
        while ($row = $result->fetch_assoc()) {
            $usuarios[] = $row['id'];
        }
        
        $count = 0;
        foreach ($usuarios as $usuario_id) {
            if (crearNotificacion($usuario_id, $tipo, $titulo, $mensaje)) {
                $count++;
            }
        }
        
        return $count;
        
    } catch (Exception $e) {
        error_log("Error al crear notificaciones por rol: " . $e->getMessage());
        return 0;
    }
}

/**
 * Notificar cuando un paquete es asignado a un repartidor
 */
function notificarAsignacionPaquete($repartidor_id, $paquete_id, $tracking) {
    return crearNotificacion(
        $repartidor_id,
        'info',
        'Nuevo paquete asignado',
        "Se te ha asignado el paquete #$tracking para entrega"
    );
}

/**
 * Notificar cuando un paquete se vuelve rezagado
 */
function notificarPaqueteRezagado($admin_ids, $paquete_id, $tracking) {
    $count = 0;
    foreach ($admin_ids as $admin_id) {
        if (crearNotificacion(
            $admin_id,
            'alerta',
            'Paquete rezagado',
            "El paquete #$tracking ha sido marcado como rezagado"
        )) {
            $count++;
        }
    }
    return $count;
}

/**
 * Notificar entrega exitosa
 */
function notificarEntregaExitosa($admin_ids, $tracking, $repartidor_nombre) {
    $count = 0;
    foreach ($admin_ids as $admin_id) {
        if (crearNotificacion(
            $admin_id,
            'info',
            'Entrega completada',
            "$repartidor_nombre completó la entrega del paquete #$tracking"
        )) {
            $count++;
        }
    }
    return $count;
}

/**
 * Notificar pago pendiente a repartidor
 */
function notificarPagoPendiente($repartidor_id, $monto) {
    return crearNotificacion(
        $repartidor_id,
        'alerta',
        'Pago pendiente',
        "Tienes un pago pendiente de S/ " . number_format($monto, 2)
    );
}

/**
 * Notificar pago registrado
 */
function notificarPagoRegistrado($repartidor_id, $monto, $fecha) {
    return crearNotificacion(
        $repartidor_id,
        'info',
        'Pago registrado',
        "Se ha registrado tu pago de S/ " . number_format($monto, 2) . " correspondiente a $fecha"
    );
}

/**
 * Obtener todos los IDs de administradores activos
 */
function obtenerAdministradores() {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE rol = 'admin' AND estado = 'activo'");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        error_log("Error al obtener administradores: " . $e->getMessage());
        return [];
    }
}
