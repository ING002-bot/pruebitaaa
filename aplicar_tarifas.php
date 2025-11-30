<?php
/**
 * Aplicar nuevas tarifas al sistema
 */

require_once 'config/config.php';

echo "🔧 APLICANDO NUEVAS TARIFAS AL SISTEMA\n";
echo "=====================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Agregar columna costo_cliente si no existe
    echo "📊 1. VERIFICANDO ESTRUCTURA DE TABLA...\n";
    
    $columns = $db->query("SHOW COLUMNS FROM zonas_tarifas LIKE 'costo_cliente'");
    if ($columns->num_rows === 0) {
        echo "   ➕ Agregando columna costo_cliente...\n";
        $db->query("ALTER TABLE zonas_tarifas ADD COLUMN costo_cliente DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Precio que se cobra al cliente'");
        echo "   ✅ Columna agregada exitosamente\n";
    } else {
        echo "   ✅ Columna costo_cliente ya existe\n";
    }
    
    // 2. Verificar/Agregar columna zona_tarifa_id en paquetes
    $columns_paquetes = $db->query("SHOW COLUMNS FROM paquetes LIKE 'zona_tarifa_id'");
    if ($columns_paquetes->num_rows === 0) {
        echo "   ➕ Agregando columna zona_tarifa_id a paquetes...\n";
        $db->query("ALTER TABLE paquetes ADD COLUMN zona_tarifa_id INT NULL, ADD INDEX idx_zona_tarifa (zona_tarifa_id)");
        echo "   ✅ Columna zona_tarifa_id agregada\n";
    } else {
        echo "   ✅ Columna zona_tarifa_id ya existe\n";
    }
    
    // 3. Limpiar tarifas existentes
    echo "\n🗑️  2. LIMPIANDO TARIFAS ANTIGUAS...\n";
    $db->query("DELETE FROM zonas_tarifas WHERE 1=1");
    echo "   ✅ Tarifas antiguas eliminadas\n";
    
    // 4. Insertar nuevas tarifas
    echo "\n📋 3. INSERTANDO NUEVAS TARIFAS...\n";
    
    $tarifas = [
        // URBANO (S/ 3.00)
        ['URBANO', 'Chiclayo', 2.50, 3.00],
        ['URBANO', 'Leonardo Ortiz', 2.50, 3.00],
        ['URBANO', 'La Victoria', 2.50, 3.00],
        ['URBANO', 'Santa Victoria', 2.50, 3.00],
        
        // PUEBLOS
        ['PUEBLOS', 'Lambayeque', 4.00, 5.00],
        ['PUEBLOS', 'Mochumi', 6.00, 8.00],
        ['PUEBLOS', 'Tucume', 6.00, 8.00],
        ['PUEBLOS', 'Illimo', 6.00, 8.00],
        ['PUEBLOS', 'Nueva Arica', 6.00, 8.00],
        ['PUEBLOS', 'Jayanca', 6.00, 8.00],
        ['PUEBLOS', 'Pacora', 6.00, 8.00],
        ['PUEBLOS', 'Morrope', 6.00, 8.00],
        ['PUEBLOS', 'Motupe', 6.00, 8.00],
        ['PUEBLOS', 'Olmos', 6.00, 8.00],
        ['PUEBLOS', 'Salas', 6.00, 8.00],
        
        // PLAYAS
        ['PLAYAS', 'San Jose', 4.00, 5.00],
        ['PLAYAS', 'Santa Rosa', 4.00, 5.00],
        ['PLAYAS', 'Pimentel', 4.00, 5.00],
        ['PLAYAS', 'Reque', 4.00, 5.00],
        ['PLAYAS', 'Monsefu', 4.00, 5.00],
        ['PLAYAS', 'Eten', 6.00, 8.00],
        ['PLAYAS', 'Puerto Eten', 6.00, 8.00],
        
        // COOPERATIVAS
        ['COOPERATIVAS', 'Pomalca', 4.00, 5.00],
        ['COOPERATIVAS', 'Tuman', 6.00, 8.00],
        ['COOPERATIVAS', 'Patapo', 6.00, 8.00],
        ['COOPERATIVAS', 'Pucala', 6.00, 8.00],
        ['COOPERATIVAS', 'Sartur', 6.00, 8.00],
        ['COOPERATIVAS', 'Chongoyape', 6.00, 8.00],
        
        // EXCOPERATIVAS
        ['EXCOPERATIVAS', 'Ucupe', 6.00, 8.00],
        ['EXCOPERATIVAS', 'Mocupe', 6.00, 8.00],
        ['EXCOPERATIVAS', 'Zaña', 6.00, 8.00],
        ['EXCOPERATIVAS', 'Cayalti', 6.00, 8.00],
        ['EXCOPERATIVAS', 'Oyotun', 6.00, 8.00],
        ['EXCOPERATIVAS', 'Lagunas', 6.00, 8.00],
        
        // FERREÑAFE
        ['FERREÑAFE', 'Ferreñafe', 6.00, 8.00],
        ['FERREÑAFE', 'Picsi', 6.00, 8.00],
        ['FERREÑAFE', 'Pitipo', 6.00, 8.00],
        ['FERREÑAFE', 'Motupillo', 6.00, 8.00],
        ['FERREÑAFE', 'Pueblo Nuevo', 6.00, 8.00]
    ];
    
    $stmt = $db->prepare("
        INSERT INTO zonas_tarifas (categoria, nombre_zona, tipo_envio, tarifa_repartidor, costo_cliente, activo) 
        VALUES (?, ?, 'Paquete', ?, ?, 1)
    ");
    
    $insertadas = 0;
    foreach ($tarifas as $tarifa) {
        [$categoria, $nombre, $tarifa_repartidor, $costo_cliente] = $tarifa;
        
        $stmt->bind_param("ssdd", $categoria, $nombre, $tarifa_repartidor, $costo_cliente);
        
        if ($stmt->execute()) {
            $insertadas++;
            echo "   ✅ $categoria - $nombre: S/ $costo_cliente (Repartidor: S/ $tarifa_repartidor)\n";
        } else {
            echo "   ❌ Error insertando $nombre: " . $stmt->error . "\n";
        }
    }
    
    echo "\n📊 4. RESUMEN FINAL...\n";
    echo "   ✅ Tarifas insertadas: $insertadas\n";
    
    // Mostrar estadísticas
    $stats = $db->query("
        SELECT 
            categoria,
            COUNT(*) as total_zonas,
            MIN(costo_cliente) as precio_min,
            MAX(costo_cliente) as precio_max,
            AVG(costo_cliente) as precio_promedio
        FROM zonas_tarifas 
        WHERE activo = 1 
        GROUP BY categoria 
        ORDER BY categoria
    ");
    
    echo "\n📋 ESTADÍSTICAS POR CATEGORÍA:\n";
    while ($row = $stats->fetch_assoc()) {
        echo sprintf("   %s: %d zonas (S/ %.2f - S/ %.2f, promedio: S/ %.2f)\n", 
            $row['categoria'], 
            $row['total_zonas'], 
            $row['precio_min'], 
            $row['precio_max'], 
            $row['precio_promedio']
        );
    }
    
    // 5. Actualizar paquetes existentes con zona_tarifa_id
    echo "\n🔄 5. ACTUALIZANDO PAQUETES EXISTENTES...\n";
    $paquetes_actualizados = 0;
    
    $paquetes = $db->query("SELECT id, distrito FROM paquetes WHERE zona_tarifa_id IS NULL AND distrito IS NOT NULL AND distrito != ''");
    
    while ($paquete = $paquetes->fetch_assoc()) {
        $tarifa_info = obtenerTarifaPorDistrito($paquete['distrito']);
        
        if ($tarifa_info) {
            $update_stmt = $db->prepare("UPDATE paquetes SET zona_tarifa_id = ? WHERE id = ?");
            $update_stmt->bind_param("ii", $tarifa_info['id'], $paquete['id']);
            
            if ($update_stmt->execute()) {
                $paquetes_actualizados++;
            }
            $update_stmt->close();
        }
    }
    
    echo "   ✅ Paquetes actualizados con zona_tarifa_id: $paquetes_actualizados\n";
    
    echo "\n🎉 ¡SISTEMA DE TARIFAS ACTUALIZADO EXITOSAMENTE!\n";
    echo "\n💡 PRÓXIMOS PASOS:\n";
    echo "   1. Verificar que los formularios muestren las tarifas correctas\n";
    echo "   2. Probar crear un paquete nuevo\n";
    echo "   3. Confirmar las tarifas para repartidores\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . " Línea: " . $e->getLine() . "\n";
}

echo "\n⏰ " . date('d/m/Y H:i:s') . "\n";
?>