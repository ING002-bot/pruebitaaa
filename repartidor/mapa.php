<?php
require_once '../config/config.php';
requireRole('repartidor');

$db = Database::getInstance()->getConnection();
$repartidor_id = $_SESSION['usuario_id'];

// Obtener ruta activa
$stmt = $db->prepare("SELECT * FROM rutas WHERE repartidor_id = ? AND fecha_ruta = CURDATE() AND estado IN ('planificada', 'en_progreso') ORDER BY id DESC LIMIT 1");
$stmt->bind_param("i", $repartidor_id);
$stmt->execute();
$rutaActiva = $stmt->get_result()->fetch_assoc();

// Obtener paquetes en ruta
$paquetes = [];
if ($rutaActiva) {
    $stmt = $db->prepare("SELECT p.*, rp.orden_entrega 
                          FROM paquetes p 
                          INNER JOIN ruta_paquetes rp ON p.id = rp.paquete_id 
                          WHERE rp.ruta_id = ? AND p.estado = 'en_ruta'
                          ORDER BY rp.orden_entrega");
    $stmt->bind_param("i", $rutaActiva['id']);
    $stmt->execute();
    $paquetes = Database::getInstance()->fetchAll($stmt->get_result());
}

$pageTitle = "Mapa en Tiempo Real";
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
        #map {
            height: calc(100vh - 140px);
            width: 100%;
            border-radius: 15px;
        }
        .map-controls {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.2);
        }
        .paquete-card {
            background: white;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .paquete-card:hover {
            background: #f8f9fa;
            transform: translateX(5px);
        }
        .paquete-card.activo {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
        }
        .info-panel {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.2);
            max-width: 350px;
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }
        .ubicacion-actual {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="page-content">
            <div class="page-title">
                <h1><i class="bi bi-geo-alt"></i> Mapa en Tiempo Real</h1>
            </div>
            
            <div style="position: relative;">
                <!-- Panel de información -->
                <div class="info-panel">
                    <h5 class="mb-3">
                        <i class="bi bi-list-ul"></i> Paquetes en Ruta
                        <span class="badge bg-primary float-end"><?php echo count($paquetes); ?></span>
                    </h5>
                    
                    <?php if(count($paquetes) > 0): ?>
                        <div id="listaPaquetes">
                            <?php foreach($paquetes as $index => $paq): ?>
                            <div class="paquete-card" onclick="focusPaquete(<?php echo $index; ?>)" 
                                 data-lat="<?php echo $paq['direccion_latitud']; ?>" 
                                 data-lng="<?php echo $paq['direccion_longitud']; ?>"
                                 id="paquete-<?php echo $index; ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong><?php echo $index + 1; ?>. <?php echo $paq['codigo_seguimiento']; ?></strong>
                                        <p class="mb-1 small"><?php echo $paq['destinatario_nombre']; ?></p>
                                        <p class="mb-0 text-muted" style="font-size: 11px;">
                                            <i class="bi bi-geo-alt"></i> <?php echo substr($paq['direccion_completa'], 0, 40) . '...'; ?>
                                        </p>
                                    </div>
                                    <span class="badge bg-<?php echo $paq['prioridad'] === 'urgente' ? 'danger' : ($paq['prioridad'] === 'express' ? 'warning' : 'secondary'); ?>">
                                        <?php echo $paq['prioridad']; ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox" style="font-size: 48px;"></i>
                            <p class="mt-2">No hay paquetes en ruta</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Controles del mapa -->
                <div class="map-controls">
                    <button class="btn btn-primary btn-sm mb-2 w-100" onclick="centrarMiUbicacion()">
                        <i class="bi bi-crosshair"></i> Mi Ubicación
                    </button>
                    <button class="btn btn-success btn-sm mb-2 w-100" onclick="mostrarRuta()">
                        <i class="bi bi-diagram-3"></i> Ver Ruta Completa
                    </button>
                    <button class="btn btn-warning btn-sm w-100" id="btnTracker">
                        <i class="bi bi-radar"></i> <span id="trackerText">Activar Tracker</span>
                    </button>
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="bi bi-circle-fill ubicacion-actual text-success"></i> Tu ubicación
                        </small>
                    </div>
                </div>
                
                <!-- Mapa -->
                <div id="map"></div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_MAPS_API_KEY; ?>"></script>
    <script>
        let map;
        let miUbicacionMarker;
        let paquetesMarkers = [];
        let directionsService;
        let directionsRenderer;
        let watchId = null;
        let isTracking = false;
        
        // Datos de paquetes
        const paquetes = <?php echo json_encode($paquetes); ?>;
        
        // Inicializar mapa
        function initMap() {
            const center = paquetes.length > 0 && paquetes[0].direccion_latitud ? 
                {lat: parseFloat(paquetes[0].direccion_latitud), lng: parseFloat(paquetes[0].direccion_longitud)} :
                {lat: -12.046374, lng: -77.042793}; // Lima, Perú por defecto
            
            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 13,
                center: center,
                mapTypeControl: true,
                streetViewControl: true,
                fullscreenControl: true
            });
            
            directionsService = new google.maps.DirectionsService();
            directionsRenderer = new google.maps.DirectionsRenderer({
                map: map,
                suppressMarkers: true
            });
            
            // Agregar marcadores de paquetes
            paquetes.forEach((paquete, index) => {
                if (paquete.direccion_latitud && paquete.direccion_longitud) {
                    const marker = new google.maps.Marker({
                        position: {
                            lat: parseFloat(paquete.direccion_latitud),
                            lng: parseFloat(paquete.direccion_longitud)
                        },
                        map: map,
                        label: (index + 1).toString(),
                        title: paquete.destinatario_nombre,
                        icon: {
                            url: 'http://maps.google.com/mapfiles/ms/icons/' + 
                                 (paquete.prioridad === 'urgente' ? 'red' : 'blue') + '-dot.png'
                        }
                    });
                    
                    const infoWindow = new google.maps.InfoWindow({
                        content: `
                            <div style="padding: 10px;">
                                <h6>${paquete.codigo_seguimiento}</h6>
                                <p class="mb-1"><strong>${paquete.destinatario_nombre}</strong></p>
                                <p class="mb-1">${paquete.destinatario_telefono}</p>
                                <p class="mb-1"><small>${paquete.direccion_completa}</small></p>
                                <a href="entregar.php?paquete=${paquete.id}" class="btn btn-sm btn-success mt-2">
                                    <i class="bi bi-check-circle"></i> Entregar
                                </a>
                            </div>
                        `
                    });
                    
                    marker.addListener('click', () => {
                        infoWindow.open(map, marker);
                    });
                    
                    paquetesMarkers.push(marker);
                }
            });
            
            // Obtener ubicación inicial
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(position => {
                    const pos = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    
                    miUbicacionMarker = new google.maps.Marker({
                        position: pos,
                        map: map,
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 10,
                            fillColor: '#4CAF50',
                            fillOpacity: 1,
                            strokeColor: 'white',
                            strokeWeight: 3
                        },
                        title: 'Mi ubicación'
                    });
                    
                    map.setCenter(pos);
                });
            }
        }
        
        // Centrar en mi ubicación
        function centrarMiUbicacion() {
            if (miUbicacionMarker) {
                map.setCenter(miUbicacionMarker.getPosition());
                map.setZoom(16);
            } else {
                alert('Ubicación no disponible');
            }
        }
        
        // Focus en paquete específico
        function focusPaquete(index) {
            // Remover clase activa de todos
            document.querySelectorAll('.paquete-card').forEach(card => {
                card.classList.remove('activo');
            });
            
            // Agregar clase activa al seleccionado
            document.getElementById('paquete-' + index).classList.add('activo');
            
            // Centrar en el marcador
            if (paquetesMarkers[index]) {
                map.setCenter(paquetesMarkers[index].getPosition());
                map.setZoom(17);
                
                // Mostrar info window
                google.maps.event.trigger(paquetesMarkers[index], 'click');
            }
        }
        
        // Mostrar ruta completa
        function mostrarRuta() {
            if (paquetes.length === 0) {
                alert('No hay paquetes para crear ruta');
                return;
            }
            
            if (!miUbicacionMarker) {
                alert('Esperando ubicación actual...');
                return;
            }
            
            const waypoints = [];
            paquetes.forEach(paq => {
                if (paq.direccion_latitud && paq.direccion_longitud) {
                    waypoints.push({
                        location: {
                            lat: parseFloat(paq.direccion_latitud),
                            lng: parseFloat(paq.direccion_longitud)
                        },
                        stopover: true
                    });
                }
            });
            
            if (waypoints.length === 0) return;
            
            const origin = miUbicacionMarker.getPosition();
            const destination = waypoints[waypoints.length - 1].location;
            waypoints.pop(); // Remover el último para usarlo como destino
            
            const request = {
                origin: origin,
                destination: destination,
                waypoints: waypoints,
                travelMode: google.maps.TravelMode.DRIVING,
                optimizeWaypoints: true
            };
            
            directionsService.route(request, (result, status) => {
                if (status === 'OK') {
                    directionsRenderer.setDirections(result);
                    
                    const route = result.routes[0];
                    let totalDistance = 0;
                    let totalDuration = 0;
                    
                    route.legs.forEach(leg => {
                        totalDistance += leg.distance.value;
                        totalDuration += leg.duration.value;
                    });
                    
                    alert(`Ruta calculada:\nDistancia: ${(totalDistance/1000).toFixed(2)} km\nTiempo estimado: ${Math.round(totalDuration/60)} min`);
                } else {
                    alert('Error al calcular ruta: ' + status);
                }
            });
        }
        
        // Activar/Desactivar tracking
        document.getElementById('btnTracker').addEventListener('click', function() {
            if (!isTracking) {
                startTracking();
            } else {
                stopTracking();
            }
        });
        
        function startTracking() {
            if ('geolocation' in navigator) {
                watchId = navigator.geolocation.watchPosition(
                    position => {
                        const pos = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };
                        
                        if (miUbicacionMarker) {
                            miUbicacionMarker.setPosition(pos);
                        }
                        
                        // Enviar al servidor
                        fetch('../api/actualizar_ubicacion.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                lat: pos.lat,
                                lng: pos.lng,
                                ruta_id: <?php echo $rutaActiva['id'] ?? 'null'; ?>
                            })
                        });
                    },
                    error => console.error('Error de tracking:', error),
                    {
                        enableHighAccuracy: true,
                        timeout: 5000,
                        maximumAge: 0
                    }
                );
                
                isTracking = true;
                document.getElementById('trackerText').textContent = 'Desactivar Tracker';
                document.getElementById('btnTracker').classList.remove('btn-warning');
                document.getElementById('btnTracker').classList.add('btn-danger');
            }
        }
        
        function stopTracking() {
            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
                isTracking = false;
                document.getElementById('trackerText').textContent = 'Activar Tracker';
                document.getElementById('btnTracker').classList.remove('btn-danger');
                document.getElementById('btnTracker').classList.add('btn-warning');
            }
        }
        
        // Inicializar al cargar
        window.onload = initMap;
        
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>
