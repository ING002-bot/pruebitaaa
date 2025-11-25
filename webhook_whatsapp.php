<?php
/**
 * Webhook para recibir mensajes WhatsApp desde Twilio Sandbox
 * URL a configurar en Twilio Console: http://localhost/pruebitaaa/webhook_whatsapp.php
 */

// Leer entrada POST de Twilio
$body = file_get_contents('php://input');
$request = $_POST;

// Log de entrada
error_log("=== WEBHOOK TWILIO RECIBIDO ===");
error_log("Timestamp: " . date('Y-m-d H:i:s'));
error_log("Request: " . json_encode($request));

// Verificar que sea de Twilio
$from = $request['From'] ?? '';
$message = $request['Body'] ?? '';
$message_sid = $request['MessageSid'] ?? '';

if (empty($from) || empty($message_sid)) {
    error_log("❌ Webhook inválido - faltan parámetros");
    http_response_code(400);
    exit;
}

// Procesar el mensaje
error_log("📱 Mensaje recibido de: $from");
error_log("📝 Contenido: $message");
error_log("✓ SID: $message_sid");

// Guardar en base de datos (opcional)
require_once 'config/config.php';

try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        INSERT INTO logs_whatsapp (
            tipo,
            telefono,
            mensaje,
            referencia_externa,
            fecha_registro
        ) VALUES (?, ?, ?, ?, NOW())
    ");
    
    if ($stmt) {
        $tipo = 'entrada_webhook';
        $telefono = str_replace('whatsapp:', '', $from);
        
        $stmt->bind_param(
            "ssss",
            $tipo,
            $telefono,
            $message,
            $message_sid
        );
        
        $stmt->execute();
        $stmt->close();
        error_log("✅ Guardado en BD");
    }
} catch (Exception $e) {
    error_log("⚠️ Error guardando: " . $e->getMessage());
}

// Responder a Twilio con XML (requerido)
header('Content-Type: application/xml');
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<Response>';
echo '</Response>';

error_log("✅ Respuesta enviada a Twilio\n");

?>
