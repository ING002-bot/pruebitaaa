<?php
/**
 * Monitor intensivo FlexSender - Verificación cada 2 minutos
 */

require_once 'config/config.php';

echo "⚡ MONITOR INTENSIVO FLEXSENDER ⚡\n";
echo "===============================\n";
echo "🔄 Verificando cada 2 minutos\n";
echo "⏰ Inicio: " . date('d/m/Y H:i:s') . "\n\n";

$max_intentos = 30; // 1 hora
$intervalo = 120; // 2 minutos

for ($i = 1; $i <= $max_intentos; $i++) {
    echo "🔍 Verificación $i/$max_intentos - " . date('H:i:s') . "\n";
    
    // Probar API directamente
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.ultramsg.com/" . FLEXBIS_API_SID . "/messages/chat");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'token' => FLEXBIS_API_KEY,
        'to' => '51903417579',
        'body' => '🎉 ¡FlexSender ACTIVADO! Mensaje automático desde HERMES EXPRESS - ' . date('H:i:s')
    ]));
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $json = json_decode($response, true);
    
    if ($http_code == 200 && isset($json['sent']) && $json['sent']) {
        echo "🎉 ¡¡¡FLEXSENDER ACTIVADO!!!\n";
        echo "✅ Mensaje enviado exitosamente!\n";
        echo "🆔 ID: " . ($json['id'] ?? 'N/A') . "\n\n";
        
        echo "🚀 Activando sistema completo...\n";
        
        // Probar sistema completo
        system('php test_sistema_completo.php');
        
        echo "\n🎯 ¡HERMES EXPRESS COMPLETAMENTE FUNCIONAL!\n";
        echo "📱 Enviando WhatsApp reales desde ahora\n";
        break;
        
    } elseif (isset($json['error'])) {
        if (strpos($json['error'], 'non-payment') !== false) {
            echo "⏳ Procesando pago... próxima verificación en 2 min\n";
        } else {
            echo "❓ Error: " . $json['error'] . "\n";
        }
        
        if ($i < $max_intentos) {
            echo "💤 Esperando hasta " . date('H:i:s', time() + $intervalo) . "\n\n";
            sleep($intervalo);
        }
        
    } else {
        echo "❓ Respuesta inesperada: HTTP $http_code\n";
        echo "📨 " . substr($response, 0, 100) . "\n";
        
        if ($i < $max_intentos) {
            sleep($intervalo);
        }
    }
}

if ($i > $max_intentos) {
    echo "⚠️ Monitor completado sin activación\n";
    echo "💡 Puede tardar más tiempo del esperado\n";
    echo "🔗 Verifica: https://panel.flexbis.com\n";
}

echo "\n⏰ Fin: " . date('d/m/Y H:i:s') . "\n";
?>