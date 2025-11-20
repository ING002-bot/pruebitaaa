<?php
require_once '../config/config.php';
requireRole(['admin', 'asistente']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    echo '<div class="alert alert-danger">ID inválido</div>';
    exit;
}

$db = Database::getInstance()->getConnection();
$sql = "SELECT errores FROM importaciones_savar WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$id]);
$importacion = $stmt->fetch();

if (!$importacion) {
    echo '<div class="alert alert-danger">Importación no encontrada</div>';
    exit;
}
?>

<div class="alert alert-danger">
    <h6><i class="bi bi-exclamation-triangle"></i> Errores de Importación #<?php echo $id; ?></h6>
    <hr>
    <?php if ($importacion['errores']): ?>
        <pre class="mb-0"><?php echo htmlspecialchars($importacion['errores']); ?></pre>
    <?php else: ?>
        <p class="mb-0">No hay errores registrados para esta importación</p>
    <?php endif; ?>
</div>

<div class="alert alert-info">
    <h6><i class="bi bi-lightbulb"></i> Soluciones comunes:</h6>
    <ul class="mb-0 small">
        <li><strong>Credenciales incorrectas:</strong> Verifica usuario y contraseña en <code>python/savar_importer.py</code></li>
        <li><strong>ChromeDriver no encontrado:</strong> Ejecuta <code>pip install webdriver-manager</code></li>
        <li><strong>Timeout:</strong> Aumenta los valores de timeout en el script</li>
        <li><strong>Error de conexión:</strong> Verifica que SAVAR esté accesible: <a href="https://app.savarexpress.com.pe" target="_blank">https://app.savarexpress.com.pe</a></li>
    </ul>
</div>
