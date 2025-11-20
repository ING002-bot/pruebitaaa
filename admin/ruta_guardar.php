<?php
require_once '../config/config.php';
requireRole(['admin', 'asistente']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . 'admin/rutas.php');
    exit;
}

$nombre = sanitize($_POST['nombre']);
$descripcion = sanitize($_POST['descripcion']);
$repartidor_id = (int)$_POST['repartidor_id'];
$fecha_ruta = sanitize($_POST['fecha_ruta']);

try {
    $db = Database::getInstance()->getConnection();
    $sql = "INSERT INTO rutas (nombre, descripcion, repartidor_id, fecha_ruta, estado, creado_por) 
            VALUES (?, ?, ?, ?, 'planificada', ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$nombre, $descripcion, $repartidor_id, $fecha_ruta, $_SESSION['usuario_id']]);
    
    $ruta_id = $db->lastInsertId();
    logActivity("Ruta creada: $nombre", 'rutas', $ruta_id);
    
    setFlashMessage('success', 'Ruta creada exitosamente');
    redirect(APP_URL . "admin/ruta_detalle.php?id=$ruta_id");
    
} catch (Exception $e) {
    setFlashMessage('danger', 'Error al crear ruta: ' . $e->getMessage());
    redirect(APP_URL . 'admin/rutas.php');
}
