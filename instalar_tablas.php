<?php
/**
 * Script para instalar tablas faltantes en la base de datos
 * Ejecutar en: http://localhost/pruebitaaa/instalar_tablas.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'config/config.php';

$db = Database::getInstance()->getConnection();

echo "<h1>Instalador de Tablas - HERMES EXPRESS</h1>";
echo "<hr>";

// Archivos SQL a ejecutar
$archivos_sql = [
    'database/add_caja_chica.sql' => 'Tablas de Caja Chica',
    'database/add_zonas_tarifas.sql' => 'Tablas de Zonas y Tarifas',
];

foreach ($archivos_sql as $archivo => $descripcion) {
    $ruta_completa = __DIR__ . '/' . $archivo;
    
    if (!file_exists($ruta_completa)) {
        echo "<p style='color: orange;'><strong>⚠ Archivo no encontrado:</strong> $archivo</p>";
        continue;
    }
    
    echo "<h3>$descripcion</h3>";
    echo "<p><strong>Archivo:</strong> $archivo</p>";
    
    $sql_content = file_get_contents($ruta_completa);
    
    // Dividir por punto y coma (;) pero respetando comentarios
    $statements = [];
    $current = '';
    $lines = explode("\n", $sql_content);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Saltar comentarios
        if (strpos($line, '--') === 0 || strpos($line, '/*') === 0) {
            continue;
        }
        
        // Saltar líneas vacías
        if (empty($line)) {
            continue;
        }
        
        $current .= " " . $line;
        
        if (substr($line, -1) === ';') {
            $statements[] = trim($current);
            $current = '';
        }
    }
    
    $exito = 0;
    $errores = 0;
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) {
            continue;
        }
        
        // Mostrar el statement
        echo "<pre style='background: #f5f5f5; padding: 10px; margin: 5px 0; font-size: 11px;'>";
        echo htmlspecialchars(substr($statement, 0, 200)) . (strlen($statement) > 200 ? '...' : '');
        echo "</pre>";
        
        if ($db->multi_query($statement)) {
            // Consumir todos los resultados
            while ($db->next_result()) {
                if ($result = $db->store_result()) {
                    $result->free();
                }
            }
            echo "<span style='color: green;'>✓ OK</span><br>";
            $exito++;
        } else {
            echo "<span style='color: red;'>✗ Error: " . htmlspecialchars($db->error) . "</span><br>";
            $errores++;
        }
    }
    
    echo "<p><strong>Resultados:</strong> $exito ejecutados, $errores errores</p>";
    echo "<hr>";
}

echo "<h3>Verificación de Tablas</h3>";

$tablas_esperadas = [
    'usuarios',
    'paquetes',
    'entregas',
    'rutas',
    'tarifas',
    'caja_chica',
    'notificaciones',
];

$result = $db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'hermes_express'");
$tablas_existentes = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tablas_existentes[] = $row['TABLE_NAME'];
    }
    $result->free();
}

echo "<table style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #ddd;'><th style='border: 1px solid #999; padding: 8px;'>Tabla</th><th style='border: 1px solid #999; padding: 8px;'>Estado</th></tr>";

foreach ($tablas_esperadas as $tabla) {
    $existe = in_array($tabla, $tablas_existentes);
    $estado = $existe ? '<span style="color: green;">✓ Existe</span>' : '<span style="color: red;">✗ Falta</span>';
    echo "<tr><td style='border: 1px solid #999; padding: 8px;'>$tabla</td><td style='border: 1px solid #999; padding: 8px;'>$estado</td></tr>";
}

echo "</table>";

// Verificar vistas
echo "<h3>Verificación de Vistas</h3>";

$vistas_esperadas = [
    'saldo_caja_chica',
];

$result = $db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = 'hermes_express'");
$vistas_existentes = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $vistas_existentes[] = $row['TABLE_NAME'];
    }
    $result->free();
}

echo "<table style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #ddd;'><th style='border: 1px solid #999; padding: 8px;'>Vista</th><th style='border: 1px solid #999; padding: 8px;'>Estado</th></tr>";

foreach ($vistas_esperadas as $vista) {
    $existe = in_array($vista, $vistas_existentes);
    $estado = $existe ? '<span style="color: green;">✓ Existe</span>' : '<span style="color: red;">✗ Falta</span>';
    echo "<tr><td style='border: 1px solid #999; padding: 8px;'>$vista</td><td style='border: 1px solid #999; padding: 8px;'>$estado</td></tr>";
}

echo "</table>";

echo "<hr>";
echo "<p><a href='admin/dashboard.php'>&lt; Volver a Dashboard</a></p>";
?>
