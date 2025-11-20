<?php
require_once '../config/config.php';
requireRole('admin');

$db = Database::getInstance()->getConnection();

// Obtener asistentes
$stmt = $db->query("SELECT id, nombre, apellido FROM usuarios WHERE rol = 'asistente' AND estado = 'activo'");
$asistentes = $stmt->fetchAll();

// Obtener saldos
$stmt = $db->query("SELECT * FROM saldo_caja_chica ORDER BY apellido, nombre");
$saldos = $stmt->fetchAll();

// Obtener historial completo
$stmt = $db->query("
    SELECT cc.*, 
           ua.nombre as admin_nombre, ua.apellido as admin_apellido,
           uas.nombre as asistente_nombre, uas.apellido as asistente_apellido,
           ur.nombre as registro_nombre, ur.apellido as registro_apellido
    FROM caja_chica cc
    LEFT JOIN usuarios ua ON cc.asignado_por = ua.id
    LEFT JOIN usuarios uas ON cc.asignado_a = uas.id
    INNER JOIN usuarios ur ON cc.registrado_por = ur.id
    ORDER BY cc.fecha_operacion DESC, cc.id DESC
    LIMIT 100
");
$historial = $stmt->fetchAll();

$pageTitle = "Caja Chica";
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
                <h1><i class="bi bi-wallet2"></i> Caja Chica</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Caja Chica</li>
                    </ol>
                </nav>
            </div>

            <?php if (isset($_SESSION['flash_message'])): 
                $flash = $_SESSION['flash_message'];
                unset($_SESSION['flash_message']);
            ?>
                <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($flash['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Botón Nueva Asignación -->
            <div class="row mb-4">
                <div class="col-12">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaAsignacionModal">
                        <i class="bi bi-cash-coin"></i> Nueva Asignación
                    </button>
                </div>
            </div>

            <!-- Saldos por Asistente -->
            <div class="row mb-4">
                <?php foreach ($saldos as $saldo): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card border-<?php echo $saldo['saldo_actual'] > 0 ? 'success' : 'secondary'; ?>">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-person-badge"></i>
                                    <?php echo htmlspecialchars($saldo['nombre'] . ' ' . $saldo['apellido']); ?>
                                </h5>
                                <hr>
                                <div class="row text-center">
                                    <div class="col-6">
                                        <small class="text-muted">Total Asignado</small>
                                        <h6 class="text-primary">S/ <?php echo number_format($saldo['total_asignado'], 2); ?></h6>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Total Gastado</small>
                                        <h6 class="text-danger">S/ <?php echo number_format($saldo['total_gastado'], 2); ?></h6>
                                    </div>
                                </div>
                                <hr>
                                <div class="text-center">
                                    <small class="text-muted">Saldo Disponible</small>
                                    <h4 class="text-success mb-0">S/ <?php echo number_format($saldo['saldo_actual'], 2); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($saldos)): ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> No hay asignaciones registradas aún
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Historial -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Historial de Movimientos</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($historial)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                            <p class="text-muted mt-3">No hay movimientos registrados</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Asistente</th>
                                        <th>Concepto</th>
                                        <th>Monto</th>
                                        <th>Registrado por</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historial as $mov): 
                                        $badgeTipo = [
                                            'asignacion' => 'success',
                                            'gasto' => 'danger',
                                            'devolucion' => 'info'
                                        ][$mov['tipo']] ?? 'secondary';
                                        
                                        $iconoTipo = [
                                            'asignacion' => 'bi-arrow-down-circle',
                                            'gasto' => 'bi-arrow-up-circle',
                                            'devolucion' => 'bi-arrow-return-left'
                                        ][$mov['tipo']] ?? 'bi-circle';
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo date('d/m/Y', strtotime($mov['fecha_operacion'])); ?></strong>
                                                <br><small class="text-muted"><?php echo date('H:i', strtotime($mov['fecha_operacion'])); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $badgeTipo; ?>">
                                                    <i class="bi <?php echo $iconoTipo; ?>"></i>
                                                    <?php echo ucfirst($mov['tipo']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($mov['asistente_nombre'] . ' ' . $mov['asistente_apellido']); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($mov['concepto']); ?></strong>
                                                <?php if ($mov['descripcion']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($mov['descripcion'], 0, 50)); ?><?php echo strlen($mov['descripcion']) > 50 ? '...' : ''; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong class="text-<?php echo $mov['tipo'] == 'asignacion' ? 'success' : 'danger'; ?>">
                                                    <?php echo $mov['tipo'] == 'asignacion' ? '+' : '-'; ?>S/ <?php echo number_format($mov['monto'], 2); ?>
                                                </strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($mov['registro_nombre'] . ' ' . $mov['registro_apellido']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick="verDetalle(<?php echo htmlspecialchars(json_encode($mov)); ?>)">
                                                    <i class="bi bi-eye"></i>
                                                </button>
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

    <!-- Modal Nueva Asignación -->
    <div class="modal fade" id="nuevaAsignacionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="caja_chica_asignar.php" method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="bi bi-cash-coin"></i> Nueva Asignación</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Asistente *</label>
                            <select name="asignado_a" class="form-select" required>
                                <option value="">Seleccionar asistente...</option>
                                <?php foreach ($asistentes as $asistente): ?>
                                    <option value="<?php echo $asistente['id']; ?>">
                                        <?php echo htmlspecialchars($asistente['nombre'] . ' ' . $asistente['apellido']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Monto (S/) *</label>
                            <input type="number" name="monto" class="form-control" step="0.01" min="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Concepto *</label>
                            <input type="text" name="concepto" class="form-control" placeholder="Ej: Compras de oficina" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="3" placeholder="Detalles adicionales..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fecha de Operación *</label>
                            <input type="datetime-local" name="fecha_operacion" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Asignar Dinero</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Detalle -->
    <div class="modal fade" id="detalleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle del Movimiento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detalleContent">
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

        function verDetalle(mov) {
            const badgeTipo = {
                'asignacion': 'success',
                'gasto': 'danger',
                'devolucion': 'info'
            }[mov.tipo] || 'secondary';

            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="bi bi-info-circle"></i> Información General</h6>
                        <table class="table table-sm table-bordered">
                            <tr><th>Tipo:</th><td><span class="badge bg-${badgeTipo}">${mov.tipo.toUpperCase()}</span></td></tr>
                            <tr><th>Fecha:</th><td>${new Date(mov.fecha_operacion).toLocaleString('es-ES')}</td></tr>
                            <tr><th>Monto:</th><td><strong class="text-${mov.tipo === 'asignacion' ? 'success' : 'danger'}">S/ ${parseFloat(mov.monto).toFixed(2)}</strong></td></tr>
                            <tr><th>Concepto:</th><td>${mov.concepto}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-people"></i> Involucrados</h6>
                        <table class="table table-sm table-bordered">
                            <tr><th>Asistente:</th><td>${mov.asistente_nombre} ${mov.asistente_apellido}</td></tr>
                            <tr><th>Registrado por:</th><td>${mov.registro_nombre} ${mov.registro_apellido}</td></tr>
                            ${mov.admin_nombre ? `<tr><th>Asignado por:</th><td>${mov.admin_nombre} ${mov.admin_apellido}</td></tr>` : ''}
                        </table>
                    </div>
                </div>
            `;

            if (mov.descripcion) {
                html += `
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6><i class="bi bi-chat-left-text"></i> Descripción</h6>
                            <p class="border p-3 rounded">${mov.descripcion}</p>
                        </div>
                    </div>
                `;
            }

            if (mov.foto_comprobante) {
                html += `
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6><i class="bi bi-image"></i> Comprobante</h6>
                            <img src="../uploads/caja_chica/${mov.foto_comprobante}" class="img-fluid rounded" alt="Comprobante">
                        </div>
                    </div>
                `;
            }

            document.getElementById('detalleContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('detalleModal')).show();
        }
    </script>
</body>
</html>
