<?php
session_start();
require_once '../config/conexion.php';
require_once '../config/Database.php';

// Verificar autenticación y rol
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'asistente') {
    $_SESSION['error'] = "Acceso denegado";
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: importar_savar.php");
    exit();
}

$db = Database::getInstance()->getConnection();

// Obtener fechas del formulario
$fecha_inicio = $_POST['fecha_inicio'] ?? date('Y-m-d', strtotime('-1 day'));
$fecha_fin = $_POST['fecha_fin'] ?? date('Y-m-d');
$user_id = $_SESSION['user_id'];

try {
    // Registrar la importación en la base de datos
    $sql = "INSERT INTO importaciones_savar (fecha_importacion, estado, procesado_por, fecha_inicio, fecha_fin) 
            VALUES (NOW(), 'procesando', ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("iss", $user_id, $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $importacion_id = $db->insert_id;
    
    // Construir el comando Python con las fechas
    $python_path = "python"; // O especificar ruta completa si es necesario
    $script_path = realpath("../python/savar_importer.py");
    
    if (!file_exists($script_path)) {
        throw new Exception("Script de Python no encontrado: " . $script_path);
    }
    
    // Preparar el comando con argumentos de fecha
    $command = escapeshellcmd($python_path) . " " . escapeshellarg($script_path) . 
               " --fecha-inicio " . escapeshellarg($fecha_inicio) . 
               " --fecha-fin " . escapeshellarg($fecha_fin) .
               " --importacion-id " . escapeshellarg($importacion_id);
    
    // Ejecutar el script en segundo plano
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows
        $full_command = "cd /d " . escapeshellarg(dirname($script_path)) . " && " . $command . " > nul 2>&1 &";
        pclose(popen("start /B " . $full_command, "r"));
    } else {
        // Linux/Unix
        $full_command = "cd " . escapeshellarg(dirname($script_path)) . " && " . $command . " > /dev/null 2>&1 &";
        exec($full_command);
    }
    
    $_SESSION['success'] = "Importación iniciada correctamente. El proceso se ejecuta en segundo plano.";
    
    // Log de la acción
    error_log("SAVAR Import iniciado por usuario ID: $user_id, Importación ID: $importacion_id");
    
} catch (Exception $e) {
    // Actualizar el estado de la importación a error si ya se creó
    if (isset($importacion_id)) {
        $sql_error = "UPDATE importaciones_savar SET estado = 'error', errores = ? WHERE id = ?";
        $stmt_error = $db->prepare($sql_error);
        $error_msg = $e->getMessage();
        $stmt_error->bind_param("si", $error_msg, $importacion_id);
        $stmt_error->execute();
    }
    
    $_SESSION['error'] = "Error al ejecutar la importación: " . $e->getMessage();
    error_log("Error en SAVAR Import: " . $e->getMessage());
}

header("Location: importar_savar.php");
exit();
?>