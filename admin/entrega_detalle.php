<?php
require_once '../config/config.php';
requireRole(['admin', 'asistente', 'repartidor']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    echo '<div class="alert alert-danger">ID inválido</div>';
    exit;
}

$db = Database::getInstance()->getConnection();

// Si es repartidor, solo puede ver sus propias entregas
$whereSql = "WHERE e.id = ?";
$params = [$id];

if ($_SESSION['rol'] === 'repartidor') {
    $whereSql .= " AND e.repartidor_id = ?";
    $params[] = $_SESSION['usuario_id'];
}

$sql = "SELECT e.*, p.codigo_seguimiento, p.destinatario_nombre, p.destinatario_telefono, p.direccion_completa,
        u.nombre as repartidor_nombre, u.apellido as repartidor_apellido, u.telefono as repartidor_telefono
        FROM entregas e
        LEFT JOIN paquetes p ON e.paquete_id = p.id
        LEFT JOIN usuarios u ON e.repartidor_id = u.id
        $whereSql";
$stmt = $db->prepare($sql);

// Determinar tipos y vincular parámetros
if (count($params) === 1) {
    $stmt->bind_param("i", $params[0]);
} else if (count($params) === 2) {
    $stmt->bind_param("ii", $params[0], $params[1]);
}

$stmt->execute();
$entrega = Database::getInstance()->fetch($stmt);

if (!$entrega) {
    echo '<div class="alert alert-danger">Entrega no encontrada</div>';
    exit;
}
?>

<div class="row">
    <div class="col-md-6">
        <h6><i class="bi bi-box"></i> Información del Paquete</h6>
        <table class="table table-sm table-bordered">
            <tr>
                <th width="40%">Código:</th>
                <td><strong><?php echo $entrega['codigo_seguimiento']; ?></strong></td>
            </tr>
            <tr>
                <th>Destinatario:</th>
                <td><?php echo $entrega['destinatario_nombre']; ?></td>
            </tr>
            <tr>
                <th>Teléfono:</th>
                <td><?php echo $entrega['destinatario_telefono']; ?></td>
            </tr>
            <tr>
                <th>Dirección:</th>
                <td><?php echo $entrega['direccion_completa']; ?></td>
            </tr>
        </table>

        <h6 class="mt-3"><i class="bi bi-person-check"></i> Información del Receptor</h6>
        <table class="table table-sm table-bordered">
            <tr>
                <th width="40%">Nombre:</th>
                <td><?php echo $entrega['receptor_nombre'] ?: '-'; ?></td>
            </tr>
            <tr>
                <th>DNI:</th>
                <td><?php echo $entrega['receptor_dni'] ?: '-'; ?></td>
            </tr>
        </table>
    </div>

    <div class="col-md-6">
        <h6><i class="bi bi-truck"></i> Información de Entrega</h6>
        <table class="table table-sm table-bordered">
            <tr>
                <th width="40%">Repartidor:</th>
                <td><?php echo $entrega['repartidor_nombre'] . ' ' . $entrega['repartidor_apellido']; ?></td>
            </tr>
            <tr>
                <th>Teléfono:</th>
                <td><?php echo $entrega['repartidor_telefono']; ?></td>
            </tr>
            <tr>
                <th>Fecha:</th>
                <td><?php echo formatDate($entrega['fecha_entrega']); ?></td>
            </tr>
            <tr>
                <th>Tipo:</th>
                <td>
                    <?php
                    $badges = ['exitosa' => 'success', 'rechazada' => 'danger', 'parcial' => 'warning'];
                    $badge = $badges[$entrega['tipo_entrega']] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?php echo $badge; ?>"><?php echo ucfirst($entrega['tipo_entrega']); ?></span>
                </td>
            </tr>
            <?php if (!empty($entrega['observaciones']) && $entrega['tipo_entrega'] == 'rechazada'): ?>
            <tr>
                <th>Observaciones:</th>
                <td class="text-danger"><?php echo $entrega['observaciones']; ?></td>
            </tr>
            <?php endif; ?>
        </table>

        <?php if ($entrega['observaciones']): ?>
        <h6 class="mt-3"><i class="bi bi-chat-left-text"></i> Observaciones</h6>
        <div class="alert alert-info">
            <?php echo nl2br(htmlspecialchars($entrega['observaciones'])); ?>
        </div>
        <?php endif; ?>

        <?php if ($entrega['foto_entrega']): ?>
        <h6 class="mt-3"><i class="bi bi-camera"></i> Foto de Entrega</h6>
        <img src="../uploads/entregas/<?php echo $entrega['foto_entrega']; ?>" class="img-fluid rounded" alt="Foto">
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($entrega['latitud_entrega']) && !empty($entrega['longitud_entrega'])): ?>
<div class="row mt-3">
    <div class="col-12">
        <h6><i class="bi bi-geo-alt"></i> Ubicación GPS</h6>
        <p class="small text-muted">
            Latitud: <?php echo $entrega['latitud_entrega']; ?> | 
            Longitud: <?php echo $entrega['longitud_entrega']; ?>
        </p>
        <a href="https://www.google.com/maps?q=<?php echo $entrega['latitud_entrega']; ?>,<?php echo $entrega['longitud_entrega']; ?>" 
           target="_blank" class="btn btn-sm btn-success">
            <i class="bi bi-map"></i> Ver en Google Maps
        </a>
    </div>
</div>
<?php endif; ?>
