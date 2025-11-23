<?php
require_once '../config/config.php';
requireRole(['admin']);

try {
    $db = Database::getInstance()->getConnection();
    $problemas = [];
    $verificaciones = 0;
    
    // 1. Verificar tablas requeridas
    $tablas_requeridas = [
        'usuarios', 'paquetes', 'rutas', 'entregas', 
        'ruta_paquetes', 'paquetes_rezagados', 'zonas_tarifas',
        'pagos', 'ingresos', 'gastos', 'caja_chica'
    ];
    
    foreach ($tablas_requeridas as $tabla) {
        $result = $db->query("SHOW TABLES LIKE '$tabla'");
        if ($result->num_rows == 0) {
            $problemas[] = "Tabla faltante: $tabla";
        }
        $verificaciones++;
    }
    
    // 2. Verificar integridad referencial
    // Usuarios sin rol válido
    $result = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE rol NOT IN ('admin', 'asistente', 'repartidor')");
    $row = $result->fetch_assoc();
    if ($row['total'] > 0) {
        $problemas[] = "{$row['total']} usuarios con rol inválido";
    }
    $verificaciones++;
    
    // Paquetes sin repartidor válido (si está asignado)
    $result = $db->query("SELECT COUNT(*) as total FROM paquetes WHERE repartidor_id IS NOT NULL AND repartidor_id NOT IN (SELECT id FROM usuarios WHERE rol = 'repartidor')");
    $row = $result->fetch_assoc();
    if ($row['total'] > 0) {
        $problemas[] = "{$row['total']} paquetes asignados a repartidores inexistentes";
    }
    $verificaciones++;
    
    // Entregas sin paquete válido
    $result = $db->query("SELECT COUNT(*) as total FROM entregas WHERE paquete_id NOT IN (SELECT id FROM paquetes)");
    $row = $result->fetch_assoc();
    if ($row['total'] > 0) {
        $problemas[] = "{$row['total']} entregas huérfanas (sin paquete asociado)";
    }
    $verificaciones++;
    
    // 3. Verificar datos inconsistentes
    // Paquetes entregados sin registro en entregas
    $result = $db->query("SELECT COUNT(*) as total FROM paquetes WHERE estado = 'entregado' AND id NOT IN (SELECT DISTINCT paquete_id FROM entregas)");
    $row = $result->fetch_assoc();
    if ($row['total'] > 0) {
        $problemas[] = "{$row['total']} paquetes marcados como entregados sin registro de entrega";
    }
    $verificaciones++;
    
    // 4. Verificar permisos de directorios
    $directorios_requeridos = [
        '../uploads/entregas/',
        '../uploads/perfiles/',
        '../uploads/gastos/',
        '../uploads/caja_chica/',
        '../backups/'
    ];
    
    foreach ($directorios_requeridos as $dir) {
        if (!is_dir($dir)) {
            $problemas[] = "Directorio faltante: $dir";
            // Crear el directorio
            mkdir($dir, 0777, true);
        } elseif (!is_writable($dir)) {
            $problemas[] = "Directorio sin permisos de escritura: $dir";
        }
        $verificaciones++;
    }
    
    // 5. Optimizar tablas
    foreach ($tablas_requeridas as $tabla) {
        $result = $db->query("SHOW TABLES LIKE '$tabla'");
        if ($result->num_rows > 0) {
            $db->query("OPTIMIZE TABLE $tabla");
        }
    }
    
    // Generar reporte
    if (empty($problemas)) {
        $_SESSION['success_message'] = sprintf(
            'Verificación completada exitosamente. %d verificaciones realizadas. No se encontraron problemas. Tablas optimizadas.',
            $verificaciones
        );
    } else {
        $reporte = sprintf(
            'Verificación completada. %d verificaciones realizadas. Se encontraron %d problemas:<br>',
            $verificaciones,
            count($problemas)
        );
        $reporte .= '<ul><li>' . implode('</li><li>', $problemas) . '</li></ul>';
        $_SESSION['error_message'] = $reporte;
    }
    
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error durante la verificación: ' . $e->getMessage();
}

header('Location: configuracion.php');
exit;
