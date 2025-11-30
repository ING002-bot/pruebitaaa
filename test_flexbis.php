<?php
/**
 * HERMES EXPRESS - Test Flexbis WhatsApp API
 * 
 * Interfaz de pruebas para validar la integración con Flexbis WhatsApp API
 * Solo accesible para administradores
 */

session_start();
require_once 'config/config.php';

// Verificar acceso de administrador
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: admin/login.php');
    exit;
}

// Procesamiento de formulario
$resultado = null;
$test_realizado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $test_realizado = true;
    
    try {
        switch ($accion) {
            case 'verificar_config':
                $resultado = verificarConfiguracion();
                break;
                
            case 'test_auth':
                $resultado = testearAutenticacion();
                break;
                
            case 'enviar_mensaje':
                $telefono = $_POST['telefono'] ?? '';
                $mensaje = $_POST['mensaje'] ?? '';
                
                if (empty($telefono) || empty($mensaje)) {
                    $resultado = ['success' => false, 'error' => 'Teléfono y mensaje son requeridos'];
                } else {
                    $resultado = enviarMensajePrueba($telefono, $mensaje);
                }
                break;
                
            default:
                $resultado = ['success' => false, 'error' => 'Acción no válida'];
        }
    } catch (Exception $e) {
        $resultado = [
            'success' => false,
            'error' => 'Exception: ' . $e->getMessage()
        ];
    }
}

function verificarConfiguracion() {
    $config = [
        'FLEXBIS_API_SID' => defined('FLEXBIS_API_SID') ? (empty(FLEXBIS_API_SID) ? 'NO_SET' : 'CONFIGURED') : 'NOT_DEFINED',
        'FLEXBIS_API_KEY' => defined('FLEXBIS_API_KEY') ? (empty(FLEXBIS_API_KEY) ? 'NO_SET' : 'CONFIGURED') : 'NOT_DEFINED',
        'FLEXBIS_API_URL' => defined('FLEXBIS_API_URL') ? FLEXBIS_API_URL : 'NOT_DEFINED',
        'FLEXBIS_WHATSAPP_FROM' => defined('FLEXBIS_WHATSAPP_FROM') ? (empty(FLEXBIS_WHATSAPP_FROM) ? 'NO_SET' : FLEXBIS_WHATSAPP_FROM) : 'NOT_DEFINED',
        'WHATSAPP_API_TYPE' => defined('WHATSAPP_API_TYPE') ? WHATSAPP_API_TYPE : 'NOT_DEFINED'
    ];
    
    $todo_ok = true;
    $errores = [];
    
    if ($config['FLEXBIS_API_SID'] !== 'CONFIGURED') {
        $todo_ok = false;
        $errores[] = 'FLEXBIS_API_SID no está configurado';
    }
    
    if ($config['FLEXBIS_API_KEY'] !== 'CONFIGURED') {
        $todo_ok = false;
        $errores[] = 'FLEXBIS_API_KEY no está configurado';
    }
    
    if (empty($config['FLEXBIS_WHATSAPP_FROM'])) {
        $todo_ok = false;
        $errores[] = 'FLEXBIS_WHATSAPP_FROM no está configurado';
    }
    
    return [
        'success' => $todo_ok,
        'config' => $config,
        'errores' => $errores,
        'php_curl' => extension_loaded('curl'),
        'php_json' => extension_loaded('json')
    ];
}

function testearAutenticacion() {
    if (empty(FLEXBIS_API_SID) || empty(FLEXBIS_API_KEY)) {
        return [
            'success' => false,
            'error' => 'Credenciales no configuradas'
        ];
    }
    
    // Intentar diferentes endpoints comunes para test
    $test_endpoints = [
        '/status',
        '/auth/test', 
        '/account/balance',
        '/me',
        '/health'
    ];
    
    $base_url = rtrim(FLEXBIS_API_URL, '/');
    $last_error = '';
    
    foreach ($test_endpoints as $endpoint) {
        $url = $base_url . $endpoint;
        
        $headers = [
            'Authorization: Bearer ' . FLEXBIS_API_KEY,
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: HermesExpress/1.0'
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            $last_error = "cURL Error en $endpoint: " . $curl_error;
            continue;
        }
        
        // Si responde 200-299, es exitoso
        if ($http_code >= 200 && $http_code < 300) {
            $response_data = json_decode($response, true);
            return [
                'success' => true,
                'http_code' => $http_code,
                'endpoint' => $endpoint,
                'response' => $response_data,
                'message' => "Conexión exitosa en endpoint: $endpoint"
            ];
        }
        
        // Si es 401, credenciales incorrectas
        if ($http_code === 401) {
            return [
                'success' => false,
                'error' => 'Credenciales inválidas (HTTP 401)',
                'http_code' => $http_code,
                'endpoint' => $endpoint,
                'raw_response' => $response
            ];
        }
        
        $last_error = "HTTP $http_code en $endpoint: $response";
    }
    
    return [
        'success' => false,
        'error' => $last_error ?: 'No se pudo conectar a ningún endpoint',
        'endpoints_tested' => $test_endpoints
    ];
}

