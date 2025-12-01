<?php
/**
 * Script para actualizar las tarifas con los nuevos valores
 */

require_once 'config/config.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "🔧 Actualizando tarifas en la base de datos...\n\n";
    
    // Datos actualizados: [zona, costo_cliente, tarifa_repartidor]
    $nuevas_tarifas = [
        // URBANO
        ['Chiclayo', 3.00, 1.50],
        ['Leonardo Ortiz', 3.00, 1.80], 
        ['La Victoria', 3.00, 1.50],
        ['Santa Victoria', 3.00, 1.50],
        
        // PUEBLOS  
        ['Lambayeque', 5.00, 3.00],
        ['Mochumi', 8.00, 3.00],
        ['Tucume', 8.00, 3.00],
        ['Illimo', 8.00, 3.00],
        ['Nueva Arica', 8.00, 3.00],
        ['Jayanca', 8.00, 3.00],
        ['Pacora', 8.00, 3.00],
        ['Morrope', 8.00, 3.00],
        ['Motupe', 8.00, 3.00],
        ['Olmos', 8.00, 3.00],
        ['Salas', 8.00, 3.00],
        
        // PLAYAS
        ['San Jose', 5.00, 2.00],
        ['Santa Rosa', 5.00, 2.00],
        ['Pimentel', 5.00, 2.00],
        ['Reque', 5.00, 2.50],
        ['Monsefu', 5.00, 2.50],
        ['Eten', 8.00, 2.50],
        ['Puerto Eten', 8.00, 2.50],
        
        // COOPERATIVAS
        ['Pomalca', 5.00, 2.00],
        ['Tuman', 8.00, 2.00],
        ['Patapo', 8.00, 2.00],
        ['Pucala', 8.00, 2.00],
        ['Sartur', 8.00, 2.00],
        ['Chongoyape', 8.00, 2.00],
        
        // EXCOPERATIVAS
        ['Ucupe', 8.00, 2.00],
        ['Mocupe', 8.00, 2.00],
        ['Zaña', 8.00, 2.00],
        ['Cayalti', 8.00, 2.00],
        ['Oyotun', 8.00, 2.00],
        ['Lagunas', 8.00, 2.00],
        
        // FERREÑAFE
        ['Ferreñafe', 8.00, 2.50],
        ['Picsi', 8.00, 2.50],
        ['Pitipo', 8.00, 2.50],
        ['Motupillo', 8.00, 2.50],
        ['Pueblo Nuevo', 8.00, 2.50]
    ];
    
    $stmt = $db->prepare("UPDATE zonas_tarifas SET costo_cliente = ?, tarifa_repartidor = ? WHERE nombre_zona = ?");
    
    $contador = 0;
    $total_zonas = count($nuevas_tarifas);
    
    foreach ($nuevas_tarifas as $tarifa) {
        $zona = $tarifa[0];
        $costo_cliente = $tarifa[1];
        $tarifa_repartidor = $tarifa[2];
        
        $stmt->bind_param("dds", $costo_cliente, $tarifa_repartidor, $zona);
        
        if ($stmt->execute()) {
            if ($db->affected_rows > 0) {
                $contador++;
                echo "✓ $zona: Cliente S/ $costo_cliente - Repartidor S/ $tarifa_repartidor\n";
            } else {
                echo "⚠ $zona: No se encontró en la base de datos\n";
            }
        }
    }
    
    echo "\n📊 Resumen de actualización:\n";
    echo "✅ $contador de $total_zonas zonas actualizadas correctamente\n\n";
    
    // Mostrar resumen por categoría
    $resumen = $db->query("
        SELECT categoria, 
               COUNT(*) as total_zonas,
               MIN(costo_cliente) as cliente_min,
               MAX(costo_cliente) as cliente_max,
               MIN(tarifa_repartidor) as repartidor_min,
               MAX(tarifa_repartidor) as repartidor_max,
               AVG(costo_cliente - tarifa_repartidor) as ganancia_promedio
        FROM zonas_tarifas 
        WHERE activo = 1 
        GROUP BY categoria
        ORDER BY FIELD(categoria, 'URBANO', 'PUEBLOS', 'PLAYAS', 'COOPERATIVAS', 'EXCOPERATIVAS', 'FERREÑAFE')
    ");
    
    echo "📋 NUEVAS TARIFAS POR CATEGORÍA:\n";
    echo str_repeat("-", 85) . "\n";
    printf("%-15s | %-6s | %-12s | %-14s | %-12s\n", "CATEGORIA", "ZONAS", "CLIENTE", "REPARTIDOR", "GANANCIA");
    echo str_repeat("-", 85) . "\n";
    
    while ($row = $resumen->fetch_assoc()) {
        printf("%-15s | %6d | S/ %4.2f-%5.2f | S/ %4.2f-%5.2f | S/ %7.2f\n", 
            $row['categoria'],
            $row['total_zonas'],
            $row['cliente_min'],
            $row['cliente_max'],
            $row['repartidor_min'],
            $row['repartidor_max'],
            $row['ganancia_promedio']
        );
    }
    
    echo str_repeat("-", 85) . "\n";
    echo "\n🎉 ¡TARIFAS ACTUALIZADAS EXITOSAMENTE!\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>