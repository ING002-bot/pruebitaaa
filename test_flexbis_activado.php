<?php
/**
 * Prueba FlexSender después de vincular WhatsApp
 */

require_once 'config/config.php';
require_once 'config/flexbis_client.php';

echo "🔥 PROBANDO FLEXSENDER VINCULADO 🔥\n";
echo "===================================\n";

try {
    $flexbis = new FlexBisClient();
    
    echo "📱 Instancia: " . FLEXBIS_API_SID . "\n";
    echo "🔑 Token: " . substr(FLEXBIS_API_KEY, 0, 10) . "...\n";
    echo "📞 Número de prueba: 51903417579\n\n";
    
    // Probar conexión
    echo "🔍 Probando conexión...\n";
    $test = $flexbis->testConnection();
    
    if ($test['success']) {
        echo "✅ FLEXSENDER ACTIVO!\n";
        echo "Respuesta: " . $test['message'] . "\n\n";
        
        // Intentar envío de prueba
        echo "📤 Enviando mensaje de prueba...\n";
        $resultado = $flexbis->sendMessage(
            '51903417579',
            '🎉 ¡FlexSender está ACTIVO! Mensaje enviado desde HERMES EXPRESS el ' . date('d/m/Y H:i:s')
        );
        
        if ($resultado['success']) {
            echo "✅ MENSAJE ENVIADO EXITOSAMENTE!\n";
            echo "ID: " . $resultado['message_id'] . "\n";
            echo "Estado: " . $resultado['status'] . "\n\n";
            
            echo "🚀 FLEXSENDER COMPLETAMENTE FUNCIONAL!\n";
            echo "Ahora puedes activar el modo real.\n";
            
        } else {
            echo "❌ Error enviando mensaje:\n";
            echo $resultado['error'] . "\n";
        }
        
    } else {
        echo "⏳ FlexSender aún no está activo\n";
        echo "Error: " . $test['error'] . "\n";
        echo "Intenta de nuevo en unos minutos.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n===================================\n";
echo "Fecha: " . date('d/m/Y H:i:s') . "\n";
?>