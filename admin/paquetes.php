<?php
require_once '../config/config.php';
requireRole('admin');

$db = Database::getInstance()->getConnection();

// Filtros
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$filtro_busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$filtro_repartidor = isset($_GET['repartidor']) ? $_GET['repartidor'] : '';

// Query base
$sql = "SELECT p.*, u.nombre, u.apellido 
        FROM paquetes p 
        LEFT JOIN usuarios u ON p.repartidor_id = u.id 
        WHERE 1=1";
$params = [];

if ($filtro_estado) {
    $sql .= " AND p.estado = ?";
    $params[] = $filtro_estado;
}

if ($filtro_busqueda) {
    $sql .= " AND (p.codigo_seguimiento LIKE ? OR p.destinatario_nombre LIKE ? OR p.direccion_completa LIKE ?)";
    $searchTerm = "%$filtro_busqueda%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($filtro_repartidor) {
    $sql .= " AND p.repartidor_id = ?";
    $params[] = $filtro_repartidor;
}

$sql .= " ORDER BY p.fecha_recepcion DESC LIMIT 100";

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
$paquetes = Database::getInstance()->fetchAll($stmt->get_result());

// Obtener repartidores para filtro
$repartidores = Database::getInstance()->fetchAll($db->query("SELECT id, nombre, apellido FROM usuarios WHERE rol = 'repartidor' AND estado = 'activo' ORDER BY nombre"));

