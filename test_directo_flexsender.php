<?php
/**
 * Prueba directa con FlexSender activo
 */

require_once 'config/config.php';

echo "🚀 PRUEBA DIRECTA FLEXSENDER ACTIVO\n";
echo "===================================\n";

$url = "https://whatsapp-service.flexbis.com/api/v1/message/text";
$sid = FLEXBIS_API_SID;
$token = FLEXBIS_API_KEY;
$numero = "+51903417579";
$mensaje = "🎉 ¡Mensaje enviado desde HERMES EXPRESS!\n\n✅ FlexSender está activo\n⏰ " . date('d/m/Y H:i:s');

echo "📱 URL CORRECTA: $url\n";
echo "🔑 Instance: $sid\n";
echo "🔑 Token: " . substr($token, 0, 10) . "...\n";
echo "📞 Para: $numero\n";
echo "💬 Mensaje: " . substr($mensaje, 0, 50) . "...\n\n";

// Datos FlexBis formato correcto
$data = [
    'numero_destinatario' => $numero,
    'tipo_destinatario' => 'contacto',
    'tipo_mensaje' => 'texto',
    'texto' => $mensaje
];

// Envío directo con formato FlexBis correcto
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'flexbis-instance: ' . $sid,
    'flexbis-token: ' . $token
]);

echo "📤 Enviando...\n";
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "🔍 Código HTTP: $http_code\n";
if ($error) {
    echo "❌ Error cURL: $error\n";
} else {
    echo "📨 Respuesta completa:\n";
    echo $response . "\n\n";
    
    $json = json_decode($response, true);
    if ($json) {
        if (isset($json['sent']) && $json['sent'] === true) {
            echo "🎉 ¡MENSAJE ENVIADO EXITOSAMENTE!\n";
            if (isset($json['id'])) {
                echo "🆔 ID del mensaje: " . $json['id'] . "\n";
            }
            echo "✅ FlexSender está completamente funcional!\n\n";
            
            echo "🚀 LISTO PARA ACTIVAR MODO REAL\n";
            
        } elseif (isset($json['error'])) {
            echo "❌ Error de API: " . $json['error'] . "\n";
        } else {
            echo "❓ Respuesta no reconocida\n";
        }
    } else {
        echo "❓ Respuesta no es JSON válido\n";
    }
}

echo "\n===================================\n";
echo "⏰ " . date('d/m/Y H:i:s') . "\n";
?>