<?php
/**
 * Prueba del WhatsApp Helper corregido
 */

require_once 'config/config.php';
require_once 'config/whatsapp_helper.php';

echo "🧪 PRUEBA WHATSAPP HELPER - CONSTANTES CORREGIDAS\n";
echo "================================================\n\n";

try {
    // Crear instancia del helper
    echo "📱 Creando instancia WhatsAppNotificaciones...\n";
    $whatsapp = new WhatsAppNotificaciones();
    echo "✅ Instancia creada exitosamente\n\n";
    
    // Verificar configuración
    echo "🔍 VERIFICANDO CONFIGURACIÓN:\n";
    echo "  - WHATSAPP_API_TYPE: " . (defined('WHATSAPP_API_TYPE') ? WHATSAPP_API_TYPE : 'No definida') . "\n";
    echo "  - FLEXBIS_API_SID: " . (defined('FLEXBIS_API_SID') ? FLEXBIS_API_SID : 'No definida') . "\n";
    echo "  - FLEXBIS_API_KEY: " . (defined('FLEXBIS_API_KEY') ? substr(FLEXBIS_API_KEY, 0, 6) . '****' : 'No definida') . "\n";
    echo "  - FLEXBIS_API_URL: " . (defined('FLEXBIS_API_URL') ? FLEXBIS_API_URL : 'No definida') . "\n\n";
    
    // Probar envío directo
    echo "📞 PROBANDO ENVÍO DIRECTO...\n";
    $telefono_prueba = "+51903417579"; // Número que sabemos funciona
    $mensaje_prueba = "🧪 PRUEBA CONSTANTES CORREGIDAS\n⏰ " . date('H:i:s') . "\n\n¿Sistema funcionando correctamente?";
    
    echo "  - Teléfono: $telefono_prueba\n";
    echo "  - Mensaje: " . substr($mensaje_prueba, 0, 50) . "...\n\n";
    
    $resultado = $whatsapp->enviarMensajeDirecto($telefono_prueba, $mensaje_prueba);
    
    if ($resultado !== 'error') {
        echo "✅ MENSAJE ENVIADO EXITOSAMENTE\n";
        echo "  - ID Respuesta: $resultado\n";
    } else {
        echo "❌ ERROR AL ENVIAR MENSAJE\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "  Archivo: " . $e->getFile() . "\n";
    echo "  Línea: " . $e->getLine() . "\n";
}

echo "\n⏰ " . date('d/m/Y H:i:s') . "\n";
echo "🎉 ¡PRUEBA COMPLETADA!\n";
?>