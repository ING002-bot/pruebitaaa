<?php
require_once '../config/config.php';
requireRole(['asistente']);

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: rutas.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Obtener información de la ruta
$stmt = $db->prepare("SELECT r.*, u.nombre as repartidor_nombre, u.apellido as repartidor_apellido
                      FROM rutas r
                      LEFT JOIN usuarios u ON r.repartidor_id = u.id
                      WHERE r.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$ruta = $result->fetch_assoc();

if (!$ruta) {
    header('Location: rutas.php');
    exit;
}

// Obtener paquetes de la ruta
// Intentar primero con ruta_paquetes
$paquetes = [];
$query = "SELECT p.*, e.estado as estado_entrega, e.fecha_entrega, e.observaciones
          FROM ruta_paquetes rp
          INNER JOIN paquetes p ON rp.paquete_id = p.id
          LEFT JOIN entregas e ON p.id = e.paquete_id
          WHERE rp.ruta_id = ?
          ORDER BY COALESCE(rp.orden_entrega, 999), p.id";

$stmt = $db->prepare($query);

if ($stmt) {
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $paquetes = Database::getInstance()->fetchAll($stmt->get_result());
    }
    $stmt->close();
} else {
    // Si falla, puede que la tabla ruta_paquetes no exista o no tenga datos
    // Intentar método alternativo
    $query2 = "SELECT p.*, e.estado as estado_entrega, e.fecha_entrega, e.observaciones
               FROM paquetes p
               LEFT JOIN entregas e ON p.id = e.paquete_id
               WHERE p.repartidor_id = (SELECT repartidor_id FROM rutas WHERE id = ?)
               ORDER BY p.id";
    
    $stmt2 = $db->prepare($query2);
    if ($stmt2) {
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $paquetes = Database::getInstance()->fetchAll($stmt2->get_result());
        $stmt2->close();
    }
}

$pageTitle = 'Detalle de Ruta #' . $id;
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
                <div>
                    <h1><i class="bi bi-map"></i> <?php echo $pageTitle; ?></h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="rutas.php">Rutas</a></li>
                            <li class="breadcrumb-item active">#<?php echo $id; ?></li>
                        </ol>
                    </nav>
                </div>
                <a href="rutas.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Información de la Ruta</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Nombre:</th>
                                    <td><?php echo $ruta['nombre']; ?></td>
                                </tr>
                                <tr>
                                    <th>Zona:</th>
                                    <td><?php echo $ruta['zona'] ?: '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>Repartidor:</th>
                                    <td><?php echo $ruta['repartidor_nombre'] ? $ruta['repartidor_nombre'] . ' ' . $ruta['repartidor_apellido'] : 'Sin asignar'; ?></td>
                                </tr>
                                <tr>
                                    <th>Fecha:</th>
                                    <td><?php echo date('d/m/Y', strtotime($ruta['fecha_ruta'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Estado:</th>
                                    <td>
                                        <?php
                                        $badges = ['planificada' => 'secondary', 'en_progreso' => 'primary', 'completada' => 'success', 'cancelada' => 'danger'];
                                        ?>
                                        <span class="badge bg-<?php echo $badges[$ruta['estado']]; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $ruta['estado'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Estadísticas</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <h3 class="mb-0"><?php echo $ruta['total_paquetes']; ?></h3>
                                    <small class="text-muted">Total Paquetes</small>
                                </div>
                                <div class="col-6 mb-3">
                                    <h3 class="mb-0 text-success"><?php echo $ruta['paquetes_entregados']; ?></h3>
                                    <small class="text-muted">Entregados</small>
                                </div>
                                <div class="col-12">
                                    <?php
                                    $porcentaje = $ruta['total_paquetes'] > 0 ? round(($ruta['paquetes_entregados'] / $ruta['total_paquetes']) * 100) : 0;
                                    ?>
                                    <div class="progress" style="height: 30px;">
                                        <div class="progress-bar bg-success" style="width: <?php echo $porcentaje; ?>%">
                                            <?php echo $porcentaje; ?>%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Paquetes de la Ruta</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Destinatario</th>
                                    <th>Dirección</th>
                                    <th>Zona</th>
                                    <th>Estado</th>
                                    <th>Fecha Entrega</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paquetes as $paq): ?>
                                <tr>
                                    <td><strong><?php echo $paq['codigo_seguimiento']; ?></strong></td>
                                    <td><?php echo $paq['destinatario_nombre']; ?></td>
                                    <td><?php echo $paq['destinatario_direccion']; ?></td>
                                    <td><?php echo $paq['zona']; ?></td>
                                    <td>
                                        <?php if ($paq['estado_entrega'] === 'entregado'): ?>
                                            <span class="badge bg-success">Entregado</span>
                                        <?php elseif ($paq['estado'] === 'en_ruta'): ?>
                                            <span class="badge bg-primary">En Ruta</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?php echo ucfirst($paq['estado']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $paq['fecha_entrega'] ? date('d/m/Y H:i', strtotime($paq['fecha_entrega'])) : '-'; ?></td>
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
