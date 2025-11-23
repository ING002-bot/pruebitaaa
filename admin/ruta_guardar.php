<?php
require_once '../config/config.php';
requireRole(['admin', 'asistente']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . 'admin/rutas.php');
    exit;
}

$nombre = sanitize($_POST['nombre']);
$zona = sanitize($_POST['zona']);
$ubicaciones = isset($_POST['ubicaciones']) ? implode(', ', $_POST['ubicaciones']) : '';
$descripcion = sanitize($_POST['descripcion']);
$repartidor_id = (int)$_POST['repartidor_id'];
$fecha_ruta = sanitize($_POST['fecha_ruta']);

try {
    $db = Database::getInstance()->getConnection();
    $sql = "INSERT INTO rutas (nombre, zona, ubicaciones, descripcion, repartidor_id, fecha_ruta, estado, creado_por) 
            VALUES (?, ?, ?, ?, ?, ?, 'planificada', ?)";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . $db->error);
    }
    
    $estado = 'planificada';
    $stmt->bind_param("ssssiis", $nombre, $zona, $ubicaciones, $descripcion, $repartidor_id, $fecha_ruta, $_SESSION['usuario_id']);
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar consulta: " . $stmt->error);
    }
    
    $ruta_id = $db->insert_id;
    $stmt->close();
    
    logActivity("Ruta creada: $nombre - Zona: $zona", 'rutas', $ruta_id);
    
    setFlashMessage('success', 'Ruta creada exitosamente');
    redirect(APP_URL . "admin/ruta_detalle.php?id=$ruta_id");
    
} catch (Exception $e) {
    setFlashMessage('danger', 'Error al crear ruta: ' . $e->getMessage());
    redirect(APP_URL . 'admin/rutas.php');
}
