<?php
/**
 * Búsqueda de dominios alternativos FlexBis
 * Probando diferentes subdominios y rutas
 */

echo "=== BÚSQUEDA DOMINIOS ALTERNATIVOS FLEXBIS ===\n";
echo "Probando diferentes subdominios y estructuras...\n\n";

$sid = 'serhsznr';
$token = 'H4vP1g837ZxKR0VMz3yD';
$test_phone = '+51987654321';
$test_message = 'TEST FlexBis - ' . date('H:i:s');

// Posibles dominios y rutas de FlexBis
$dominios_rutas = [
    // Subdominios comunes para APIs WhatsApp
    'https://whatsapp.flexbis.com/api/send',
    'https://wa.flexbis.com/api/send',
    'https://api-wa.flexbis.com/send',
    'https://gateway.flexbis.com/whatsapp/send',
    
    // Rutas con instancia
    'https://api.flexbis.com/' . $sid . '/send',
    'https://api.flexbis.com/instance/' . $sid . '/send',
    'https://api.flexbis.com/instances/' . $sid . '/messages',
    
    // Estructuras comunes de APIs WhatsApp Business
    'https://graph.flexbis.com/v15.0/' . $sid . '/messages',
    'https://api.flexbis.com/v2/instances/' . $sid . '/messages',
    'https://api.flexbis.com/client/sendMessage/' . $sid,
    
    // Otras posibilidades
    'https://flexbis.com/webhook/send',
    'https://app.flexbis.com/api/send',
    'https://panel.flexbis.com/api/whatsapp/send'
];

foreach ($dominios_rutas as $url) {
    echo "🔍 Probando: $url\n";
    
    // Configuraciones de datos comunes
    $configs = [
        'JSON Bearer' => [
            'headers' => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ],
            'data' => json_encode([
                'to' => $test_phone,
                'message' => $test_message,
                'instance_id' => $sid
            ])
        ],
        'JSON con SID' => [
            'headers' => [
                'X-Instance-ID: ' . $sid,
                'X-API-Token: ' . $token,
                'Content-Type: application/json'
            ],
            'data' => json_encode([
                'phone' => $test_phone,
                'body' => $test_message
            ])
        ]
    ];
    
    foreach ($configs as $config_name => $config) {
        echo "   Config: $config_name\n";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $config['data'],
            CURLOPT_HTTPHEADER => $config['headers'],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => false
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            echo "     ❌ cURL: $curl_error\n";
            continue;
        }
        
        echo "     HTTP $http_code";
        
        if (strpos($content_type, 'application/json') !== false) {
            echo " 📄 JSON";
            $data = json_decode($response, true);
            if ($data && json_last_error() === JSON_ERROR_NONE) {
                echo " ✅";
                
                // Buscar indicadores de éxito en la respuesta JSON
                $success_indicators = ['success', 'message_id', 'id', 'sent', 'queued', 'delivered'];
                $found_success = false;
                
                foreach ($success_indicators as $indicator) {
                    if (isset($data[$indicator])) {
                        $found_success = true;
                        echo "\n     🎉 POSIBLE ÉXITO: $indicator = " . json_encode($data[$indicator]);
                        break;
                    }
                }
                
                if (!$found_success && isset($data['error'])) {
                    echo "\n     ⚠️  Error: " . $data['error'];
                } elseif (!$found_success) {
                    echo "\n     📋 Respuesta: " . json_encode($data);
                }
            }
        } elseif ($http_code === 200) {
            echo " 🌐 HTML/Text";
            $clean_response = trim(strip_tags($response));
            if ($clean_response && strlen($clean_response) < 100) {
                echo " - " . $clean_response;
            }
        } elseif ($http_code === 401) {
            echo " 🔐 No autorizado";
        } elseif ($http_code === 404) {
            echo " 🚫 No encontrado";
        } elseif ($http_code >= 500) {
            echo " 🔥 Error servidor";
        }
        
        echo "\n";
    }
    echo "\n";
}

echo "🔍 TAMBIÉN PROBANDO MÉTODO GET PARA DESCUBRIR ENDPOINTS:\n\n";

$discovery_urls = [
    'https://api.flexbis.com/',
    'https://api.flexbis.com/docs',
    'https://api.flexbis.com/swagger',
    'https://api.flexbis.com/openapi.json',
    'https://flexbis.com/.well-known/api',
    'https://api.flexbis.com/health',
    'https://api.flexbis.com/ping'
];

foreach ($discovery_urls as $url) {
    echo "🔍 GET $url\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    
    echo "   HTTP $http_code";
    
    if (strpos($content_type, 'application/json') !== false) {
        echo " 📄 JSON";
        $data = json_decode($response, true);
        if ($data) {
            echo " ✅\n";
            print_r($data);
        }
    } elseif ($http_code === 200 && $response) {
        echo " 🌐 Contenido\n";
        $clean = trim(strip_tags($response));
        if (strlen($clean) < 200) {
            echo "   " . $clean . "\n";
        } else {
            echo "   " . substr($clean, 0, 150) . "...\n";
        }
    } else {
        echo " (sin contenido útil)\n";
    }
}

echo "\n💡 PRÓXIMO PASO:\n";
echo "Si no hay éxito, las credenciales podrían ser para un panel web\n";
echo "en lugar de una API REST. Contactar FlexBis para confirmación.\n";
?>