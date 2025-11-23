<?php
require_once '../config/config.php';
requireRole(['admin']);

$pageTitle = 'Gestión de Gastos';

$db = Database::getInstance()->getConnection();

// Verificar si existe la columna 'descripcion'
$check_column = $db->query("SHOW COLUMNS FROM gastos LIKE 'descripcion'");
$tiene_descripcion = ($check_column && $check_column->num_rows > 0);

// Filtros
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');

if ($tiene_descripcion) {
    // Estructura nueva
    $sql = "SELECT g.*, u.nombre, u.apellido
            FROM gastos g
            LEFT JOIN usuarios u ON g.registrado_por = u.id
            WHERE DATE(g.fecha_gasto) BETWEEN ? AND ?
            ORDER BY g.fecha_gasto DESC";
} else {
    // Estructura antigua - usar 'concepto' como 'descripcion'
    $sql = "SELECT g.*, u.nombre, u.apellido, g.concepto as descripcion, '' as numero_comprobante, '' as comprobante_archivo
            FROM gastos g
            LEFT JOIN usuarios u ON g.registrado_por = u.id
            WHERE DATE(g.fecha_gasto) BETWEEN ? AND ?
            ORDER BY g.fecha_gasto DESC";
}

$stmt = $db->prepare($sql);
$stmt->bind_param("ss", $fecha_desde, $fecha_hasta);
$stmt->execute();
$gastos = Database::getInstance()->fetchAll($stmt);

$total = array_sum(array_column($gastos, 'monto'));
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
                <h1><i class="bi bi-receipt"></i> <?php echo $pageTitle; ?></h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevo">
                    <i class="bi bi-plus-circle"></i> Nuevo Gasto
                </button>
            </div>

            <!-- Filtros -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Desde</label>
                            <input type="date" name="fecha_desde" class="form-control" value="<?php echo $fecha_desde; ?>">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Hasta</label>
                            <input type="date" name="fecha_hasta" class="form-control" value="<?php echo $fecha_hasta; ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Total -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-danger">
                            <i class="bi bi-arrow-down-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo formatCurrency($total); ?></h3>
                            <p>Total Gastos</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Categoría</th>
                                    <th>Descripción</th>
                                    <th>Comprobante</th>
                                    <th>Monto</th>
                                    <th>Registrado Por</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($gastos as $gasto): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($gasto['fecha_gasto'])); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo ucfirst($gasto['categoria']); ?></span></td>
                                    <td><?php echo $gasto['descripcion']; ?></td>
                                    <td><?php echo $gasto['numero_comprobante'] ?: '-'; ?></td>
                                    <td><strong class="text-danger"><?php echo formatCurrency($gasto['monto']); ?></strong></td>
                                    <td><?php echo $gasto['nombre'] . ' ' . $gasto['apellido']; ?></td>
                                    <td>
                                        <?php if ($gasto['comprobante_archivo']): ?>
                                            <a href="../uploads/gastos/<?php echo $gasto['comprobante_archivo']; ?>" target="_blank" class="btn btn-sm btn-info">
                                                <i class="bi bi-file-earmark"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <th colspan="4" class="text-end">TOTAL:</th>
                                    <th colspan="3"><strong class="text-danger"><?php echo formatCurrency($total); ?></strong></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Nuevo Gasto -->
    <div class="modal fade" id="modalNuevo" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registrar Gasto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="gasto_guardar.php" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Fecha *</label>
                            <input type="date" name="fecha_gasto" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Categoría *</label>
                            <select name="categoria" class="form-select" required>
                                <option value="combustible">Combustible</option>
                                <option value="mantenimiento">Mantenimiento</option>
                                <option value="personal">Personal</option>
                                <option value="oficina">Oficina</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción *</label>
                            <textarea name="descripcion" class="form-control" required rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Monto *</label>
                            <input type="number" name="monto" class="form-control" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">N° Comprobante</label>
                            <input type="text" name="numero_comprobante" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Archivo Comprobante</label>
                            <input type="file" name="comprobante" class="form-control" accept="image/*,application/pdf">
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
</body>
</html>
