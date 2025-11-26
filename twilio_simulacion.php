<?php
/**
 * Simulación de notificación Twilio para registro de paquetes
 * Este script muestra cómo se integraría Twilio al sistema real
 */

class TwilioNotificacion {
    private $sid;
    private $token;
    private $from_number;
    
    public function __construct() {
        $this->sid = 'AC7cde09ffb05d087aafa652c485a2529b';
        $this->token = '1ee60ed1e2208401b06eae6d839c16ec';
        $this->from_number = 'whatsapp:+14155238886'; // Cambiar cuando actualices cuenta
    }
    
    /**
     * Enviar notificación de paquete registrado
     */
    public function notificarPaqueteRegistrado($numero_cliente, $tracking, $destinatario) {
        $numero_cliente = $this->formatearNumero($numero_cliente);
        
        $mensaje = "🎉 *Hermes Express*\n\n";
        $mensaje .= "Su paquete ha sido registrado exitosamente:\n\n";
        $mensaje .= "📦 *Tracking:* $tracking\n";
        $mensaje .= "👤 *Destinatario:* $destinatario\n\n";
        $mensaje .= "Recibirá actualizaciones del estado de su envío.";
        
        return $this->enviarMensaje($numero_cliente, $mensaje);
    }
    
    /**
     * Enviar notificación de paquete en ruta
     */
    public function notificarEnRuta($numero_cliente, $tracking, $repartidor) {
        $numero_cliente = $this->formatearNumero($numero_cliente);
        
        $mensaje = "🚚 *Hermes Express*\n\n";
        $mensaje .= "Su paquete está en camino:\n\n";
        $mensaje .= "📦 *Tracking:* $tracking\n";
        $mensaje .= "👨‍✈️ *Repartidor:* $repartidor\n\n";
        $mensaje .= "Pronto llegará a su destino.";
        
        return $this->enviarMensaje($numero_cliente, $mensaje);
    }
    
    /**
     * Enviar notificación de entrega exitosa
     */
    public function notificarEntregado($numero_cliente, $tracking) {
        $numero_cliente = $this->formatearNumero($numero_cliente);
        
        $mensaje = "✅ *Hermes Express*\n\n";
        $mensaje .= "¡Paquete entregado exitosamente!\n\n";
        $mensaje .= "📦 *Tracking:* $tracking\n\n";
        $mensaje .= "Gracias por confiar en nosotros.";
        
        return $this->enviarMensaje($numero_cliente, $mensaje);
    }
    
    /**
     * Formatear número a formato internacional WhatsApp
     */
    private function formatearNumero($numero) {
        // Limpiar número
        $numero = preg_replace('/[^0-9]/', '', $numero);
        
        // Si no tiene código de país, agregar +51 (Perú)
        if (strlen($numero) == 9 && !str_starts_with($numero, '51')) {
            $numero = '51' . $numero;
        }
        
        return 'whatsapp:+' . $numero;
    }
    
    /**
     * Enviar mensaje via Twilio API
     */
    private function enviarMensaje($to, $body) {
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->sid}/Messages.json";
        
        $data = [
            'From' => $this->from_number,
            'To' => $to,
            'Body' => $body
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->sid}:{$this->token}");
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 201) {
            $result = json_decode($response, true);
            return [
                'exito' => true,
                'mensaje' => 'Notificación enviada',
                'sid' => $result['sid'] ?? null
            ];
        } else {
            return [
                'exito' => false,
                'mensaje' => 'Error al enviar',
                'error' => $response
            ];
        }
    }
}

// PRUEBA DE SIMULACIÓN
echo "=== SIMULACIÓN DE NOTIFICACIONES TWILIO ===\n\n";

$twilio = new TwilioNotificacion();

// Simular notificación de paquete registrado
echo "1. Paquete Registrado:\n";
$resultado = $twilio->notificarPaqueteRegistrado('970252386', 'PKG-12345', 'Juan Pérez');
echo "   Estado: " . ($resultado['exito'] ? '✅ Enviado' : '❌ Error') . "\n";
if (!$resultado['exito']) {
    echo "   Detalle: " . $resultado['error'] . "\n";
}
echo "\n";

// Simular notificación en ruta
echo "2. Paquete En Ruta:\n";
$resultado = $twilio->notificarEnRuta('970252386', 'PKG-12345', 'Carlos Ramos');
echo "   Estado: " . ($resultado['exito'] ? '✅ Enviado' : '❌ Error') . "\n";
if (!$resultado['exito']) {
    echo "   Detalle: " . $resultado['error'] . "\n";
}
echo "\n";

// Simular notificación entregado
echo "3. Paquete Entregado:\n";
$resultado = $twilio->notificarEntregado('970252386', 'PKG-12345');
echo "   Estado: " . ($resultado['exito'] ? '✅ Enviado' : '❌ Error') . "\n";
if (!$resultado['exito']) {
    echo "   Detalle: " . $resultado['error'] . "\n";
}
echo "\n";

echo "=== FIN DE SIMULACIÓN ===\n";
echo "\nNOTA: Para que funcione en producción:\n";
echo "1. Actualiza tu cuenta de Twilio (Upgrade)\n";
echo "2. Vincula tu número de WhatsApp\n";
echo "3. Los mensajes se enviarán automáticamente\n";
