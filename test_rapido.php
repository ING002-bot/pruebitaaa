<?php
/**
 * Prueba rápida FlexSender - verificación simple
 */

require_once 'config/config.php';

echo "⚡ PRUEBA RÁPIDA FLEXSENDER ⚡\n";
echo "============================\n";
echo "⏰ " . date('H:i:s') . " - Probando...\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.ultramsg.com/" . FLEXBIS_API_SID . "/messages/chat");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'token' => FLEXBIS_API_KEY,
    'to' => '51903417579',
    'body' => '🎯 Test FlexSender - ' . date('H:i:s')
]));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$json = json_decode($response, true);

if ($http_code == 200 && isset($json['sent']) && $json['sent']) {
    echo "🎉 ¡¡¡ACTIVADO!!! ✅\n";
    echo "ID: " . ($json['id'] ?? 'N/A') . "\n";
} elseif (isset($json['error'])) {
    if (strpos($json['error'], 'non-payment') !== false) {
        echo "⏳ Aún procesando pago...\n";
    } else {
        echo "❓ Error: " . $json['error'] . "\n";
    }
} else {
    echo "❓ Respuesta: HTTP $http_code\n";
    echo substr($response, 0, 100) . "\n";
}

echo "\n⏰ " . date('H:i:s') . "\n";
?>