<?php
require_once '../config/config.php';
requireRole(['asistente']);

$pageTitle = 'Rutas';

// Obtener rutas
$db = Database::getInstance()->getConnection();
$sql = "SELECT r.*, u.nombre as repartidor_nombre, u.apellido as repartidor_apellido,
        uc.nombre as creado_nombre, uc.apellido as creado_apellido
        FROM rutas r
        LEFT JOIN usuarios u ON r.repartidor_id = u.id
        LEFT JOIN usuarios uc ON r.creado_por = uc.id
        ORDER BY r.fecha_ruta DESC, r.id DESC
        LIMIT 100";
$rutas = Database::getInstance()->fetchAll($db->query($sql));
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
                <h1><i class="bi bi-map"></i> <?php echo $pageTitle; ?></h1>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre / Zona</th>
                                    <th>Ubicaciones</th>
                                    <th>Repartidor</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Progreso</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rutas as $ruta): ?>
                                <tr>
                                    <td><?php echo $ruta['id']; ?></td>
                                    <td>
                                        <strong><?php echo $ruta['nombre']; ?></strong>
                                        <?php if (!empty($ruta['zona'])): ?>
                                            <br><span class="badge bg-primary"><?php echo $ruta['zona']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($ruta['ubicaciones'])): ?>
                                            <small class="text-muted"><?php echo substr($ruta['ubicaciones'], 0, 50) . (strlen($ruta['ubicaciones']) > 50 ? '...' : ''); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $ruta['repartidor_nombre'] ? $ruta['repartidor_nombre'] . ' ' . $ruta['repartidor_apellido'] : '-'; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($ruta['fecha_ruta'])); ?></td>
                                    <td>
                                        <?php
                                        $badges = ['planificada' => 'secondary', 'en_progreso' => 'primary', 'completada' => 'success', 'cancelada' => 'danger'];
                                        ?>
                                        <span class="badge bg-<?php echo $badges[$ruta['estado']]; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $ruta['estado'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($ruta['total_paquetes'] > 0): ?>
                                            <?php $porcentaje = round(($ruta['paquetes_entregados'] / $ruta['total_paquetes']) * 100); ?>
                                            <div class="progress" style="height: 20px; min-width: 100px;">
                                                <div class="progress-bar bg-success" style="width: <?php echo $porcentaje; ?>%">
                                                    <?php echo $ruta['paquetes_entregados']; ?>/<?php echo $ruta['total_paquetes']; ?>
                                                </div>
                                            </div>
                                            <small class="text-muted"><?php echo $porcentaje; ?>%</small>
                                        <?php else: ?>
                                            <span class="text-muted">Sin paquetes</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="ruta_detalle.php?id=<?php echo $ruta['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i> Ver
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
