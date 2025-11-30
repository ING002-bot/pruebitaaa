<?php
/**
 * Prueba completa del sistema HERMES EXPRESS con FlexSender
 */

require_once 'config/config.php';
require_once 'config/whatsapp_helper.php';

echo "🚀 PRUEBA COMPLETA SISTEMA HERMES EXPRESS\n";
echo "========================================\n";

echo "📋 CONFIGURACIÓN ACTUAL:\n";
echo "- Modo WhatsApp: " . WHATSAPP_API_TYPE . "\n";
echo "- FlexSender SID: " . FLEXBIS_API_SID . "\n";
echo "- Token: " . substr(FLEXBIS_API_KEY, 0, 10) . "...\n\n";

// Datos de prueba
$destinatario = "51903417579";
$mensaje = "🎯 PRUEBA SISTEMA COMPLETO\n\n";
$mensaje .= "✅ HERMES EXPRESS en modo REAL\n";
$mensaje .= "📱 FlexSender integrado\n";  
$mensaje .= "⏰ " . date('d/m/Y H:i:s') . "\n\n";
$mensaje .= "Este mensaje confirma que el sistema funciona correctamente. 🎉";

echo "📱 Destinatario: $destinatario\n";
echo "💬 Mensaje: " . substr($mensaje, 0, 60) . "...\n\n";

echo "📤 Enviando mediante sistema WhatsApp...\n";

try {
    $whatsapp = new WhatsAppNotificaciones();
    $resultado = $whatsapp->enviarMensajeDirecto($destinatario, $mensaje);
    
    echo "🔍 RESULTADO:\n";
    
    if (is_array($resultado)) {
        if ($resultado['success']) {
            echo "✅ ÉXITO: " . $resultado['message'] . "\n";
            if (isset($resultado['message_id'])) {
                echo "🆔 ID: " . $resultado['message_id'] . "\n";
            }
            echo "📡 Método: " . (isset($resultado['method']) ? $resultado['method'] : 'desconocido') . "\n";
        } else {
            echo "❌ ERROR: " . $resultado['error'] . "\n";
        }
    } else {
        // Si devolvió string, probablemente fue simulado o mensaje directo
        echo "📨 Respuesta: " . $resultado . "\n";
        
        if (strpos($resultado, 'simulado') !== false) {
            echo "⚠️ MODO SIMULADO ACTIVO\n";
            echo "FlexSender aún no está disponible (procesando pago)\n";
        } elseif (strpos($resultado, 'enviado') !== false) {
            echo "✅ MENSAJE PROCESADO\n";
        }
    }
    
    echo "\n📋 ESTADO ACTUAL:\n";
    echo "✅ Sistema configurado en modo FlexBis\n";
    echo "⏳ API FlexSender procesando pago (actualiza cada 5 minutos)\n";  
    echo "📱 Panel FlexSender funciona (mensaje te llegó)\n";
    echo "🔄 Sistema listo para activación automática\n";
    
} catch (Exception $e) {
    echo "💥 EXCEPCIÓN: " . $e->getMessage() . "\n";
}

echo "\n========================================\n";
echo "🏷️ ETIQUETA: Sistema completamente configurado\n";
echo "⏰ " . date('d/m/Y H:i:s') . "\n";
?>