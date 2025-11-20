<?php
require_once '../config/config.php';
requireRole('repartidor');

$db = Database::getInstance()->getConnection();
$repartidor_id = $_SESSION['usuario_id'];

// Mes actual por defecto
$mes = isset($_GET['mes']) ? $_GET['mes'] : date('Y-m');

// Obtener entregas del mes con tarifas
$stmt = $db->prepare("
    SELECT 
        COUNT(e.id) as total_entregas,
        SUM(CASE WHEN e.tipo_entrega = 'exitosa' THEN 1 ELSE 0 END) as entregas_exitosas,
        SUM(CASE WHEN e.tipo_entrega != 'exitosa' THEN 1 ELSE 0 END) as entregas_fallidas,
        SUM(CASE WHEN e.tipo_entrega = 'exitosa' THEN COALESCE(zt.tarifa_repartidor, 3.50) ELSE 0 END) as ingresos_base
    FROM entregas e
    INNER JOIN paquetes p ON e.paquete_id = p.id
    LEFT JOIN zonas_tarifas zt ON p.zona_tarifa_id = zt.id
    WHERE e.repartidor_id = ? AND DATE_FORMAT(e.fecha_entrega, '%Y-%m') = ?
");
$stmt->execute([$repartidor_id, $mes]);
$stats = $stmt->fetch();

// Ingresos base ya calculados con tarifas reales
$ingresos_base = (float)($stats['ingresos_base'] ?? 0);

// Obtener bonificaciones y deducciones del mes
$stmt = $db->prepare("
    SELECT 
        COALESCE(SUM(bonificaciones), 0) as bonificaciones,
        COALESCE(SUM(deducciones), 0) as deducciones
    FROM pagos
    WHERE repartidor_id = ? AND DATE_FORMAT(periodo_inicio, '%Y-%m') = ?
");
$stmt->execute([$repartidor_id, $mes]);
$ajustes = $stmt->fetch();

$total_ingresos = $ingresos_base + $ajustes['bonificaciones'] - $ajustes['deducciones'];

// Historial de pagos
$stmt = $db->prepare("
    SELECT * FROM pagos
    WHERE repartidor_id = ?
    ORDER BY fecha_generacion DESC
    LIMIT 12
");
$stmt->execute([$repartidor_id]);
$historial_pagos = $stmt->fetchAll();

// Entregas por día del mes
$stmt = $db->prepare("
    SELECT DATE(fecha_entrega) as fecha, COUNT(*) as total
    FROM entregas
    WHERE repartidor_id = ? AND DATE_FORMAT(fecha_entrega, '%Y-%m') = ? AND tipo_entrega = 'exitosa'
    GROUP BY DATE(fecha_entrega)
    ORDER BY fecha
");
$stmt->execute([$repartidor_id, $mes]);
$entregas_por_dia = $stmt->fetchAll();

$pageTitle = "Mis Ingresos";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - HERMES EXPRESS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="page-content">
            <div class="page-title">
                <h1><i class="bi bi-cash-stack"></i> Mis Ingresos</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Ingresos</li>
                    </ol>
                </nav>
            </div>
            
            <!-- Selector de mes -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Seleccionar Mes</label>
                            <input type="month" class="form-control" name="mes" value="<?php echo $mes; ?>" onchange="this.form.submit()">
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Resumen de ingresos -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h6 class="text-muted">Entregas Exitosas</h6>
                            <h2 class="text-success"><?php echo $stats['entregas_exitosas']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h6 class="text-muted">Ingresos Base</h6>
                            <h2 class="text-primary"><?php echo formatCurrency($ingresos_base); ?></h2>
                            <small class="text-muted">Según tarifas por zona</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h6 class="text-muted">Bonificaciones</h6>
                            <h2 class="text-success">+<?php echo formatCurrency($ajustes['bonificaciones']); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h6 class="text-muted">Total del Mes</h6>
                            <h2 class="text-success"><?php echo formatCurrency($total_ingresos); ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Gráfico de entregas -->
            <div class="row mb-4">
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Entregas por Día</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="entregasChart" height="80"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Eficiencia</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $total = $stats['total_entregas'];
                            $exitosas = $stats['entregas_exitosas'];
                            $eficiencia = $total > 0 ? ($exitosas / $total) * 100 : 0;
                            ?>
                            <div class="text-center">
                                <h1 class="display-3"><?php echo round($eficiencia); ?>%</h1>
                                <p class="text-muted">Tasa de éxito</p>
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-success" style="width: <?php echo $eficiencia; ?>%">
                                        <?php echo $exitosas; ?> exitosas
                                    </div>
                                </div>
                                <p class="mt-3 mb-0">
                                    <small class="text-muted">
                                        <?php echo $stats['total_entregas']; ?> entregas totales
                                    </small>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Historial de pagos -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Historial de Pagos</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Periodo</th>
                                    <th>Paquetes</th>
                                    <th>Base</th>
                                    <th>Bonificaciones</th>
                                    <th>Deducciones</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Fecha Pago</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($historial_pagos as $pago): ?>
                                <tr>
                                    <td><?php echo formatDate($pago['periodo_inicio']) . ' - ' . formatDate($pago['periodo_fin']); ?></td>
                                    <td><?php echo $pago['total_paquetes']; ?></td>
                                    <td><?php echo formatCurrency($pago['total_paquetes'] * $pago['monto_por_paquete']); ?></td>
                                    <td class="text-success">+<?php echo formatCurrency($pago['bonificaciones']); ?></td>
                                    <td class="text-danger">-<?php echo formatCurrency($pago['deducciones']); ?></td>
                                    <td><strong><?php echo formatCurrency($pago['total_pagar']); ?></strong></td>
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
                                    </td>
                                    <td><?php echo $pago['fecha_pago'] ? formatDate($pago['fecha_pago']) : '-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        // Gráfico de entregas por día
        const entregasData = <?php echo json_encode($entregas_por_dia); ?>;
        const labels = entregasData.map(item => {
            const date = new Date(item.fecha);
            return date.getDate() + '/' + (date.getMonth() + 1);
        });
        const data = entregasData.map(item => parseInt(item.total));
        
        new Chart(document.getElementById('entregasChart'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Entregas',
                    data: data,
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: '#28a745',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
