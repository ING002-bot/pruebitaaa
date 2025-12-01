<?php
require_once '../config/config.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('danger', 'Método no permitido');
    redirect(APP_URL . 'admin/pagos.php');
}

$pago_id = (int)($_POST['pago_id'] ?? 0);
$accion = sanitize($_POST['accion'] ?? '');
$metodo_pago_real = sanitize($_POST['metodo_pago_real'] ?? '');
$notas_pago = sanitize($_POST['notas_pago'] ?? '');

if ($pago_id <= 0 || !in_array($accion, ['marcar_pagado', 'cancelar', 'reactivar'])) {
    setFlashMessage('danger', 'Datos inválidos');
    redirect(APP_URL . 'admin/pagos.php');
}

$db = Database::getInstance()->getConnection();

try {
    // Obtener información del pago
    $stmt = $db->prepare("
        SELECT p.*, u.nombre, u.apellido 
        FROM pagos p 
        INNER JOIN usuarios u ON p.repartidor_id = u.id 
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $pago_id);
    $stmt->execute();
    $pago = $stmt->get_result()->fetch_assoc();
    
    if (!$pago) {
        throw new Exception("Pago no encontrado");
    }
    
    switch ($accion) {
        case 'marcar_pagado':
            if ($pago['estado'] === 'pagado') {
                throw new Exception("Este pago ya está marcado como pagado");
            }
            
            $stmt_update = $db->prepare("
                UPDATE pagos 
                SET estado = 'pagado', 
                    fecha_pago = NOW(),
                    metodo_pago = COALESCE(NULLIF(?, ''), metodo_pago),
                    notas = CONCAT(COALESCE(notas, ''), ?, ' [Pagado por admin: ', ?, ' el ', NOW(), ']')
                WHERE id = ?
            ");
            $nota_adicional = $notas_pago ? "\n" . $notas_pago : '';
            $stmt_update->bind_param("sssi", $metodo_pago_real, $nota_adicional, $_SESSION['nombre'], $pago_id);
            
            if ($stmt_update->execute()) {
                logActivity(
                    'Marcar pago como pagado', 
                    'pagos', 
                    $pago_id, 
                    "Pago marcado como pagado para {$pago['nombre']} {$pago['apellido']} - S/ {$pago['total_pagar']}"
                );
                
                setFlashMessage('success', "Pago marcado como pagado para {$pago['nombre']} {$pago['apellido']}");
            } else {
                throw new Exception("Error al actualizar el pago");
            }
            break;
            
        case 'cancelar':
            if ($pago['estado'] === 'cancelado') {
                throw new Exception("Este pago ya está cancelado");
            }
            
            $stmt_update = $db->prepare("
                UPDATE pagos 
                SET estado = 'cancelado',
                    notas = CONCAT(COALESCE(notas, ''), ?, ' [Cancelado por admin: ', ?, ' el ', NOW(), ']')
                WHERE id = ?
            ");
            $nota_cancelacion = $notas_pago ? "\n" . $notas_pago : "\nPago cancelado";
            $stmt_update->bind_param("ssi", $nota_cancelacion, $_SESSION['nombre'], $pago_id);
            
            if ($stmt_update->execute()) {
                logActivity(
                    'Cancelar pago', 
                    'pagos', 
                    $pago_id, 
                    "Pago cancelado para {$pago['nombre']} {$pago['apellido']} - S/ {$pago['total_pagar']}"
                );
                
                setFlashMessage('warning', "Pago cancelado para {$pago['nombre']} {$pago['apellido']}");
            } else {
                throw new Exception("Error al cancelar el pago");
            }
            break;
            
        case 'reactivar':
            if ($pago['estado'] === 'pendiente') {
                throw new Exception("Este pago ya está pendiente");
            }
            
            $stmt_update = $db->prepare("
                UPDATE pagos 
                SET estado = 'pendiente',
                    fecha_pago = NULL,
                    notas = CONCAT(COALESCE(notas, ''), ?, ' [Reactivado por admin: ', ?, ' el ', NOW(), ']')
                WHERE id = ?
            ");
            $nota_reactivacion = $notas_pago ? "\n" . $notas_pago : "\nPago reactivado";
            $stmt_update->bind_param("ssi", $nota_reactivacion, $_SESSION['nombre'], $pago_id);
            
            if ($stmt_update->execute()) {
                logActivity(
                    'Reactivar pago', 
                    'pagos', 
                    $pago_id, 
                    "Pago reactivado para {$pago['nombre']} {$pago['apellido']} - S/ {$pago['total_pagar']}"
                );
                
                setFlashMessage('info', "Pago reactivado para {$pago['nombre']} {$pago['apellido']}");
            } else {
                throw new Exception("Error al reactivar el pago");
            }
            break;
    }
    
} catch (Exception $e) {
    error_log("Error en pago_actualizar_estado.php: " . $e->getMessage());
    setFlashMessage('danger', 'Error: ' . $e->getMessage());
}

redirect(APP_URL . 'admin/pagos.php');
?>