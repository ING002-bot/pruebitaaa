<?php
/**
 * Crear paquete de prueba y asignar repartidor automáticamente
 */

require_once '../config/config.php';
require_once '../config/whatsapp_helper.php';

echo "🧪 CREANDO PAQUETE DE PRUEBA PARA WHATSAPP\n";
echo "==========================================\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Crear paquete nuevo
    $codigo = 'TEST-' . date('His');
    
    $sql = "INSERT INTO paquetes (
        codigo_seguimiento, codigo_savar, destinatario_nombre, destinatario_telefono,
        destinatario_email, direccion_completa, ciudad, provincia, distrito, peso, 
        valor_declarado, costo_envio, prioridad, notas, estado
    ) VALUES (?, '', ?, ?, '', ?, 'Lima', 'Lima', 'Miraflores', 1.0, 100.0, 15.0, 'normal', 'Prueba WhatsApp', 'pendiente')";
    
    $stmt = $db->prepare($sql);
    $nombre = 'Test Usuario';
    $telefono = '903417579';
    $direccion = 'Av. Test 123';
    
    $stmt->bind_param("ssss", 
        $codigo,
        $nombre,
        $telefono,
        $direccion
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Error creando paquete: " . $stmt->error);
    }
    
    $paquete_id = $db->insert_id;
    echo "✅ Paquete creado - ID: $paquete_id, Código: $codigo\n";
    
    // Asignar repartidor (ID 3 = Carlos Rodriguez según diagnóstico)
    $update_sql = "UPDATE paquetes SET repartidor_id = 3, estado = 'en_ruta', fecha_asignacion = NOW() WHERE id = ?";
    $update_stmt = $db->prepare($update_sql);
    $update_stmt->bind_param("i", $paquete_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception("Error asignando repartidor: " . $update_stmt->error);
    }
    
    echo "✅ Repartidor asignado (Carlos Rodriguez)\n";
    
    // Enviar WhatsApp
    echo "📱 Enviando WhatsApp...\n";
    $whatsapp = new WhatsAppNotificaciones();
    $result = $whatsapp->notificarAsignacion($paquete_id);
    
    if ($result) {
        echo "✅ ¡WhatsApp enviado exitosamente!\n";
        echo "📲 Revisa tu teléfono 903417579\n";
    } else {
        echo "❌ Error enviando WhatsApp\n";
    }
    
    // Mostrar detalles
    echo "\n📋 DETALLES DEL PAQUETE:\n";
    $details = $db->query("
        SELECT p.*, u.nombre as repartidor_nombre, u.telefono as repartidor_telefono
        FROM paquetes p 
        LEFT JOIN usuarios u ON p.repartidor_id = u.id 
        WHERE p.id = $paquete_id
    ");
    
    if ($details && $row = $details->fetch_assoc()) {
        echo "- Código: {$row['codigo_seguimiento']}\n";
        echo "- Cliente: {$row['destinatario_nombre']}\n";
        echo "- Teléfono: {$row['destinatario_telefono']}\n";
        echo "- Dirección: {$row['direccion_completa']}\n";
        echo "- Repartidor: {$row['repartidor_nombre']}\n";
        echo "- Tel. Repartidor: {$row['repartidor_telefono']}\n";
        echo "- Estado: {$row['estado']}\n";
    }
    
    echo "\n⏰ " . date('d/m/Y H:i:s') . "\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>