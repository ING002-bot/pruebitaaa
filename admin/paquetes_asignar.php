<?php
require_once '../config/config.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: paquetes.php');
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    $stmt = $db->prepare("
        UPDATE paquetes SET
            repartidor_id = ?,
            estado = 'en_ruta',
            fecha_asignacion = NOW()
        WHERE id = ?
    ");
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . $db->error);
    }
    
    $repartidor_id = (int)$_POST['repartidor_id'];
    $paquete_id = (int)$_POST['paquete_id'];
    $stmt->bind_param("ii", $repartidor_id, $paquete_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar consulta: " . $stmt->error);
    }
    $stmt->close();
    
    // Obtener info del paquete
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
    $mensaje = 'Se te ha asignado el paquete ' . $codigo;
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
