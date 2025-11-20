<?php
require_once '../config/config.php';
requireRole('repartidor');

$db = Database::getInstance()->getConnection();
$repartidor_id = $_SESSION['usuario_id'];

// Paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Obtener total de entregas
$stmt = $db->prepare("SELECT COUNT(*) as total FROM entregas WHERE repartidor_id = ?");
$stmt->execute([$repartidor_id]);
$totalEntregas = $stmt->fetch()['total'];
$totalPages = ceil($totalEntregas / $limit);

// Obtener historial de entregas
$stmt = $db->prepare("
    SELECT e.*, 
           p.codigo_seguimiento,
           p.destinatario_nombre,
           p.direccion_completa,
           p.ciudad,
           p.costo_envio
    FROM entregas e
    INNER JOIN paquetes p ON e.paquete_id = p.id
    WHERE e.repartidor_id = ?
    ORDER BY e.fecha_entrega DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$repartidor_id, $limit, $offset]);
$entregas = $stmt->fetchAll();

// Estadísticas del historial
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN tipo_entrega = 'exitosa' THEN 1 END) as exitosas,
        COUNT(CASE WHEN tipo_entrega = 'rechazada' THEN 1 END) as rechazadas,
        COUNT(CASE WHEN tipo_entrega = 'no_encontrado' THEN 1 END) as no_encontrado,
        SUM(CASE WHEN tipo_entrega = 'exitosa' THEN p.costo_envio ELSE 0 END) as total_ganado
    FROM entregas e
    INNER JOIN paquetes p ON e.paquete_id = p.id
    WHERE e.repartidor_id = ?
");
$stmt->execute([$repartidor_id]);
$stats = $stmt->fetch();

