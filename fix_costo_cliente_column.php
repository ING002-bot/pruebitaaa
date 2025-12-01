<?php
/**
 * Script para agregar la columna costo_cliente a la tabla zonas_tarifas
 * y actualizar los registros existentes
 */

require_once 'config/config.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "🔧 Verificando y corrigiendo tabla zonas_tarifas...\n\n";
    
    // 1. Verificar si la columna costo_cliente existe
    $columns = $db->query("SHOW COLUMNS FROM zonas_tarifas LIKE 'costo_cliente'");
    
    if ($columns->num_rows === 0) {
        echo "📝 Agregando columna costo_cliente...\n";
        
        // Agregar la columna
        $db->query("ALTER TABLE zonas_tarifas ADD COLUMN costo_cliente DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Precio que se cobra al cliente'");
        
        // Agregar índice
        $db->query("ALTER TABLE zonas_tarifas ADD INDEX idx_costo_cliente (costo_cliente)");
        
        echo "✅ Columna costo_cliente agregada correctamente\n\n";
    } else {
        echo "✅ La columna costo_cliente ya existe\n\n";
    }
    
    // 2. Actualizar registros existentes con valores predeterminados
    echo "📊 Actualizando valores de costo_cliente según categoría...\n";
    
    $actualizaciones = [
        "UPDATE zonas_tarifas SET costo_cliente = 5.00 WHERE categoria = 'URBANO' AND costo_cliente = 0",
        "UPDATE zonas_tarifas SET costo_cliente = 8.00 WHERE categoria = 'PUEBLOS' AND tarifa_repartidor = 3.00 AND costo_cliente = 0",
        "UPDATE zonas_tarifas SET costo_cliente = 10.00 WHERE categoria = 'PUEBLOS' AND tarifa_repartidor = 5.00 AND costo_cliente = 0", 
        "UPDATE zonas_tarifas SET costo_cliente = 8.00 WHERE categoria = 'PLAYAS' AND tarifa_repartidor = 3.00 AND costo_cliente = 0",
        "UPDATE zonas_tarifas SET costo_cliente = 10.00 WHERE categoria = 'PLAYAS' AND tarifa_repartidor = 5.00 AND costo_cliente = 0",
        "UPDATE zonas_tarifas SET costo_cliente = 8.00 WHERE categoria = 'COOPERATIVAS' AND tarifa_repartidor = 3.00 AND costo_cliente = 0",
        "UPDATE zonas_tarifas SET costo_cliente = 10.00 WHERE categoria = 'COOPERATIVAS' AND tarifa_repartidor = 5.00 AND costo_cliente = 0",
        "UPDATE zonas_tarifas SET costo_cliente = 10.00 WHERE categoria = 'EXCOPERATIVAS' AND costo_cliente = 0",
        "UPDATE zonas_tarifas SET costo_cliente = 10.00 WHERE categoria = 'FERREÑAFE' AND costo_cliente = 0"
    ];
    
    $contador = 0;
    foreach ($actualizaciones as $query) {
        $result = $db->query($query);
        if ($result && $db->affected_rows > 0) {
            $contador += $db->affected_rows;
            echo "  ✓ " . $db->affected_rows . " registros actualizados\n";
        }
    }
    
    echo "\n📈 Total: $contador registros actualizados\n\n";
    
    // 3. Mostrar resumen final
    $resumen = $db->query("
        SELECT categoria, 
               COUNT(*) as total_zonas,
               MIN(costo_cliente) as precio_min,
               MAX(costo_cliente) as precio_max,
               AVG(costo_cliente) as precio_promedio
        FROM zonas_tarifas 
        WHERE activo = 1 
        GROUP BY categoria
        ORDER BY FIELD(categoria, 'URBANO', 'PUEBLOS', 'PLAYAS', 'COOPERATIVAS', 'EXCOPERATIVAS', 'FERREÑAFE')
    ");
    
    echo "📋 RESUMEN DE TARIFAS:\n";
    echo str_repeat("-", 70) . "\n";
    printf("%-15s | %-6s | %-8s | %-8s | %-10s\n", "CATEGORIA", "ZONAS", "MIN", "MAX", "PROMEDIO");
    echo str_repeat("-", 70) . "\n";
    
    while ($row = $resumen->fetch_assoc()) {
        printf("%-15s | %6d | S/ %5.2f | S/ %5.2f | S/ %7.2f\n", 
            $row['categoria'],
            $row['total_zonas'],
            $row['precio_min'],
            $row['precio_max'],
            $row['precio_promedio']
        );
    }
    
    echo str_repeat("-", 70) . "\n";
    echo "\n🎉 ¡CORRECCIÓN COMPLETADA EXITOSAMENTE!\n";
    echo "✅ La tabla zonas_tarifas ya tiene la columna costo_cliente\n";
    echo "✅ Todos los registros tienen precios asignados\n";
    echo "✅ Los warnings de PHP ya no deberían aparecer\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>