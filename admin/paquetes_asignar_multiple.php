<?php
require_once '../config/config.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: paquetes.php');
    exit;
}

$paquetes_ids = $_POST['paquetes_ids'] ?? '';
$repartidor_id = $_POST['repartidor_id'] ?? '';

if (empty($paquetes_ids) || empty($repartidor_id)) {
    setFlashMessage('error', 'Datos incompletos para la asignación');
    header('Location: paquetes.php');
    exit;
}

// Convertir string de IDs a array
$ids_array = explode(',', $paquetes_ids);
$ids_array = array_filter(array_map('trim', $ids_array));

if (empty($ids_array)) {
    setFlashMessage('error', 'No se proporcionaron paquetes válidos');
    header('Location: paquetes.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Verificar que el repartidor existe y está activo
$stmt = $db->prepare("SELECT id, nombre, apellido FROM usuarios WHERE id = ? AND rol = 'repartidor' AND estado = 'activo'");
$stmt->bind_param("i", $repartidor_id);
$stmt->execute();
$repartidor = $stmt->get_result()->fetch_assoc();

if (!$repartidor) {
    setFlashMessage('error', 'El repartidor seleccionado no es válido');
    header('Location: paquetes.php');
    exit;
}

// Preparar la actualización
$placeholders = implode(',', array_fill(0, count($ids_array), '?'));
$sql = "UPDATE paquetes SET repartidor_id = ?, estado = 'en_ruta', fecha_asignacion = NOW() 
        WHERE id IN ($placeholders) AND estado = 'pendiente'";

$stmt = $db->prepare($sql);

// Crear array de tipos para bind_param
$types = 'i' . str_repeat('i', count($ids_array));
$params = array_merge([$repartidor_id], $ids_array);

// Usar call_user_func_array para bind_param dinámico
$bind_params = [];
$bind_params[] = $types;
foreach ($params as $key => $value) {
    $bind_params[] = &$params[$key];
}
call_user_func_array([$stmt, 'bind_param'], $bind_params);

if ($stmt->execute()) {
    $paquetes_asignados = $stmt->affected_rows;
    
    if ($paquetes_asignados > 0) {
        setFlashMessage('success', "Se asignaron exitosamente $paquetes_asignados paquete(s) al repartidor {$repartidor['nombre']} {$repartidor['apellido']}");
        
        // Registrar en historial cada paquete asignado
        $stmt_historial = $db->prepare("INSERT INTO paquetes_historial (paquete_id, estado_anterior, estado_nuevo, usuario_id, observaciones) VALUES (?, 'pendiente', 'en_ruta', ?, ?)");
        $admin_id = $_SESSION['user_id'];
        $observacion = "Asignado a repartidor: {$repartidor['nombre']} {$repartidor['apellido']} (Asignación masiva)";
        
        foreach ($ids_array as $paquete_id) {
            $stmt_historial->bind_param("iis", $paquete_id, $admin_id, $observacion);
            $stmt_historial->execute();
        }
    } else {
        setFlashMessage('warning', 'No se pudo asignar ningún paquete. Verifique que los paquetes estén en estado pendiente.');
    }
} else {
    setFlashMessage('error', 'Error al asignar los paquetes: ' . $db->error);
}

header('Location: paquetes.php');
exit;
