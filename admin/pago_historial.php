<?php
require_once '../config/config.php';
requireRole(['admin']);

if (!isset($_GET['repartidor_id']) || !is_numeric($_GET['repartidor_id'])) {
    echo '<div class="alert alert-danger">ID de repartidor inválido</div>';
    exit;
}

$repartidor_id = (int)$_GET['repartidor_id'];
$db = Database::getInstance()->getConnection();

try {
    // Obtener información del repartidor
    $stmt = $db->prepare("SELECT nombre, apellido FROM usuarios WHERE id = ? AND rol = 'repartidor'");
    $stmt->bind_param("i", $repartidor_id);
    $stmt->execute();
    $repartidor = $stmt->get_result()->fetch_assoc();
    
    if (!$repartidor) {
        echo '<div class="alert alert-danger">Repartidor no encontrado</div>';
        exit;
    }
    
    // Obtener historial de pagos
    $query = "
        SELECT 
            p.*,
            COUNT(pq.id) as paquetes_incluidos,
            (SELECT COUNT(*) FROM paquetes pq2 
             INNER JOIN entregas e2 ON pq2.id = e2.paquete_id 
             WHERE pq2.repartidor_id = p.repartidor_id 
             AND pq2.ultimo_pago_id = p.id
             AND pq2.estado = 'entregado'
             AND e2.tipo_entrega = 'exitosa') as paquetes_pagados
        FROM pagos p
        LEFT JOIN paquetes pq ON pq.ultimo_pago_id = p.id
        WHERE p.repartidor_id = ?
        GROUP BY p.id
        ORDER BY p.fecha_generacion DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $repartidor_id);
    $stmt->execute();
    $pagos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Calcular estadísticas
    $total_pagos = count($pagos);
    $total_monto = array_sum(array_column($pagos, 'total_pagar'));
    $pagos_pendientes = count(array_filter($pagos, function($p) { return $p['estado'] === 'pendiente'; }));
    $pagos_completados = count(array_filter($pagos, function($p) { return $p['estado'] === 'pagado'; }));
    
    ?>
    
    <!-- Resumen estadístico -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title"><?php echo $total_pagos; ?></h5>
                    <p class="card-text">Total Pagos</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">S/ <?php echo number_format($total_monto, 2); ?></h5>
                    <p class="card-text">Monto Total</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title"><?php echo $pagos_completados; ?></h5>
                    <p class="card-text">Pagados</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title"><?php echo $pagos_pendientes; ?></h5>
                    <p class="card-text">Pendientes</p>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (empty($pagos)): ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle"></i> 
            <strong><?php echo $repartidor['nombre'] . ' ' . $repartidor['apellido']; ?></strong> 
            aún no tiene pagos registrados.
        </div>
    <?php else: ?>
        <!-- Tabla de historial -->
        <div class="table-responsive">
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Fecha</th>
                        <th>Concepto</th>
                        <th>Paquetes</th>
                        <th>Monto</th>
                        <th>Método</th>
                        <th>Estado</th>
                        <th>Bonif./Deduc.</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pagos as $pago): ?>
                        <tr>
                            <td>
                                <strong><?php echo date('d/m/Y', strtotime($pago['fecha_generacion'])); ?></strong>
                                <br>
                                <small class="text-muted"><?php echo date('H:i', strtotime($pago['fecha_generacion'])); ?></small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($pago['concepto'] ?: 'Pago por paquetes entregados'); ?>
                                <?php if (isset($pago['periodo']) && !empty($pago['periodo'])): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($pago['periodo']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo $pago['paquetes_pagados'] ?: $pago['total_paquetes']; ?></span>
                                <?php if (isset($pago['monto_por_paquete']) && $pago['monto_por_paquete'] > 0): ?>
                                    <br><small class="text-muted">S/ <?php echo number_format($pago['monto_por_paquete'], 2); ?> c/u</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong class="text-success">S/ <?php echo number_format($pago['total_pagar'], 2); ?></strong>
                                <?php if (isset($pago['monto']) && $pago['monto'] != $pago['total_pagar']): ?>
                                    <br><small class="text-muted">Base: S/ <?php echo number_format($pago['monto'], 2); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?php echo ucfirst($pago['metodo_pago']); ?></span>
                                <?php if (isset($pago['metodo_pago_real']) && $pago['metodo_pago_real'] && $pago['metodo_pago_real'] != $pago['metodo_pago']): ?>
                                    <br><small class="text-success">Real: <?php echo ucfirst($pago['metodo_pago_real']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $badges = [
                                    'pendiente' => 'warning',
                                    'pagado' => 'success',
                                    'cancelado' => 'danger'
                                ];
                                ?>
                                <span class="badge bg-<?php echo $badges[$pago['estado']]; ?>">
                                    <?php echo ucfirst($pago['estado']); ?>
                                </span>
                                <?php if (isset($pago['fecha_pago']) && $pago['fecha_pago'] && $pago['estado'] === 'pagado'): ?>
                                    <br><small class="text-muted">
                                        Pagado: <?php echo date('d/m/Y', strtotime($pago['fecha_pago'])); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (isset($pago['bonificaciones']) && $pago['bonificaciones'] > 0): ?>
                                    <small class="text-success">+S/ <?php echo number_format($pago['bonificaciones'], 2); ?></small>
                                <?php endif; ?>
                                <?php if (isset($pago['deducciones']) && $pago['deducciones'] > 0): ?>
                                    <small class="text-danger">-S/ <?php echo number_format($pago['deducciones'], 2); ?></small>
                                <?php endif; ?>
                                <?php if ((!isset($pago['bonificaciones']) || $pago['bonificaciones'] == 0) && (!isset($pago['deducciones']) || $pago['deducciones'] == 0)): ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($pago['notas'])): ?>
                                    <small><?php echo htmlspecialchars($pago['notas']); ?></small>
                                <?php elseif (isset($pago['notas_pago']) && !empty($pago['notas_pago'])): ?>
                                    <small><?php echo htmlspecialchars($pago['notas_pago']); ?></small>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Resumen final -->
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-calendar-month"></i> Resumen Mensual</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        // Agrupar por mes
                        $por_mes = [];
                        foreach ($pagos as $pago) {
                            $mes = date('Y-m', strtotime($pago['fecha_generacion']));
                            if (!isset($por_mes[$mes])) {
                                $por_mes[$mes] = ['count' => 0, 'monto' => 0];
                            }
                            $por_mes[$mes]['count']++;
                            $por_mes[$mes]['monto'] += $pago['total_pagar'];
                        }
                        
                        foreach (array_reverse($por_mes, true) as $mes => $data): 
                            $fecha_mes = DateTime::createFromFormat('Y-m', $mes);
                        ?>
                            <div class="d-flex justify-content-between">
                                <span><?php echo $fecha_mes->format('F Y'); ?>:</span>
                                <span>
                                    <strong><?php echo $data['count']; ?> pagos</strong> - 
                                    <span class="text-success">S/ <?php echo number_format($data['monto'], 2); ?></span>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-graph-up"></i> Últimos 5 Pagos</h6>
                    </div>
                    <div class="card-body">
                        <?php 
                        $ultimos_5 = array_slice($pagos, 0, 5);
                        foreach ($ultimos_5 as $pago): 
                        ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span>
                                    <small><?php echo date('d/m/Y', strtotime($pago['fecha_generacion'])); ?></small>
                                    <br>
                                    <?php echo $pago['paquetes_pagados'] ?: $pago['total_paquetes']; ?> paquetes
                                </span>
                                <span class="text-end">
                                    <strong class="text-success">S/ <?php echo number_format($pago['total_pagar'], 2); ?></strong>
                                    <br>
                                    <span class="badge bg-<?php echo $badges[$pago['estado']]; ?> badge-sm">
                                        <?php echo ucfirst($pago['estado']); ?>
                                    </span>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error al cargar el historial: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>