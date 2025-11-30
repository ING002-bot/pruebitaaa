<?php
/**
 * Monitor FlexSender hasta que se active
 */

require_once 'config/config.php';
require_once 'config/flexbis_client.php';

echo "=== MONITOR ACTIVACIÓN FLEXSENDER ===\n";
echo "Esperando activación después del pago...\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

$flexbis = new FlexBisClient();
$intentos = 0;
$max_intentos = 12; // 12 intentos x 30 segundos = 6 minutos

while ($intentos < $max_intentos) {
    $intentos++;
    
    echo "🔄 Intento $intentos/$max_intentos - " . date('H:i:s') . "\n";
    
    $result = $flexbis->testConnection();
    
    if ($result['success']) {
        echo "🎉 ¡FLEXSENDER ACTIVADO!\n\n";
        
        // Probar envío real inmediatamente
        echo "📱 Enviando mensaje de prueba real...\n";
        $mensaje = "🚀 ¡FlexSender ACTIVADO! HERMES EXPRESS funcionando - " . date('H:i:s');
        $envio = $flexbis->sendMessage('903417579', $mensaje);
        
        if ($envio['success']) {
            echo "✅ ¡MENSAJE ENVIADO EXITOSAMENTE!\n";
            echo "ID: " . ($envio['message_id'] ?? 'N/A') . "\n";
            echo "📱 ¡REVISA TU WHATSAPP (903417579)!\n\n";
            
            echo "🎯 SISTEMA LISTO PARA PRODUCCIÓN:\n";
            echo "- FlexSender: ✅ Activo\n";
            echo "- Envíos reales: ✅ Funcionando\n";
            echo "- HERMES EXPRESS: ✅ Listo\n";
        } else {
            echo "⚠️  Activo pero error en envío: " . $envio['error'] . "\n";
        }
        
        break;
        
    } else {
        echo "⏳ Aún no activo: " . $result['error'] . "\n";
        
        // Si es un error diferente a "non-payment", salir
        if (strpos($result['error'], 'non-payment') === false) {
            echo "❌ Error diferente detectado, revisando...\n";
            print_r($result);
            break;
        }
    }
    
    if ($intentos < $max_intentos) {
        echo "   Esperando 30 segundos...\n\n";
        sleep(30);
    }
}

if ($intentos >= $max_intentos) {
    echo "⏰ Tiempo agotado. Posibles causas:\n";
    echo "1. El pago aún se está procesando\n";
    echo "2. Necesitas conectar WhatsApp en el panel\n";  
    echo "3. Hay un delay mayor a 6 minutos\n\n";
    echo "💡 Revisa tu panel FlexSender y vuelve a intentar\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Monitor finalizado: " . date('Y-m-d H:i:s') . "\n";
?>