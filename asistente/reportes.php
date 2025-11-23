<?php
require_once '../config/config.php';
requireRole(['asistente']);

$pageTitle = 'Reportes de Paquetes';

$db = Database::getInstance()->getConnection();

// Filtros
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$estado = $_GET['estado'] ?? '';
$zona = $_GET['zona'] ?? '';

// Construir query
$where = ["DATE(p.fecha_recepcion) BETWEEN ? AND ?"];
$params = [$fecha_inicio, $fecha_fin];
$types = "ss";

if (!empty($estado)) {
    $where[] = "p.estado = ?";
    $params[] = $estado;
    $types .= "s";
}

if (!empty($zona)) {
    $where[] = "r.zona = ?";
    $params[] = $zona;
    $types .= 's';
}

$whereClause = implode(' AND ', $where);

// Obtener paquetes
$sql = "SELECT p.id, p.codigo_seguimiento, p.destinatario_nombre, p.direccion_completa, 
        p.destinatario_telefono, p.estado, p.fecha_recepcion as fecha_registro,
        r.nombre as ruta_nombre, r.zona, u.nombre as repartidor_nombre, u.apellido as repartidor_apellido
        FROM paquetes p
        LEFT JOIN ruta_paquetes rp ON p.id = rp.paquete_id
        LEFT JOIN rutas r ON rp.ruta_id = r.id
        LEFT JOIN usuarios u ON p.repartidor_id = u.id
        WHERE $whereClause
        ORDER BY p.fecha_recepcion DESC";

$stmt = $db->prepare($sql);
if (!$stmt) {
    die('Error en la consulta SQL: ' . $db->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$paquetes = Database::getInstance()->fetchAll($result);

// Estadísticas
$stats = [
    'total' => count($paquetes),
    'pendiente' => 0,
    'en_ruta' => 0,
    'entregado' => 0,
    'rezagado' => 0
];

foreach ($paquetes as $p) {
    if (isset($stats[$p['estado']])) {
        $stats[$p['estado']]++;
    }
}

// Obtener zonas para filtro
$zona_query = $db->query("SELECT DISTINCT zona FROM rutas WHERE zona IS NOT NULL ORDER BY zona");
$zonas = $zona_query ? Database::getInstance()->fetchAll($zona_query) : [];
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
                <h1><i class="bi bi-file-earmark-text"></i> <?php echo $pageTitle; ?></h1>
            </div>

            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
                            <small class="text-muted">Total Paquetes</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-warning">
                        <div class="card-body text-center">
                            <h3 class="mb-0 text-warning"><?php echo $stats['pendiente']; ?></h3>
                            <small class="text-muted">Pendientes</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-primary">
                        <div class="card-body text-center">
                            <h3 class="mb-0 text-primary"><?php echo $stats['en_ruta']; ?></h3>
                            <small class="text-muted">En Ruta</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <h3 class="mb-0 text-success"><?php echo $stats['entregado']; ?></h3>
                            <small class="text-muted">Entregados</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-funnel"></i> Filtros</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" class="form-control" value="<?php echo $fecha_inicio; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha Fin</label>
                            <input type="date" name="fecha_fin" class="form-control" value="<?php echo $fecha_fin; ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select">
                                <option value="">Todos</option>
                                <option value="pendiente" <?php echo $estado === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="en_ruta" <?php echo $estado === 'en_ruta' ? 'selected' : ''; ?>>En Ruta</option>
                                <option value="entregado" <?php echo $estado === 'entregado' ? 'selected' : ''; ?>>Entregado</option>
                                <option value="rezagado" <?php echo $estado === 'rezagado' ? 'selected' : ''; ?>>Rezagado</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Zona</label>
                            <select name="zona" class="form-select">
                                <option value="">Todas</option>
                                <?php foreach ($zonas as $z): ?>
                                    <option value="<?php echo $z['zona']; ?>" <?php echo $zona === $z['zona'] ? 'selected' : ''; ?>>
                                        <?php echo $z['zona']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Botones de exportación -->
            <div class="mb-3">
                <form method="POST" action="reportes_export.php" class="d-inline">
                    <input type="hidden" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>">
                    <input type="hidden" name="fecha_fin" value="<?php echo $fecha_fin; ?>">
                    <input type="hidden" name="estado" value="<?php echo $estado; ?>">
                    <input type="hidden" name="zona" value="<?php echo $zona; ?>">
                    <input type="hidden" name="tipo_reporte" value="paquetes">
                    
                    <button type="submit" name="formato" value="excel" class="btn btn-success">
                        <i class="bi bi-file-earmark-excel"></i> Exportar a Excel
                    </button>
                    <button type="submit" name="formato" value="pdf" class="btn btn-danger">
                        <i class="bi bi-file-earmark-pdf"></i> Exportar a PDF
                    </button>
                </form>
            </div>

            <!-- Tabla de paquetes -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Listado de Paquetes (<?php echo count($paquetes); ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Destinatario</th>
                                    <th>Dirección</th>
                                    <th>Zona</th>
                                    <th>Estado</th>
                                    <th>Repartidor</th>
                                    <th>Fecha Registro</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paquetes as $p): ?>
                                <tr>
                                    <td><strong><?php echo $p['codigo_seguimiento']; ?></strong></td>
                                    <td><?php echo $p['destinatario_nombre']; ?></td>
                                    <td><?php echo substr($p['direccion_completa'], 0, 40) . (strlen($p['direccion_completa']) > 40 ? '...' : ''); ?></td>
                                    <td><span class="badge bg-info"><?php echo $p['zona'] ?: '-'; ?></span></td>
                                    <td>
                                        <?php
                                        $badges = ['pendiente' => 'warning', 'en_ruta' => 'primary', 'entregado' => 'success', 'rezagado' => 'danger'];
                                        ?>
                                        <span class="badge bg-<?php echo $badges[$p['estado']] ?? 'secondary'; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $p['estado'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $p['repartidor_nombre'] ? $p['repartidor_nombre'] . ' ' . $p['repartidor_apellido'] : '-'; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($p['fecha_registro'])); ?></td>
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
