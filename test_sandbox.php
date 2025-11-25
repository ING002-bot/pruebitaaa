<?php
/**
 * Script de prueba Twilio Sandbox
 * Las credenciales de prueba tienen limitaciones pero funcionan para enviar
 */

require_once 'config/config.php';

$sid = defined('TWILIO_ACCOUNT_SID') ? TWILIO_ACCOUNT_SID : '';
$token = defined('TWILIO_AUTH_TOKEN') ? TWILIO_AUTH_TOKEN : '';

echo "=== VERIFICACIÓN TWILIO SANDBOX ===\n\n";

echo "✓ SID: " . $sid . "\n";
echo "✓ Token: " . substr($token, 0, 8) . "..." . substr($token, -8) . "\n";
echo "✓ Número WhatsApp FROM: " . (defined('TWILIO_WHATSAPP_FROM') ? TWILIO_WHATSAPP_FROM : 'N/A') . "\n\n";

echo "NOTA IMPORTANTE SOBRE SANDBOX:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Las credenciales de PRUEBA (Sandbox) tienen limitaciones:\n\n";
echo "1. Solo puedes enviar a números APROBADOS\n";
echo "2. Los números deben estar agregados en la lista blanca\n";
echo "3. Para producción, debes ACTUALIZAR la cuenta\n\n";

echo "PASOS PARA APROBAR NÚMEROS EN SANDBOX:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "1. Ve a: https://www.twilio.com/console/sms/sandbox\n";
echo "2. Busca 'Participant phone numbers'\n";
echo "3. Click en 'Add participant phone number'\n";
echo "4. Ingresa el número del cliente (ej: +51987654321)\n";
echo "5. Listo, ahora puedes enviar a ese número\n\n";

echo "TEST DE CONEXIÓN:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Verificar si podemos conectar enviando un mensaje de prueba
require_once 'config/whatsapp_helper.php';

$whatsapp = new WhatsAppNotificaciones();

// Número de prueba (reemplaza con uno real)
$numero_prueba = '+51987654321'; // Cambiar a un número real

echo "\nIntentando enviar WhatsApp de prueba a: " . $numero_prueba . "\n";
echo "(Este número debe estar aprobado en Sandbox)\n\n";

$mensaje_prueba = "🧪 Mensaje de prueba desde Hermes Express\nHora: " . date('Y-m-d H:i:s');

// Llamar método privado via reflexión para probar envío
$result = $whatsapp->enviarMensajeDirecto($numero_prueba, $mensaje_prueba);

if ($result === true) {
    echo "✅ MENSAJE ENVIADO EXITOSAMENTE\n";
} else {
    echo "❌ ERROR AL ENVIAR: " . $result . "\n";
}

?>
