<?php
require_once '../config/config.php';
requireRole(['admin']);

$action = $_GET['action'] ?? '';

if ($action === 'create') {
    try {
        // Configuración de la base de datos
        $host = DB_HOST;
        $user = DB_USER;
        $pass = DB_PASS;
        $dbname = DB_NAME;
        
        // Crear directorio de backups si no existe
        $backup_dir = '../backups/';
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0777, true);
        }
        
        // Nombre del archivo de backup
        $filename = 'backup_' . $dbname . '_' . date('Y-m-d_His') . '.sql';
        $filepath = $backup_dir . $filename;
        
        // Ruta de mysqldump
        $mysqldump_path = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
        
        // Verificar si existe mysqldump
        if (!file_exists($mysqldump_path)) {
            throw new Exception('No se encontró mysqldump en: ' . $mysqldump_path);
        }
        
        // Comando para crear el backup
        $command = sprintf(
            '"%s" --user=%s --password=%s --host=%s %s > "%s" 2>&1',
            $mysqldump_path,
            $user,
            $pass,
            $host,
            $dbname,
            $filepath
        );
        
        // Ejecutar el comando
        exec($command, $output, $return_var);
        
        if ($return_var !== 0) {
            throw new Exception('Error al crear el backup: ' . implode("\n", $output));
        }
        
        // Verificar que se creó el archivo
        if (!file_exists($filepath) || filesize($filepath) == 0) {
            throw new Exception('El archivo de backup está vacío o no se creó correctamente');
        }
        
        // Registrar en log (si existe la tabla)
        try {
            $db = Database::getInstance()->getConnection();
            $check_table = $db->query("SHOW TABLES LIKE 'logs_sistema'");
            if ($check_table && $check_table->num_rows > 0) {
                $stmt = $db->prepare("INSERT INTO logs_sistema (usuario_id, accion, descripcion, ip_address) VALUES (?, ?, ?, ?)");
                if ($stmt) {
                    $usuario_id = $_SESSION['usuario_id'];
                    $accion = 'backup_creado';
                    $descripcion = 'Backup creado: ' . $filename . ' (' . formatBytes(filesize($filepath)) . ')';
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $stmt->bind_param("isss", $usuario_id, $accion, $descripcion, $ip);
                    $stmt->execute();
                }
            }
        } catch (Exception $e) {
            // Ignorar error de log
        }
        
        $_SESSION['success_message'] = 'Backup creado correctamente: ' . $filename;
        
        // Descargar el archivo
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Error al crear backup: ' . $e->getMessage();
        header('Location: configuracion.php');
        exit;
    }
    
} elseif ($action === 'list') {
    // Listar backups disponibles
    $backup_dir = '../backups/';
    $backups = [];
    
    if (is_dir($backup_dir)) {
        $files = scandir($backup_dir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                $backups[] = [
                    'nombre' => $file,
                    'tamaño' => filesize($backup_dir . $file),
                    'fecha' => filemtime($backup_dir . $file)
                ];
            }
        }
    }
    
    // Ordenar por fecha descendente
    usort($backups, function($a, $b) {
        return $b['fecha'] - $a['fecha'];
    });
    
    header('Content-Type: application/json');
    echo json_encode($backups);
    exit;
    
} elseif ($action === 'download') {
    $filename = $_GET['file'] ?? '';
    $filepath = '../backups/' . basename($filename);
    
    if (!file_exists($filepath)) {
        $_SESSION['error_message'] = 'Archivo de backup no encontrado';
        header('Location: configuracion.php');
        exit;
    }
    
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
    exit;
    
} elseif ($action === 'delete') {
    $filename = $_GET['file'] ?? '';
    $filepath = '../backups/' . basename($filename);
    
    if (file_exists($filepath)) {
        unlink($filepath);
        $_SESSION['success_message'] = 'Backup eliminado correctamente';
    } else {
        $_SESSION['error_message'] = 'Archivo de backup no encontrado';
    }
    
    header('Location: configuracion.php');
    exit;
    
} else {
    $_SESSION['error_message'] = 'Acción no válida';
    header('Location: configuracion.php');
    exit;
}

// Función auxiliar para formatear bytes
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
