<?php
require_once '../config/config.php';
requireRole(['admin', 'asistente']);

$pageTitle = 'Paquetes Rezagados';

$db = Database::getInstance()->getConnection();
$sql = "SELECT p.*, pr.*, u.nombre as repartidor_nombre, u.apellido as repartidor_apellido
        FROM paquetes_rezagados pr
        INNER JOIN paquetes p ON pr.paquete_id = p.id
        LEFT JOIN usuarios u ON p.repartidor_id = u.id
        WHERE pr.solucionado = 0
        ORDER BY pr.fecha_rezago DESC";
$rezagados = $db->query($sql)->fetchAll();
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

            <div class="alert alert-warning">
                <i class="bi bi-info-circle"></i> <strong>Paquetes rezagados:</strong> 
                Entregas que no pudieron completarse y requieren un nuevo intento.
            </div>

            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo count($rezagados); ?></h3>
                            <p>Paquetes Rezagados</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Destinatario</th>
                                    <th>Dirección</th>
                                    <th>Repartidor</th>
                                    <th>Motivo</th>
                                    <th>Fecha Reintento</th>
                                    <th>Intentos</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rezagados as $rez): ?>
                                <tr>
                                    <td><strong><?php echo $rez['codigo_seguimiento']; ?></strong></td>
                                    <td><?php echo $rez['destinatario_nombre']; ?></td>
                                    <td class="small"><?php echo $rez['direccion_completa']; ?></td>
                                    <td><?php echo $rez['repartidor_nombre'] ? $rez['repartidor_nombre'] . ' ' . $rez['repartidor_apellido'] : '-'; ?></td>
                                    <td><span class="badge bg-danger"><?php echo ucfirst(str_replace('_', ' ', $rez['motivo'])); ?></span></td>
                                    <td><?php echo $rez['proximo_intento'] ? formatDate($rez['proximo_intento']) : '-'; ?></td>
                                    <td><span class="badge bg-warning"><?php echo $rez['intentos_realizados']; ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="reasignar(<?php echo $rez['paquete_id']; ?>)">
                                            <i class="bi bi-arrow-repeat"></i> Reasignar
                                        </button>
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
    <script>
        function reasignar(id) {
            if (confirm('¿Reasignar este paquete a un repartidor?')) {
                window.location.href = 'paquetes.php?reasignar=' + id;
            }
        }
    </script>
</body>
</html>
