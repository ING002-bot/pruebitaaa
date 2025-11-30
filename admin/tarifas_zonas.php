<?php
require_once '../config/config.php';
require_once '../config/tarifas_helper.php';

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit();
}

// Verificar si es administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: dashboard.php");
    exit();
}

$mensaje = '';
$tipoMensaje = '';

// Procesar actualizaciones
if ($_POST && isset($_POST['actualizar_tarifa'])) {
    $zona_id = (int)$_POST['zona_id'];
    $costo_cliente = (float)$_POST['costo_cliente'];
    $tarifa_repartidor = (float)$_POST['tarifa_repartidor'];
    
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            UPDATE zonas_tarifas 
            SET costo_cliente = ?, tarifa_repartidor = ?, fecha_actualizacion = NOW() 
            WHERE id = ?
        ");
        $stmt->bind_param("ddi", $costo_cliente, $tarifa_repartidor, $zona_id);
        
        if ($stmt->execute()) {
            $mensaje = "Tarifa actualizada correctamente";
            $tipoMensaje = "success";
        } else {
            $mensaje = "Error al actualizar tarifa: " . $stmt->error;
            $tipoMensaje = "danger";
        }
        $stmt->close();
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipoMensaje = "danger";
    }
}

// Obtener todas las tarifas
$db = Database::getInstance()->getConnection();
$tarifas = $db->query("
    SELECT 
        id,
        nombre_zona,
        categoria,
        costo_cliente,
        tarifa_repartidor,
        (costo_cliente - tarifa_repartidor) as ganancia,
        ROUND(((costo_cliente - tarifa_repartidor) / costo_cliente) * 100, 1) as margen_porcentaje,
        activo,
        fecha_creacion,
        fecha_actualizacion
    FROM zonas_tarifas 
    ORDER BY categoria, nombre_zona
")->fetch_all(MYSQLI_ASSOC);

// Agrupar por categoría
$tarifas_por_categoria = [];
foreach ($tarifas as $tarifa) {
    $tarifas_por_categoria[$tarifa['categoria']][] = $tarifa;
}

$pageTitle = "Administrar Tarifas por Zonas";
include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">💰 Gestión de Tarifas</h1>
                    <p class="text-muted">Administra lo que cobras a clientes y pagas a repartidores por cada zona</p>
                </div>
            </div>
        </div>
    </div>

    <?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show" role="alert">
        <?php echo $mensaje; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Resumen Simple -->
    <div class="alert alert-info mb-4">
        <div class="row text-center">
            <div class="col-md-4">
                <strong><?php echo count($tarifas); ?> Zonas Configuradas</strong>
            </div>
            <div class="col-md-4">
                <?php
                $ganancia_promedio = count($tarifas) > 0 ? array_sum(array_column($tarifas, 'ganancia')) / count($tarifas) : 0;
                ?>
                <strong>Ganancia Promedio: S/ <?php echo number_format($ganancia_promedio, 2); ?></strong>
            </div>
            <div class="col-md-4">
                <em>💡 Haz clic en ✏️ para editar cualquier tarifa</em>
            </div>
        </div>
    </div>

    <!-- Tarifas por Categoría -->
    <?php foreach ($tarifas_por_categoria as $categoria => $zonas_categoria): ?>
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                📍 <?php echo $categoria; ?> 
                <span class="badge bg-secondary"><?php echo count($zonas_categoria); ?> zonas</span>
                <span class="badge bg-success">
                    Ganancia promedio: S/ <?php echo number_format(array_sum(array_column($zonas_categoria, 'ganancia')) / count($zonas_categoria), 2); ?>
                </span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-primary">
                        <tr>
                            <th width="20%">📍 Zona de Entrega</th>
                            <th width="18%" class="text-center">💰 TÚ COBRAS<br><small class="fw-normal">(Al Cliente)</small></th>
                            <th width="18%" class="text-center">💸 TÚ PAGAS<br><small class="fw-normal">(Al Repartidor)</small></th>
                            <th width="18%" class="text-center">📈 TU GANANCIA<br><small class="fw-normal">(Por Paquete)</small></th>
                            <th width="11%" class="text-center">% Margen</th>
                            <th width="15%" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($zonas_categoria as $tarifa): ?>
                        <tr id="fila-<?php echo $tarifa['id']; ?>" class="align-middle">
                            <td>
                                <strong class="text-dark"><?php echo htmlspecialchars($tarifa['nombre_zona']); ?></strong>
                                <?php if (!$tarifa['activo']): ?>
                                <span class="badge bg-danger ms-1">Inactiva</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center bg-light-primary">
                                <div class="d-flex flex-column">
                                    <span class="fs-4 fw-bold text-success">S/ <?php echo number_format($tarifa['costo_cliente'], 2); ?></span>
                                    <small class="text-muted">Ingresas por paquete</small>
                                </div>
                            </td>
                            <td class="text-center bg-light-danger">
                                <div class="d-flex flex-column">
                                    <span class="fs-4 fw-bold text-danger">S/ <?php echo number_format($tarifa['tarifa_repartidor'], 2); ?></span>
                                    <small class="text-muted">Gastas por paquete</small>
                                </div>
                            </td>
                            <td class="text-center bg-light-success">
                                <div class="d-flex flex-column">
                                    <span class="fs-4 fw-bold <?php echo $tarifa['ganancia'] >= 5 ? 'text-success' : ($tarifa['ganancia'] >= 2.5 ? 'text-warning' : 'text-danger'); ?>">
                                        S/ <?php echo number_format($tarifa['ganancia'], 2); ?>
                                    </span>
                                    <small class="text-muted">Ganas neto</small>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge fs-6 p-2 <?php echo $tarifa['margen_porcentaje'] >= 60 ? 'bg-success' : ($tarifa['margen_porcentaje'] >= 40 ? 'bg-warning text-dark' : 'bg-danger'); ?>">
                                    <?php echo $tarifa['margen_porcentaje']; ?>%
                                </span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-warning btn-sm fw-bold" onclick="editarTarifa(<?php echo $tarifa['id']; ?>)" title="Editar Tarifas">
                                    <i class="bi bi-pencil-fill"></i> EDITAR
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Estilos personalizados para destacar las columnas -->
<style>
.bg-light-primary { background-color: #e7f3ff !important; border-left: 3px solid #0d6efd; }
.bg-light-danger { background-color: #ffe7e7 !important; border-left: 3px solid #dc3545; }
.bg-light-success { background-color: #e7ffe7 !important; border-left: 3px solid #198754; }
.table-primary th { 
    background-color: #0d6efd !important; 
    color: white !important; 
    font-weight: bold;
    text-align: center;
}
</style>

<!-- Modal Editar Tarifa -->
<div class="modal fade" id="modalEditarTarifa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">💰 Editar Tarifas de Zona</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="zona_id" id="edit_zona_id">
                    
                    <div class="text-center mb-4">
                        <h6 class="text-muted">ZONA DE ENTREGA:</h6>
                        <h4 id="edit_nombre_zona" class="text-primary"></h4>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white text-center">
                                    <h6 class="mb-0">💰 LO QUE COBRAS</h6>
                                </div>
                                <div class="card-body">
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text">S/</span>
                                        <input type="number" step="0.01" min="0" class="form-control text-center fs-4" id="edit_costo_cliente" name="costo_cliente" required>
                                    </div>
                                    <small class="text-muted d-block mt-1 text-center">Precio al cliente por paquete</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card border-danger">
                                <div class="card-header bg-danger text-white text-center">
                                    <h6 class="mb-0">🚚 LO QUE PAGAS</h6>
                                </div>
                                <div class="card-body">
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text">S/</span>
                                        <input type="number" step="0.01" min="0" class="form-control text-center fs-4" id="edit_tarifa_repartidor" name="tarifa_repartidor" required>
                                    </div>
                                    <small class="text-muted d-block mt-1 text-center">Pago al repartidor por paquete</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-success" id="preview_ganancia">
                        <div class="card-header bg-success text-white text-center">
                            <h6 class="mb-0">📈 TU GANANCIA NETA</h6>
                        </div>
                        <div class="card-body text-center" id="preview_content">
                            <p class="text-muted">Ajusta los valores arriba para calcular tu ganancia</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="actualizar_tarifa" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Actualizar Tarifa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Datos de tarifas para JavaScript
const tarifasData = <?php echo json_encode($tarifas); ?>;

function editarTarifa(zonaId) {
    const tarifa = tarifasData.find(t => t.id == zonaId);
    if (!tarifa) return;
    
    // Llenar el modal con los datos actuales
    document.getElementById('edit_zona_id').value = tarifa.id;
    document.getElementById('edit_nombre_zona').textContent = tarifa.nombre_zona + ' (' + tarifa.categoria + ')';
    document.getElementById('edit_costo_cliente').value = tarifa.costo_cliente;
    document.getElementById('edit_tarifa_repartidor').value = tarifa.tarifa_repartidor;
    
    // Mostrar vista previa inicial
    actualizarPreview();
    
    // Mostrar modal
    new bootstrap.Modal(document.getElementById('modalEditarTarifa')).show();
}

function actualizarPreview() {
    const costoCliente = parseFloat(document.getElementById('edit_costo_cliente').value) || 0;
    const tarifaRepartidor = parseFloat(document.getElementById('edit_tarifa_repartidor').value) || 0;
    const ganancia = costoCliente - tarifaRepartidor;
    const margen = costoCliente > 0 ? ((ganancia / costoCliente) * 100) : 0;
    
    const previewDiv = document.getElementById('preview_content');
    
    if (costoCliente === 0 && tarifaRepartidor === 0) {
        previewDiv.innerHTML = '<p class="text-muted mb-0">Ajusta los valores arriba para calcular tu ganancia</p>';
        return;
    }
    
    const colorClass = ganancia >= 4 ? 'success' : (ganancia >= 2 ? 'warning' : 'danger');
    const colorText = ganancia >= 4 ? 'text-success' : (ganancia >= 2 ? 'text-warning' : 'text-danger');
    
    previewDiv.innerHTML = `
        <div class="row align-items-center">
            <div class="col-5 text-center">
                <h2 class="text-primary mb-0">S/ ${costoCliente.toFixed(2)}</h2>
                <small class="text-muted">Cobras</small>
            </div>
            <div class="col-2 text-center">
                <h3 class="text-muted mb-0">-</h3>
            </div>
            <div class="col-5 text-center">
                <h2 class="text-danger mb-0">S/ ${tarifaRepartidor.toFixed(2)}</h2>
                <small class="text-muted">Pagas</small>
            </div>
        </div>
        <hr class="my-3">
        <div class="text-center">
            <h1 class="${colorText} mb-1">S/ ${ganancia.toFixed(2)}</h1>
            <p class="mb-2"><strong>${margen.toFixed(1)}% de margen de ganancia</strong></p>
            ${ganancia < 1 ? 
                '<div class="alert alert-danger py-2 mb-0"><small>⚠️ Ganancia muy baja - Revisa los precios</small></div>' : 
                ganancia >= 4 ? 
                '<div class="alert alert-success py-2 mb-0"><small>🎯 ¡Excelente rentabilidad por paquete!</small></div>' :
                '<div class="alert alert-warning py-2 mb-0"><small>💰 Ganancia moderada - Considera optimizar</small></div>'
            }
        </div>
    `;
}

// Eventos para actualizar vista previa en tiempo real
document.getElementById('edit_costo_cliente').addEventListener('input', actualizarPreview);
document.getElementById('edit_tarifa_repartidor').addEventListener('input', actualizarPreview);
</script>

<?php include 'includes/footer.php'; ?>