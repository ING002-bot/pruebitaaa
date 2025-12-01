<?php
require_once '../config/config.php';
requireRole(['asistente']);

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: paquetes.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Obtener información del paquete
$stmt = $db->prepare("SELECT p.*, u.nombre as repartidor_nombre, u.apellido as repartidor_apellido
                      FROM paquetes p
                      LEFT JOIN usuarios u ON p.repartidor_id = u.id
                      WHERE p.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$paquete = $stmt->get_result()->fetch_assoc();

if (!$paquete) {
    header('Location: paquetes.php');
    exit;
}

// Obtener historial de entregas
$stmt = $db->prepare("SELECT * FROM entregas WHERE paquete_id = ? ORDER BY fecha_entrega DESC");
$stmt->bind_param("i", $id);
$stmt->execute();
$entregas = Database::getInstance()->fetchAll($stmt->get_result());

// Verificar si está rezagado
$stmt = $db->prepare("SELECT * FROM paquetes_rezagados WHERE paquete_id = ? ORDER BY fecha_rezago DESC LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$rezagado = $stmt->get_result()->fetch_assoc();

$pageTitle = 'Detalle del Paquete - ' . $paquete['codigo_seguimiento'];
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
                    <p class="text-muted">Información completa del paquete</p>
                </div>
                <div>
                    <a href="paquetes.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Información General -->
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5><i class="bi bi-info-circle"></i> Información General</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">ID:</th>
                                    <td><?php echo $paquete['id']; ?></td>
                                </tr>
                                <tr>
                                    <th>Código de Seguimiento:</th>
                                    <td><strong><?php echo $paquete['codigo_seguimiento']; ?></strong></td>
                                </tr>
                                <?php if ($paquete['codigo_savar']): ?>
                                <tr>
                                    <th>Código SAVAR:</th>
                                    <td><?php echo $paquete['codigo_savar']; ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <th>Estado:</th>
                                    <td>
                                        <?php
                                        $badges = [
                                            'pendiente' => 'warning',
                                            'en_ruta' => 'info',
                                            'entregado' => 'success',
                                            'rezagado' => 'danger',
                                            'devuelto' => 'secondary',
                                            'cancelado' => 'dark'
                                        ];
                                        ?>
                                        <span class="badge bg-<?php echo $badges[$paquete['estado']]; ?>">
                                            <?php echo ucfirst($paquete['estado']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Prioridad:</th>
                                    <td>
                                        <?php
                                        $prioridad_badges = ['normal' => 'secondary', 'urgente' => 'warning', 'express' => 'danger'];
                                        ?>
                                        <span class="badge bg-<?php echo $prioridad_badges[$paquete['prioridad']]; ?>">
                                            <?php echo ucfirst($paquete['prioridad']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Fecha de Recepción:</th>
                                    <td><?php echo date('d/m/Y H:i', strtotime($paquete['fecha_recepcion'])); ?></td>
                                </tr>
                                <?php if ($paquete['fecha_asignacion']): ?>
                                <tr>
                                    <th>Fecha de Asignación:</th>
                                    <td><?php echo date('d/m/Y H:i', strtotime($paquete['fecha_asignacion'])); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($paquete['fecha_entrega']): ?>
                                <tr>
                                    <th>Fecha de Entrega:</th>
                                    <td><?php echo date('d/m/Y H:i', strtotime($paquete['fecha_entrega'])); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Destinatario -->
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5><i class="bi bi-person"></i> Destinatario</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Nombre:</th>
                                    <td><?php echo $paquete['destinatario_nombre']; ?></td>
                                </tr>
                                <?php if ($paquete['destinatario_telefono']): ?>
                                <tr>
                                    <th>Teléfono:</th>
                                    <td><?php echo $paquete['destinatario_telefono']; ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($paquete['destinatario_email']): ?>
                                <tr>
                                    <th>Email:</th>
                                    <td><?php echo $paquete['destinatario_email']; ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <th>Dirección:</th>
                                    <td><?php echo $paquete['direccion_completa']; ?></td>
                                </tr>
                                <?php if ($paquete['ciudad']): ?>
                                <tr>
                                    <th>Ciudad:</th>
                                    <td><?php echo $paquete['ciudad']; ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($paquete['provincia']): ?>
                                <tr>
                                    <th>Provincia:</th>
                                    <td><?php echo $paquete['provincia']; ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($paquete['distrito']): ?>
                                <tr>
                                    <th>Distrito:</th>
                                    <td><?php echo $paquete['distrito']; ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Detalles del Paquete -->
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5><i class="bi bi-box-seam"></i> Detalles del Paquete</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <?php if ($paquete['peso']): ?>
                                <tr>
                                    <th width="40%">Peso:</th>
                                    <td><?php echo $paquete['peso']; ?> kg</td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($paquete['dimensiones']): ?>
                                <tr>
                                    <th>Dimensiones:</th>
                                    <td><?php echo $paquete['dimensiones']; ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($paquete['descripcion']): ?>
                                <tr>
                                    <th>Descripción:</th>
                                    <td><?php echo $paquete['descripcion']; ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($paquete['valor_declarado']): ?>
                                <tr>
                                    <th>Valor Declarado:</th>
                                    <td>S/ <?php echo number_format($paquete['valor_declarado'], 2); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($paquete['costo_envio']): ?>
                                <tr>
                                    <th>Costo de Envío:</th>
                                    <td>S/ <?php echo number_format($paquete['costo_envio'], 2); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Repartidor Asignado -->
                <?php if ($paquete['repartidor_id']): ?>
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5><i class="bi bi-truck"></i> Repartidor Asignado</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-0">
                                <strong><?php echo $paquete['repartidor_nombre'] . ' ' . $paquete['repartidor_apellido']; ?></strong>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Información de Rezago -->
                <?php if ($rezagado): ?>
                <div class="col-12">
                    <div class="card border-danger mb-3">
                        <div class="card-header bg-danger text-white">
                            <h5><i class="bi bi-exclamation-triangle"></i> Información de Rezago</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Motivo:</strong> <?php echo ucfirst(str_replace('_', ' ', $rezagado['motivo'])); ?></p>
                                    <p><strong>Descripción:</strong> <?php echo $rezagado['descripcion_motivo']; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Intentos Realizados:</strong> <?php echo $rezagado['intentos_realizados']; ?></p>
                                    <p><strong>Fecha de Rezago:</strong> <?php echo date('d/m/Y H:i', strtotime($rezagado['fecha_rezago'])); ?></p>
                                    <?php if ($rezagado['proximo_intento']): ?>
                                    <p><strong>Próximo Intento:</strong> <?php echo date('d/m/Y', strtotime($rezagado['proximo_intento'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Historial de Entregas -->
                <?php if (count($entregas) > 0): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-clock-history"></i> Historial de Entregas</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Repartidor</th>
                                            <th>Tipo</th>
                                            <th>Receptor</th>
                                            <th>Observaciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($entregas as $e): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i', strtotime($e['fecha_entrega'])); ?></td>
                                            <td><?php echo $paquete['repartidor_nombre'] . ' ' . $paquete['repartidor_apellido']; ?></td>
                                            <td>
                                                <?php
                                                $tipo_badges = ['exitosa' => 'success', 'parcial' => 'warning', 'rechazada' => 'danger', 'no_encontrado' => 'secondary'];
                                                ?>
                                                <span class="badge bg-<?php echo $tipo_badges[$e['tipo_entrega']] ?? 'secondary'; ?>">
                                                    <?php echo ucfirst($e['tipo_entrega']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $e['receptor_nombre'] ?? '-'; ?></td>
                                            <td><?php echo $e['observaciones'] ?? '-'; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Notas -->
                <?php if ($paquete['notas']): ?>
                <div class="col-12 mt-3">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-sticky"></i> Notas</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($paquete['notas'])); ?></p>
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
