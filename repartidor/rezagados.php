<?php
require_once '../config/config.php';
requireRole('repartidor');

$db = Database::getInstance()->getConnection();
$repartidor_id = $_SESSION['usuario_id'];

// Obtener paquetes rezagados del repartidor
$stmt = $db->prepare("
    SELECT p.*, pr.motivo, pr.descripcion_motivo, pr.fecha_rezago, pr.intentos_realizados, pr.proximo_intento
    FROM paquetes p
    INNER JOIN paquetes_rezagados pr ON p.id = pr.paquete_id
    WHERE p.repartidor_id = ? 
    AND pr.solucionado = 0
    AND p.estado IN ('rezagado', 'pendiente', 'en_ruta')
    ORDER BY pr.fecha_rezago DESC
");
$stmt->execute([$repartidor_id]);
$paquetes_rezagados = $stmt->fetchAll();

$pageTitle = "Paquetes Rezagados";
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
                <h1><i class="bi bi-exclamation-triangle"></i> Paquetes Rezagados</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Rezagados</li>
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
                <div class="card-header">
                    <h5 class="card-title">
                        Mis Paquetes Rezagados
                        <span class="badge bg-danger"><?php echo count($paquetes_rezagados); ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if(count($paquetes_rezagados) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Destinatario</th>
                                    <th>Dirección</th>
                                    <th>Motivo</th>
                                    <th>Intentos</th>
                                    <th>Fecha Rezago</th>
                                    <th>Próximo Intento</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($paquetes_rezagados as $paq): ?>
                                <tr>
                                    <td><strong><?php echo $paq['codigo_seguimiento']; ?></strong></td>
                                    <td>
                                        <?php echo $paq['destinatario_nombre']; ?><br>
                                        <small><?php echo $paq['destinatario_telefono']; ?></small>
                                    </td>
                                    <td><small><?php echo substr($paq['direccion_completa'], 0, 50) . '...'; ?></small></td>
                                    <td>
                                        <?php
                                        $motivos = [
                                            'direccion_incorrecta' => 'Dirección Incorrecta',
                                            'destinatario_ausente' => 'Destinatario Ausente',
                                            'rechazo' => 'Rechazado',
                                            'zona_peligrosa' => 'Zona Peligrosa',
                                            'otros' => 'Otros'
                                        ];
                                        echo $motivos[$paq['motivo']] ?? $paq['motivo'];
                                        ?>
                                        <?php if($paq['descripcion_motivo']): ?>
                                        <br><small class="text-muted"><?php echo $paq['descripcion_motivo']; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning"><?php echo $paq['intentos_realizados']; ?></span>
                                    </td>
                                    <td><?php echo formatDateTime($paq['fecha_rezago']); ?></td>
                                    <td>
                                        <?php echo $paq['proximo_intento'] ? formatDate($paq['proximo_intento']) : 'No programado'; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="entregar.php?paquete=<?php echo $paq['id']; ?>" class="btn btn-success" title="Intentar Entrega">
                                                <i class="bi bi-repeat"></i>
                                            </a>
                                            <button class="btn btn-info" onclick="verMapa(<?php echo $paq['direccion_latitud']; ?>, <?php echo $paq['direccion_longitud']; ?>)" title="Ver en Mapa">
                                                <i class="bi bi-geo-alt"></i>
                                            </button>
                                            <a href="tel:<?php echo $paq['destinatario_telefono']; ?>" class="btn btn-primary" title="Llamar">
                                                <i class="bi bi-telephone"></i>
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
                        <i class="bi bi-check-circle" style="font-size: 64px; color: #28a745;"></i>
                        <h4 class="mt-3">¡Excelente trabajo!</h4>
                        <p class="text-muted">No tienes paquetes rezagados</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
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
