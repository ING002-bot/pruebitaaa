<?php
require_once '../config/config.php';
requireRole(['asistente']);

$pageTitle = 'Gestión de Entregas';

// Filtros
$filtro_estado = isset($_GET['estado']) ? sanitize($_GET['estado']) : '';
$filtro_repartidor = isset($_GET['repartidor']) ? (int)$_GET['repartidor'] : 0;
$filtro_fecha_desde = isset($_GET['fecha_desde']) ? sanitize($_GET['fecha_desde']) : '';
$filtro_fecha_hasta = isset($_GET['fecha_hasta']) ? sanitize($_GET['fecha_hasta']) : '';
$buscar = isset($_GET['buscar']) ? sanitize($_GET['buscar']) : '';

// Construir query
$db = Database::getInstance()->getConnection();
$sql = "SELECT e.*, p.codigo_seguimiento, p.destinatario_nombre, p.direccion_completa,
        u.nombre as repartidor_nombre, u.apellido as repartidor_apellido
        FROM entregas e
        LEFT JOIN paquetes p ON e.paquete_id = p.id
        LEFT JOIN usuarios u ON e.repartidor_id = u.id
        WHERE 1=1";

$params = [];
$types = '';

if ($filtro_estado) {
    $sql .= " AND e.tipo_entrega = ?";
    $params[] = $filtro_estado;
    $types .= 's';
}

if ($filtro_repartidor) {
    $sql .= " AND e.repartidor_id = ?";
    $params[] = $filtro_repartidor;
    $types .= 'i';
}

if ($filtro_fecha_desde) {
    $sql .= " AND DATE(e.fecha_entrega) >= ?";
    $params[] = $filtro_fecha_desde;
    $types .= 's';
}

if ($filtro_fecha_hasta) {
    $sql .= " AND DATE(e.fecha_entrega) <= ?";
    $params[] = $filtro_fecha_hasta;
    $types .= 's';
}

if ($buscar) {
    $sql .= " AND (p.codigo_seguimiento LIKE ? OR p.destinatario_nombre LIKE ? OR e.receptor_nombre LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
    $types .= 'sss';
}

$sql .= " ORDER BY e.fecha_entrega DESC LIMIT 100";

$stmt = $db->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$entregas = Database::getInstance()->fetchAll($stmt->get_result());

// Obtener repartidores para filtro
$sqlRepartidores = "SELECT id, nombre, apellido FROM usuarios WHERE rol = 'repartidor' AND estado = 'activo' ORDER BY nombre";
$repartidores = Database::getInstance()->fetchAll($db->query($sqlRepartidores));

// Estadísticas del día
$sqlStats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN tipo_entrega = 'exitosa' THEN 1 ELSE 0 END) as exitosas,
    SUM(CASE WHEN tipo_entrega = 'rechazada' THEN 1 ELSE 0 END) as rechazadas,
    SUM(CASE WHEN tipo_entrega = 'parcial' THEN 1 ELSE 0 END) as parciales
    FROM entregas 
    WHERE DATE(fecha_entrega) = CURDATE()";
$statsHoy = Database::getInstance()->fetch($db->query($sqlStats));
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
                <h1><i class="bi bi-check-circle"></i> <?php echo $pageTitle; ?></h1>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Estadísticas del día -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 class="mb-0"><?php echo $statsHoy['total'] ?? 0; ?></h3>
                            <small class="text-muted">Total Hoy</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <h3 class="mb-0 text-success"><?php echo $statsHoy['exitosas'] ?? 0; ?></h3>
                            <small class="text-muted">Exitosas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-danger">
                        <div class="card-body text-center">
                            <h3 class="mb-0 text-danger"><?php echo $statsHoy['rechazadas'] ?? 0; ?></h3>
                            <small class="text-muted">Rechazadas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-warning">
                        <div class="card-body text-center">
                            <h3 class="mb-0 text-warning"><?php echo $statsHoy['parciales'] ?? 0; ?></h3>
                            <small class="text-muted">Parciales</small>
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
                            <label class="form-label">Buscar</label>
                            <input type="text" name="buscar" class="form-control" placeholder="Código, destinatario..." value="<?php echo $buscar; ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select">
                                <option value="">Todos</option>
                                <option value="exitosa" <?php echo $filtro_estado === 'exitosa' ? 'selected' : ''; ?>>Exitosa</option>
                                <option value="rechazada" <?php echo $filtro_estado === 'rechazada' ? 'selected' : ''; ?>>Rechazada</option>
                                <option value="parcial" <?php echo $filtro_estado === 'parcial' ? 'selected' : ''; ?>>Parcial</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Repartidor</label>
                            <select name="repartidor" class="form-select">
                                <option value="0">Todos</option>
                                <?php foreach ($repartidores as $rep): ?>
                                    <option value="<?php echo $rep['id']; ?>" <?php echo $filtro_repartidor == $rep['id'] ? 'selected' : ''; ?>>
                                        <?php echo $rep['nombre'] . ' ' . $rep['apellido']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Desde</label>
                            <input type="date" name="fecha_desde" class="form-control" value="<?php echo $filtro_fecha_desde; ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Hasta</label>
                            <input type="date" name="fecha_hasta" class="form-control" value="<?php echo $filtro_fecha_hasta; ?>">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de entregas -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Listado de Entregas (<?php echo count($entregas); ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Destinatario</th>
                                    <th>Dirección</th>
                                    <th>Repartidor</th>
                                    <th>Tipo</th>
                                    <th>Receptor</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($entregas as $e): ?>
                                <tr>
                                    <td><strong><?php echo $e['codigo_seguimiento']; ?></strong></td>
                                    <td><?php echo $e['destinatario_nombre']; ?></td>
                                    <td><?php echo substr($e['direccion_completa'] ?? '', 0, 30) . '...'; ?></td>
                                    <td><?php echo $e['repartidor_nombre'] . ' ' . $e['repartidor_apellido']; ?></td>
                                    <td>
                                        <?php
                                        $badges = ['exitosa' => 'success', 'rechazada' => 'danger', 'parcial' => 'warning'];
                                        ?>
                                        <span class="badge bg-<?php echo $badges[$e['tipo_entrega']] ?? 'secondary'; ?>">
                                            <?php echo ucfirst($e['tipo_entrega']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $e['receptor_nombre'] ?? '-'; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($e['fecha_entrega'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="verDetalle(<?php echo $e['id']; ?>)">
                                            <i class="bi bi-eye"></i>
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
    <script src="../assets/js/dashboard.js"></script>
    <script>
        function verDetalle(id) {
            window.location.href = 'entrega_detalle.php?id=' + id;
        }
    </script>
</body>
</html>
