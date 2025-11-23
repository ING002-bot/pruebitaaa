<?php
require_once '../config/config.php';
requireRole(['asistente']);

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: entregas.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Obtener información de la entrega
$stmt = $db->prepare("SELECT e.*, p.codigo_seguimiento, p.destinatario_nombre, p.direccion_completa,
                      u.nombre as repartidor_nombre, u.apellido as repartidor_apellido
                      FROM entregas e
                      INNER JOIN paquetes p ON e.paquete_id = p.id
                      INNER JOIN usuarios u ON e.repartidor_id = u.id
                      WHERE e.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$entrega = $stmt->get_result()->fetch_assoc();

if (!$entrega) {
    header('Location: entregas.php');
    exit;
}

$pageTitle = 'Detalle de Entrega #' . $id;
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
                    <h1><?php echo $pageTitle; ?></h1>
                    <p class="text-muted">Información detallada de la entrega</p>
                </div>
                <div>
                    <a href="entregas.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Información del Paquete -->
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5><i class="bi bi-box"></i> Información del Paquete</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Código de Seguimiento:</th>
                                    <td><?php echo $entrega['codigo_seguimiento']; ?></td>
                                </tr>
                                <tr>
                                    <th>Destinatario:</th>
                                    <td><?php echo $entrega['destinatario_nombre']; ?></td>
                                </tr>
                                <tr>
                                    <th>Dirección:</th>
                                    <td><?php echo $entrega['direccion_completa']; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Información de la Entrega -->
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5><i class="bi bi-truck"></i> Información de Entrega</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Repartidor:</th>
                                    <td><?php echo $entrega['repartidor_nombre'] . ' ' . $entrega['repartidor_apellido']; ?></td>
                                </tr>
                                <tr>
                                    <th>Fecha de Entrega:</th>
                                    <td><?php echo date('d/m/Y H:i', strtotime($entrega['fecha_entrega'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Tipo de Entrega:</th>
                                    <td>
                                        <?php
                                        $badges = ['exitosa' => 'success', 'parcial' => 'warning', 'rechazada' => 'danger', 'no_encontrado' => 'secondary'];
                                        ?>
                                        <span class="badge bg-<?php echo $badges[$entrega['tipo_entrega']] ?? 'secondary'; ?>">
                                            <?php echo ucfirst($entrega['tipo_entrega']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php if ($entrega['receptor_nombre']): ?>
                                <tr>
                                    <th>Receptor:</th>
                                    <td><?php echo $entrega['receptor_nombre']; ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($entrega['receptor_dni']): ?>
                                <tr>
                                    <th>DNI Receptor:</th>
                                    <td><?php echo $entrega['receptor_dni']; ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($entrega['tiempo_entrega']): ?>
                                <tr>
                                    <th>Tiempo de Entrega:</th>
                                    <td><?php echo $entrega['tiempo_entrega']; ?> minutos</td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Observaciones -->
                <?php if ($entrega['observaciones']): ?>
                <div class="col-12">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5><i class="bi bi-chat-text"></i> Observaciones</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($entrega['observaciones'])); ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Evidencias Fotográficas -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-camera"></i> Evidencias Fotográficas</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <?php if ($entrega['foto_entrega']): ?>
                                <div class="col-md-4">
                                    <h6>Foto Principal</h6>
                                    <a href="../uploads/entregas/<?php echo $entrega['foto_entrega']; ?>" target="_blank">
                                        <img src="../uploads/entregas/<?php echo $entrega['foto_entrega']; ?>" 
                                             class="img-fluid rounded" 
                                             alt="Foto de entrega">
                                    </a>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($entrega['foto_adicional_1']): ?>
                                <div class="col-md-4">
                                    <h6>Foto Adicional 1</h6>
                                    <a href="../uploads/entregas/<?php echo $entrega['foto_adicional_1']; ?>" target="_blank">
                                        <img src="../uploads/entregas/<?php echo $entrega['foto_adicional_1']; ?>" 
                                             class="img-fluid rounded" 
                                             alt="Foto adicional 1">
                                    </a>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($entrega['foto_adicional_2']): ?>
                                <div class="col-md-4">
                                    <h6>Foto Adicional 2</h6>
                                    <a href="../uploads/entregas/<?php echo $entrega['foto_adicional_2']; ?>" target="_blank">
                                        <img src="../uploads/entregas/<?php echo $entrega['foto_adicional_2']; ?>" 
                                             class="img-fluid rounded" 
                                             alt="Foto adicional 2">
                                    </a>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($entrega['receptor_firma']): ?>
                                <div class="col-md-4">
                                    <h6>Firma del Receptor</h6>
                                    <a href="../uploads/entregas/<?php echo $entrega['receptor_firma']; ?>" target="_blank">
                                        <img src="../uploads/entregas/<?php echo $entrega['receptor_firma']; ?>" 
                                             class="img-fluid rounded border" 
                                             alt="Firma del receptor">
                                    </a>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!$entrega['foto_entrega'] && !$entrega['foto_adicional_1'] && !$entrega['foto_adicional_2'] && !$entrega['receptor_firma']): ?>
                                <div class="col-12">
                                    <p class="text-muted mb-0">No hay evidencias fotográficas disponibles</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ubicación de Entrega -->
                <?php if ($entrega['latitud_entrega'] && $entrega['longitud_entrega']): ?>
                <div class="col-12 mt-3">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-geo-alt"></i> Ubicación de Entrega</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Coordenadas:</strong> 
                                <?php echo $entrega['latitud_entrega']; ?>, <?php echo $entrega['longitud_entrega']; ?>
                            </p>
                            <a href="https://www.google.com/maps?q=<?php echo $entrega['latitud_entrega']; ?>,<?php echo $entrega['longitud_entrega']; ?>" 
                               target="_blank" 
                               class="btn btn-outline-primary">
                                <i class="bi bi-map"></i> Ver en Google Maps
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
