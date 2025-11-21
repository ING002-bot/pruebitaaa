<?php
require_once '../config/config.php';
requireRole('repartidor');

$db = Database::getInstance()->getConnection();
$repartidor_id = $_SESSION['usuario_id'];

// Obtener tarifas por categoría
$stmt = $db->query("
    SELECT * FROM zonas_tarifas 
    WHERE activo = 1
    ORDER BY 
        FIELD(categoria, 'URBANO', 'PUEBLOS', 'PLAYAS', 'COOPERATIVAS', 'EXCOPERATIVAS', 'FERREÑAFE'),
        nombre_zona ASC
");
$todasTarifas = Database::getInstance()->fetchAll($stmt);

// Agrupar por categoría
$tarifasPorCategoria = [];
foreach ($todasTarifas as $tarifa) {
    $tarifasPorCategoria[$tarifa['categoria']][] = $tarifa;
}

// Estadísticas del repartidor por zona
$stmt = $db->prepare("
    SELECT 
        zt.categoria,
        zt.nombre_zona,
        zt.tarifa_repartidor,
        COUNT(e.id) as total_entregas,
        SUM(zt.tarifa_repartidor) as total_ganado
    FROM entregas e
    INNER JOIN paquetes p ON e.paquete_id = p.id
    LEFT JOIN zonas_tarifas zt ON p.zona_tarifa_id = zt.id
    WHERE e.repartidor_id = ? AND e.tipo_entrega = 'exitosa'
    GROUP BY zt.id
    ORDER BY total_entregas DESC
    LIMIT 10
");
$stmt->bind_param("i", $repartidor_id);
$stmt->execute();
$misEstadisticas = Database::getInstance()->fetchAll($stmt->get_result());

$pageTitle = "Tarifas por Zona";
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
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="page-content">
            <div class="page-title">
                <h1><i class="bi bi-cash-coin"></i> Tarifas por Zona</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Tarifas</li>
                    </ol>
                </nav>
            </div>

            <!-- Mis Estadísticas -->
            <?php if (!empty($misEstadisticas)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Mis Zonas Más Trabajadas</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Categoría</th>
                                        <th>Zona</th>
                                        <th>Tarifa</th>
                                        <th>Entregas Exitosas</th>
                                        <th>Total Ganado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($misEstadisticas as $stat): ?>
                                        <tr>
                                            <td><span class="badge bg-primary"><?php echo htmlspecialchars($stat['categoria']); ?></span></td>
                                            <td><strong><?php echo htmlspecialchars($stat['nombre_zona']); ?></strong></td>
                                            <td><span class="badge bg-success">S/ <?php echo number_format($stat['tarifa_repartidor'], 2); ?></span></td>
                                            <td><?php echo number_format($stat['total_entregas']); ?> entregas</td>
                                            <td><strong class="text-success">S/ <?php echo number_format($stat['total_ganado'], 2); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Información General -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5><i class="bi bi-info-circle"></i> Información Importante</h5>
                    <ul class="mb-0">
                        <li>Solo recibes pago por <strong>entregas exitosas</strong></li>
                        <li>Los paquetes <strong>rezagados o devueltos</strong> no generan ingreso</li>
                        <li>La tarifa depende de la zona de entrega</li>
                        <li>Consulta con administración si tienes dudas sobre las tarifas</li>
                    </ul>
                </div>
            </div>

            <!-- Tarifas por Categoría -->
            <?php foreach ($tarifasPorCategoria as $categoria => $tarifas): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($categoria); ?>
                            <span class="badge bg-light text-dark float-end"><?php echo count($tarifas); ?> zonas</span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="60%">Nombre de Zona</th>
                                        <th width="20%">Tipo Envío</th>
                                        <th width="20%">Pago por Entrega</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tarifas as $tarifa): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($tarifa['nombre_zona']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($tarifa['tipo_envio']); ?></td>
                                            <td>
                                                <span class="badge bg-success fs-6">
                                                    S/ <?php echo number_format($tarifa['tarifa_repartidor'], 2); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($tarifasPorCategoria)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                        <p class="text-muted mt-3">No hay tarifas disponibles</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/notificaciones.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>
