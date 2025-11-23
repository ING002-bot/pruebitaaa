<?php
require_once '../config/config.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . 'admin/tarifas.php');
}

$db = Database::getInstance()->getConnection();

try {
    $id = (int)$_POST['id'];
    $categoria = sanitize($_POST['categoria']);
    $nombre_zona = sanitize($_POST['nombre_zona']);
    $tipo_envio = sanitize($_POST['tipo_envio']);
    $tarifa_repartidor = (float)$_POST['tarifa_repartidor'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Verificar que la tarifa existe
    $stmt = $db->prepare("SELECT * FROM zonas_tarifas WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . $db->error);
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $tarifaActual = $result->fetch_assoc();
    $stmt->close();
    
    if (!$tarifaActual) {
        setFlashMessage('danger', 'Tarifa no encontrada');
        redirect(APP_URL . 'admin/tarifas.php');
    }
    
    // Actualizar tarifa
    $stmt = $db->prepare("
        UPDATE zonas_tarifas 
        SET categoria = ?, 
            nombre_zona = ?, 
            tipo_envio = ?, 
            tarifa_repartidor = ?, 
            activo = ?
        WHERE id = ?
    ");
    
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . $db->error);
    }
    
    $stmt->bind_param("sssdii", $categoria, $nombre_zona, $tipo_envio, $tarifa_repartidor, $activo, $id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar consulta: " . $stmt->error);
    }
    $stmt->close();
    
    $cambios = [];
    if ($tarifaActual['tarifa_repartidor'] != $tarifa_repartidor) {
        $cambios[] = "Tarifa: S/ {$tarifaActual['tarifa_repartidor']} → S/ $tarifa_repartidor";
    }
    if ($tarifaActual['activo'] != $activo) {
        $cambios[] = "Estado: " . ($activo ? 'Activado' : 'Desactivado');
    }
    
    logActivity('Actualizar tarifa', 'zonas_tarifas', $id, implode(', ', $cambios));
    
    setFlashMessage('success', 'Tarifa actualizada exitosamente');
    redirect(APP_URL . 'admin/tarifas.php');
    
} catch (Exception $e) {
    error_log("Error al actualizar tarifa: " . $e->getMessage());
    setFlashMessage('danger', 'Error al actualizar la tarifa');
    redirect(APP_URL . 'admin/tarifas.php');
}
