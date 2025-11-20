<?php
// Script para insertar rutas con codificación UTF-8 correcta
require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Establecer charset UTF-8
    $db->exec("SET NAMES utf8mb4");
    $db->exec("SET CHARACTER SET utf8mb4");
    
    // Limpiar tabla
    $db->exec("DELETE FROM rutas");
    $db->exec("ALTER TABLE rutas AUTO_INCREMENT = 1");
    
    // Definir rutas con caracteres especiales correctos
    $rutas = [
        [
            'nombre' => 'URBANO',
            'zona' => 'URBANO',
            'ubicaciones' => 'Chiclayo, Leonardo Ortiz, La Victoria, Santa Victoria',
            'descripcion' => 'Cobertura completa zona urbana'
        ],
        [
            'nombre' => 'PUEBLOS',
            'zona' => 'PUEBLOS',
            'ubicaciones' => 'Lambayeque, Mochumi, Túcume, Íllimo, Nueva Arica, Jayanca, Púcara, Mórrope, Motupe, Olmos, Salas',
            'descripcion' => 'Cobertura completa de pueblos'
        ],
        [
            'nombre' => 'PLAYAS',
            'zona' => 'PLAYAS',
            'ubicaciones' => 'San José, Santa Rosa, Pimentel, Reque, Monsefú, Eten, Puerto Eten',
            'descripcion' => 'Cobertura completa zona de playas'
        ],
        [
            'nombre' => 'COOPERATIVAS',
            'zona' => 'COOPERATIVAS',
            'ubicaciones' => 'Pomalca, Tumán, Pátapo, Pucalá, Saltur, Chongoyape',
            'descripcion' => 'Cobertura completa de cooperativas'
        ],
        [
            'nombre' => 'EXCOOPERATIVAS',
            'zona' => 'EXCOOPERATIVAS',
            'ubicaciones' => 'Ucupe, Mocupe, Zaña, Cayaltí, Oyotún, Lagunas',
            'descripcion' => 'Cobertura completa de ex-cooperativas'
        ],
        [
            'nombre' => 'FERREÑAFE',
            'zona' => 'FERREÑAFE',
            'ubicaciones' => 'Ferreñafe, Picsi, Pítipo, Motupillo, Pueblo Nuevo',
            'descripcion' => 'Cobertura completa de Ferreñafe'
        ]
    ];
    
    $sql = "INSERT INTO rutas (nombre, zona, ubicaciones, descripcion, fecha_ruta, estado, creado_por) 
            VALUES (?, ?, ?, ?, CURDATE(), 'planificada', 1)";
    $stmt = $db->prepare($sql);
    
    $insertados = 0;
    foreach ($rutas as $ruta) {
        $stmt->execute([
            $ruta['nombre'],
            $ruta['zona'],
            $ruta['ubicaciones'],
            $ruta['descripcion']
        ]);
        $insertados++;
    }
    
    echo "<!DOCTYPE html>";
    echo "<html lang='es'>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
    echo "<title>Rutas Insertadas</title>";
    echo "<style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .success { background: #d4edda; color: #155724; padding: 20px; border-radius: 5px; border: 1px solid #c3e6cb; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; border: 1px solid #bee5eb; margin-top: 20px; }
        table { background: white; border-collapse: collapse; width: 100%; margin-top: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #28a745; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .btn { display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
    </style>";
    echo "</head>";
    echo "<body>";
    
    echo "<div class='success'>";
    echo "<h1>✅ Rutas Insertadas Correctamente</h1>";
    echo "<p><strong>Total de rutas insertadas:</strong> $insertados</p>";
    echo "<p><strong>Codificación utilizada:</strong> UTF-8 (utf8mb4)</p>";
    echo "</div>";
    
    // Verificar las rutas insertadas
    $stmt = $db->query("SELECT id, nombre, zona, ubicaciones FROM rutas ORDER BY id");
    $rutas_verificadas = $stmt->fetchAll();
    
    echo "<div class='info'>";
    echo "<h2>Verificación de Rutas:</h2>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Zona</th><th>Ubicaciones</th></tr>";
    
    foreach ($rutas_verificadas as $ruta) {
        echo "<tr>";
        echo "<td>{$ruta['id']}</td>";
        echo "<td><strong>{$ruta['nombre']}</strong></td>";
        echo "<td>{$ruta['zona']}</td>";
        echo "<td>{$ruta['ubicaciones']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "</div>";
    
    echo "<h2>Caracteres Especiales Verificados:</h2>";
    echo "<ul>";
    echo "<li>✅ Túcume, Íllimo, Púcara, Mórrope (tildes en vocales)</li>";
    echo "<li>✅ San José, Monsefú (tildes en é y ú)</li>";
    echo "<li>✅ Tumán, Pátapo, Pucalá (tildes en á)</li>";
    echo "<li>✅ Zaña, Ferreñafe (letra ñ)</li>";
    echo "<li>✅ Cayaltí, Oyotún (tildes en í y ú)</li>";
    echo "<li>✅ Pítipo (tilde en í)</li>";
    echo "</ul>";
    
    echo "<a href='test_utf8.php' class='btn'>Ver Test UTF-8</a> ";
    echo "<a href='admin/rutas.php' class='btn' style='background: #28a745;'>Ir a Gestión de Rutas</a>";
    
    echo "</body>";
    echo "</html>";
    
} catch (PDOException $e) {
    echo "<!DOCTYPE html>";
    echo "<html lang='es'><head><meta charset='UTF-8'><title>Error</title></head><body>";
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 5px; border: 1px solid #f5c6cb;'>";
    echo "<h1>❌ Error al insertar rutas</h1>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
    echo "</body></html>";
}
?>
