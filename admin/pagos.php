<?php
require_once '../config/config.php';
requireRole(['admin']);

$pageTitle = 'Gestión de Pagos';

$db = Database::getInstance()->getConnection();
$pagos = $db->query("SELECT p.*, u.nombre, u.apellido FROM pagos p 
                     LEFT JOIN usuarios u ON p.repartidor_id = u.id 
                     ORDER BY p.fecha_pago DESC LIMIT 100")->fetchAll();
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
                <h1><i class="bi bi-cash-stack"></i> <?php echo $pageTitle; ?></h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevo">
                    <i class="bi bi-plus-circle"></i> Registrar Pago
                </button>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Repartidor</th>
                                    <th>Concepto</th>
                                    <th>Periodo</th>
                                    <th>Método</th>
                                    <th>Monto</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pagos as $pago): ?>
                                <tr>
                                    <td><?php echo formatDate($pago['fecha_pago']); ?></td>
                                    <td><?php echo $pago['nombre'] . ' ' . $pago['apellido']; ?></td>
                                    <td><?php echo $pago['concepto']; ?></td>
                                    <td><?php echo $pago['periodo'] ?: '-'; ?></td>
                                    <td><span class="badge bg-info"><?php echo ucfirst($pago['metodo_pago']); ?></span></td>
                                    <td><strong><?php echo formatCurrency($pago['monto']); ?></strong></td>
                                    <td>
                                        <?php
                                        $badges = ['pendiente' => 'warning', 'pagado' => 'success', 'cancelado' => 'danger'];
                                        ?>
                                        <span class="badge bg-<?php echo $badges[$pago['estado']]; ?>">
                                            <?php echo ucfirst($pago['estado']); ?>
                                        </span>
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
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registrar Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="pago_guardar.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Repartidor *</label>
                            <select name="repartidor_id" class="form-select" required>
                                <option value="">Seleccionar...</option>
                                <?php
                                $reps = $db->query("SELECT id, nombre, apellido FROM usuarios WHERE rol = 'repartidor' AND estado = 'activo'")->fetchAll();
                                foreach ($reps as $rep):
                                ?>
                                    <option value="<?php echo $rep['id']; ?>"><?php echo $rep['nombre'] . ' ' . $rep['apellido']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Concepto *</label>
                            <input type="text" name="concepto" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Periodo</label>
                            <input type="text" name="periodo" class="form-control" placeholder="Ej: Enero 2025">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Monto *</label>
                            <input type="number" name="monto" class="form-control" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Método de Pago *</label>
                            <select name="metodo_pago" class="form-select" required>
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="deposito">Depósito</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fecha *</label>
                            <input type="date" name="fecha_pago" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
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
