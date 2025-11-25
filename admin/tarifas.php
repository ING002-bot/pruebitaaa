<?php
require_once '../config/config.php';
requireRole('admin');

$db = Database::getInstance()->getConnection();

// Obtener todas las tarifas agrupadas por categoría
$stmt = $db->query("
    SELECT * FROM zonas_tarifas 
    ORDER BY 
        FIELD(categoria, 'URBANO', 'PUEBLOS', 'PLAYAS', 'COOPERATIVAS', 'EXCOPERATIVAS', 'FERREÑAFE'),
        nombre_zona ASC
");
$todasTarifas = Database::getInstance()->fetchAll($stmt);

// Agrupar por categoría
$tarifasPorCategoria = [];
foreach ($todasTarifas as $tarifa) {
    $tarifasPorCategoria[$tarifa['categoria']][] = $tarifa;
}

$pageTitle = "Tarifas por Zona";
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
                <h1><i class="bi bi-cash-coin"></i> Tarifas por Zona</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Tarifas</li>
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

            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1">Gestión de Tarifas para Repartidores</h5>
                                    <p class="text-muted mb-0">Configura cuánto gana cada repartidor por paquete entregado según la zona</p>
                                </div>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaZonaModal">
                                    <i class="bi bi-plus-circle"></i> Nueva Zona
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tarifas por Categoría -->
            <?php foreach ($tarifasPorCategoria as $categoria => $tarifas): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($categoria); ?>
                            <span class="badge bg-light text-dark float-end"><?php echo count($tarifas); ?> zonas</span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">ID</th>
                                        <th width="35%">Nombre de Zona</th>
                                        <th width="15%">Tipo Envío</th>
                                        <th width="15%">Tarifa Repartidor</th>
                                        <th width="10%">Estado</th>
                                        <th width="15%">Última Actualización</th>
                                        <th width="5%">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tarifas as $tarifa): ?>
                                        <tr>
                                            <td><?php echo $tarifa['id']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($tarifa['nombre_zona']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($tarifa['tipo_envio']); ?></td>
                                            <td>
                                                <span class="badge bg-success fs-6">
                                                    S/ <?php echo number_format($tarifa['tarifa_repartidor'], 2); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($tarifa['activo']): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y H:i', strtotime($tarifa['fecha_actualizacion'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" onclick="editarTarifa(<?php echo htmlspecialchars(json_encode($tarifa)); ?>)">
                                                    <i class="bi bi-pencil"></i>
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

            <?php if (empty($tarifasPorCategoria)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                        <p class="text-muted mt-3">No hay tarifas configuradas</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaZonaModal">
                            <i class="bi bi-plus-circle"></i> Crear Primera Zona
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Nueva Zona -->
    <div class="modal fade" id="nuevaZonaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="tarifa_guardar.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Nueva Zona</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Categoría</label>
                            <select name="categoria" class="form-select" required>
                                <option value="">Seleccionar...</option>
                                <option value="URBANO">URBANO</option>
                                <option value="PUEBLOS">PUEBLOS</option>
                                <option value="PLAYAS">PLAYAS</option>
                                <option value="COOPERATIVAS">COOPERATIVAS</option>
                                <option value="EXCOPERATIVAS">EXCOPERATIVAS</option>
                                <option value="FERREÑAFE">FERREÑAFE</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre de Zona</label>
                            <input type="text" name="nombre_zona" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo de Envío</label>
                            <input type="text" name="tipo_envio" class="form-control" value="Paquete" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tarifa para Repartidor (S/)</label>
                            <input type="number" name="tarifa_repartidor" class="form-control" step="0.01" min="0" required>
                            <small class="text-muted">Monto que recibirá el repartidor por cada paquete entregado</small>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="activo" class="form-check-input" id="activoCheck" value="1" checked>
                                <label class="form-check-label" for="activoCheck">Zona activa</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Zona</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Zona -->
    <div class="modal fade" id="editarZonaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="tarifa_actualizar.php" method="POST">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Zona</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Categoría</label>
                            <select name="categoria" id="edit_categoria" class="form-select" required>
                                <option value="URBANO">URBANO</option>
                                <option value="PUEBLOS">PUEBLOS</option>
                                <option value="PLAYAS">PLAYAS</option>
                                <option value="COOPERATIVAS">COOPERATIVAS</option>
                                <option value="EXCOPERATIVAS">EXCOPERATIVAS</option>
                                <option value="FERREÑAFE">FERREÑAFE</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre de Zona</label>
                            <input type="text" name="nombre_zona" id="edit_nombre_zona" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo de Envío</label>
                            <input type="text" name="tipo_envio" id="edit_tipo_envio" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tarifa para Repartidor (S/)</label>
                            <input type="number" name="tarifa_repartidor" id="edit_tarifa_repartidor" class="form-control" step="0.01" min="0" required>
                            <small class="text-muted">Monto que recibirá el repartidor por cada paquete entregado</small>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="activo" class="form-check-input" id="edit_activo" value="1">
                                <label class="form-check-label" for="edit_activo">Zona activa</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Actualizar Zona</button>
                    </div>
                </form>
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

        function editarTarifa(tarifa) {
            document.getElementById('edit_id').value = tarifa.id;
            document.getElementById('edit_categoria').value = tarifa.categoria;
            document.getElementById('edit_nombre_zona').value = tarifa.nombre_zona;
            document.getElementById('edit_tipo_envio').value = tarifa.tipo_envio;
            document.getElementById('edit_tarifa_repartidor').value = tarifa.tarifa_repartidor;
            document.getElementById('edit_activo').checked = tarifa.activo == 1;
            
            const modal = new bootstrap.Modal(document.getElementById('editarZonaModal'));
            modal.show();
        }
    </script>
    
    <!-- Chatbot Widget -->
    <?php include 'includes/chatbot_widget.php'; ?>
</body>
</html>

