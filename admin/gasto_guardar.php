<?php
require_once '../config/config.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . 'admin/gastos.php');
    exit;
}

$fecha_gasto = sanitize($_POST['fecha_gasto']);
$categoria = sanitize($_POST['categoria']);
$descripcion = sanitize($_POST['descripcion']);
$monto = (float)$_POST['monto'];
$numero_comprobante = sanitize($_POST['numero_comprobante']);
$comprobante_archivo = null;

// Procesar archivo
if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === 0) {
    $upload_dir = UPLOADS_DIR . 'gastos/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    
    $ext = pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION);
    $filename = 'gasto_' . time() . '_' . uniqid() . '.' . $ext;
    move_uploaded_file($_FILES['comprobante']['tmp_name'], $upload_dir . $filename);
    $comprobante_archivo = $filename;
}

try {
    $db = Database::getInstance()->getConnection();
    $sql = "INSERT INTO gastos (fecha_gasto, categoria, descripcion, monto, numero_comprobante, comprobante_archivo, registrado_por) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$fecha_gasto, $categoria, $descripcion, $monto, $numero_comprobante, $comprobante_archivo, $_SESSION['usuario_id']]);
    
    logActivity("Gasto registrado: $descripcion - " . formatCurrency($monto), 'gastos', $db->lastInsertId());
    setFlashMessage('success', 'Gasto registrado exitosamente');
    
} catch (Exception $e) {
    setFlashMessage('danger', 'Error: ' . $e->getMessage());
}

redirect(APP_URL . 'admin/gastos.php');
