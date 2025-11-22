<?php
require_once '../config/config.php';
requireRole(['admin', 'asistente']);

$pageTitle = 'Editar Ruta';

// Obtener ID de la ruta
$ruta_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$db = Database::getInstance()->getConnection();

// Obtener información de la ruta
$sql = "SELECT * FROM rutas WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $ruta_id);
$stmt->execute();
$ruta = $stmt->get_result()->fetch_assoc();

if (!$ruta) {
    header('Location: rutas.php?error=no_encontrada');
    exit;
}

// Solo se pueden editar rutas planificadas
if ($ruta['estado'] != 'planificada') {
    header('Location: rutas.php?error=no_editable');
    exit;
}

// Obtener repartidores activos
$repartidores = Database::getInstance()->fetchAll($db->query("SELECT id, nombre, apellido FROM usuarios WHERE rol = 'repartidor' AND estado = 'activo'));

// Procesar array de ubicaciones
$ubicaciones_array = !empty($ruta['ubicaciones']) ? explode(',', $ruta['ubicaciones']) : [];
$ubicaciones_array = array_map('trim', $ubicaciones_array);
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
                <div>
                    <a href="ruta_detalle.php?id=<?php echo $ruta_id; ?>" class="btn btn-secondary btn-sm mb-2">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                    <h1><i class="bi bi-pencil-square"></i> <?php echo $pageTitle; ?></h1>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="ruta_actualizar.php" id="formEditarRuta">
                        <input type="hidden" name="id" value="<?php echo $ruta_id; ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Zona / Tipo de Ruta *</label>
                                <select name="zona" id="zonaSelect" class="form-select" required onchange="cargarUbicaciones()">
                                    <option value="">Seleccionar zona...</option>
                                    <option value="URBANO" <?php echo $ruta['zona'] == 'URBANO' ? 'selected' : ''; ?>>URBANO</option>
                                    <option value="PUEBLOS" <?php echo $ruta['zona'] == 'PUEBLOS' ? 'selected' : ''; ?>>PUEBLOS</option>
                                    <option value="PLAYAS" <?php echo $ruta['zona'] == 'PLAYAS' ? 'selected' : ''; ?>>PLAYAS</option>
                                    <option value="COOPERATIVAS" <?php echo $ruta['zona'] == 'COOPERATIVAS' ? 'selected' : ''; ?>>COOPERATIVAS</option>
                                    <option value="EXCOOPERATIVAS" <?php echo $ruta['zona'] == 'EXCOOPERATIVAS' ? 'selected' : ''; ?>>EXCOOPERATIVAS</option>
                                    <option value="FERREÑAFE" <?php echo $ruta['zona'] == 'FERREÑAFE' ? 'selected' : ''; ?>>FERREÑAFE</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha de la Ruta *</label>
                                <input type="date" name="fecha_ruta" class="form-control" value="<?php echo $ruta['fecha_ruta']; ?>" required>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Ubicaciones que abarca *</label>
                                <select name="ubicaciones[]" id="ubicacionesSelect" class="form-select" multiple size="10" required>
                                    <!-- Se cargarán dinámicamente -->
                                </select>
                                <small class="text-muted">Mantén presionado Ctrl (Cmd en Mac) para seleccionar múltiples ubicaciones</small>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Nombre de la Ruta *</label>
                                <input type="text" name="nombre" id="nombreRuta" class="form-control" value="<?php echo htmlspecialchars($ruta['nombre']); ?>" required>
                                <small class="text-muted">El nombre se genera automáticamente, pero puedes modificarlo</small>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Repartidor</label>
                                <select name="repartidor_id" class="form-select">
                                    <option value="">Sin asignar</option>
                                    <?php foreach ($repartidores as $rep): ?>
                                        <option value="<?php echo $rep['id']; ?>" <?php echo $ruta['repartidor_id'] == $rep['id'] ? 'selected' : ''; ?>>
                                            <?php echo $rep['nombre'] . ' ' . $rep['apellido']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea name="descripcion" class="form-control" rows="3"><?php echo htmlspecialchars($ruta['descripcion']); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Guardar Cambios
                            </button>
                            <a href="ruta_detalle.php?id=<?php echo $ruta_id; ?>" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Definición de zonas y ubicaciones
        const zonasUbicaciones = {
            'URBANO': ['Chiclayo', 'Leonardo Ortiz', 'La Victoria', 'Santa Victoria'],
            'PUEBLOS': ['Lambayeque', 'Mochumi', 'Túcume', 'Íllimo', 'Nueva Arica', 'Jayanca', 'Púcara', 'Mórrope', 'Motupe', 'Olmos', 'Salas'],
            'PLAYAS': ['San José', 'Santa Rosa', 'Pimentel', 'Reque', 'Monsefú', 'Eten', 'Puerto Eten'],
            'COOPERATIVAS': ['Pomalca', 'Tumán', 'Pátapo', 'Pucalá', 'Saltur', 'Chongoyape'],
            'EXCOOPERATIVAS': ['Ucupe', 'Mocupe', 'Zaña', 'Cayaltí', 'Oyotún', 'Lagunas'],
            'FERREÑAFE': ['Ferreñafe', 'Picsi', 'Pítipo', 'Motupillo', 'Pueblo Nuevo']
        };
        
        // Ubicaciones seleccionadas previamente
        const ubicacionesSeleccionadas = <?php echo json_encode($ubicaciones_array); ?>;
        
        function cargarUbicaciones() {
            const zona = document.getElementById('zonaSelect').value;
            const selectUbicaciones = document.getElementById('ubicacionesSelect');
            
            // Limpiar opciones
            selectUbicaciones.innerHTML = '';
            
            if (zona && zonasUbicaciones[zona]) {
                zonasUbicaciones[zona].forEach(ubicacion => {
                    const option = document.createElement('option');
                    option.value = ubicacion;
                    option.textContent = ubicacion;
                    
                    // Marcar como seleccionada si estaba en el array original
                    if (ubicacionesSeleccionadas.includes(ubicacion)) {
                        option.selected = true;
                    }
                    
                    selectUbicaciones.appendChild(option);
                });
            }
            
            generarNombreRuta();
        }
        
        function generarNombreRuta() {
            const zona = document.getElementById('zonaSelect').value;
            const ubicacionesSelect = document.getElementById('ubicacionesSelect');
            const selectedOptions = Array.from(ubicacionesSelect.selectedOptions);
            const nombreRuta = document.getElementById('nombreRuta');
            
            if (!zona) {
                return;
            }
            
            const selectedUbicaciones = selectedOptions.map(opt => opt.value);
            const cantidad = selectedUbicaciones.length;
            
            if (cantidad === 0) {
                nombreRuta.value = zona;
            } else if (cantidad === 1) {
                nombreRuta.value = `${zona} - ${selectedUbicaciones[0]}`;
            } else if (cantidad <= 3) {
                nombreRuta.value = `${zona} - ${selectedUbicaciones.join(', ')}`;
            } else {
                nombreRuta.value = `${zona} - ${cantidad} ubicaciones`;
            }
        }
        
        // Cargar ubicaciones al inicio
        document.addEventListener('DOMContentLoaded', function() {
            cargarUbicaciones();
        });
        
        // Actualizar nombre cuando cambian las ubicaciones
        document.getElementById('ubicacionesSelect').addEventListener('change', generarNombreRuta);
    </script>
</body>
</html>
