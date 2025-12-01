<?php
require_once '../config/config.php';
requireRole(['admin']);

$pageTitle = 'Gestión de Pagos a Empleados';

$db = Database::getInstance()->getConnection();

// Obtener repartidores activos con sus estadísticas de paquetes entregados
// Obtener repartidores de forma simple y directa
$repartidores = [];

$query_simple = "SELECT id, nombre, apellido, telefono FROM usuarios WHERE rol = 'repartidor' AND estado = 'activo' ORDER BY nombre";
$result = $db->query($query_simple);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Calcular estadísticas reales para cada repartidor
        $repartidor_id = $row['id'];
        
        // Calcular paquetes pendientes de pago (sin importar si hay pagos previos)
        $query_mes = "
            SELECT COUNT(*) as count,
                   SUM(
                       COALESCE(
                           (SELECT zt1.tarifa_repartidor FROM zonas_tarifas zt1 WHERE zt1.nombre_zona = p.distrito AND zt1.activo = 1 LIMIT 1),
                           (SELECT zt2.tarifa_repartidor FROM zonas_tarifas zt2 WHERE zt2.nombre_zona = p.ciudad AND zt2.activo = 1 LIMIT 1),
                           (SELECT zt3.tarifa_repartidor FROM zonas_tarifas zt3 WHERE zt3.nombre_zona = p.provincia AND zt3.activo = 1 LIMIT 1),
                           0
                       )
                   ) as total_monto
            FROM paquetes p
            INNER JOIN entregas e ON p.id = e.paquete_id
            WHERE p.repartidor_id = $repartidor_id 
            AND p.estado = 'entregado' 
            AND e.tipo_entrega = 'exitosa'
            AND p.ultimo_pago_id IS NULL";
        $result_mes = $db->query($query_mes);
        $data_mes = $result_mes ? $result_mes->fetch_assoc() : ['count' => 0, 'total_monto' => 0];
        $row['paquetes_mes_actual'] = $data_mes['count'];
        $row['monto_mes_actual'] = (float)$data_mes['total_monto'];
        
        // Obtener último pago realizado (para mostrar información)
        $query_ultimo_pago = "
            SELECT fecha_generacion, total_pagar 
            FROM pagos 
            WHERE repartidor_id = $repartidor_id 
            AND estado = 'pagado'
            ORDER BY fecha_generacion DESC 
            LIMIT 1
        ";
        $result_ultimo = $db->query($query_ultimo_pago);
        $row['ultimo_pago'] = $result_ultimo ? $result_ultimo->fetch_assoc() : null;
        
        // Total paquetes entregados (histórico)
        $query_entregados = "SELECT COUNT(*) as count FROM paquetes p INNER JOIN entregas e ON p.id = e.paquete_id WHERE p.repartidor_id = $repartidor_id AND p.estado = 'entregado' AND e.tipo_entrega = 'exitosa'";
        $result_entregados = $db->query($query_entregados);
        $row['total_paquetes_entregados'] = $result_entregados ? $result_entregados->fetch_assoc()['count'] : 0;
        
        // Total paquetes asignados (histórico)
        $query_asignados = "SELECT COUNT(*) as count FROM paquetes WHERE repartidor_id = $repartidor_id";
        $result_asignados = $db->query($query_asignados);
        $row['total_paquetes_asignados'] = $result_asignados ? $result_asignados->fetch_assoc()['count'] : 0;
        
        $repartidores[] = $row;
    }
}

// Si aún está vacío, agregar datos de prueba
if (empty($repartidores)) {
    $repartidores = [
        ['id' => 1, 'nombre' => 'Carlos', 'apellido' => 'Rodriguez', 'paquetes_mes_actual' => 0, 'monto_mes_actual' => 0, 'total_paquetes_entregados' => 0, 'total_paquetes_asignados' => 0, 'ultimo_pago' => null],
        ['id' => 2, 'nombre' => 'Juan', 'apellido' => 'Perez', 'paquetes_mes_actual' => 3, 'monto_mes_actual' => 7.50, 'total_paquetes_entregados' => 15, 'total_paquetes_asignados' => 20, 'ultimo_pago' => ['fecha_generacion' => '2025-10-15 10:30:00', 'total_pagar' => 15.00]]
    ];
}

