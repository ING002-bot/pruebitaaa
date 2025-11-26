<?php
require_once 'config/config.php';

$db = Database::getInstance()->getConnection();

// Verificar si la columna ya existe
$check = $db->query("SHOW COLUMNS FROM paquetes LIKE 'distrito'");
if ($check->num_rows > 0) {
    echo "La columna 'distrito' ya existe en la tabla paquetes.\n";
    exit;
}

// Agregar la columna distrito
$sql = "ALTER TABLE paquetes ADD COLUMN distrito VARCHAR(100) AFTER provincia";

if ($db->query($sql)) {
    echo "✓ Columna 'distrito' agregada exitosamente a la tabla paquetes.\n";
} else {
    echo "✗ Error al agregar columna: " . $db->error . "\n";
}

$db->close();
?>
