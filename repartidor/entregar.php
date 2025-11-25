<?php
require_once '../config/config.php';
requireRole('repartidor');

$db = Database::getInstance()->getConnection();
$repartidor_id = $_SESSION['usuario_id'];

// Si viene un paquete específico
$paquete_seleccionado = null;
if(isset($_GET['paquete'])) {
    $stmt = $db->prepare("SELECT * FROM paquetes WHERE id = ? AND repartidor_id = ?");
    $stmt->bind_param("ii", $_GET['paquete'], $repartidor_id);
    $stmt->execute();
    $paquete_seleccionado = $stmt->get_result()->fetch_assoc();
}

// Obtener paquetes del repartidor que están en ruta
$stmt = $db->prepare("SELECT * FROM paquetes WHERE repartidor_id = ? AND estado = 'en_ruta' ORDER BY prioridad DESC");
$stmt->bind_param("i", $repartidor_id);
$stmt->execute();
$paquetes_disponibles = Database::getInstance()->fetchAll($stmt->get_result());

$pageTitle = "Registrar Entrega";
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
    <style>
        .camera-preview {
            width: 100%;
            max-width: 400px;
            height: 300px;
            background: #000;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }
        .camera-preview video,
        .camera-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .camera-controls {
            margin-top: 15px;
        }
        .map-container {
            width: 100%;
            height: 400px;
            border-radius: 10px;
            overflow: hidden;
        }
        .foto-preview {
            max-width: 200px;
            border-radius: 8px;
            margin: 10px 0;
        }
        
        /* Notificaciones Emergentes */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            max-width: 400px;
            animation: slideIn 0.3s ease-out;
            font-size: 14px;
            border-left: 4px solid;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
        
        .notification.success {
            background-color: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }
        
        .notification.error {
            background-color: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }
        
        .notification.warning {
            background-color: #fff3cd;
            color: #856404;
            border-left-color: #ffc107;
        }
        
        .notification.remove {
            animation: slideOut 0.3s ease-out;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="page-content">
            <div class="page-title">
                <h1>Registrar Entrega de Paquete</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Entregar Paquete</li>
                    </ol>
                </nav>
            </div>
            
            <?php if($flash = getFlashMessage()): ?>
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $flash['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form action="entregar_procesar.php" method="POST" enctype="multipart/form-data" id="formEntrega">
                <div class="row">
                    <!-- Columna Izquierda: Selección de Paquete y Datos -->
                    <div class="col-lg-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="bi bi-box"></i> Información del Paquete
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Seleccionar Paquete *</label>
                                    <select class="form-select" name="paquete_id" id="paqueteSelect" required onchange="cargarDatosPaquete()">
                                        <option value="">Seleccione un paquete...</option>
                                        <?php foreach($paquetes_disponibles as $paq): ?>
                                        <option value="<?php echo $paq['id']; ?>" 
                                                data-destinatario="<?php echo htmlspecialchars($paq['destinatario_nombre']); ?>"
                                                data-telefono="<?php echo $paq['destinatario_telefono']; ?>"
                                                data-direccion="<?php echo htmlspecialchars($paq['direccion_completa']); ?>"
                                                data-lat="<?php echo $paq['direccion_latitud']; ?>"
                                                data-lng="<?php echo $paq['direccion_longitud']; ?>"
                                                <?php echo ($paquete_seleccionado && $paquete_seleccionado['id'] == $paq['id']) ? 'selected' : ''; ?>>
                                            <?php echo $paq['codigo_seguimiento']; ?> - <?php echo $paq['destinatario_nombre']; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div id="infoPaquete" style="display: <?php echo $paquete_seleccionado ? 'block' : 'none'; ?>;">
                                    <div class="alert alert-info">
                                        <p class="mb-1"><strong>Destinatario:</strong> <span id="infoDestinatario"><?php echo $paquete_seleccionado['destinatario_nombre'] ?? ''; ?></span></p>
                                        <p class="mb-1"><strong>Teléfono:</strong> <span id="infoTelefono"><?php echo $paquete_seleccionado['destinatario_telefono'] ?? ''; ?></span></p>
                                        <p class="mb-0"><strong>Dirección:</strong> <span id="infoDireccion"><?php echo $paquete_seleccionado['direccion_completa'] ?? ''; ?></span></p>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Tipo de Entrega *</label>
                                    <select class="form-select" name="tipo_entrega" required>
                                        <option value="exitosa">Entrega Exitosa</option>
                                        <option value="parcial">Entrega Parcial</option>
                                        <option value="rechazada">Rechazada por Cliente</option>
                                        <option value="no_encontrado">Destinatario No Encontrado</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Nombre de Quien Recibe</label>
                                    <input type="text" class="form-control" name="receptor_nombre" 
                                           placeholder="Ej: María González">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">DNI de Quien Recibe</label>
                                    <input type="text" class="form-control" name="receptor_dni" 
                                           placeholder="Ej: 12345678" maxlength="8" pattern="[0-9]{8}" 
                                           title="Debe ingresar exactamente 8 dígitos" 
                                           oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 8)">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Observaciones</label>
                                    <textarea class="form-control" name="observaciones" rows="3" 
                                              placeholder="Detalles adicionales de la entrega..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Columna Derecha: Fotos y Ubicación -->
                    <div class="col-lg-6">
                        <!-- Sección de Fotos -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="bi bi-camera"></i> Evidencia Fotográfica
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Foto Principal de Entrega *</label>
                                    <div class="camera-preview mb-2" id="preview1">
                                        <img id="imgPreview1" style="display:none;">
                                        <video id="video1" style="display:none;" autoplay></video>
                                    </div>
                                    <div class="camera-controls">
                                        <button type="button" class="btn btn-primary" onclick="activarCamara(1)">
                                            <i class="bi bi-camera"></i> Abrir Cámara
                                        </button>
                                        <button type="button" class="btn btn-success" id="btnCapturar1" style="display:none;" onclick="capturarFoto(1)">
                                            <i class="bi bi-camera-fill"></i> Capturar
                                        </button>
                                        <input type="file" class="form-control mt-2" name="foto_entrega" id="fileInput1" accept="image/*" onchange="previewFile(1)">
                                        <input type="hidden" name="foto_entrega_data" id="fotoData1">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Foto Adicional 1 (Opcional)</label>
                                    <input type="file" class="form-control" name="foto_adicional_1" accept="image/*">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Foto Adicional 2 (Opcional)</label>
                                    <input type="file" class="form-control" name="foto_adicional_2" accept="image/*">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sección de Ubicación -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="bi bi-geo-alt"></i> Ubicación de Entrega
                                </h5>
                            </div>
                            <div class="card-body">
                                <button type="button" class="btn btn-primary mb-3" onclick="obtenerUbicacion()">
                                    <i class="bi bi-geo-alt-fill"></i> Obtener Mi Ubicación Actual
                                </button>
                                
                                <div id="mapaEntrega" class="map-container mb-3"></div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">Latitud</label>
                                        <input type="text" class="form-control" name="latitud_entrega" id="latitudInput" readonly>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">Longitud</label>
                                        <input type="text" class="form-control" name="longitud_entrega" id="longitudInput" readonly>
                                    </div>
                                </div>
                                
                                <div class="alert alert-warning">
                                    <small>
                                        <i class="bi bi-info-circle"></i> 
                                        La ubicación se captura automáticamente al obtener tu posición actual
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Botón de Envío -->
                <div class="card">
                    <div class="card-body text-center">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-check-circle"></i> Registrar Entrega
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary btn-lg ms-2">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_MAPS_API_KEY; ?>"></script>
    <script>
        let map;
        let marker;
        let stream1;
        
        // Inicializar mapa
        function initMap(lat = -12.046374, lng = -77.042793) {
            map = new google.maps.Map(document.getElementById('mapaEntrega'), {
                center: {lat: lat, lng: lng},
                zoom: 15
            });
            
            marker = new google.maps.Marker({
                position: {lat: lat, lng: lng},
                map: map,
                draggable: true,
                title: "Ubicación de entrega"
            });
            
            // Actualizar coordenadas al mover el marcador
            marker.addListener('dragend', function() {
                const pos = marker.getPosition();
                document.getElementById('latitudInput').value = pos.lat();
                document.getElementById('longitudInput').value = pos.lng();
            });
        }
        
        // Mostrar notificación emergente
        function mostrarNotificacion(mensaje, tipo = 'success') {
            const container = document.createElement('div');
            container.className = `notification ${tipo}`;
            container.textContent = mensaje;
            document.body.appendChild(container);
            
            setTimeout(() => {
                container.classList.add('remove');
                setTimeout(() => container.remove(), 300);
            }, 4000);
        }
        
        // Obtener ubicación actual
        function obtenerUbicacion() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    document.getElementById('latitudInput').value = lat;
                    document.getElementById('longitudInput').value = lng;
                    
                    if (map) {
                        map.setCenter({lat: lat, lng: lng});
                        marker.setPosition({lat: lat, lng: lng});
                    } else {
                        initMap(lat, lng);
                    }
                    
                    mostrarNotificacion('✅ Ubicación capturada correctamente. Coordenadas: ' + lat.toFixed(4) + ', ' + lng.toFixed(4), 'success');
                }, function(error) {
                    mostrarNotificacion('❌ Error al obtener ubicación: ' + error.message, 'error');
                });
            } else {
                mostrarNotificacion('⚠️ Tu navegador no soporta geolocalización', 'warning');
            }
        }
        
        // Cargar datos del paquete seleccionado
        function cargarDatosPaquete() {
            const select = document.getElementById('paqueteSelect');
            const option = select.options[select.selectedIndex];
            
            if (option.value) {
                document.getElementById('infoDestinatario').textContent = option.dataset.destinatario;
                document.getElementById('infoTelefono').textContent = option.dataset.telefono;
                document.getElementById('infoDireccion').textContent = option.dataset.direccion;
                document.getElementById('infoPaquete').style.display = 'block';
                
                // Si tiene coordenadas, mostrar en el mapa
                if (option.dataset.lat && option.dataset.lng) {
                    const lat = parseFloat(option.dataset.lat);
                    const lng = parseFloat(option.dataset.lng);
                    if (!isNaN(lat) && !isNaN(lng)) {
                        initMap(lat, lng);
                    }
                }
            } else {
                document.getElementById('infoPaquete').style.display = 'none';
            }
        }
        
        // Activar cámara
        function activarCamara(num) {
            const video = document.getElementById('video' + num);
            const btnCapturar = document.getElementById('btnCapturar' + num);
            
            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
                .then(function(mediaStream) {
                    if (num === 1) stream1 = mediaStream;
                    video.srcObject = mediaStream;
                    video.style.display = 'block';
                    btnCapturar.style.display = 'inline-block';
                })
                .catch(function(err) {
                    alert('Error al acceder a la cámara: ' + err.message);
                });
        }
        
        // Capturar foto
        function capturarFoto(num) {
            const video = document.getElementById('video' + num);
            const preview = document.getElementById('imgPreview' + num);
            const canvas = document.createElement('canvas');
            
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            
            const dataURL = canvas.toDataURL('image/jpeg');
            preview.src = dataURL;
            preview.style.display = 'block';
            video.style.display = 'none';
            
            document.getElementById('fotoData' + num).value = dataURL;
            
            // Detener cámara
            if (num === 1 && stream1) {
                stream1.getTracks().forEach(track => track.stop());
            }
            
            document.getElementById('btnCapturar' + num).style.display = 'none';
        }
        
        // Preview de archivo
        function previewFile(num) {
            const file = document.getElementById('fileInput' + num).files[0];
            const preview = document.getElementById('imgPreview' + num);
            const reader = new FileReader();
            
            reader.onloadend = function() {
                preview.src = reader.result;
                preview.style.display = 'block';
                document.getElementById('video' + num).style.display = 'none';
            }
            
            if (file) {
                reader.readAsDataURL(file);
            }
        }
        
        // Inicializar al cargar
        window.onload = function() {
            <?php if($paquete_seleccionado): ?>
                cargarDatosPaquete();
            <?php endif; ?>
        };
    </script>
</body>
</html>
