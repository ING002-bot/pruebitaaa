<?php
require_once '../config/config.php';
requireRole(['admin', 'asistente']);

$pageTitle = 'Detalle de Ruta';

// Obtener ID de la ruta
$ruta_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$db = Database::getInstance()->getConnection();

// Obtener información de la ruta
$sql = "SELECT r.*, u.nombre as repartidor_nombre, u.apellido as repartidor_apellido, u.telefono as repartidor_telefono,
        uc.nombre as creado_nombre, uc.apellido as creado_apellido
        FROM rutas r
        LEFT JOIN usuarios u ON r.repartidor_id = u.id
        LEFT JOIN usuarios uc ON r.creado_por = uc.id
        WHERE r.id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$ruta_id]);
$ruta = $stmt->fetch();

if (!$ruta) {
    header('Location: rutas.php?error=no_encontrada');
    exit;
}

// Obtener paquetes asignados a la ruta
$sql = "SELECT p.*, c.nombre as cliente_nombre, c.telefono as cliente_telefono
        FROM paquetes p
        LEFT JOIN clientes c ON p.cliente_id = c.id
        WHERE p.ruta_id = ?
        ORDER BY p.estado, p.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute([$ruta_id]);
$paquetes = $stmt->fetchAll();

// Estadísticas de paquetes
$stats = [
    'total' => count($paquetes),
    'pendientes' => 0,
    'en_ruta' => 0,
    'entregados' => 0,
    'devueltos' => 0
];

