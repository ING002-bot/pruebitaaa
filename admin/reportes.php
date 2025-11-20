<?php
require_once '../config/config.php';
requireRole(['admin']);

$pageTitle = 'Reportes y Estadísticas';

$db = Database::getInstance()->getConnection();

// Parámetros de fecha
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');

// Estadísticas generales
$stats = [
    'total_paquetes' => $db->query("SELECT COUNT(*) FROM paquetes WHERE DATE(fecha_recepcion) BETWEEN '$fecha_desde' AND '$fecha_hasta'")->fetchColumn(),
    'paquetes_entregados' => $db->query("SELECT COUNT(*) FROM paquetes WHERE estado='entregado' AND DATE(fecha_entrega) BETWEEN '$fecha_desde' AND '$fecha_hasta'")->fetchColumn(),
    'paquetes_rezagados' => $db->query("SELECT COUNT(*) FROM paquetes_rezagados WHERE DATE(fecha_rezago) BETWEEN '$fecha_desde' AND '$fecha_hasta'")->fetchColumn(),
    'total_ingresos' => $db->query("SELECT COALESCE(SUM(monto), 0) FROM ingresos WHERE DATE(fecha_ingreso) BETWEEN '$fecha_desde' AND '$fecha_hasta'")->fetchColumn(),
    'total_gastos' => $db->query("SELECT COALESCE(SUM(monto), 0) FROM gastos WHERE DATE(fecha_gasto) BETWEEN '$fecha_desde' AND '$fecha_hasta'")->fetchColumn(),
];

$stats['utilidad'] = $stats['total_ingresos'] - $stats['total_gastos'];
$stats['tasa_entrega'] = $stats['total_paquetes'] > 0 ? round(($stats['paquetes_entregados'] / $stats['total_paquetes']) * 100, 2) : 0;

