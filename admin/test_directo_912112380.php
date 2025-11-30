<?php
/**
 * Prueba directa API FlexBis para +51912112380
 */

require_once '../config/config.php';

echo "🧪 PRUEBA DIRECTA API FLEXBIS - +51912112380\n";
echo "============================================\n";

$numero = "+51912112380";
$mensaje = "🧪 PRUEBA FINAL para número $numero\n⏰ " . date('H:i:s d/m/Y') . "\n\n¿Recibes este mensaje?";

echo "📱 Enviando a: $numero\n";
echo "📝 Mensaje: $mensaje\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://whatsapp-service.flexbis.com/api/v1/message/text");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'numero_destinatario' => $numero,
    'tipo_destinatario' => 'contacto',
    'tipo_mensaje' => 'texto',
    'texto' => $mensaje
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'flexbis-instance: ' . FLEXBIS_API_SID,
    'flexbis-token: ' . FLEXBIS_API_KEY
]);

echo "🔄 Ejecutando llamada a FlexBis...\n";
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "📊 RESULTADOS:\n";
echo "  - HTTP Code: $http_code\n";
echo "  - Error cURL: " . ($error ? $error : "Ninguno") . "\n";
echo "  - Respuesta: $response\n\n";

$json = json_decode($response, true);
if ($json) {
    echo "🔍 ANÁLISIS RESPUESTA:\n";
    echo "  - Tipo: " . ($json['type'] ?? 'No definido') . "\n";
    echo "  - Mensaje: " . ($json['message'] ?? 'No definido') . "\n";
    
    if (isset($json['type']) && $json['type'] === 'success') {
        echo "\n✅ RESPUESTA EXITOSA DE FLEXBIS\n";
        echo "💡 Si no llega el mensaje, posibles causas:\n";
        echo "   1. Número sin WhatsApp: $numero\n";
        echo "   2. Configuración de privacidad\n";  
        echo "   3. Número bloqueado/inactivo\n";
        echo "   4. Operador móvil con restricciones\n";
    } else {
        echo "\n❌ ERROR EN RESPUESTA\n";
    }
} else {
    echo "❌ No se pudo parsear respuesta JSON\n";
}

echo "\n⏰ " . date('d/m/Y H:i:s') . "\n";
?>