foreach ($paquetes as $paquete) {
    if (isset($stats[$paquete['estado']])) {
        $stats[$paquete['estado']]++;
    }
}
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
                    <a href="rutas.php" class="btn btn-secondary btn-sm mb-2">
                        <i class="bi bi-arrow-left"></i> Volver a Rutas
                    </a>
                    <h1><i class="bi bi-map-fill"></i> <?php echo $ruta['nombre']; ?></h1>
                </div>
                <?php if ($ruta['estado'] == 'planificada'): ?>
                <a href="ruta_editar.php?id=<?php echo $ruta_id; ?>" class="btn btn-warning">
                    <i class="bi bi-pencil"></i> Editar Ruta
                </a>
                <?php endif; ?>
            </div>

            <div class="row">
                <!-- Información de la Ruta -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-info-circle"></i> Información de la Ruta</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <th>ID:</th>
                                    <td><?php echo $ruta['id']; ?></td>
                                </tr>
                                <tr>
                                    <th>Nombre:</th>
                                    <td><?php echo $ruta['nombre']; ?></td>
                                </tr>
                                <tr>
                                    <th>Zona:</th>
                                    <td><span class="badge bg-primary"><?php echo $ruta['zona'] ?? 'N/A'; ?></span></td>
                                </tr>
                                <tr>
                                    <th>Ubicaciones:</th>
                                    <td>
                                        <?php if (!empty($ruta['ubicaciones'])): ?>
                                            <?php
                                            $ubicaciones = explode(',', $ruta['ubicaciones']);
                                            foreach ($ubicaciones as $ubicacion) {
                                                echo '<span class="badge bg-secondary me-1 mb-1">' . trim($ubicacion) . '</span>';
                                            }
                                            ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Repartidor:</th>
                                    <td>
                                        <?php if ($ruta['repartidor_nombre']): ?>
                                            <?php echo $ruta['repartidor_nombre'] . ' ' . $ruta['repartidor_apellido']; ?><br>
                                            <small class="text-muted"><i class="bi bi-telephone"></i> <?php echo $ruta['repartidor_telefono']; ?></small>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Sin asignar</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Fecha:</th>
                                    <td><?php echo date('d/m/Y', strtotime($ruta['fecha_ruta'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Estado:</th>
                                    <td>
                                        <?php
                                        $badges = [
                                            'planificada' => 'secondary',
                                            'en_progreso' => 'primary',
                                            'completada' => 'success',
                                            'cancelada' => 'danger'
                                        ];
                                        ?>
                                        <span class="badge bg-<?php echo $badges[$ruta['estado']]; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $ruta['estado'])); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Creado por:</th>
                                    <td><?php echo $ruta['creado_nombre'] . ' ' . $ruta['creado_apellido']; ?><br>
                                        <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($ruta['fecha_creacion'])); ?></small>
                                    </td>
                                </tr>
                                <?php if ($ruta['descripcion']): ?>
                                <tr>
                                    <th>Descripción:</th>
                                    <td><?php echo nl2br($ruta['descripcion']); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="col-md-8 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Estadísticas de Paquetes</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <div class="p-3 border rounded">
                                        <i class="bi bi-box-seam fs-1 text-secondary"></i>
                                        <h3 class="mt-2"><?php echo $stats['total']; ?></h3>
                                        <p class="text-muted mb-0">Total Paquetes</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="p-3 border rounded">
                                        <i class="bi bi-clock-history fs-1 text-warning"></i>
                                        <h3 class="mt-2"><?php echo $stats['pendientes'] + $stats['en_ruta']; ?></h3>
                                        <p class="text-muted mb-0">Pendientes</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="p-3 border rounded">
                                        <i class="bi bi-check-circle fs-1 text-success"></i>
                                        <h3 class="mt-2"><?php echo $stats['entregados']; ?></h3>
                                        <p class="text-muted mb-0">Entregados</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="p-3 border rounded">
                                        <i class="bi bi-x-circle fs-1 text-danger"></i>
                                        <h3 class="mt-2"><?php echo $stats['devueltos']; ?></h3>
                                        <p class="text-muted mb-0">Devueltos</p>
                                    </div>
                                </div>
                            </div>

                            <?php if ($stats['total'] > 0): ?>
                                <?php $porcentaje = round(($stats['entregados'] / $stats['total']) * 100); ?>
                                <div class="mt-4">
                                    <h6>Progreso de Entregas: <?php echo $porcentaje; ?>%</h6>
                                    <div class="progress" style="height: 25px;">
                                        <div class="progress-bar bg-success" style="width: <?php echo $porcentaje; ?>%">
                                            <?php echo $stats['entregados']; ?> / <?php echo $stats['total']; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Paquetes -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-boxes"></i> Paquetes Asignados (<?php echo count($paquetes); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (count($paquetes) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Código Seguimiento</th>
                                    <th>Cliente</th>
                                    <th>Dirección</th>
                                    <th>Ubicación</th>
                                    <th>Tipo Servicio</th>
                                    <th>Monto</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paquetes as $paquete): ?>
                                <tr>
                                    <td>
                                        <a href="paquete_detalle.php?id=<?php echo $paquete['id']; ?>" class="text-decoration-none">
                                            <strong><?php echo $paquete['codigo_seguimiento']; ?></strong>
                                        </a>
                                    </td>
                                    <td>
                                        <?php echo $paquete['cliente_nombre']; ?><br>
                                        <small class="text-muted"><?php echo $paquete['cliente_telefono']; ?></small>
                                    </td>
                                    <td><small><?php echo $paquete['direccion_destino']; ?></small></td>
                                    <td><?php echo $paquete['ubicacion']; ?></td>
                                    <td>
                                        <?php
                                        $tipo_badges = ['normal' => 'secondary', 'express' => 'warning', 'urgente' => 'danger'];
                                        ?>
                                        <span class="badge bg-<?php echo $tipo_badges[$paquete['tipo_servicio']] ?? 'secondary'; ?>">
                                            <?php echo ucfirst($paquete['tipo_servicio']); ?>
                                        </span>
                                    </td>
                                    <td>S/ <?php echo number_format($paquete['monto_envio'], 2); ?></td>
                                    <td>
                                        <?php
                                        $estado_badges = [
                                            'pendiente' => 'secondary',
                                            'en_ruta' => 'primary',
                                            'entregado' => 'success',
                                            'devuelto' => 'danger'
                                        ];
                                        ?>
                                        <span class="badge bg-<?php echo $estado_badges[$paquete['estado']] ?? 'secondary'; ?>">
                                            <?php echo ucfirst($paquete['estado']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No hay paquetes asignados a esta ruta todavía.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
