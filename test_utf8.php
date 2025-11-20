<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test UTF-8</title>
</head>
<body>
    <h1>Test de Codificación UTF-8</h1>
    <?php
    require_once 'config/database.php';
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT id, nombre, zona, ubicaciones FROM rutas ORDER BY id");
    $rutas = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Zona</th><th>Ubicaciones</th></tr>";
    
    foreach ($rutas as $ruta) {
        echo "<tr>";
        echo "<td>{$ruta['id']}</td>";
        echo "<td>{$ruta['nombre']}</td>";
        echo "<td>{$ruta['zona']}</td>";
        echo "<td>{$ruta['ubicaciones']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    ?>
</body>
</html>
