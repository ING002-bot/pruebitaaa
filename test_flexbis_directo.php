<?php
/**
 * Test específico para credenciales FlexBis proporcionadas
 * Usando diferentes configuraciones comunes de APIs WhatsApp
 */

echo "=== TEST FLEXBIS CON CREDENCIALES REALES ===\n";
echo "SID: serhsznr\n";
echo "Token: H4vP1g837ZxKR0VMz3yD\n\n";

// Configuraciones a probar
$configuraciones = [
    'FlexBis Estándar' => [
        'url' => 'https://api.flexbis.com/v1/',
        'headers' => ['Authorization: Bearer H4vP1g837ZxKR0VMz3yD'],
        'endpoints' => ['/messages', '/send', '/status']
    ],
    'FlexBis Alternativa 1' => [
        'url' => 'https://flexbis.com/api/v1/',
        'headers' => ['Authorization: Bearer H4vP1g837ZxKR0VMz3yD'],
        'endpoints' => ['/messages', '/send', '/whatsapp']
    ],
    'FlexBis con SID' => [
        'url' => 'https://api.flexbis.com/',
        'headers' => ['X-SID: serhsznr', 'X-Token: H4vP1g837ZxKR0VMz3yD'],
        'endpoints' => ['/messages', '/send', '/api/send']
    ],
    'FlexBis WhatsApp directo' => [
        'url' => 'https://whatsapp.flexbis.com/',
        'headers' => ['Authorization: Bearer H4vP1g837ZxKR0VMz3yD'],
        'endpoints' => ['/send', '/messages', '/api/send']
    ],
    'Formato Twilio-like' => [
        'url' => 'https://api.flexbis.com/',
        'headers' => ['Authorization: Basic ' . base64_encode('serhsznr:H4vP1g837ZxKR0VMz3yD')],
        'endpoints' => ['/Messages', '/Accounts/serhsznr/Messages']
    ]
];

foreach ($configuraciones as $nombre => $config) {
    echo "🔍 Probando: $nombre\n";
    echo "   URL base: " . $config['url'] . "\n";
    
    foreach ($config['endpoints'] as $endpoint) {
        $url_completa = rtrim($config['url'], '/') . $endpoint;
        echo "   Endpoint: $url_completa\n";
        
        // Test GET
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url_completa,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => array_merge([
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: HermesExpress-FlexBis/1.0'
            ], $config['headers']),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            echo "     ❌ cURL Error: $curl_error\n";
        } else {
            echo "     GET HTTP $http_code";
            
            if (strpos($content_type, 'application/json') !== false) {
                echo " 📄 JSON";
                
                $data = json_decode($response, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    echo " ✅ Válido";
                    
                    // Revisar indicadores de API válida
                    if (isset($data['error'])) {
                        echo " - Error: " . $data['error'];
                    } elseif (isset($data['message'])) {
                        echo " - Mensaje: " . $data['message'];
                    } elseif (isset($data['status'])) {
                        echo " - Status: " . $data['status'];
                    } elseif (count($data) > 0) {
                        echo " - Respuesta válida";
                    }
                }
            } elseif ($http_code === 200) {
                echo " 🌐 HTML/Text";
            } elseif ($http_code === 401) {
                echo " 🔐 No autorizado (endpoint correcto?)";
            } elseif ($http_code === 404) {
                echo " 🚫 No encontrado";
            } elseif ($http_code >= 500) {
                echo " 🔥 Error servidor";
            }
            
            echo "\n";
            
            // Mostrar primeras líneas de respuesta útil
            if ($response && $http_code !== 404 && strlen($response) < 500) {
                $response_clean = trim(strip_tags($response));
                if ($response_clean && $response_clean !== 'OK') {
                    echo "     Respuesta: " . substr($response_clean, 0, 100) . (strlen($response_clean) > 100 ? '...' : '') . "\n";
                }
            }
        }
        
        // Test POST para endpoints de envío
        if (strpos($endpoint, 'send') !== false || strpos($endpoint, 'messages') !== false || strpos($endpoint, 'Messages') !== false) {
            echo "     POST Test: ";
            
            $test_data = [
                'to' => '+51987654321',
                'message' => 'Test FlexBis',
                'from' => '+51987654321'
            ];
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url_completa,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($test_data),
                CURLOPT_HTTPHEADER => array_merge([
                    'Content-Type: application/json',
                    'Accept: application/json'
                ], $config['headers']),
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            
            $post_response = curl_exec($ch);
            $post_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $post_curl_error = curl_error($ch);
            curl_close($ch);
            
            if ($post_curl_error) {
                echo "❌ cURL Error\n";
            } else {
                echo "HTTP $post_http_code";
                
                if ($post_http_code === 200 || $post_http_code === 201) {
                    echo " ✅ Éxito!";
                    $post_data = json_decode($post_response, true);
                    if ($post_data && isset($post_data['id'])) {
                        echo " ID: " . $post_data['id'];
                    }
                } elseif ($post_http_code === 401) {
                    echo " 🔐 No autorizado";
                } elseif ($post_http_code === 422 || $post_http_code === 400) {
                    echo " 📝 Error validación";
                    $error_data = json_decode($post_response, true);
                    if ($error_data && isset($error_data['error'])) {
                        echo " - " . $error_data['error'];
                    }
                }
                echo "\n";
            }
        }
    }
    echo "\n";
}

echo "📞 CONTACTO DIRECTO CON FLEXBIS:\n";
echo "   WhatsApp: +51926420256\n";
echo "   Email: info@flexbis.com\n";
echo "   Web: https://flexbis.com\n\n";

echo "💡 RECOMENDACIÓN:\n";
echo "   Contacta directamente con FlexBis para obtener:\n";
echo "   1. URL exacta de la API\n";
echo "   2. Documentación de endpoints\n";
echo "   3. Formato de autenticación\n";
echo "   4. Ejemplos de uso\n\n";

echo "=== FIN DEL TEST ===\n";
?>