// Obtener historial de pagos recientes
$sql_pagos_recientes = "
    SELECT p.*, u.nombre, u.apellido 
    FROM pagos p 
    INNER JOIN usuarios u ON p.repartidor_id = u.id 
    WHERE p.fecha_generacion >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
    ORDER BY p.fecha_generacion DESC 
    LIMIT 20
";
$pagos_recientes = Database::getInstance()->fetchAll($db->query($sql_pagos_recientes));

// Obtener resumen general
$sql_resumen = "SELECT 
    SUM(CASE WHEN p.estado = 'entregado' AND MONTH(p.fecha_entrega) = MONTH(CURRENT_DATE()) THEN 1 ELSE 0 END) as paquetes_mes,
    COUNT(DISTINCT CASE WHEN u.estado = 'activo' THEN u.id END) as repartidores_activos
    FROM usuarios u
    LEFT JOIN paquetes p ON u.id = p.repartidor_id
    WHERE u.rol = 'repartidor'
";
$resumen_basic = $db->query($sql_resumen)->fetch_assoc();

// Calcular pagos pendientes reales (montos que se deben a repartidores por paquetes entregados no pagados)
// Calcular pagos pendientes (paquetes entregados sin pagar)
$sql_pagos_pendientes = "
    SELECT SUM(
        COALESCE(
            (SELECT zt1.tarifa_repartidor FROM zonas_tarifas zt1 WHERE zt1.nombre_zona = p.distrito AND zt1.activo = 1 LIMIT 1),
            (SELECT zt2.tarifa_repartidor FROM zonas_tarifas zt2 WHERE zt2.nombre_zona = p.ciudad AND zt2.activo = 1 LIMIT 1),
            (SELECT zt3.tarifa_repartidor FROM zonas_tarifas zt3 WHERE zt3.nombre_zona = p.provincia AND zt3.activo = 1 LIMIT 1),
            0
        )
    ) as total_pendiente
    FROM paquetes p
    INNER JOIN entregas e ON p.id = e.paquete_id
    INNER JOIN usuarios u ON p.repartidor_id = u.id
    WHERE p.estado = 'entregado' 
    AND e.tipo_entrega = 'exitosa'
    AND u.rol = 'repartidor' 
    AND u.estado = 'activo'
    AND p.ultimo_pago_id IS NULL
";
$pagos_pendientes_result = $db->query($sql_pagos_pendientes);
$pagos_pendientes = $pagos_pendientes_result ? (float)$pagos_pendientes_result->fetch_assoc()['total_pendiente'] : 0;

