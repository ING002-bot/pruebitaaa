<?php
/**
 * Test REAL FlexBis con documentación oficial
 * Número de prueba: 903417579
 */

require_once 'config/config.php';
require_once 'config/flexbis_client.php';

echo "=== TEST REAL FLEXBIS CON DOCUMENTACIÓN OFICIAL ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n";
echo "Número de prueba: 903417579\n\n";

try {
    // Crear instancia FlexBis
    echo "🔧 Creando cliente FlexBis...\n";
    $flexbis = new FlexBisClient();
    
    // Verificar configuración
    echo "🔍 Verificando configuración...\n";
    $config = $flexbis->getConfig();
    
    echo "- SID: " . ($config['has_sid'] ? '✅' : '❌') . "\n";
    echo "- Key: " . ($config['has_key'] ? '✅' : '❌') . "\n";
    echo "- URL: " . $config['api_url'] . "\n";
    echo "- Configurado: " . ($config['is_configured'] ? '✅' : '❌') . "\n\n";
    
    if (!$config['is_configured']) {
        echo "❌ FlexBis no está configurado correctamente\n";
        exit(1);
    }
    
    // Test de conexión
    echo "🌐 Probando conexión con FlexBis...\n";
    $connection = $flexbis->testConnection();
    
    if ($connection['success']) {
        echo "✅ Conexión exitosa!\n";
        echo "Endpoint: " . $connection['endpoint'] . "\n";
        if (isset($connection['message'])) {
            echo "Mensaje: " . $connection['message'] . "\n";
        }
    } else {
        echo "❌ Error de conexión: " . $connection['error'] . "\n";
        if (isset($connection['details'])) {
            echo "Detalles:\n";
            print_r($connection['details']);
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n\n";
    
    // ENVÍO REAL - ⚠️ ESTO CONSUME CRÉDITOS ⚠️
    $test_phone = '903417579';
    $test_message = '🚀 TEST HERMES EXPRESS - FlexBis funcionando! ' . date('H:i:s');
    
    echo "📱 ENVIANDO MENSAJE REAL...\n";
    echo "A: $test_phone\n";
    echo "Mensaje: $test_message\n\n";
    
    $result = $flexbis->sendMessage($test_phone, $test_message);
    
    if ($result['success']) {
        echo "🎉 ¡MENSAJE ENVIADO EXITOSAMENTE!\n";
        echo "ID: " . ($result['message_id'] ?? 'N/A') . "\n";
        echo "Estado: " . ($result['status'] ?? 'sent') . "\n";
        
        if (isset($result['data'])) {
            echo "Datos completos:\n";
            print_r($result['data']);
        }
        
        echo "\n✅ ¡REVISA TU WHATSAPP ($test_phone)!\n";
        
    } else {
        echo "❌ ERROR AL ENVIAR MENSAJE:\n";
        echo "Error: " . $result['error'] . "\n";
        
        if (isset($result['http_code'])) {
            echo "Código HTTP: " . $result['http_code'] . "\n";
        }
        
        if (isset($result['data'])) {
            echo "Respuesta API:\n";
            print_r($result['data']);
        }
    }
    
} catch (Exception $e) {
    echo "💥 EXCEPCIÓN: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "📋 RESUMEN:\n";
echo "- API Type: " . (defined('WHATSAPP_API_TYPE') ? WHATSAPP_API_TYPE : 'no definido') . "\n";
echo "- FlexBis SID: " . (defined('FLEXBIS_API_SID') ? 'Configurado' : 'No configurado') . "\n";
echo "- FlexBis Key: " . (defined('FLEXBIS_API_KEY') ? 'Configurado' : 'No configurado') . "\n";
echo "- Número de prueba: 903417579\n";
echo "- Fecha test: " . date('Y-m-d H:i:s') . "\n";
?>