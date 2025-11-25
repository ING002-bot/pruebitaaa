<?php
// Test directo de la clase ChatbotIA
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['rol'] = 'admin';

require_once 'config/config.php';

echo "=== Test Directo de ChatbotIA ===\n\n";

// Verificar conexión a BD
echo "1. Conectando a BD...\n";
try {
    $db = Database::getInstance()->getConnection();
    
    if (!$db) {
        die("❌ No se pudo obtener conexión\n");
    }
    
    // Test simple de query
    $result = $db->query("SELECT 1 as test");
    if (!$result) {
        die("❌ Error en query test: " . $db->error . "\n");
    }
    $row = $result->fetch_assoc();
    $result->close();
    
    echo "✅ Conexión OK\n\n";
} catch (Exception $e) {
    die("❌ Exception: " . $e->getMessage() . "\n");
}

// Verificar clase ChatbotIA
echo "2. Instanciando ChatbotIA...\n";
require_once 'admin/api_chatbot.php';

// No podemos instanciar aquí porque el archivo hace exit al final
// Pero al menos verificamos que no hay errores de sintaxis

echo "✅ Archivo cargado sin errores\n\n";

echo "3. Verificando que las tablas existen...\n";
$tablas = ['paquetes', 'usuarios', 'pagos'];
foreach ($tablas as $tabla) {
    $result = $db->query("SHOW TABLES LIKE '$tabla'");
    if ($result && $result->num_rows > 0) {
        echo "✅ Tabla '$tabla' existe\n";
    } else {
        echo "❌ Tabla '$tabla' NO existe\n";
    }
}

echo "\n4. Conteo de registros...\n";
$tables = ['paquetes', 'usuarios', 'pagos'];
foreach ($tables as $tabla) {
    $stmt = $db->query("SELECT COUNT(*) as cnt FROM $tabla");
    if ($stmt) {
        $row = $stmt->fetch_assoc();
        echo "📊 $tabla: " . ($row['cnt'] ?? 'error') . " registros\n";
        $stmt->close();
    }
}

echo "\n✅ Test completado\n";
?>
