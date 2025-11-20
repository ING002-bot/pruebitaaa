<?php
require_once '../config/config.php';
requireRole(['admin', 'asistente']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    echo '<div class="alert alert-danger">ID inválido</div>';
    exit;
}

$db = Database::getInstance()->getConnection();
$sql = "SELECT * FROM importaciones_savar WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$id]);
$importacion = $stmt->fetch();

if (!$importacion) {
    echo '<div class="alert alert-danger">Importación no encontrada</div>';
    exit;
}

// Decodificar JSON
$datos = json_decode($importacion['datos_json'], true);
?>

<div class="row">
    <div class="col-md-6">
        <h6>Información General</h6>
        <table class="table table-sm table-bordered">
            <tr>
                <th>ID Importación:</th>
                <td><?php echo $importacion['id']; ?></td>
            </tr>
            <tr>
                <th>Fecha:</th>
                <td><?php echo formatDate($importacion['fecha_importacion']); ?></td>
            </tr>
            <tr>
                <th>Estado:</th>
                <td><span class="badge bg-<?php echo $importacion['estado'] == 'completado' ? 'success' : 'warning'; ?>">
                    <?php echo ucfirst($importacion['estado']); ?>
                </span></td>
            </tr>
            <tr>
                <th>Total Registros:</th>
                <td><?php echo $importacion['total_registros']; ?></td>
            </tr>
            <tr>
                <th>Procesados:</th>
                <td><span class="badge bg-success"><?php echo $importacion['registros_procesados']; ?></span></td>
            </tr>
            <tr>
                <th>Fallidos:</th>
                <td><span class="badge bg-danger"><?php echo $importacion['registros_fallidos']; ?></span></td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6>Datos JSON</h6>
        <?php if ($datos && is_array($datos)): ?>
            <div class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;">
                <pre class="mb-0 small"><?php echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
            </div>
        <?php else: ?>
            <p class="text-muted">No hay datos JSON disponibles</p>
        <?php endif; ?>
    </div>
</div>

<?php if ($importacion['errores']): ?>
    <div class="row mt-3">
        <div class="col-12">
            <h6>Errores</h6>
            <div class="alert alert-danger">
                <?php echo nl2br(htmlspecialchars($importacion['errores'])); ?>
            </div>
        </div>
    </div>
<?php endif; ?>
