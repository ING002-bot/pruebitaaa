<?php
/**
 * Monitor post-confirmación de pago FlexSender
 */

require_once 'config/config.php';

echo "💳 PAGO CONFIRMADO - MONITOR ACTIVACIÓN\n";
echo "======================================\n";
echo "✅ Orden: 152248498\n";
echo "💰 Monto: 18.00 PEN\n";
echo "🕐 Pago: 30-11-2025 11:49\n";
echo "⏰ Monitor: " . date('d/m/Y H:i:s') . "\n\n";

echo "🔍 Verificando activación cada 60 segundos...\n\n";

$intentos = 0;
$max_intentos = 15; // 15 minutos
$intervalo = 60; // 1 minuto

while ($intentos < $max_intentos) {
    $intentos++;
    echo "🔍 Verificación $intentos/$max_intentos - " . date('H:i:s') . "\n";
    
    // Probar API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.ultramsg.com/" . FLEXBIS_API_SID . "/messages/chat");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'token' => FLEXBIS_API_KEY,
        'to' => '51903417579',
        'body' => '🎉 ¡FLEXSENDER ACTIVADO! Pago confirmado - HERMES EXPRESS funcional'
    ]));
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $json = json_decode($response, true);
    
    if ($http_code == 200 && isset($json['sent']) && $json['sent']) {
        echo "\n🎉🎉🎉 ¡¡¡FLEXSENDER COMPLETAMENTE ACTIVADO!!! 🎉🎉🎉\n";
        echo "✅ Mensaje enviado exitosamente!\n";
        echo "🆔 ID: " . ($json['id'] ?? 'N/A') . "\n\n";
        
        echo "🚀 HERMES EXPRESS AHORA ENVÍA WHATSAPP REALES!\n";
        echo "📱 Sistema completamente funcional\n";
        echo "💯 Integración FlexSender exitosa\n\n";
        
        // Probar sistema completo
        echo "🧪 Probando sistema completo...\n";
        system('php test_sistema_completo.php');
        
        break;
        
    } elseif (isset($json['error']) && strpos($json['error'], 'non-payment') !== false) {
        echo "⏳ Aún propagando pago... (esperado después de confirmación)\n";
        
    } else {
        echo "❓ Respuesta: " . substr($response, 0, 100) . "\n";
    }
    
    if ($intentos < $max_intentos) {
        echo "💤 Esperando 60 segundos...\n\n";
        sleep($intervalo);
    }
}

if ($intentos >= $max_intentos) {
    echo "⚠️ Pago confirmado pero API aún no activa\n";
    echo "💡 Puede tardar hasta 30 minutos en algunos casos\n";
    echo "🔗 Panel: https://panel.flexbis.com\n";
}

echo "\n⏰ Fin: " . date('d/m/Y H:i:s') . "\n";
?>