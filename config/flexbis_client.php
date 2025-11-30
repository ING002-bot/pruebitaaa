<?php
/**
 * FlexBis WhatsApp API Client
 * Cliente PHP para integración con FlexBis WhatsApp Business API
 * 
 * @author HERMES EXPRESS
 * @version 1.0
 */

class FlexBisClient {
    
    private $api_sid;
    private $api_key;
    private $api_url;
    private $whatsapp_from;
    private $timeout = 30;
    
    /**
     * Constructor
     */
    public function __construct($config = []) {
        $this->api_sid = $config['api_sid'] ?? (defined('FLEXBIS_API_SID') ? FLEXBIS_API_SID : '');
        $this->api_key = $config['api_key'] ?? (defined('FLEXBIS_API_KEY') ? FLEXBIS_API_KEY : '');
        $this->api_url = $config['api_url'] ?? (defined('FLEXBIS_API_URL') ? FLEXBIS_API_URL : 'https://api.flexbis.com/v1/');
        $this->whatsapp_from = $config['whatsapp_from'] ?? (defined('FLEXBIS_WHATSAPP_FROM') ? FLEXBIS_WHATSAPP_FROM : '');
        
        $this->api_url = rtrim($this->api_url, '/');
    }
    
    /**
     * Verificar si las credenciales están configuradas
     */
    public function isConfigured() {
        return !empty($this->api_sid) && !empty($this->api_key) && !empty($this->whatsapp_from);
    }
    
