<?php
/**
 * Prueba específica con el número +51912112380
 */

require_once '../config/config.php';
require_once '../config/whatsapp_helper.php';

echo "🧪 PRUEBA ESPECÍFICA CON +51912112380\n";
echo "=====================================\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Crear paquete específicamente para julio con +51912112380
    $codigo = "JULIO" . rand(100, 999);
    $telefono = "+51912112380";
    $repartidor_id = 1; // Carlos
    
    echo "📦 CREANDO PAQUETE PARA JULIO...\n";
    echo "  - Código: $codigo\n";
    echo "  - Teléfono: $telefono\n";
    echo "  - Cliente: Julio Test\n\n";
    
    $sql = "INSERT INTO paquetes (
        codigo_seguimiento, destinatario_nombre, destinatario_telefono,
        direccion_completa, ciudad, provincia, distrito, peso, valor_declarado,
        costo_envio, prioridad, repartidor_id, estado, fecha_asignacion
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_ruta', NOW())";
    
    $stmt = $db->prepare($sql);
    
    $nombre = "Julio Test";
    $direccion = "Av. Julio 456, Chiclayo";
    $ciudad = "Lambayeque";
    $provincia = "Chiclayo";
    $distrito = "Chiclayo";
    $peso = 1.0;
    $valor = 30.0;
    $costo = 10.0;
    $prioridad = "normal";
    
    $stmt->bind_param(
        "sssssssddssi",
        $codigo,
        $nombre,
        $telefono,
        $direccion,
        $ciudad,
        $provincia,
        $distrito,
        $peso,
        $valor,
        $costo,
        $prioridad,
        $repartidor_id
    );
    
    if ($stmt->execute()) {
        $paquete_id = $db->insert_id;
        echo "✅ Paquete creado con ID: $paquete_id\n\n";
        
        // Notificación con sistema nuevo
        echo "📱 ENVIANDO NOTIFICACIÓN WHATSAPP A +51912112380...\n";
        $whatsapp = new WhatsAppNotificaciones();
        $resultado = $whatsapp->notificarAsignacion($paquete_id);
        
        if ($resultado) {
            echo "✅ Sistema reporta envío exitoso\n\n";
            
            // Verificar registro
            echo "🔍 VERIFICANDO REGISTRO EN BD...\n";
            $check = $db->prepare("SELECT * FROM notificaciones_whatsapp WHERE paquete_id = ? ORDER BY id DESC LIMIT 1");
            $check->bind_param("i", $paquete_id);
            $check->execute();
            $notif = $check->get_result()->fetch_assoc();
            
            if ($notif) {
                echo "✅ Notificación registrada:\n";
                echo "  - ID: {$notif['id']}\n";
                echo "  - Estado: {$notif['estado']}\n";
                echo "  - Mensaje: " . substr($notif['mensaje'], 0, 100) . "...\n";
                echo "  - Fecha: {$notif['fecha_envio']}\n\n";
            }
            
            // Probar llamada directa a API FlexBis
            echo "🔧 VERIFICANDO RESPUESTA DIRECTA DE FLEXBIS...\n";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://whatsapp-service.flexbis.com/api/v1/message/text");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'numero_destinatario' => $telefono,
                'tipo_destinatario' => 'contacto',
                'tipo_mensaje' => 'texto',
                'texto' => "🧪 PRUEBA DIRECTA 2 para $codigo\n📱 Número: $telefono\n⏰ " . date('H:i:s') . "\n\nSi recibes este mensaje, el sistema funciona correctamente."
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'flexbis-instance: ' . FLEXBIS_API_SID,
                'flexbis-token: ' . FLEXBIS_API_KEY
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            echo "  - HTTP Code: $http_code\n";
            echo "  - Respuesta: $response\n\n";
            
            $json = json_decode($response, true);
            if ($json && isset($json['type']) && $json['type'] === 'success') {
                echo "✅ FlexBis API responde correctamente\n";
                echo "💡 Si no llega el mensaje, el problema está en:\n";
                echo "   1. El número no tiene WhatsApp\n";
                echo "   2. Configuración de privacidad del usuario\n";
                echo "   3. El número está inactivo\n";
            } else {
                echo "❌ Problema con FlexBis API\n";
            }
            
        } else {
            echo "❌ Error al enviar notificación\n";
        }
        
    } else {
        echo "❌ Error al crear paquete: " . $stmt->error . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n📞 RECUERDA: Confirma con Julio si tiene WhatsApp activo en +51912112380\n";
echo "⏰ " . date('d/m/Y H:i:s') . "\n";
?>