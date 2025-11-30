<?php
require_once '../config/config.php';
require_once '../config/whatsapp_helper.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . 'admin/paquetes.php');
}

$db = Database::getInstance()->getConnection();

try {
    $db->autocommit(false);
    
    // Procesar teléfono con +51 automático
    $telefono = sanitize($_POST['destinatario_telefono']);
    if (!empty($telefono)) {
        // Si el usuario ingresó solo 9 dígitos, agregar +51
        if (preg_match('/^[9][0-9]{8}$/', $telefono)) {
            $telefono = '+51' . $telefono;
        }
        // Si no tiene +51 pero tiene 9 dígitos válidos, agregarlo
        elseif (strlen($telefono) === 9 && !str_contains($telefono, '+51')) {
            $telefono = '+51' . $telefono;
        }
    }

    $data = [
        'codigo_seguimiento' => sanitize($_POST['codigo_seguimiento']),
        'codigo_savar' => sanitize($_POST['codigo_savar'] ?? ''),
        'destinatario_nombre' => sanitize($_POST['destinatario_nombre']),
        'destinatario_telefono' => $telefono,
        'destinatario_email' => sanitize($_POST['destinatario_email'] ?? ''),
        'direccion_completa' => sanitize($_POST['direccion_completa']),
        'ciudad' => sanitize($_POST['departamento'] ?? 'Lambayeque'),
        'provincia' => sanitize($_POST['provincia'] ?? ''),
        'distrito' => sanitize($_POST['distrito'] ?? ''),
        'peso' => 0,
        'valor_declarado' => 0,
        'costo_envio' => TARIFA_POR_PAQUETE,
        'prioridad' => 'normal',
        'repartidor_id' => !empty($_POST['repartidor_id']) ? (int)$_POST['repartidor_id'] : null,
        'notas' => ''
    ];
    
    $sql = "INSERT INTO paquetes (
        codigo_seguimiento, codigo_savar, destinatario_nombre, destinatario_telefono,
        destinatario_email, direccion_completa, ciudad, provincia, distrito, peso, valor_declarado,
        costo_envio, prioridad, repartidor_id, notas, estado, fecha_asignacion
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
        CASE WHEN ? IS NOT NULL THEN 'en_ruta' ELSE 'pendiente' END,
        CASE WHEN ? IS NOT NULL THEN NOW() ELSE NULL END
    )";
    
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . $db->error);
    }
    
    $stmt->bind_param(
        "sssssssssdddsisii",
        $data['codigo_seguimiento'],
        $data['codigo_savar'],
        $data['destinatario_nombre'],
        $data['destinatario_telefono'],
        $data['destinatario_email'],
        $data['direccion_completa'],
        $data['ciudad'],
        $data['provincia'],
        $data['distrito'],
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
    
    // Si se asignó repartidor, enviar notificación WhatsApp de asignación
    if ($data['repartidor_id']) {
        $whatsapp = new WhatsAppNotificaciones();
        $whatsapp->notificarAsignacion($paquete_id);
        
        // Notificación interna al repartidor
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
