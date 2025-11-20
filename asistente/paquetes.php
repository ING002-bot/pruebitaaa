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
$stmt->execute($params);
$paquetes = $stmt->fetchAll();
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
    <div class="dashboard-header">
        <button class="btn btn-link" onclick="toggleSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <h2><?php echo APP_NAME; ?></h2>
        <div class="user-profile">
            <img src="../assets/img/<?php echo $_SESSION['foto_perfil']; ?>" alt="Avatar" onerror="this.src='../assets/img/default-avatar.svg'">
            <div class="user-info">
                <span class="user-name"><?php echo $_SESSION['nombre']; ?></span>
                <span class="user-role">Asistente</span>
            </div>
        </div>
    </div>
    
    <div class="dashboard-container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <i class="bi bi-box-seam"></i>
                <h3>HERMES EXPRESS</h3>
            </div>
            <div class="sidebar-menu">
                <a href="dashboard.php" class="menu-item">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="paquetes.php" class="menu-item active">
                    <i class="bi bi-box"></i>
                    <span>Paquetes</span>
                </a>
                <a href="entregas.php" class="menu-item">
                    <i class="bi bi-check-circle"></i>
                    <span>Entregas</span>
                </a>
                <a href="rezagados.php" class="menu-item">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span>Rezagados</span>
                </a>
                <a href="caja_chica.php" class="menu-item">
                    <i class="bi bi-wallet2"></i>
                    <span>Caja Chica</span>
                </a>
                <a href="../auth/logout.php" class="menu-item">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </div>
        </div>
        
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
                                        <button class="btn btn-sm btn-info" onclick="alert('Ver detalle próximamente')">
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
                <form method="POST" action="../admin/paquetes_guardar.php">
                    <div class="modal-body">
                        <p class="text-muted">Formulario idéntico al de admin</p>
                        <div class="mb-3">
                            <label class="form-label">Código de Seguimiento *</label>
                            <input type="text" name="codigo_seguimiento" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Destinatario *</label>
                            <input type="text" name="destinatario_nombre" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dirección *</label>
                            <textarea name="direccion_completa" class="form-control" required></textarea>
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
</body>
</html>
