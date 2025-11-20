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
    
    $stmt->execute([
        $_POST['repartidor_id'],
        $_POST['paquete_id']
    ]);
    
    // Obtener info del paquete
    $paquete = $db->prepare("SELECT codigo_seguimiento FROM paquetes WHERE id = ?");
    $paquete->execute([$_POST['paquete_id']]);
    $codigo = $paquete->fetchColumn();
    
    // Crear notificación para el repartidor
    $db->prepare("INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje) VALUES (?, 'info', ?, ?)")
       ->execute([
           $_POST['repartidor_id'],
           'Nuevo paquete asignado',
           'Se te ha asignado el paquete ' . $codigo
       ]);
    
    // Registrar log
    $db->prepare("INSERT INTO logs_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles) VALUES (?, ?, ?, ?, ?)")
       ->execute([
           $_SESSION['usuario_id'],
           'asignar',
           'paquetes',
           $_POST['paquete_id'],
           'Paquete asignado a repartidor ID: ' . $_POST['repartidor_id']
       ]);
    
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
