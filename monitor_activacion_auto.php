<?php
/**
 * Monitor automático de activación FlexSender
 * Se ejecuta cada 5 minutos para detectar cuando se active
 */

require_once 'config/config.php';

echo "🔄 MONITOR AUTOMÁTICO FLEXSENDER\n";
echo "===============================\n";
echo "Verificando cada 5 minutos hasta activación...\n\n";

$max_intentos = 24; // 2 horas máximo
$intervalo = 300; // 5 minutos

for ($i = 1; $i <= $max_intentos; $i++) {
    echo "🔍 Intento $i/$max_intentos - " . date('H:i:s') . "\n";
    
    // Probar API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.ultramsg.com/" . FLEXBIS_API_SID . "/messages/chat");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'token' => FLEXBIS_API_KEY,
        'to' => '51903417579',
        'body' => '🎉 ¡FlexSender ACTIVADO! Enviado automáticamente desde HERMES EXPRESS'
    ]));
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $json = json_decode($response, true);
    
    if ($http_code == 200 && isset($json['sent']) && $json['sent']) {
        echo "✅ ¡FLEXSENDER ACTIVADO!\n";
        echo "📤 Mensaje enviado exitosamente!\n";
        echo "🔄 Cambiando automáticamente a modo real...\n";
        
        // Activar automáticamente
        $env_content = file_get_contents('.env');
        $env_content = preg_replace('/WHATSAPP_API_TYPE=.*/', 'WHATSAPP_API_TYPE=flexbis', $env_content);
        file_put_contents('.env', $env_content);
        
        echo "🚀 ¡SISTEMA ACTIVADO EN MODO REAL!\n";
        echo "HERMES EXPRESS ahora envía WhatsApp reales.\n";
        break;
        
    } elseif (isset($json['error']) && strpos($json['error'], 'non-payment') !== false) {
        echo "⏳ Aún procesando pago... esperando 5 minutos\n";
        
        if ($i < $max_intentos) {
            echo "💤 Durmiendo hasta " . date('H:i:s', time() + $intervalo) . "\n\n";
            sleep($intervalo);
        }
        
    } else {
        echo "❓ Respuesta inesperada: " . substr($response, 0, 100) . "\n";
        if ($i < $max_intentos) {
            sleep($intervalo);
        }
    }
}

if ($i > $max_intentos) {
    echo "⚠️ Se agotó el tiempo de espera\n";
    echo "Contacta a FlexBis si el problema persiste.\n";
}

echo "\n" . date('d/m/Y H:i:s') . "\n";
?>