$resumen = $resumen_basic;
$resumen['pagos_pendientes'] = $pagos_pendientes;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="content-header">
                <h1><i class="bi bi-cash-stack"></i> <?php echo $pageTitle; ?></h1>
                <div class="d-flex gap-2">
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalGenerarPago">
                        <i class="bi bi-plus-circle"></i> Generar Pago Individual
                    </button>
                    <button class="btn btn-primary" onclick="generarPagosMasivos()">
                        <i class="bi bi-cash-coin"></i> Generar Pagos Masivos
                    </button>
                </div>
            </div>

            <!-- Tarjetas de resumen -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title"><?php echo number_format($resumen['paquetes_mes']); ?></h4>
                                    <p class="card-text">Paquetes Entregados Este Mes</p>
                                </div>
                                <i class="bi bi-box-seam" style="font-size: 2rem; opacity: 0.8;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">S/ <?php echo number_format($resumen['pagos_pendientes'], 2); ?></h4>
                                    <p class="card-text">Pagos Pendientes</p>
                                </div>
                                <i class="bi bi-exclamation-triangle" style="font-size: 2rem; opacity: 0.8;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title"><?php echo $resumen['repartidores_activos']; ?></h4>
                                    <p class="card-text">Repartidores Activos</p>
                                </div>
                                <i class="bi bi-people" style="font-size: 2rem; opacity: 0.8;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Empleados -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-people-fill"></i> Empleados y Pagos por Paquetes Entregados</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Repartidor</th>
                                    <th>Paquetes Mes Actual</th>
                                    <th>Total Paquetes</th>
                                    <th>Monto Pendiente</th>
                                    <th>Último Pago</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($repartidores as $rep): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle bg-primary text-white me-2">
                                                <?php echo strtoupper(substr($rep['nombre'], 0, 1) . substr($rep['apellido'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo $rep['nombre'] . ' ' . $rep['apellido']; ?></strong>
                                                <br><small class="text-muted"><?php echo $rep['telefono']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info fs-6"><?php echo $rep['paquetes_mes_actual']; ?></span>
                                        <small class="text-muted d-block">paquetes entregados</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo $rep['total_paquetes_entregados']; ?></span>
                                        <small class="text-muted d-block">de <?php echo $rep['total_paquetes_asignados']; ?> asignados</small>
                                    </td>
                                    <td>
                                        <strong class="text-<?php echo $rep['monto_mes_actual'] > 0 ? 'success' : 'muted'; ?>">
                                            S/ <?php echo number_format($rep['monto_mes_actual'], 2); ?>
                                        </strong>
                                        <?php if ($rep['paquetes_mes_actual'] > 0): ?>
                                            <small class="text-muted d-block">
                                                <?php echo $rep['paquetes_mes_actual']; ?> paquetes pendientes
                                            </small>
                                        <?php elseif ($rep['ultimo_pago']): ?>
                                            <small class="text-success d-block">
                                                Último: S/ <?php echo number_format($rep['ultimo_pago']['total_pagar'], 2); ?>
                                                (<?php echo date('d/m/Y', strtotime($rep['ultimo_pago']['fecha_generacion'])); ?>)
                                            </small>
                                        <?php else: ?>
                                            <small class="text-muted d-block">Sin pagos realizados</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($rep['ultimo_pago']): ?>
                                            <small><?php echo date('d/m/Y', strtotime($rep['ultimo_pago']['fecha_generacion'])); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">Sin pagos</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($rep['monto_mes_actual'] > 0): ?>
                                            <span class="badge bg-warning">PENDIENTE</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">AL DÍA</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <?php if ($rep['monto_mes_actual'] > 0): ?>
                                                <button class="btn btn-success btn-sm" onclick="generarPagoIndividual(<?php echo $rep['id']; ?>, '<?php echo htmlspecialchars($rep['nombre'] . ' ' . $rep['apellido']); ?>', <?php echo $rep['monto_mes_actual']; ?>, <?php echo $rep['paquetes_mes_actual']; ?>)">
                                                    <i class="bi bi-cash"></i> Pagar Ahora
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-outline-primary btn-sm" onclick="verHistorialPagos(<?php echo $rep['id']; ?>, '<?php echo htmlspecialchars($rep['nombre'] . ' ' . $rep['apellido']); ?>')">
                                                <i class="bi bi-clock-history"></i> Historial
                                            </button>
                                            <button class="btn btn-outline-info btn-sm" onclick="verDetallesPaquetes(<?php echo $rep['id']; ?>)">
                                                <i class="bi bi-eye"></i> Detalles
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Historial de Pagos (Colapsable) -->
            <div class="collapse mt-4" id="historialPagos">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Historial de Pagos Recientes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Repartidor</th>
                                        <th>Concepto</th>
                                        <th>Paquetes</th>
                                        <th>Monto</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pagos_recientes as $pago): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($pago['fecha_generacion'])); ?></td>
                                        <td><?php echo $pago['nombre'] . ' ' . $pago['apellido']; ?></td>
                                        <td><?php echo $pago['concepto'] ?: 'Pago por paquetes entregados'; ?></td>
                                        <td><span class="badge bg-info"><?php echo $pago['total_paquetes'] ?: 0; ?></span></td>
                                        <td><strong>S/ <?php echo number_format($pago['total_pagar'] ?: $pago['monto'], 2); ?></strong></td>
                                        <td>
                                            <?php
                                            $badges = ['pendiente' => 'warning', 'pagado' => 'success', 'cancelado' => 'danger'];
                                            ?>
                                            <span class="badge bg-<?php echo $badges[$pago['estado']]; ?>">
                                                <?php echo ucfirst($pago['estado']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <?php if ($pago['estado'] === 'pendiente'): ?>
                                                    <button class="btn btn-success btn-sm" onclick="marcarPagado(<?php echo $pago['id']; ?>, '<?php echo $pago['nombre'] . ' ' . $pago['apellido']; ?>')">
                                                        <i class="bi bi-check"></i> Pagado
                                                    </button>
                                                    <button class="btn btn-danger btn-sm" onclick="cancelarPago(<?php echo $pago['id']; ?>, '<?php echo $pago['nombre'] . ' ' . $pago['apellido']; ?>')">
                                                        <i class="bi bi-x"></i> Cancelar
                                                    </button>
                                                <?php elseif ($pago['estado'] === 'cancelado'): ?>
                                                    <button class="btn btn-warning btn-sm" onclick="reactivarPago(<?php echo $pago['id']; ?>, '<?php echo $pago['nombre'] . ' ' . $pago['apellido']; ?>')">
                                                        <i class="bi bi-arrow-clockwise"></i> Reactivar
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted small">Sin acciones</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Generar Pago Individual -->
    <div class="modal fade" id="modalGenerarPago" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-cash"></i> Generar Pago Individual</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formGenerarPago" method="POST" action="pago_procesar.php">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Repartidor *</label>
                                    <select name="repartidor_id" id="selectRepartidor" class="form-select" required onchange="cargarDatosPago()">
                                        <option value="">Seleccionar repartidor...</option>
                                        <?php foreach ($repartidores as $rep): ?>
                                            <option value="<?php echo $rep['id']; ?>" 
                                                    data-nombre="<?php echo $rep['nombre'] . ' ' . $rep['apellido']; ?>"
                                                    data-paquetes="<?php echo $rep['paquetes_mes_actual']; ?>"
                                                    data-monto="<?php echo $rep['monto_mes_actual']; ?>"
                                                    <?php echo $rep['paquetes_mes_actual'] == 0 ? 'style="color: #dc3545; font-style: italic;"' : ''; ?>>
                                                <?php echo $rep['nombre'] . ' ' . $rep['apellido']; ?> 
                                                <?php if ($rep['paquetes_mes_actual'] == 0): ?>
                                                    - ⚠️ Sin paquetes entregados
                                                <?php else: ?>
                                                    (<?php echo $rep['paquetes_mes_actual']; ?> paquetes - S/ <?php echo number_format($rep['monto_mes_actual'], 2); ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Período</label>
                                    <input type="text" name="periodo" class="form-control" 
                                           value="<?php echo date('F Y'); ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Método de Pago *</label>
                                    <select name="metodo_pago" class="form-select" required>
                                        <option value="efectivo" selected>Efectivo</option>
                                        <option value="transferencia">Transferencia Bancaria</option>
                                        <option value="deposito">Depósito</option>
                                        <option value="yape">Yape</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Resumen del Pago</h6>
                                        <div class="mb-2">
                                            <strong>Empleado:</strong> <span id="nombreEmpleado">-</span>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Paquetes Pendientes:</strong> 
                                            <span class="badge bg-warning" id="cantidadPaquetes">0</span>
                                            <br><small class="text-muted">Incluye todos los paquetes no pagados</small>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Monto por Paquete:</strong> 
                                            <span id="montoPorPaquete">S/ 0.00</span>
                                        </div>
                                        <hr>
                                        <div class="mb-2">
                                            <strong>Subtotal:</strong> <span id="subtotal">S/ 0.00</span>
                                        </div>
                                        <div class="mb-2">
                                            <label>Bonificaciones:</label>
                                            <input type="number" name="bonificaciones" id="bonificaciones" 
                                                   class="form-control form-control-sm" step="0.01" value="0" 
                                                   onchange="calcularTotal()">
                                        </div>
                                        <div class="mb-2">
                                            <label>Deducciones:</label>
                                            <input type="number" name="deducciones" id="deducciones" 
                                                   class="form-control form-control-sm" step="0.01" value="0" 
                                                   onchange="calcularTotal()">
                                        </div>
                                        <hr>
                                        <div class="fs-5">
                                            <strong>Total a Pagar: <span class="text-success" id="totalPagar">S/ 0.00</span></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notas / Observaciones</label>
                            <textarea name="notas" class="form-control" rows="2" 
                                      placeholder="Observaciones adicionales sobre el pago..."></textarea>
                        </div>
                        <input type="hidden" name="total_paquetes" id="inputTotalPaquetes" value="0">
                        <input type="hidden" name="monto_por_paquete" id="inputMontoPorPaquete" value="0">
                        <input type="hidden" name="total_pagar" id="inputTotalPagar" value="0">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-success" id="btnGenerarPago" disabled onclick="procesarPago()">
                            <i class="bi bi-cash"></i> Pagar Todos los Pendientes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Detalles de Paquetes -->
    <div class="modal fade" id="modalDetallesPaquetes" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-box-seam"></i> Detalles de Paquetes del Empleado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="contenidoDetallesPaquetes">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Cambiar Estado de Pago -->
    <div class="modal fade" id="modalCambiarEstado" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloModalEstado">Cambiar Estado del Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="pago_actualizar_estado.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <strong>Empleado:</strong> <span id="empleadoModalEstado"></span>
                        </div>
                        <div class="mb-3" id="divMetodoPago" style="display: none;">
                            <label class="form-label">Método de Pago Real</label>
                            <select name="metodo_pago_real" class="form-select">
                                <option value="">Sin cambios</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia Bancaria</option>
                                <option value="deposito">Depósito</option>
                                <option value="yape">Yape</option>
                                <option value="plin">Plin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notas / Observaciones</label>
                            <textarea name="notas_pago" class="form-control" rows="3" 
                                      placeholder="Observaciones sobre el cambio de estado..."></textarea>
                        </div>
                        <input type="hidden" name="pago_id" id="pagoIdModal">
                        <input type="hidden" name="accion" id="accionModal">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btnConfirmarEstado">Confirmar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Historial de Pagos -->
    <div class="modal fade" id="modalHistorialPagos" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloHistorialPagos">
                        <i class="bi bi-clock-history"></i> Historial de Pagos
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="contenidoHistorialPagos">
                        <!-- El contenido se carga dinámicamente -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        .avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .card-success {
            border-left: 4px solid #28a745;
        }
    </style>

    <script>
        let repartidoresData = <?php echo json_encode($repartidores); ?>;
        
        function cargarDatosPago() {
            const select = document.getElementById('selectRepartidor');
            const selectedOption = select.options[select.selectedIndex];
            
            if (selectedOption.value) {
                const nombre = selectedOption.dataset.nombre;
                const paquetes = parseInt(selectedOption.dataset.paquetes);
                const monto = parseFloat(selectedOption.dataset.monto);
                const montoPorPaquete = paquetes > 0 ? (monto / paquetes) : 0;
                
                // Verificar si el repartidor no tiene paquetes entregados
                if (paquetes === 0) {
                    // Mostrar alerta de error
                    Swal.fire({
                        icon: 'warning',
                        title: '⚠️ Sin paquetes para pagar',
                        text: `${nombre} no tiene paquetes entregados en este período`,
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#ffc107',
                        background: '#fff3cd',
                        iconColor: '#856404'
                    });
                    
                    // Limpiar selección después de un momento
                    setTimeout(() => {
                        select.selectedIndex = 0;
                        limpiarResumen();
                    }, 100);
                    
                    return;
                }
                
                document.getElementById('nombreEmpleado').textContent = nombre;
                document.getElementById('cantidadPaquetes').textContent = paquetes;
                document.getElementById('montoPorPaquete').textContent = 'S/ ' + montoPorPaquete.toFixed(2);
                document.getElementById('subtotal').textContent = 'S/ ' + monto.toFixed(2);
                
                document.getElementById('inputTotalPaquetes').value = paquetes;
                document.getElementById('inputMontoPorPaquete').value = montoPorPaquete.toFixed(2);
                
                calcularTotal();
                console.log('Habilitando botón de pago, datos:', {
                    paquetes: paquetes,
                    monto: monto,
                    nombre: nombre
                });
                document.getElementById('btnGenerarPago').disabled = false;
            } else {
                limpiarResumen();
            }
        }
        
        function calcularTotal() {
            const select = document.getElementById('selectRepartidor');
            const selectedOption = select.options[select.selectedIndex];
            
            if (selectedOption.value) {
                const subtotal = parseFloat(selectedOption.dataset.monto);
                const bonificaciones = parseFloat(document.getElementById('bonificaciones').value) || 0;
                const deducciones = parseFloat(document.getElementById('deducciones').value) || 0;
                
                const total = subtotal + bonificaciones - deducciones;
                
                document.getElementById('totalPagar').textContent = 'S/ ' + total.toFixed(2);
                document.getElementById('inputTotalPagar').value = total.toFixed(2);
            }
        }
        
        function limpiarResumen() {
            document.getElementById('nombreEmpleado').textContent = '-';
            document.getElementById('cantidadPaquetes').textContent = '0';
            document.getElementById('montoPorPaquete').textContent = 'S/ 0.00';
            document.getElementById('subtotal').textContent = 'S/ 0.00';
            document.getElementById('totalPagar').textContent = 'S/ 0.00';
            document.getElementById('bonificaciones').value = '0';
            document.getElementById('deducciones').value = '0';
            document.getElementById('btnGenerarPago').disabled = true;
        }
        
        // Funciones globales para onclick
        function generarPagoIndividual(repartidorId, nombre, monto, paquetes) {
            console.log('generarPagoIndividual llamada:', repartidorId, nombre, monto, paquetes);
            const modal = new bootstrap.Modal(document.getElementById('modalGenerarPago'));
            const select = document.getElementById('selectRepartidor');
            
            select.value = repartidorId;
            cargarDatosPago();
            
            modal.show();
        }
        
        function verDetallesPaquetes(repartidorId) {
            const modal = new bootstrap.Modal(document.getElementById('modalDetallesPaquetes'));
            const contenido = document.getElementById('contenidoDetallesPaquetes');
            
            contenido.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p>Cargando detalles de paquetes...</p>
                </div>
            `;
            
            modal.show();
            
            // Cargar detalles vía AJAX
            fetch(`pago_detalles_paquetes.php?repartidor_id=${repartidorId}`)
                .then(response => response.text())
                .then(data => {
                    contenido.innerHTML = data;
                })
                .catch(error => {
                    contenido.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> Error al cargar los detalles: ${error.message}
                        </div>
                    `;
                });
        }
        
        function generarPagosMasivos() {
            if (confirm('¿Está seguro de generar pagos para todos los repartidores con paquetes entregados este mes?')) {
                // Mostrar indicador de carga
                const btn = event.target;
                const textoOriginal = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Generando...';
                btn.disabled = true;
                
                fetch('pago_generar_masivo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`Éxito: ${data.message}`);
                        location.reload();
                    } else {
                        alert(`Error: ${data.message}`);
                    }
                })
                .catch(error => {
                    alert('Error al generar pagos masivos: ' + error.message);
                })
                .finally(() => {
                    btn.innerHTML = textoOriginal;
                    btn.disabled = false;
                });
            }
        }
        
        function marcarPagado(pagoId, nombreEmpleado) {
            document.getElementById('pagoIdModal').value = pagoId;
            document.getElementById('accionModal').value = 'marcar_pagado';
            document.getElementById('tituloModalEstado').textContent = 'Marcar como Pagado';
            document.getElementById('textoModalEstado').textContent = `¿Confirmar que el pago para ${nombreEmpleado} ha sido realizado?`;
            document.getElementById('divMetodoPago').style.display = 'block';
            document.getElementById('btnConfirmarEstado').textContent = 'Marcar Pagado';
            document.getElementById('btnConfirmarEstado').className = 'btn btn-success';
            
            const modal = new bootstrap.Modal(document.getElementById('modalCambiarEstado'));
            modal.show();
        }
        
        function cancelarPago(pagoId, nombreEmpleado) {
            document.getElementById('pagoIdModal').value = pagoId;
            document.getElementById('accionModal').value = 'cancelar';
            document.getElementById('tituloModalEstado').textContent = 'Cancelar Pago';
            document.getElementById('textoModalEstado').textContent = `¿Está seguro de cancelar el pago para ${nombreEmpleado}?`;
            document.getElementById('divMetodoPago').style.display = 'none';
            document.getElementById('btnConfirmarEstado').textContent = 'Cancelar Pago';
            document.getElementById('btnConfirmarEstado').className = 'btn btn-danger';
            
            const modal = new bootstrap.Modal(document.getElementById('modalCambiarEstado'));
            modal.show();
        }
        
        function reactivarPago(pagoId, nombreEmpleado) {
            document.getElementById('pagoIdModal').value = pagoId;
            document.getElementById('accionModal').value = 'reactivar';
            document.getElementById('tituloModalEstado').textContent = 'Reactivar Pago';
            document.getElementById('textoModalEstado').textContent = `¿Confirmar reactivación del pago para ${nombreEmpleado}?`;
            document.getElementById('divMetodoPago').style.display = 'none';
            document.getElementById('btnConfirmarEstado').textContent = 'Reactivar Pago';
            document.getElementById('btnConfirmarEstado').className = 'btn btn-warning';
            
            const modal = new bootstrap.Modal(document.getElementById('modalCambiarEstado'));
            modal.show();
        }
        
        function verHistorialPagos(repartidorId, nombreEmpleado) {
            const modal = new bootstrap.Modal(document.getElementById('modalHistorialPagos'));
            const contenido = document.getElementById('contenidoHistorialPagos');
            const titulo = document.getElementById('tituloHistorialPagos');
            
            titulo.textContent = `Historial de Pagos - ${nombreEmpleado}`;
            
            contenido.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p>Cargando historial de pagos...</p>
                </div>
            `;
            
            modal.show();
            
            // Cargar historial vía AJAX
            fetch(`pago_historial.php?repartidor_id=${repartidorId}`)
                .then(response => response.text())
                .then(data => {
                    contenido.innerHTML = data;
                })
                .catch(error => {
                    contenido.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> Error al cargar el historial: ${error.message}
                        </div>
                    `;
                });
        }
        
        function verDetallesPaquetes(repartidorId) {
            const modal = new bootstrap.Modal(document.getElementById('modalDetallesPaquetes'));
            const contenido = document.getElementById('contenidoDetallesPaquetes');
            
            contenido.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p>Cargando detalles de paquetes...</p>
                </div>
            `;
            
            modal.show();
            
            // Cargar detalles vía AJAX
            fetch(`pago_detalles_paquetes.php?repartidor_id=${repartidorId}`)
                .then(response => response.text())
                .then(data => {
                    contenido.innerHTML = data;
                })
                .catch(error => {
                    contenido.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> Error al cargar los detalles: ${error.message}
                        </div>
                    `;
                });
        }
        
        function generarPagosMasivos() {
            if (confirm('¿Está seguro de generar pagos para todos los repartidores con paquetes entregados este mes?')) {
                // Mostrar indicador de carga
                const btn = event.target;
                const textoOriginal = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Generando...';
                btn.disabled = true;
                
                fetch('pago_generar_masivo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`Éxito: Se generaron ${data.pagos_creados} pagos correctamente.`);
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error al generar pagos masivos: ' + error.message);
                })
                .finally(() => {
                    btn.innerHTML = textoOriginal;
                    btn.disabled = false;
                });
            }
        }
        
        function marcarPagado(pagoId, nombreEmpleado) {
            document.getElementById('tituloModalEstado').textContent = 'Marcar Pago como Pagado';
            document.getElementById('empleadoModalEstado').textContent = nombreEmpleado;
            document.getElementById('pagoIdModal').value = pagoId;
            document.getElementById('accionModal').value = 'marcar_pagado';
            document.getElementById('divMetodoPago').style.display = 'block';
            document.getElementById('btnConfirmarEstado').textContent = 'Marcar como Pagado';
            document.getElementById('btnConfirmarEstado').className = 'btn btn-success';
            
            const modal = new bootstrap.Modal(document.getElementById('modalCambiarEstado'));
            modal.show();
        }
        
        function cancelarPago(pagoId, nombreEmpleado) {
            document.getElementById('tituloModalEstado').textContent = 'Cancelar Pago';
            document.getElementById('empleadoModalEstado').textContent = nombreEmpleado;
            document.getElementById('pagoIdModal').value = pagoId;
            document.getElementById('accionModal').value = 'cancelar';
            document.getElementById('divMetodoPago').style.display = 'none';
            document.getElementById('btnConfirmarEstado').textContent = 'Cancelar Pago';
            document.getElementById('btnConfirmarEstado').className = 'btn btn-danger';
            
            const modal = new bootstrap.Modal(document.getElementById('modalCambiarEstado'));
            modal.show();
        }
        
        function reactivarPago(pagoId, nombreEmpleado) {
            document.getElementById('tituloModalEstado').textContent = 'Reactivar Pago';
            document.getElementById('empleadoModalEstado').textContent = nombreEmpleado;
            document.getElementById('pagoIdModal').value = pagoId;
            document.getElementById('accionModal').value = 'reactivar';
            document.getElementById('divMetodoPago').style.display = 'none';
            document.getElementById('btnConfirmarEstado').textContent = 'Reactivar Pago';
            document.getElementById('btnConfirmarEstado').className = 'btn btn-warning';
            
            const modal = new bootstrap.Modal(document.getElementById('modalCambiarEstado'));
            modal.show();
        }
        
        // Función simple para procesar pago (global)
        function procesarPago() {
            console.log('procesarPago() llamada');
            
            const btn = document.getElementById('btnGenerarPago');
            const repartidor = document.getElementById('selectRepartidor');
            const form = document.getElementById('formGenerarPago');
            
            // Verificaciones básicas
            if (btn.disabled) {
                alert('Botón deshabilitado');
                return;
            }
            
            if (!repartidor.value) {
                alert('Seleccione un repartidor');
                return;
            }
            
            // Confirmar
            const nombre = repartidor.options[repartidor.selectedIndex].dataset.nombre || 'Repartidor';
            const monto = document.getElementById('inputTotalPagar').value;
            
            if (!confirm(`¿Confirmar pago de S/ ${monto} para ${nombre}?`)) {
                return;
            }
            
            // Cambiar botón
            btn.disabled = true;
            btn.innerHTML = 'Procesando...';
            
            // Enviar formulario directamente
            console.log('Enviando formulario...');
            form.submit();
        }
        
        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            
            // Limpiar formulario al cerrar modal
            document.getElementById('modalGenerarPago').addEventListener('hidden.bs.modal', function () {
                document.getElementById('formGenerarPago').reset();
                limpiarResumen();
            });
            

            
            // Limpiar modal de cambiar estado
            document.getElementById('modalCambiarEstado').addEventListener('hidden.bs.modal', function () {
                document.querySelector('#modalCambiarEstado form').reset();
            });
        });
        
        // Función para toggle del sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            
            sidebar.classList.toggle('active');
            if (mainContent) {
                mainContent.classList.toggle('expanded');
            }
        }

    </script>
    
    <!-- Chatbot Widget -->
    <?php include 'includes/chatbot_widget.php'; ?>
</body>
</html>

