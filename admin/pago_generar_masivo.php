<?php
require_once '../config/config.php';
requireRole('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    // Obtener repartidores con paquetes entregados este mes
    $sql_repartidores = "
        SELECT u.id, u.nombre, u.apellido,
               COUNT(p.id) as paquetes_mes,
               COALESCE(SUM(zt.tarifa_repartidor), 0) as monto_total
        FROM usuarios u
        INNER JOIN paquetes p ON u.id = p.repartidor_id
        LEFT JOIN zonas_tarifas zt ON (p.ciudad = zt.nombre_zona OR p.provincia = zt.nombre_zona) AND zt.activo = 1
        WHERE u.rol = 'repartidor' 
        AND u.estado = 'activo'
        AND p.estado = 'entregado'
        AND MONTH(p.fecha_entrega) = MONTH(CURRENT_DATE()) 
        AND YEAR(p.fecha_entrega) = YEAR(CURRENT_DATE())
        GROUP BY u.id, u.nombre, u.apellido
        HAVING paquetes_mes > 0
    ";
    
    $repartidores = Database::getInstance()->fetchAll($db->query($sql_repartidores));
    
    if (empty($repartidores)) {
        echo json_encode(['success' => false, 'message' => 'No hay repartidores con paquetes entregados este mes']);
        exit;
    }
    
    $pagos_creados = 0;
    $errores = [];
    $admin_id = $_SESSION['usuario_id'];
    
    $db->autocommit(false); // Iniciar transacción
    
    foreach ($repartidores as $rep) {
        try {
            // Verificar si ya existe un pago para este repartidor este mes
            $stmt_check = $db->prepare("
                SELECT id FROM pagos 
                WHERE repartidor_id = ? 
                AND MONTH(fecha_generacion) = MONTH(CURRENT_DATE()) 
                AND YEAR(fecha_generacion) = YEAR(CURRENT_DATE())
                AND estado != 'cancelado'
            ");
            $stmt_check->bind_param("i", $rep['id']);
            $stmt_check->execute();
            $existe = $stmt_check->get_result()->fetch_assoc();
            
            if ($existe) {
                $errores[] = "Ya existe pago para {$rep['nombre']} {$rep['apellido']}";
                continue;
            }
            
            // Datos del pago
            $periodo = date('F Y');
            $concepto = "Pago por {$rep['paquetes_mes']} paquetes entregados - {$periodo}";
            $monto_por_paquete = $rep['paquetes_mes'] > 0 ? ($rep['monto_total'] / $rep['paquetes_mes']) : 0;
            $fecha_inicio = date('Y-m-01');
            $fecha_fin = date('Y-m-t');
            
            // Insertar pago
            $stmt_insert = $db->prepare("
                INSERT INTO pagos (
                    repartidor_id, concepto, periodo, monto, 
                    periodo_inicio, periodo_fin, total_paquetes, monto_por_paquete,
                    bonificaciones, deducciones, total_pagar, estado,
                    metodo_pago, registrado_por, generado_por
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?, 'pendiente', 'transferencia', ?, ?)
            ");
            
            $stmt_insert->bind_param(
                "issdssiddii", 
                $rep['id'], $concepto, $periodo, $rep['monto_total'],
                $fecha_inicio, $fecha_fin, $rep['paquetes_mes'], $monto_por_paquete,
                $rep['monto_total'], $admin_id, $admin_id
            );
            
            if ($stmt_insert->execute()) {
                $pago_id = $db->insert_id;
                
                // Registrar actividad
                logActivity(
                    'Generar pago masivo', 
                    'pagos', 
                    $pago_id, 
                    "Pago masivo generado para {$rep['nombre']} {$rep['apellido']} - S/ {$rep['monto_total']}"
                );
                
                // Crear notificación para el repartidor
                try {
                    $stmt_notif = $db->prepare("
                        INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, fecha_creacion) 
                        VALUES (?, 'pago', 'Pago Generado', ?, NOW())
                    ");
                    $mensaje_notif = "Se ha generado un pago de S/ {$rep['monto_total']} por {$rep['paquetes_mes']} paquetes entregados";
                    $stmt_notif->bind_param("is", $rep['id'], $mensaje_notif);
                    $stmt_notif->execute();
                } catch (Exception $e) {
                    // Si falla la notificación, no interrumpir el proceso
                    error_log("Error al crear notificación de pago masivo: " . $e->getMessage());
                }
                
                $pagos_creados++;
            } else {
                $errores[] = "Error al crear pago para {$rep['nombre']} {$rep['apellido']}: " . $stmt_insert->error;
            }
            
        } catch (Exception $e) {
            $errores[] = "Error procesando {$rep['nombre']} {$rep['apellido']}: " . $e->getMessage();
        }
    }
    
    if ($pagos_creados > 0) {
        $db->commit(); // Confirmar transacción
        
        $mensaje = "Se crearon {$pagos_creados} pagos correctamente.";
        if (!empty($errores)) {
            $mensaje .= " Errores: " . implode(', ', $errores);
        }
        
        echo json_encode([
            'success' => true, 
            'pagos_creados' => $pagos_creados,
            'message' => $mensaje,
            'errores' => $errores
        ]);
    } else {
        $db->rollback(); // Revertir transacción
        echo json_encode([
            'success' => false, 
            'message' => 'No se pudo crear ningún pago. Errores: ' . implode(', ', $errores)
        ]);
    }
    
} catch (Exception $e) {
    $db->rollback(); // Revertir transacción en caso de error
    error_log("Error en pago masivo: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
} finally {
    $db->autocommit(true); // Restaurar autocommit
}
?>