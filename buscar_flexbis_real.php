<?php
/**
 * Búsqueda exhaustiva del endpoint correcto de FlexBis
 * Probando ENVÍO REAL con diferentes configuraciones
 */

echo "=== BÚSQUEDA ENDPOINT FLEXBIS REAL ===\n";
echo "SID: serhsznr\n";
echo "Token: H4vP1g837ZxKR0VMz3yD\n";
echo "⚠️  PROBANDO ENVÍOS REALES - PUEDE CONSUMIR CRÉDITOS\n\n";

$sid = 'serhsznr';
$token = 'H4vP1g837ZxKR0VMz3yD';
$test_phone = '+51987654321'; // Cambiar por tu número real
$test_message = 'TEST FlexBis API - ' . date('H:i:s');

// Configuraciones posibles de FlexBis API
$configuraciones = [
    'FlexBis v1 Bearer' => [
        'url' => 'https://api.flexbis.com/v1/messages',
        'method' => 'POST',
        'headers' => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ],
        'data' => [
            'to' => $test_phone,
            'message' => $test_message,
            'from' => '+51987654321'
        ]
    ],
    'FlexBis v1 Send' => [
        'url' => 'https://api.flexbis.com/v1/send',
        'method' => 'POST',
        'headers' => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ],
        'data' => [
            'to' => $test_phone,
            'text' => $test_message,
            'sid' => $sid
        ]
    ],
    'FlexBis WhatsApp' => [
        'url' => 'https://api.flexbis.com/whatsapp/send',
        'method' => 'POST',
        'headers' => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ],
        'data' => [
            'number' => str_replace('+', '', $test_phone),
            'message' => $test_message,
            'instance_id' => $sid
        ]
    ],
    'FlexBis Basic Auth' => [
        'url' => 'https://api.flexbis.com/send',
        'method' => 'POST',
        'headers' => [
            'Authorization: Basic ' . base64_encode($sid . ':' . $token),
            'Content-Type: application/json'
        ],
        'data' => [
            'to' => $test_phone,
            'message' => $test_message
        ]
    ],
    'FlexBis con SID Header' => [
        'url' => 'https://api.flexbis.com/api/send',
        'method' => 'POST',
        'headers' => [
            'X-Instance-ID: ' . $sid,
            'X-API-Token: ' . $token,
            'Content-Type: application/json'
        ],
        'data' => [
            'phone' => $test_phone,
            'body' => $test_message
        ]
    ],
    'FlexBis Twilio Style' => [
        'url' => 'https://api.flexbis.com/Accounts/' . $sid . '/Messages',
        'method' => 'POST',
        'headers' => [
            'Authorization: Basic ' . base64_encode($sid . ':' . $token),
            'Content-Type: application/x-www-form-urlencoded'
        ],
        'data' => [
            'To' => $test_phone,
            'Body' => $test_message,
            'From' => 'whatsapp:+51987654321'
        ]
    ],
    'FlexBis Simple' => [
        'url' => 'https://flexbis.com/api/send-message',
        'method' => 'POST',
        'headers' => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ],
        'data' => [
            'instance' => $sid,
            'phone' => $test_phone,
            'message' => $test_message
        ]
    ]
];

foreach ($configuraciones as $nombre => $config) {
    echo "🔍 Probando: $nombre\n";
    echo "   URL: " . $config['url'] . "\n";
    
    $ch = curl_init();
    
    $curl_options = [
        CURLOPT_URL => $config['url'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => $config['headers'],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ];
    
    if ($config['method'] === 'POST') {
        $curl_options[CURLOPT_POST] = true;
        
        // Determinar formato de datos
        $content_type = implode(' ', $config['headers']);
        if (strpos($content_type, 'application/json') !== false) {
            $curl_options[CURLOPT_POSTFIELDS] = json_encode($config['data']);
        } else {
            $curl_options[CURLOPT_POSTFIELDS] = http_build_query($config['data']);
        }
    }
    
    curl_setopt_array($ch, $curl_options);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        echo "   ❌ cURL Error: $curl_error\n\n";
        continue;
    }
    
    echo "   HTTP $http_code ($content_type)\n";
    
    // Analizar respuesta
    if ($http_code >= 200 && $http_code < 300) {
        echo "   ✅ ÉXITO! Respuesta:\n";
        
        if (strpos($content_type, 'application/json') !== false) {
            $data = json_decode($response, true);
            if ($data) {
                print_r($data);
                
                // Buscar indicadores de éxito
                if (isset($data['message_id']) || isset($data['id']) || isset($data['success']) || isset($data['sent'])) {
                    echo "   🎉 ¡MENSAJE ENVIADO EXITOSAMENTE!\n";
                    echo "   📱 Revisa tu WhatsApp ($test_phone)\n";
                    
                    if (isset($data['message_id'])) echo "   ID: " . $data['message_id'] . "\n";
                    if (isset($data['id'])) echo "   ID: " . $data['id'] . "\n";
                }
            }
        } else {
            echo "   Respuesta: " . substr(strip_tags($response), 0, 200) . "\n";
        }
    } elseif ($http_code === 401) {
        echo "   🔐 No autorizado - credenciales incorrectas o formato auth incorrecto\n";
    } elseif ($http_code === 422 || $http_code === 400) {
        echo "   📝 Error de validación:\n";
        if (strpos($content_type, 'application/json') !== false) {
            $data = json_decode($response, true);
            if ($data) {
                print_r($data);
            }
        } else {
            echo "   " . substr($response, 0, 200) . "\n";
        }
    } else {
        echo "   ❌ Error HTTP $http_code\n";
        echo "   Respuesta: " . substr($response, 0, 200) . "\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

echo "💡 INSTRUCCIONES:\n";
echo "1. Revisa cuál configuración dio ✅ ÉXITO\n";
echo "2. Verifica tu WhatsApp ($test_phone) si llegó mensaje\n";
echo "3. Esa será la configuración correcta para FlexBis\n\n";

echo "📞 Si ninguna funciona:\n";
echo "- Contacta FlexBis: +51926420256\n";
echo "- Pide documentación específica de tu cuenta\n";
?>