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
    
    // Verificar si existe la columna 'descripcion'
    $check_column = $db->query("SHOW COLUMNS FROM gastos LIKE 'descripcion'");
    $tiene_descripcion = ($check_column && $check_column->num_rows > 0);
    
    if ($tiene_descripcion) {
        // Estructura nueva con descripcion, numero_comprobante, comprobante_archivo
        $sql = "INSERT INTO gastos (fecha_gasto, categoria, descripcion, monto, numero_comprobante, comprobante_archivo, registrado_por) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar consulta: " . $db->error);
        }
        
        $stmt->bind_param("sssdssi", $fecha_gasto, $categoria, $descripcion, $monto, $numero_comprobante, $comprobante_archivo, $_SESSION['usuario_id']);
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar consulta: " . $stmt->error);
        }
    } else {
        // Estructura antigua - usar campo 'concepto' y 'notas'
        $notas = "Comprobante: " . ($numero_comprobante ?: 'N/A');
        if ($comprobante_archivo) {
            $notas .= " | Archivo: " . $comprobante_archivo;
        }
        
        $sql = "INSERT INTO gastos (fecha_gasto, categoria, concepto, monto, notas, registrado_por) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar consulta: " . $db->error);
        }
        
        $stmt->bind_param("sssdsi", $fecha_gasto, $categoria, $descripcion, $monto, $notas, $_SESSION['usuario_id']);
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar consulta: " . $stmt->error);
        }
    }
    
    $gasto_id = $db->insert_id;
    $stmt->close();
    
    logActivity("Gasto registrado: $descripcion - " . formatCurrency($monto), 'gastos', $gasto_id);
    setFlashMessage('success', 'Gasto registrado exitosamente');
    
} catch (Exception $e) {
    setFlashMessage('danger', 'Error: ' . $e->getMessage());
}

redirect(APP_URL . 'admin/gastos.php');
