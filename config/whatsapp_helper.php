<?php
/**
 * Helper para enviar notificaciones WhatsApp
 * Soporta: Twilio, WhatsApp Business Cloud API, y simular envíos
 */

class WhatsAppNotificaciones {
    private $api_url;
    private $api_token;
    private $api_type = 'simulado'; // 'twilio', 'whatsapp_cloud', 'simulado', 'flexbis', 'hibrido'
    private $db;
    private $numero_empresa = null;
    private $flexbis = null;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        
        // Configurar según el tipo de API disponible
        $this->api_type = defined('WHATSAPP_API_TYPE') ? WHATSAPP_API_TYPE : 'flexbis';
        
        // Usar constantes FlexBis que están realmente definidas
        if (defined('FLEXBIS_API_KEY')) {
            $this->api_token = FLEXBIS_API_KEY;
        }
        
        if (defined('FLEXBIS_API_URL')) {
            $this->api_url = FLEXBIS_API_URL;
        }
        
        if (defined('FLEXBIS_WHATSAPP_FROM')) {
            $this->numero_empresa = FLEXBIS_WHATSAPP_FROM;
        }
        
        // Log del tipo de API configurado
        error_log("WhatsApp Helper initialized with API type: " . $this->api_type);
        
        // Si es modo híbrido, cargar también FlexBis
        if ($this->api_type === 'hibrido' || $this->api_type === 'flexbis') {
            if (file_exists(__DIR__ . '/flexbis_client.php')) {
                require_once __DIR__ . '/flexbis_client.php';
                $this->flexbis = new FlexBisClient();
            }
        }
    }
    
    /**
     * Enviar notificación de asignación de paquete al cliente
     * @param int $paquete_id ID del paquete
     * @return bool true si se envió o simuló correctamente
     */
    public function notificarAsignacion($paquete_id) {
        try {
            // Obtener datos del paquete
            $stmt = $this->db->prepare("
                SELECT p.*, 
                       u.nombre as repartidor_nombre, 
                       u.apellido as repartidor_apellido,
                       u.telefono as repartidor_telefono
                FROM paquetes p
                LEFT JOIN usuarios u ON p.repartidor_id = u.id
                WHERE p.id = ?
            ");
            
            if (!$stmt) {
                error_log("Error preparando consulta en notificarAsignacion: " . $this->db->error);
                return false;
            }
            
            $stmt->bind_param("i", $paquete_id);
            $stmt->execute();
            $paquete = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            // Validar datos necesarios
            if (!$paquete) {
                error_log("Paquete no encontrado: $paquete_id");
                return false;
            }
            
            if (empty($paquete['destinatario_telefono'])) {
                error_log("Paquete sin teléfono del destinatario: $paquete_id");
                return false;
            }
            
            if (empty($paquete['repartidor_id'])) {
                error_log("Paquete sin repartidor asignado: $paquete_id");
                return false;
            }
            
            // Limpiar número de teléfono
            $telefono = $this->limpiarTelefono($paquete['destinatario_telefono']);
            
            // Crear mensaje personalizado
            $fecha_estimada = !empty($paquete['fecha_limite_entrega']) 
                ? date('d/m/Y', strtotime($paquete['fecha_limite_entrega']))
                : date('d/m/Y', strtotime('+2 days'));
            
            $nombre_repartidor = $paquete['repartidor_nombre'] 
                ? trim($paquete['repartidor_nombre'] . ' ' . $paquete['repartidor_apellido'])
                : 'nuestro equipo de repartidores';
            
            $mensaje = $this->construirMensajeAsignacion(
                $paquete['destinatario_nombre'],
                $paquete['codigo_seguimiento'],
                $nombre_repartidor,
                $paquete['repartidor_telefono'],
                $fecha_estimada,
                $paquete['direccion_completa']
            );
            
            // Enviar notificación
            $respuesta = $this->enviarMensaje($telefono, $mensaje, 'asignacion');
            $enviado = !empty($respuesta) && $respuesta !== 'error';
            
            // Registrar intento de envío
            $this->registrarNotificacion(
                $paquete_id,
                $telefono,
                $mensaje,
                'asignacion',
                $enviado ? 'enviado' : 'fallido',
                $respuesta
            );
            
            // Actualizar estado en paquete
            if ($enviado) {
                $update_stmt = $this->db->prepare("
                    UPDATE paquetes 
                    SET notificacion_whatsapp_enviada = 1, 
                        fecha_notificacion_whatsapp = NOW()
                    WHERE id = ?
                ");
                if ($update_stmt) {
                    $update_stmt->bind_param("i", $paquete_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
            }
            
            return $enviado;
            
        } catch (Exception $e) {
            error_log("Error en notificarAsignacion: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enviar alerta 24 horas antes del vencimiento
     * @param int $paquete_id ID del paquete
     * @param int $repartidor_id ID del repartidor
     * @return bool
     */
    public function enviarAlerta24Horas($paquete_id, $repartidor_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, u.telefono as repartidor_tel, u.nombre, u.apellido
                FROM paquetes p
                LEFT JOIN usuarios u ON p.repartidor_id = u.id
                WHERE p.id = ? AND p.repartidor_id = ?
            ");
            
            if (!$stmt) return false;
            
            $stmt->bind_param("ii", $paquete_id, $repartidor_id);
            $stmt->execute();
            $paquete = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$paquete || empty($paquete['repartidor_tel'])) {
                return false;
            }
            
            $telefono = $this->limpiarTelefono($paquete['repartidor_tel']);
            
            $mensaje = $this->construirMensajeAlerta(
                $paquete['nombre'],
                $paquete['codigo_seguimiento'],
                $paquete['destinatario_nombre'],
                $paquete['direccion_completa'],
                date('d/m/Y H:i', strtotime($paquete['fecha_limite_entrega']))
            );
            
            $respuesta = $this->enviarMensaje($telefono, $mensaje, 'alerta_24h');
            $enviado = !empty($respuesta) && $respuesta !== 'error';
            
            $this->registrarNotificacion(
                $paquete_id,
                $telefono,
                $mensaje,
                'alerta_24h',
                $enviado ? 'enviado' : 'fallido',
                $respuesta
            );
            
            return $enviado;
            
        } catch (Exception $e) {
            error_log("Error en enviarAlerta24Horas: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Notificar entrega exitosa al cliente
     * @param int $paquete_id ID del paquete
     * @param string $receptor_nombre Nombre de quién recibió
     * @return bool
     */
    public function notificarEntregaExitosa($paquete_id, $receptor_nombre = '') {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, u.nombre as repartidor_nombre, u.apellido as repartidor_apellido
                FROM paquetes p
                LEFT JOIN usuarios u ON p.repartidor_id = u.id
                WHERE p.id = ?
            ");
            
            if (!$stmt) return false;
            
            $stmt->bind_param("i", $paquete_id);
            $stmt->execute();
            $paquete = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$paquete || empty($paquete['destinatario_telefono'])) {
                return false;
            }
            
            $telefono = $this->limpiarTelefono($paquete['destinatario_telefono']);
            
            $mensaje = $this->construirMensajeEntregaExitosa(
                $paquete['destinatario_nombre'],
                $paquete['codigo_seguimiento'],
                $receptor_nombre ?: 'Recibido',
                trim($paquete['repartidor_nombre'] . ' ' . $paquete['repartidor_apellido'])
            );
            
            $respuesta = $this->enviarMensaje($telefono, $mensaje, 'entrega_exitosa');
            $enviado = !empty($respuesta) && $respuesta !== 'error';
            
            $this->registrarNotificacion(
                $paquete_id,
                $telefono,
                $mensaje,
                'entrega_exitosa',
                $enviado ? 'enviado' : 'fallido',
                $respuesta
            );
            
            return $enviado;
            
        } catch (Exception $e) {
            error_log("Error en notificarEntregaExitosa: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Notificar cuando hay problema en la entrega
     * @param int $paquete_id ID del paquete
     * @param string $motivo Motivo del problema
     * @return bool
     */
    public function notificarProblemaEntrega($paquete_id, $motivo = 'no_encontrado') {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, u.nombre as repartidor_nombre, u.apellido as repartidor_apellido
                FROM paquetes p
                LEFT JOIN usuarios u ON p.repartidor_id = u.id
                WHERE p.id = ?
            ");
            
            if (!$stmt) return false;
            
            $stmt->bind_param("i", $paquete_id);
            $stmt->execute();
            $paquete = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$paquete || empty($paquete['destinatario_telefono'])) {
                return false;
            }
            
            $telefono = $this->limpiarTelefono($paquete['destinatario_telefono']);
            
            $mensaje = $this->construirMensajeProblema(
                $paquete['destinatario_nombre'],
                $paquete['codigo_seguimiento'],
                $motivo
            );
            
            $respuesta = $this->enviarMensaje($telefono, $mensaje, 'problema_entrega');
            $enviado = !empty($respuesta) && $respuesta !== 'error';
            
            $this->registrarNotificacion(
                $paquete_id,
                $telefono,
                $mensaje,
                'problema_entrega',
                $enviado ? 'enviado' : 'fallido',
                $respuesta
            );
            
            return $enviado;
            
        } catch (Exception $e) {
            error_log("Error en notificarProblemaEntrega: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Construir mensaje de asignación
     */
    private function construirMensajeAsignacion($cliente, $codigo, $repartidor, $tel_repartidor, $fecha, $direccion) {
        $mensaje = "🚚 *HERMES EXPRESS*\n";
        $mensaje .= "─────────────────────\n\n";
        $mensaje .= "¡Hola *$cliente*! 👋\n\n";
        $mensaje .= "Tu paquete ha sido asignado para entrega\n\n";
        $mensaje .= "📦 *Código:* $codigo\n";
        $mensaje .= "🚘 *Repartidor:* $repartidor\n";
        $mensaje .= "📅 *Fecha estimada:* $fecha\n";
        $mensaje .= "📍 *Dirección:* $direccion\n\n";
        
        if (!empty($tel_repartidor)) {
            $tel_limpio = $this->limpiarTelefono($tel_repartidor);
            $mensaje .= "📱 *Contacto repartidor:* " . substr($tel_limpio, -9) . "\n\n";
        }
        
        $mensaje .= "Gracias por confiar en nosotros! 🙏\n";
        $mensaje .= "HERMES EXPRESS LOGISTIC";
        
        return $mensaje;
    }
    
    /**
     * Construir mensaje de alerta
     */
    private function construirMensajeAlerta($nombre_repartidor, $codigo, $cliente, $direccion, $vencimiento) {
        $mensaje = "⚠️ *ALERTA DE ENTREGA*\n";
        $mensaje .= "─────────────────────\n\n";
        $mensaje .= "Hola *$nombre_repartidor*,\n\n";
        $mensaje .= "Quedan *24 HORAS* para entregar:\n\n";
        $mensaje .= "📦 *Código:* $codigo\n";
        $mensaje .= "👤 *Cliente:* $cliente\n";
        $mensaje .= "📍 *Dirección:* $direccion\n";
        $mensaje .= "⏰ *Vence:* $vencimiento\n\n";
        $mensaje .= "Por favor confirma la entrega antes del plazo";
        
        return $mensaje;
    }
    
    /**
     * Construir mensaje de entrega exitosa
     */
    private function construirMensajeEntregaExitosa($cliente, $codigo, $receptor, $repartidor) {
        $mensaje = "✅ *PAQUETE ENTREGADO*\n";
        $mensaje .= "─────────────────────\n\n";
        $mensaje .= "¡Hola *$cliente*! 🎉\n\n";
        $mensaje .= "Tu paquete ha sido entregado exitosamente\n\n";
        $mensaje .= "📦 *Código:* $codigo\n";
        $mensaje .= "👤 *Recibido por:* $receptor\n";
        $mensaje .= "🚘 *Repartidor:* $repartidor\n\n";
        $mensaje .= "Gracias por tu compra 🙏\n";
        $mensaje .= "HERMES EXPRESS LOGISTIC";
        
        return $mensaje;
    }
    
    /**
     * Construir mensaje de problema en entrega
     */
    private function construirMensajeProblema($cliente, $codigo, $motivo) {
        $motivos_descripcion = [
            'no_encontrado' => 'no fue posible ubicar la dirección',
            'rechazada' => 'fue rechazado',
            'destinatario_ausente' => 'el destinatario no se encontraba disponible'
        ];
        
        $descripcion = $motivos_descripcion[$motivo] ?? 'hubo un problema en la entrega';
        
        $mensaje = "⚠️ *PROBLEMA EN ENTREGA*\n";
        $mensaje .= "─────────────────────\n\n";
        $mensaje .= "Hola *$cliente*,\n\n";
        $mensaje .= "Lamentamos informarte que tu paquete *$codigo* ";
        $mensaje .= "$descripcion.\n\n";
        $mensaje .= "Nuestro equipo se contactará pronto para coordinar.\n\n";
        $mensaje .= "¿Necesitas ayuda? Contáctanos 📱";
        
        return $mensaje;
    }
    
    /**
     * Enviar WhatsApp directamente (público para pruebas)
     * @param string $telefono Número de teléfono
     * @param string $mensaje Mensaje a enviar
     * @return string Message SID si éxito, 'error' si falla
     */
    public function enviarMensajeDirecto($telefono, $mensaje) {
        $telefono_limpio = $this->limpiarTelefono($telefono);
        
        if (empty($telefono_limpio)) {
            return 'error';
        }
        
        return $this->enviarMensaje($telefono_limpio, $mensaje, 'prueba_directa');
    }

    /**
     * Enviar mensaje por WhatsApp
     * @param string $telefono Número de teléfono (formato +51XXX...)
     * @param string $mensaje Contenido del mensaje
     * @param string $tipo Tipo de notificación
     * @return string Respuesta o 'error'
     */
    private function enviarMensaje($telefono, $mensaje, $tipo = 'general') {
        if ($this->api_type === 'simulado') {
            return $this->simularEnvio($telefono, $mensaje, $tipo);
        }
        
        if ($this->api_type === 'twilio') {
            return $this->enviarConTwilio($telefono, $mensaje);
        }
        
        if ($this->api_type === 'whatsapp_cloud') {
            return $this->enviarConWhatsAppCloud($telefono, $mensaje);
        }
        
        if ($this->api_type === 'flexbis') {
            return $this->enviarConFlexbis($telefono, $mensaje);
        }
        
        if ($this->api_type === 'hibrido') {
            return $this->enviarConModoHibrido($telefono, $mensaje, $tipo);
        }
        
        return 'error';
    }
    
    /**
     * Simular envío de WhatsApp (para desarrollo/testing)
     */
    private function simularEnvio($telefono, $mensaje, $tipo) {
        $log = [
            'timestamp' => date('Y-m-d H:i:s'),
            'tipo' => $tipo,
            'telefono' => $telefono,
            'mensaje_longitud' => strlen($mensaje),
            'preview' => substr($mensaje, 0, 100) . '...'
        ];
        
        error_log("📱 [WhatsApp Simulado] " . json_encode($log));
        
        // Retornar un ID simulado
        return 'sim_' . bin2hex(random_bytes(8));
    }
    
    /**
     * Enviar con Twilio
     */
    private function enviarConTwilio($telefono, $mensaje) {
        $twilio_sid = defined('TWILIO_ACCOUNT_SID') ? constant('TWILIO_ACCOUNT_SID') : '';
        $twilio_token = defined('TWILIO_AUTH_TOKEN') ? constant('TWILIO_AUTH_TOKEN') : '';
        $twilio_from = defined('TWILIO_WHATSAPP_FROM') ? constant('TWILIO_WHATSAPP_FROM') : 'whatsapp:+14155238886';
        
        if (empty($twilio_sid) || empty($twilio_token)) {
            error_log("Twilio: Credenciales no configuradas en config.php");
            return 'error';
        }
        
        try {
            // URL de la API de Twilio para WhatsApp
            $url = 'https://api.twilio.com/2010-04-01/Accounts/' . $twilio_sid . '/Messages.json';
            
            // Datos del mensaje
            $post_data = [
                'From' => $twilio_from,
                'To' => 'whatsapp:' . $telefono,
                'Body' => $mensaje
            ];
            
            // Crear credenciales básicas
            $auth = base64_encode($twilio_sid . ':' . $twilio_token);
            
            // Inicializar cURL
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Basic ' . $auth,
                'Content-Type: application/x-www-form-urlencoded'
            ]);
            
            // Ejecutar
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);
            
            // Verificar respuesta
            if ($http_code === 201) {
                $result = json_decode($response, true);
                error_log("✅ Twilio WhatsApp enviado: SID " . ($result['sid'] ?? 'N/A'));
                return $result['sid'] ?? 'success';
            } else {
                error_log("❌ Twilio Error HTTP $http_code: $response");
                if ($curl_error) {
                    error_log("❌ cURL Error: $curl_error");
                }
                return 'error';
            }
            
        } catch (Exception $e) {
            error_log("❌ Twilio Exception: " . $e->getMessage());
            return 'error';
        }
    }
    
    /**
     * Enviar con WhatsApp Business Cloud API
     */
    private function enviarConWhatsAppCloud($telefono, $mensaje) {
        if (empty($this->api_url) || empty($this->api_token)) {
            error_log("WhatsApp Cloud: Configuración incompleta");
            return 'error';
        }
        
        $data = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $telefono,
            'type' => 'text',
            'text' => ['body' => $mensaje]
        ];
        
        $ch = curl_init($this->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->api_token,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $result = json_decode($response, true);
            return $result['messages'][0]['id'] ?? 'success';
        }
        
        error_log("WhatsApp Cloud API Error: HTTP $http_code - $response");
        return 'error';
    }

    /**
     * Enviar con Flexbis WhatsApp API usando FlexBisClient
     */
    private function enviarConFlexbis($telefono, $mensaje) {
        try {
            // Incluir la clase FlexBis si no está cargada
            if (!class_exists('FlexBisClient')) {
                require_once __DIR__ . '/flexbis_client.php';
            }
            
            // Crear instancia del cliente FlexBis
            $flexbis = new FlexBisClient();
            
            // Verificar configuración
            if (!$flexbis->isConfigured()) {
                error_log("❌ FlexBis no configurado correctamente");
                return 'error';
            }
            
            // Enviar mensaje usando la clase FlexBis
            $result = $flexbis->sendMessage($telefono, $mensaje);
            
            if ($result['success']) {
                $message_id = $result['message_id'] ?? 'success';
                error_log("✅ FlexBis WhatsApp enviado: ID $message_id a $telefono");
                return $message_id;
            } else {
                error_log("❌ FlexBis Error: " . $result['error']);
                return 'error';
            }
            
        } catch (Exception $e) {
            error_log("❌ FlexBis Exception: " . $e->getMessage());
            return 'error';
        }
    }
    
    /**
     * Limpiar y formatear número de teléfono
     * @param string $telefono Número a limpiar
     * @return string Número formateado (+51...)
     */
    private function limpiarTelefono($telefono) {
        // Quitar espacios, guiones, paréntesis
        $telefono = preg_replace('/[^0-9+]/', '', trim($telefono));
        
        // Si empieza con +, devolverlo así
        if (str_starts_with($telefono, '+')) {
            return $telefono;
        }
        
        // Si tiene 9 dígitos (Perú), agregar +51
        if (strlen($telefono) === 9) {
            return '+51' . $telefono;
        }
        
        // Si tiene 11 dígitos y empieza con 0 (Perú), agregar +51
        if (strlen($telefono) === 11 && str_starts_with($telefono, '0')) {
            return '+51' . substr($telefono, 1);
        }
        
        // Si no tiene +, agregar +51
        if (!str_starts_with($telefono, '+')) {
            return '+51' . $telefono;
        }
        
        return $telefono;
    }
    
    /**
     * Registrar notificación en BD
     */
    private function registrarNotificacion($paquete_id, $telefono, $mensaje, $tipo, $estado, $respuesta) {
        try {
            // Verificar si tabla existe
            $result = $this->db->query("SHOW TABLES LIKE 'notificaciones_whatsapp'");
            
            if ($result && $result->num_rows > 0) {
                // Insertar registro
                $stmt = $this->db->prepare("
                    INSERT INTO notificaciones_whatsapp 
                    (paquete_id, telefono, mensaje, tipo, estado, respuesta_api, fecha_envio, intentos) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), 1)
                    ON DUPLICATE KEY UPDATE
                    intentos = intentos + 1,
                    estado = ?,
                    respuesta_api = ?,
                    fecha_envio = NOW()
                ");
                
                if ($stmt) {
                    $stmt->bind_param("isssssss", 
                        $paquete_id, $telefono, $mensaje, $tipo, $estado, 
                        substr($respuesta, 0, 255), $estado, substr($respuesta, 0, 255)
                    );
                    $stmt->execute();
                    $stmt->close();
                }
            }
        } catch (Exception $e) {
            error_log("Error al registrar notificación WhatsApp: " . $e->getMessage());
        }
    }
    
    /**
     * Modo híbrido: simula envío pero prueba conexión FlexBis
     */
    private function enviarConModoHibrido($telefono, $mensaje, $tipo) {
        // Siempre simular el envío (para no consumir créditos reales)
        $resultado_simulado = $this->simularEnvio($telefono, $mensaje, $tipo);
        
        // Paralelamente, probar FlexBis para debugging
        if ($this->flexbis && $this->flexbis->isConfigured()) {
            try {
                // Solo test de conexión, no envío real
                $test_connection = $this->flexbis->testConnection();
                
                $log = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'mode' => 'hibrido',
                    'telefono' => $telefono,
                    'simulado' => true,
                    'flexbis_connection' => $test_connection['success'] ? 'OK' : 'ERROR',
                    'flexbis_error' => $test_connection['error'] ?? null
                ];
                
                error_log("MODO HÍBRIDO: " . json_encode($log));
                
                // Si queremos probar envío real (comentado por defecto)
                // $result = $this->flexbis->sendMessage($telefono, "[TEST] " . $mensaje);
                
            } catch (Exception $e) {
                error_log("MODO HÍBRIDO - FlexBis test error: " . $e->getMessage());
            }
        }
        
        return $resultado_simulado;
    }
}
