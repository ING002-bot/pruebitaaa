<?php
require_once '../config/config.php';
requireRole(['admin', 'asistente']);

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

if ($filtro_estado) {
    $sql .= " AND e.tipo_entrega = ?";
    $params[] = $filtro_estado;
}

if ($filtro_repartidor) {
    $sql .= " AND e.repartidor_id = ?";
    $params[] = $filtro_repartidor;
}

if ($filtro_fecha_desde) {
    $sql .= " AND DATE(e.fecha_entrega) >= ?";
    $params[] = $filtro_fecha_desde;
}

if ($filtro_fecha_hasta) {
    $sql .= " AND DATE(e.fecha_entrega) <= ?";
    $params[] = $filtro_fecha_hasta;
}

if ($buscar) {
    $sql .= " AND (p.codigo_seguimiento LIKE ? OR p.destinatario_nombre LIKE ? OR e.receptor_nombre LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
}

$sql .= " ORDER BY e.fecha_entrega DESC LIMIT 100";

$stmt = $db->prepare($sql);
if (!empty($params)) {
    $types = '';
    foreach ($params as $param) {
        if (is_int($param)) $types .= 'i';
        elseif (is_float($param)) $types .= 'd';
        else $types .= 's';
    }
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$entregas = Database::getInstance()->fetchAll($stmt->get_result());

// Obtener repartidores para filtro
$sqlRepartidores = "SELECT id, nombre, apellido FROM usuarios WHERE rol = 'repartidor' AND estado = 'activo' ORDER BY nombre";
$repartidores = Database::getInstance()->fetchAll($db->query($sqlRepartidores));

// Estadísticas
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
                <h1><i class="bi bi-box-seam"></i> <?php echo $pageTitle; ?></h1>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalFiltros">
                        <i class="bi bi-funnel"></i> Filtros
                    </button>
                </div>
            </div>

            <!-- Estadísticas del día -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $statsHoy['total']; ?></h3>
                            <p>Entregas Hoy</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $statsHoy['exitosas']; ?></h3>
                            <p>Exitosas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-danger">
                            <i class="bi bi-x-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $statsHoy['rechazadas']; ?></h3>
                            <p>Rechazadas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $statsHoy['parciales']; ?></h3>
                            <p>Parciales</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de entregas -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Listado de Entregas</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="tablaEntregas">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Destinatario</th>
                                    <th>Receptor</th>
                                    <th>Repartidor</th>
                                    <th>Fecha Entrega</th>
                                    <th>Tipo</th>
                                    <th>Foto</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($entregas as $entrega): ?>
                                <tr>
                                    <td><strong><?php echo $entrega['codigo_seguimiento']; ?></strong></td>
                                    <td><?php echo $entrega['destinatario_nombre']; ?></td>
                                    <td><?php echo $entrega['receptor_nombre'] ?: '-'; ?></td>
                                    <td><?php echo $entrega['repartidor_nombre'] . ' ' . $entrega['repartidor_apellido']; ?></td>
                                    <td><?php echo formatDate($entrega['fecha_entrega']); ?></td>
                                    <td>
                                        <?php
                                        $badges = [
                                            'exitosa' => 'success',
                                            'rechazada' => 'danger',
                                            'parcial' => 'warning'
                                        ];
                                        $badge = $badges[$entrega['tipo_entrega']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $badge; ?>">
                                            <?php echo ucfirst($entrega['tipo_entrega']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($entrega['foto_entrega']): ?>
                                            <button class="btn btn-sm btn-info" onclick="verFoto('<?php echo $entrega['foto_entrega']; ?>')">
                                                <i class="bi bi-image"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="verDetalle(<?php echo $entrega['id']; ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <?php if (!empty($entrega['latitud_entrega']) && !empty($entrega['longitud_entrega'])): ?>
                                            <button class="btn btn-sm btn-success" onclick="verMapa(<?php echo $entrega['latitud_entrega']; ?>, <?php echo $entrega['longitud_entrega']; ?>)">
                                                <i class="bi bi-geo-alt"></i>
                                            </button>
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

    <!-- Modal Filtros -->
    <div class="modal fade" id="modalFiltros" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filtrar Entregas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="GET">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Buscar</label>
                            <input type="text" name="buscar" class="form-control" value="<?php echo $buscar; ?>" placeholder="Código, destinatario, receptor...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo de Entrega</label>
                            <select name="estado" class="form-select">
                                <option value="">Todos</option>
                                <option value="exitosa" <?php echo $filtro_estado == 'exitosa' ? 'selected' : ''; ?>>Exitosa</option>
                                <option value="rechazada" <?php echo $filtro_estado == 'rechazada' ? 'selected' : ''; ?>>Rechazada</option>
                                <option value="parcial" <?php echo $filtro_estado == 'parcial' ? 'selected' : ''; ?>>Parcial</option>
                            </select>
                        </div>
                        <div class="mb-3">
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
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Desde</label>
                                <input type="date" name="fecha_desde" class="form-control" value="<?php echo $filtro_fecha_desde; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Hasta</label>
                                <input type="date" name="fecha_hasta" class="form-control" value="<?php echo $filtro_fecha_hasta; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="entregas.php" class="btn btn-secondary">Limpiar</a>
                        <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Detalle -->
    <div class="modal fade" id="modalDetalle" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle de Entrega</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detalleContent">
                    <div class="text-center"><div class="spinner-border"></div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Foto -->
    <div class="modal fade" id="modalFoto" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Foto de Entrega</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="fotoEntrega" src="" class="img-fluid" alt="Foto de entrega">
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Mapa -->
    <div class="modal fade" id="modalMapa" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubicación de Entrega</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="mapaEntrega" style="height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_MAPS_API_KEY; ?>"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        function verFoto(foto) {
            document.getElementById('fotoEntrega').src = '../uploads/entregas/' + foto;
            new bootstrap.Modal(document.getElementById('modalFoto')).show();
        }

        function verDetalle(id) {
            const modal = new bootstrap.Modal(document.getElementById('modalDetalle'));
            modal.show();
            
            fetch(`entrega_detalle.php?id=${id}`)
                .then(r => r.text())
                .then(html => document.getElementById('detalleContent').innerHTML = html);
        }

        let mapaEntrega, markerEntrega;
        function verMapa(lat, lng) {
            const modal = new bootstrap.Modal(document.getElementById('modalMapa'));
            modal.show();
            
            setTimeout(() => {
                const center = {lat: parseFloat(lat), lng: parseFloat(lng)};
                mapaEntrega = new google.maps.Map(document.getElementById('mapaEntrega'), {
                    center: center,
                    zoom: 16
                });
                markerEntrega = new google.maps.Marker({
                    position: center,
                    map: mapaEntrega,
                    title: 'Ubicación de entrega'
                });
            }, 500);
        }
    </script>
    
    <!-- Chatbot Widget -->
    <?php include 'includes/chatbot_widget.php'; ?>
</body>
</html>
