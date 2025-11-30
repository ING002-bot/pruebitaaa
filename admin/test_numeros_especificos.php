<?php
/**
 * Probar números específicos con FlexBis
 */

require_once '../config/config.php';
require_once '../config/flexbis_client.php';

echo "📱 PROBANDO NÚMEROS ESPECÍFICOS CON FLEXBIS\n";
echo "===========================================\n";

$numeros_prueba = [
    '903417579' => 'Número que funciona',
    '912112380' => 'Número que no funciona',
    '+51903417579' => 'Con código país +51',
    '+51912112380' => 'Segundo número con +51'
];

try {
    $flexbis = new FlexBisClient();
    
    foreach ($numeros_prueba as $numero => $descripcion) {
        echo "\n📞 Probando: $numero ($descripcion)\n";
        
        // Probar envío directo
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://whatsapp-service.flexbis.com/api/v1/message/text");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'numero_destinatario' => $numero,
            'tipo_destinatario' => 'contacto',
            'tipo_mensaje' => 'texto',
            'texto' => "🧪 Prueba para $numero - " . date('H:i:s')
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
        echo "  Respuesta: " . substr($response, 0, 200) . "\n";
        
        $json = json_decode($response, true);
        if ($json) {
            if (isset($json['type']) && $json['type'] === 'success') {
                echo "  ✅ ÉXITO: " . $json['message'] . "\n";
            } elseif (isset($json['message'])) {
                echo "  ❌ ERROR: " . $json['message'] . "\n";
            } elseif (isset($json['error'])) {
                echo "  ❌ ERROR: " . $json['error'] . "\n";
            }
        }
    }
    
    echo "\n🔍 ANÁLISIS:\n";
    echo "- Si 903417579 funciona y 912112380 no, puede ser:\n";
    echo "  1. Número no registrado en FlexBis\n";
    echo "  2. Número bloqueado/inválido\n";
    echo "  3. Formato incorrecto\n";
    echo "  4. Restricciones de la cuenta FlexBis\n\n";
    
    echo "💡 RECOMENDACIÓN:\n";
    echo "- Contactar a FlexBis para agregar 912112380 como número permitido\n";
    echo "- Verificar que el número esté activo en WhatsApp\n";
    echo "- Confirmar formato correcto del número\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n⏰ " . date('d/m/Y H:i:s') . "\n";
?>