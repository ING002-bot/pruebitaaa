<?php
/**
 * Diagnóstico completo del sistema WhatsApp
 */

require_once '../config/config.php';
require_once '../config/whatsapp_helper.php';

echo "🔍 DIAGNÓSTICO SISTEMA WHATSAPP - HERMES EXPRESS\n";
echo "===============================================\n";

// 1. Verificar configuración
echo "📋 1. CONFIGURACIÓN:\n";
echo "- Modo API: " . (defined('WHATSAPP_API_TYPE') ? WHATSAPP_API_TYPE : 'NO DEFINIDO') . "\n";
echo "- FlexBis SID: " . (defined('FLEXBIS_API_SID') ? FLEXBIS_API_SID : 'NO DEFINIDO') . "\n";
echo "- FlexBis Token: " . (defined('FLEXBIS_API_KEY') ? 'Configurado' : 'NO DEFINIDO') . "\n\n";

// 2. Probar conexión a BD
try {
    $db = Database::getInstance()->getConnection();
    echo "✅ 2. CONEXIÓN BD: OK\n\n";
} catch (Exception $e) {
    echo "❌ 2. CONEXIÓN BD: ERROR - " . $e->getMessage() . "\n\n";
    exit;
}

// 3. Verificar tabla notificaciones_whatsapp
$check_table = $db->query("SHOW TABLES LIKE 'notificaciones_whatsapp'");
if ($check_table && $check_table->num_rows > 0) {
    echo "✅ 3. TABLA notificaciones_whatsapp: EXISTE\n";
    
    // Mostrar últimas notificaciones
    $last_notifications = $db->query("
        SELECT * FROM notificaciones_whatsapp 
        ORDER BY fecha_envio DESC 
        LIMIT 5
    ");
    
    if ($last_notifications && $last_notifications->num_rows > 0) {
        echo "📨 Últimas notificaciones:\n";
        while ($row = $last_notifications->fetch_assoc()) {
            echo "  - ID: {$row['id']} | Paquete: {$row['paquete_id']} | Estado: {$row['estado']} | Fecha: {$row['fecha_envio']}\n";
        }
    } else {
        echo "❌ No hay notificaciones registradas\n";
    }
} else {
    echo "❌ 3. TABLA notificaciones_whatsapp: NO EXISTE\n";
}
echo "\n";

// 4. Probar paquetes recientes
echo "📦 4. PAQUETES RECIENTES:\n";
$recent_packages = $db->query("
    SELECT id, codigo_seguimiento, destinatario_nombre, destinatario_telefono, 
           repartidor_id, estado, fecha_asignacion
    FROM paquetes 
    ORDER BY id DESC 
    LIMIT 5
");

if ($recent_packages && $recent_packages->num_rows > 0) {
    while ($pkg = $recent_packages->fetch_assoc()) {
        echo "  - ID: {$pkg['id']} | Código: {$pkg['codigo_seguimiento']} | ";
        echo "Cliente: {$pkg['destinatario_nombre']} | Tel: {$pkg['destinatario_telefono']} | ";
        echo "Repartidor: " . ($pkg['repartidor_id'] ?: 'Sin asignar') . " | ";
        echo "Estado: {$pkg['estado']}\n";
    }
} else {
    echo "❌ No hay paquetes\n";
}
echo "\n";

// 5. Probar clase WhatsApp
echo "🔧 5. PRUEBA CLASE WHATSAPP:\n";
try {
    $whatsapp = new WhatsAppNotificaciones();
    echo "✅ Clase WhatsAppNotificaciones: CARGADA\n";
    
    // Probar notificación del paquete más reciente
    if ($recent_packages && $recent_packages->num_rows > 0) {
        $recent_packages->data_seek(0);
        $test_package = $recent_packages->fetch_assoc();
        
        if ($test_package['repartidor_id']) {
            echo "🧪 Probando notificación paquete ID: {$test_package['id']}\n";
            
            $result = $whatsapp->notificarAsignacion($test_package['id']);
            
            if ($result) {
                echo "✅ Notificación enviada correctamente\n";
            } else {
                echo "❌ Error enviando notificación\n";
            }
        } else {
            echo "⚠️ Paquete más reciente no tiene repartidor asignado\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error clase WhatsApp: " . $e->getMessage() . "\n";
}
echo "\n";

// 6. Verificar logs PHP
echo "📋 6. VERIFICACIÓN LOGS:\n";
echo "Revisa el error_log de PHP para mensajes como:\n";
echo "- '📱 [WhatsApp Simulado]'\n";
echo "- 'FlexBis WhatsApp enviado'\n";
echo "- 'WhatsApp Helper initialized'\n\n";

echo "⏰ Diagnóstico completado: " . date('d/m/Y H:i:s') . "\n";
?>