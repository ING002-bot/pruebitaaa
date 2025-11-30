<?php
/**
 * CONFIGURADOR DE FLEXBIS API
 * Interfaz administrativa para configurar credenciales de FlexBis
 */

session_start();
require_once 'config/config.php';

// Verificar acceso de administrador
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: admin/login.php');
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

// Procesamiento de formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'actualizar_config') {
        $api_type = trim($_POST['api_type'] ?? 'simulado');
        $api_sid = trim($_POST['api_sid'] ?? '');
        $api_key = trim($_POST['api_key'] ?? '');
        $api_url = trim($_POST['api_url'] ?? 'https://api.flexbis.com/v1/');
        $whatsapp_from = trim($_POST['whatsapp_from'] ?? '');
        
        // Validar datos
        $errores = [];
        
        if ($api_type === 'flexbis') {
            if (empty($api_sid)) $errores[] = 'SID de API es requerido';
            if (empty($api_key)) $errores[] = 'Key de API es requerida';
            if (empty($whatsapp_from)) $errores[] = 'Número de WhatsApp es requerido';
        }
        
        if (!filter_var($api_url, FILTER_VALIDATE_URL)) {
            $errores[] = 'URL de API inválida';
        }
        
        if ($whatsapp_from && !preg_match('/^\+[0-9]{10,15}$/', $whatsapp_from)) {
            $errores[] = 'Formato de número de WhatsApp inválido (debe incluir código de país)';
        }
        
        if (empty($errores)) {
            // Crear archivo .env
            $env_content = "# Configuración FlexBis - Generado automáticamente\n";
            $env_content .= "WHATSAPP_API_TYPE=" . $api_type . "\n";
            $env_content .= "FLEXBIS_API_SID=" . $api_sid . "\n";
            $env_content .= "FLEXBIS_API_KEY=" . $api_key . "\n";
            $env_content .= "FLEXBIS_API_URL=" . $api_url . "\n";
            $env_content .= "FLEXBIS_WHATSAPP_FROM=" . $whatsapp_from . "\n";
            $env_content .= "\n# Base de datos\n";
            $env_content .= "DB_HOST=localhost\n";
            $env_content .= "DB_USER=root\n";
            $env_content .= "DB_PASSWORD=\n";
            $env_content .= "DB_NAME=pruebitaaa\n";
            
            $env_path = __DIR__ . '/.env';
            
            if (file_put_contents($env_path, $env_content)) {
                // También actualizar las constantes en tiempo de ejecución
                if (!defined('WHATSAPP_API_TYPE') || constant('WHATSAPP_API_TYPE') !== $api_type) {
                    // Las constantes no se pueden redefinir, así que solo mostramos mensaje de éxito
                }
                
                $mensaje = 'Configuración guardada correctamente. Los cambios se aplicarán en la próxima carga de página.';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error al guardar la configuración. Verifica los permisos de escritura.';
                $tipo_mensaje = 'danger';
            }
        } else {
            $mensaje = 'Errores encontrados: ' . implode(', ', $errores);
            $tipo_mensaje = 'danger';
        }
    }
    
    if ($accion === 'test_conexion') {
        // Test básico de conectividad
        $resultado = testConexionFlexbis();
        $mensaje = $resultado['success'] ? 
            'Conexión exitosa: ' . $resultado['message'] : 
            'Error de conexión: ' . $resultado['error'];
        $tipo_mensaje = $resultado['success'] ? 'success' : 'danger';
    }
}

function testConexionFlexbis() {
    $api_url = defined('FLEXBIS_API_URL') ? FLEXBIS_API_URL : 'https://api.flexbis.com/v1/';
    $api_key = defined('FLEXBIS_API_KEY') ? FLEXBIS_API_KEY : '';
    
    if (empty($api_key)) {
        return ['success' => false, 'error' => 'API Key no configurada'];
    }
    
    $test_url = rtrim($api_url, '/') . '/status';
    
    $ch = curl_init($test_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => 'cURL Error: ' . $error];
    }
    
    if ($http_code >= 200 && $http_code < 300) {
        return ['success' => true, 'message' => 'API responde correctamente (HTTP ' . $http_code . ')'];
    } else {
        return ['success' => false, 'error' => 'HTTP Error ' . $http_code . ': ' . $response];
    }
}

