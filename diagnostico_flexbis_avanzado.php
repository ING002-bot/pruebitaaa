<?php
/**
 * Diagnóstico avanzado de FlexBis API
 * Para identificar exactamente dónde está el problema
 */

require_once 'config/config.php';

echo "=== DIAGNÓSTICO AVANZADO FLEXBIS ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Verificar constantes
echo "1. VERIFICACIÓN DE CONSTANTES:\n";
echo "   WHATSAPP_API_TYPE: " . (defined('WHATSAPP_API_TYPE') ? WHATSAPP_API_TYPE : 'NO DEFINIDO') . "\n";
echo "   FLEXBIS_API_SID: " . (defined('FLEXBIS_API_SID') ? (FLEXBIS_API_SID ? 'DEFINIDO (' . substr(FLEXBIS_API_SID, 0, 4) . '****)' : 'VACÍO') : 'NO DEFINIDO') . "\n";
echo "   FLEXBIS_API_KEY: " . (defined('FLEXBIS_API_KEY') ? (FLEXBIS_API_KEY ? 'DEFINIDO (****' . substr(FLEXBIS_API_KEY, -4) . ')' : 'VACÍO') : 'NO DEFINIDO') . "\n";
echo "   FLEXBIS_API_URL: " . (defined('FLEXBIS_API_URL') ? FLEXBIS_API_URL : 'NO DEFINIDO') . "\n";
echo "   FLEXBIS_WHATSAPP_FROM: " . (defined('FLEXBIS_WHATSAPP_FROM') ? FLEXBIS_WHATSAPP_FROM : 'NO DEFINIDO') . "\n\n";

// 2. Verificar extensiones PHP
echo "2. VERIFICACIÓN DE EXTENSIONES PHP:\n";
$extensiones = ['curl', 'json', 'openssl'];
foreach ($extensiones as $ext) {
    echo "   $ext: " . (extension_loaded($ext) ? '✅ HABILITADA' : '❌ NO DISPONIBLE') . "\n";
}
echo "\n";

// 3. Test de conectividad básica
echo "3. TEST DE CONECTIVIDAD BÁSICA:\n";
$test_urls = [
    'Google' => 'https://www.google.com',
    'FlexBis (estimada)' => 'https://api.flexbis.com'
];

foreach ($test_urls as $name => $url) {
    echo "   Probando $name ($url)...\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_NOBODY => true, // Solo HEAD request
        CURLOPT_FOLLOWLOCATION => true
    ]);
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        echo "   ❌ ERROR cURL: $curl_error\n";
    } else {
        echo "   ✅ HTTP $http_code\n";
    }
}
echo "\n";

// 4. Test de endpoints FlexBis específicos
echo "4. TEST DE ENDPOINTS FLEXBIS:\n";

if (!defined('FLEXBIS_API_KEY') || !FLEXBIS_API_KEY) {
    echo "   ⚠️ No hay API Key configurada, saltando tests de API\n";
} else {
    $base_url = defined('FLEXBIS_API_URL') ? FLEXBIS_API_URL : 'https://api.flexbis.com/v1/';
    $base_url = rtrim($base_url, '/');
    
    // Posibles endpoints de FlexBis
    $endpoints = [
        '/health',
        '/status', 
        '/v1/health',
        '/v1/status',
        '/api/v1/health',
        '/api/v1/status',
        '/me',
        '/account',
        '/auth/verify'
    ];
    
    foreach ($endpoints as $endpoint) {
        $full_url = $base_url . $endpoint;
        echo "   Probando: $full_url\n";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $full_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . FLEXBIS_API_KEY,
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        
        if ($curl_error) {
            echo "     ❌ cURL Error: $curl_error\n";
        } else {
            echo "     HTTP $http_code";
            if ($content_type) {
                echo " ($content_type)";
            }
            
            if ($http_code === 200) {
                echo " ✅ ÉXITO!";
                $data = json_decode($response, true);
                if ($data && json_last_error() === JSON_ERROR_NONE) {
                    echo " - JSON válido";
                    if (isset($data['status']) || isset($data['message']) || isset($data['health'])) {
                        echo " - Respuesta de API reconocida";
                    }
                }
            } elseif ($http_code === 401) {
                echo " 🔑 Necesita autenticación";
            } elseif ($http_code === 404) {
                echo " 🚫 No encontrado";
            } elseif ($http_code >= 500) {
                echo " 🔥 Error del servidor";
            }
            echo "\n";
            
            // Mostrar respuesta si es pequeña y útil
            if ($response && strlen($response) < 200 && $http_code !== 404) {
                echo "     Respuesta: " . trim($response) . "\n";
            }
        }
        echo "\n";
    }
}

// 5. Test de diferentes métodos de autenticación
echo "5. TEST DE MÉTODOS DE AUTENTICACIÓN:\n";

if (defined('FLEXBIS_API_KEY') && FLEXBIS_API_KEY && defined('FLEXBIS_API_SID') && FLEXBIS_API_SID) {
    $base_url = defined('FLEXBIS_API_URL') ? FLEXBIS_API_URL : 'https://api.flexbis.com/v1/';
    $base_url = rtrim($base_url, '/');
    
    $auth_methods = [
        'Bearer Token' => ['Authorization: Bearer ' . FLEXBIS_API_KEY],
        'API Key Header' => ['X-API-Key: ' . FLEXBIS_API_KEY],
        'Basic Auth (SID:Key)' => ['Authorization: Basic ' . base64_encode(FLEXBIS_API_SID . ':' . FLEXBIS_API_KEY)],
        'FlexBis Auth' => ['Authorization: FlexBis ' . FLEXBIS_API_KEY],
        'SID + Key Headers' => ['X-SID: ' . FLEXBIS_API_SID, 'X-API-Key: ' . FLEXBIS_API_KEY]
    ];
    
    foreach ($auth_methods as $method_name => $headers) {
        echo "   Método: $method_name\n";
        
        $all_headers = array_merge([
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: HermesExpress-Test/1.0'
        ], $headers);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $base_url . '/status',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => $all_headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            echo "     ❌ Error: $curl_error\n";
        } else {
            echo "     HTTP $http_code";
            if ($http_code === 200) {
                echo " ✅ FUNCIONA!";
            } elseif ($http_code === 401) {
                echo " 🔑 No autorizado";
            } elseif ($http_code === 404) {
                echo " 🚫 Endpoint no encontrado";
            }
            echo "\n";
            
            if ($response && strlen($response) < 300) {
                echo "     Respuesta: " . trim($response) . "\n";
            }
        }
        echo "\n";
    }
}

echo "=== FIN DEL DIAGNÓSTICO ===\n";
?>