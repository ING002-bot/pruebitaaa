<?php
require_once '../config/config.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . 'admin/pagos.php');
    exit;
}

$repartidor_id = (int)$_POST['repartidor_id'];
$concepto = sanitize($_POST['concepto']);
$periodo = sanitize($_POST['periodo']);
$monto = (float)$_POST['monto'];
$metodo_pago = sanitize($_POST['metodo_pago']);
$fecha_pago = sanitize($_POST['fecha_pago']);

try {
    $db = Database::getInstance()->getConnection();
    $sql = "INSERT INTO pagos (repartidor_id, concepto, periodo, monto, metodo_pago, fecha_pago, estado, registrado_por) 
            VALUES (?, ?, ?, ?, ?, ?, 'pagado', ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$repartidor_id, $concepto, $periodo, $monto, $metodo_pago, $fecha_pago, $_SESSION['usuario_id']]);
    
    logActivity("Pago registrado: $concepto - " . formatCurrency($monto), 'pagos', $db->lastInsertId());
    setFlashMessage('success', 'Pago registrado exitosamente');
    
} catch (Exception $e) {
    setFlashMessage('danger', 'Error: ' . $e->getMessage());
}

redirect(APP_URL . 'admin/pagos.php');
