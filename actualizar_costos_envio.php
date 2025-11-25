<?php
require_once 'config/config.php';

echo "=== Actualización de costos de envío ===\n\n";

$db = Database::getInstance()->getConnection();

// Obtener paquetes entregados sin costo_envio
$query = "SELECT id, ciudad, provincia FROM paquetes WHERE estado = 'entregado' AND (costo_envio IS NULL OR costo_envio = 0 OR costo_envio = 0.00)";
$stmt = $db->query($query);

if (!$stmt) {
    die("Error en consulta: " . $db->error . "\n");
}

$paquetes = [];
while ($row = $stmt->fetch_assoc()) {
    $paquetes[] = $row;
}

echo "Paquetes sin costo_envio: " . count($paquetes) . "\n\n";

$actualizados = 0;
foreach ($paquetes as $paquete) {
    $costo = 3.50; // Por defecto
    $zona_encontrada = null;
    
    // Extraer distrito de ciudad (formato: "Departamento - Provincia - Distrito")
    if (!empty($paquete['ciudad'])) {
        $partes = array_map('trim', explode(' - ', $paquete['ciudad']));
        $distrito = trim(end($partes));
        
        $s = $db->prepare("SELECT costo_cliente, nombre_zona FROM zonas_tarifas WHERE UPPER(TRIM(nombre_zona)) = UPPER(TRIM(?)) AND activo = 1 LIMIT 1");
        if ($s) {
            $s->bind_param('s', $distrito);
            $s->execute();
            $r = $s->get_result();
            if ($tarifa = $r->fetch_assoc()) {
                $costo = $tarifa['costo_cliente'];
                $zona_encontrada = $tarifa['nombre_zona'];
            }
        }
    }
    
    // Si no encontró por distrito, buscar por provincia
    if ($costo == 3.50 && !empty($paquete['provincia'])) {
        $s = $db->prepare("SELECT costo_cliente, nombre_zona FROM zonas_tarifas WHERE UPPER(TRIM(nombre_zona)) = UPPER(TRIM(?)) AND activo = 1 LIMIT 1");
        if ($s) {
            $s->bind_param('s', $paquete['provincia']);
            $s->execute();
            $r = $s->get_result();
            if ($tarifa = $r->fetch_assoc()) {
                $costo = $tarifa['costo_cliente'];
                $zona_encontrada = $tarifa['nombre_zona'];
            }
        }
    }
    
    // Actualizar paquete
    $upd = $db->prepare("UPDATE paquetes SET costo_envio = ? WHERE id = ?");
    $upd->bind_param('di', $costo, $paquete['id']);
    $upd->execute();
    
    $actualizados++;
    echo "Paquete #{$paquete['id']}: S/ " . number_format($costo, 2);
    if ($zona_encontrada) {
        echo " (Zona: {$zona_encontrada})";
    } else {
        echo " (Tarifa por defecto)";
    }
    echo "\n";
}

echo "\n✅ Proceso completado: {$actualizados} paquetes actualizados\n";
