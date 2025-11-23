<?php
require_once '../config/config.php';
requireRole(['asistente']);

$pageTitle = 'Paquetes Rezagados';

$db = Database::getInstance()->getConnection();
$sql = "SELECT p.*, pr.*, u.nombre as repartidor_nombre, u.apellido as repartidor_apellido
        FROM paquetes_rezagados pr
        INNER JOIN paquetes p ON pr.paquete_id = p.id
        LEFT JOIN usuarios u ON p.repartidor_id = u.id
        WHERE pr.solucionado = 0 
        AND p.estado IN ('rezagado', 'pendiente', 'en_ruta')
        ORDER BY pr.fecha_rezago DESC";
$rezagados = Database::getInstance()->fetchAll($db->query($sql));
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
                <h1><i class="bi bi-exclamation-triangle"></i> <?php echo $pageTitle; ?></h1>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card border-warning">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem; margin-right: 1rem;"></i>
                                <div>
                                    <h2 class="mb-0"><?php echo count($rezagados); ?></h2>
                                    <p class="mb-0 text-muted">Paquetes Rezagados</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Listado de Paquetes Rezagados</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($rezagados)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                            <p class="text-muted mt-3">¡Excelente! No hay paquetes rezagados</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Destinatario</th>
                                        <th>Dirección</th>
                                        <th>Repartidor</th>
                                        <th>Motivo</th>
                                        <th>Fecha Rezago</th>
                                        <th>Intentos</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rezagados as $r): ?>
                                    <tr>
                                        <td><strong><?php echo $r['codigo_seguimiento']; ?></strong></td>
                                        <td><?php echo $r['destinatario_nombre']; ?></td>
                                        <td><?php echo substr($r['direccion_completa'], 0, 40) . (strlen($r['direccion_completa']) > 40 ? '...' : ''); ?></td>
                                        <td><?php echo $r['repartidor_nombre'] ? $r['repartidor_nombre'] . ' ' . $r['repartidor_apellido'] : '-'; ?></td>
                                        <td><?php echo $r['motivo_rezago']; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($r['fecha_rezago'])); ?></td>
                                        <td>
                                            <span class="badge bg-warning"><?php echo $r['intentos_entrega'] ?? 0; ?></span>
                                        </td>
                                        <td>
                                            <a href="rezagado_detalle.php?id=<?php echo $r['paquete_id']; ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i> Ver
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
