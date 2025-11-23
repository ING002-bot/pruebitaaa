<?php
/**
 * Script para actualizar la estructura de la tabla gastos
 * Agrega los campos necesarios: descripcion, numero_comprobante, comprobante_archivo
 */

require_once 'config/config.php';

// Solo permitir ejecución para administradores o desde línea de comandos
if (php_sapi_name() !== 'cli') {
    session_start();
    if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
        die('Acceso denegado. Solo administradores pueden ejecutar este script.');
    }
}

echo "<h2>Actualización de la tabla gastos</h2>";
echo "<pre>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar si la tabla existe
    $check_table = $db->query("SHOW TABLES LIKE 'gastos'");
    if ($check_table->num_rows === 0) {
        throw new Exception("La tabla 'gastos' no existe. Por favor, ejecute primero schema.sql");
    }
    
    echo "✓ Tabla 'gastos' encontrada\n\n";
    
    // Verificar si ya tiene la columna 'descripcion'
    $check_column = $db->query("SHOW COLUMNS FROM gastos LIKE 'descripcion'");
    
    if ($check_column->num_rows > 0) {
        echo "ℹ La tabla ya tiene la estructura actualizada.\n";
    } else {
        echo "Actualizando estructura de la tabla gastos...\n\n";
        
        // Agregar columna descripcion
        echo "- Agregando columna 'descripcion'... ";
        $db->query("ALTER TABLE gastos ADD COLUMN descripcion VARCHAR(200) AFTER categoria");
        echo "✓\n";
        
        // Copiar datos de concepto a descripcion
        echo "- Migrando datos de 'concepto' a 'descripcion'... ";
        $db->query("UPDATE gastos SET descripcion = concepto WHERE descripcion IS NULL OR descripcion = ''");
        echo "✓\n";
        
        // Agregar columna numero_comprobante
        echo "- Agregando columna 'numero_comprobante'... ";
        $db->query("ALTER TABLE gastos ADD COLUMN numero_comprobante VARCHAR(100) AFTER monto");
        echo "✓\n";
        
        // Agregar columna comprobante_archivo
        echo "- Agregando columna 'comprobante_archivo'... ";
        $db->query("ALTER TABLE gastos ADD COLUMN comprobante_archivo VARCHAR(255) AFTER numero_comprobante");
        echo "✓\n";
        
        // Hacer concepto opcional
        echo "- Modificando columna 'concepto' a opcional... ";
        $db->query("ALTER TABLE gastos MODIFY COLUMN concepto VARCHAR(200) NULL");
        echo "✓\n";
        
        // Agregar índice
        echo "- Agregando índice para 'numero_comprobante'... ";
        $check_index = $db->query("SHOW INDEX FROM gastos WHERE Key_name = 'idx_numero_comprobante'");
        if ($check_index->num_rows === 0) {
            $db->query("ALTER TABLE gastos ADD INDEX idx_numero_comprobante (numero_comprobante)");
        }
        echo "✓\n";
        
        echo "\n✅ Actualización completada exitosamente!\n";
    }
    
    // Crear directorio de uploads si no existe
    echo "\nVerificando directorio de uploads...\n";
    $upload_dir = __DIR__ . '/uploads/gastos/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
        echo "✓ Directorio 'uploads/gastos/' creado\n";
    } else {
        echo "✓ Directorio 'uploads/gastos/' ya existe\n";
    }
    
    // Mostrar estructura actual
    echo "\nEstructura actual de la tabla 'gastos':\n";
    echo str_repeat("-", 80) . "\n";
    
    $columns = $db->query("SHOW COLUMNS FROM gastos");
    printf("%-25s %-20s %-10s %-10s\n", "Campo", "Tipo", "Null", "Key");
    echo str_repeat("-", 80) . "\n";
    while ($column = $columns->fetch_assoc()) {
        printf("%-25s %-20s %-10s %-10s\n", 
            $column['Field'], 
            $column['Type'], 
            $column['Null'], 
            $column['Key']
        );
    }
    
    echo "\n✓ Script completado\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    if (isset($db)) {
        echo "Error MySQL: " . $db->error . "\n";
    }
}

echo "</pre>";

if (php_sapi_name() !== 'cli') {
    echo '<br><a href="admin/gastos.php" class="btn btn-primary">Ir a Gastos</a>';
    echo ' <a href="index.php" class="btn btn-secondary">Volver al inicio</a>';
}
?>
