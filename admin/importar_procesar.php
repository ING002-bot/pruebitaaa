<?php
require_once '../config/config.php';
requireRole(['admin', 'asistente']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . 'admin/importar.php');
    exit;
}

$fecha_inicio = sanitize($_POST['fecha_inicio']);
$fecha_fin = sanitize($_POST['fecha_fin']);

// Validar fechas
if (!$fecha_inicio || !$fecha_fin) {
    setFlashMessage('danger', 'Las fechas son obligatorias');
    redirect(APP_URL . 'admin/importar.php');
    exit;
}

// Registrar intento de importación
$db = Database::getInstance()->getConnection();
$sql = "INSERT INTO importaciones_savar (total_registros, estado, procesado_por) VALUES (0, 'procesando', ?)";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$importacion_id = $db->insert_id;

// Preparar comando Python
$pythonPath = 'python'; // o 'python3' en algunos sistemas
$scriptPath = __DIR__ . '/../python/savar_importer.py';
$logPath = __DIR__ . '/../python/import_log.txt';

// Comando completo (modificar las fechas en el script sería mejor, pero como workaround usamos variables de entorno)
$command = sprintf(
    '%s "%s" 2>&1',
    $pythonPath,
    $scriptPath
);

// Verificar si exec() está habilitado
if (!function_exists('exec')) {
    // Actualizar importación como error
    $sql = "UPDATE importaciones_savar SET estado = 'error', errores = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $error_msg = 'La función exec() está deshabilitada en PHP. Ejecuta el script manualmente desde terminal.';
    $stmt->bind_param("si", $error_msg, $importacion_id);
    $stmt->execute();
    
    setFlashMessage('danger', 'No se puede ejecutar el script desde el navegador. La función exec() está deshabilitada.<br>Por favor, ejecuta manualmente: <code>python python/savar_importer.py</code>');
    redirect(APP_URL . 'admin/importar.php');
    exit;
}

// Intentar ejecutar el script
try {
    // Ejecutar en segundo plano (Windows)
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $fullCommand = "start /B $command > \"$logPath\" 2>&1";
        pclose(popen($fullCommand, 'r'));
        $message = 'Importación iniciada en segundo plano. El proceso puede tardar varios minutos.';
        $status = 'info';
    } else {
        // Linux/Mac
        $fullCommand = "$command > \"$logPath\" 2>&1 &";
        exec($fullCommand);
        $message = 'Importación iniciada en segundo plano. El proceso puede tardar varios minutos.';
        $status = 'info';
    }
    
    // Registrar actividad
    logActivity('Importación SAVAR iniciada', 'importaciones_savar', $importacion_id);
    
    setFlashMessage($status, $message . '<br><small>Revisa el historial de importaciones en unos minutos para ver el resultado.</small>');
    
} catch (Exception $e) {
    // Error al ejecutar
    $sql = "UPDATE importaciones_savar SET estado = 'error', errores = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute(['Error al ejecutar script: ' . $e->getMessage(), $importacion_id]);
    
    setFlashMessage('danger', 'Error al ejecutar la importación: ' . $e->getMessage() . '<br>Intenta ejecutar manualmente: <code>python python/savar_importer.py</code>');
}

redirect(APP_URL . 'admin/importar.php');
