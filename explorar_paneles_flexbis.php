<?php
/**
 * Intentar acceso a panel FlexBis para encontrar API real
 */

echo "=== ACCESO A PANELES FLEXBIS ===\n";
echo "Buscando API real dentro de los paneles...\n\n";

$sid = 'serhsznr';
$token = 'H4vP1g837ZxKR0VMz3yD';

$paneles = [
    'https://app.flexbis.com',
    'https://panel.flexbis.com', 
    'https://dashboard.flexbis.com'
];

foreach ($paneles as $panel_url) {
    echo "🔍 Explorando: $panel_url\n";
    
    // Primero obtener la página de login
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $panel_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $final_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    
    echo "   HTTP $http_code\n";
    echo "   URL final: $final_url\n";
    
    if ($response && $http_code === 200) {
        // Buscar información relevante en la página
        $keywords = [
            'api' => '/api/i',
            'documentation' => '/documentation|docs|api-docs/i',
            'webhook' => '/webhook/i',
            'token' => '/token|api.*key/i',
            'whatsapp' => '/whatsapp|wp|wa/i',
            'login' => '/login|signin|auth/i'
        ];
        
        foreach ($keywords as $keyword => $pattern) {
            if (preg_match($pattern, $response)) {
                echo "   ✅ Encontrado: $keyword\n";
            }
        }
        
        // Buscar formularios de login
        if (preg_match_all('/<form[^>]*>/i', $response, $forms)) {
            echo "   📝 Formularios encontrados: " . count($forms[0]) . "\n";
        }
        
        // Buscar enlaces a API o documentación
        if (preg_match_all('/href=["\']([^"\']*(?:api|docs|documentation)[^"\']*)["\']/', $response, $links)) {
            echo "   🔗 Enlaces API/Docs:\n";
            foreach (array_unique($links[1]) as $link) {
                echo "     - $link\n";
            }
        }
        
        // Buscar scripts o configuraciones JavaScript que puedan tener endpoints
        if (preg_match_all('/(?:api|endpoint|url).*?[\'"]([^\'"]*(api|send|message)[^\'"]*)[\'"]/', $response, $endpoints)) {
            echo "   🎯 Posibles endpoints:\n";
            foreach (array_unique($endpoints[1]) as $endpoint) {
                echo "     - $endpoint\n";
            }
        }
        
    } else {
        echo "   ❌ No accesible\n";
    }
    
    echo "\n";
}

echo "🔍 BUSCANDO RUTAS COMUNES DE LOGIN Y API:\n\n";

$rutas_comunes = [
    '/login',
    '/api/login',
    '/auth/login',
    '/signin',
    '/api/auth',
    '/api/token',
    '/api/v1/auth',
    '/api/docs',
    '/docs',
    '/documentation',
    '/swagger',
    '/api-docs'
];

foreach ($paneles as $panel_url) {
    echo "📱 Panel: $panel_url\n";
    
    foreach ($rutas_comunes as $ruta) {
        $full_url = $panel_url . $ruta;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $full_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_NOBODY => true, // Solo HEAD request
            CURLOPT_USERAGENT => 'Mozilla/5.0'
        ]);
        
        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            echo "   ✅ $ruta (HTTP $http_code)\n";
        } elseif ($http_code === 302 || $http_code === 301) {
            echo "   🔄 $ruta (Redirect $http_code)\n";
        }
    }
    echo "\n";
}

echo "💡 ALTERNATIVA - CREAR TICKET DE SOPORTE:\n";
echo "Si los paneles requieren login web, podemos:\n";
echo "1. Contactar soporte técnico directamente\n";
echo "2. Solicitar documentación específica de API\n";
echo "3. Preguntar si las credenciales son correctas\n\n";

// Intentar una búsqueda más específica en Google
echo "🔍 RECOMENDACIÓN:\n";
echo "Buscar en Google: 'site:flexbis.com API documentation WhatsApp'\n";
echo "O: 'FlexBis API endpoints PHP example'\n";
?>