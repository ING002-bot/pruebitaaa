<?php
require_once '../config/config.php';
requireRole(['admin', 'asistente']);

$pageTitle = 'Gestión de Rutas';

// Obtener rutas
$db = Database::getInstance()->getConnection();
$sql = "SELECT r.*, u.nombre as repartidor_nombre, u.apellido as repartidor_apellido,
        uc.nombre as creado_nombre, uc.apellido as creado_apellido
        FROM rutas r
        LEFT JOIN usuarios u ON r.repartidor_id = u.id
        LEFT JOIN usuarios uc ON r.creado_por = uc.id
        ORDER BY r.fecha_ruta DESC, r.id DESC
        LIMIT 100";
$rutas = $db->query($sql)->fetchAll();

// Repartidores activos
$repartidores = $db->query("SELECT id, nombre, apellido FROM usuarios WHERE rol = 'repartidor' AND estado = 'activo'")->fetchAll();
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
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaRuta">
                    <i class="bi bi-plus-circle"></i> Nueva Ruta
                </button>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Repartidor</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Paquetes</th>
                                    <th>Progreso</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rutas as $ruta): ?>
                                <tr>
                                    <td><?php echo $ruta['id']; ?></td>
                                    <td><strong><?php echo $ruta['nombre']; ?></strong></td>
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
                                        <span class="badge bg-info"><?php echo $ruta['total_paquetes']; ?> total</span>
                                    </td>
                                    <td>
                                        <?php if ($ruta['total_paquetes'] > 0): ?>
                                            <?php $porcentaje = round(($ruta['paquetes_entregados'] / $ruta['total_paquetes']) * 100); ?>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-success" style="width: <?php echo $porcentaje; ?>%">
                                                    <?php echo $ruta['paquetes_entregados']; ?>/<?php echo $ruta['total_paquetes']; ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Sin paquetes</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="ruta_detalle.php?id=<?php echo $ruta['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($ruta['estado'] == 'planificada'): ?>
                                            <a href="ruta_editar.php?id=<?php echo $ruta['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
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

    <!-- Modal Nueva Ruta -->
    <div class="modal fade" id="modalNuevaRuta" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Ruta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="ruta_guardar.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre de la Ruta *</label>
                            <input type="text" name="nombre" class="form-control" required placeholder="Ej: Ruta Lima Centro">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Repartidor *</label>
                            <select name="repartidor_id" class="form-select" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($repartidores as $rep): ?>
                                    <option value="<?php echo $rep['id']; ?>"><?php echo $rep['nombre'] . ' ' . $rep['apellido']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fecha de Ruta *</label>
                            <input type="date" name="fecha_ruta" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Ruta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
