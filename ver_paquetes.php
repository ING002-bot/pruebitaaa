<?php
require_once 'config/config.php';
$db = Database::getInstance()->getConnection();

$stmt = $db->query("SELECT id, codigo_seguimiento, ciudad, provincia, costo_envio FROM paquetes WHERE estado = 'entregado' LIMIT 5");

echo "Paquetes entregados:\n\n";
while ($row = $stmt->fetch_assoc()) {
    echo "ID: {$row['id']}\n";
    echo "Código: {$row['codigo_seguimiento']}\n";
    echo "Ciudad: {$row['ciudad']}\n";
    echo "Provincia: {$row['provincia']}\n";
    echo "Costo actual: S/ " . number_format($row['costo_envio'], 2) . "\n";
    echo "---\n";
}

echo "\nZonas tarifas disponibles:\n\n";
$stmt2 = $db->query("SELECT nombre_zona, costo_cliente FROM zonas_tarifas WHERE activo = 1");
while ($row2 = $stmt2->fetch_assoc()) {
    echo "{$row2['nombre_zona']}: S/ " . number_format($row2['costo_cliente'], 2) . "\n";
}
