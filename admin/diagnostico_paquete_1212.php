<?php
/**
 * Diagnóstico específico para paquete 1212 y número +51912112380
 */

require_once '../config/config.php';
require_once '../config/whatsapp_helper.php';

echo "🔍 DIAGNÓSTICO PAQUETE 1212 - NÚMERO +51912112380\n";
echo "================================================\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Buscar paquete por código 1212
    echo "📦 BUSCANDO PAQUETE 1212...\n";
    $paquete_query = $db->query("
        SELECT p.*, u.nombre as repartidor_nombre, u.telefono as repartidor_telefono
        FROM paquetes p 
        LEFT JOIN usuarios u ON p.repartidor_id = u.id 
        WHERE p.codigo_seguimiento = '1212'
        ORDER BY p.id DESC 
        LIMIT 1
    ");
    
    if ($paquete_query && $paquete = $paquete_query->fetch_assoc()) {
        echo "✅ Paquete encontrado:\n";
        echo "  - ID: {$paquete['id']}\n";
        echo "  - Código: {$paquete['codigo_seguimiento']}\n";
        echo "  - Cliente: {$paquete['destinatario_nombre']}\n";
        echo "  - Teléfono: {$paquete['destinatario_telefono']}\n";
        echo "  - Repartidor: {$paquete['repartidor_nombre']}\n";
        echo "  - Estado: {$paquete['estado']}\n";
        echo "  - Fecha asignación: {$paquete['fecha_asignacion']}\n\n";
        
        // Verificar notificaciones WhatsApp para este paquete
        echo "📱 VERIFICANDO NOTIFICACIONES WHATSAPP...\n";
        $notif_query = $db->prepare("
            SELECT * FROM notificaciones_whatsapp 
            WHERE paquete_id = ? 
            ORDER BY fecha_envio DESC
        ");
        $notif_query->bind_param("i", $paquete['id']);
        $notif_query->execute();
        $notificaciones = $notif_query->get_result();
        
        if ($notificaciones->num_rows > 0) {
            echo "📨 Notificaciones encontradas:\n";
            while ($notif = $notificaciones->fetch_assoc()) {
                echo "  - ID: {$notif['id']} | Estado: {$notif['estado']} | Fecha: {$notif['fecha_envio']}\n";
                echo "  - Teléfono: {$notif['telefono_destinatario']}\n";
                echo "  - Mensaje: " . substr($notif['mensaje'], 0, 100) . "...\n";
            }
        } else {
            echo "❌ No hay notificaciones WhatsApp registradas para este paquete\n";
        }
        
        echo "\n🧪 PROBANDO ENVÍO DIRECTO AL NÚMERO +51912112380...\n";
        
        // Probar envío directo con FlexBis
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://whatsapp-service.flexbis.com/api/v1/message/text");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'numero_destinatario' => '+51912112380',
            'tipo_destinatario' => 'contacto',
            'tipo_mensaje' => 'texto',
            'texto' => "🧪 PRUEBA DIRECTA para paquete 1212\n📱 Número: +51912112380\n⏰ " . date('H:i:s')
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'flexbis-instance: ' . FLEXBIS_API_SID,
            'flexbis-token: ' . FLEXBIS_API_KEY
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "  HTTP: $http_code\n";
        echo "  Respuesta: $response\n";
        
        $json = json_decode($response, true);
        if ($json && isset($json['type']) && $json['type'] === 'success') {
            echo "  ✅ API RESPONDE OK - El mensaje debería llegar\n";
        } else {
            echo "  ❌ PROBLEMA CON LA API\n";
        }
        
        echo "\n🔧 PROBANDO SISTEMA WHATSAPP...\n";
        $whatsapp = new WhatsAppNotificaciones();
        $resultado = $whatsapp->notificarAsignacion($paquete['id']);
        
        if ($resultado) {
            echo "✅ Sistema WhatsApp procesó correctamente\n";
        } else {
            echo "❌ Error en sistema WhatsApp\n";
        }
        
        echo "\n💡 POSIBLES CAUSAS SI NO LLEGA:\n";
        echo "1. El número +51912112380 no tiene WhatsApp activo\n";
        echo "2. El número bloqueó mensajes de números desconocidos\n";
        echo "3. Mensaje filtrado como spam\n";
        echo "4. Delay en entrega (puede tardar 1-5 minutos)\n";
        echo "5. Número no registrado correctamente\n";
        
    } else {
        echo "❌ No se encontró paquete con código 1212\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n⏰ " . date('d/m/Y H:i:s') . "\n";
?>