$pageTitle = "Historial de Entregas";
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
                <h1><i class="bi bi-clock-history"></i> Historial de Entregas</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Historial</li>
                    </ol>
                </nav>
            </div>

            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-primary">
                        <div class="card-body text-center">
                            <i class="bi bi-truck text-primary" style="font-size: 2rem;"></i>
                            <h3 class="mt-2"><?php echo number_format($stats['total']); ?></h3>
                            <p class="mb-0 text-muted">Total Entregas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                            <h3 class="mt-2"><?php echo number_format($stats['exitosas']); ?></h3>
                            <p class="mb-0 text-muted">Exitosas</p>
                            <?php if ($stats['total'] > 0): ?>
                                <small class="text-success">
                                    <?php echo round(($stats['exitosas'] / $stats['total']) * 100, 1); ?>% tasa de éxito
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-danger">
                        <div class="card-body text-center">
                            <i class="bi bi-x-circle text-danger" style="font-size: 2rem;"></i>
                            <h3 class="mt-2"><?php echo number_format($stats['rechazadas'] + $stats['no_encontrado']); ?></h3>
                            <p class="mb-0 text-muted">Fallidas</p>
                            <small class="text-muted">
                                <?php echo $stats['rechazadas']; ?> rechazadas, <?php echo $stats['no_encontrado']; ?> no encontrado
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-info">
                        <div class="card-body text-center">
                            <i class="bi bi-cash-stack text-info" style="font-size: 2rem;"></i>
                            <h3 class="mt-2">S/ <?php echo number_format($stats['total_ganado'], 2); ?></h3>
                            <p class="mb-0 text-muted">Total Ganado</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Tipo de Entrega</label>
                            <select class="form-select" id="filtroTipo">
                                <option value="">Todas</option>
                                <option value="exitosa">Exitosa</option>
                                <option value="rechazada">Rechazada</option>
                                <option value="no_encontrado">No Encontrado</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Desde</label>
                            <input type="date" class="form-control" id="filtroDesde">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Hasta</label>
                            <input type="date" class="form-control" id="filtroHasta">
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-secondary w-100" onclick="limpiarFiltros()">
                                <i class="bi bi-x-circle"></i> Limpiar Filtros
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Historial -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Registro de Entregas</h5>
                    <span class="badge bg-primary"><?php echo number_format($totalEntregas); ?> registros</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($entregas)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                            <p class="text-muted mt-3">No hay entregas registradas</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha/Hora</th>
                                        <th>Tracking</th>
                                        <th>Destinatario</th>
                                        <th>Dirección</th>
                                        <th>Tipo Entrega</th>
                                        <th>Receptor</th>
                                        <th>Monto</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaHistorial">
                                    <?php foreach ($entregas as $entrega): 
                                        $badgeTipo = [
                                            'exitosa' => 'success',
                                            'rechazada' => 'danger',
                                            'no_encontrado' => 'warning'
                                        ][$entrega['tipo_entrega']] ?? 'secondary';
                                    ?>
                                        <tr data-tipo="<?php echo $entrega['tipo_entrega']; ?>" 
                                            data-fecha="<?php echo date('Y-m-d', strtotime($entrega['fecha_entrega'])); ?>">
                                            <td>
                                                <strong><?php echo date('d/m/Y', strtotime($entrega['fecha_entrega'])); ?></strong>
                                                <br><small class="text-muted"><?php echo date('H:i', strtotime($entrega['fecha_entrega'])); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($entrega['codigo_seguimiento']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($entrega['destinatario_nombre']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars(substr($entrega['direccion_completa'], 0, 40)); ?><?php echo strlen($entrega['direccion_completa']) > 40 ? '...' : ''; ?>
                                                <?php if ($entrega['ciudad']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($entrega['ciudad']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $badgeTipo; ?>">
                                                    <?php 
                                                    $tipoTexto = [
                                                        'exitosa' => 'Exitosa',
                                                        'rechazada' => 'Rechazada',
                                                        'no_encontrado' => 'No Encontrado'
                                                    ][$entrega['tipo_entrega']] ?? $entrega['tipo_entrega'];
                                                    echo $tipoTexto;
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($entrega['tipo_entrega'] === 'exitosa'): ?>
                                                    <?php echo htmlspecialchars($entrega['receptor_nombre'] ?? '-'); ?>
                                                    <?php if ($entrega['receptor_dni']): ?>
                                                        <br><small class="text-muted">DNI: <?php echo htmlspecialchars($entrega['receptor_dni']); ?></small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($entrega['tipo_entrega'] === 'exitosa'): ?>
                                                    <strong class="text-success">S/ <?php echo number_format($entrega['costo_envio'] ?? TARIFA_POR_PAQUETE, 2); ?></strong>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick="verDetalle(<?php echo $entrega['id']; ?>)">
                                                    <i class="bi bi-eye"></i> Ver
                                                </button>
                                                <?php if ($entrega['latitud_entrega'] && $entrega['longitud_entrega']): ?>
                                                    <a href="https://www.google.com/maps?q=<?php echo $entrega['latitud_entrega']; ?>,<?php echo $entrega['longitud_entrega']; ?>" 
                                                       target="_blank" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-geo-alt"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <?php if ($totalPages > 1): ?>
                            <div class="card-footer">
                                <nav aria-label="Paginación">
                                    <ul class="pagination justify-content-center mb-0">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">Anterior</a>
                                        </li>
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <?php if ($i == 1 || $i == $totalPages || abs($i - $page) <= 2): ?>
                                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                                </li>
                                            <?php elseif (abs($i - $page) == 3): ?>
                                                <li class="page-item disabled"><span class="page-link">...</span></li>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">Siguiente</a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detalle -->
    <div class="modal fade" id="detalleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle de Entrega</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detalleContent">
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

        function verDetalle(entregaId) {
            const modal = new bootstrap.Modal(document.getElementById('detalleModal'));
            modal.show();
            
            fetch(`../admin/entrega_detalle.php?id=${entregaId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('detalleContent').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('detalleContent').innerHTML = 
                        '<div class="alert alert-danger">Error al cargar los detalles</div>';
                });
        }

        // Filtros
        const filtroTipo = document.getElementById('filtroTipo');
        const filtroDesde = document.getElementById('filtroDesde');
        const filtroHasta = document.getElementById('filtroHasta');

        filtroTipo.addEventListener('change', aplicarFiltros);
        filtroDesde.addEventListener('change', aplicarFiltros);
        filtroHasta.addEventListener('change', aplicarFiltros);

        function aplicarFiltros() {
            const tipo = filtroTipo.value;
            const desde = filtroDesde.value;
            const hasta = filtroHasta.value;
            const rows = document.querySelectorAll('#tablaHistorial tr');

            rows.forEach(row => {
                let mostrar = true;

                if (tipo && row.dataset.tipo !== tipo) {
                    mostrar = false;
                }

                if (desde && row.dataset.fecha < desde) {
                    mostrar = false;
                }

                if (hasta && row.dataset.fecha > hasta) {
                    mostrar = false;
                }

                row.style.display = mostrar ? '' : 'none';
            });
        }

        function limpiarFiltros() {
            filtroTipo.value = '';
            filtroDesde.value = '';
            filtroHasta.value = '';
            aplicarFiltros();
        }
    </script>
</body>
</html>
