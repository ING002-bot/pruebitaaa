<?php
/**
 * Script de verificación exhaustiva de credenciales Twilio
 */

require_once 'config/config.php';

// Verificar qué está en config.php
$sid = defined('TWILIO_ACCOUNT_SID') ? TWILIO_ACCOUNT_SID : '';
$token = defined('TWILIO_AUTH_TOKEN') ? TWILIO_AUTH_TOKEN : '';

echo "=== VERIFICACIÓN DE CREDENCIALES TWILIO ===\n\n";

echo "1. SID en config.php:\n";
echo "   Valor: " . $sid . "\n";
echo "   Longitud: " . strlen($sid) . "\n";
echo "   Comienza con AC: " . (strpos($sid, 'AC') === 0 ? 'SÍ ✓' : 'NO ✗') . "\n";
echo "   Válido: " . (strlen($sid) === 34 ? 'SÍ ✓' : 'NO ✗ (debe tener 34 caracteres)') . "\n\n";

echo "2. Token en config.php:\n";
echo "   Valor: " . substr($token, 0, 8) . "..." . substr($token, -8) . "\n";
echo "   Longitud: " . strlen($token) . "\n";
echo "   Válido: " . (strlen($token) === 32 ? 'SÍ ✓' : 'NO ✗ (debe tener 32 caracteres)') . "\n\n";

// Intentar conexión
echo "3. Intentando conexión a Twilio API...\n\n";

if (empty($sid) || empty($token)) {
    echo "❌ ERROR: Credenciales vacías\n";
    exit;
}

$auth = base64_encode($sid . ':' . $token);
$url = 'https://api.twilio.com/2010-04-01/Accounts/' . $sid . '.json';

echo "   URL: " . $url . "\n";
echo "   Auth Header: Basic " . substr($auth, 0, 20) . "...\n\n";

// Hacer petición
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . $auth,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "   HTTP Code: " . $http_code . "\n";
echo "   Response: " . substr($response, 0, 500) . "\n\n";

if ($http_code === 200) {
    echo "✅ AUTENTICACIÓN EXITOSA\n";
    $result = json_decode($response, true);
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} else {
    echo "❌ FALLO EN AUTENTICACIÓN\n";
    echo "Código: " . $http_code . "\n";
    if ($curl_error) {
        echo "Error cURL: " . $curl_error . "\n";
    }
    
    // Intentar parsear error de Twilio
    $error_data = json_decode($response, true);
    if ($error_data) {
        echo "\nDetalle del error:\n";
        echo json_encode($error_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}

?>
