<?php
/**
 * Verificación Completa del Chatbot - v2.0
 * Acceso: http://localhost/pruebitaaa/verificar_chatbot.php
 */
require_once 'config/config.php';

$tests_result = [];
$all_ok = true;

// Test 1: Conexión BD
try {
    $db = Database::getInstance()->getConnection();
    if (!$db) {
        $tests_result['conexion'] = ['OK' => false, 'msg' => 'No se pudo obtener conexión'];
        $all_ok = false;
    } else {
        $tests_result['conexion'] = ['OK' => true, 'msg' => 'Conectado a ' . DB_NAME];
    }
} catch (Exception $e) {
    $tests_result['conexion'] = ['OK' => false, 'msg' => 'Exception: ' . $e->getMessage()];
    $all_ok = false;
}

// Test 2: Tablas
if (isset($db) && $db) {
    $tablas = ['paquetes', 'usuarios', 'pagos'];
    $tabla_results = [];
    foreach ($tablas as $tabla) {
        $result = $db->query("SELECT COUNT(*) as cnt FROM $tabla");
        if ($result) {
            $row = $result->fetch_assoc();
            $tabla_results[$tabla] = $row['cnt'];
            $result->close();
        } else {
            $tabla_results[$tabla] = 'ERROR';
        }
    }
    $tests_result['tablas'] = ['OK' => true, 'data' => $tabla_results];
} else {
    $tests_result['tablas'] = ['OK' => false, 'msg' => 'BD no disponible'];
    $all_ok = false;
}

// Test 3: Archivo API
if (file_exists('admin/api_chatbot.php')) {
    $tests_result['api_file'] = ['OK' => true, 'msg' => 'Archivo existe (' . filesize('admin/api_chatbot.php') . ' bytes)'];
} else {
    $tests_result['api_file'] = ['OK' => false, 'msg' => 'Archivo no existe'];
    $all_ok = false;
}

// Test 4: Archivo Frontend
if (file_exists('admin/chatbot.php')) {
    $tests_result['frontend_file'] = ['OK' => true, 'msg' => 'Archivo existe (' . filesize('admin/chatbot.php') . ' bytes)'];
} else {
    $tests_result['frontend_file'] = ['OK' => false, 'msg' => 'Archivo no existe'];
    $all_ok = false;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación Chatbot v2.0</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .card { border: none; box-shadow: 0 8px 16px rgba(0,0,0,0.1); border-radius: 10px; }
        .resultado { font-family: 'Courier New'; font-size: 14px; }
        .estado-ok { color: #28a745; font-weight: bold; }
        .estado-error { color: #dc3545; font-weight: bold; }
        .btn-acceso { margin: 5px; }
    </style>
</head>
<body>
    <div class="container mt-5 mb-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h2>🤖 Verificación del Chatbot v2.0</h2>
                <p class="mb-0">Estado General: <span class="<?= $all_ok ? 'estado-ok' : 'estado-error' ?>">
                    <?= $all_ok ? '✅ LISTO' : '❌ PROBLEMAS' ?>
                </span></p>
            </div>
            <div class="card-body">
                <h5>📋 Pruebas de Sistema</h5>
                <hr>
                
                <!-- Test Conexión BD -->
                <div class="row mb-3">
                    <div class="col-md-2">
                        <span class="<?= $tests_result['conexion']['OK'] ? 'estado-ok' : 'estado-error' ?>">
                            <?= $tests_result['conexion']['OK'] ? '✅' : '❌' ?>
                        </span>
                    </div>
                    <div class="col-md-10">
                        <strong>Conexión Base de Datos</strong>
                        <p class="text-muted resultado"><?= $tests_result['conexion']['msg'] ?></p>
                    </div>
                </div>
                
                <!-- Test Tablas -->
                <?php if ($tests_result['tablas']['OK']): ?>
                <div class="row mb-3">
                    <div class="col-md-2">
                        <span class="estado-ok">✅</span>
                    </div>
                    <div class="col-md-10">
                        <strong>Tablas de Base de Datos</strong>
                        <?php foreach ($tests_result['tablas']['data'] as $tabla => $cnt): ?>
                            <p class="text-muted resultado mb-1">
                                📊 <strong><?= $tabla ?></strong>: 
                                <?= is_numeric($cnt) ? $cnt . ' registros' : '<span class="text-danger">' . $cnt . '</span>' ?>
                            </p>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Test Archivo API -->
                <div class="row mb-3">
                    <div class="col-md-2">
                        <span class="<?= $tests_result['api_file']['OK'] ? 'estado-ok' : 'estado-error' ?>">
                            <?= $tests_result['api_file']['OK'] ? '✅' : '❌' ?>
                        </span>
                    </div>
                    <div class="col-md-10">
                        <strong>Archivo API</strong>
                        <p class="text-muted resultado"><?= $tests_result['api_file']['msg'] ?></p>
                    </div>
                </div>
                
                <!-- Test Archivo Frontend -->
                <div class="row mb-3">
                    <div class="col-md-2">
                        <span class="<?= $tests_result['frontend_file']['OK'] ? 'estado-ok' : 'estado-error' ?>">
                            <?= $tests_result['frontend_file']['OK'] ? '✅' : '❌' ?>
                        </span>
                    </div>
                    <div class="col-md-10">
                        <strong>Archivo Frontend</strong>
                        <p class="text-muted resultado"><?= $tests_result['frontend_file']['msg'] ?></p>
                    </div>
                </div>
                
                <hr>
                
                <!-- Botones de Acceso -->
                <h5>🔗 Acceso Rápido</h5>
                <a href="<?= APP_URL ?>admin/chatbot.php" class="btn btn-primary btn-acceso" target="_blank">
                    💬 Abrir Chatbot
                </a>
                <a href="<?= APP_URL ?>admin/dashboard.php" class="btn btn-secondary btn-acceso" target="_blank">
                    📊 Dashboard Admin
                </a>
                <a href="<?= APP_URL ?>diagnostico_chatbot.php" class="btn btn-info btn-acceso" target="_blank">
                    🔍 Diagnóstico Completo
                </a>
                
            </div>
            <div class="card-footer text-muted">
                <small>
                    Última verificación: <?= date('Y-m-d H:i:s') ?> |
                    Estado: <?= $all_ok ? '🟢 OPERATIVO' : '🔴 REVISAR' ?>
                </small>
            </div>
        </div>
    </div>
</body>
</html>

    echo "<li class='list-group-item'><span class='text-danger'>❌</span> Error BD: " . $e->getMessage() . "</li>";
}

// 3. Verificar autenticación
if (isLoggedIn() && $_SESSION['rol'] === 'admin') {
    echo "<li class='list-group-item'><span class='text-success'>✅</span> Autenticación admin</li>";
} else {
    echo "<li class='list-group-item'><span class='text-warning'>⚠️</span> No logueado como admin</li>";
}

echo "
        </ul>
        
        <h3 class='mt-4'>🚀 Acceso al Chatbot</h3>
        <a href='admin/chatbot.php' class='btn btn-primary btn-lg'>
            <i class='bi bi-chat-dots'></i> Abrir Chatbot
        </a>
        
        <h3 class='mt-4'>📖 Documentación</h3>
        <a href='CHATBOT_PRUEBA.md' class='btn btn-info'>Ver Guía de Prueba</a>
        
        <hr>
        <small class='text-muted'>
            Si algún ✅ muestra ❌, revisa que los archivos existan y estén bien creados.
        </small>
    </div>
</body>
</html>";

?>
