<?php
require_once '../config/config.php';
requireRole('admin');

$repartidor_id = (int)($_GET['repartidor_id'] ?? 0);

if ($repartidor_id <= 0) {
    echo '<div class="alert alert-danger">ID de repartidor inválido</div>';
    exit;
}

$db = Database::getInstance()->getConnection();

// Obtener información del repartidor
$stmt = $db->prepare("SELECT nombre, apellido, telefono FROM usuarios WHERE id = ? AND rol = 'repartidor'");
$stmt->bind_param("i", $repartidor_id);
$stmt->execute();
$repartidor = $stmt->get_result()->fetch_assoc();

if (!$repartidor) {
    echo '<div class="alert alert-danger">Repartidor no encontrado</div>';
    exit;
}

// Obtener paquetes entregados del mes actual
$stmt = $db->prepare("
    SELECT p.*, 
           COALESCE(
               (SELECT zt1.tarifa_repartidor FROM zonas_tarifas zt1 WHERE zt1.nombre_zona = p.distrito AND zt1.activo = 1 LIMIT 1),
               (SELECT zt2.tarifa_repartidor FROM zonas_tarifas zt2 WHERE zt2.nombre_zona = p.ciudad AND zt2.activo = 1 LIMIT 1),
               (SELECT zt3.tarifa_repartidor FROM zonas_tarifas zt3 WHERE zt3.nombre_zona = p.provincia AND zt3.activo = 1 LIMIT 1)
           ) as tarifa_repartidor,
           COALESCE(
               (SELECT zt1.nombre_zona FROM zonas_tarifas zt1 WHERE zt1.nombre_zona = p.distrito AND zt1.activo = 1 LIMIT 1),
               (SELECT zt2.nombre_zona FROM zonas_tarifas zt2 WHERE zt2.nombre_zona = p.ciudad AND zt2.activo = 1 LIMIT 1),
               (SELECT zt3.nombre_zona FROM zonas_tarifas zt3 WHERE zt3.nombre_zona = p.provincia AND zt3.activo = 1 LIMIT 1)
           ) as zona_tarifa,
           e.fecha_entrega,
           e.tipo_entrega,
           e.receptor_nombre
    FROM paquetes p
    LEFT JOIN entregas e ON p.id = e.paquete_id
    WHERE p.repartidor_id = ? 
    AND p.estado = 'entregado'
    AND e.tipo_entrega = 'exitosa'
    AND MONTH(p.fecha_entrega) = MONTH(CURRENT_DATE()) 
    AND YEAR(p.fecha_entrega) = YEAR(CURRENT_DATE())
    ORDER BY p.fecha_entrega DESC
");
$stmt->bind_param("i", $repartidor_id);
$stmt->execute();
$paquetes_mes = Database::getInstance()->fetchAll($stmt->get_result());

// Obtener todos los paquetes entregados (historial)
$stmt = $db->prepare("
    SELECT p.*, 
           COALESCE(
               (SELECT zt1.tarifa_repartidor FROM zonas_tarifas zt1 WHERE zt1.nombre_zona = p.distrito AND zt1.activo = 1 LIMIT 1),
               (SELECT zt2.tarifa_repartidor FROM zonas_tarifas zt2 WHERE zt2.nombre_zona = p.ciudad AND zt2.activo = 1 LIMIT 1),
               (SELECT zt3.tarifa_repartidor FROM zonas_tarifas zt3 WHERE zt3.nombre_zona = p.provincia AND zt3.activo = 1 LIMIT 1)
           ) as tarifa_repartidor,
           COALESCE(
               (SELECT zt1.nombre_zona FROM zonas_tarifas zt1 WHERE zt1.nombre_zona = p.distrito AND zt1.activo = 1 LIMIT 1),
               (SELECT zt2.nombre_zona FROM zonas_tarifas zt2 WHERE zt2.nombre_zona = p.ciudad AND zt2.activo = 1 LIMIT 1),
               (SELECT zt3.nombre_zona FROM zonas_tarifas zt3 WHERE zt3.nombre_zona = p.provincia AND zt3.activo = 1 LIMIT 1)
           ) as zona_tarifa,
           e.fecha_entrega,
           e.tipo_entrega,
           e.receptor_nombre,
           DATE_FORMAT(p.fecha_entrega, '%M %Y') as mes_entrega
    FROM paquetes p
    LEFT JOIN entregas e ON p.id = e.paquete_id
    WHERE p.repartidor_id = ? 
    AND p.estado = 'entregado'
    AND e.tipo_entrega = 'exitosa'
    ORDER BY p.fecha_entrega DESC
    LIMIT 100
");
$stmt->bind_param("i", $repartidor_id);
$stmt->execute();
$paquetes_historial = Database::getInstance()->fetchAll($stmt->get_result());

// Calcular estadísticas
$total_mes = count($paquetes_mes);
$monto_total_mes = array_sum(array_column($paquetes_mes, 'tarifa_repartidor'));
$total_historico = count($paquetes_historial);

// Agrupar por mes para estadísticas
$estadisticas_mensuales = [];
foreach ($paquetes_historial as $paquete) {
    $mes = $paquete['mes_entrega'];
    if (!isset($estadisticas_mensuales[$mes])) {
        $estadisticas_mensuales[$mes] = ['cantidad' => 0, 'monto' => 0];
    }
    $estadisticas_mensuales[$mes]['cantidad']++;
    $estadisticas_mensuales[$mes]['monto'] += $paquete['tarifa_repartidor'];
}
?>

<div class="container-fluid">
    <!-- Información del Repartidor -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-person-circle"></i> 
                        <?php echo $repartidor['nombre'] . ' ' . $repartidor['apellido']; ?>
                    </h5>
                    <p class="card-text">
                        <i class="bi bi-telephone"></i> <?php echo $repartidor['telefono']; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas Rápidas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-primary"><?php echo $total_mes; ?></h3>
                    <small>Paquetes Este Mes</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-success">S/ <?php echo number_format($monto_total_mes, 2); ?></h3>
                    <small>Monto Este Mes</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-info"><?php echo $total_historico; ?></h3>
                    <small>Total Histórico</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-warning">
                        <?php echo $total_mes > 0 ? 'S/ ' . number_format($monto_total_mes / $total_mes, 2) : 'S/ 0.00'; ?>
                    </h3>
                    <small>Promedio por Paquete</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs para diferentes vistas -->
    <ul class="nav nav-tabs" id="paquetesTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="mes-actual-tab" data-bs-toggle="tab" data-bs-target="#mes-actual" 
                    type="button" role="tab">
                Paquetes del Mes Actual (<?php echo $total_mes; ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="historial-tab" data-bs-toggle="tab" data-bs-target="#historial" 
                    type="button" role="tab">
                Historial Completo (<?php echo $total_historico; ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="estadisticas-tab" data-bs-toggle="tab" data-bs-target="#estadisticas" 
                    type="button" role="tab">
                Estadísticas Mensuales
            </button>
        </li>
    </ul>

    <div class="tab-content" id="paquetesTabContent">
        <!-- Paquetes del Mes Actual -->
        <div class="tab-pane fade show active" id="mes-actual" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <?php if (count($paquetes_mes) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Destinatario</th>
                                        <th>Dirección</th>
                                        <th>Zona</th>
                                        <th>Fecha Entrega</th>
                                        <th>Receptor</th>
                                        <th>Tarifa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($paquetes_mes as $paq): ?>
                                    <tr>
                                        <td><strong><?php echo $paq['codigo_seguimiento']; ?></strong></td>
                                        <td><?php echo $paq['destinatario_nombre']; ?></td>
                                        <td class="small">
                                            <?php echo substr($paq['direccion_completa'], 0, 30); ?>...
                                            <br><span class="text-muted"><?php echo $paq['ciudad']; ?>
                                            <?php if ($paq['distrito']): ?> - <?php echo $paq['distrito']; ?><?php endif; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($paq['zona_tarifa']): ?>
                                                <span class="badge bg-info"><?php echo $paq['zona_tarifa']; ?></span>
                                            <?php elseif ($paq['distrito']): ?>
                                                <span class="badge bg-primary"><?php echo $paq['distrito']; ?></span>
                                            <?php elseif ($paq['ciudad']): ?>
                                                <span class="badge bg-warning"><?php echo $paq['ciudad']; ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Sin zona</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m H:i', strtotime($paq['fecha_entrega'])); ?></td>
                                        <td class="small"><?php echo $paq['receptor_nombre'] ?: '-'; ?></td>
                                        <td><strong class="text-success">S/ <?php echo number_format($paq['tarifa_repartidor'], 2); ?></strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-success">
                                        <td colspan="6"><strong>TOTAL</strong></td>
                                        <td><strong>S/ <?php echo number_format($monto_total_mes, 2); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                            <h5 class="mt-3 text-muted">Sin paquetes entregados este mes</h5>
                            <p class="text-muted">Este repartidor no ha entregado paquetes en el mes actual.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Historial Completo -->
        <div class="tab-pane fade" id="historial" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <?php if (count($paquetes_historial) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Código</th>
                                        <th>Destinatario</th>
                                        <th>Ciudad</th>
                                        <th>Zona</th>
                                        <th>Tarifa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($paquetes_historial as $paq): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($paq['fecha_entrega'])); ?></td>
                                        <td><?php echo $paq['codigo_seguimiento']; ?></td>
                                        <td><?php echo $paq['destinatario_nombre']; ?></td>
                                        <td>
                                            <?php echo $paq['ciudad']; ?>
                                            <?php if ($paq['distrito']): ?>
                                                <br><small class="text-muted"><?php echo $paq['distrito']; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($paq['zona_tarifa']): ?>
                                                <span class="badge bg-info small"><?php echo $paq['zona_tarifa']; ?></span>
                                            <?php elseif ($paq['distrito']): ?>
                                                <span class="badge bg-primary small"><?php echo $paq['distrito']; ?></span>
                                            <?php elseif ($paq['ciudad']): ?>
                                                <span class="badge bg-warning small"><?php echo $paq['ciudad']; ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary small">Sin zona</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>S/ <?php echo number_format($paq['tarifa_repartidor'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-archive" style="font-size: 3rem; color: #ccc;"></i>
                            <h5 class="mt-3 text-muted">Sin historial de entregas</h5>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Estadísticas Mensuales -->
        <div class="tab-pane fade" id="estadisticas" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <?php if (count($estadisticas_mensuales) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Mes</th>
                                        <th>Paquetes Entregados</th>
                                        <th>Monto Total</th>
                                        <th>Promedio por Paquete</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($estadisticas_mensuales as $mes => $datos): ?>
                                    <tr>
                                        <td><strong><?php echo $mes; ?></strong></td>
                                        <td><span class="badge bg-primary"><?php echo $datos['cantidad']; ?></span></td>
                                        <td><strong class="text-success">S/ <?php echo number_format($datos['monto'], 2); ?></strong></td>
                                        <td>S/ <?php echo number_format($datos['monto'] / $datos['cantidad'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-graph-up" style="font-size: 3rem; color: #ccc;"></i>
                            <h5 class="mt-3 text-muted">Sin estadísticas disponibles</h5>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Activar tabs de Bootstrap
var triggerTabList = [].slice.call(document.querySelectorAll('#paquetesTabs button'))
triggerTabList.forEach(function (triggerEl) {
    var tabTrigger = new bootstrap.Tab(triggerEl)
    triggerEl.addEventListener('click', function (event) {
        event.preventDefault()
        tabTrigger.show()
    })
})
</script>