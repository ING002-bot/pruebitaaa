<?php
require_once '../config/config.php';
requireRole(['asistente']);

$pageTitle = 'Gestión de Paquetes';

// Código idéntico a admin/paquetes.php pero sin acceso a ingresos totales
$db = Database::getInstance()->getConnection();

$filtro_estado = isset($_GET['estado']) ? sanitize($_GET['estado']) : '';
$buscar = isset($_GET['buscar']) ? sanitize($_GET['buscar']) : '';

$sql = "SELECT p.*, u.nombre as repartidor_nombre, u.apellido as repartidor_apellido
        FROM paquetes p
        LEFT JOIN usuarios u ON p.repartidor_id = u.id
        WHERE 1=1";

$params = [];

if ($filtro_estado) {
    $sql .= " AND p.estado = ?";
    $params[] = $filtro_estado;
}

if ($buscar) {
    $sql .= " AND (p.codigo_seguimiento LIKE ? OR p.destinatario_nombre LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
}

$sql .= " ORDER BY p.fecha_recepcion DESC LIMIT 100";

$stmt = $db->prepare($sql);
if (!empty($params)) {
    $types = '';
    foreach ($params as $param) {
        $types .= is_int($param) ? 'i' : 's';
    }
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$paquetes = Database::getInstance()->fetchAll($stmt->get_result());
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
                <h1><i class="bi bi-box"></i> <?php echo $pageTitle; ?></h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevo">
                    <i class="bi bi-plus-circle"></i> Nuevo Paquete
                </button>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" name="buscar" class="form-control" placeholder="Buscar..." value="<?php echo $buscar; ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="estado" class="form-select">
                                <option value="">Todos los estados</option>
                                <option value="pendiente" <?php echo $filtro_estado == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="en_ruta" <?php echo $filtro_estado == 'en_ruta' ? 'selected' : ''; ?>>En Ruta</option>
                                <option value="entregado" <?php echo $filtro_estado == 'entregado' ? 'selected' : ''; ?>>Entregado</option>
                                <option value="rezagado" <?php echo $filtro_estado == 'rezagado' ? 'selected' : ''; ?>>Rezagado</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        </div>
                    </form>
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
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paquetes as $paq): ?>
                                <tr>
                                    <td><strong><?php echo $paq['codigo_seguimiento']; ?></strong></td>
                                    <td><?php echo $paq['destinatario_nombre']; ?></td>
                                    <td class="small"><?php echo substr($paq['direccion_completa'], 0, 50) . '...'; ?></td>
                                    <td><?php echo $paq['repartidor_nombre'] ? $paq['repartidor_nombre'] . ' ' . $paq['repartidor_apellido'] : '-'; ?></td>
                                    <td>
                                        <?php
                                        $badges = ['pendiente' => 'secondary', 'en_ruta' => 'primary', 'entregado' => 'success', 'rezagado' => 'warning'];
                                        ?>
                                        <span class="badge bg-<?php echo $badges[$paq['estado']]; ?>">
                                            <?php echo ucfirst($paq['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="verDetalle(<?php echo $paq['id']; ?>)">
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

    <!-- Modal Nuevo -->
    <div class="modal fade" id="modalNuevo" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Paquete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="paquetes_guardar.php">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Código de Seguimiento</label>
                                <input type="text" name="codigo_seguimiento" class="form-control" placeholder="Auto-generado si se deja vacío">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Código SAVAR</label>
                                <input type="text" name="codigo_savar" class="form-control">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Destinatario *</label>
                                <input type="text" name="destinatario_nombre" class="form-control" required pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+" title="Solo se permiten letras y espacios">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="destinatario_telefono" class="form-control" pattern="[\+]?[0-9\s\-\(\)]+" title="Solo números, espacios, guiones y paréntesis">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="destinatario_email" class="form-control">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Dirección *</label>
                                <textarea name="direccion_completa" class="form-control" rows="2" required></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Prioridad</label>
                                <select name="prioridad" class="form-select">
                                    <option value="normal">Normal</option>
                                    <option value="urgente">Urgente</option>
                                    <option value="express">Express</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Costo de Envío</label>
                                <input type="number" name="costo_envio" class="form-control" step="0.01" min="0" title="Solo se permiten números decimales">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        function verDetalle(id) {
            window.location.href = 'paquete_detalle.php?id=' + id;
        }
    </script>
</body>
</html>
