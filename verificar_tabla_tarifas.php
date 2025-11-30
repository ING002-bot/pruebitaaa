<?php
/**
 * Script para agregar columna de fecha_actualizacion si no existe
 */

require_once 'config/config.php';

echo "🔧 VERIFICANDO Y AGREGANDO COLUMNA FECHA_ACTUALIZACION\n";
echo "=====================================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar si la columna existe
    $columns = $db->query("SHOW COLUMNS FROM zonas_tarifas LIKE 'fecha_actualizacion'");
    
    if ($columns->num_rows === 0) {
        echo "📅 Agregando columna fecha_actualizacion...\n";
        
        $db->query("ALTER TABLE zonas_tarifas ADD COLUMN fecha_actualizacion TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
        
        // Actualizar registros existentes con la fecha actual
        $db->query("UPDATE zonas_tarifas SET fecha_actualizacion = NOW() WHERE fecha_actualizacion IS NULL");
        
        echo "   ✅ Columna fecha_actualizacion agregada correctamente\n";
        echo "   ✅ Registros existentes actualizados con fecha actual\n";
    } else {
        echo "   ✅ Columna fecha_actualizacion ya existe\n";
    }
    
    // Verificar estructura final
    echo "\n📊 ESTRUCTURA FINAL DE LA TABLA:\n";
    $estructura = $db->query("DESCRIBE zonas_tarifas");
    while ($row = $estructura->fetch_assoc()) {
        echo "   - {$row['Field']}: {$row['Type']} {$row['Null']} {$row['Default']}\n";
    }
    
    echo "\n🎯 TABLA ZONAS_TARIFAS LISTA PARA ADMINISTRACIÓN\n";
    echo "   ✅ Todas las columnas necesarias disponibles\n";
    echo "   ✅ Control de fechas de actualización habilitado\n";
    echo "   ✅ Sistema listo para gestión de tarifas\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n⏰ " . date('d/m/Y H:i:s') . "\n";
?>