<?php
require_once '../config/config.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('danger', 'Método no permitido');
    redirect(APP_URL . 'admin/pagos.php');
}

$db = Database::getInstance()->getConnection();

try {
    // Debug: Mostrar datos recibidos
    error_log("DEBUG PAGO_PROCESAR - POST recibido: " . print_r($_POST, true));
    
    // Obtener datos del formulario
    $repartidor_id = (int)$_POST['repartidor_id'];
    $periodo = sanitize($_POST['periodo']);
    $metodo_pago = sanitize($_POST['metodo_pago']);
    $bonificaciones = (float)($_POST['bonificaciones'] ?? 0);
    $deducciones = (float)($_POST['deducciones'] ?? 0);
    $notas = sanitize($_POST['notas'] ?? '');
    $total_paquetes = (int)($_POST['total_paquetes'] ?? 0);
    $monto_por_paquete = (float)($_POST['monto_por_paquete'] ?? 0);
    $total_pagar = (float)($_POST['total_pagar'] ?? 0);
    
    // Validaciones básicas
    if ($repartidor_id <= 0 || $total_pagar <= 0) {
        throw new Exception("Datos inválidos para generar el pago");
    }
    
    // Verificar que el repartidor existe y está activo
    $stmt = $db->prepare("SELECT nombre, apellido FROM usuarios WHERE id = ? AND rol = 'repartidor' AND estado = 'activo'");
    $stmt->bind_param("i", $repartidor_id);
    $stmt->execute();
    $repartidor = $stmt->get_result()->fetch_assoc();
    
    if (!$repartidor) {
        throw new Exception("El repartidor seleccionado no es válido");
    }
    
    // Verificar que hay paquetes pendientes de pago
    $stmt_verificar = $db->prepare("
        SELECT COUNT(*) as count FROM paquetes p
        INNER JOIN entregas e ON p.id = e.paquete_id
        WHERE p.repartidor_id = ? 
        AND p.estado = 'entregado' 
        AND e.tipo_entrega = 'exitosa'
        AND p.ultimo_pago_id IS NULL
    ");
    $stmt_verificar->bind_param("i", $repartidor_id);
    $stmt_verificar->execute();
    $paquetes_pendientes = $stmt_verificar->get_result()->fetch_assoc();
    
    if ($paquetes_pendientes['count'] == 0) {
        throw new Exception("No hay paquetes pendientes de pago para este repartidor");
    }
    
    // Obtener los paquetes entregados del mes para el concepto
    $fecha_inicio = date('Y-m-01');
    $fecha_fin = date('Y-m-t');
    
    $concepto = "Pago por {$total_paquetes} paquetes entregados - {$periodo}";
    
    // Insertar el pago
    $stmt = $db->prepare("
        INSERT INTO pagos (
            repartidor_id, concepto, periodo, monto, 
            periodo_inicio, periodo_fin, total_paquetes, monto_por_paquete,
            bonificaciones, deducciones, total_pagar, estado,
            metodo_pago, registrado_por, notas, generado_por
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', ?, ?, ?, ?)
    ");
    
    $monto_base = $total_paquetes * $monto_por_paquete;
    $admin_id = $_SESSION['usuario_id'];
    
    $stmt->bind_param(
        "issdssidddssisi", 
        $repartidor_id, $concepto, $periodo, $monto_base,
        $fecha_inicio, $fecha_fin, $total_paquetes, $monto_por_paquete,
        $bonificaciones, $deducciones, $total_pagar, 
        $metodo_pago, $admin_id, $notas, $admin_id
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Error al registrar el pago: " . $stmt->error);
    }
    
    $pago_id = $db->insert_id;
    
    // Marcar TODOS los paquetes pendientes como pagados (no solo del mes actual)
    $stmt_mark = $db->prepare("
        UPDATE paquetes p
        INNER JOIN entregas e ON p.id = e.paquete_id
        SET p.ultimo_pago_id = ?
        WHERE p.repartidor_id = ? 
        AND p.estado = 'entregado' 
        AND e.tipo_entrega = 'exitosa'
        AND p.ultimo_pago_id IS NULL
    ");
    $stmt_mark->bind_param("ii", $pago_id, $repartidor_id);
    $stmt_mark->execute();
    
    // Registrar como gasto en el historial de gastos
    try {
        $stmt_gasto = $db->prepare("
            INSERT INTO gastos (
                fecha_gasto, categoria, descripcion, monto, 
                registrado_por, fecha_registro, estado
            ) VALUES (CURDATE(), 'Pagos a Personal', ?, ?, ?, NOW(), 'confirmado')
        ");
        
        if ($stmt_gasto) {
            $descripcion_gasto = "Pago a repartidor {$repartidor['nombre']} {$repartidor['apellido']} - {$total_paquetes} paquetes";
            $stmt_gasto->bind_param("sdi", $descripcion_gasto, $total_pagar, $admin_id);
            $stmt_gasto->execute();
        } else {
            error_log("Error al preparar statement de gastos: " . $db->error);
        }
    } catch (Exception $e) {
        // Si falla el registro de gasto, no interrumpir el pago
        error_log("Error al registrar gasto: " . $e->getMessage());
    }
    
    // Registrar actividad
    logActivity(
        'Generar pago', 
        'pagos', 
        $pago_id, 
        "Pago generado para {$repartidor['nombre']} {$repartidor['apellido']} - S/ {$total_pagar}"
    );
    
    // Crear notificación para el repartidor (opcional)
    try {
        $stmt_notif = $db->prepare("
            INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, fecha_creacion) 
            VALUES (?, 'pago', 'Pago Generado', ?, NOW())
        ");
        $mensaje_notif = "Se ha generado un pago de S/ {$total_pagar} por {$total_paquetes} paquetes entregados";
        $stmt_notif->bind_param("is", $repartidor_id, $mensaje_notif);
        $stmt_notif->execute();
    } catch (Exception $e) {
        // Si falla la notificación, no interrumpir el proceso
        error_log("Error al crear notificación de pago: " . $e->getMessage());
    }
    
    setFlashMessage('success', "Pago generado exitosamente para {$repartidor['nombre']} {$repartidor['apellido']} por S/ " . number_format($total_pagar, 2));
    redirect(APP_URL . 'admin/pagos.php');
    
} catch (Exception $e) {
    error_log("Error al generar pago: " . $e->getMessage());
    setFlashMessage('danger', 'Error al generar el pago: ' . $e->getMessage());
    redirect(APP_URL . 'admin/pagos.php');
}
?>