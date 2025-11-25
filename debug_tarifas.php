<?php
require_once 'config/config.php';
$db = Database::getInstance()->getConnection();

echo "=== DIAGNÓSTICO DE TARIFAS ===\n\n";

// Ver todas las tarifas
echo "TARIFAS REGISTRADAS:\n";
$stmt = $db->query("SELECT * FROM zonas_tarifas WHERE activo = 1");
if ($stmt) {
    while ($row = $stmt->fetch_assoc()) {
        echo "- Zona: '{$row['nombre_zona']}' | Cliente: S/ {$row['costo_cliente']} | Repartidor: S/ {$row['tarifa_repartidor']}\n";
    }
} else {
    echo "ERROR: " . $db->error . "\n";
}

echo "\n=== PAQUETES ENTREGADOS ===\n";
$stmt2 = $db->query("SELECT id, codigo_seguimiento, ciudad, provincia, costo_envio FROM paquetes WHERE estado = 'entregado' ORDER BY id DESC LIMIT 5");
if ($stmt2) {
    while ($row2 = $stmt2->fetch_assoc()) {
        echo "\nPaquete #{$row2['id']} - {$row2['codigo_seguimiento']}\n";
        echo "  Ciudad: {$row2['ciudad']}\n";
        echo "  Provincia: {$row2['provincia']}\n";
        echo "  Costo: S/ {$row2['costo_envio']}\n";
        
        // Extraer distrito
        if (!empty($row2['ciudad'])) {
            $partes = array_map('trim', explode(' - ', $row2['ciudad']));
            $distrito = trim(end($partes));
            echo "  Distrito extraído: '{$distrito}'\n";
            
            // Buscar tarifa
            $s = $db->prepare("SELECT nombre_zona, costo_cliente FROM zonas_tarifas WHERE UPPER(TRIM(nombre_zona)) = UPPER(TRIM(?)) AND activo = 1");
            if ($s) {
                $s->bind_param('s', $distrito);
                $s->execute();
                $r = $s->get_result();
                if ($tarifa = $r->fetch_assoc()) {
                    echo "  ✅ TARIFA ENCONTRADA: {$tarifa['nombre_zona']} = S/ {$tarifa['costo_cliente']}\n";
                } else {
                    echo "  ❌ NO SE ENCONTRÓ TARIFA PARA: '{$distrito}'\n";
                }
            }
        }
    }
}
