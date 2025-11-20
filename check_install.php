/**
 * Verificación de instalación del sistema
 * Este archivo se debe ejecutar una sola vez después de la instalación
 */

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Instalación - HERMES EXPRESS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 50px 0;
        }
        .check-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .check-item {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .check-item.success {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        .check-item.error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .check-item.warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="check-card">
            <h1 class="text-center mb-4">
                <i class="bi bi-gear-fill"></i> Verificación de Instalación
            </h1>
            <h4 class="text-center text-muted mb-5">HERMES EXPRESS LOGISTIC</h4>
            
            <?php
            $checks = [];
            $allOk = true;
            
            // Verificar versión de PHP
            $phpVersion = phpversion();
            $phpOk = version_compare($phpVersion, '7.4.0', '>=');
            $checks[] = [
                'name' => 'Versión de PHP',
                'status' => $phpOk ? 'success' : 'error',
                'message' => $phpOk ? "PHP $phpVersion ✓" : "PHP $phpVersion - Se requiere 7.4 o superior",
                'icon' => $phpOk ? 'check-circle-fill' : 'x-circle-fill'
            ];
            if (!$phpOk) $allOk = false;
            
            // Verificar extensión PDO
            $pdoOk = extension_loaded('pdo') && extension_loaded('pdo_mysql');
            $checks[] = [
                'name' => 'Extensión PDO MySQL',
                'status' => $pdoOk ? 'success' : 'error',
                'message' => $pdoOk ? 'PDO MySQL habilitado ✓' : 'PDO MySQL no está habilitado',
                'icon' => $pdoOk ? 'check-circle-fill' : 'x-circle-fill'
            ];
            if (!$pdoOk) $allOk = false;
            
            // Verificar extensión GD
            $gdOk = extension_loaded('gd');
            $checks[] = [
                'name' => 'Extensión GD',
                'status' => $gdOk ? 'success' : 'warning',
                'message' => $gdOk ? 'GD habilitado ✓' : 'GD no está habilitado (necesario para manipular imágenes)',
                'icon' => $gdOk ? 'check-circle-fill' : 'exclamation-triangle-fill'
            ];
            
            // Verificar conexión a base de datos
            require_once 'config/database.php';
            try {
                $db = Database::getInstance()->getConnection();
                $dbOk = true;
                $checks[] = [
                    'name' => 'Conexión a Base de Datos',
                    'status' => 'success',
                    'message' => 'Conexión exitosa a MySQL ✓',
                    'icon' => 'check-circle-fill'
                ];
            } catch (Exception $e) {
                $dbOk = false;
                $allOk = false;
                $checks[] = [
                    'name' => 'Conexión a Base de Datos',
                    'status' => 'error',
                    'message' => 'Error de conexión: ' . $e->getMessage(),
                    'icon' => 'x-circle-fill'
                ];
            }
            
            // Verificar tablas
            if ($dbOk) {
                try {
                    $tables = ['usuarios', 'paquetes', 'rutas', 'entregas', 'pagos', 'ingresos'];
                    $stmt = $db->query("SHOW TABLES");
                    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    $missingTables = array_diff($tables, $existingTables);
                    
                    if (empty($missingTables)) {
                        $checks[] = [
                            'name' => 'Tablas de Base de Datos',
                            'status' => 'success',
                            'message' => 'Todas las tablas necesarias existen ✓',
                            'icon' => 'check-circle-fill'
                        ];
                    } else {
                        $allOk = false;
                        $checks[] = [
                            'name' => 'Tablas de Base de Datos',
                            'status' => 'error',
                            'message' => 'Faltan tablas: ' . implode(', ', $missingTables),
                            'icon' => 'x-circle-fill'
                        ];
                    }
                } catch (Exception $e) {
                    $checks[] = [
                        'name' => 'Tablas de Base de Datos',
                        'status' => 'error',
                        'message' => 'Error al verificar tablas: ' . $e->getMessage(),
                        'icon' => 'x-circle-fill'
                    ];
                }
            }
            
            // Verificar permisos de carpetas
            $folders = [
                'uploads/entregas',
                'uploads/perfiles',
                'assets/img'
            ];
            
            $foldersOk = true;
            foreach ($folders as $folder) {
                if (!is_dir($folder)) {
                    @mkdir($folder, 0777, true);
                }
                if (!is_writable($folder)) {
                    $foldersOk = false;
                }
            }
            
            $checks[] = [
                'name' => 'Permisos de Carpetas',
                'status' => $foldersOk ? 'success' : 'warning',
                'message' => $foldersOk ? 'Todas las carpetas tienen permisos de escritura ✓' : 'Algunas carpetas no tienen permisos de escritura',
                'icon' => $foldersOk ? 'check-circle-fill' : 'exclamation-triangle-fill'
            ];
            
            // Verificar archivo de configuración
            $configFile = 'config/config.php';
            $configOk = file_exists($configFile);
            $checks[] = [
                'name' => 'Archivo de Configuración',
                'status' => $configOk ? 'success' : 'error',
                'message' => $configOk ? 'config.php existe ✓' : 'config.php no encontrado',
                'icon' => $configOk ? 'check-circle-fill' : 'x-circle-fill'
            ];
            if (!$configOk) $allOk = false;
            
            // Verificar Google Maps API Key
            if ($configOk) {
                require_once $configFile;
                $apiKeyOk = defined('GOOGLE_MAPS_API_KEY') && GOOGLE_MAPS_API_KEY !== 'TU_API_KEY_AQUI';
                $checks[] = [
                    'name' => 'Google Maps API Key',
                    'status' => $apiKeyOk ? 'success' : 'warning',
                    'message' => $apiKeyOk ? 'API Key configurada ✓' : 'API Key no configurada (el mapa no funcionará)',
                    'icon' => $apiKeyOk ? 'check-circle-fill' : 'exclamation-triangle-fill'
                ];
            }
            
            // Mostrar resultados
            foreach ($checks as $check) {
                echo '<div class="check-item ' . $check['status'] . '">';
                echo '<div>';
                echo '<strong>' . $check['name'] . '</strong><br>';
                echo '<small>' . $check['message'] . '</small>';
                echo '</div>';
                echo '<i class="bi bi-' . $check['icon'] . ' fs-3"></i>';
                echo '</div>';
            }
            ?>
            
            <?php if ($allOk): ?>
            <div class="alert alert-success mt-4 text-center">
                <h4><i class="bi bi-check-circle-fill"></i> ¡Instalación Correcta!</h4>
                <p>El sistema está listo para usarse.</p>
                <a href="auth/login.php" class="btn btn-success btn-lg mt-3">
                    <i class="bi bi-box-arrow-in-right"></i> Ir al Sistema
                </a>
            </div>
            <?php else: ?>
            <div class="alert alert-danger mt-4 text-center">
                <h4><i class="bi bi-x-circle-fill"></i> Hay Errores de Configuración</h4>
                <p>Por favor, corrige los errores marcados arriba antes de usar el sistema.</p>
                <a href="INSTALACION.md" class="btn btn-primary mt-3">
                    <i class="bi bi-book"></i> Ver Guía de Instalación
                </a>
            </div>
            <?php endif; ?>
            
            <div class="mt-4 text-center">
                <small class="text-muted">
                    HERMES EXPRESS LOGISTIC © 2025<br>
                    <a href="README.md">Documentación</a> | 
                    <a href="INSTALACION.md">Guía de Instalación</a>
                </small>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
