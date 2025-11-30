<?php
/**
 * HERMES EXPRESS - Diagnóstico Flexbis
 * 
 * Verificación rápida del estado de la migración a Flexbis
 */

require_once 'config/config.php';

echo "🔍 DIAGNÓSTICO FLEXBIS WHATSAPP API\n";
echo "==================================\n\n";

// 1. Verificar constantes
echo "📋 CONSTANTES DEFINIDAS:\n";
$constantes = [
    'WHATSAPP_API_TYPE',
    'FLEXBIS_API_SID', 
    'FLEXBIS_API_KEY',
    'FLEXBIS_API_URL',
    'FLEXBIS_WHATSAPP_FROM'
];

foreach ($constantes as $const) {
    $definida = defined($const);
    $valor = $definida ? constant($const) : 'NO DEFINIDA';
    $status = $definida ? '✅' : '❌';
    
    if ($const === 'FLEXBIS_API_KEY' && $definida && !empty($valor)) {
        $valor = substr($valor, 0, 8) . '...'; // Ocultar key
    }
    
    echo "  $status $const: $valor\n";
}

echo "\n";

// 2. Verificar extensiones PHP
echo "🔧 EXTENSIONES PHP:\n";
$extensiones = ['curl', 'json', 'mysqli'];
foreach ($extensiones as $ext) {
    $cargada = extension_loaded($ext);
    $status = $cargada ? '✅' : '❌';
    echo "  $status $ext: " . ($cargada ? 'Habilitada' : 'NO DISPONIBLE') . "\n";
}

echo "\n";

// 3. Verificar archivos críticos
echo "📁 ARCHIVOS CRÍTICOS:\n";
$archivos = [
    'config/whatsapp_helper.php',
    'test_flexbis.php',
    '.env.example'
];

foreach ($archivos as $archivo) {
    $existe = file_exists($archivo);
    $status = $existe ? '✅' : '❌';
    $tamaño = $existe ? filesize($archivo) : 0;
    echo "  $status $archivo" . ($existe ? " ($tamaño bytes)" : '') . "\n";
}

echo "\n";

// 4. Verificar conectividad básica (si curl está disponible)
if (extension_loaded('curl')) {
    echo "🌐 TEST DE CONECTIVIDAD:\n";
    
    $test_url = defined('FLEXBIS_API_URL') ? constant('FLEXBIS_API_URL') : 'https://api.flexbis.com/v1/';
    
    $ch = curl_init($test_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_NOBODY, true); // Solo headers
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "  ❌ Error de conectividad: $error\n";
    } else {
        $status = ($http_code >= 200 && $http_code < 500) ? '✅' : '⚠️';
        echo "  $status Conectividad a $test_url: HTTP $http_code\n";
    }
} else {
    echo "🌐 TEST DE CONECTIVIDAD: ❌ cURL no disponible\n";
}

echo "\n";

// 5. Estado de configuración
echo "⚙️  ESTADO DE CONFIGURACIÓN:\n";

$api_type = defined('WHATSAPP_API_TYPE') ? constant('WHATSAPP_API_TYPE') : 'no_definido';
$sid_ok = defined('FLEXBIS_API_SID') && !empty(constant('FLEXBIS_API_SID'));
$key_ok = defined('FLEXBIS_API_KEY') && !empty(constant('FLEXBIS_API_KEY'));
$from_ok = defined('FLEXBIS_WHATSAPP_FROM') && !empty(constant('FLEXBIS_WHATSAPP_FROM'));

echo "  API Tipo: $api_type\n";
echo "  Credenciales: " . (($sid_ok && $key_ok) ? '✅ Configuradas' : '❌ Faltantes') . "\n";
echo "  Número From: " . ($from_ok ? '✅ Configurado' : '❌ Faltante') . "\n";

$listo = ($api_type === 'flexbis') && $sid_ok && $key_ok && $from_ok;
echo "  Estado general: " . ($listo ? '✅ LISTO PARA USAR' : '⚠️ REQUIERE CONFIGURACIÓN') . "\n";

echo "\n";

// 6. Próximos pasos
echo "📝 PRÓXIMOS PASOS:\n";

if (!$listo) {
    echo "  1. Configurar variables de entorno en .env\n";
    echo "  2. Obtener credenciales SID y KEY de Flexbis\n";
    echo "  3. Configurar número FROM autorizado\n";
    echo "  4. Ejecutar test_flexbis.php\n";
} else {
    echo "  1. Ir a: http://localhost/pruebitaaa/test_flexbis.php\n";
    echo "  2. Ejecutar 'Verificar Configuración'\n";
    echo "  3. Ejecutar 'Test de Autenticación'\n";
    echo "  4. Enviar mensaje de prueba\n";
}

echo "\n";
echo "🕒 Diagnóstico completado: " . date('Y-m-d H:i:s') . "\n";
echo "==================================\n";
?>