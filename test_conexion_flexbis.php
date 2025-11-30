<?php
/**
 * Test rápido de FlexBis con credenciales reales
 * Ejecutar desde línea de comandos: php test_conexion_flexbis.php
 */

require_once 'config/config.php';
require_once 'config/flexbis_client.php';

echo "=== TEST CONEXIÓN FLEXBIS ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

// Mostrar configuración (sin credenciales sensibles)
echo "Configuración:\n";
echo "- API Type: " . WHATSAPP_API_TYPE . "\n";
echo "- API URL: " . FLEXBIS_API_URL . "\n";
echo "- SID: " . (FLEXBIS_API_SID ? substr(FLEXBIS_API_SID, 0, 4) . '****' : 'NO CONFIGURADO') . "\n";
echo "- Key: " . (FLEXBIS_API_KEY ? '****' . substr(FLEXBIS_API_KEY, -4) : 'NO CONFIGURADO') . "\n";
echo "- From: " . FLEXBIS_WHATSAPP_FROM . "\n\n";

try {
    // Crear instancia de FlexBis
    echo "Creando cliente FlexBis...\n";
    $flexbis = new FlexBisClient();
    
    // Verificar configuración
    echo "Verificando configuración...\n";
    $config = $flexbis->getConfig();
    
    if (!$config['is_configured']) {
        echo "❌ ERROR: FlexBis no está configurado correctamente\n";
        print_r($config);
        exit(1);
    }
    
    echo "✅ Configuración OK\n\n";
    
    // Test de conexión
    echo "Probando conexión con API FlexBis...\n";
    $test_result = $flexbis->testConnection();
    
    if ($test_result['success']) {
        echo "✅ CONEXIÓN EXITOSA!\n";
        echo "Mensaje: " . $test_result['message'] . "\n";
        
        if (isset($test_result['account_info'])) {
            echo "\nInformación de la cuenta:\n";
            print_r($test_result['account_info']);
        }
        
        // Intentar obtener balance
        echo "\nConsultando balance...\n";
        $balance_result = $flexbis->getBalance();
        
        if ($balance_result['success']) {
            echo "✅ Balance obtenido: " . $balance_result['balance'] . " " . ($balance_result['currency'] ?? '') . "\n";
        } else {
            echo "⚠️  No se pudo obtener balance: " . $balance_result['error'] . "\n";
        }
        
    } else {
        echo "❌ ERROR DE CONEXIÓN:\n";
        echo "Error: " . $test_result['error'] . "\n";
        
        if (isset($test_result['http_code'])) {
            echo "Código HTTP: " . $test_result['http_code'] . "\n";
        }
        
        if (isset($test_result['response'])) {
            echo "Respuesta API:\n";
            print_r($test_result['response']);
        }
    }
    
} catch (Exception $e) {
    echo "❌ EXCEPCIÓN: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}

echo "\n=== FIN DEL TEST ===\n";
?>