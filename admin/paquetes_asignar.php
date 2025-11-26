<?php
require_once '../config/config.php';
require_once '../config/whatsapp_helper.php';
require_once '../lib/TwilioWhatsApp.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: paquetes.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$whatsapp = new WhatsAppNotificaciones();
$twilio = new TwilioWhatsApp();

try {
    // Calcular fecha límite de entrega (2 días desde ahora)
    $fecha_limite = date('Y-m-d H:i:s', strtotime('+2 days'));
    
    $stmt = $db->prepare("
        UPDATE paquetes SET
            repartidor_id = ?,
            estado = 'en_ruta',
            fecha_asignacion = NOW(),
            fecha_limite_entrega = ?
        WHERE id = ?
    ");
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . $db->error);
    }
    
    $repartidor_id = (int)$_POST['repartidor_id'];
    $paquete_id = (int)$_POST['paquete_id'];
    $stmt->bind_param("isi", $repartidor_id, $fecha_limite, $paquete_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar consulta: " . $stmt->error);
    }
    $stmt->close();
    
    // Enviar notificación WhatsApp al cliente
    $notificacion_enviada = $whatsapp->notificarAsignacion($paquete_id);
    
    // Obtener info del paquete para Twilio
    $info_paquete = $db->prepare("
        SELECT p.codigo_seguimiento, p.destinatario_nombre, p.destinatario_telefono,
               u.nombre as repartidor_nombre, u.apellido as repartidor_apellido
        FROM paquetes p
        LEFT JOIN usuarios u ON p.repartidor_id = u.id
        WHERE p.id = ?
    ");
    if ($info_paquete) {
        $info_paquete->bind_param("i", $paquete_id);
        $info_paquete->execute();
        $result = $info_paquete->get_result();
        $datos = $result->fetch_assoc();
        $info_paquete->close();
        
        // Enviar notificación Twilio WhatsApp al cliente
        if ($datos && !empty($datos['destinatario_telefono'])) {
            $nombre_repartidor = trim($datos['repartidor_nombre'] . ' ' . $datos['repartidor_apellido']);
            $twilio->notificarEnRuta(
                $datos['destinatario_telefono'],
                $datos['codigo_seguimiento'],
                $nombre_repartidor,
                '' // Placa vacía ya que no existe en la BD
            );
        }
    }
    
    // Obtener info del paquete para notificación interna
    $paquete = $db->prepare("SELECT codigo_seguimiento FROM paquetes WHERE id = ?");
    if (!$paquete) {
        throw new Exception("Error al preparar consulta: " . $db->error);
    }
    $paquete->bind_param("i", $paquete_id);
    $paquete->execute();
    $result = $paquete->get_result();
    $row = $result->fetch_assoc();
    $codigo = $row['codigo_seguimiento'] ?? '';
    $paquete->close();
    
    // Crear notificación para el repartidor
    $notif_stmt = $db->prepare("INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje) VALUES (?, 'info', ?, ?)");
    if (!$notif_stmt) {
        throw new Exception("Error al preparar consulta: " . $db->error);
    }
    $titulo = 'Nuevo paquete asignado';
    $mensaje = 'Se te ha asignado el paquete ' . $codigo . '. Fecha límite de entrega: ' . date('d/m/Y H:i', strtotime($fecha_limite));
    $notif_stmt->bind_param("iss", $repartidor_id, $titulo, $mensaje);
    $notif_stmt->execute();
    $notif_stmt->close();
    
    // Registrar log
    $log_stmt = $db->prepare("INSERT INTO logs_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles) VALUES (?, ?, ?, ?, ?)");
    if (!$log_stmt) {
        throw new Exception("Error al preparar consulta: " . $db->error);
    }
    $accion = 'asignar';
    $tabla = 'paquetes';
    $detalles = 'Paquete asignado a repartidor ID: ' . $repartidor_id;
    $log_stmt->bind_param("issis", $_SESSION['usuario_id'], $accion, $tabla, $paquete_id, $detalles);
    $log_stmt->execute();
    $log_stmt->close();
    
    $_SESSION['flash_message'] = [
        'type' => 'success',
        'message' => 'Paquete asignado correctamente'
    ];
    
} catch (Exception $e) {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => 'Error al asignar el paquete: ' . $e->getMessage()
    ];
}

header('Location: paquetes.php');
exit;
