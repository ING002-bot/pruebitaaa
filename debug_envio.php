<?php
/**
 * Debug detallado del envío Twilio
 */

require_once 'config/config.php';

$numero = '+51970252386';
$mensaje = "🧪 Test desde sistema";

echo "=== DEBUG DE ENVÍO TWILIO ===\n\n";

echo "1. Credenciales:\n";
echo "   SID: " . TWILIO_ACCOUNT_SID . "\n";
echo "   Token: " . substr(TWILIO_AUTH_TOKEN, 0, 8) . "...\n";
echo "   From: " . TWILIO_WHATSAPP_FROM . "\n\n";

echo "2. Parámetros del Envío:\n";
echo "   To: whatsapp:" . $numero . "\n";
echo "   Body: " . $mensaje . "\n\n";

echo "3. Preparando solicitud...\n";

$url = 'https://api.twilio.com/2010-04-01/Accounts/' . TWILIO_ACCOUNT_SID . '/Messages.json';
$post_data = [
    'From' => TWILIO_WHATSAPP_FROM,
    'To' => 'whatsapp:' . $numero,
    'Body' => $mensaje
];

echo "   URL: " . $url . "\n";
echo "   POST Data: " . http_build_query($post_data) . "\n\n";

echo "4. Ejecutando solicitud cURL...\n";

$auth = base64_encode(TWILIO_ACCOUNT_SID . ':' . TWILIO_AUTH_TOKEN);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . $auth,
    'Content-Type: application/x-www-form-urlencoded'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "   HTTP Code: " . $http_code . "\n";
echo "   cURL Error: " . ($curl_error ?: 'Ninguno') . "\n\n";

echo "5. Respuesta Completa:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo $response . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

if ($http_code === 201) {
    echo "✅ ÉXITO - Mensaje enviado\n";
    $result = json_decode($response, true);
    echo "Message SID: " . ($result['sid'] ?? 'N/A') . "\n";
} else {
    echo "❌ ERROR - HTTP " . $http_code . "\n";
    $result = json_decode($response, true);
    if ($result) {
        echo "Código Error: " . ($result['code'] ?? 'N/A') . "\n";
        echo "Mensaje Error: " . ($result['message'] ?? 'N/A') . "\n";
        echo "Más info: " . ($result['more_info'] ?? 'N/A') . "\n";
    }
}

?>
