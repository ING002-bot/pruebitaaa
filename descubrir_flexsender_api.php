<?php
/**
 * Descubrir URL correcta de FlexSender API
 */

echo "=== DESCUBRIENDO URL CORRECTA FLEXSENDER ===\n\n";

// Posibles dominios basados en FlexSender
$dominios_base = [
    'https://api.flexsender.com',
    'https://flexsender.com/api', 
    'https://app.flexsender.com/api',
    'https://panel.flexsender.com/api',
    'https://gateway.flexsender.com',
    'https://api.flexbis.com', // Por si sigue siendo FlexBis
    'https://whatsapp.flexsender.com',
    'https://send.flexsender.com'
];

$sid = 'serhsznr';
$token = 'H4vP1g837ZxKR0VMz3yD';

foreach ($dominios_base as $domain) {
    echo "🔍 Probando dominio: $domain\n";
    
    // Test básico de conectividad
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $domain,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_NOBODY => true, // Solo HEAD
        CURLOPT_FOLLOWLOCATION => true
    ]);
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        echo "   ❌ No resuelve: $curl_error\n";
    } else {
        echo "   ✅ Responde: HTTP $http_code\n";
        
        if ($http_code === 200) {
            // Probar endpoints comunes
            $endpoints = [
                "/$sid/messages/chat",
                "/$sid/send-message", 
                "/instances/$sid/send",
                "/api/$sid/send",
                "/send"
            ];
            
            foreach ($endpoints as $endpoint) {
                $test_url = $domain . $endpoint;
                
                $ch2 = curl_init();
                curl_setopt_array($ch2, [
                    CURLOPT_URL => $test_url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 5,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => http_build_query([
                        'token' => $token,
                        'to' => '123456789',
                        'body' => 'test'
                    ]),
                    CURLOPT_SSL_VERIFYPEER => false
                ]);
                
                curl_exec($ch2);
                $endpoint_code = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                $endpoint_error = curl_error($ch2);
                curl_close($ch2);
                
                if (!$endpoint_error && $endpoint_code !== 404) {
                    echo "     🎯 Endpoint posible: $endpoint (HTTP $endpoint_code)\n";
                }
            }
        }
    }
    
    echo "\n";
}

echo "🔍 TAMBIÉN PROBANDO CON DIFERENTES MÉTODOS:\n\n";

// Basándome en la documentación de UltraMsg (que parece ser la base)
$ultramsg_style = [
    'URL' => 'https://api.ultramsg.com/' . $sid . '/messages/chat',
    'headers' => ['Content-Type: application/x-www-form-urlencoded'],
    'data' => [
        'token' => $token,
        'to' => '51903417579',
        'body' => 'Test desde HERMES EXPRESS'
    ]
];

echo "🔍 Probando estilo UltraMsg:\n";
echo "URL: " . $ultramsg_style['URL'] . "\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $ultramsg_style['URL'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($ultramsg_style['data']),
    CURLOPT_HTTPHEADER => $ultramsg_style['headers'],
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    echo "❌ cURL Error: $curl_error\n";
} else {
    echo "✅ HTTP $http_code ($content_type)\n";
    
    if ($response) {
        $data = json_decode($response, true);
        if ($data) {
            echo "📄 Respuesta JSON:\n";
            print_r($data);
            
            if (isset($data['sent']) && $data['sent'] === 'true') {
                echo "🎉 ¡MENSAJE ENVIADO CON ÉXITO!\n";
                echo "📱 ¡Revisa tu WhatsApp (903417579)!\n";
            }
        } else {
            echo "📄 Respuesta texto: " . substr($response, 0, 200) . "\n";
        }
    }
}

echo "\n💡 RECOMENDACIÓN:\n";
echo "Si UltraMsg funciona, FlexSender probablemente use la misma API\n";
echo "Revisa tu panel FlexSender para ver la URL exacta de la API\n";
?>