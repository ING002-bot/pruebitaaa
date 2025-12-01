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
                                        <option value="rechazada">Rechazada por Cliente</option>
                                        <option value="no_encontrado">Destinatario No Encontrado</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Nombre de Quien Recibe</label>
                                    <input type="text" class="form-control" name="receptor_nombre" 
                                           placeholder="Ej: María González" pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+" title="Solo se permiten letras y espacios">
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
                                <div class="mb-3">
                                    <button type="button" class="btn btn-primary" id="btnObtenerUbicacion" onclick="obtenerUbicacion()">
                                        <i class="bi bi-geo-alt-fill"></i> Obtener Mi Ubicación Actual
                                    </button>
                                </div>
                                
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
                                
                                <div class="alert alert-info">
                                    <small>
                                        <i class="bi bi-info-circle"></i> 
                                        <strong>Instrucciones:</strong><br>
                                        • Haz clic en "Obtener Mi Ubicación Actual" para usar GPS<br>
                                        • Puedes arrastrar el marcador en el mapa para ajustar la posición<br>
                                        • Asegúrate de permitir el acceso a la ubicación cuando te lo solicite el navegador
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
    <!-- OpenStreetMap con Leaflet como alternativa a Google Maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        let map;
        let marker;
        let stream1;
        
        // Inicializar mapa con OpenStreetMap
        function initMap(lat = -12.046374, lng = -77.042793) {
            try {
                // Si el mapa ya existe, eliminarlo
                if (map) {
                    map.remove();
                }
                
                // Verificar si Leaflet está disponible
                if (typeof L === 'undefined') {
                    throw new Error('Leaflet no está disponible');
                }
                
                map = L.map('mapaEntrega').setView([lat, lng], 15);
                
                // Usar OpenStreetMap tiles (no requiere API key)
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19,
                    errorTileUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjU2IiBoZWlnaHQ9IjI1NiIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjU2IiBoZWlnaHQ9IjI1NiIgZmlsbD0iI2YwZjBmMCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5NYXBhIG5vIGRpc3BvbmlibGU8L3RleHQ+PC9zdmc+'
                }).addTo(map);
                
                // Añadir marcador draggable
                marker = L.marker([lat, lng], {
                    draggable: true,
                    title: "Ubicación de entrega"
                }).addTo(map);
                
                // Actualizar coordenadas al mover el marcador
                marker.on('dragend', function(e) {
                    const pos = e.target.getLatLng();
                    document.getElementById('latitudInput').value = pos.lat.toFixed(6);
                    document.getElementById('longitudInput').value = pos.lng.toFixed(6);
                    mostrarNotificacion('📍 Marcador movido a: ' + pos.lat.toFixed(4) + ', ' + pos.lng.toFixed(4), 'success');
                });
                
                // Actualizar inputs con la posición inicial
                document.getElementById('latitudInput').value = lat.toFixed(6);
                document.getElementById('longitudInput').value = lng.toFixed(6);
                
                // Manejar errores de carga de tiles
                map.on('tileerror', function(error) {
                    console.log('Error cargando mapa:', error);
                    mostrarNotificacion('⚠️ Problemas cargando el mapa. Las coordenadas funcionan normalmente.', 'warning');
                });
                
            } catch (error) {
                console.error('Error inicializando mapa:', error);
                modoSoloCoordenas();
                mostrarNotificacion('⚠️ El mapa no se pudo cargar. Usa los botones para obtener coordenadas.', 'warning');
            }
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
            // Verificar si el navegador soporta geolocalización
            if (!navigator.geolocation) {
                mostrarNotificacion('⚠️ Tu navegador no soporta geolocalización', 'warning');
                return;
            }
            
            // Obtener referencia al botón de forma más segura
            const btnUbicacion = document.getElementById('btnObtenerUbicacion');
            if (!btnUbicacion) return;
            
            const textoOriginal = btnUbicacion.innerHTML;
            btnUbicacion.innerHTML = '<i class="bi bi-hourglass-split"></i> Obteniendo ubicación...';
            btnUbicacion.disabled = true;
            
            // Opciones para la geolocalización
            const opciones = {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 30000
            };
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const precision = position.coords.accuracy;
                    
                    // Actualizar inputs
                    document.getElementById('latitudInput').value = lat.toFixed(6);
                    document.getElementById('longitudInput').value = lng.toFixed(6);
                    
                    // Actualizar mapa
                    try {
                        if (map && marker) {
                            map.setView([lat, lng], 16);
                            marker.setLatLng([lat, lng]);
                        } else {
                            initMap(lat, lng);
                        }
                    } catch (error) {
                        console.log('Error actualizando mapa:', error);
                    }
                    
                    // Mostrar notificación
                    const mensaje = `✅ Ubicación obtenida correctamente: ${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                    mostrarNotificacion(mensaje, 'success');
                    
                    // Restaurar botón
                    btnUbicacion.innerHTML = textoOriginal;
                    btnUbicacion.disabled = false;
                }, 
                function(error) {
                    let mensaje = '';
                    let titulo = '';
                    
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            titulo = '🔒 Permisos de Ubicación';
                            mensaje = `
                                <div class="mb-3">
                                    <strong>Para usar esta función necesitas permitir el acceso a tu ubicación:</strong>
                                </div>
                                <div class="text-start">
                                    <p><strong>En Chrome/Edge:</strong></p>
                                    <ol class="small">
                                        <li>Haz clic en el ícono del candado 🔒 en la barra de direcciones</li>
                                        <li>Selecciona "Permitir" para Ubicación</li>
                                        <li>Recarga la página</li>
                                    </ol>
                                    <p><strong>En Firefox:</strong></p>
                                    <ol class="small">
                                        <li>Haz clic en el ícono de escudo en la barra de direcciones</li>
                                        <li>Permite el acceso a la ubicación</li>
                                        <li>Recarga la página</li>
                                    </ol>
                                </div>
                            `;
                            
                            // Mostrar modal de ayuda
                            mostrarModalAyudaUbicacion(titulo, mensaje);
                            break;
                        case error.POSITION_UNAVAILABLE:
                            mostrarNotificacion('❌ Ubicación no disponible. Verifica que tengas GPS habilitado y conexión a internet.', 'error');
                            break;
                        case error.TIMEOUT:
                            mostrarNotificacion('⏰ Tiempo agotado obteniendo ubicación. Inténtalo de nuevo.', 'error');
                            break;
                        default:
                            mostrarNotificacion('❌ Error desconocido al obtener ubicación. Inténtalo de nuevo.', 'error');
                            break;
                    }
                    
                    // Restaurar botón
                    btnUbicacion.innerHTML = textoOriginal;
                    btnUbicacion.disabled = false;
                }, 
                opciones
            );
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
        
        // Función de respaldo sin mapa
        function modoSoloCoordenas() {
            document.getElementById('mapaEntrega').innerHTML = `
                <div class="alert alert-warning text-center">
                    <i class="bi bi-exclamation-triangle"></i><br>
                    <strong>Modo Sin Mapa</strong><br>
                    El mapa no se pudo cargar. Usa el botón "Obtener Mi Ubicación Actual" para capturar coordenadas.
                </div>
            `;
        }
        
        // Mostrar modal de ayuda para permisos de ubicación
        function mostrarModalAyudaUbicacion(titulo, contenido) {
            // Crear modal dinámicamente si no existe
            let modal = document.getElementById('modalAyudaUbicacion');
            if (!modal) {
                const modalHTML = `
                    <div class="modal fade" id="modalAyudaUbicacion" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title" id="tituloAyudaUbicacion">${titulo}</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body" id="contenidoAyudaUbicacion">
                                    ${contenido}
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendido</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                document.body.insertAdjacentHTML('beforeend', modalHTML);
                modal = document.getElementById('modalAyudaUbicacion');
            } else {
                // Actualizar contenido si ya existe
                document.getElementById('tituloAyudaUbicacion').innerHTML = titulo;
                document.getElementById('contenidoAyudaUbicacion').innerHTML = contenido;
            }
            
            // Mostrar modal
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        }

        // Verificar si el protocolo es seguro (HTTPS)
        function verificarProtocolo() {
            if (location.protocol !== 'https:' && location.hostname !== 'localhost') {
                mostrarNotificacion('⚠️ La geolocalización requiere HTTPS. Algunas funciones pueden no funcionar correctamente.', 'warning');
            }
        }
        
        // Detectar y manejar errores de recursos bloqueados
        window.addEventListener('error', function(e) {
            if (e.message && e.message.includes('ERR_BLOCKED_BY_CLIENT')) {
                mostrarNotificacion('⚠️ Algunos recursos están bloqueados. Si tienes AdBlock, considera agregarnos a la lista blanca.', 'warning');
            }
        }, true);
        
        // Inicializar al cargar
        window.addEventListener('DOMContentLoaded', function() {
            // Verificar protocolo
            verificarProtocolo();
            
            // Inicializar mapa con timeout
            setTimeout(function() {
                try {
                    initMap(-12.046374, -77.042793);
                } catch (error) {
                    console.error('Error inicializando mapa:', error);
                    modoSoloCoordenas();
                }
            }, 500);
            
            <?php if($paquete_seleccionado): ?>
                // Cargar datos del paquete seleccionado
                setTimeout(cargarDatosPaquete, 100);
            <?php endif; ?>
            
            // Validación del formulario antes de enviar
            document.getElementById('formEntrega').addEventListener('submit', function(e) {
                const lat = document.getElementById('latitudInput').value;
                const lng = document.getElementById('longitudInput').value;
                
                if (!lat || !lng || parseFloat(lat) === 0 || parseFloat(lng) === 0) {
                    e.preventDefault();
                    mostrarNotificacion('⚠️ Por favor, obtén tu ubicación antes de registrar la entrega', 'warning');
                    return false;
                }
                
                return true;
            });
        });
    </script>
</body>
</html>
