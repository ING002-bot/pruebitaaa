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

    // Calcular costo de envío basado en distrito y prioridad
    $distrito = sanitize($_POST['distrito'] ?? '');
    $prioridad = sanitize($_POST['prioridad'] ?? 'normal');
    $costo_envio = !empty($_POST['costo_envio']) ? (float)$_POST['costo_envio'] : calcularCostoEnvio($distrito, $prioridad);
    
    // Obtener zona_tarifa_id para vincular con tarifas
    $zona_tarifa_id = null;
    if (!empty($distrito)) {
        $tarifa_info = obtenerTarifaPorDistrito($distrito);
        if ($tarifa_info) {
            $zona_tarifa_id = $tarifa_info['id'];
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
        'distrito' => $distrito,
        'peso' => 0,
        'valor_declarado' => 0,
        'costo_envio' => $costo_envio,
        'prioridad' => $prioridad,
        'repartidor_id' => !empty($_POST['repartidor_id']) ? (int)$_POST['repartidor_id'] : null,
        'notas' => '',
        'zona_tarifa_id' => $zona_tarifa_id
    ];
    
    $sql = "INSERT INTO paquetes (
        codigo_seguimiento, codigo_savar, destinatario_nombre, destinatario_telefono,
        destinatario_email, direccion_completa, ciudad, provincia, distrito, peso, valor_declarado,
        costo_envio, prioridad, repartidor_id, notas, zona_tarifa_id, estado, fecha_asignacion
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
        CASE WHEN ? IS NOT NULL THEN 'en_ruta' ELSE 'pendiente' END,
        CASE WHEN ? IS NOT NULL THEN NOW() ELSE NULL END
    )";
    
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . $db->error);
    }
    
    $stmt->bind_param(
        "sssssssssddsisiii",
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
        $data['zona_tarifa_id'],
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
