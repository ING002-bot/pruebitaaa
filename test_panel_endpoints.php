<?php
/**
 * Prueba usando el endpoint del panel FlexSender directamente
 */

require_once 'config/config.php';

echo "🔥 PROBANDO ENDPOINT PANEL FLEXSENDER\n";
echo "====================================\n";

$endpoints_panel = [
    'Panel API 1' => "https://panel.flexbis.com/api/" . FLEXBIS_API_SID . "/send",
    'Panel API 2' => "https://panel.flexbis.com/api/send",
    'Panel API 3' => "https://flexbis.com/api/" . FLEXBIS_API_SID . "/send",
    'Panel API 4' => "https://api.flexbis.com/" . FLEXBIS_API_SID . "/send"
];

$mensaje = "🎯 Mensaje desde HERMES EXPRESS via API Panel\n⏰ " . date('d/m/Y H:i:s');
$numero = "51903417579";

foreach ($endpoints_panel as $name => $url) {
    echo "🔍 Probando: $name\n";
    echo "URL: $url\n";
    
    // Probar diferentes formatos de datos
    $datos_formatos = [
        'Formato 1' => [
            'token' => FLEXBIS_API_KEY,
            'to' => $numero,
            'body' => $mensaje,
            'instance' => FLEXBIS_API_SID
        ],
        'Formato 2' => [
            'api_key' => FLEXBIS_API_KEY,
            'number' => $numero,
            'message' => $mensaje
        ],
        'Formato 3' => [
            'token' => FLEXBIS_API_KEY,
            'phone' => $numero,
            'text' => $mensaje
        ]
    ];
    
    foreach ($datos_formatos as $formato_name => $datos) {
        echo "  📋 $formato_name: ";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($datos));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            echo "❌ Error: $error\n";
        } else {
            echo "HTTP $http_code - ";
            $json = json_decode($response, true);
            
            if ($json && isset($json['success'])) {
                echo "✅ SUCCESS!\n";
                echo "    📨 Respuesta: " . json_encode($json) . "\n";
            } elseif ($json && isset($json['sent'])) {
                echo "✅ SENT!\n";
                echo "    📨 Respuesta: " . json_encode($json) . "\n";
            } elseif (strlen($response) < 10) {
                echo "📭 Respuesta vacía\n";
            } else {
                echo "📨 " . substr($response, 0, 100) . "...\n";
            }
        }
    }
    echo "---\n";
}

echo "\n🔍 RESUMEN:\n";
echo "- Panel FlexSender: Funciona (mensaje llegó)\n";
echo "- API UltraMsg: No funciona (non-payment)\n";
echo "- Necesitamos encontrar el endpoint correcto del panel\n";

echo "\n⏰ " . date('d/m/Y H:i:s') . "\n";
?>