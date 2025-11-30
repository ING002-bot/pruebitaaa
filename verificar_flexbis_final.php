<?php
/**
 * Verificación Final del Sistema FlexBis
 * Script para validar que toda la infraestructura FlexBis está lista
 */

require_once 'config/config.php';
require_once 'config/flexbis_client.php';

header('Content-Type: text/html; charset=UTF-8');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación Sistema FlexBis - HERMES EXPRESS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .status-ok { color: #28a745; }
        .status-warning { color: #ffc107; }
        .status-error { color: #dc3545; }
        .check-item {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .check-result {
            font-weight: bold;
            margin-left: 10px;
        }
        .info-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 10px 0;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            Verificación Final - Sistema FlexBis
                        </h3>
                    </div>
                    <div class="card-body">
                        
                        <!-- Verificación de Archivos -->
                        <div class="mb-4">
                            <h5><i class="fas fa-file-code me-2"></i>Archivos del Sistema</h5>
                            <?php
                            $archivos_requeridos = [
                                'config/flexbis_client.php' => 'Clase FlexBisClient',
                                'config/whatsapp_helper.php' => 'WhatsApp Helper actualizado',
                                'configurar_flexbis.php' => 'Interfaz de configuración',
                                'test_flexbis.php' => 'Interfaz de testing',
                                '.env' => 'Archivo de variables (opcional)'
                            ];

                            foreach ($archivos_requeridos as $archivo => $descripcion) {
                                $existe = file_exists($archivo);
                                echo '<div class="check-item">';
                                echo '<i class="fas ' . ($existe ? 'fa-check status-ok' : 'fa-times status-error') . '"></i> ';
                                echo $descripcion;
                                echo '<span class="check-result ' . ($existe ? 'status-ok' : 'status-error') . '">';
                                echo $existe ? 'Presente' : 'Faltante';
                                echo '</span>';
                                echo '</div>';
                            }
                            ?>
                        </div>

                        <!-- Verificación de Clases -->
                        <div class="mb-4">
                            <h5><i class="fas fa-cogs me-2"></i>Clases y Métodos</h5>
                            <?php
                            $clases_disponibles = [];
                            
                            // Verificar FlexBisClient
                            if (class_exists('FlexBisClient')) {
                                $reflexion = new ReflectionClass('FlexBisClient');
                                $metodos = $reflexion->getMethods(ReflectionMethod::IS_PUBLIC);
                                $clases_disponibles['FlexBisClient'] = count($metodos);
                                
                                echo '<div class="check-item">';
                                echo '<i class="fas fa-check status-ok"></i> ';
                                echo 'FlexBisClient cargada';
                                echo '<span class="check-result status-ok">';
                                echo count($metodos) . ' métodos públicos';
                                echo '</span>';
                                echo '</div>';
                                
                                // Verificar métodos principales
                                $metodos_requeridos = ['sendMessage', 'testConnection', 'getBalance', 'getMessageStatus'];
                                foreach ($metodos_requeridos as $metodo) {
                                    $existe_metodo = $reflexion->hasMethod($metodo);
                                    echo '<div class="check-item ms-3">';
                                    echo '<i class="fas ' . ($existe_metodo ? 'fa-check status-ok' : 'fa-times status-error') . '"></i> ';
                                    echo 'Método ' . $metodo . '()';
                                    echo '<span class="check-result ' . ($existe_metodo ? 'status-ok' : 'status-error') . '">';
                                    echo $existe_metodo ? 'Disponible' : 'Faltante';
                                    echo '</span>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="check-item">';
                                echo '<i class="fas fa-times status-error"></i> ';
                                echo 'FlexBisClient';
                                echo '<span class="check-result status-error">No encontrada</span>';
                                echo '</div>';
                            }

                            // Verificar WhatsAppHelper
                            if (class_exists('WhatsAppHelper')) {
                                echo '<div class="check-item">';
                                echo '<i class="fas fa-check status-ok"></i> ';
                                echo 'WhatsAppHelper cargada';
                                echo '<span class="check-result status-ok">Disponible</span>';
                                echo '</div>';
                            }
                            ?>
                        </div>

                        <!-- Verificación de Variables -->
                        <div class="mb-4">
                            <h5><i class="fas fa-key me-2"></i>Variables de Entorno</h5>
                            <?php
                            $variables_requeridas = [
                                'WHATSAPP_API_TYPE' => 'Tipo de API WhatsApp',
                                'FLEXBIS_API_SID' => 'FlexBis SID',
                                'FLEXBIS_API_KEY' => 'FlexBis API Key',
                                'FLEXBIS_API_URL' => 'FlexBis URL',
                                'FLEXBIS_WHATSAPP_FROM' => 'Número WhatsApp'
                            ];

                            foreach ($variables_requeridas as $var => $descripcion) {
                                $valor = $_ENV[$var] ?? getenv($var) ?? '';
                                $configurada = !empty($valor);
                                
                                echo '<div class="check-item">';
                                echo '<i class="fas ' . ($configurada ? 'fa-check status-ok' : 'fa-times status-error') . '"></i> ';
                                echo $descripcion . ' (' . $var . ')';
                                echo '<span class="check-result ' . ($configurada ? 'status-ok' : 'status-error') . '">';
                                
                                if ($configurada) {
                                    if (in_array($var, ['FLEXBIS_API_SID', 'FLEXBIS_API_KEY'])) {
                                        echo 'Configurada (***' . substr($valor, -4) . ')';
                                    } else {
                                        echo $valor;
                                    }
                                } else {
                                    echo 'No configurada';
                                }
                                echo '</span>';
                                echo '</div>';
                            }
                            ?>
                        </div>

                        <!-- Test de FlexBis -->
                        <?php if (class_exists('FlexBisClient')): ?>
                        <div class="mb-4">
                            <h5><i class="fas fa-satellite-dish me-2"></i>Prueba de FlexBis</h5>
                            <?php
                            try {
                                $flexbis = new FlexBisClient();
                                $config = $flexbis->getConfig();
                                
                                echo '<div class="check-item">';
                                echo '<i class="fas ' . ($config['is_configured'] ? 'fa-check status-ok' : 'fa-times status-error') . '"></i> ';
                                echo 'Configuración FlexBis';
                                echo '<span class="check-result ' . ($config['is_configured'] ? 'status-ok' : 'status-error') . '">';
                                echo $config['is_configured'] ? 'Completa' : 'Incompleta';
                                echo '</span>';
                                echo '</div>';
                                
                                if ($config['is_configured']) {
                                    // Test de conexión (sin enviar mensajes)
                                    $test_connection = $flexbis->testConnection();
                                    echo '<div class="check-item">';
                                    echo '<i class="fas ' . ($test_connection['success'] ? 'fa-check status-ok' : 'fa-times status-error') . '"></i> ';
                                    echo 'Conexión API';
                                    echo '<span class="check-result ' . ($test_connection['success'] ? 'status-ok' : 'status-error') . '">';
                                    echo $test_connection['success'] ? 'Exitosa' : $test_connection['error'];
                                    echo '</span>';
                                    echo '</div>';
                                }
                                
                            } catch (Exception $e) {
                                echo '<div class="check-item">';
                                echo '<i class="fas fa-times status-error"></i> ';
                                echo 'Error al instanciar FlexBis';
                                echo '<span class="check-result status-error">' . $e->getMessage() . '</span>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                        <?php endif; ?>

                        <!-- Extensiones PHP -->
                        <div class="mb-4">
                            <h5><i class="fas fa-code me-2"></i>Extensiones PHP</h5>
                            <?php
                            $extensiones_requeridas = [
                                'curl' => 'cURL (para peticiones API)',
                                'json' => 'JSON (para datos API)',
                                'openssl' => 'OpenSSL (para HTTPS)'
                            ];

                            foreach ($extensiones_requeridas as $ext => $descripcion) {
                                $cargada = extension_loaded($ext);
                                echo '<div class="check-item">';
                                echo '<i class="fas ' . ($cargada ? 'fa-check status-ok' : 'fa-times status-error') . '"></i> ';
                                echo $descripcion;
                                echo '<span class="check-result ' . ($cargada ? 'status-ok' : 'status-error') . '">';
                                echo $cargada ? 'Habilitada' : 'No disponible';
                                echo '</span>';
                                echo '</div>';
                            }
                            ?>
                        </div>

                        <!-- Resumen del Estado -->
                        <div class="info-box">
                            <h5><i class="fas fa-info-circle me-2"></i>Resumen del Sistema</h5>
                            <?php
                            $flexbis_client_ok = class_exists('FlexBisClient');
                            $whatsapp_helper_ok = class_exists('WhatsAppHelper');
                            $curl_ok = extension_loaded('curl');
                            $json_ok = extension_loaded('json');
                            $config_files_ok = file_exists('configurar_flexbis.php') && file_exists('test_flexbis.php');
                            
                            $todo_listo = $flexbis_client_ok && $whatsapp_helper_ok && $curl_ok && $json_ok && $config_files_ok;
                            
                            if ($todo_listo) {
                                echo '<div class="alert alert-success">';
                                echo '<i class="fas fa-check-circle me-2"></i>';
                                echo '<strong>✅ Sistema FlexBis LISTO</strong><br>';
                                echo 'Toda la infraestructura FlexBis está correctamente instalada. ';
                                echo 'Puedes proceder con la compra de FlexBis y configurar las credenciales reales.';
                                echo '</div>';
                            } else {
                                echo '<div class="alert alert-warning">';
                                echo '<i class="fas fa-exclamation-triangle me-2"></i>';
                                echo '<strong>⚠️ Sistema INCOMPLETO</strong><br>';
                                echo 'Algunos componentes necesitan atención antes de usar FlexBis en producción.';
                                echo '</div>';
                            }
                            ?>
                        </div>

                        <!-- Enlaces Útiles -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <i class="fas fa-cog me-2"></i>Configuración
                                    </div>
                                    <div class="card-body">
                                        <a href="configurar_flexbis.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-wrench me-1"></i>Configurar FlexBis
                                        </a>
                                        <p class="small text-muted mt-2">
                                            Configurar credenciales y ajustes de FlexBis
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <i class="fas fa-vial me-2"></i>Testing
                                    </div>
                                    <div class="card-body">
                                        <a href="test_flexbis.php" class="btn btn-success btn-sm">
                                            <i class="fas fa-play me-1"></i>Probar FlexBis
                                        </a>
                                        <p class="small text-muted mt-2">
                                            Hacer pruebas de conexión y envío
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información Adicional -->
                        <div class="mt-4">
                            <h6><i class="fas fa-lightbulb me-2"></i>Siguiente Paso</h6>
                            <div class="alert alert-info">
                                <strong>📞 Comprar FlexBis:</strong><br>
                                1. Ve al sitio web de FlexBis<br>
                                2. Compra tu plan de WhatsApp Business API<br>
                                3. Obtén tus credenciales (SID y API Key)<br>
                                4. Configúralas usando <code>configurar_flexbis.php</code><br>
                                5. Prueba con mensajes reales usando <code>test_flexbis.php</code>
                            </div>
                        </div>

                    </div>
                    <div class="card-footer text-muted">
                        <small>
                            <i class="fas fa-clock me-1"></i>
                            Verificación realizada: <?= date('d/m/Y H:i:s') ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>