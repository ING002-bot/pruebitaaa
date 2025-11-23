<?php
require_once '../config/config.php';
requireRole(['asistente']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: paquetes.php');
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    // Generar código de seguimiento único si no se proporciona
    $codigo_seguimiento = trim($_POST['codigo_seguimiento'] ?? '');
    if (empty($codigo_seguimiento)) {
        $codigo_seguimiento = 'PKG-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
    
    // Verificar que el código sea único
    $stmt = $db->prepare("SELECT id FROM paquetes WHERE codigo_seguimiento = ?");
    $stmt->bind_param("s", $codigo_seguimiento);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('El código de seguimiento ya existe');
    }
    
    // Datos básicos
    $codigo_savar = trim($_POST['codigo_savar'] ?? '');
    $destinatario_nombre = trim($_POST['destinatario_nombre'] ?? '');
    $destinatario_telefono = trim($_POST['destinatario_telefono'] ?? '');
    $destinatario_email = trim($_POST['destinatario_email'] ?? '');
    $direccion_completa = trim($_POST['direccion_completa'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $provincia = trim($_POST['provincia'] ?? '');
    $codigo_postal = trim($_POST['codigo_postal'] ?? '');
    
    // Detalles del paquete
    $peso = !empty($_POST['peso']) ? floatval($_POST['peso']) : null;
    $dimensiones = trim($_POST['dimensiones'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $valor_declarado = !empty($_POST['valor_declarado']) ? floatval($_POST['valor_declarado']) : null;
    $costo_envio = !empty($_POST['costo_envio']) ? floatval($_POST['costo_envio']) : null;
    
    // Estado y prioridad
    $estado = $_POST['estado'] ?? 'pendiente';
    $prioridad = $_POST['prioridad'] ?? 'normal';
    $repartidor_id = !empty($_POST['repartidor_id']) ? intval($_POST['repartidor_id']) : null;
    $notas = trim($_POST['notas'] ?? '');
    
    // Validaciones
    if (empty($destinatario_nombre)) {
        throw new Exception('El nombre del destinatario es requerido');
    }
    
    if (empty($direccion_completa)) {
        throw new Exception('La dirección es requerida');
    }
    
    // Insertar paquete
    $stmt = $db->prepare("INSERT INTO paquetes (
        codigo_seguimiento, codigo_savar, destinatario_nombre, destinatario_telefono, 
        destinatario_email, direccion_completa, ciudad, provincia, codigo_postal,
        peso, dimensiones, descripcion, valor_declarado, costo_envio,
        estado, prioridad, repartidor_id, notas
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("sssssssssdsdddssds",
        $codigo_seguimiento, $codigo_savar, $destinatario_nombre, $destinatario_telefono,
        $destinatario_email, $direccion_completa, $ciudad, $provincia, $codigo_postal,
        $peso, $dimensiones, $descripcion, $valor_declarado, $costo_envio,
        $estado, $prioridad, $repartidor_id, $notas
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Error al guardar el paquete: ' . $stmt->error);
    }
    
    $paquete_id = $db->insert_id;
    
    // Si se asignó un repartidor, actualizar fecha de asignación
    if ($repartidor_id) {
        $db->query("UPDATE paquetes SET fecha_asignacion = NOW() WHERE id = $paquete_id");
    }
    
    $_SESSION['success_message'] = 'Paquete creado exitosamente con código: ' . $codigo_seguimiento;
    
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
}

header('Location: paquetes.php');
exit;
