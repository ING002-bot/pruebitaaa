<?php
require_once '../config/config.php';
requireRole(['asistente', 'admin']);

$paquete_id = $_GET['id'] ?? 0;
$db = Database::getInstance()->getConnection();

// Obtener detalles del paquete rezagado
$sql = "SELECT p.*, pr.*, 
        u.nombre as repartidor_nombre, 
        u.apellido as repartidor_apellido,
        u.telefono as repartidor_telefono
        FROM paquetes p
        LEFT JOIN paquetes_rezagados pr ON p.id = pr.paquete_id
        LEFT JOIN usuarios u ON p.repartidor_id = u.id
        WHERE p.id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $paquete_id);
$stmt->execute();
$paquete = Database::getInstance()->fetch($stmt);

if (!$paquete) {
    header('Location: rezagados.php');
    exit;
}

// Obtener historial del paquete (si la tabla existe)
$historial = [];
$table_check = $db->query("SHOW TABLES LIKE 'paquetes_historial'");
if ($table_check && $table_check->num_rows > 0) {
    $sql_historial = "SELECT ph.*, u.nombre, u.apellido 
                      FROM paquetes_historial ph
                      LEFT JOIN usuarios u ON ph.usuario_id = u.id
                      WHERE ph.paquete_id = ?
                      ORDER BY ph.fecha_cambio DESC";
    $stmt_hist = $db->prepare($sql_historial);
    $stmt_hist->bind_param("i", $paquete_id);
    $stmt_hist->execute();
    $historial = Database::getInstance()->fetchAll($stmt_hist->get_result());
}

$pageTitle = 'Detalle Paquete Rezagado';
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
                    <h1><i class="bi bi-exclamation-triangle-fill text-warning"></i> Detalle Paquete Rezagado</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="rezagados.php">Rezagados</a></li>
                            <li class="breadcrumb-item active">Detalle</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="rezagados.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Información del Paquete -->
                <div class="col-md-6 mb-4">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0"><i class="bi bi-box-seam"></i> Información del Paquete</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Código:</th>
                                    <td><strong class="text-primary"><?php echo $paquete['codigo_seguimiento']; ?></strong></td>
                                </tr>
                                <tr>
                                    <th>Estado:</th>
                                    <td>
                                        <span class="badge bg-danger"><?php echo ucfirst($paquete['estado']); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Destinatario:</th>
                                    <td><?php echo $paquete['destinatario_nombre']; ?></td>
                                </tr>
                                <tr>
                                    <th>Teléfono:</th>
                                    <td><?php echo $paquete['destinatario_telefono']; ?></td>
                                </tr>
                                <tr>
                                    <th>Dirección:</th>
                                    <td><?php echo $paquete['direccion_completa']; ?></td>
                                </tr>
                                <tr>
                                    <th>Departamento:</th>
                                    <td><?php echo $paquete['departamento'] ?? '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>Provincia:</th>
                                    <td><?php echo $paquete['provincia'] ?? '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>Distrito:</th>
                                    <td><?php echo $paquete['distrito'] ?? '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>Peso:</th>
                                    <td><?php echo $paquete['peso'] ?? 'No especificado'; ?> kg</td>
                                </tr>
                                <tr>
                                    <th>Fecha Recepción:</th>
                                    <td><?php echo date('d/m/Y H:i', strtotime($paquete['fecha_recepcion'])); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Información del Rezago -->
                <div class="col-md-6 mb-4">
                    <div class="card border-danger">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="bi bi-exclamation-circle"></i> Información del Rezago</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Repartidor:</th>
                                    <td>
                                        <?php if($paquete['repartidor_nombre']): ?>
                                            <?php echo $paquete['repartidor_nombre'] . ' ' . $paquete['repartidor_apellido']; ?>
                                            <br><small class="text-muted"><?php echo $paquete['repartidor_telefono'] ?? ''; ?></small>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Sin asignar</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Motivo Rezago:</th>
                                    <td>
                                        <span class="badge bg-warning text-dark">
                                            <?php echo $paquete['motivo_rezago'] ?? $paquete['motivo'] ?? 'No especificado'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Fecha Rezago:</th>
                                    <td>
                                        <?php if(isset($paquete['fecha_rezago'])): ?>
                                            <strong class="text-danger">
                                                <?php echo date('d/m/Y H:i', strtotime($paquete['fecha_rezago'])); ?>
                                            </strong>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Intentos de Entrega:</th>
                                    <td>
                                        <span class="badge bg-info"><?php echo $paquete['intentos_entrega'] ?? 0; ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Estado Solución:</th>
                                    <td>
                                        <?php if(isset($paquete['solucionado']) && $paquete['solucionado']): ?>
                                            <span class="badge bg-success">Solucionado</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php if(isset($paquete['observaciones_rezago']) && $paquete['observaciones_rezago']): ?>
                                <tr>
                                    <th>Observaciones:</th>
                                    <td><?php echo nl2br(htmlspecialchars($paquete['observaciones_rezago'])); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>

                            <?php if(!isset($paquete['solucionado']) || !$paquete['solucionado']): ?>
                            <div class="mt-3">
                                <a href="rezagado_solucionar.php?id=<?php echo $paquete['id']; ?>" class="btn btn-success btn-sm w-100">
                                    <i class="bi bi-check-circle"></i> Marcar como Solucionado
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Historial -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Historial de Cambios</h5>
                        </div>
                        <div class="card-body">
                            <?php if(count($historial) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Estado Anterior</th>
                                                <th>Estado Nuevo</th>
                                                <th>Usuario</th>
                                                <th>Observaciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($historial as $h): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y H:i', strtotime($h['fecha_cambio'])); ?></td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?php echo ucfirst(str_replace('_', ' ', $h['estado_anterior'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <?php echo ucfirst(str_replace('_', ' ', $h['estado_nuevo'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo $h['nombre'] ? $h['nombre'] . ' ' . $h['apellido'] : 'Sistema'; ?>
                                                </td>
                                                <td><?php echo $h['observaciones'] ?? '-'; ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center py-3">No hay historial de cambios</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