    /**
     * Probar conectividad con la API
     */
    public function testConnection() {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Credenciales no configuradas',
                'missing' => $this->getMissingCredentials()
            ];
        }
        
        // Test simple: intentar enviar un mensaje de prueba a un número inválido
        // Esto nos dirá si la API responde correctamente
        $result = $this->makeRequest('/messages/chat', [
            'to' => '123456789', // Número inválido para test
            'body' => 'test'
        ], 'POST');
        
        if ($result['success'] || 
            (isset($result['http_code']) && $result['http_code'] === 422)) {
            // 422 es esperado para número inválido, significa que la API funciona
            return [
                'success' => true,
                'message' => 'API FlexBis responde correctamente',
                'endpoint' => '/send-message',
                'test_result' => $result
            ];
        }
        
        // Si es error de autenticación
        if (isset($result['http_code']) && $result['http_code'] === 401) {
            return [
                'success' => false,
                'error' => 'Credenciales inválidas - verificar API Key',
                'http_code' => 401
            ];
        }
        
        return [
            'success' => false,
            'error' => 'No se pudo conectar a la API FlexBis',
            'details' => $result
        ];
    }
    
    /**
     * Enviar mensaje de WhatsApp usando FlexBis API
     */
    public function sendMessage($to, $message, $options = []) {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'FlexBis no está configurado correctamente',
                'missing' => $this->getMissingCredentials()
            ];
        }
        
        // Limpiar número de teléfono (FlexBis espera formato 51903417579)
        $to = $this->cleanPhoneNumber($to);
        
        // Preparar datos según FlexBis API CORRECTA
        $data = [
            'numero_destinatario' => '+' . $to,
            'tipo_destinatario' => 'contacto',
            'tipo_mensaje' => 'texto',
            'texto' => $message
        ];
        
        // Opciones adicionales para otros tipos de mensajes
        if (isset($options['media_url'])) {
            $data['type'] = 'media';
            $data['media_url'] = $options['media_url'];
            if (isset($options['caption'])) {
                $data['caption'] = $options['caption'];
            }
        }
        
        $result = $this->makeRequest('/message/text', $data, 'POST');
        
        if ($result['success']) {
            return [
                'success' => true,
                'message_id' => $result['data']['message_id'] ?? $result['data']['id'] ?? 'flexbis_sent',
                'status' => $result['data']['status'] ?? 'sent',
                'data' => $result['data']
            ];
        }
        
        return $result;
    }
    
    /**
     * Obtener balance de la cuenta
     */
    public function getBalance() {
        $result = $this->makeRequest('/account/balance', [], 'GET');
        
        if ($result['success']) {
            return [
                'success' => true,
                'balance' => $result['data']['balance'] ?? null,
                'currency' => $result['data']['currency'] ?? 'USD',
                'data' => $result['data']
            ];
        }
        
        return $result;
    }
    
    /**
     * Obtener estado de un mensaje
     */
    public function getMessageStatus($message_id) {
        $result = $this->makeRequest("/messages/$message_id/status", [], 'GET');
        
        if ($result['success']) {
            return [
                'success' => true,
                'status' => $result['data']['status'] ?? 'unknown',
                'delivered_at' => $result['data']['delivered_at'] ?? null,
                'read_at' => $result['data']['read_at'] ?? null,
                'data' => $result['data']
            ];
        }
        
        return $result;
    }
    
    /**
     * Realizar petición HTTP a la API FlexBis
     */
    private function makeRequest($endpoint, $data = [], $method = 'GET') {
        // URL base CORRECTA para FlexBis
        $base_url = 'https://whatsapp-service.flexbis.com/api/v1';
        $url = $base_url . $endpoint;
        
        // Headers CORRECTOS según documentación FlexBis
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'flexbis-instance: ' . $this->api_sid,
            'flexbis-token: ' . $this->api_key,
            'User-Agent: HermesExpress-FlexBis/2.0'
        ];
        
        $ch = curl_init();
        
        // Configuración básica de cURL
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3
        ]);
        
        // Configurar método y datos según FlexBis CORRECTO
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                // FlexBis usa JSON, NO form-urlencoded
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'GET') {
            if (!empty($data)) {
                $url .= '?' . http_build_query($data);
                curl_setopt($ch, CURLOPT_URL, $url);
            }
        }
        
        // Ejecutar petición
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        // Manejar errores de cURL
        if ($curl_error) {
            return [
                'success' => false,
                'error' => 'cURL Error: ' . $curl_error,
                'endpoint' => $endpoint,
                'method' => $method
            ];
        }
        
        // Decodificar respuesta JSON
        $response_data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Respuesta JSON inválida: ' . json_last_error_msg(),
                'http_code' => $http_code,
                'raw_response' => $response
            ];
        }
        
        // Verificar código HTTP
        if ($http_code >= 200 && $http_code < 300) {
            return [
                'success' => true,
                'data' => $response_data,
                'http_code' => $http_code
            ];
        }
        
        return [
            'success' => false,
            'error' => $response_data['error'] ?? $response_data['message'] ?? 'Error HTTP ' . $http_code,
            'http_code' => $http_code,
            'data' => $response_data,
            'raw_response' => $response
        ];
    }
    
    /**
     * Limpiar y formatear número de teléfono
     */
    private function cleanPhoneNumber($phone) {
        // Remover caracteres no numéricos excepto +
        $phone = preg_replace('/[^0-9+]/', '', trim($phone));
        
        // FlexBis requiere formato +51XXXXXXXXX
        
        // Si ya tiene +51, devolverlo
        if (str_starts_with($phone, '+51')) {
            return $phone;
        }
        
        // Si solo tiene +, removerlo
        $phone = ltrim($phone, '+');
        
        // Si tiene 9 dígitos y empieza con 9, agregar +51
        if (strlen($phone) === 9 && str_starts_with($phone, '9')) {
            return '+51' . $phone;
        }
        
        // Si empieza con 51 y tiene 11 dígitos, agregar +
        if (str_starts_with($phone, '51') && strlen($phone) === 11) {
            return '+' . $phone;
        }
        
        // Si empieza con 0, removerlo y agregar +51
        if (str_starts_with($phone, '0') && strlen($phone) === 10) {
            return '+51' . substr($phone, 1);
        }
        
        // Si no tiene código de país y son 9 dígitos, agregar +51
        if (strlen($phone) >= 9 && strlen($phone) <= 9) {
            return '+51' . $phone;
        }
        
        // Default: agregar +51 si no tiene código de país
        if (!str_starts_with($phone, '51') && strlen($phone) === 9) {
            return '+51' . $phone;
        }
        
        return '+51' . ltrim($phone, '0');
    }
    
    /**
     * Obtener credenciales faltantes
     */
    private function getMissingCredentials() {
        $missing = [];
        
        if (empty($this->api_sid)) $missing[] = 'API SID';
        if (empty($this->api_key)) $missing[] = 'API Key';
        if (empty($this->whatsapp_from)) $missing[] = 'WhatsApp From Number';
        
        return $missing;
    }
    
    /**
     * Configurar timeout para peticiones
     */
    public function setTimeout($seconds) {
        $this->timeout = max(5, intval($seconds));
    }
    
    /**
     * Obtener configuración actual (sin exponer credenciales)
     */
    public function getConfig() {
        return [
            'api_url' => $this->api_url,
            'whatsapp_from' => $this->whatsapp_from,
            'has_sid' => !empty($this->api_sid),
            'has_key' => !empty($this->api_key),
            'is_configured' => $this->isConfigured(),
            'timeout' => $this->timeout
        ];
    }
    
    /**
     * Validar formato de webhook (para futuros webhooks)
     */
    public static function validateWebhookSignature($payload, $signature, $secret) {
        $expected = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }
    
    /**
     * Log de actividad (para debugging)
     */
    private function log($message, $level = 'info') {
        if (defined('FLEXBIS_DEBUG') && FLEXBIS_DEBUG) {
            $timestamp = date('Y-m-d H:i:s');
            error_log("[$timestamp] FlexBis [$level]: $message");
        }
    }
}

/**
 * Función helper para crear instancia rápida
 */
function createFlexBisClient($config = []) {
    return new FlexBisClient($config);
}

/**
 * Función helper para envío rápido de mensaje
 */
function sendFlexBisMessage($to, $message, $options = []) {
    $client = new FlexBisClient();
    return $client->sendMessage($to, $message, $options);
}