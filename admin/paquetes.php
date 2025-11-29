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
                    
                    <!-- Botón de Asignación Masiva -->
                    <div class="mb-3" id="asignacion-masiva-container">
                        <button type="button" class="btn btn-success" id="btnAsignarMultiple" style="display: none;" onclick="asignarMultiples()">
                            <i class="bi bi-person-plus"></i> Asignar <span id="countSelected">0</span> paquete(s) seleccionado(s)
                        </button>
                    </div>
                    
                    <!-- Tabla -->
                    <div class="table-responsive" style="max-height: 600px; overflow-x: auto; overflow-y: auto;">
                        <table class="table table-hover" style="min-width: 1200px;">
                            <thead class="sticky-top bg-white">
                                <tr>
                                    <th style="width: 50px;">
                                        <input type="checkbox" id="selectAll" class="form-check-input" onclick="toggleSelectAll()">
                                    </th>
                                    <th style="min-width: 150px;">Código</th>
                                    <th style="min-width: 200px;">Destinatario</th>
                                    <th style="min-width: 250px;">Dirección</th>
                                    <th style="min-width: 150px;">Repartidor</th>
                                    <th style="min-width: 120px;">Estado</th>
                                    <th style="min-width: 150px;">Fecha Recepción</th>
                                    <th style="min-width: 150px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($paquetes as $paquete): ?>
                                <tr>
                                    <td>
                                        <?php if($paquete['estado'] === 'pendiente'): ?>
                                        <input type="checkbox" class="form-check-input paquete-checkbox" 
                                               value="<?php echo $paquete['id']; ?>" 
                                               onchange="updateSelection()">
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo $paquete['codigo_seguimiento']; ?></strong></td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($paquete['destinatario_nombre']); ?>">
                                            <?php echo $paquete['destinatario_nombre']; ?>
                                        </div>
                                        <small class="text-muted"><?php echo $paquete['destinatario_telefono']; ?></small>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 250px;" title="<?php echo htmlspecialchars($paquete['direccion_completa']); ?>">
                                            <?php echo $paquete['direccion_completa']; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($paquete['nombre']): ?>
                                            <div class="text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($paquete['nombre'] . ' ' . $paquete['apellido']); ?>">
                                                <?php echo $paquete['nombre'] . ' ' . $paquete['apellido']; ?>
                                            </div>
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
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Departamento *</label>
                                <input type="text" class="form-control" name="departamento" value="Lambayeque" readonly>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Provincia *</label>
                                <select class="form-select" name="provincia" required>
                                    <option value="">Seleccione provincia</option>
                                    <option value="Chiclayo">Chiclayo</option>
                                    <option value="Ferreñafe">Ferreñafe</option>
                                    <option value="Lambayeque">Lambayeque</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Distrito *</label>
                                <select class="form-select" name="distrito" required>
                                    <option value="">Seleccione distrito</option>
                                    <optgroup label="CHICLAYO">
                                        <option value="Chiclayo">Chiclayo</option>
                                        <option value="Leonardo Ortiz">Leonardo Ortiz</option>
                                        <option value="La Victoria">La Victoria</option>
                                        <option value="Santa Victoria">Santa Victoria</option>
                                    </optgroup>
                                    <optgroup label="PUEBLOS">
                                        <option value="Lambayeque">Lambayeque</option>
                                        <option value="Mochumi">Mochumi</option>
                                        <option value="Tucume">Tucume</option>
                                        <option value="Illimo">Illimo</option>
                                        <option value="Nuevos Aires">Nuevos Aires</option>
                                        <option value="Jayanca">Jayanca</option>
                                        <option value="Pacora">Pacora</option>
                                        <option value="Morrope">Morrope</option>
                                        <option value="Olmos">Olmos</option>
                                        <option value="Salas">Salas</option>
                                    </optgroup>
                                    <optgroup label="PLAYAS">
                                        <option value="San Jose">San Jose</option>
                                        <option value="Santa Rosa">Santa Rosa</option>
                                        <option value="Pimentel">Pimentel</option>
                                        <option value="Reque">Reque</option>
                                        <option value="Monsefu">Monsefu</option>
                                        <option value="Eten">Eten</option>
                                        <option value="Puerto Eten">Puerto Eten</option>
                                    </optgroup>
                                    <optgroup label="COOPERATIVAS">
                                        <option value="Pomalca">Pomalca</option>
                                        <option value="Patapo">Patapo</option>
                                        <option value="Tumán">Tumán</option>
                                        <option value="Pucala">Pucala</option>
                                        <option value="Saltur">Saltur</option>
                                    </optgroup>
                                    <optgroup label="EXCOPERATIVAS">
                                        <option value="Chongoyape">Chongoyape</option>
                                        <option value="Ucupe">Ucupe</option>
                                        <option value="Mocupe">Mocupe</option>
                                        <option value="Zaña">Zaña</option>
                                        <option value="Lagunas">Lagunas</option>
                                        <option value="Picsi">Picsi</option>
                                        <option value="Cayalti">Cayalti</option>
                                        <option value="Oyotun">Oyotun</option>
                                    </optgroup>
                                    <optgroup label="FERREÑAFE">
                                        <option value="Ferreñafe">Ferreñafe</option>
                                        <option value="Motupillo">Motupillo</option>
                                        <option value="Pueblo Nuevo">Pueblo Nuevo</option>
                                    </optgroup>
                                </select>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Dirección Completa *</label>
                                <textarea class="form-control" name="direccion_completa" rows="2" required></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Asignar Repartidor</label>
                                <div class="autocomplete-wrapper" style="position: relative;">
                                    <input type="text" 
                                           class="form-control autocomplete-repartidor" 
                                           id="repartidor_nuevo_search" 
                                           placeholder="Escriba el nombre del repartidor..."
                                           autocomplete="off">
                                    <input type="hidden" name="repartidor_id" id="repartidor_nuevo_id" value="">
                                    <div class="autocomplete-suggestions" id="suggestions_nuevo" style="display: none;"></div>
                                </div>
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
                            <div class="autocomplete-wrapper" style="position: relative;">
                                <input type="text" 
                                       class="form-control autocomplete-repartidor" 
                                       id="repartidor_asignar_search" 
                                       placeholder="Escriba el nombre del repartidor..."
                                       autocomplete="off"
                                       required>
                                <input type="hidden" name="repartidor_id" id="repartidor_asignar_id" value="">
                                <div class="autocomplete-suggestions" id="suggestions_asignar" style="display: none;"></div>
                            </div>
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

    <!-- Modal Asignar M\u00faltiples Paquetes -->
    <div class="modal fade" id="modalAsignarMultiple" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Asignar M\u00faltiples Paquetes a Repartidor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="paquetes_asignar_multiple.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="paquetes_ids" id="asignar_paquetes_ids">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Se asignar\u00e1n <strong><span id="count_paquetes_modal">0</span></strong> paquete(s) al repartidor seleccionado.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Seleccionar Repartidor</label>
                            <div class="autocomplete-wrapper" style="position: relative;">
                                <input type="text" 
                                       class="form-control autocomplete-repartidor" 
                                       id="repartidor_multiple_search" 
                                       placeholder="Escriba el nombre del repartidor..."
                                       autocomplete="off"
                                       required>
                                <input type="hidden" name="repartidor_id" id="repartidor_multiple_id" value="">
                                <div class="autocomplete-suggestions" id="suggestions_multiple" style="display: none;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Asignar Paquetes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        // Datos de repartidores
        const repartidores = [
            <?php foreach($repartidores as $rep): ?>
            {
                id: '<?php echo $rep["id"]; ?>',
                nombre: '<?php echo addslashes($rep["nombre"] . " " . $rep["apellido"]); ?>'
            },
            <?php endforeach; ?>
        ];

        // Función para crear autocompletado
        function setupAutocomplete(searchId, hiddenId, suggestionsId) {
            const searchInput = document.getElementById(searchId);
            const hiddenInput = document.getElementById(hiddenId);
            const suggestionsDiv = document.getElementById(suggestionsId);
            
            if (!searchInput) return;

            searchInput.addEventListener('input', function() {
                const value = this.value.toLowerCase().trim();
                hiddenInput.value = '';
                
                if (value.length === 0) {
                    suggestionsDiv.style.display = 'none';
                    suggestionsDiv.innerHTML = '';
                    return;
                }

                // Filtrar repartidores
                const filtered = repartidores.filter(rep => 
                    rep.nombre.toLowerCase().includes(value)
                );

                if (filtered.length === 0) {
                    suggestionsDiv.style.display = 'none';
                    suggestionsDiv.innerHTML = '';
                    return;
                }

                // Mostrar sugerencias
                suggestionsDiv.innerHTML = filtered.map(rep => 
                    `<div class="autocomplete-item" data-id="${rep.id}" data-nombre="${rep.nombre}">
                        ${rep.nombre}
                    </div>`
                ).join('');
                
                suggestionsDiv.style.display = 'block';

                // Agregar eventos a las sugerencias
                suggestionsDiv.querySelectorAll('.autocomplete-item').forEach(item => {
                    item.addEventListener('click', function() {
                        searchInput.value = this.getAttribute('data-nombre');
                        hiddenInput.value = this.getAttribute('data-id');
                        suggestionsDiv.style.display = 'none';
                        suggestionsDiv.innerHTML = '';
                    });
                });
            });

            // Cerrar sugerencias al hacer clic fuera
            document.addEventListener('click', function(e) {
                if (e.target !== searchInput && !suggestionsDiv.contains(e.target)) {
                    suggestionsDiv.style.display = 'none';
                }
            });

            // Validar al perder foco
            searchInput.addEventListener('blur', function() {
                setTimeout(() => {
                    const exactMatch = repartidores.find(rep => 
                        rep.nombre.toLowerCase() === this.value.toLowerCase().trim()
                    );
                    
                    if (exactMatch) {
                        hiddenInput.value = exactMatch.id;
                    } else if (this.value.trim() !== '') {
                        // Si hay texto pero no coincide exactamente, limpiar
                        this.value = '';
                        hiddenInput.value = '';
                    }
                }, 200);
            });
        }

        // Inicializar cuando el DOM esté listo
        $(document).ready(function() {
            setupAutocomplete('repartidor_nuevo_search', 'repartidor_nuevo_id', 'suggestions_nuevo');
            setupAutocomplete('repartidor_asignar_search', 'repartidor_asignar_id', 'suggestions_asignar');
            setupAutocomplete('repartidor_multiple_search', 'repartidor_multiple_id', 'suggestions_multiple');
        });

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

        // Funciones para selección múltiple
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.paquete-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            updateSelection();
        }

        function updateSelection() {
            const checkboxes = document.querySelectorAll('.paquete-checkbox:checked');
            const count = checkboxes.length;
            const btnAsignar = document.getElementById('btnAsignarMultiple');
            const countSpan = document.getElementById('countSelected');
            
            if (count > 0) {
                btnAsignar.style.display = 'inline-block';
                countSpan.textContent = count;
            } else {
                btnAsignar.style.display = 'none';
            }

            // Actualizar estado del checkbox "Seleccionar todo"
            const allCheckboxes = document.querySelectorAll('.paquete-checkbox');
            const selectAll = document.getElementById('selectAll');
            if (allCheckboxes.length > 0) {
                selectAll.checked = checkboxes.length === allCheckboxes.length;
            }
        }

        function asignarMultiples() {
            const checkboxes = document.querySelectorAll('.paquete-checkbox:checked');
            const ids = Array.from(checkboxes).map(cb => cb.value);
            
            if (ids.length === 0) {
                alert('Por favor seleccione al menos un paquete');
                return;
            }

            // Guardar los IDs en un campo oculto
            document.getElementById('asignar_paquetes_ids').value = ids.join(',');
            
            // Actualizar el contador en el modal
            document.getElementById('count_paquetes_modal').textContent = ids.length;
            
            const modal = new bootstrap.Modal(document.getElementById('modalAsignarMultiple'));
            modal.show();
        }
    </script>
    
    <!-- Chatbot Widget -->
    <?php include 'includes/chatbot_widget.php'; ?>
</body>
</html>
