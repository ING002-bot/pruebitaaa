<?php
require_once '../config/config.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . 'admin/tarifas.php');
}

$db = Database::getInstance()->getConnection();

try {
    $categoria = sanitize($_POST['categoria']);
    $nombre_zona = sanitize($_POST['nombre_zona']);
    $tipo_envio = sanitize($_POST['tipo_envio']);
    $tarifa_repartidor = (float)$_POST['tarifa_repartidor'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Verificar si ya existe la zona
    $stmt = $db->prepare("SELECT id FROM zonas_tarifas WHERE categoria = ? AND nombre_zona = ?");
    $stmt->execute([$categoria, $nombre_zona]);
    
    if ($stmt->fetch()) {
        setFlashMessage('danger', 'Ya existe una zona con ese nombre en esta categoría');
        redirect(APP_URL . 'admin/tarifas.php');
    }
    
    // Insertar nueva tarifa
    $stmt = $db->prepare("
        INSERT INTO zonas_tarifas (categoria, nombre_zona, tipo_envio, tarifa_repartidor, activo)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$categoria, $nombre_zona, $tipo_envio, $tarifa_repartidor, $activo]);
    
    logActivity('Crear tarifa', 'zonas_tarifas', $db->lastInsertId(), "Zona: $nombre_zona, Tarifa: S/ $tarifa_repartidor");
    
    setFlashMessage('success', 'Zona creada exitosamente');
    redirect(APP_URL . 'admin/tarifas.php');
    
} catch (Exception $e) {
    error_log("Error al crear tarifa: " . $e->getMessage());
    setFlashMessage('danger', 'Error al crear la zona');
    redirect(APP_URL . 'admin/tarifas.php');
}
