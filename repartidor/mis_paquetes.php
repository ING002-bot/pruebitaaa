<?php
require_once '../config/config.php';
requireRole('repartidor');

$db = Database::getInstance()->getConnection();
$repartidor_id = $_SESSION['usuario_id'];

// Obtener paquetes del repartidor
$stmt = $db->prepare("
    SELECT p.*, 
           e.tipo_entrega as ultimo_intento,
           e.fecha_entrega as fecha_ultimo_intento
    FROM paquetes p
    LEFT JOIN entregas e ON p.id = e.paquete_id 
        AND e.id = (SELECT MAX(id) FROM entregas WHERE paquete_id = p.id)
    WHERE p.repartidor_id = ?
    ORDER BY 
        CASE p.estado 
            WHEN 'en_ruta' THEN 1
            WHEN 'pendiente' THEN 2
            WHEN 'rezagado' THEN 3
            WHEN 'entregado' THEN 4
            ELSE 5
        END,
        p.prioridad DESC,
        p.fecha_asignacion DESC
");
$stmt->execute([$repartidor_id]);
$paquetes = $stmt->fetchAll();

$pageTitle = "Mis Paquetes";
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
                <h1><i class="bi bi-box-seam"></i> Mis Paquetes</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Mis Paquetes</li>
                    </ol>
                </nav>
            </div>

            <!-- Estadísticas Rápidas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-primary">
                        <div class="card-body text-center">
                            <i class="bi bi-truck text-primary" style="font-size: 2rem;"></i>
                            <h3 class="mt-2"><?php echo count(array_filter($paquetes, fn($p) => $p['estado'] === 'en_ruta')); ?></h3>
                            <p class="mb-0 text-muted">En Ruta</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-warning">
                        <div class="card-body text-center">
                            <i class="bi bi-clock text-warning" style="font-size: 2rem;"></i>
                            <h3 class="mt-2"><?php echo count(array_filter($paquetes, fn($p) => $p['estado'] === 'pendiente')); ?></h3>
                            <p class="mb-0 text-muted">Pendientes</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                            <h3 class="mt-2"><?php echo count(array_filter($paquetes, fn($p) => $p['estado'] === 'entregado')); ?></h3>
                            <p class="mb-0 text-muted">Entregados</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-danger">
                        <div class="card-body text-center">
                            <i class="bi bi-exclamation-triangle text-danger" style="font-size: 2rem;"></i>
                            <h3 class="mt-2"><?php echo count(array_filter($paquetes, fn($p) => $p['estado'] === 'rezagado')); ?></h3>
                            <p class="mb-0 text-muted">Rezagados</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" id="filtroEstado">
                                <option value="">Todos</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="en_ruta">En Ruta</option>
                                <option value="entregado">Entregado</option>
                                <option value="rezagado">Rezagado</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Prioridad</label>
                            <select class="form-select" id="filtroPrioridad">
                                <option value="">Todas</option>
                                <option value="urgente">Urgente</option>
                                <option value="alta">Alta</option>
                                <option value="media">Media</option>
                                <option value="baja">Baja</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Buscar</label>
                            <input type="text" class="form-control" id="buscarPaquete" placeholder="Tracking, destinatario, dirección...">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-secondary w-100" onclick="limpiarFiltros()">
                                <i class="bi bi-x-circle"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Paquetes -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Todos mis Paquetes</h5>
                    <span class="badge bg-primary"><?php echo count($paquetes); ?> paquetes</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($paquetes)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                            <p class="text-muted mt-3">No tienes paquetes asignados</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tracking</th>
                                        <th>Destinatario</th>
                                        <th>Dirección</th>
                                        <th>Ciudad</th>
                                        <th>Prioridad</th>
                                        <th>Estado</th>
                                        <th>Último Intento</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaPaquetes">
                                    <?php foreach ($paquetes as $paquete): 
                                        $badgeEstado = [
                                            'pendiente' => 'secondary',
                                            'en_ruta' => 'primary',
                                            'entregado' => 'success',
                                            'rezagado' => 'warning',
                                            'devuelto' => 'danger'
                                        ][$paquete['estado']] ?? 'secondary';

                                        $badgePrioridad = [
                                            'urgente' => 'danger',
                                            'alta' => 'warning',
                                            'media' => 'info',
                                            'baja' => 'secondary'
                                        ][$paquete['prioridad']] ?? 'secondary';
                                    ?>
                                        <tr data-estado="<?php echo $paquete['estado']; ?>" 
                                            data-prioridad="<?php echo $paquete['prioridad']; ?>"
                                            data-search="<?php echo strtolower($paquete['codigo_seguimiento'] . ' ' . $paquete['destinatario_nombre'] . ' ' . $paquete['direccion_completa']); ?>">
                                            <td>
                                                <strong><?php echo htmlspecialchars($paquete['codigo_seguimiento']); ?></strong>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($paquete['destinatario_nombre']); ?>
                                                <?php if ($paquete['destinatario_telefono']): ?>
                                                    <br><small class="text-muted">
                                                        <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($paquete['destinatario_telefono']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars(substr($paquete['direccion_completa'], 0, 50)); ?><?php echo strlen($paquete['direccion_completa']) > 50 ? '...' : ''; ?></td>
                                            <td><?php echo htmlspecialchars($paquete['ciudad'] ?? '-'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $badgePrioridad; ?>">
                                                    <?php echo ucfirst($paquete['prioridad']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $badgeEstado; ?>">
                                                    <?php echo ucfirst($paquete['estado']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($paquete['ultimo_intento']): ?>
                                                    <small>
                                                        <?php 
                                                        $iconoIntento = [
                                                            'exitosa' => '<i class="bi bi-check-circle text-success"></i>',
                                                            'rechazada' => '<i class="bi bi-x-circle text-danger"></i>',
                                                            'no_encontrado' => '<i class="bi bi-question-circle text-warning"></i>'
                                                        ][$paquete['ultimo_intento']] ?? '';
                                                        echo $iconoIntento;
                                                        ?>
                                                        <?php echo date('d/m H:i', strtotime($paquete['fecha_ultimo_intento'])); ?>
                                                    </small>
                                                <?php else: ?>
                                                    <small class="text-muted">Sin intentos</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <?php if (in_array($paquete['estado'], ['en_ruta', 'pendiente', 'rezagado'])): ?>
                                                        <a href="entregar.php?paquete_id=<?php echo $paquete['id']; ?>" 
                                                           class="btn btn-success" title="Entregar">
                                                            <i class="bi bi-truck"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($paquete['direccion_latitud'] && $paquete['direccion_longitud']): ?>
                                                        <a href="https://www.google.com/maps?q=<?php echo $paquete['direccion_latitud']; ?>,<?php echo $paquete['direccion_longitud']; ?>" 
                                                           target="_blank" class="btn btn-primary" title="Ver en mapa">
                                                            <i class="bi bi-geo-alt"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/notificaciones.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // Filtros
        const filtroEstado = document.getElementById('filtroEstado');
        const filtroPrioridad = document.getElementById('filtroPrioridad');
        const buscarPaquete = document.getElementById('buscarPaquete');

        filtroEstado.addEventListener('change', aplicarFiltros);
        filtroPrioridad.addEventListener('change', aplicarFiltros);
        buscarPaquete.addEventListener('input', aplicarFiltros);

        function aplicarFiltros() {
            const estado = filtroEstado.value.toLowerCase();
            const prioridad = filtroPrioridad.value.toLowerCase();
            const busqueda = buscarPaquete.value.toLowerCase();
            const rows = document.querySelectorAll('#tablaPaquetes tr');

            rows.forEach(row => {
                let mostrar = true;

                if (estado && row.dataset.estado !== estado) {
                    mostrar = false;
                }

                if (prioridad && row.dataset.prioridad !== prioridad) {
                    mostrar = false;
                }

                if (busqueda && !row.dataset.search.includes(busqueda)) {
                    mostrar = false;
                }

                row.style.display = mostrar ? '' : 'none';
            });
        }

        function limpiarFiltros() {
            filtroEstado.value = '';
            filtroPrioridad.value = '';
            buscarPaquete.value = '';
            aplicarFiltros();
        }
    </script>
</body>
</html>
