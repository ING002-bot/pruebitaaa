<?php
require_once '../config/config.php';
require_once '../lib/TwilioWhatsApp.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . 'admin/paquetes.php');
}

$db = Database::getInstance()->getConnection();

try {
    $db->autocommit(false);
    
    $data = [
        'codigo_seguimiento' => sanitize($_POST['codigo_seguimiento']),
        'codigo_savar' => sanitize($_POST['codigo_savar'] ?? ''),
        'destinatario_nombre' => sanitize($_POST['destinatario_nombre']),
        'destinatario_telefono' => sanitize($_POST['destinatario_telefono']),
        'destinatario_email' => sanitize($_POST['destinatario_email'] ?? ''),
        'direccion_completa' => sanitize($_POST['direccion_completa']),
        'ciudad' => sanitize($_POST['ciudad'] ?? ''),
        'provincia' => sanitize($_POST['provincia'] ?? ''),
        'peso' => (float)($_POST['peso'] ?? 0),
        'valor_declarado' => (float)($_POST['valor_declarado'] ?? 0),
        'costo_envio' => (float)($_POST['costo_envio'] ?? TARIFA_POR_PAQUETE),
        'prioridad' => sanitize($_POST['prioridad'] ?? 'normal'),
        'repartidor_id' => !empty($_POST['repartidor_id']) ? (int)$_POST['repartidor_id'] : null,
        'notas' => sanitize($_POST['notas'] ?? '')
    ];
    
    $sql = "INSERT INTO paquetes (
        codigo_seguimiento, codigo_savar, destinatario_nombre, destinatario_telefono,
        destinatario_email, direccion_completa, ciudad, provincia, peso, valor_declarado,
        costo_envio, prioridad, repartidor_id, notas, estado, fecha_asignacion
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
        CASE WHEN ? IS NOT NULL THEN 'en_ruta' ELSE 'pendiente' END,
        CASE WHEN ? IS NOT NULL THEN NOW() ELSE NULL END
    )";
    
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . $db->error);
    }
    
    $stmt->bind_param(
        "ssssssssdddsisii",
        $data['codigo_seguimiento'],
        $data['codigo_savar'],
        $data['destinatario_nombre'],
        $data['destinatario_telefono'],
        $data['destinatario_email'],
        $data['direccion_completa'],
        $data['ciudad'],
        $data['provincia'],
        $data['peso'],
        $data['valor_declarado'],
        $data['costo_envio'],
        $data['prioridad'],
        $data['repartidor_id'],
        $data['notas'],
        $data['repartidor_id'],
        $data['repartidor_id']
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar consulta: " . $stmt->error);
    }
    
    $paquete_id = $db->insert_id;
    $stmt->close();
    
    // Log
    logActivity('Creación de paquete', 'paquetes', $paquete_id, 'Código: ' . $data['codigo_seguimiento']);
    
    // Enviar notificación Twilio WhatsApp al cliente
    if (!empty($data['destinatario_telefono'])) {
        $twilio = new TwilioWhatsApp();
        $twilio->notificarNuevoPaquete(
            $data['destinatario_telefono'],
            $data['codigo_seguimiento'],
            $data['destinatario_nombre'],
            $data['direccion_completa']
        );
    }
    
    // Notificación al repartidor si fue asignado
    if ($data['repartidor_id']) {
        createNotification(
            $data['repartidor_id'],
            'info',
            'Nuevo paquete asignado',
            "Se te ha asignado el paquete {$data['codigo_seguimiento']}"
        );
    }
    
    $db->commit();
    $db->autocommit(true);
    
    setFlashMessage('success', 'Paquete registrado exitosamente');
    redirect(APP_URL . 'admin/paquetes.php');
    
} catch (Exception $e) {
    $db->rollback();
    $db->autocommit(true);
    error_log("Error al guardar paquete: " . $e->getMessage());
    setFlashMessage('danger', 'Error al guardar el paquete: ' . $e->getMessage());
    redirect(APP_URL . 'admin/paquetes.php');
}
