<?php
/**
 * VERIFICACIÓN FINAL DEL SISTEMA - PRE-PRESENTACIÓN
 * Ejecutar antes de la presentación para confirmar que todo funciona
 */

require_once 'config/config.php';
require_once 'config/whatsapp_helper.php';

echo "🎯 VERIFICACIÓN FINAL - SISTEMA LISTO PARA PRESENTACIÓN\n";
echo "=====================================================\n\n";

$errores = [];
$warnings = [];

// 1. Verificar conexión a base de datos
echo "🔍 1. VERIFICANDO BASE DE DATOS...\n";
try {
    $db = Database::getInstance()->getConnection();
    echo "   ✅ Conexión a MySQL: OK\n";
    
    // Verificar tablas principales
    $tablas = ['usuarios', 'paquetes', 'notificaciones_whatsapp', 'distritos'];
    foreach ($tablas as $tabla) {
        $result = $db->query("SELECT COUNT(*) as count FROM $tabla");
        $count = $result->fetch_assoc()['count'];
        echo "   ✅ Tabla $tabla: $count registros\n";
    }
} catch (Exception $e) {
    $errores[] = "Base de datos: " . $e->getMessage();
    echo "   ❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n🔍 2. VERIFICANDO CONFIGURACIÓN WHATSAPP...\n";
// Verificar credenciales FlexBis
if (defined('FLEXBIS_API_SID') && defined('FLEXBIS_API_KEY')) {
    echo "   ✅ Credenciales FlexBis configuradas\n";
    echo "   📱 SID: " . substr(FLEXBIS_API_SID, 0, 4) . "****\n";
    echo "   🔑 Token: " . substr(FLEXBIS_API_KEY, 0, 6) . "****\n";
} else {
    $errores[] = "Credenciales FlexBis no configuradas";
    echo "   ❌ Credenciales FlexBis faltantes\n";
}

echo "\n🔍 3. PROBANDO FUNCIONALIDADES PRINCIPALES...\n";

// Probar sistema de usuarios
try {
    $usuarios = $db->query("SELECT COUNT(*) as count FROM usuarios WHERE rol = 'admin'")->fetch_assoc()['count'];
    echo "   ✅ Usuarios admin: $usuarios\n";
    
    $repartidores = $db->query("SELECT COUNT(*) as count FROM usuarios WHERE rol = 'repartidor'")->fetch_assoc()['count'];
    echo "   ✅ Repartidores: $repartidores\n";
} catch (Exception $e) {
    $warnings[] = "Error verificando usuarios: " . $e->getMessage();
}

// Probar paquetes
try {
    $paquetes_total = $db->query("SELECT COUNT(*) as count FROM paquetes")->fetch_assoc()['count'];
    $paquetes_pendientes = $db->query("SELECT COUNT(*) as count FROM paquetes WHERE estado = 'pendiente'")->fetch_assoc()['count'];
    $paquetes_ruta = $db->query("SELECT COUNT(*) as count FROM paquetes WHERE estado = 'en_ruta'")->fetch_assoc()['count'];
    $paquetes_entregados = $db->query("SELECT COUNT(*) as count FROM paquetes WHERE estado = 'entregado'")->fetch_assoc()['count'];
    
    echo "   ✅ Paquetes total: $paquetes_total\n";
    echo "   📦 Pendientes: $paquetes_pendientes | En ruta: $paquetes_ruta | Entregados: $paquetes_entregados\n";
} catch (Exception $e) {
    $warnings[] = "Error verificando paquetes: " . $e->getMessage();
}

// Probar WhatsApp
echo "\n🔍 4. PROBANDO INTEGRACIÓN WHATSAPP...\n";
try {
    $whatsapp = new WhatsAppNotificaciones();
    echo "   ✅ Clase WhatsAppNotificaciones: OK\n";
    
    // Verificar notificaciones enviadas
    $notif_count = $db->query("SELECT COUNT(*) as count FROM notificaciones_whatsapp")->fetch_assoc()['count'];
    echo "   📱 Notificaciones registradas: $notif_count\n";
    
    $exitosas = $db->query("SELECT COUNT(*) as count FROM notificaciones_whatsapp WHERE estado = 'enviado'")->fetch_assoc()['count'];
    echo "   ✅ Notificaciones exitosas: $exitosas\n";
} catch (Exception $e) {
    $errores[] = "WhatsApp: " . $e->getMessage();
}

echo "\n🔍 5. VERIFICANDO ARCHIVOS DEL SISTEMA...\n";
$archivos_criticos = [
    'index.php' => 'Página principal',
    'admin/dashboard.php' => 'Dashboard admin',
    'admin/paquetes.php' => 'Gestión paquetes',
    'config/config.php' => 'Configuración',
    'config/whatsapp_helper.php' => 'Helper WhatsApp',
    'assets/js/validaciones.js' => 'Validaciones JS'
];

foreach ($archivos_criticos as $archivo => $descripcion) {
    if (file_exists($archivo)) {
        echo "   ✅ $descripcion: OK\n";
    } else {
        $errores[] = "Archivo faltante: $archivo";
        echo "   ❌ $descripcion: FALTANTE\n";
    }
}

echo "\n🔍 6. DATOS DE PRUEBA SUGERIDOS...\n";
// Verificar si hay datos para demo
try {
    $admin_demo = $db->query("SELECT * FROM usuarios WHERE rol = 'admin' LIMIT 1")->fetch_assoc();
    if ($admin_demo) {
        echo "   👤 Usuario admin demo: {$admin_demo['username']}\n";
    }
    
    $repartidor_demo = $db->query("SELECT * FROM usuarios WHERE rol = 'repartidor' LIMIT 1")->fetch_assoc();
    if ($repartidor_demo) {
        echo "   🚚 Repartidor demo: {$repartidor_demo['nombre']}\n";
    }
    
    $paquete_demo = $db->query("SELECT * FROM paquetes ORDER BY id DESC LIMIT 1")->fetch_assoc();
    if ($paquete_demo) {
        echo "   📦 Último paquete: {$paquete_demo['codigo_seguimiento']}\n";
    }
} catch (Exception $e) {
    $warnings[] = "Error verificando datos demo: " . $e->getMessage();
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🎯 RESUMEN FINAL\n";
echo str_repeat("=", 60) . "\n";

if (empty($errores)) {
    echo "🎉 ¡SISTEMA LISTO PARA PRESENTACIÓN!\n";
    echo "✅ Todas las funcionalidades principales verificadas\n";
    echo "✅ Base de datos operativa\n";
    echo "✅ WhatsApp configurado\n";
    echo "✅ Archivos del sistema presentes\n";
} else {
    echo "⚠️  ERRORES CRÍTICOS ENCONTRADOS:\n";
    foreach ($errores as $error) {
        echo "   ❌ $error\n";
    }
}

if (!empty($warnings)) {
    echo "\n⚠️  ADVERTENCIAS:\n";
    foreach ($warnings as $warning) {
        echo "   ⚠️  $warning\n";
    }
}

echo "\n💡 RECOMENDACIONES PRE-PRESENTACIÓN:\n";
echo "1. 📱 Ten un teléfono con WhatsApp listo para mostrar mensajes\n";
echo "2. 🌐 Verifica tu conexión a internet\n";
echo "3. 🔄 Practica el flujo: Login → Crear paquete → Asignar → Mostrar WhatsApp\n";
echo "4. 📋 Ten datos de prueba listos (nombre cliente, teléfono, dirección)\n";
echo "5. 🎯 Enfócate en la integración WhatsApp como diferenciador principal\n";

echo "\n⏰ Verificación realizada: " . date('d/m/Y H:i:s') . "\n";
echo "🚀 ¡ÉXITO EN TU PRESENTACIÓN!\n";
?>