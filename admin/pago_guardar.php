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
    
    // Verificar si existe la columna 'concepto' en la tabla pagos
    $check_column = $db->query("SHOW COLUMNS FROM pagos LIKE 'concepto'");
    
    if ($check_column && $check_column->num_rows > 0) {
        // La tabla tiene la estructura nueva con columna 'concepto'
        $sql = "INSERT INTO pagos (repartidor_id, concepto, periodo, monto, metodo_pago, fecha_pago, estado, registrado_por) 
                VALUES (?, ?, ?, ?, ?, ?, 'pagado', ?)";
        $stmt = $db->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $db->error);
        }
        
        $estado = 'pagado';
        $stmt->bind_param("issdssi", $repartidor_id, $concepto, $periodo, $monto, $metodo_pago, $fecha_pago, $_SESSION['usuario_id']);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
        }
        
        $pago_id = $db->insert_id;
        $stmt->close();
        
    } else {
        // La tabla tiene la estructura antigua, necesitamos adaptarla
        // Calcular fechas del período si está especificado
        $periodo_inicio = date('Y-m-01', strtotime($fecha_pago));
        $periodo_fin = date('Y-m-t', strtotime($fecha_pago));
        
        $sql = "INSERT INTO pagos (repartidor_id, periodo_inicio, periodo_fin, total_paquetes, monto_por_paquete, 
                bonificaciones, deducciones, total_pagar, estado, fecha_pago, metodo_pago, notas, generado_por) 
                VALUES (?, ?, ?, 0, 0, 0, 0, ?, 'pagado', ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $db->error);
        }
        
        $notas = "Concepto: " . $concepto . ($periodo ? " | Periodo: " . $periodo : "");
        $stmt->bind_param("issdsssi", $repartidor_id, $periodo_inicio, $periodo_fin, $monto, $fecha_pago, $metodo_pago, $notas, $_SESSION['usuario_id']);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
        }
        
        $pago_id = $db->insert_id;
        $stmt->close();
    }
    
    logActivity("Pago registrado: $concepto - " . formatCurrency($monto), 'pagos', $pago_id);
    setFlashMessage('success', 'Pago registrado exitosamente');
    
} catch (Exception $e) {
    error_log("Error en pago_guardar.php: " . $e->getMessage());
    setFlashMessage('danger', 'Error al registrar el pago: ' . $e->getMessage());
}

redirect(APP_URL . 'admin/pagos.php');
