<?php
require_once '../config/config.php';
requireRole(['admin']);

try {
    $directorios_cache = [
        '../cache/',
        '../tmp/',
        '../uploads/temp/'
    ];
    
    $archivos_eliminados = 0;
    $espacio_liberado = 0;
    
    foreach ($directorios_cache as $dir) {
        if (is_dir($dir)) {
            $archivos = glob($dir . '*');
            foreach ($archivos as $archivo) {
                if (is_file($archivo)) {
                    $espacio_liberado += filesize($archivo);
                    unlink($archivo);
                    $archivos_eliminados++;
                }
            }
        }
    }
    
    // Limpiar sesiones antiguas (opcional)
    $session_path = session_save_path();
    if (!empty($session_path) && is_dir($session_path)) {
        $session_files = glob($session_path . '/sess_*');
        $tiempo_limite = time() - (24 * 60 * 60); // 24 horas
        
        foreach ($session_files as $session_file) {
            if (filemtime($session_file) < $tiempo_limite) {
                $espacio_liberado += filesize($session_file);
                unlink($session_file);
                $archivos_eliminados++;
            }
        }
    }
    
    $_SESSION['success_message'] = sprintf(
        'Caché limpiado correctamente. %d archivos eliminados, %s liberados.',
        $archivos_eliminados,
        formatBytes($espacio_liberado)
    );
    
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error al limpiar caché: ' . $e->getMessage();
}

header('Location: configuracion.php');
exit;

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
