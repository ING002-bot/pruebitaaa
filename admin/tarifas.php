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
                <h1><i class="bi bi-calculator"></i> 💰 Gestión de Tarifas</h1>
                <p class="text-muted">Administra lo que cobras a clientes y pagas a repartidores por cada zona</p>
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
                                <thead class="table-primary text-white">
                                    <tr>
                                        <th width="20%">📍 Zona de Entrega</th>
                                        <th width="18%" class="text-center">💰 TÚ COBRAS<br><small>(Al Cliente)</small></th>
                                        <th width="18%" class="text-center">💸 TÚ PAGAS<br><small>(Al Repartidor)</small></th>
                                        <th width="18%" class="text-center">📈 TU GANANCIA<br><small>(Por Paquete)</small></th>
                                        <th width="11%" class="text-center">% Margen</th>
                                        <th width="15%" class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tarifas as $tarifa): 
                                        $costo_cliente = $tarifa['costo_cliente'] ?? $tarifa['tarifa_repartidor'] ?? 0;
                                        $ganancia = $costo_cliente - $tarifa['tarifa_repartidor'];
                                        $margen = $costo_cliente > 0 ? (($ganancia / $costo_cliente) * 100) : 0;
                                    ?>
                                        <tr class="align-middle">
                                            <td>
                                                <strong class="text-dark"><?php echo htmlspecialchars($tarifa['nombre_zona']); ?></strong>
                                                <?php if (!$tarifa['activo']): ?>
                                                <span class="badge bg-danger ms-1">Inactiva</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center bg-light-primary">
                                                <div class="d-flex flex-column">
                                                    <span class="fs-4 fw-bold text-success">S/ <?php echo number_format($costo_cliente, 2); ?></span>
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
                                                    <span class="fs-4 fw-bold <?php echo $ganancia >= 5 ? 'text-success' : ($ganancia >= 2.5 ? 'text-warning' : 'text-danger'); ?>">
                                                        S/ <?php echo number_format($ganancia, 2); ?>
                                                    </span>
                                                    <small class="text-muted">Ganas neto</small>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge fs-6 p-2 <?php echo $margen >= 60 ? 'bg-success' : ($margen >= 40 ? 'bg-warning text-dark' : 'bg-danger'); ?>">
                                                    <?php echo number_format($margen, 1); ?>%
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-warning btn-sm fw-bold" 
                                                        onclick="editarTarifa(<?php echo htmlspecialchars(json_encode($tarifa)); ?>)" 
                                                        title="Editar Tarifas">
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
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="tarifa_guardar.php" method="POST">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="bi bi-plus-circle"></i> 🆕 Crear Nueva Zona de Entrega</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">📍 Categoría</label>
                                <select name="categoria" class="form-select" required>
                                    <option value="">Seleccionar categoría...</option>
                                    <option value="URBANO">URBANO</option>
                                    <option value="PUEBLOS">PUEBLOS</option>
                                    <option value="PLAYAS">PLAYAS</option>
                                    <option value="COOPERATIVAS">COOPERATIVAS</option>
                                    <option value="EXCOPERATIVAS">EXCOPERATIVAS</option>
                                    <option value="FERREÑAFE">FERREÑAFE</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">🏘️ Nombre de Zona</label>
                                <input type="text" name="nombre_zona" class="form-control" required 
                                       pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ\s\-]+" 
                                       title="Solo se permiten letras, espacios y guiones"
                                       placeholder="Ej: Chiclayo, Lambayeque, etc.">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white text-center">
                                        <h6 class="mb-0">💰 LO QUE COBRARÁS AL CLIENTE</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text fw-bold">S/</span>
                                            <input type="number" step="0.01" min="0" class="form-control text-center fs-3 fw-bold" 
                                                   id="new_costo_cliente" name="costo_cliente" required 
                                                   placeholder="0.00" oninput="actualizarPreviewNuevo()">
                                        </div>
                                        <small class="text-muted d-block mt-2 text-center">
                                            <i class="bi bi-info-circle"></i> Precio que cobrarás por cada paquete
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card border-danger">
                                    <div class="card-header bg-danger text-white text-center">
                                        <h6 class="mb-0">🚚 LO QUE PAGARÁS AL REPARTIDOR</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text fw-bold">S/</span>
                                            <input type="number" step="0.01" min="0" class="form-control text-center fs-3 fw-bold" 
                                                   id="new_tarifa_repartidor" name="tarifa_repartidor" required 
                                                   placeholder="0.00" oninput="actualizarPreviewNuevo()">
                                        </div>
                                        <small class="text-muted d-block mt-2 text-center">
                                            <i class="bi bi-info-circle"></i> Lo que pagarás por cada entrega
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card border-info">
                            <div class="card-header bg-info text-white text-center">
                                <h6 class="mb-0">📈 VISTA PREVIA DE TU GANANCIA</h6>
                            </div>
                            <div class="card-body text-center" id="preview_content_nuevo">
                                <p class="text-muted fs-5">Ingresa los valores para ver tu ganancia</p>
                            </div>
                        </div>

                        <input type="hidden" name="tipo_envio" value="Paquete">
                        <input type="hidden" name="activo" value="1">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-lg" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-check-circle"></i> Crear Zona
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Tarifas -->
    <div class="modal fade" id="editarZonaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="actualizar_tarifa.php" method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">💰 Editar Tarifas de Zona</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_id">
                        <input type="hidden" name="categoria" id="edit_categoria">
                        <input type="hidden" name="nombre_zona" id="edit_nombre_zona">
                        <input type="hidden" name="activo" value="1">
                        
                        <div class="text-center mb-4">
                            <h6 class="text-muted">EDITANDO ZONA:</h6>
                            <h3 id="edit_zona_display" class="text-primary fw-bold"></h3>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white text-center">
                                        <h6 class="mb-0">💰 LO QUE COBRAS AL CLIENTE</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text fw-bold">S/</span>
                                            <input type="number" step="0.01" min="0" class="form-control text-center fs-3 fw-bold" 
                                                   id="edit_costo_cliente" name="costo_cliente" required 
                                                   placeholder="0.00" oninput="actualizarPreview()">
                                        </div>
                                        <small class="text-muted d-block mt-2 text-center">
                                            <i class="bi bi-info-circle"></i> Lo que ingresas por cada paquete
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card border-danger">
                                    <div class="card-header bg-danger text-white text-center">
                                        <h6 class="mb-0">🚚 LO QUE PAGAS AL REPARTIDOR</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text fw-bold">S/</span>
                                            <input type="number" step="0.01" min="0" class="form-control text-center fs-3 fw-bold" 
                                                   id="edit_tarifa_repartidor" name="tarifa_repartidor" required 
                                                   placeholder="0.00" oninput="actualizarPreview()">
                                        </div>
                                        <small class="text-muted d-block mt-2 text-center">
                                            <i class="bi bi-info-circle"></i> Lo que gastas por cada entrega
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card border-info">
                            <div class="card-header bg-info text-white text-center">
                                <h6 class="mb-0">📈 VISTA PREVIA DE TU GANANCIA</h6>
                            </div>
                            <div class="card-body text-center" id="preview_content">
                                <p class="text-muted fs-5">Ingresa los valores para ver tu ganancia</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-lg" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-check-circle"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/notificaciones.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // Función para editar tarifas con datos pasados directamente
        function editarTarifa(tarifa) {
            console.log('Editando tarifa:', tarifa);
            
            try {
                // Llenar campos ocultos
                document.getElementById('edit_id').value = tarifa.id;
                document.getElementById('edit_categoria').value = tarifa.categoria;
                document.getElementById('edit_nombre_zona').value = tarifa.nombre_zona;
                
                // Mostrar nombre de zona
                document.getElementById('edit_zona_display').textContent = 
                    tarifa.nombre_zona + ' (' + tarifa.categoria + ')';
                
                // Llenar campos editables
                document.getElementById('edit_costo_cliente').value = parseFloat(tarifa.costo_cliente).toFixed(2);
                document.getElementById('edit_tarifa_repartidor').value = parseFloat(tarifa.tarifa_repartidor).toFixed(2);
                
                // Actualizar vista previa
                actualizarPreview();
                
                // Mostrar modal
                const modal = new bootstrap.Modal(document.getElementById('editarZonaModal'));
                modal.show();
                
            } catch (error) {
                console.error('Error al procesar datos:', error);
                alert('Error al cargar los datos de la tarifa');
            }
        }
        
        function actualizarPreview() {
            const costoCliente = parseFloat(document.getElementById('edit_costo_cliente').value) || 0;
            const tarifaRepartidor = parseFloat(document.getElementById('edit_tarifa_repartidor').value) || 0;
            const ganancia = costoCliente - tarifaRepartidor;
            const margen = costoCliente > 0 ? ((ganancia / costoCliente) * 100) : 0;
            
            const previewDiv = document.getElementById('preview_content');
            
            if (costoCliente === 0 && tarifaRepartidor === 0) {
                previewDiv.innerHTML = '<p class="text-muted fs-5">Ingresa los valores para ver tu ganancia</p>';
                return;
            }
            
            let colorClass, mensaje;
            if (ganancia < 1) {
                colorClass = 'text-danger';
                mensaje = '⚠️ Ganancia muy baja';
            } else if (ganancia >= 4) {
                colorClass = 'text-success';
                mensaje = '🎯 ¡Excelente rentabilidad!';
            } else {
                colorClass = 'text-warning';
                mensaje = '💰 Ganancia moderada';
            }
            
            previewDiv.innerHTML = `
                <div class="row text-center mb-3">
                    <div class="col-4">
                        <h3 class="text-success mb-1">S/ ${costoCliente.toFixed(2)}</h3>
                        <small class="text-muted">Cobras al cliente</small>
                    </div>
                    <div class="col-1">
                        <h3 class="text-muted">-</h3>
                    </div>
                    <div class="col-4">
                        <h3 class="text-danger mb-1">S/ ${tarifaRepartidor.toFixed(2)}</h3>
                        <small class="text-muted">Pagas al repartidor</small>
                    </div>
                    <div class="col-1">
                        <h3 class="text-muted">=</h3>
                    </div>
                    <div class="col-2">
                        <h2 class="${colorClass} mb-1 fw-bold">S/ ${ganancia.toFixed(2)}</h2>
                        <small class="text-muted">Tu ganancia</small>
                    </div>
                </div>
                <div class="text-center">
                    <div class="badge bg-secondary fs-6 mb-2">${margen.toFixed(1)}% margen</div>
                    <p class="${colorClass} mb-0"><strong>${mensaje}</strong></p>
                </div>
            `;
        }
        
        // Función para vista previa del modal de nueva zona
        function actualizarPreviewNuevo() {
            const costoCliente = parseFloat(document.getElementById('new_costo_cliente').value) || 0;
            const tarifaRepartidor = parseFloat(document.getElementById('new_tarifa_repartidor').value) || 0;
            const ganancia = costoCliente - tarifaRepartidor;
            const margen = costoCliente > 0 ? ((ganancia / costoCliente) * 100) : 0;
            
            const previewDiv = document.getElementById('preview_content_nuevo');
            
            if (costoCliente === 0 && tarifaRepartidor === 0) {
                previewDiv.innerHTML = '<p class="text-muted fs-5">Ingresa los valores para ver tu ganancia</p>';
                return;
            }
            
            let colorClass, mensaje;
            if (ganancia < 1) {
                colorClass = 'text-danger';
                mensaje = '⚠️ Ganancia muy baja - Revisa los precios';
            } else if (ganancia >= 4) {
                colorClass = 'text-success';
                mensaje = '🎯 ¡Excelente rentabilidad!';
            } else {
                colorClass = 'text-warning';
                mensaje = '💰 Ganancia moderada';
            }
            
            previewDiv.innerHTML = `
                <div class="row text-center mb-3">
                    <div class="col-4">
                        <h3 class="text-success mb-1">S/ ${costoCliente.toFixed(2)}</h3>
                        <small class="text-muted">Cobrarás</small>
                    </div>
                    <div class="col-1">
                        <h3 class="text-muted">-</h3>
                    </div>
                    <div class="col-4">
                        <h3 class="text-danger mb-1">S/ ${tarifaRepartidor.toFixed(2)}</h3>
                        <small class="text-muted">Pagarás</small>
                    </div>
                    <div class="col-1">
                        <h3 class="text-muted">=</h3>
                    </div>
                    <div class="col-2">
                        <h2 class="${colorClass} mb-1 fw-bold">S/ ${ganancia.toFixed(2)}</h2>
                        <small class="text-muted">Tu ganancia</small>
                    </div>
                </div>
                <div class="text-center">
                    <div class="badge bg-secondary fs-6 mb-2">${margen.toFixed(1)}% margen</div>
                    <p class="${colorClass} mb-0"><strong>${mensaje}</strong></p>
                </div>
            `;
        }

    </script>
    
    <!-- Chatbot Widget -->
    <?php include 'includes/chatbot_widget.php'; ?>
</body>
</html>

