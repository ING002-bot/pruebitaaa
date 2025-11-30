<?php
/**
 * Obtener documentación API de FlexBis
 */

echo "=== OBTENIENDO DOCUMENTACIÓN FLEXBIS ===\n\n";

$sid = 'serhsznr';
$token = 'H4vP1g837ZxKR0VMz3yD';

$doc_urls = [
    'https://app.flexbis.com/api/docs',
    'https://app.flexbis.com/swagger',
    'https://app.flexbis.com/docs',
    'https://panel.flexbis.com/api/docs',
    'https://panel.flexbis.com/swagger',
    'https://dashboard.flexbis.com/api/docs'
];

foreach ($doc_urls as $url) {
    echo "🔍 Consultando: $url\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json, text/html',
            'Authorization: Bearer ' . $token,
            'User-Agent: Mozilla/5.0 (compatible; API-Client/1.0)'
        ]
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    
    echo "   HTTP $http_code ($content_type)\n";
    
    if ($http_code === 200 && $response) {
        // Intentar parsear como JSON primero
        $json_data = json_decode($response, true);
        
        if ($json_data && json_last_error() === JSON_ERROR_NONE) {
            echo "   ✅ JSON encontrado!\n";
            
            // Buscar información de endpoints
            if (isset($json_data['paths']) || isset($json_data['endpoints'])) {
                echo "   🎉 DOCUMENTACIÓN SWAGGER/OPENAPI ENCONTRADA!\n";
                
                $paths = $json_data['paths'] ?? $json_data['endpoints'] ?? [];
                foreach ($paths as $path => $methods) {
                    if (strpos($path, 'whatsapp') !== false || 
                        strpos($path, 'send') !== false || 
                        strpos($path, 'message') !== false) {
                        echo "   📱 Endpoint WhatsApp: $path\n";
                        
                        if (is_array($methods)) {
                            foreach ($methods as $method => $details) {
                                echo "     - $method\n";
                            }
                        }
                    }
                }
                
                // Guardar documentación completa
                file_put_contents('flexbis_api_docs.json', json_encode($json_data, JSON_PRETTY_PRINT));
                echo "   💾 Documentación guardada en: flexbis_api_docs.json\n";
            }
            
            // Buscar información de autenticación
            if (isset($json_data['security']) || isset($json_data['auth'])) {
                echo "   🔐 Información de autenticación encontrada!\n";
            }
            
            // Buscar URL base
            if (isset($json_data['servers']) || isset($json_data['host']) || isset($json_data['basePath'])) {
                echo "   🌐 URL base encontrada!\n";
                if (isset($json_data['servers'][0]['url'])) {
                    echo "     Base URL: " . $json_data['servers'][0]['url'] . "\n";
                }
            }
            
        } else {
            // Es HTML, buscar información útil
            echo "   📄 HTML encontrado\n";
            
            // Buscar Swagger UI
            if (strpos($response, 'swagger') !== false || strpos($response, 'SwaggerUI') !== false) {
                echo "   ✅ Swagger UI detectado!\n";
                
                // Buscar URL de la spec
                if (preg_match('/url.*?[\'"]([^\'"]*\.json)[\'"]/', $response, $matches)) {
                    echo "   🎯 Spec JSON: " . $matches[1] . "\n";
                    
                    // Intentar obtener el JSON
                    $spec_url = $matches[1];
                    if (!preg_match('/^https?:\/\//', $spec_url)) {
                        $spec_url = dirname($url) . '/' . ltrim($spec_url, '/');
                    }
                    
                    echo "   🔍 Obteniendo: $spec_url\n";
                    
                    $ch2 = curl_init();
                    curl_setopt_array($ch2, [
                        CURLOPT_URL => $spec_url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT => 10,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_HTTPHEADER => ['Accept: application/json']
                    ]);
                    
                    $spec_response = curl_exec($ch2);
                    $spec_http_code = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                    curl_close($ch2);
                    
                    if ($spec_http_code === 200 && $spec_response) {
                        $spec_data = json_decode($spec_response, true);
                        if ($spec_data) {
                            echo "   🎉 SPEC JSON OBTENIDA!\n";
                            file_put_contents('flexbis_openapi_spec.json', json_encode($spec_data, JSON_PRETTY_PRINT));
                            echo "   💾 Spec guardada en: flexbis_openapi_spec.json\n";
                        }
                    }
                }
            }
            
            // Buscar endpoints en el HTML
            if (preg_match_all('/(?:POST|GET|PUT|DELETE)\s+([\/\w\-{}]+)/', $response, $endpoints)) {
                echo "   🎯 Endpoints encontrados:\n";
                foreach (array_unique($endpoints[1]) as $endpoint) {
                    echo "     - $endpoint\n";
                }
            }
        }
    } else {
        echo "   ❌ No accesible o sin contenido\n";
    }
    
    echo "\n";
}

echo "💡 TAMBIÉN PROBANDO RUTAS DE ESPECIFICACIÓN OPENAPI:\n\n";

$spec_urls = [
    'https://app.flexbis.com/openapi.json',
    'https://app.flexbis.com/api/v1/openapi.json',
    'https://app.flexbis.com/swagger.json',
    'https://panel.flexbis.com/openapi.json'
];

foreach ($spec_urls as $url) {
    echo "🔍 Spec: $url\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ]
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 && $response) {
        $data = json_decode($response, true);
        if ($data && isset($data['openapi'])) {
            echo "   ✅ OpenAPI spec encontrada!\n";
            echo "   Versión: " . $data['openapi'] . "\n";
            
            if (isset($data['info']['title'])) {
                echo "   Título: " . $data['info']['title'] . "\n";
            }
            
            $filename = 'flexbis_openapi_' . time() . '.json';
            file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
            echo "   💾 Guardada en: $filename\n";
        }
    }
    
    echo "\n";
}

echo "📁 Archivos generados:\n";
$files = glob('flexbis_*.json');
foreach ($files as $file) {
    echo "   - $file (" . filesize($file) . " bytes)\n";
}
?>