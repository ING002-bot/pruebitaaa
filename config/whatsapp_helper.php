<?php
/**
 * Helper para enviar notificaciones WhatsApp
 * Usando API de WhatsApp Business (puedes cambiar por la API que uses)
 */

class WhatsAppNotificaciones {
    private $api_url;
    private $api_token;
    private $db;
    
    public function __construct() {
        // Configurar con tu API de WhatsApp (ejemplo: Twilio, WhatsApp Business API, etc.)
        $this->api_url = 'https://api.whatsapp.com/send'; // Cambiar por tu API
        $this->api_token = 'TU_TOKEN_API'; // Configurar en config.php
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Enviar notificación de asignación de paquete
     */
    public function notificarAsignacion($paquete_id) {
        try {
            // Obtener datos del paquete
            $stmt = $this->db->prepare("
                SELECT p.*, u.nombre as repartidor_nombre, u.apellido as repartidor_apellido
                FROM paquetes p
                LEFT JOIN usuarios u ON p.repartidor_id = u.id
                WHERE p.id = ?
            ");
            $stmt->bind_param("i", $paquete_id);
            $stmt->execute();
            $paquete = $stmt->get_result()->fetch_assoc();
            
            if (!$paquete || empty($paquete['destinatario_telefono'])) {
                return false;
            }
            
            // Limpiar número de teléfono
            $telefono = $this->limpiarTelefono($paquete['destinatario_telefono']);
            
            // Crear mensaje
            $fecha_estimada = date('d/m/Y', strtotime($paquete['fecha_limite_entrega']));
            $mensaje = "🚚 *HERMES EXPRESS*\n\n";
            $mensaje .= "Hola *{$paquete['destinatario_nombre']}*\n\n";
            $mensaje .= "Su paquete con código *{$paquete['codigo_seguimiento']}* ha sido asignado a nuestro repartidor ";
            $mensaje .= "*{$paquete['repartidor_nombre']} {$paquete['repartidor_apellido']}*\n\n";
            $mensaje .= "📅 Fecha estimada de entrega: *{$fecha_estimada}*\n";
            $mensaje .= "📍 Dirección: {$paquete['direccion_completa']}\n\n";
            $mensaje .= "Gracias por confiar en nosotros! 📦";
            
            // Enviar notificación
            $enviado = $this->enviarMensaje($telefono, $mensaje);
            
            // Registrar en BD
            $tipo = 'asignacion';
            $estado = $enviado ? 'enviado' : 'fallido';
            $respuesta = $enviado ? 'Enviado exitosamente' : 'Error al enviar';
            
            $stmt = $this->db->prepare("
                INSERT INTO notificaciones_whatsapp 
                (paquete_id, telefono, mensaje, tipo, estado, respuesta_api, intentos) 
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->bind_param("isssss", $paquete_id, $telefono, $mensaje, $tipo, $estado, $respuesta);
            $stmt->execute();
            
            // Actualizar paquete
            if ($enviado) {
                $this->db->query("UPDATE paquetes SET notificacion_whatsapp_enviada = 1 WHERE id = $paquete_id");
            }
            
            return $enviado;
            
        } catch (Exception $e) {
            error_log("Error notificación WhatsApp: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enviar alerta de tiempo (24 horas antes)
     */
    public function enviarAlerta24Horas($paquete_id, $repartidor_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, u.telefono as repartidor_tel
                FROM paquetes p
                LEFT JOIN usuarios u ON p.repartidor_id = u.id
                WHERE p.id = ?
            ");
            $stmt->bind_param("i", $paquete_id);
            $stmt->execute();
            $paquete = $stmt->get_result()->fetch_assoc();
            
            if (!$paquete) return false;
            
            $telefono = $this->limpiarTelefono($paquete['repartidor_tel']);
            
            $mensaje = "⚠️ *ALERTA DE TIEMPO*\n\n";
            $mensaje .= "Quedan *24 horas* para entregar:\n\n";
            $mensaje .= "📦 Código: *{$paquete['codigo_seguimiento']}*\n";
            $mensaje .= "👤 Cliente: {$paquete['destinatario_nombre']}\n";
            $mensaje .= "📍 Dirección: {$paquete['direccion_completa']}\n";
            $mensaje .= "⏰ Vence: " . date('d/m/Y H:i', strtotime($paquete['fecha_limite_entrega']));
            
            $enviado = $this->enviarMensaje($telefono, $mensaje);
            
            // Registrar alerta en BD
            $tipo_alerta = '24_horas';
            $stmt = $this->db->prepare("
                INSERT INTO alertas_entrega (paquete_id, repartidor_id, tipo_alerta, mensaje) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("iiss", $paquete_id, $repartidor_id, $tipo_alerta, $mensaje);
            $stmt->execute();
            
            if ($enviado) {
                $this->db->query("UPDATE paquetes SET alerta_enviada = 1 WHERE id = $paquete_id");
            }
            
            return $enviado;
            
        } catch (Exception $e) {
            error_log("Error alerta 24h: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enviar mensaje por WhatsApp (implementar según tu API)
     */
    private function enviarMensaje($telefono, $mensaje) {
        // MÉTODO 1: Usando Twilio (requiere cuenta Twilio)
        /*
        $twilio_sid = 'TU_ACCOUNT_SID';
        $twilio_token = 'TU_AUTH_TOKEN';
        $twilio_whatsapp = 'whatsapp:+14155238886'; // Número de Twilio
        
        $client = new Twilio\Rest\Client($twilio_sid, $twilio_token);
        
        $message = $client->messages->create(
            "whatsapp:$telefono",
            [
                'from' => $twilio_whatsapp,
                'body' => $mensaje
            ]
        );
        
        return $message->sid ? true : false;
        */
        
        // MÉTODO 2: Usando API de WhatsApp Business Cloud
        /*
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $telefono,
            'type' => 'text',
            'text' => ['body' => $mensaje]
        ];
        
        $ch = curl_init($this->api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->api_token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return !empty($response);
        */
        
        // MÉTODO 3: Por ahora simulamos el envío (CAMBIAR POR TU API REAL)
        // En producción, implementar con tu proveedor de WhatsApp
        
        // Simular envío (quitar en producción)
        error_log("WhatsApp a $telefono: $mensaje");
        return true; // Cambiar por lógica real
    }
    
    /**
     * Limpiar y formatear número de teléfono
     */
    private function limpiarTelefono($telefono) {
        // Quitar espacios, guiones, paréntesis
        $telefono = preg_replace('/[^0-9+]/', '', $telefono);
        
        // Si no tiene código de país, agregar +51 (Perú)
        if (!str_starts_with($telefono, '+')) {
            $telefono = '+51' . $telefono;
        }
        
        return $telefono;
    }
}
