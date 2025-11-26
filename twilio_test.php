<?php
// twilio_test.php
// Prueba de envío de WhatsApp con Twilio usando cURL directo (sin composer)

$sid    = 'AC7cde09ffb05d087aafa652c485a2529b';
$token  = '1ee60ed1e2208401b06eae6d839c16ec';
$twilio_number = '+14155238886'; // Número de prueba de Twilio (para SMS)
$destino = '+51970252386'; // Tu número verificado (SIN whatsapp:)

$mensaje = '¡Hola! Esta es una prueba de notificación de Hermes Express. Paquete registrado: #12345';

// URL de la API de Twilio
$url = "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json";

// Datos del mensaje SMS
$data = [
    'From' => $twilio_number,
    'To' => $destino,
    'Body' => $mensaje
];

// Configurar cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_USERPWD, "$sid:$token");
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

// Ejecutar petición
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Mostrar resultado
if ($httpCode == 201) {
    $result = json_decode($response, true);
    echo "✅ Mensaje enviado con éxito!\n";
    echo "SID: " . ($result['sid'] ?? 'N/A') . "\n";
    echo "Estado: " . ($result['status'] ?? 'N/A') . "\n";
} else {
    echo "❌ Error al enviar mensaje\n";
    echo "Código HTTP: $httpCode\n";
    echo "Respuesta: $response\n";
    if ($error) {
        echo "Error cURL: $error\n";
    }
}
