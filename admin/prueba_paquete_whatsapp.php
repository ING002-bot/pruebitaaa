<?php
/**
 * Prueba de creación de paquete con notificación automática
 */

require_once '../config/config.php';
require_once '../config/whatsapp_helper.php';

echo "🧪 PRUEBA DE CREACIÓN DE PAQUETE CON WHATSAPP AUTOMÁTICO\n";
echo "======================================================\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Crear paquete de prueba
    $codigo = "TEST" . rand(1000, 9999);
    $telefono = "+51903417579"; // Número que sabemos que funciona
    $repartidor_id = 1; // Carlos
    
    echo "📦 CREANDO PAQUETE DE PRUEBA...\n";
    echo "  - Código: $codigo\n";
    echo "  - Teléfono: $telefono\n";
    echo "  - Repartidor: Carlos (ID: $repartidor_id)\n\n";
    
    $sql = "INSERT INTO paquetes (
        codigo_seguimiento, destinatario_nombre, destinatario_telefono,
        direccion_completa, ciudad, provincia, distrito, peso, valor_declarado,
        costo_envio, prioridad, repartidor_id, estado, fecha_asignacion
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_ruta', NOW())";
    
    $stmt = $db->prepare($sql);
    $nombre = "Cliente Prueba";
    $direccion = "Av. Test 123, Chiclayo";
    $ciudad = "Lambayeque";
    $provincia = "Chiclayo";
    $distrito = "Chiclayo";
    $peso = 1.5;
    $valor = 50.0;
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
        
        // Ahora probar notificación
        echo "📱 ENVIANDO NOTIFICACIÓN WHATSAPP...\n";
        $whatsapp = new WhatsAppNotificaciones();
        $resultado = $whatsapp->notificarAsignacion($paquete_id);
        
        if ($resultado) {
            echo "✅ Notificación enviada correctamente\n\n";
            
            // Verificar que se registró en la BD
            echo "🔍 VERIFICANDO REGISTRO EN BD...\n";
            $check = $db->prepare("SELECT * FROM notificaciones_whatsapp WHERE paquete_id = ?");
            $check->bind_param("i", $paquete_id);
            $check->execute();
            $notif = $check->get_result()->fetch_assoc();
            
            if ($notif) {
                echo "✅ Notificación registrada:\n";
                echo "  - ID: {$notif['id']}\n";
                echo "  - Estado: {$notif['estado']}\n";
                echo "  - Teléfono: {$notif['telefono_destinatario']}\n";
                echo "  - Fecha: {$notif['fecha_envio']}\n";
            } else {
                echo "❌ Notificación NO se registró en BD\n";
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

echo "\n⏰ " . date('d/m/Y H:i:s') . "\n";
?>