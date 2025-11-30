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
    $costo_cliente = (float)$_POST['costo_cliente'];
    $tarifa_repartidor = (float)$_POST['tarifa_repartidor'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validaciones
    if ($costo_cliente <= 0 || $tarifa_repartidor <= 0) {
        throw new Exception("Las tarifas deben ser mayores a cero");
    }
    
    if ($costo_cliente <= $tarifa_repartidor) {
        throw new Exception("La tarifa al cliente debe ser mayor que la tarifa al repartidor para tener ganancia");
    }
    
    // Verificar si ya existe la zona
    $stmt = $db->prepare("SELECT id FROM zonas_tarifas WHERE categoria = ? AND nombre_zona = ?");
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . $db->error);
    }
    $stmt->bind_param("ss", $categoria, $nombre_zona);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->close();
        setFlashMessage('danger', 'Ya existe una zona con ese nombre en esta categoría');
        redirect(APP_URL . 'admin/tarifas.php');
    }
    $stmt->close();
    
    // Insertar nueva tarifa
    $stmt = $db->prepare("
        INSERT INTO zonas_tarifas (categoria, nombre_zona, tipo_envio, costo_cliente, tarifa_repartidor, activo)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . $db->error);
    }
    
    $stmt->bind_param("sssddi", $categoria, $nombre_zona, $tipo_envio, $costo_cliente, $tarifa_repartidor, $activo);
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar consulta: " . $stmt->error);
    }
    
    $tarifa_id = $db->insert_id;
    $stmt->close();
    
    logActivity('Crear tarifa', 'zonas_tarifas', $tarifa_id, "Zona: $nombre_zona, Tarifa: S/ $tarifa_repartidor");
    
    setFlashMessage('success', 'Zona creada exitosamente');
    redirect(APP_URL . 'admin/tarifas.php');
    
} catch (Exception $e) {
    error_log("Error al crear tarifa: " . $e->getMessage());
    setFlashMessage('danger', 'Error al crear la zona');
    redirect(APP_URL . 'admin/tarifas.php');
}
