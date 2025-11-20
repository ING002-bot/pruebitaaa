<?php
require_once '../config/config.php';
requireRole(['admin', 'asistente']);

// Obtener estadísticas (similar a admin pero sin ingresos totales)
$db = Database::getInstance()->getConnection();

// Total de paquetes
$stmt = $db->query("SELECT COUNT(*) as total FROM paquetes");
$totalPaquetes = $stmt->fetch()['total'];

// Paquetes entregados hoy
$stmt = $db->query("SELECT COUNT(*) as total FROM paquetes WHERE DATE(fecha_entrega) = CURDATE() AND estado = 'entregado'");
$paquetesHoy = $stmt->fetch()['total'];

// Paquetes en ruta
$stmt = $db->query("SELECT COUNT(*) as total FROM paquetes WHERE estado = 'en_ruta'");
$paquetesEnRuta = $stmt->fetch()['total'];

// Paquetes rezagados
$stmt = $db->query("SELECT COUNT(*) as total FROM paquetes WHERE estado = 'rezagado'");
$paquetesRezagados = $stmt->fetch()['total'];

// Repartidores activos
$stmt = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'repartidor' AND estado = 'activo'");
$repartidoresActivos = $stmt->fetch()['total'];

// Últimos paquetes
$stmt = $db->query("SELECT p.*, u.nombre, u.apellido FROM paquetes p LEFT JOIN usuarios u ON p.repartidor_id = u.id ORDER BY p.fecha_recepcion DESC LIMIT 10");
$ultimosPaquetes = $stmt->fetchAll();

// Paquetes por estado
$stmt = $db->query("SELECT estado, COUNT(*) as total FROM paquetes GROUP BY estado");
$paquetesPorEstado = $stmt->fetchAll();

$pageTitle = "Dashboard Asistente";
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
        
        <!-- Content -->
        <div class="page-content">
            <div class="page-title">
                <h1>Dashboard - Asistente</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </nav>
            </div>
            
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3><?php echo number_format($totalPaquetes); ?></h3>
                            <p>Total Paquetes</p>
                        </div>
                        <div class="stat-icon primary">
                            <i class="bi bi-box"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3><?php echo number_format($paquetesHoy); ?></h3>
                            <p>Entregados Hoy</p>
                        </div>
                        <div class="stat-icon success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3><?php echo number_format($paquetesEnRuta); ?></h3>
                            <p>En Ruta</p>
                        </div>
                        <div class="stat-icon warning">
                            <i class="bi bi-truck"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3><?php echo number_format($paquetesRezagados); ?></h3>
                            <p>Rezagados</p>
                        </div>
                        <div class="stat-icon danger">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts and Tables -->
            <div class="row">
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between">
                            <h5 class="card-title">Últimos Paquetes</h5>
                            <a href="../admin/paquetes.php" class="btn btn-sm btn-primary">Ver Todos</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Destinatario</th>
                                            <th>Repartidor</th>
                                            <th>Estado</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($ultimosPaquetes as $paquete): ?>
                                        <tr>
                                            <td><strong><?php echo $paquete['codigo_seguimiento']; ?></strong></td>
                                            <td><?php echo $paquete['destinatario_nombre']; ?></td>
                                            <td><?php echo $paquete['nombre'] ? $paquete['nombre'] . ' ' . $paquete['apellido'] : 'Sin asignar'; ?></td>
                                            <td>
                                                <?php
                                                $badgeClass = [
                                                    'pendiente' => 'bg-secondary',
                                                    'en_ruta' => 'bg-warning',
                                                    'entregado' => 'bg-success',
                                                    'rezagado' => 'bg-danger'
                                                ];
                                                ?>
                                                <span class="badge <?php echo $badgeClass[$paquete['estado']] ?? 'bg-secondary'; ?>">
                                                    <?php echo ucfirst($paquete['estado']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDateTime($paquete['fecha_recepcion']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Paquetes por Estado</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="estadosChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/notificaciones.js"></script>
    <script>
        // Gráfico de estados
        const estadosData = <?php echo json_encode($paquetesPorEstado); ?>;
        const labels = estadosData.map(item => item.estado.charAt(0).toUpperCase() + item.estado.slice(1));
        const data = estadosData.map(item => parseInt(item.total));
        
        new Chart(document.getElementById('estadosChart'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: ['#6c757d', '#ffc107', '#28a745', '#dc3545', '#17a2b8', '#343a40']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
