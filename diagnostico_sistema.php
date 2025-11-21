<?php
/**
 * Archivo de diagnóstico del sistema
 * Verifica compatibilidad y configuración de PHP/MySQLi
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico del Sistema - HERMES EXPRESS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        h2 {
            color: #667eea;
            margin-top: 30px;
        }
        .status-ok {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin: 5px 0;
            border-left: 4px solid #28a745;
        }
        .status-error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin: 5px 0;
            border-left: 4px solid #dc3545;
        }
        .status-warning {
            background: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 4px;
            margin: 5px 0;
            border-left: 4px solid #ffc107;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
        }
        tr:hover {
            background: #f9f9f9;
        }
        .code {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            margin: 10px 0;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico del Sistema - HERMES EXPRESS</h1>
        <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>

        <h2>📋 Información de PHP</h2>
        <table>
            <tr>
                <th>Parámetro</th>
                <th>Valor</th>
            </tr>
            <tr>
                <td>Versión PHP</td>
                <td><?php echo phpversion(); ?></td>
            </tr>
            <tr>
                <td>Sistema Operativo</td>
                <td><?php echo php_uname(); ?></td>
            </tr>
            <tr>
                <td>Servidor Web</td>
                <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'No disponible'; ?></td>
            </tr>
            <tr>
                <td>SAPI</td>
                <td><?php echo php_sapi_name(); ?></td>
            </tr>
        </table>

        <h2>🔌 Extensiones de Base de Datos</h2>
        <?php
        $extensions = ['mysqli', 'mysql', 'pdo', 'pdo_mysql'];
        foreach ($extensions as $ext) {
            if (extension_loaded($ext)) {
                echo "<div class='status-ok'><strong>✓ {$ext}</strong> está instalado y habilitado</div>";
            } else {
                echo "<div class='status-error'><strong>✗ {$ext}</strong> NO está disponible</div>";
            }
        }
        ?>

        <h2>📊 Información de MySQLi</h2>
        <?php
        if (extension_loaded('mysqli')) {
            echo "<div class='status-ok'><strong>✓ MySQLi disponible</strong></div>";
            
            // Versión de MySQLi
            $mysqli_version = defined('MYSQLI_VERSION') ? MYSQLI_VERSION : 'Desconocida';
            echo "<p><strong>Versión MySQLi:</strong> {$mysqli_version}</p>";
            
            // Verificar que la clase existe
            if (class_exists('mysqli')) {
                echo "<div class='status-ok'><strong>✓ Clase mysqli</strong> disponible</div>";
            } else {
                echo "<div class='status-error'><strong>✗ Clase mysqli</strong> NO disponible</div>";
            }
        } else {
            echo "<div class='status-error'><strong>✗ MySQLi NO está disponible</strong></div>";
        }
        ?>

        <h2>🧪 Prueba de Conexión a Base de Datos</h2>
        <?php
        require_once 'config/database.php';
        
        try {
            $db = Database::getInstance()->getConnection();
            
            if ($db) {
                echo "<div class='status-ok'><strong>✓ Conexión exitosa a la base de datos</strong></div>";
                
                // Información de servidor MySQL
                $info = $db->get_server_info();
                echo "<p><strong>Versión MySQL:</strong> {$info}</p>";
                
                // Tabla de variables importantes
                $result = $db->query("SELECT @@version as version, @@character_set_database as charset, @@sql_mode as sql_mode LIMIT 1");
                if ($result) {
                    $info = $result->fetch_assoc();
                    echo "<table>";
                    echo "<tr><th>Variable</th><th>Valor</th></tr>";
                    echo "<tr><td>@@version</td><td>{$info['version']}</td></tr>";
                    echo "<tr><td>@@character_set_database</td><td>{$info['charset']}</td></tr>";
                    echo "</table>";
                }
                
                // Verificar tablas existentes
                $result = $db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'hermes_express'");
                if ($result) {
                    $tables = [];
                    while ($row = $result->fetch_assoc()) {
                        $tables[] = $row['TABLE_NAME'];
                    }
                    echo "<p><strong>Tablas encontradas (" . count($tables) . "):</strong></p>";
                    echo "<div class='code'>" . implode(", ", $tables) . "</div>";
                } else {
                    echo "<div class='status-warning'><strong>⚠ No se pudo listar las tablas</strong></div>";
                }
                
            } else {
                echo "<div class='status-error'><strong>✗ Error de conexión</strong></div>";
                echo "<p>" . Database::getInstance()->getConnection()->connect_error . "</p>";
            }
        } catch (Exception $e) {
            echo "<div class='status-error'><strong>✗ Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        ?>

        <h2>✅ Métodos MySQLi Disponibles</h2>
        <?php
        if (extension_loaded('mysqli')) {
            $methods = [
                'fetch_assoc' => 'Obtener fila como array asociativo',
                'fetch_array' => 'Obtener fila como array',
                'fetch_object' => 'Obtener fila como objeto',
                'fetch_row' => 'Obtener fila como array numérico',
                'fetch_column' => 'Obtener valor de una columna',
                'fetchAll' => 'Obtener todas las filas (PDO)',
                'fetch' => 'Obtener una fila (PDO)'
            ];
            
            $reflection = new ReflectionClass('mysqli_result');
            $methods_available = array_map(function($m) { return $m->getName(); }, $reflection->getMethods());
            
            echo "<table>";
            echo "<tr><th>Método</th><th>Estado</th><th>Descripción</th></tr>";
            
            foreach ($methods as $method => $desc) {
                if (in_array($method, $methods_available)) {
                    echo "<tr>";
                    echo "<td><code>$method</code></td>";
                    echo "<td><span class='status-ok' style='display:inline-block; padding: 5px 10px; width: auto;'>✓ Disponible</span></td>";
                    echo "<td>$desc</td>";
                    echo "</tr>";
                } else {
                    echo "<tr>";
                    echo "<td><code>$method</code></td>";
                    echo "<td><span class='status-error' style='display:inline-block; padding: 5px 10px; width: auto;'>✗ NO disponible</span></td>";
                    echo "<td>$desc</td>";
                    echo "</tr>";
                }
            }
            echo "</table>";
        }
        ?>

        <h2>💾 Tamaño Base de Datos</h2>
        <?php
        try {
            $db = Database::getInstance()->getConnection();
            $result = $db->query("
                SELECT 
                    SUM(data_length + index_length) as size
                FROM information_schema.TABLES 
                WHERE table_schema = 'hermes_express'
            ");
            
            if ($result) {
                $data = $result->fetch_assoc();
                $size_mb = round($data['size'] / 1024 / 1024, 2);
                echo "<p><strong>Tamaño total:</strong> {$size_mb} MB</p>";
            }
        } catch (Exception $e) {
            echo "<div class='status-warning'>⚠ No se pudo calcular el tamaño</div>";
        }
        ?>

        <h2>🔐 Recomendaciones</h2>
        <div style="background: #e7f3ff; padding: 15px; border-radius: 4px; border-left: 4px solid #667eea;">
            <ul>
                <li>Asegúrate de que <strong>MySQLi esté habilitado</strong> en php.ini</li>
                <li>Verifica que la <strong>versión de PHP sea 7.0 o superior</strong></li>
                <li>Confirma que el <strong>usuario root de MySQL tiene acceso</strong> a la base de datos</li>
                <li>Si ves errores de conexión, revisa que <strong>MySQL esté ejecutándose</strong></li>
            </ul>
        </div>

        <hr>
        <p style="text-align: center; color: #666; margin-top: 30px;">
            <small>Diagnóstico generado el <?php echo date('d/m/Y H:i:s'); ?></small>
        </p>
    </div>
</body>
</html>
