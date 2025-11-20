<?php
require_once '../config/config.php';
requireRole(['admin', 'asistente']);

$pageTitle = 'Paquetes Rezagados';

$db = Database::getInstance()->getConnection();
$sql = "SELECT p.*, pr.*, u.nombre as repartidor_nombre, u.apellido as repartidor_apellido
        FROM paquetes_rezagados pr
        INNER JOIN paquetes p ON pr.paquete_id = p.id
        LEFT JOIN usuarios u ON p.repartidor_id = u.id
        WHERE pr.solucionado = 0
        ORDER BY pr.fecha_rezago DESC";
$rezagados = $db->query($sql)->fetchAll();
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
                <h1><i class="bi bi-exclamation-triangle"></i> <?php echo $pageTitle; ?></h1>
            </div>

            <div class="alert alert-warning">
                <i class="bi bi-info-circle"></i> <strong>Paquetes rezagados:</strong> 
                Entregas que no pudieron completarse y requieren un nuevo intento.
            </div>

            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo count($rezagados); ?></h3>
                            <p>Paquetes Rezagados</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Destinatario</th>
                                    <th>Dirección</th>
                                    <th>Repartidor</th>
                                    <th>Motivo</th>
                                    <th>Fecha Reintento</th>
                                    <th>Intentos</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rezagados as $rez): ?>
                                <tr>
                                    <td><strong><?php echo $rez['codigo_seguimiento']; ?></strong></td>
                                    <td><?php echo $rez['destinatario_nombre']; ?></td>
                                    <td class="small"><?php echo $rez['direccion_completa']; ?></td>
                                    <td><?php echo $rez['repartidor_nombre'] ? $rez['repartidor_nombre'] . ' ' . $rez['repartidor_apellido'] : '-'; ?></td>
                                    <td><span class="badge bg-danger"><?php echo ucfirst(str_replace('_', ' ', $rez['motivo'])); ?></span></td>
                                    <td><?php echo $rez['proximo_intento'] ? formatDate($rez['proximo_intento']) : '-'; ?></td>
                                    <td><span class="badge bg-warning"><?php echo $rez['intentos_realizados']; ?></span></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-success" onclick="reasignar(<?php echo $rez['paquete_id']; ?>)" title="Reasignar">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>
                                            <?php if ($rez['direccion_latitud'] && $rez['direccion_longitud']): ?>
                                            <button class="btn btn-info" onclick="verUbicacion(<?php echo $rez['direccion_latitud']; ?>, <?php echo $rez['direccion_longitud']; ?>, '<?php echo addslashes($rez['destinatario_nombre']); ?>', '<?php echo addslashes($rez['direccion_completa']); ?>')" title="Ver en Mapa">
                                                <i class="bi bi-geo-alt"></i>
                                            </button>
                                            <?php endif; ?>
                                            <button class="btn btn-primary" onclick="llamar('<?php echo $rez['destinatario_telefono']; ?>')" title="Llamar">
                                                <i class="bi bi-telephone"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Ver Ubicación -->
    <div class="modal fade" id="modalMapa" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubicación del Destinatario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="mapaInfo" class="mb-3">
                        <h6 id="mapaNombre"></h6>
                        <p class="text-muted" id="mapaDireccion"></p>
                    </div>
                    <div id="map" style="height: 400px; width: 100%;"></div>
                </div>
                <div class="modal-footer">
                    <a id="btnGoogleMaps" href="#" target="_blank" class="btn btn-primary">
                        <i class="bi bi-box-arrow-up-right"></i> Abrir en Google Maps
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_MAPS_API_KEY; ?>"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        let map;
        let marker;

        function verUbicacion(lat, lng, nombre, direccion) {
            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('modalMapa'));
            modal.show();
            
            // Actualizar información
            document.getElementById('mapaNombre').textContent = nombre;
            document.getElementById('mapaDireccion').textContent = direccion;
            
            // Configurar enlace a Google Maps
            document.getElementById('btnGoogleMaps').href = `https://www.google.com/maps?q=${lat},${lng}`;
            
            // Esperar a que el modal se muestre completamente
            setTimeout(() => {
                // Crear mapa
                const location = { lat: parseFloat(lat), lng: parseFloat(lng) };
                
                map = new google.maps.Map(document.getElementById('map'), {
                    center: location,
                    zoom: 16,
                    mapTypeId: 'roadmap'
                });
                
                // Agregar marcador
                marker = new google.maps.Marker({
                    position: location,
                    map: map,
                    title: nombre,
                    animation: google.maps.Animation.DROP
                });
                
                // InfoWindow
                const infoWindow = new google.maps.InfoWindow({
                    content: `
                        <div style="padding: 10px;">
                            <h6 class="mb-2">${nombre}</h6>
                            <p class="mb-0 small text-muted">${direccion}</p>
                        </div>
                    `
                });
                
                infoWindow.open(map, marker);
                
                marker.addListener('click', () => {
                    infoWindow.open(map, marker);
                });
            }, 300);
        }

        function llamar(telefono) {
            if (telefono) {
                window.location.href = 'tel:' + telefono;
            } else {
                alert('No hay número de teléfono registrado');
            }
        }
        
        function reasignar(id) {
            if (confirm('¿Reasignar este paquete a un repartidor?')) {
                window.location.href = 'paquetes.php?reasignar=' + id;
            }
        }
    </script>
</body>
</html>