function enviarMensajePrueba($telefono, $mensaje) {
    try {
        require_once 'config/flexbis_client.php';
        
        // Crear instancia del cliente FlexBis
        $flexbis = new FlexBisClient();
        
        // Verificar configuración
        if (!$flexbis->isConfigured()) {
            return [
                'success' => false,
                'error' => 'FlexBis no está configurado correctamente',
                'missing' => implode(', ', $flexbis->getConfig()['missing'] ?? ['credenciales'])
            ];
        }
        
        // Preparar mensaje de prueba
        $mensaje_completo = "🧪 [PRUEBA FLEXBIS]\n\n" . $mensaje . "\n\n⚡ Hermes Express\n📅 " . date('d/m/Y H:i:s');
        
        // Enviar mensaje usando FlexBis directamente
        $resultado = $flexbis->sendMessage($telefono, $mensaje_completo);
        
        if ($resultado['success']) {
            return [
                'success' => true,
                'message_id' => $resultado['message_id'],
                'status' => $resultado['status'],
                'telefono' => $telefono,
                'mensaje' => $mensaje_completo,
                'metodo' => 'FlexBisClient->sendMessage',
                'data' => $resultado['data'] ?? []
            ];
        } else {
            return [
                'success' => false,
                'error' => $resultado['error'],
                'telefono' => $telefono,
                'mensaje' => $mensaje_completo,
                'metodo' => 'FlexBisClient->sendMessage',
                'data' => $resultado['data'] ?? []
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Exception: ' . $e->getMessage()
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Flexbis WhatsApp API - HERMES EXPRESS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .bg-gradient-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
        .btn-outline-success:hover { color: #fff; }
        .config-item { padding: 0.5rem; border-left: 3px solid #17a2b8; background: #f8f9fa; }
        .config-ok { border-left-color: #28a745; }
        .config-error { border-left-color: #dc3545; }
        .response-json { background: #2d3748; color: #e2e8f0; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; }
    </style>
</head>
<body class="bg-light">
    
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-gradient-primary text-white mb-4">
        <div class="container">
            <span class="navbar-brand mb-0 h1 text-white">
                <i class="fab fa-whatsapp me-2"></i>
                Test Flexbis WhatsApp API
            </span>
            <a href="admin/dashboard.php" class="btn btn-outline-light btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver al Dashboard
            </a>
        </div>
    </nav>

    <div class="container">
        
        <!-- Alertas de resultado -->
        <?php if ($test_realizado && $resultado): ?>
            <div class="alert <?= $resultado['success'] ? 'alert-success' : 'alert-danger' ?> alert-dismissible fade show">
                <i class="fas <?= $resultado['success'] ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> me-2"></i>
                <strong><?= $resultado['success'] ? 'Éxito:' : 'Error:' ?></strong>
                <?= $resultado['success'] ? 'Operación completada correctamente' : ($resultado['error'] ?? 'Error desconocido') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            
            <!-- Detalles de la respuesta -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-1"></i> Detalles de la Respuesta</h6>
                </div>
                <div class="card-body">
                    <pre class="response-json"><code><?= json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></code></pre>
                </div>
            </div>
        <?php endif; ?>

        <div class="row">
            
            <!-- Panel de Configuración -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Verificar Configuración</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Verifica que las credenciales de Flexbis estén correctamente configuradas.</p>
                        
                        <form method="POST">
                            <input type="hidden" name="accion" value="verificar_config">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i> Verificar Configuración
                            </button>
                        </form>
                        
                        <hr>
                        
                        <div class="small text-muted">
                            <strong>Configuración actual:</strong><br>
                            API Type: <code><?= defined('WHATSAPP_API_TYPE') ? WHATSAPP_API_TYPE : 'NO DEFINIDO' ?></code><br>
                            API URL: <code><?= defined('FLEXBIS_API_URL') ? FLEXBIS_API_URL : 'NO DEFINIDO' ?></code>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel de Test de Autenticación -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-key me-2"></i>Test de Autenticación</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Prueba la conectividad y autenticación con los servidores de Flexbis.</p>
                        
                        <form method="POST">
                            <input type="hidden" name="accion" value="test_auth">
                            <button type="submit" class="btn btn-info w-100">
                                <i class="fas fa-plug me-1"></i> Probar Conexión
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>

        <!-- Panel de Envío de Mensaje -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fab fa-whatsapp me-2"></i>Enviar Mensaje de Prueba</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Atención:</strong> Este envío consumirá créditos reales de tu cuenta Flexbis. Úsalo solo para pruebas necesarias.
                </div>
                
                <form method="POST">
                    <input type="hidden" name="accion" value="enviar_mensaje">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="telefono" class="form-label">Número de Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono" 
                                   placeholder="+51987654321" required>
                            <small class="text-muted">Incluye el código de país (+51 para Perú)</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="mensaje" class="form-label">Mensaje</label>
                            <textarea class="form-control" id="mensaje" name="mensaje" rows="3" 
                                      placeholder="Escribe tu mensaje de prueba aquí..." required></textarea>
                            <small class="text-muted">Se agregará automáticamente un prefijo [PRUEBA FLEXBIS]</small>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success">
                        <i class="fab fa-whatsapp me-1"></i> Enviar Mensaje de Prueba
                    </button>
                </form>
            </div>
        </div>

        <!-- Información del Sistema -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-server me-1"></i> Información del Sistema</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="config-item mb-2 <?= extension_loaded('curl') ? 'config-ok' : 'config-error' ?>">
                            <strong>cURL:</strong> <?= extension_loaded('curl') ? '✅ Habilitado' : '❌ No disponible' ?>
                        </div>
                        
                        <div class="config-item mb-2 <?= extension_loaded('json') ? 'config-ok' : 'config-error' ?>">
                            <strong>JSON:</strong> <?= extension_loaded('json') ? '✅ Habilitado' : '❌ No disponible' ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="config-item mb-2">
                            <strong>PHP Version:</strong> <?= PHP_VERSION ?>
                        </div>
                        
                        <div class="config-item mb-2">
                            <strong>Timestamp:</strong> <?= date('Y-m-d H:i:s') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>