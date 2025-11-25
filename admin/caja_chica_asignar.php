<?php
require_once '../config/config.php';
require_once '../config/notificaciones_helper.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . 'admin/caja_chica.php');
}

$db = Database::getInstance()->getConnection();

try {
    $asignado_a = (int)$_POST['asignado_a'];
    $monto = (float)$_POST['monto'];
    $concepto = sanitize($_POST['concepto']);
    $descripcion = sanitize($_POST['descripcion'] ?? '');
    $fecha_operacion = $_POST['fecha_operacion'];
    
    // Validar que el asistente existe
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ? AND rol = 'asistente' AND estado = 'activo'");
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . $db->error);
    }
    $stmt->bind_param("i", $asignado_a);
    $stmt->execute();
    $result = $stmt->get_result();
    $asistente = $result->fetch_assoc();
    $stmt->close();
    
    if (!$asistente) {
        setFlashMessage('danger', 'Asistente no válido');
        redirect(APP_URL . 'admin/caja_chica.php');
    }
    
    // Insertar asignación
    $stmt = $db->prepare("
        INSERT INTO caja_chica (
            tipo, monto, concepto, descripcion, 
            asignado_por, asignado_a, registrado_por, fecha_operacion
        ) VALUES (
            'asignacion', ?, ?, ?, ?, ?, ?, ?
        )
    ");
    
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . $db->error);
    }
    
    $tipo = 'asignacion';
    $stmt->bind_param(
        "dssiiis",
        $monto,
        $concepto,
        $descripcion,
        $_SESSION['usuario_id'], // asignado_por
        $asignado_a,
        $_SESSION['usuario_id'], // registrado_por
        $fecha_operacion
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar consulta: " . $stmt->error);
    }
    
    $asignacion_id = $db->insert_id;
    $stmt->close();
    
    // Crear notificación para el asistente
    crearNotificacion(
        $asignado_a,
        'info',
        'Nueva Asignación de Caja Chica',
        "Se te ha asignado S/ " . number_format($monto, 2) . " para: $concepto"
    );
    
    logActivity('Asignación caja chica', 'caja_chica', $asignacion_id, "S/ $monto para {$asistente['nombre']} {$asistente['apellido']}");
    
    setFlashMessage('success', 'Asignación registrada exitosamente. El asistente ha sido notificado.');
    redirect(APP_URL . 'admin/caja_chica.php');
    
} catch (Exception $e) {
    error_log("Error al asignar caja chica: " . $e->getMessage());
    setFlashMessage('danger', 'Error al registrar la asignación');
    redirect(APP_URL . 'admin/caja_chica.php');
}