$pageTitle = "Gestión de Paquetes";
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
                <h1>Gestión de Paquetes</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Paquetes</li>
                    </ol>
                </nav>
            </div>
            
            <?php if($flash = getFlashMessage()): ?>
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $flash['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title">Listado de Paquetes</h5>
                    <div>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalNuevoPaquete">
                            <i class="bi bi-plus-circle"></i> Nuevo Paquete
                        </button>
                        <a href="importar.php" class="btn btn-primary">
                            <i class="bi bi-cloud-upload"></i> Importar SAVAR
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filtros -->
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="busqueda" 
                                   placeholder="Buscar por código, destinatario..." 
                                   value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="estado">
                                <option value="">Todos los estados</option>
                                <option value="pendiente" <?php echo $filtro_estado === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="en_ruta" <?php echo $filtro_estado === 'en_ruta' ? 'selected' : ''; ?>>En Ruta</option>
                                <option value="entregado" <?php echo $filtro_estado === 'entregado' ? 'selected' : ''; ?>>Entregado</option>
                                <option value="rezagado" <?php echo $filtro_estado === 'rezagado' ? 'selected' : ''; ?>>Rezagado</option>
                                <option value="devuelto" <?php echo $filtro_estado === 'devuelto' ? 'selected' : ''; ?>>Devuelto</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="repartidor">
                                <option value="">Todos los repartidores</option>
                                <?php foreach($repartidores as $rep): ?>
                                    <option value="<?php echo $rep['id']; ?>" <?php echo $filtro_repartidor == $rep['id'] ? 'selected' : ''; ?>>
                                        <?php echo $rep['nombre'] . ' ' . $rep['apellido']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Filtrar
                            </button>
                        </div>
                    </form>
                    
                    <!-- Tabla -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Destinatario</th>
                                    <th>Dirección</th>
                                    <th>Repartidor</th>
                                    <th>Estado</th>
                                    <th>Fecha Recepción</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($paquetes as $paquete): ?>
                                <tr>
                                    <td><strong><?php echo $paquete['codigo_seguimiento']; ?></strong></td>
                                    <td>
                                        <?php echo $paquete['destinatario_nombre']; ?><br>
                                        <small class="text-muted"><?php echo $paquete['destinatario_telefono']; ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo substr($paquete['direccion_completa'], 0, 50) . '...'; ?></small>
                                    </td>
                                    <td>
                                        <?php if($paquete['nombre']): ?>
                                            <?php echo $paquete['nombre'] . ' ' . $paquete['apellido']; ?>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Sin asignar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $badgeClass = [
                                            'pendiente' => 'bg-secondary',
                                            'en_ruta' => 'bg-warning',
                                            'entregado' => 'bg-success',
                                            'rezagado' => 'bg-danger',
                                            'devuelto' => 'bg-info',
                                            'cancelado' => 'bg-dark'
                                        ];
                                        ?>
                                        <span class="badge <?php echo $badgeClass[$paquete['estado']] ?? 'bg-secondary'; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $paquete['estado'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDateTime($paquete['fecha_recepcion']); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-info" onclick="verDetalle(<?php echo $paquete['id']; ?>)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-warning" onclick="editarPaquete(<?php echo $paquete['id']; ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php if($paquete['estado'] === 'pendiente'): ?>
                                            <button class="btn btn-success" onclick="asignarRepartidor(<?php echo $paquete['id']; ?>)">
                                                <i class="bi bi-person-plus"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Nuevo Paquete -->
    <div class="modal fade" id="modalNuevoPaquete" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registrar Nuevo Paquete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="paquetes_guardar.php" method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Código de Seguimiento *</label>
                                <input type="text" class="form-control" name="codigo_seguimiento" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Código SAVAR</label>
                                <input type="text" class="form-control" name="codigo_savar">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre Destinatario *</label>
                                <input type="text" class="form-control" name="destinatario_nombre" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Teléfono Destinatario *</label>
                                <input type="text" class="form-control" name="destinatario_telefono" required>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Dirección Completa *</label>
                                <textarea class="form-control" name="direccion_completa" rows="2" required></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ciudad</label>
                                <input type="text" class="form-control" name="ciudad">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Provincia</label>
                                <input type="text" class="form-control" name="provincia">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Peso (kg)</label>
                                <input type="number" step="0.01" class="form-control" name="peso">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Valor Declarado</label>
                                <input type="number" step="0.01" class="form-control" name="valor_declarado">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Costo Envío</label>
                                <input type="number" step="0.01" class="form-control" name="costo_envio" value="<?php echo TARIFA_POR_PAQUETE; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Prioridad</label>
                                <select class="form-select" name="prioridad">
                                    <option value="normal">Normal</option>
                                    <option value="urgente">Urgente</option>
                                    <option value="express">Express</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Asignar Repartidor</label>
                                <select class="form-select" name="repartidor_id">
                                    <option value="">Sin asignar</option>
                                    <?php foreach($repartidores as $rep): ?>
                                        <option value="<?php echo $rep['id']; ?>">
                                            <?php echo $rep['nombre'] . ' ' . $rep['apellido']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Notas</label>
                                <textarea class="form-control" name="notas" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Paquete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Ver Detalle -->
    <div class="modal fade" id="modalDetalle" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle del Paquete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detalleContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Paquete -->
    <div class="modal fade" id="modalEditar" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Paquete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="editarContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Asignar Repartidor -->
    <div class="modal fade" id="modalAsignar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Asignar Repartidor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="paquetes_asignar.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="paquete_id" id="asignar_paquete_id">
                        <div class="mb-3">
                            <label class="form-label">Seleccionar Repartidor</label>
                            <select class="form-select" name="repartidor_id" required>
                                <option value="">Seleccione...</option>
                                <?php foreach($repartidores as $rep): ?>
                                    <option value="<?php echo $rep['id']; ?>">
                                        <?php echo $rep['nombre'] . ' ' . $rep['apellido']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Asignar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        function verDetalle(id) {
            const modal = new bootstrap.Modal(document.getElementById('modalDetalle'));
            modal.show();
            
            fetch('paquete_detalle.php?id=' + id)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('detalleContent').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('detalleContent').innerHTML = '<div class="alert alert-danger">Error al cargar los detalles</div>';
                });
        }

        function editarPaquete(id) {
            const modal = new bootstrap.Modal(document.getElementById('modalEditar'));
            modal.show();
            
            fetch('paquete_editar.php?id=' + id)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('editarContent').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('editarContent').innerHTML = '<div class="alert alert-danger">Error al cargar el formulario</div>';
                });
        }

        function asignarRepartidor(id) {
            document.getElementById('asignar_paquete_id').value = id;
            const modal = new bootstrap.Modal(document.getElementById('modalAsignar'));
            modal.show();
        }
    </script>
</body>
</html>
