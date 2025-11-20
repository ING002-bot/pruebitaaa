<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test UTF-8 - Rutas</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        h1 { color: #333; }
        table { background: white; border-collapse: collapse; width: 100%; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #007bff; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-primary { background: #007bff; color: white; }
    </style>
</head>
<body>
    <h1>✅ Test de Codificación UTF-8 - Rutas con Zonas</h1>
    <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
    
    <?php
    require_once 'config/database.php';
    
    try {
        $db = Database::getInstance()->getConnection();
        
        // Verificar configuración de charset
        $charset = $db->query("SELECT @@character_set_client, @@character_set_connection, @@character_set_results, @@character_set_database")->fetch();
        
        echo "<h2>Configuración de MySQL:</h2>";
        echo "<table style='width: auto;'>";
        echo "<tr><th>Variable</th><th>Valor</th></tr>";
        echo "<tr><td>character_set_client</td><td>{$charset['@@character_set_client']}</td></tr>";
        echo "<tr><td>character_set_connection</td><td>{$charset['@@character_set_connection']}</td></tr>";
        echo "<tr><td>character_set_results</td><td>{$charset['@@character_set_results']}</td></tr>";
        echo "<tr><td>character_set_database</td><td>{$charset['@@character_set_database']}</td></tr>";
        echo "</table><br>";
        
        $stmt = $db->query("SELECT id, nombre, zona, ubicaciones FROM rutas ORDER BY id");
        $rutas = $stmt->fetchAll();
        
        echo "<h2>Rutas Registradas (" . count($rutas) . "):</h2>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Zona</th><th>Ubicaciones</th></tr>";
        
        foreach ($rutas as $ruta) {
            echo "<tr>";
            echo "<td>{$ruta['id']}</td>";
            echo "<td><strong>{$ruta['nombre']}</strong></td>";
            echo "<td><span class='badge badge-primary'>{$ruta['zona']}</span></td>";
            echo "<td>{$ruta['ubicaciones']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<br><h2>Caracteres Especiales a Verificar:</h2>";
        echo "<ul>";
        echo "<li>✅ Túcume (con tilde en ú)</li>";
        echo "<li>✅ Íllimo (con tilde en í)</li>";
        echo "<li>✅ Púcara (con tilde en ú)</li>";
        echo "<li>✅ Mórrope (con tilde en ó)</li>";
        echo "<li>✅ San José (con tilde en é)</li>";
        echo "<li>✅ Monsefú (con tilde en ú)</li>";
        echo "<li>✅ Tumán (con tilde en á)</li>";
        echo "<li>✅ Pátapo (con tilde en á)</li>";
        echo "<li>✅ Pucalá (con tilde en á)</li>";
        echo "<li>✅ Zaña (con ñ)</li>";
        echo "<li>✅ Cayaltí (con tilde en í)</li>";
        echo "<li>✅ Oyotún (con tilde en ú)</li>";
        echo "<li>✅ Ferreñafe (con ñ)</li>";
        echo "<li>✅ Pítipo (con tilde en í)</li>";
        echo "</ul>";
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
        echo "<strong>Error:</strong> " . $e->getMessage();
        echo "</div>";
    }
    ?>
    
    <br>
    <p><a href="admin/rutas.php" style="display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Ir a Gestión de Rutas</a></p>
</body>
</html>
