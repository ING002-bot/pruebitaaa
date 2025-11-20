<?php
require_once '../config/config.php';
requireRole(['admin', 'asistente']);

$id = $_GET['id'] ?? 0;
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    SELECT p.*, 
           u.nombre as repartidor_nombre, 
           u.apellido as repartidor_apellido,
           e.fecha_entrega, 
           e.tipo_entrega,
           e.receptor_nombre,
           e.foto_entrega
    FROM paquetes p
    LEFT JOIN usuarios u ON p.repartidor_id = u.id
    LEFT JOIN entregas e ON e.paquete_id = p.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$paquete = $stmt->fetch();

if (!$paquete) {
    echo '<div class="alert alert-danger">Paquete no encontrado</div>';
    exit;
}
?>

<div class="row">
    <div class="col-md-6">
        <h6 class="text-muted mb-3">Información del Paquete</h6>
        <table class="table table-sm">
            <tr>
                <th width="40%">Código:</th>
                <td><strong><?php echo $paquete['codigo_seguimiento']; ?></strong></td>
            </tr>
            <tr>
                <th>Código SAVAR:</th>
                <td><?php echo $paquete['codigo_savar'] ?: '-'; ?></td>
            </tr>
            <tr>
                <th>Estado:</th>
                <td>
                    <?php
                    $badgeClass = [
                        'pendiente' => 'bg-secondary',
                        'en_ruta' => 'bg-warning',
                        'entregado' => 'bg-success',
                        'rezagado' => 'bg-danger',
                        'devuelto' => 'bg-info'
                    ];
                    ?>
                    <span class="badge <?php echo $badgeClass[$paquete['estado']] ?? 'bg-secondary'; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $paquete['estado'])); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>Prioridad:</th>
                <td>
                    <span class="badge <?php echo $paquete['prioridad'] === 'express' ? 'bg-danger' : ($paquete['prioridad'] === 'urgente' ? 'bg-warning' : 'bg-secondary'); ?>">
                        <?php echo ucfirst($paquete['prioridad']); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>Peso:</th>
                <td><?php echo $paquete['peso'] ? $paquete['peso'] . ' kg' : '-'; ?></td>
            </tr>
            <tr>
                <th>Dimensiones:</th>
                <td><?php echo $paquete['dimensiones'] ?: '-'; ?></td>
            </tr>
            <tr>
                <th>Valor Declarado:</th>
                <td><?php echo $paquete['valor_declarado'] ? formatCurrency($paquete['valor_declarado']) : '-'; ?></td>
            </tr>
            <tr>
                <th>Costo Envío:</th>
                <td><strong><?php echo formatCurrency($paquete['costo_envio']); ?></strong></td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6 class="text-muted mb-3">Información del Destinatario</h6>
        <table class="table table-sm">
            <tr>
                <th width="40%">Nombre:</th>
                <td><?php echo $paquete['destinatario_nombre']; ?></td>
            </tr>
            <tr>
                <th>Teléfono:</th>
                <td><?php echo $paquete['destinatario_telefono']; ?></td>
            </tr>
            <tr>
                <th>Email:</th>
                <td><?php echo $paquete['destinatario_email'] ?: '-'; ?></td>
            </tr>
            <tr>
                <th>Dirección:</th>
                <td><?php echo $paquete['direccion_completa']; ?></td>
            </tr>
            <tr>
                <th>Ciudad:</th>
                <td><?php echo $paquete['ciudad'] ?: '-'; ?></td>
            </tr>
            <tr>
                <th>Provincia:</th>
                <td><?php echo $paquete['provincia'] ?: '-'; ?></td>
            </tr>
            <tr>
                <th>Código Postal:</th>
                <td><?php echo $paquete['codigo_postal'] ?: '-'; ?></td>
            </tr>
        </table>
    </div>
</div>

<hr>

<div class="row">
    <div class="col-md-6">
        <h6 class="text-muted mb-3">Asignación y Fechas</h6>
        <table class="table table-sm">
            <tr>
                <th width="40%">Repartidor:</th>
                <td>
                    <?php if ($paquete['repartidor_nombre']): ?>
                        <?php echo $paquete['repartidor_nombre'] . ' ' . $paquete['repartidor_apellido']; ?>
                    <?php else: ?>
                        <span class="badge bg-secondary">Sin asignar</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Fecha Recepción:</th>
                <td><?php echo formatDateTime($paquete['fecha_recepcion']); ?></td>
            </tr>
            <tr>
                <th>Fecha Asignación:</th>
                <td><?php echo $paquete['fecha_asignacion'] ? formatDateTime($paquete['fecha_asignacion']) : '-'; ?></td>
            </tr>
            <tr>
                <th>Fecha Entrega:</th>
                <td><?php echo $paquete['fecha_entrega'] ? formatDateTime($paquete['fecha_entrega']) : '-'; ?></td>
            </tr>
            <tr>
                <th>Intentos Entrega:</th>
                <td><span class="badge bg-info"><?php echo $paquete['intentos_entrega']; ?></span></td>
            </tr>
        </table>
    </div>
    
    <?php if ($paquete['estado'] === 'entregado' && $paquete['fecha_entrega']): ?>
    <div class="col-md-6">
        <h6 class="text-muted mb-3">Información de Entrega</h6>
        <table class="table table-sm">
            <tr>
                <th width="40%">Tipo Entrega:</th>
                <td>
                    <span class="badge <?php echo $paquete['tipo_entrega'] === 'exitosa' ? 'bg-success' : 'bg-warning'; ?>">
                        <?php echo ucfirst($paquete['tipo_entrega']); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>Receptor:</th>
                <td><?php echo $paquete['receptor_nombre'] ?: '-'; ?></td>
            </tr>
            <?php if ($paquete['foto_entrega']): ?>
            <tr>
                <th>Foto Entrega:</th>
                <td>
                    <img src="../uploads/entregas/<?php echo $paquete['foto_entrega']; ?>" 
                         alt="Foto de entrega" 
                         class="img-thumbnail" 
                         style="max-width: 200px; cursor: pointer;"
                         onclick="window.open(this.src, '_blank')">
                </td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php if ($paquete['notas']): ?>
<hr>
<div class="row">
    <div class="col-12">
        <h6 class="text-muted mb-2">Notas</h6>
        <div class="alert alert-info">
            <?php echo nl2br(htmlspecialchars($paquete['notas'])); ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($paquete['descripcion']): ?>
<div class="row">
    <div class="col-12">
        <h6 class="text-muted mb-2">Descripción del Contenido</h6>
        <p><?php echo nl2br(htmlspecialchars($paquete['descripcion'])); ?></p>
    </div>
</div>
<?php endif; ?>