// Leer configuración actual
$current_config = [
    'api_type' => defined('WHATSAPP_API_TYPE') ? WHATSAPP_API_TYPE : 'simulado',
    'api_sid' => defined('FLEXBIS_API_SID') ? FLEXBIS_API_SID : '',
    'api_key' => defined('FLEXBIS_API_KEY') ? FLEXBIS_API_KEY : '',
    'api_url' => defined('FLEXBIS_API_URL') ? FLEXBIS_API_URL : 'https://api.flexbis.com/v1/',
    'whatsapp_from' => defined('FLEXBIS_WHATSAPP_FROM') ? FLEXBIS_WHATSAPP_FROM : ''
];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar FlexBis API - HERMES EXPRESS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .bg-gradient-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
        .config-status { padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; }
        .status-ok { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .status-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .credential-input { position: relative; }
        .credential-input .toggle-visibility { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; }
    </style>
</head>
<body class="bg-light">
    
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-gradient-primary text-white mb-4">
        <div class="container">
            <span class="navbar-brand mb-0 h1 text-white">
                <i class="fas fa-cog me-2"></i>
                Configurar FlexBis API
            </span>
            <div>
                <a href="test_flexbis.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="fas fa-flask me-1"></i> Probar API
                </a>
                <a href="admin/dashboard.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        
        <!-- Mensajes -->
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show">
                <i class="fas <?= $tipo_mensaje === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> me-2"></i>
                <?= htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estado Actual -->
        <div class="row mb-4">
            <div class="col-md-12">
                <?php
                $api_type = $current_config['api_type'];
                $is_configured = !empty($current_config['api_sid']) && !empty($current_config['api_key']);
                
                if ($api_type === 'simulado'): ?>
                    <div class="config-status status-warning">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Modo Simulación Activo</h5>
                        <p class="mb-0">Los mensajes de WhatsApp se simularán. No se enviarán mensajes reales.</p>
                    </div>
                <?php elseif ($api_type === 'flexbis' && $is_configured): ?>
                    <div class="config-status status-ok">
                        <h5><i class="fas fa-check-circle me-2"></i>FlexBis Configurado</h5>
                        <p class="mb-0">API configurada para enviar mensajes reales a través de FlexBis.</p>
                    </div>
                <?php else: ?>
                    <div class="config-status status-error">
                        <h5><i class="fas fa-times-circle me-2"></i>Configuración Incompleta</h5>
                        <p class="mb-0">FlexBis está seleccionado pero faltan credenciales.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Formulario de Configuración -->
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fab fa-whatsapp me-2"></i>Configuración de WhatsApp API</h5>
                    </div>
                    <div class="card-body">
                        
                        <form method="POST">
                            <input type="hidden" name="accion" value="actualizar_config">
                            
                            <!-- Tipo de API -->
                            <div class="mb-4">
                                <label class="form-label"><strong>Tipo de API de WhatsApp</strong></label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="api_type" id="api_simulado" 
                                           value="simulado" <?= $current_config['api_type'] === 'simulado' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="api_simulado">
                                        <strong>Simulado</strong> - Solo para pruebas (no envía mensajes reales)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="api_type" id="api_flexbis" 
                                           value="flexbis" <?= $current_config['api_type'] === 'flexbis' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="api_flexbis">
                                        <strong>FlexBis</strong> - Envía mensajes reales (requiere credenciales)
                                    </label>
                                </div>
                            </div>

                            <!-- Configuración FlexBis -->
                            <div id="flexbis_config" style="<?= $current_config['api_type'] !== 'flexbis' ? 'display:none' : '' ?>">
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Nota:</strong> Estas credenciales las proporciona FlexBis después de contratar el servicio.
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">API SID <span class="text-danger">*</span></label>
                                    <div class="credential-input">
                                        <input type="password" class="form-control" name="api_sid" id="api_sid"
                                               value="<?= htmlspecialchars($current_config['api_sid']) ?>" 
                                               placeholder="Ingresa el SID proporcionado por FlexBis">
                                        <i class="fas fa-eye toggle-visibility" onclick="toggleVisibility('api_sid')"></i>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">API Key <span class="text-danger">*</span></label>
                                    <div class="credential-input">
                                        <input type="password" class="form-control" name="api_key" id="api_key"
                                               value="<?= htmlspecialchars($current_config['api_key']) ?>" 
                                               placeholder="Ingresa la Key proporcionada por FlexBis">
                                        <i class="fas fa-eye toggle-visibility" onclick="toggleVisibility('api_key')"></i>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">URL de la API</label>
                                    <input type="url" class="form-control" name="api_url"
                                           value="<?= htmlspecialchars($current_config['api_url']) ?>" 
                                           placeholder="https://api.flexbis.com/v1/">
                                    <small class="text-muted">Normalmente no necesitas cambiar esto</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Número de WhatsApp <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" name="whatsapp_from"
                                           value="<?= htmlspecialchars($current_config['whatsapp_from']) ?>" 
                                           placeholder="+51987654321">
                                    <small class="text-muted">Incluye el código de país (ej: +51 para Perú)</small>
                                </div>

                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Guardar Configuración
                                </button>
                                
                                <button type="submit" name="accion" value="test_conexion" class="btn btn-outline-info"
                                        <?= $current_config['api_type'] !== 'flexbis' ? 'disabled' : '' ?>>
                                    <i class="fas fa-plug me-1"></i> Probar Conexión
                                </button>
                            </div>

                        </form>
                        
                    </div>
                </div>

                <!-- Información Adicional -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-question-circle me-1"></i> Información de FlexBis</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>¿Qué es FlexBis?</h6>
                                <p class="small text-muted">
                                    FlexBis es un proveedor de API de WhatsApp Business que permite enviar 
                                    mensajes programáticos de forma oficial y confiable.
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6>¿Cómo obtener credenciales?</h6>
                                <p class="small text-muted">
                                    Debes contratar el servicio directamente con FlexBis. Ellos te 
                                    proporcionarán el SID, Key y número de WhatsApp autorizado.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar/ocultar configuración FlexBis
        document.addEventListener('DOMContentLoaded', function() {
            const radioButtons = document.querySelectorAll('input[name="api_type"]');
            const flexbisConfig = document.getElementById('flexbis_config');
            
            radioButtons.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value === 'flexbis') {
                        flexbisConfig.style.display = 'block';
                    } else {
                        flexbisConfig.style.display = 'none';
                    }
                });
            });
        });
        
        // Toggle visibility de credenciales
        function toggleVisibility(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling;
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>

</body>
</html>