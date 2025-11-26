<?php
// Verificar cuenta de Twilio

$sid = 'AC7cde09ffb05d087aafa652c485a2529b';
$token = '1ee60ed1e2208401b06eae6d839c16ec';

$url = "https://api.twilio.com/2010-04-01/Accounts/$sid.json";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "$sid:$token");
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Código HTTP: $httpCode\n\n";

if ($httpCode == 200) {
    $data = json_decode($response, true);
    echo "✅ Cuenta verificada:\n";
    echo "Nombre: " . ($data['friendly_name'] ?? 'N/A') . "\n";
    echo "Estado: " . ($data['status'] ?? 'N/A') . "\n";
    echo "Tipo: " . ($data['type'] ?? 'N/A') . "\n";
} else {
    echo "❌ Error de autenticación:\n";
    echo "$response\n";
}
