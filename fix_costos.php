<?php
require_once 'config/config.php';
$db = Database::getInstance()->getConnection();

echo "=== ACTUALIZACIÓN DE COSTOS CORRECTA ===\n\n";

// Actualizar paquetes entregados
$paquetes = [
    ['id' => 1, 'zona' => 'TUMAN', 'costo' => 5.00],
    ['id' => 2, 'zona' => 'LAMBAYEQUE', 'costo' => 3.00]
];

foreach ($paquetes as $p) {
    $db->query("UPDATE paquetes SET costo_envio = {$p['costo']} WHERE id = {$p['id']}");
    echo "✅ Paquete #{$p['id']} ({$p['zona']}): S/ " . number_format($p['costo'], 2) . "\n";
}

echo "\n✅ Actualización completada\n";
