<?php
require_once '../config/config.php';
requireRole('repartidor');

$db = Database::getInstance()->getConnection();
$repartidor_id = $_SESSION['usuario_id'];

// Obtener rutas del repartidor
$stmt = $db->prepare("
    SELECT r.*, 
           COUNT(DISTINCT rp.paquete_id) as total_paquetes,
           COUNT(DISTINCT CASE WHEN rp.estado = 'entregado' THEN rp.paquete_id END) as paquetes_entregados
    FROM rutas r
    LEFT JOIN ruta_paquetes rp ON r.id = rp.ruta_id
    WHERE r.repartidor_id = ?
    GROUP BY r.id
    ORDER BY r.fecha_ruta DESC, r.id DESC
");
$stmt->execute([$repartidor_id]);
$rutas = $stmt->fetchAll();

$pageTitle = "Mis Rutas";
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
                <h1><i class="bi bi-map"></i> Mis Rutas</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Mis Rutas</li>
                    </ol>
                </nav>
            </div>

            <?php if (isset($_SESSION['flash_message'])): 
                $flash = $_SESSION['flash_message'];
                unset($_SESSION['flash_message']);
            ?>
                <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($flash['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" id="filtroEstado">
                                <option value="">Todos</option>
                                <option value="planificada">Planificada</option>
                                <option value="en_progreso">En Progreso</option>
                                <option value="completada">Completada</option>
                                <option value="cancelada">Cancelada</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha</label>
                            <input type="date" class="form-control" id="filtroFecha">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-secondary w-100" onclick="limpiarFiltros()">
                                <i class="bi bi-x-circle"></i> Limpiar Filtros
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Rutas -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Mis Rutas Asignadas</h5>
                    <span class="badge bg-primary"><?php echo count($rutas); ?> rutas</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($rutas)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                            <p class="text-muted mt-3">No tienes rutas asignadas</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Nombre Ruta</th>
                                        <th>Estado</th>
                                        <th>Paquetes</th>
                                        <th>Progreso</th>
                                        <th>Hora Inicio</th>
                                        <th>Hora Fin</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rutas as $ruta): 
                                        $progreso = $ruta['total_paquetes'] > 0 
                                            ? ($ruta['paquetes_entregados'] / $ruta['total_paquetes']) * 100 
                                            : 0;
                                        
                                        $badgeClass = [
                                            'planificada' => 'secondary',
                                            'en_progreso' => 'primary',
                                            'completada' => 'success',
                                            'cancelada' => 'danger'
                                        ][$ruta['estado']] ?? 'secondary';
                                    ?>
                                        <tr data-estado="<?php echo $ruta['estado']; ?>" data-fecha="<?php echo $ruta['fecha_ruta']; ?>">
                                            <td><?php echo date('d/m/Y', strtotime($ruta['fecha_ruta'])); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($ruta['nombre_ruta']); ?></strong>
                                                <?php if (!empty($ruta['descripcion'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($ruta['descripcion']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $badgeClass; ?>">
                                                    <?php echo ucfirst($ruta['estado']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info text-dark">
                                                    <?php echo $ruta['paquetes_entregados']; ?> / <?php echo $ruta['total_paquetes']; ?>
                                                </span>
                                            </td>
                                            <td style="width: 120px;">
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: <?php echo $progreso; ?>%;" 
                                                         aria-valuenow="<?php echo $progreso; ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo round($progreso); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo $ruta['hora_inicio'] ? date('H:i', strtotime($ruta['hora_inicio'])) : '-'; ?></td>
                                            <td><?php echo $ruta['hora_fin'] ? date('H:i', strtotime($ruta['hora_fin'])) : '-'; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="verDetalleRuta(<?php echo $ruta['id']; ?>)">
                                                    <i class="bi bi-eye"></i> Ver
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detalle Ruta -->
    <div class="modal fade" id="detalleRutaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle de Ruta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detalleRutaContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/notificaciones.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        function verDetalleRuta(rutaId) {
            const modal = new bootstrap.Modal(document.getElementById('detalleRutaModal'));
            modal.show();
            
            fetch(`../admin/ruta_detalle.php?id=${rutaId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('detalleRutaContent').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('detalleRutaContent').innerHTML = 
                        '<div class="alert alert-danger">Error al cargar los detalles</div>';
                });
        }

        // Filtros
        document.getElementById('filtroEstado').addEventListener('change', aplicarFiltros);
        document.getElementById('filtroFecha').addEventListener('change', aplicarFiltros);

        function aplicarFiltros() {
            const estado = document.getElementById('filtroEstado').value;
            const fecha = document.getElementById('filtroFecha').value;
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                let mostrar = true;

                if (estado && row.dataset.estado !== estado) {
                    mostrar = false;
                }

                if (fecha && row.dataset.fecha !== fecha) {
                    mostrar = false;
                }

                row.style.display = mostrar ? '' : 'none';
            });
        }

        function limpiarFiltros() {
            document.getElementById('filtroEstado').value = '';
            document.getElementById('filtroFecha').value = '';
            aplicarFiltros();
        }
    </script>
</body>
</html>
