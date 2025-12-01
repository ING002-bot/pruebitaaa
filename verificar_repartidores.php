<?php
require_once 'config/config.php';

$db = Database::getInstance()->getConnection();

echo "🔍 VERIFICANDO REPARTIDORES EN LA BASE DE DATOS\n\n";

// Verificar todos los usuarios
$usuarios = $db->query("SELECT id, nombre, apellido, rol, estado FROM usuarios ORDER BY rol, nombre");
echo "📊 TODOS LOS USUARIOS:\n";
echo str_repeat("-", 60) . "\n";
printf("%-4s | %-20s | %-12s | %-8s\n", "ID", "NOMBRE", "ROL", "ESTADO");
echo str_repeat("-", 60) . "\n";

$repartidores_count = 0;
while ($user = $usuarios->fetch_assoc()) {
    printf("%-4d | %-20s | %-12s | %-8s\n", 
        $user['id'],
        $user['nombre'] . ' ' . $user['apellido'],
        $user['rol'],
        $user['estado']
    );
    
    if ($user['rol'] === 'repartidor' && $user['estado'] === 'activo') {
        $repartidores_count++;
    }
}

echo str_repeat("-", 60) . "\n";
echo "✅ Total repartidores activos: $repartidores_count\n\n";

// Si no hay repartidores, crear algunos de prueba
if ($repartidores_count === 0) {
    echo "⚠️ No hay repartidores activos. Creando repartidores de prueba...\n\n";
    
    $repartidores_prueba = [
        ['Carlos', 'Rodriguez', '+51912345678'],
        ['Maria', 'Lopez', '+51987654321'],
        ['Pedro', 'Garcia', '+51956789123']
    ];
    
    $stmt = $db->prepare("INSERT INTO usuarios (nombre, apellido, telefono, email, password, rol, estado) VALUES (?, ?, ?, ?, ?, 'repartidor', 'activo')");
    
    foreach ($repartidores_prueba as $rep) {
        $email = strtolower($rep[0] . '.' . $rep[1]) . '@hermes.com';
        $password = password_hash('123456', PASSWORD_DEFAULT);
        
        $stmt->bind_param("sssss", $rep[0], $rep[1], $rep[2], $email, $password);
        
        if ($stmt->execute()) {
            echo "✅ Repartidor creado: {$rep[0]} {$rep[1]}\n";
        }
    }
    
    echo "\n🎉 Repartidores de prueba creados exitosamente!\n";
}

// Verificar nuevamente después de crear
$repartidores_finales = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'repartidor' AND estado = 'activo'");
$total_final = $repartidores_finales->fetch_assoc()['total'];

echo "📈 TOTAL FINAL DE REPARTIDORES ACTIVOS: $total_final\n";
?>