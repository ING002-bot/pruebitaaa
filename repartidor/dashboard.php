<?php
require_once '../config/config.php';
requireRole('repartidor');

$db = Database::getInstance()->getConnection();
$repartidor_id = $_SESSION['usuario_id'];

// Estadísticas del repartidor
$stmt = $db->prepare("SELECT COUNT(*) as total FROM paquetes WHERE repartidor_id = ? AND estado = 'en_ruta'");
$stmt->bind_param("i", $repartidor_id);
$stmt->execute();
$paquetesEnRuta = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM paquetes WHERE repartidor_id = ? AND DATE(fecha_entrega) = CURDATE() AND estado = 'entregado'");
$stmt->bind_param("i", $repartidor_id);
$stmt->execute();
$entregasHoy = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM paquetes WHERE repartidor_id = ? AND estado = 'rezagado'");
$stmt->bind_param("i", $repartidor_id);
$stmt->execute();
$rezagados = $stmt->get_result()->fetch_assoc()['total'];

// Ingresos del mes - SOLO entregas exitosas con tarifa
$stmt = $db->prepare("
    SELECT SUM(COALESCE(zt.tarifa_repartidor, 3.50)) as total_ingresos
    FROM entregas e
    INNER JOIN paquetes p ON e.paquete_id = p.id
    LEFT JOIN zonas_tarifas zt ON p.zona_tarifa_id = zt.id
    WHERE e.repartidor_id = ? 
      AND e.tipo_entrega = 'exitosa'
      AND MONTH(e.fecha_entrega) = MONTH(CURDATE()) 
      AND YEAR(e.fecha_entrega) = YEAR(CURDATE())
");
$stmt->bind_param("i", $repartidor_id);
$stmt->execute();
$ingresosMes = (float)($stmt->get_result()->fetch_assoc()['total_ingresos'] ?? 0);

// Ruta activa
$stmt = $db->prepare("SELECT * FROM rutas WHERE repartidor_id = ? AND fecha_ruta = CURDATE() AND estado IN ('planificada', 'en_progreso') ORDER BY id DESC LIMIT 1");
$stmt->bind_param("i", $repartidor_id);
$stmt->execute();
$rutaActiva = $stmt->get_result()->fetch_assoc();

// Paquetes de hoy
$stmt = $db->prepare("SELECT p.* FROM paquetes p WHERE p.repartidor_id = ? AND p.estado IN ('en_ruta', 'pendiente') ORDER BY p.prioridad DESC, p.fecha_asignacion");
$stmt->bind_param("i", $repartidor_id);
$stmt->execute();
$paquetesHoy = Database::getInstance()->fetchAll($stmt->get_result());

$pageTitle = "Mi Dashboard";
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
    <!-- Sidebar Repartidor -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-box-seam"></i>
            <h3>HERMES EXPRESS</h3>
            <p>REPARTIDOR</p>
        </div>
        
        <div class="sidebar-menu">
            <div class="menu-section">
                <a href="dashboard.php" class="menu-item active">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="mis_rutas.php" class="menu-item">
                    <i class="bi bi-map"></i>
                    <span>Mis Rutas</span>
                </a>
                <a href="mis_paquetes.php" class="menu-item">
                    <i class="bi bi-box"></i>
                    <span>Mis Paquetes</span>
                    <?php if($paquetesEnRuta > 0): ?>
                        <span class="badge bg-warning"><?php echo $paquetesEnRuta; ?></span>
                    <?php endif; ?>
                </a>
                <a href="entregar.php" class="menu-item">
                    <i class="bi bi-check-circle"></i>
                    <span>Entregar Paquete</span>
                </a>
                <a href="historial.php" class="menu-item">
                    <i class="bi bi-clock-history"></i>
                    <span>Historial</span>
                </a>
            </div>
            
            <div class="menu-section">
                <div class="menu-section-title">Herramientas</div>
                <a href="mapa.php" class="menu-item">
                    <i class="bi bi-geo-alt"></i>
                    <span>Mapa en Tiempo Real</span>
                </a>
                <a href="rezagados.php" class="menu-item">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span>Paquetes Rezagados</span>
                    <?php if($rezagados > 0): ?>
                        <span class="badge bg-danger"><?php echo $rezagados; ?></span>
                    <?php endif; ?>
                </a>
                <a href="mis_ingresos.php" class="menu-item">
                    <i class="bi bi-cash-stack"></i>
                    <span>Mis Ingresos</span>
                </a>
            </div>
            
            <div class="menu-section">
                <a href="perfil.php" class="menu-item">
                    <i class="bi bi-person"></i>
                    <span>Mi Perfil</span>
                </a>
                <a href="../auth/logout.php" class="menu-item">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </div>
        </div>
    </div>
    
    <div class="main-content">
        <!-- Header -->
        <?php include 'includes/header.php'; ?>
        
        <!-- Content -->
        <div class="page-content">
            <!-- Stats -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3><?php echo $paquetesEnRuta; ?></h3>
                            <p>En Ruta Hoy</p>
                        </div>
                        <div class="stat-icon warning">
                            <i class="bi bi-truck"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3><?php echo $entregasHoy; ?></h3>
                            <p>Entregados Hoy</p>
                        </div>
                        <div class="stat-icon success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3><?php echo $rezagados; ?></h3>
                            <p>Rezagados</p>
                        </div>
                        <div class="stat-icon danger">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3><?php echo formatCurrency($ingresosMes); ?></h3>
                            <p>Ingresos del Mes</p>
                        </div>
                        <div class="stat-icon primary">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Ruta Activa -->
            <?php if($rutaActiva): ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-map"></i> Ruta Activa de Hoy
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h6><?php echo $rutaActiva['nombre']; ?></h6>
                            <p class="text-muted"><?php echo $rutaActiva['descripcion']; ?></p>
                            <div class="progress mb-3" style="height: 25px;">
                                <?php
                                $progreso = $rutaActiva['total_paquetes'] > 0 ? 
                                    ($rutaActiva['paquetes_entregados'] / $rutaActiva['total_paquetes']) * 100 : 0;
                                ?>
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: <?php echo $progreso; ?>%">
                                    <?php echo round($progreso); ?>%
                                </div>
                            </div>
                            <p class="mb-0">
                                <strong><?php echo $rutaActiva['paquetes_entregados']; ?></strong> de 
                                <strong><?php echo $rutaActiva['total_paquetes']; ?></strong> paquetes entregados
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="mapa.php?ruta=<?php echo $rutaActiva['id']; ?>" class="btn btn-primary btn-lg mb-2">
                                <i class="bi bi-geo-alt"></i> Ver en Mapa
                            </a>
                            <br>
                            <a href="mis_rutas.php?ruta=<?php echo $rutaActiva['id']; ?>" class="btn btn-outline-primary">
                                <i class="bi bi-list-check"></i> Ver Detalles
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Paquetes de Hoy -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title">Mis Paquetes de Hoy</h5>
                    <a href="entregar.php" class="btn btn-success">
                        <i class="bi bi-plus-circle"></i> Registrar Entrega
                    </a>
                </div>
                <div class="card-body">
                    <?php if(count($paquetesHoy) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Destinatario</th>
                                    <th>Dirección</th>
                                    <th>Teléfono</th>
                                    <th>Prioridad</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($paquetesHoy as $paquete): ?>
                                <tr>
                                    <td><strong><?php echo $paquete['codigo_seguimiento']; ?></strong></td>
                                    <td><?php echo $paquete['destinatario_nombre']; ?></td>
                                    <td><small><?php echo substr($paquete['direccion_completa'], 0, 40) . '...'; ?></small></td>
                                    <td><?php echo $paquete['destinatario_telefono']; ?></td>
                                    <td>
                                        <?php
                                        $prioridadClass = [
                                            'normal' => 'bg-secondary',
                                            'urgente' => 'bg-warning',
                                            'express' => 'bg-danger'
                                        ];
                                        ?>
                                        <span class="badge <?php echo $prioridadClass[$paquete['prioridad']]; ?>">
                                            <?php echo ucfirst($paquete['prioridad']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $paquete['estado'] === 'en_ruta' ? 'bg-warning' : 'bg-secondary'; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $paquete['estado'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-info" onclick="verMapa(<?php echo $paquete['direccion_latitud']; ?>, <?php echo $paquete['direccion_longitud']; ?>)">
                                                <i class="bi bi-geo-alt"></i>
                                            </button>
                                            <a href="entregar.php?paquete=<?php echo $paquete['id']; ?>" class="btn btn-success">
                                                <i class="bi bi-check-circle"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-box" style="font-size: 48px; color: #ccc;"></i>
                        <p class="mt-3 text-muted">No tienes paquetes asignados para hoy</p>
                    </div>
                    <?php endif; ?>
                </div>
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
        
        function verMapa(lat, lng) {
            if(lat && lng) {
                window.open(`https://www.google.com/maps?q=${lat},${lng}`, '_blank');
            } else {
                alert('No hay coordenadas disponibles para este paquete');
            }
        }
    </script>
</body>
</html>
