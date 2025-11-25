<?php
require_once 'config/config.php';
$db = Database::getInstance()->getConnection();

// Actualizar paquete 1 (TUMAN) = S/ 5.00
$db->query("UPDATE paquetes SET costo_envio = 5.00 WHERE id = 1");

// Actualizar paquete 2 (LAMBAYEQUE) = S/ 3.00  
$db->query("UPDATE paquetes SET costo_envio = 3.00 WHERE id = 2");

echo "✅ Paquetes actualizados:\n";
echo "- Paquete #1 (TUMAN): S/ 5.00\n";
echo "- Paquete #2 (LAMBAYEQUE): S/ 3.00\n";
