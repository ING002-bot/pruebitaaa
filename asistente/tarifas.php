<?php
require_once '../config/config.php';
requireRole(['asistente']);

$db = Database::getInstance()->getConnection();

// Obtener todas las tarifas agrupadas por categoría
$stmt = $db->query("
    SELECT * FROM zonas_tarifas 
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

$pageTitle = "Tarifas por Zona";
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
                <h1><i class="bi bi-tags"></i> <?php echo $pageTitle; ?></h1>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

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
                                        <th>ID</th>
                                        <th>Nombre de Zona</th>
                                        <th>Tipo Envío</th>
                                        <th>Tarifa Repartidor</th>
                                        <th>Estado</th>
                                        <th>Última Actualización</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tarifas as $tarifa): ?>
                                        <tr>
                                            <td><?php echo $tarifa['id']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($tarifa['nombre_zona']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($tarifa['tipo_envio']); ?></td>
                                            <td>
                                                <span class="badge bg-success fs-6">
                                                    S/ <?php echo number_format($tarifa['tarifa_repartidor'], 2); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($tarifa['activo']): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y H:i', strtotime($tarifa['fecha_actualizacion'])); ?>
                                                </small>
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
                        <p class="text-muted mt-3">No hay tarifas configuradas</p>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
