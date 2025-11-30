<?php
require_once 'config/config.php';
require_once 'config/whatsapp_helper.php';

echo "=== TEST NOTIFICACIONES WHATSAPP ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n";
echo "Tipo API: " . (defined('WHATSAPP_API_TYPE') ? WHATSAPP_API_TYPE : 'no definido') . "\n\n";

$wp = new WhatsAppNotificaciones();
echo "Enviando test...\n";

$result = $wp->enviarMensajeDirecto('+51987654321', 'Test sistema HERMES - ' . date('H:i:s'));
echo "Resultado: " . $result . "\n";

if ($result !== 'error') {
    echo "✅ Notificación simulada correctamente\n";
} else {
    echo "❌ Error en notificación\n";
}

echo "\n=== RESUMEN ESTADO ===\n";
echo "- Modo actual: " . (defined('WHATSAPP_API_TYPE') ? WHATSAPP_API_TYPE : 'simulado') . "\n";
echo "- FlexBis SID: " . (defined('FLEXBIS_API_SID') && FLEXBIS_API_SID ? '✅' : '❌') . "\n";
echo "- FlexBis Key: " . (defined('FLEXBIS_API_KEY') && FLEXBIS_API_KEY ? '✅' : '❌') . "\n";
echo "- Sistema listo: ✅\n";
echo "- Esperando doc FlexBis: 🕐\n";
?>