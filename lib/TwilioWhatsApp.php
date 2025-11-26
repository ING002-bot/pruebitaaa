<?php
/**
 * Clase para manejar notificaciones via Twilio WhatsApp
 * Autor: Sistema Hermes Express
 * Fecha: 26/11/2025
 */

class TwilioWhatsApp {
    private $sid;
    private $token;
    private $from_number;
    private $enabled;
    
    public function __construct() {
        // Configuración de Twilio
        $this->sid = 'AC7cde09ffb05d087aafa652c485a2529b';
        $this->token = '1ee60ed1e2208401b06eae6d839c16ec';
        $this->from_number = 'whatsapp:+14155238886';
        $this->enabled = true; // Cambiar a false para deshabilitar notificaciones
    }
    
    /**
     * Notificar registro de nuevo paquete
     */
    public function notificarNuevoPaquete($telefono, $tracking, $destinatario, $direccion) {
        if (!$this->enabled) return ['exito' => false, 'mensaje' => 'Notificaciones deshabilitadas'];
        
        $mensaje = "🎉 *Hermes Express*\n\n";
        $mensaje .= "Su paquete ha sido registrado:\n\n";
        $mensaje .= "📦 *Tracking:* $tracking\n";
        $mensaje .= "👤 *Destinatario:* $destinatario\n";
        $mensaje .= "📍 *Dirección:* $direccion\n\n";
        $mensaje .= "Le notificaremos cuando esté en ruta.";
        
        return $this->enviar($telefono, $mensaje);
    }
    
    /**
     * Notificar que el paquete está en ruta
     */
    public function notificarEnRuta($telefono, $tracking, $repartidor, $placa) {
        if (!$this->enabled) return ['exito' => false, 'mensaje' => 'Notificaciones deshabilitadas'];
        
        $mensaje = "🚚 *Hermes Express - En Ruta*\n\n";
        $mensaje .= "Su paquete está en camino:\n\n";
        $mensaje .= "📦 *Tracking:* $tracking\n";
        $mensaje .= "👨‍✈️ *Repartidor:* $repartidor\n";
        if ($placa) $mensaje .= "🚗 *Vehículo:* $placa\n";
        $mensaje .= "\nEstimamos llegar en las próximas horas.";
        
        return $this->enviar($telefono, $mensaje);
    }
    
    /**
     * Notificar entrega exitosa
     */
    public function notificarEntregado($telefono, $tracking, $recibio) {
        if (!$this->enabled) return ['exito' => false, 'mensaje' => 'Notificaciones deshabilitadas'];
        
        $mensaje = "✅ *Hermes Express - Entregado*\n\n";
        $mensaje .= "¡Paquete entregado exitosamente!\n\n";
        $mensaje .= "📦 *Tracking:* $tracking\n";
        if ($recibio) $mensaje .= "✍️ *Recibió:* $recibio\n";
        $mensaje .= "\nGracias por confiar en Hermes Express.";
        
        return $this->enviar($telefono, $mensaje);
    }
    
    /**
     * Notificar problema con entrega
     */
    public function notificarProblema($telefono, $tracking, $motivo) {
        if (!$this->enabled) return ['exito' => false, 'mensaje' => 'Notificaciones deshabilitadas'];
        
        $mensaje = "⚠️ *Hermes Express - Aviso*\n\n";
        $mensaje .= "Hubo un inconveniente con su paquete:\n\n";
        $mensaje .= "📦 *Tracking:* $tracking\n";
        $mensaje .= "📝 *Motivo:* $motivo\n\n";
        $mensaje .= "Contactaremos con usted pronto.";
        
        return $this->enviar($telefono, $mensaje);
    }
    
    /**
     * Formatear número a formato internacional
     */
    private function formatearNumero($numero) {
        // Limpiar número
        $numero = preg_replace('/[^0-9]/', '', $numero);
        
        // Si no tiene código de país, agregar +51 (Perú)
        if (strlen($numero) == 9) {
            $numero = '51' . $numero;
        }
        
        return 'whatsapp:+' . $numero;
    }
    
    /**
     * Enviar mensaje via API de Twilio
     */
    private function enviar($telefono, $mensaje) {
        try {
            $telefono_formateado = $this->formatearNumero($telefono);
            
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->sid}/Messages.json";
            
            $data = [
                'From' => $this->from_number,
                'To' => $telefono_formateado,
                'Body' => $mensaje
            ];
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_USERPWD, "{$this->sid}:{$this->token}");
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($httpCode == 201) {
                $result = json_decode($response, true);
                return [
                    'exito' => true,
                    'mensaje' => 'Notificación enviada',
                    'sid' => $result['sid'] ?? null,
                    'estado' => $result['status'] ?? null
                ];
            } else {
                // Log del error pero no detener el proceso
                error_log("Twilio Error: " . $response);
                return [
                    'exito' => false,
                    'mensaje' => 'No se pudo enviar notificación',
                    'error' => json_decode($response, true)
                ];
            }
        } catch (Exception $e) {
            error_log("Twilio Exception: " . $e->getMessage());
            return [
                'exito' => false,
                'mensaje' => 'Error en el servicio de notificaciones',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Verificar si las notificaciones están habilitadas
     */
    public function estaHabilitado() {
        return $this->enabled;
    }
    
    /**
     * Habilitar o deshabilitar notificaciones
     */
    public function setHabilitado($estado) {
        $this->enabled = (bool)$estado;
    }
}
