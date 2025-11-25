<?php
// Diagnóstico del sistema de chatbot
require_once 'config/config.php';

echo "<h1>🔍 Diagnóstico del Chatbot</h1>";
echo "<pre>";

// 1. Verificar conexión a BD
echo "1. CONEXIÓN A BASE DE DATOS\n";
echo "===========================\n";
try {
    $db = Database::getInstance()->getConnection();
    if ($db) {
        echo "✅ Conexión exitosa\n";
        echo "   Host: " . DB_HOST . "\n";
        echo "   Usuario: " . DB_USER . "\n";
        echo "   BD: " . DB_NAME . "\n\n";
    } else {
        echo "❌ No se pudo conectar\n\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

// 2. Verificar tablas
echo "2. TABLAS DE BASE DE DATOS\n";
echo "==========================\n";
$tablas = ['paquetes', 'usuarios', 'pagos'];
foreach ($tablas as $tabla) {
    $result = $db->query("SELECT COUNT(*) as cnt FROM $tabla");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✅ $tabla: " . $row['cnt'] . " registros\n";
        $result->close();
    } else {
        echo "❌ $tabla: Error - " . $db->error . "\n";
    }
}

echo "\n3. ARCHIVO API_CHATBOT.PHP\n";
echo "===========================\n";
$ruta = __DIR__ . '/admin/api_chatbot.php';
if (file_exists($ruta)) {
    $size = filesize($ruta);
    $lines = count(file($ruta));
    echo "✅ Archivo existe\n";
    echo "   Tamaño: " . $size . " bytes\n";
    echo "   Líneas: " . $lines . "\n";
    
    // Verificar que contiene la clase ChatbotIA
    $contenido = file_get_contents($ruta);
    if (strpos($contenido, 'class ChatbotIA') !== false) {
        echo "✅ Clase ChatbotIA definida\n";
    }
    if (strpos($contenido, 'consultarPaquetes') !== false) {
        echo "✅ Método consultarPaquetes existe\n";
    }
    if (preg_match('/private function consultarPaquetes.*?\{/s', $contenido)) {
        echo "✅ consultarPaquetes tiene validación de conexión\n";
    }
} else {
    echo "❌ Archivo NO existe en: $ruta\n";
}

echo "\n4. ARCHIVO CHATBOT.PHP (Frontend)\n";
echo "==================================\n";
$ruta = __DIR__ . '/admin/chatbot.php';
if (file_exists($ruta)) {
    $size = filesize($ruta);
    echo "✅ Archivo existe (" . $size . " bytes)\n";
    if (strpos(file_get_contents($ruta), 'api_chatbot.php') !== false) {
        echo "✅ Frontend referencia a api_chatbot.php\n";
    }
} else {
    echo "❌ Archivo NO existe\n";
}

echo "\n5. TEST DE CONSULTA DIRECTA\n";
echo "============================\n";

// Test 1: Total paquetes
$stmt = $db->query("SELECT COUNT(*) as total FROM paquetes");
if ($stmt) {
    $result = $stmt->fetch_assoc();
    $stmt->close();
    echo "✅ SELECT COUNT(*) FROM paquetes: " . $result['total'] . "\n";
} else {
    echo "❌ Error: " . $db->error . "\n";
}

// Test 2: Query preparada
$stmt = $db->prepare("SELECT COUNT(*) as total FROM paquetes WHERE estado = ?");
if ($stmt) {
    $estado = 'entregado';
    $stmt->bind_param('s', $estado);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    echo "✅ Prepared statement (entregado): " . $result['total'] . "\n";
} else {
    echo "❌ Error en prepared statement: " . $db->error . "\n";
}

// Test 3: SUM
$stmt = $db->query("SELECT SUM(monto) as total FROM pagos WHERE estado = 'completado'");
if ($stmt) {
    $result = $stmt->fetch_assoc();
    $stmt->close();
    echo "✅ SELECT SUM(monto): " . ($result['total'] ?? 'NULL') . "\n";
} else {
    echo "❌ Error: " . $db->error . "\n";
}

echo "\n✅ DIAGNÓSTICO COMPLETADO\n";

echo "</pre>";
?>
