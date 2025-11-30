<?php
/**
 * Test con patrones comunes de APIs WhatsApp
 * Basado en Evolution API, Baileys, y otros
 */

echo "=== TEST PATRONES COMUNES WHATSAPP APIS ===\n";
echo "Probando estructuras similares a Evolution API, Baileys, etc.\n\n";

$sid = 'serhsznr';
$token = 'H4vP1g837ZxKR0VMz3yD';
$test_phone = '51987654321'; // Sin el +
$test_message = 'TEST FlexBis Real - ' . date('H:i:s');

// Patrones basados en APIs WhatsApp populares
$patrones = [
    'Evolution API Style' => [
        'url' => 'https://api.flexbis.com/message/sendText/' . $sid,
        'headers' => [
            'apikey: ' . $token,
            'Content-Type: application/json'
        ],
        'data' => [
            'number' => $test_phone,
            'textMessage' => ['text' => $test_message]
        ]
    ],
    'WhatsApp Business API Style' => [
        'url' => 'https://graph.flexbis.com/v1/messages',
        'headers' => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ],
        'data' => [
            'messaging_product' => 'whatsapp',
            'to' => $test_phone,
            'type' => 'text',
            'text' => ['body' => $test_message]
        ]
    ],
    'Baileys Style' => [
        'url' => 'https://api.flexbis.com/instance/' . $sid . '/sendMessage',
        'headers' => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ],
        'data' => [
            'chatId' => $test_phone . '@c.us',
            'message' => $test_message
        ]
    ],
    'Generic API Style 1' => [
        'url' => 'https://api.flexbis.com/send',
        'headers' => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ],
        'data' => [
            'instance_key' => $sid,
            'number' => $test_phone,
            'type' => 'text',
            'message' => $test_message
        ]
    ],
    'Generic API Style 2' => [
        'url' => 'https://api.flexbis.com/v1/send-message',
        'headers' => [
            'X-API-Key: ' . $token,
            'Content-Type: application/json'
        ],
        'data' => [
            'instance' => $sid,
            'phone' => '+' . $test_phone,
            'body' => $test_message
        ]
    ],
    'Twilio WhatsApp Style' => [
        'url' => 'https://api.flexbis.com/2010-04-01/Accounts/' . $sid . '/Messages.json',
        'headers' => [
            'Authorization: Basic ' . base64_encode($sid . ':' . $token),
            'Content-Type: application/x-www-form-urlencoded'
        ],
        'data' => [
            'From' => 'whatsapp:+51987654321',
            'To' => 'whatsapp:+' . $test_phone,
            'Body' => $test_message
        ],
        'format' => 'form'
    ],
    'Simple POST Style' => [
        'url' => 'https://api.flexbis.com/sendMessage',
        'headers' => [
            'token: ' . $token,
            'Content-Type: application/json'
        ],
        'data' => [
            'phone' => $test_phone,
            'message' => $test_message,
            'instance_id' => $sid
        ]
    ]
];

foreach ($patrones as $nombre => $config) {
    echo "🔍 Probando: $nombre\n";
    echo "   URL: " . $config['url'] . "\n";
    
    $ch = curl_init();
    
    $curl_options = [
        CURLOPT_URL => $config['url'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $config['headers'],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_VERBOSE => false
    ];
    
    // Formatear datos según el tipo
    if (isset($config['format']) && $config['format'] === 'form') {
        $curl_options[CURLOPT_POSTFIELDS] = http_build_query($config['data']);
    } else {
        $curl_options[CURLOPT_POSTFIELDS] = json_encode($config['data']);
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
    
    echo "   HTTP $http_code";
    
    if (strpos($content_type, 'application/json') !== false) {
        echo " 📄 JSON";
        $data = json_decode($response, true);
        
        if ($data && json_last_error() === JSON_ERROR_NONE) {
            echo " ✅\n";
            
            // Analizar la respuesta JSON
            echo "   📋 Respuesta:\n";
            print_r($data);
            
            // Buscar indicadores de éxito
            $success_indicators = [
                'success' => true,
                'status' => ['success', 'sent', 'queued', 'delivered', 'ok'],
                'message_id' => 'any',
                'id' => 'any',
                'messageId' => 'any',
                'sent' => true,
                'ok' => true
            ];
            
            $is_success = false;
            foreach ($success_indicators as $key => $expected) {
                if (isset($data[$key])) {
                    if ($expected === 'any' || 
                        $expected === true && $data[$key] ||
                        (is_array($expected) && in_array($data[$key], $expected))) {
                        
                        echo "   🎉 ¡POSIBLE ÉXITO! $key = " . json_encode($data[$key]) . "\n";
                        echo "   📱 ¡REVISA TU WHATSAPP ($test_phone)!\n";
                        $is_success = true;
                        break;
                    }
                }
            }
            
            if (!$is_success && isset($data['error'])) {
                echo "   ⚠️  Error reportado: " . json_encode($data['error']) . "\n";
            } elseif (!$is_success) {
                echo "   ℹ️  Respuesta sin indicadores claros de éxito\n";
            }
            
        } else {
            echo " ❌ JSON inválido\n";
        }
        
    } elseif ($http_code >= 200 && $http_code < 300) {
        echo " 🌐 Texto/HTML\n";
        $clean_response = trim(strip_tags($response));
        
        if ($clean_response) {
            echo "   Respuesta: " . substr($clean_response, 0, 100) . "\n";
            
            // Buscar indicadores de éxito en texto
            $success_words = ['success', 'sent', 'delivered', 'ok', 'message sent'];
            foreach ($success_words as $word) {
                if (stripos($clean_response, $word) !== false) {
                    echo "   🎉 ¡POSIBLE ÉXITO! Contiene '$word'\n";
                    echo "   📱 ¡REVISA TU WHATSAPP ($test_phone)!\n";
                    break;
                }
            }
        }
        
    } elseif ($http_code === 401) {
        echo " 🔐 No autorizado - credenciales incorrectas?\n";
    } elseif ($http_code === 422 || $http_code === 400) {
        echo " 📝 Error de validación\n";
        if ($response) {
            $error_data = json_decode($response, true);
            if ($error_data && isset($error_data['message'])) {
                echo "   Mensaje: " . $error_data['message'] . "\n";
            } else {
                echo "   Respuesta: " . substr($response, 0, 200) . "\n";
            }
        }
    } else {
        echo " ❌ Error HTTP\n";
    }
    
    echo "\n" . str_repeat("-", 60) . "\n\n";
}

echo "📱 INSTRUCCIONES IMPORTANTES:\n";
echo "1. Revisa tu WhatsApp ($test_phone) AHORA\n";
echo "2. Si recibiste algún mensaje de prueba, ¡FUNCIONÓ!\n";
echo "3. Anota cuál patrón funcionó para configurarlo\n";
echo "4. Si no llegó nada, contactaremos FlexBis directamente\n\n";

echo "📞 CONTACTO DIRECTO FLEXBIS:\n";
echo "WhatsApp: +51926420256\n";
echo "Email: info@flexbis.com\n";
?>