<?php
require_once '../config/config.php';
require_once '../config/whatsapp_helper.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: paquetes.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$whatsapp = new WhatsAppNotificaciones();

try {
    $stmt = $db->prepare("
        UPDATE paquetes SET
            codigo_seguimiento = ?,
            codigo_savar = ?,
            destinatario_nombre = ?,
            destinatario_telefono = ?,
            destinatario_email = ?,
            direccion_completa = ?,
            ciudad = ?,
            provincia = ?,
            peso = ?,
            descripcion = ?,
            valor_declarado = ?,
            costo_envio = ?,
            estado = ?,
            prioridad = ?,
            repartidor_id = ?,
            notas = ?
        WHERE id = ?
    ");
    
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . $db->error);
    }
    
    $repartidor_id = $_POST['repartidor_id'] ?: null;
    $paquete_id = (int)$_POST['id'];
    
    $stmt->bind_param(
        "ssssssssdddssisi",
        $_POST['codigo_seguimiento'],
        $_POST['codigo_savar'],
        $_POST['destinatario_nombre'],
        $_POST['destinatario_telefono'],
        $_POST['destinatario_email'],
        $_POST['direccion_completa'],
        $_POST['ciudad'],
        $_POST['provincia'],
        $_POST['peso'],
        $_POST['descripcion'],
        $_POST['valor_declarado'],
        $_POST['costo_envio'],
        $_POST['estado'],
        $_POST['prioridad'],
        $repartidor_id,
        $_POST['notas'],
        $paquete_id
    );
    
    // Obtener repartidor anterior ANTES de actualizar
    $repartidor_anterior = null;
    $check_stmt = $db->prepare("SELECT repartidor_id FROM paquetes WHERE id = ?");
    if ($check_stmt) {
        $check_stmt->bind_param("i", $paquete_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $anterior = $result->fetch_assoc();
        $repartidor_anterior = $anterior['repartidor_id'] ?? null;
        $check_stmt->close();
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar consulta: " . $stmt->error);
    }
    $stmt->close();
    
    // Si se asignó un repartidor, actualizar fecha de asignación y enviar notificación
    if ($repartidor_id && $_POST['estado'] !== 'pendiente') {
        // Actualizar fecha de asignación
        $asig_stmt = $db->prepare("UPDATE paquetes SET fecha_asignacion = NOW() WHERE id = ? AND fecha_asignacion IS NULL");
        if ($asig_stmt) {
            $asig_stmt->bind_param("i", $paquete_id);
            $asig_stmt->execute();
            $asig_stmt->close();
        }
        
        // Si cambió de repartidor o es primera asignación, enviar WhatsApp
        if ($repartidor_anterior !== $repartidor_id) {
            $whatsapp->notificarAsignacion($paquete_id);
        }
    }
    
    // Registrar log
    $log_stmt = $db->prepare("INSERT INTO logs_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles) VALUES (?, ?, ?, ?, ?)");
    if ($log_stmt) {
        $accion = 'actualizar';
        $tabla = 'paquetes';
        $detalles = 'Paquete actualizado: ' . $_POST['codigo_seguimiento'];
        $log_stmt->bind_param("issis", $_SESSION['usuario_id'], $accion, $tabla, $paquete_id, $detalles);
        $log_stmt->execute();
        $log_stmt->close();
    }
    
    $_SESSION['flash_message'] = [
        'type' => 'success',
        'message' => 'Paquete actualizado correctamente'
    ];
    
} catch (Exception $e) {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => 'Error al actualizar el paquete: ' . $e->getMessage()
    ];
}

header('Location: paquetes.php');
exit;
