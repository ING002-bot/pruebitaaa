<?php
/**
 * Script de Verificación y Corrección Automática
 * Detecta y corrige errores comunes en el sistema
 */

require_once 'config/config.php';

// Solo admin
if (php_sapi_name() !== 'cli') {
    session_start();
    if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
        die('Acceso denegado');
    }
}

echo "<h2>🔍 Verificación Completa del Sistema</h2>";
echo "<pre>";

$errores = [];
$advertencias = [];
$corregidos = [];

// 1. Verificar estructura de base de datos
echo "=== VERIFICANDO BASE DE DATOS ===\n";

$db = Database::getInstance()->getConnection();

$tablas_requeridas = [
    'usuarios', 'paquetes', 'rutas', 'entregas', 'pagos',
    'ingresos', 'gastos', 'notificaciones', 'logs_sistema'
];

foreach ($tablas_requeridas as $tabla) {
    $result = $db->query("SHOW TABLES LIKE '$tabla'");
    if ($result->num_rows === 0) {
        $errores[] = "❌ Tabla '$tabla' no existe";
    } else {
        echo "✓ Tabla '$tabla' OK\n";
    }
}

// 2. Verificar directorios de uploads
echo "\n=== VERIFICANDO DIRECTORIOS ===\n";

$directorios = [
    'uploads/entregas',
    'uploads/gastos',
    'uploads/caja_chica',
    'uploads/usuarios'
];

foreach ($directorios as $dir) {
    $ruta = __DIR__ . '/' . $dir;
    if (!is_dir($ruta)) {
        mkdir($ruta, 0777, true);
        $corregidos[] = "✅ Directorio '$dir' creado";
        echo "✓ Directorio '$dir' creado\n";
    } else {
        if (!is_writable($ruta)) {
            $advertencias[] = "⚠️ Directorio '$dir' sin permisos de escritura";
        } else {
            echo "✓ Directorio '$dir' OK\n";
        }
    }
}

// 3. Verificar configuración
echo "\n=== VERIFICANDO CONFIGURACIÓN ===\n";

if (!defined('DB_NAME')) {
    $errores[] = "❌ Constante DB_NAME no definida";
} else {
    echo "✓ DB_NAME: " . DB_NAME . "\n";
}

if (!defined('APP_URL')) {
    $advertencias[] = "⚠️ APP_URL no definida";
} else {
    echo "✓ APP_URL: " . APP_URL . "\n";
}

// 4. Verificar permisos de archivos críticos
echo "\n=== VERIFICANDO PERMISOS ===\n";

$archivos_criticos = [
    'config/config.php',
    'config/database.php',
    'actualizar_tabla_pagos.php',
    'actualizar_tabla_gastos.php'
];

foreach ($archivos_criticos as $archivo) {
    if (file_exists($archivo)) {
        if (is_writable($archivo)) {
            $advertencias[] = "⚠️ Archivo '$archivo' es escribible (riesgo de seguridad)";
        } else {
            echo "✓ Archivo '$archivo' protegido\n";
        }
    }
}

// 5. Verificar integridad de datos
echo "\n=== VERIFICANDO INTEGRIDAD DE DATOS ===\n";

// Verificar paquetes sin repartidor asignado en estado "en_ruta"
$result = $db->query("SELECT COUNT(*) as total FROM paquetes WHERE estado='en_ruta' AND repartidor_id IS NULL");
$row = $result->fetch_assoc();
if ($row['total'] > 0) {
    $advertencias[] = "⚠️ {$row['total']} paquetes en ruta sin repartidor asignado";
    echo "⚠️ {$row['total']} paquetes en ruta sin repartidor\n";
}

// Verificar entregas sin foto
$result = $db->query("SELECT COUNT(*) as total FROM entregas WHERE foto_entrega IS NULL AND tipo_entrega='exitosa'");
$row = $result->fetch_assoc();
if ($row['total'] > 0) {
    $advertencias[] = "⚠️ {$row['total']} entregas exitosas sin foto";
    echo "⚠️ {$row['total']} entregas sin foto\n";
}

// 6. Verificar índices
echo "\n=== VERIFICANDO ÍNDICES ===\n";

