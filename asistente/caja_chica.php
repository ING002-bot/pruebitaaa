<?php
require_once '../config/config.php';
requireRole('asistente');

$db = Database::getInstance()->getConnection();
$asistente_id = $_SESSION['usuario_id'];

// Obtener saldo actual
$stmt = $db->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN tipo = 'asignacion' THEN monto ELSE 0 END), 0) as total_asignado,
        COALESCE(SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END), 0) as total_gastado,
        COALESCE(SUM(CASE WHEN tipo = 'devolucion' THEN monto ELSE 0 END), 0) as total_devuelto
    FROM caja_chica
    WHERE asignado_a = ?
");
$stmt->bind_param("i", $asistente_id);
$stmt->execute();
$saldoInfo = $stmt->get_result()->fetch_assoc();
$saldo_actual = $saldoInfo['total_asignado'] - $saldoInfo['total_gastado'] - $saldoInfo['total_devuelto'];

// Obtener asignaciones pendientes de gastar
$stmt = $db->prepare("
    SELECT cc.*,
           ua.nombre as admin_nombre, ua.apellido as admin_apellido,
           (
               SELECT COALESCE(SUM(monto), 0) 
               FROM caja_chica 
               WHERE asignacion_padre_id = cc.id AND tipo = 'gasto'
           ) as gastado,
           (cc.monto - (
               SELECT COALESCE(SUM(monto), 0) 
               FROM caja_chica 
               WHERE asignacion_padre_id = cc.id AND tipo = 'gasto'
           )) as disponible
    FROM caja_chica cc
    LEFT JOIN usuarios ua ON cc.asignado_por = ua.id
    WHERE cc.asignado_a = ? AND cc.tipo = 'asignacion'
    HAVING disponible > 0
    ORDER BY cc.fecha_operacion DESC
");
$stmt->bind_param("i", $asistente_id);
$stmt->execute();
$asignaciones = Database::getInstance()->fetchAll($stmt->get_result());

// Obtener historial de gastos
$stmt = $db->prepare("
    SELECT cc.*, 
           ccp.concepto as concepto_asignacion,
           ccp.monto as monto_asignacion
    FROM caja_chica cc
    LEFT JOIN caja_chica ccp ON cc.asignacion_padre_id = ccp.id
    WHERE cc.asignado_a = ? AND cc.tipo IN ('gasto', 'devolucion')
    ORDER BY cc.fecha_operacion DESC
    LIMIT 50
");
$stmt->bind_param("i", $asistente_id);
$stmt->execute();
$historial_gastos = Database::getInstance()->fetchAll($stmt->get_result());

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

            <!-- Saldo Actual -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <h6 class="text-muted">Total Asignado</h6>
                            <h3 class="text-primary">S/ <?php echo number_format($saldoInfo['total_asignado'], 2); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-danger">
                        <div class="card-body text-center">
                            <h6 class="text-muted">Total Gastado</h6>
                            <h3 class="text-danger">S/ <?php echo number_format($saldoInfo['total_gastado'], 2); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-info">
                        <div class="card-body text-center">
                            <h6 class="text-muted">Total Devuelto</h6>
                            <h3 class="text-info">S/ <?php echo number_format($saldoInfo['total_devuelto'], 2); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-success bg-success text-white">
                        <div class="card-body text-center">
                            <h6>Saldo Disponible</h6>
                            <h2>S/ <?php echo number_format($saldo_actual, 2); ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Asignaciones Activas -->
            <?php if (!empty($asignaciones)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-cash-stack"></i> Asignaciones Activas</h5>
                        <span class="badge bg-light text-dark"><?php echo count($asignaciones); ?></span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Concepto</th>
                                        <th>Asignado por</th>
                                        <th>Monto Original</th>
                                        <th>Gastado</th>
                                        <th>Disponible</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($asignaciones as $asig): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($asig['fecha_operacion'])); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($asig['concepto']); ?></strong>
                                                <?php if ($asig['descripcion']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($asig['descripcion']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($asig['admin_nombre'] . ' ' . $asig['admin_apellido']); ?></td>
                                            <td>S/ <?php echo number_format($asig['monto'], 2); ?></td>
                                            <td class="text-danger">S/ <?php echo number_format($asig['gastado'], 2); ?></td>
                                            <td><strong class="text-success">S/ <?php echo number_format($asig['disponible'], 2); ?></strong></td>
                                            <td>
                                                <button class="btn btn-sm btn-danger" onclick="registrarGasto(<?php echo $asig['id']; ?>, <?php echo $asig['disponible']; ?>, '<?php echo htmlspecialchars($asig['concepto'], ENT_QUOTES); ?>')">
                                                    <i class="bi bi-receipt"></i> Registrar Gasto
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> No tienes asignaciones activas con saldo disponible
                </div>
            <?php endif; ?>

            <!-- Historial de Gastos -->
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Historial de Gastos</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($historial_gastos)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                            <p class="text-muted mt-3">No hay gastos registrados</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Asignación</th>
                                        <th>Concepto</th>
                                        <th>Monto</th>
                                        <th>Comprobante</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historial_gastos as $gasto): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i', strtotime($gasto['fecha_operacion'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $gasto['tipo'] == 'gasto' ? 'danger' : 'info'; ?>">
                                                    <?php echo ucfirst($gasto['tipo']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                // Mostrar concepto de asignación solo si existe y no es un número
                                                $concepto_asig = $gasto['concepto_asignacion'] ?? '';
                                                if (!empty($concepto_asig) && !is_numeric($concepto_asig) && $concepto_asig !== '0') {
                                                    echo '<small class="text-muted">' . htmlspecialchars($concepto_asig) . '</small>';
                                                } else {
                                                    echo '<small class="text-muted">-</small>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($gasto['descripcion'])): ?>
                                                    <strong><?php echo htmlspecialchars($gasto['descripcion']); ?></strong>
                                                <?php elseif (!empty($gasto['concepto']) && $gasto['concepto'] !== '0'): ?>
                                                    <strong><?php echo htmlspecialchars($gasto['concepto']); ?></strong>
                                                <?php else: ?>
                                                    <strong>Gasto sin descripción</strong>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-danger"><strong>-S/ <?php echo number_format($gasto['monto'], 2); ?></strong></td>
                                            <td>
                                                <?php if ($gasto['foto_comprobante']): ?>
                                                    <button class="btn btn-sm btn-info" onclick="verComprobante('<?php echo $gasto['foto_comprobante']; ?>')">
                                                        <i class="bi bi-image"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
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

    <!-- Modal Registrar Gasto -->
    <div class="modal fade" id="registrarGastoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="caja_chica_gasto.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="asignacion_id" id="gastoAsignacionId">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="bi bi-receipt"></i> Registrar Gasto</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <strong>Asignación:</strong> <span id="gastoConceptoAsignacion"></span><br>
                            <strong>Disponible:</strong> S/ <span id="gastoDisponible"></span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Monto Gastado (S/) *</label>
                            <input type="number" name="monto" id="gastoMonto" class="form-control" step="0.01" min="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">¿Qué compraste? *</label>
                            <input type="text" name="concepto" class="form-control" placeholder="Ej: Útiles de oficina" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción Detallada</label>
                            <textarea name="descripcion" class="form-control" rows="3" placeholder="Lista de artículos comprados..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Foto del Comprobante</label>
                            <input type="file" name="foto_comprobante" class="form-control" accept="image/jpeg,image/jpg,image/png">
                            <small class="text-muted">Formatos: JPG, PNG. Máx 5MB</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fecha de Compra *</label>
                            <input type="datetime-local" name="fecha_operacion" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Registrar Gasto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Ver Comprobante -->
    <div class="modal fade" id="comprobanteModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Comprobante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="comprobanteImg" src="" class="img-fluid" alt="Comprobante">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <!-- notificaciones.js ya está cargado en header.php -->
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        function registrarGasto(asignacionId, disponible, concepto) {
            // Buscar el modal
            const modalElement = document.getElementById('registrarGastoModal');
            if (!modalElement) {
                console.error('Modal no encontrado');
                return;
            }

            // Cerrar cualquier modal abierto primero
            const existingModal = bootstrap.Modal.getInstance(modalElement);
            if (existingModal) {
                existingModal.hide();
            }

            // Esperar un poco para que se cierre completamente
            setTimeout(function() {
                // Buscar elementos del formulario
                const gastoAsignacionId = document.querySelector('#registrarGastoModal input[name="asignacion_id"]');
                const gastoDisponible = document.querySelector('#registrarGastoModal #gastoDisponible');
                const gastoConceptoAsignacion = document.querySelector('#registrarGastoModal #gastoConceptoAsignacion');
                const gastoMonto = document.querySelector('#registrarGastoModal input[name="monto"]');
                
                // Configurar valores directamente sin validaciones excesivas
                if (gastoAsignacionId) gastoAsignacionId.value = asignacionId;
                if (gastoDisponible) gastoDisponible.textContent = disponible.toFixed(2);
                if (gastoConceptoAsignacion) gastoConceptoAsignacion.textContent = concepto;
                if (gastoMonto) {
                    gastoMonto.max = disponible;
                    gastoMonto.value = '';
                }
                
                // Mostrar el modal sin crear nueva instancia
                const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
                modal.show();
                
            }, 200);
        }

        function verComprobante(foto) {
            document.getElementById('comprobanteImg').src = '../uploads/caja_chica/' + foto;
            const modalElement = document.getElementById('comprobanteModal');
            const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
            modal.show();
        }

        // Limpiar solo el formulario cuando se cierra el modal (NO los elementos informativos)
        document.addEventListener('DOMContentLoaded', function() {
            const gastoModal = document.getElementById('registrarGastoModal');
            if (gastoModal) {
                gastoModal.addEventListener('hidden.bs.modal', function() {
                    // Solo limpiar los campos de entrada del usuario
                    const montoInput = this.querySelector('input[name="monto"]');
                    const conceptoInput = this.querySelector('input[name="concepto"]');
                    const descripcionInput = this.querySelector('textarea[name="descripcion"]');
                    const fotoInput = this.querySelector('input[name="foto_comprobante"]');
                    
                    if (montoInput) montoInput.value = '';
                    if (conceptoInput) conceptoInput.value = '';
                    if (descripcionInput) descripcionInput.value = '';
                    if (fotoInput) fotoInput.value = '';
                    
                    // NO limpiar los spans informativos (gastoDisponible, gastoConceptoAsignacion)
                    // porque los necesitamos para la siguiente apertura
                });
            }
        });
    </script>
</body>
</html>
