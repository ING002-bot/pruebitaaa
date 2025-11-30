<?php
/**
 * Diagnóstico avanzado FlexSender - Post vinculación
 */

require_once 'config/config.php';

echo "🔬 DIAGNÓSTICO FLEXSENDER AVANZADO\n";
echo "==================================\n";

// Configuración
echo "📋 CONFIGURACIÓN:\n";
echo "SID: " . (defined('FLEXBIS_API_SID') ? FLEXBIS_API_SID : 'NO DEFINIDO') . "\n";
echo "Token: " . (defined('FLEXBIS_API_KEY') ? substr(FLEXBIS_API_KEY, 0, 10) . "..." : 'NO DEFINIDO') . "\n";
echo "URL Base: " . (defined('FLEXBIS_API_URL') ? FLEXBIS_API_URL : 'NO DEFINIDO') . "\n\n";

// Probar múltiples endpoints
$endpoints_to_test = [
    'UltraMsg Messages' => "https://api.ultramsg.com/" . FLEXBIS_API_SID . "/messages/chat",
    'UltraMsg Instance' => "https://api.ultramsg.com/" . FLEXBIS_API_SID . "/instance/status",
    'UltraMsg Info' => "https://api.ultramsg.com/" . FLEXBIS_API_SID . "/instance/info",
    'FlexSender Direct' => "https://api.flexsender.com/" . FLEXBIS_API_SID . "/messages/chat",
    'FlexBis Panel' => "https://panel.flexbis.com/api/" . FLEXBIS_API_SID . "/send"
];

foreach ($endpoints_to_test as $name => $url) {
    echo "🔍 Probando: $name\n";
    echo "URL: $url\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'token' => FLEXBIS_API_KEY,
        'to' => '51903417579',
        'body' => 'Test connection'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "Código HTTP: $http_code\n";
    if ($error) {
        echo "❌ Error cURL: $error\n";
    } else {
        echo "📨 Respuesta: " . substr($response, 0, 200) . "\n";
        
        // Analizar respuesta
        $json = json_decode($response, true);
        if ($json) {
            if (isset($json['sent']) && $json['sent'] === true) {
                echo "✅ ¡MENSAJE ENVIADO CORRECTAMENTE!\n";
            } elseif (isset($json['error'])) {
                echo "⚠️ Error API: " . $json['error'] . "\n";
            } elseif (isset($json['status']) && $json['status'] === 'success') {
                echo "✅ ¡API RESPONDIÓ CORRECTAMENTE!\n";
            }
        }
    }
    echo "---\n";
}

echo "\n🔧 RECOMENDACIONES:\n";
echo "1. Verifica que tu WhatsApp esté conectado en el panel\n";
echo "2. Asegúrate de que el número esté en formato correcto\n";
echo "3. Revisa que no haya limitaciones de prueba\n";
echo "4. Espera unos minutos más para propagación\n";

echo "\n" . date('d/m/Y H:i:s') . "\n";
?>