<?php
require_once '../config/config.php';
requireRole(['admin']);

$pageTitle = 'Gestión de Ingresos';

$db = Database::getInstance()->getConnection();

// Filtros
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');

$sql = "SELECT i.*, u.nombre, u.apellido, p.codigo_seguimiento, p.destinatario_nombre
        FROM ingresos i
        LEFT JOIN usuarios u ON i.registrado_por = u.id
        LEFT JOIN paquetes p ON i.paquete_id = p.id
        WHERE DATE(i.fecha_ingreso) BETWEEN ? AND ?
        ORDER BY i.fecha_ingreso DESC";
$stmt = $db->prepare($sql);
$stmt->execute([$fecha_desde, $fecha_hasta]);
$ingresos = $stmt->fetchAll();

// Total
$total = array_sum(array_column($ingresos, 'monto'));
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
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="content-header">
                <h1><i class="bi bi-cash-coin"></i> <?php echo $pageTitle; ?></h1>
            </div>

            <!-- Filtros -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Desde</label>
                            <input type="date" name="fecha_desde" class="form-control" value="<?php echo $fecha_desde; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Hasta</label>
                            <input type="date" name="fecha_hasta" class="form-control" value="<?php echo $fecha_hasta; ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Total -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo formatCurrency($total); ?></h3>
                            <p>Total Ingresos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-info">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo count($ingresos); ?></h3>
                            <p>Registros</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Paquete</th>
                                    <th>Destinatario</th>
                                    <th>Repartidor</th>
                                    <th>Concepto</th>
                                    <th>Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ingresos as $ing): ?>
                                <tr>
                                    <td><?php echo formatDate($ing['fecha_ingreso']); ?></td>
                                    <td><?php echo $ing['codigo_seguimiento'] ?: '-'; ?></td>
                                    <td><?php echo $ing['destinatario_nombre'] ?: '-'; ?></td>
                                    <td><?php echo ($ing['nombre'] && $ing['apellido']) ? $ing['nombre'] . ' ' . $ing['apellido'] : '-'; ?></td>
                                    <td><span class="badge bg-info"><?php echo ucfirst($ing['tipo']); ?></span> - <?php echo $ing['concepto']; ?></td>
                                    <td><strong class="text-success"><?php echo formatCurrency($ing['monto']); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <th colspan="5" class="text-end">TOTAL:</th>
                                    <th><strong class="text-success"><?php echo formatCurrency($total); ?></strong></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