$indices_recomendados = [
    ['tabla' => 'paquetes', 'columna' => 'codigo_seguimiento'],
    ['tabla' => 'paquetes', 'columna' => 'estado'],
    ['tabla' => 'paquetes', 'columna' => 'repartidor_id'],
    ['tabla' => 'entregas', 'columna' => 'fecha_entrega'],
    ['tabla' => 'pagos', 'columna' => 'fecha_pago'],
];

foreach ($indices_recomendados as $idx) {
    $result = $db->query("SHOW INDEX FROM {$idx['tabla']} WHERE Column_name = '{$idx['columna']}'");
    if ($result->num_rows > 0) {
        echo "✓ Índice en {$idx['tabla']}.{$idx['columna']}\n";
    } else {
        $advertencias[] = "⚠️ Falta índice en {$idx['tabla']}.{$idx['columna']}";
    }
}

// 7. Optimizar tablas
echo "\n=== OPTIMIZANDO TABLAS ===\n";

foreach ($tablas_requeridas as $tabla) {
    $result = $db->query("SHOW TABLES LIKE '$tabla'");
    if ($result->num_rows > 0) {
        $db->query("OPTIMIZE TABLE $tabla");
        echo "✓ Tabla '$tabla' optimizada\n";
    }
}

// 8. Limpiar datos antiguos
echo "\n=== LIMPIANDO DATOS ANTIGUOS ===\n";

// Eliminar notificaciones leídas de más de 30 días
$result = $db->query("DELETE FROM notificaciones WHERE leida = 1 AND fecha_creacion < DATE_SUB(NOW(), INTERVAL 30 DAY)");
$eliminadas = $db->affected_rows;
if ($eliminadas > 0) {
    echo "✓ $eliminadas notificaciones antiguas eliminadas\n";
    $corregidos[] = "✅ $eliminadas notificaciones antiguas eliminadas";
}

// Eliminar logs de más de 90 días
$result = $db->query("DELETE FROM logs_sistema WHERE fecha_accion < DATE_SUB(NOW(), INTERVAL 90 DAY)");
$eliminadas = $db->affected_rows;
if ($eliminadas > 0) {
    echo "✓ $eliminadas logs antiguos eliminados\n";
    $corregidos[] = "✅ $eliminadas logs antiguos eliminados";
}

// RESUMEN
echo "\n" . str_repeat("=", 70) . "\n";
echo "RESUMEN DE VERIFICACIÓN\n";
echo str_repeat("=", 70) . "\n\n";

echo "🔴 ERRORES CRÍTICOS: " . count($errores) . "\n";
foreach ($errores as $error) {
    echo "   $error\n";
}

echo "\n🟡 ADVERTENCIAS: " . count($advertencias) . "\n";
foreach ($advertencias as $adv) {
    echo "   $adv\n";
}

echo "\n🟢 CORRECCIONES APLICADAS: " . count($corregidos) . "\n";
foreach ($corregidos as $corr) {
    echo "   $corr\n";
}

// Puntuación de salud del sistema
$total_verificaciones = 50; // aproximado
$problemas = count($errores) + (count($advertencias) * 0.5);
$salud = max(0, min(100, 100 - ($problemas / $total_verificaciones * 100)));

echo "\n" . str_repeat("=", 70) . "\n";
echo "💊 SALUD DEL SISTEMA: " . round($salud) . "%\n";

if ($salud >= 90) {
    echo "   Estado: ✅ EXCELENTE\n";
} elseif ($salud >= 70) {
    echo "   Estado: 🟢 BUENO\n";
} elseif ($salud >= 50) {
    echo "   Estado: 🟡 ACEPTABLE - Requiere atención\n";
} else {
    echo "   Estado: 🔴 CRÍTICO - Corrección urgente necesaria\n";
}

echo str_repeat("=", 70) . "\n";

echo "\n✓ Verificación completada\n";
echo "</pre>";

if (php_sapi_name() !== 'cli') {
    echo '<br><a href="admin/dashboard.php" class="btn btn-primary">Volver al Dashboard</a>';
    echo ' <a href="ANALISIS_SISTEMA.md" class="btn btn-info" target="_blank">Ver Análisis Completo</a>';
}
?>
