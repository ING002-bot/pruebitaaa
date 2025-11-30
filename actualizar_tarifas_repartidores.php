<?php
/**
 * Actualizar tarifas de repartidores con los montos reales
 */

require_once 'config/config.php';

echo "💰 ACTUALIZANDO TARIFAS REALES DE REPARTIDORES\n";
echo "==============================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Tarifas reales que pagas a repartidores
    $tarifas_repartidores = [
        // URBANO
        'Chiclayo' => 1.50,
        'Leonardo Ortiz' => 1.80,
        'La Victoria' => 1.50,
        'Santa Victoria' => 1.50,
        
        // PUEBLOS - Todos S/ 3.00
        'Lambayeque' => 3.00,
        'Mochumi' => 3.00,
        'Tucume' => 3.00,
        'Illimo' => 3.00,
        'Nueva Arica' => 3.00,
        'Jayanca' => 3.00,
        'Pacora' => 3.00,
        'Morrope' => 3.00,
        'Motupe' => 3.00,
        'Olmos' => 3.00,
        'Salas' => 3.00,
        
        // PLAYAS
        'San Jose' => 2.00,
        'Santa Rosa' => 2.00,
        'Pimentel' => 2.00,
        'Reque' => 2.50,
        'Monsefu' => 2.50,
        'Eten' => 2.50,
        'Puerto Eten' => 2.50,
        
        // COOPERATIVAS - Todos S/ 2.00
        'Pomalca' => 2.00,
        'Tuman' => 2.00,
        'Patapo' => 2.00,
        'Pucala' => 2.00,
        'Sartur' => 2.00,
        'Chongoyape' => 2.00,
        
        // EXCOPERATIVAS - Todos S/ 2.00
        'Ucupe' => 2.00,
        'Mocupe' => 2.00,
        'Zaña' => 2.00,
        'Cayalti' => 2.00,
        'Oyotun' => 2.00,
        'Lagunas' => 2.00,
        
        // FERREÑAFE - Todos S/ 2.50
        'Ferreñafe' => 2.50,
        'Picsi' => 2.50,
        'Pitipo' => 2.50,
        'Motupillo' => 2.50,
        'Pueblo Nuevo' => 2.50
    ];
    
    echo "🔄 ACTUALIZANDO TARIFAS DE REPARTIDORES...\n";
    
    $stmt = $db->prepare("UPDATE zonas_tarifas SET tarifa_repartidor = ? WHERE nombre_zona = ?");
    $actualizadas = 0;
    $total_ganancia = 0;
    
    foreach ($tarifas_repartidores as $zona => $tarifa_repartidor) {
        $stmt->bind_param("ds", $tarifa_repartidor, $zona);
        
        if ($stmt->execute()) {
            // Obtener el costo al cliente para calcular ganancia
            $costo_query = $db->prepare("SELECT costo_cliente FROM zonas_tarifas WHERE nombre_zona = ?");
            $costo_query->bind_param("s", $zona);
            $costo_query->execute();
            $result = $costo_query->get_result()->fetch_assoc();
            $costo_cliente = $result['costo_cliente'];
            
            $ganancia = $costo_cliente - $tarifa_repartidor;
            $total_ganancia += $ganancia;
            
            echo sprintf("   ✅ %s: Repartidor S/ %.2f | Cliente S/ %.2f | 💰 Ganancia: S/ %.2f\n", 
                $zona, $tarifa_repartidor, $costo_cliente, $ganancia);
            
            $actualizadas++;
            $costo_query->close();
        } else {
            echo "   ❌ Error actualizando $zona: " . $stmt->error . "\n";
        }
    }
    
    $stmt->close();
    
    echo "\n📊 RESUMEN DE GANANCIAS POR CATEGORÍA:\n";
    
    $stats = $db->query("
        SELECT 
            categoria,
            COUNT(*) as total_zonas,
            SUM(costo_cliente - tarifa_repartidor) as ganancia_total,
            AVG(costo_cliente - tarifa_repartidor) as ganancia_promedio,
            MIN(costo_cliente - tarifa_repartidor) as ganancia_min,
            MAX(costo_cliente - tarifa_repartidor) as ganancia_max
        FROM zonas_tarifas 
        WHERE activo = 1 
        GROUP BY categoria 
        ORDER BY ganancia_promedio DESC
    ");
    
    while ($row = $stats->fetch_assoc()) {
        echo sprintf("📈 %s: %d zonas | Ganancia promedio: S/ %.2f (Min: S/ %.2f, Max: S/ %.2f)\n",
            $row['categoria'],
            $row['total_zonas'],
            $row['ganancia_promedio'],
            $row['ganancia_min'],
            $row['ganancia_max']
        );
    }
    
    // Agregar columna ganancia_calculada para reportes fáciles
    echo "\n🔧 AGREGANDO COLUMNA CALCULADA DE GANANCIA...\n";
    
    $columns = $db->query("SHOW COLUMNS FROM zonas_tarifas LIKE 'ganancia_calculada'");
    if ($columns->num_rows === 0) {
        $db->query("ALTER TABLE zonas_tarifas ADD COLUMN ganancia_calculada DECIMAL(10,2) GENERATED ALWAYS AS (costo_cliente - tarifa_repartidor) STORED");
        echo "   ✅ Columna ganancia_calculada agregada\n";
    } else {
        echo "   ✅ Columna ganancia_calculada ya existe\n";
    }
    
    // Estadísticas finales
    echo "\n💰 ESTADÍSTICAS FINALES DE RENTABILIDAD:\n";
    
    $rentabilidad = $db->query("
        SELECT 
            nombre_zona,
            categoria,
            costo_cliente,
            tarifa_repartidor,
            (costo_cliente - tarifa_repartidor) as ganancia,
            ROUND(((costo_cliente - tarifa_repartidor) / costo_cliente) * 100, 1) as margen_porcentaje
        FROM zonas_tarifas 
        WHERE activo = 1 
        ORDER BY ganancia DESC
        LIMIT 10
    ");
    
    echo "🏆 TOP 10 ZONAS MÁS RENTABLES:\n";
    while ($row = $rentabilidad->fetch_assoc()) {
        echo sprintf("   %s (%s): S/ %.2f (%.1f%% margen)\n",
            $row['nombre_zona'],
            $row['categoria'],
            $row['ganancia'],
            $row['margen_porcentaje']
        );
    }
    
    echo "\n🎯 RESUMEN EJECUTIVO:\n";
    echo "   ✅ Tarifas de repartidores actualizadas: $actualizadas\n";
    echo "   💰 Sistema calculará automáticamente tus ganancias\n";
    echo "   📊 Márgenes de ganancia configurados correctamente\n";
    echo "   🚀 Sistema listo para operación rentable\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n⏰ " . date('d/m/Y H:i:s') . "\n";
?>