// Datos para gráficos
// Entregas por día
$entregas_diarias = $db->query("
    SELECT DATE(fecha_entrega) as fecha, COUNT(*) as total
    FROM entregas
    WHERE DATE(fecha_entrega) BETWEEN '$fecha_desde' AND '$fecha_hasta'
    GROUP BY DATE(fecha_entrega)
    ORDER BY fecha
")->fetchAll();

// Ingresos por día
$ingresos_diarios = $db->query("
    SELECT DATE(fecha_ingreso) as fecha, SUM(monto) as total
    FROM ingresos
    WHERE DATE(fecha_ingreso) BETWEEN '$fecha_desde' AND '$fecha_hasta'
    GROUP BY DATE(fecha_ingreso)
    ORDER BY fecha
")->fetchAll();

// Top repartidores
$top_repartidores = $db->query("
    SELECT u.nombre, u.apellido, 
           COUNT(e.id) as total_entregas,
           SUM(CASE WHEN e.tipo_entrega='exitosa' THEN 1 ELSE 0 END) as exitosas,
           SUM(i.monto) as total_ingresos
    FROM usuarios u
    LEFT JOIN entregas e ON u.id = e.repartidor_id AND DATE(e.fecha_entrega) BETWEEN '$fecha_desde' AND '$fecha_hasta'
    LEFT JOIN ingresos i ON i.paquete_id IN (SELECT paquete_id FROM entregas WHERE repartidor_id = u.id)
    WHERE u.rol = 'repartidor'
    GROUP BY u.id
    ORDER BY total_entregas DESC
    LIMIT 10
")->fetchAll();

// Estados de paquetes
$estados_paquetes = $db->query("
    SELECT estado, COUNT(*) as total
    FROM paquetes
    WHERE DATE(fecha_recepcion) BETWEEN '$fecha_desde' AND '$fecha_hasta'
    GROUP BY estado
")->fetchAll();

// Motivos de rechazo
$motivos_rechazo = $db->query("
    SELECT motivo, COUNT(*) as total
    FROM paquetes_rezagados
    WHERE DATE(fecha_rezago) BETWEEN '$fecha_desde' AND '$fecha_hasta'
    GROUP BY motivo
    ORDER BY total DESC
")->fetchAll();
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="content-header d-flex justify-content-between align-items-center">
                <h1><i class="bi bi-graph-up"></i> <?php echo $pageTitle; ?></h1>
                <div>
                    <button class="btn btn-success" onclick="exportarPDF()">
                        <i class="bi bi-file-pdf"></i> Exportar PDF
                    </button>
                    <button class="btn btn-primary" onclick="exportarExcel()">
                        <i class="bi bi-file-excel"></i> Exportar Excel
                    </button>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Desde</label>
                            <input type="date" name="fecha_desde" class="form-control" value="<?php echo $fecha_desde; ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Hasta</label>
                            <input type="date" name="fecha_hasta" class="form-control" value="<?php echo $fecha_hasta; ?>" required>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-search"></i> Generar Reporte
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="location.href='reportes.php'">
                                <i class="bi bi-arrow-clockwise"></i> Mes Actual
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Estadísticas Generales -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary">
                            <i class="bi bi-box"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo number_format($stats['total_paquetes']); ?></h3>
                            <p>Total Paquetes</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo number_format($stats['paquetes_entregados']); ?></h3>
                            <p>Entregados (<?php echo $stats['tasa_entrega']; ?>%)</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo number_format($stats['paquetes_rezagados']); ?></h3>
                            <p>Rezagados</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-info">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo formatCurrency($stats['utilidad']); ?></h3>
                            <p>Utilidad Neta</p>
                            <small class="text-muted">
                                Ingresos: <?php echo formatCurrency($stats['total_ingresos']); ?><br>
                                Gastos: <?php echo formatCurrency($stats['total_gastos']); ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-graph-up"></i> Entregas Diarias</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="chartEntregasDiarias" height="250"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-cash-stack"></i> Ingresos Diarios</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="chartIngresosDiarios" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-pie-chart"></i> Estados de Paquetes</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="chartEstados" height="250"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-exclamation-circle"></i> Motivos de Rechazo</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="chartMotivos" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Repartidores -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="bi bi-trophy"></i> Top 10 Repartidores</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Repartidor</th>
                                    <th>Total Entregas</th>
                                    <th>Exitosas</th>
                                    <th>Tasa Éxito</th>
                                    <th>Ingresos Generados</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $posicion = 1; ?>
                                <?php foreach ($top_repartidores as $rep): ?>
                                <?php 
                                    $tasa = $rep['total_entregas'] > 0 ? round(($rep['exitosas'] / $rep['total_entregas']) * 100, 2) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <?php if($posicion == 1): ?>
                                            <i class="bi bi-trophy-fill text-warning"></i>
                                        <?php elseif($posicion == 2): ?>
                                            <i class="bi bi-trophy-fill text-secondary"></i>
                                        <?php elseif($posicion == 3): ?>
                                            <i class="bi bi-trophy-fill text-danger"></i>
                                        <?php else: ?>
                                            <?php echo $posicion; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo $rep['nombre'] . ' ' . $rep['apellido']; ?></strong></td>
                                    <td><?php echo number_format($rep['total_entregas']); ?></td>
                                    <td><?php echo number_format($rep['exitosas']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $tasa >= 90 ? 'success' : ($tasa >= 70 ? 'warning' : 'danger'); ?>">
                                            <?php echo $tasa; ?>%
                                        </span>
                                    </td>
                                    <td class="text-success"><strong><?php echo formatCurrency($rep['total_ingresos']); ?></strong></td>
                                </tr>
                                <?php $posicion++; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gráfico de Entregas Diarias
        const ctxEntregas = document.getElementById('chartEntregasDiarias').getContext('2d');
        new Chart(ctxEntregas, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($entregas_diarias, 'fecha')); ?>,
                datasets: [{
                    label: 'Entregas',
                    data: <?php echo json_encode(array_column($entregas_diarias, 'total')); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Gráfico de Ingresos Diarios
        const ctxIngresos = document.getElementById('chartIngresosDiarios').getContext('2d');
        new Chart(ctxIngresos, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($ingresos_diarios, 'fecha')); ?>,
                datasets: [{
                    label: 'Ingresos',
                    data: <?php echo json_encode(array_column($ingresos_diarios, 'total')); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgb(54, 162, 235)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Gráfico de Estados
        const ctxEstados = document.getElementById('chartEstados').getContext('2d');
        new Chart(ctxEstados, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($estados_paquetes, 'estado')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($estados_paquetes, 'total')); ?>,
                    backgroundColor: [
                        'rgba(255, 206, 86, 0.5)',
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(75, 192, 192, 0.5)',
                        'rgba(255, 99, 132, 0.5)',
                        'rgba(153, 102, 255, 0.5)',
                        'rgba(255, 159, 64, 0.5)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Gráfico de Motivos
        const ctxMotivos = document.getElementById('chartMotivos').getContext('2d');
        new Chart(ctxMotivos, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function($m) { 
                    return ucfirst(str_replace('_', ' ', $m)); 
                }, array_column($motivos_rechazo, 'motivo'))); ?>,
                datasets: [{
                    label: 'Cantidad',
                    data: <?php echo json_encode(array_column($motivos_rechazo, 'total')); ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgb(255, 99, 132)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false }
                }
            }
        });

        function exportarPDF() {
            alert('Función de exportación a PDF en desarrollo');
        }

        function exportarExcel() {
            const params = new URLSearchParams(window.location.search);
            window.location.href = 'reportes_export.php?tipo=excel&' + params.toString();
        }
    </script>
</body>
</html>
