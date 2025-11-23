<?php
/**
 * Script de verificación de sintaxis PDO vs MySQLi
 * Detecta archivos que puedan tener el problema de usar sintaxis PDO
 */

echo "<h2>Verificación de Sintaxis PDO/MySQLi</h2>";
echo "<pre>";

$directorios = ['admin', 'repartidor', 'asistente', 'api', 'auth', 'config'];
$archivos_problematicos = [];
$archivos_sospechosos = [];

foreach ($directorios as $dir) {
    if (!is_dir($dir)) continue;
    
    $archivos = glob("$dir/*.php");
    
    foreach ($archivos as $archivo) {
        $contenido = file_get_contents($archivo);
        
        // Buscar patrones problemáticos
        // 1. execute con array como parámetro (PDO style)
        if (preg_match('/\$\w+->execute\s*\(\s*\[/', $contenido)) {
            $archivos_problematicos[] = [
                'archivo' => $archivo,
                'problema' => 'Usa execute([...]) - sintaxis PDO',
                'severidad' => 'CRÍTICO'
            ];
        }
        
        // 2. fetch() sin get_result() previo
        if (preg_match('/\$\w+->execute\s*\([^)]*\)\s*;?\s*\n.*\$\w+->fetch\s*\(/', $contenido)) {
            $archivos_sospechosos[] = [
                'archivo' => $archivo,
                'problema' => 'Posible uso de fetch() sin get_result()',
                'severidad' => 'ADVERTENCIA'
            ];
        }
        
        // 3. fetchColumn() - método de PDO
        if (preg_match('/->fetchColumn\s*\(/', $contenido)) {
            $archivos_sospechosos[] = [
                'archivo' => $archivo,
                'problema' => 'Usa fetchColumn() - método de PDO',
                'severidad' => 'ADVERTENCIA'
            ];
        }
        
        // 4. prepare sin verificación de error
        if (preg_match('/\$\w+\s*=\s*\$\w+->prepare\([^;]+;\s*\n\s*\$\w+->bind_param/', $contenido)) {
            // Verificar si NO hay validación después del prepare
            if (!preg_match('/if\s*\(\s*!\s*\$\w+\s*\)/', $contenido)) {
                $archivos_sospechosos[] = [
                    'archivo' => $archivo,
                    'problema' => 'prepare() sin validación de error',
                    'severidad' => 'SUGERENCIA'
                ];
            }
        }
    }
}

// Mostrar resultados
echo "=== RESULTADOS DE LA VERIFICACIÓN ===\n\n";

if (empty($archivos_problematicos) && empty($archivos_sospechosos)) {
    echo "✅ ¡EXCELENTE! No se encontraron problemas.\n";
    echo "   Todos los archivos usan sintaxis MySQLi correctamente.\n";
} else {
    if (!empty($archivos_problematicos)) {
        echo "🔴 ARCHIVOS CON PROBLEMAS CRÍTICOS: " . count($archivos_problematicos) . "\n";
        echo str_repeat("=", 70) . "\n\n";
        
        foreach ($archivos_problematicos as $info) {
            echo "❌ {$info['archivo']}\n";
            echo "   Problema: {$info['problema']}\n";
            echo "   Severidad: {$info['severidad']}\n\n";
        }
    }
    
    if (!empty($archivos_sospechosos)) {
        echo "\n⚠️  ARCHIVOS CON ADVERTENCIAS: " . count($archivos_sospechosos) . "\n";
        echo str_repeat("=", 70) . "\n\n";
        
        foreach ($archivos_sospechosos as $info) {
            echo "⚠️  {$info['archivo']}\n";
            echo "   Problema: {$info['problema']}\n";
            echo "   Severidad: {$info['severidad']}\n\n";
        }
    }
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "Verificación completada.\n";
echo "Total de archivos analizados: ";

$total = 0;
foreach ($directorios as $dir) {
    if (is_dir($dir)) {
        $total += count(glob("$dir/*.php"));
    }
}
echo "$total\n";

echo "</pre>";

// Mostrar recomendaciones
if (!empty($archivos_problematicos) || !empty($archivos_sospechosos)) {
    echo "<h3>Recomendaciones</h3>";
    echo "<ol>";
    echo "<li>Revisar y corregir los archivos marcados como CRÍTICOS inmediatamente</li>";
    echo "<li>Verificar las advertencias para asegurar el correcto funcionamiento</li>";
    echo "<li>Agregar validación de errores en todos los prepare()</li>";
    echo "<li>Usar get_result()->fetch_assoc() en lugar de fetch()</li>";
    echo "<li>Ejecutar este script regularmente durante el desarrollo</li>";
    echo "</ol>";
}

echo '<br><a href="index.php" class="btn btn-primary">Volver al inicio</a>';
?>
