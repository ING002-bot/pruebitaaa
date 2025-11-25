<?php
/**
 * Diagnóstico detallado de credenciales Twilio
 * Acceder desde: http://localhost/pruebitaaa/diagnostico_twilio.php
 */

require_once 'config/config.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico Twilio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .mono { font-family: 'Courier New', monospace; }
        .debug-box { background: #f8f9fa; border-left: 4px solid #0d6efd; padding: 10px; margin: 10px 0; }
        .success-text { color: #198754; font-weight: bold; }
        .error-text { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body class="bg-light p-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0"><i class="bi bi-bug"></i> Diagnóstico Detallado Twilio</h4>
                    </div>
                    <div class="card-body">
                        
                        <h5 class="mb-3">📋 Credenciales en config.php</h5>
                        
                        <div class="debug-box">
                            <p><strong>TWILIO_ACCOUNT_SID:</strong></p>
                            <code class="mono d-block mb-2">
                                <?php echo defined('TWILIO_ACCOUNT_SID') ? TWILIO_ACCOUNT_SID : 'NO DEFINIDO'; ?>
                            </code>
                            <small>
                                <?php 
                                if (defined('TWILIO_ACCOUNT_SID')) {
                                    echo "✓ Longitud: " . strlen(TWILIO_ACCOUNT_SID) . " caracteres";
                                    if (strlen(TWILIO_ACCOUNT_SID) === 34 && strpos(TWILIO_ACCOUNT_SID, 'AC') === 0) {
                                        echo " <span class='success-text'>(✓ Formato correcto)</span>";
                                    } else {
                                        echo " <span class='error-text'>(✗ Formato incorrecto - debe empezar con AC y tener 34 caracteres)</span>";
                                    }
                                } else {
                                    echo "<span class='error-text'>NO CONFIGURADO</span>";
                                }
                                ?>
                            </small>
                        </div>

                        <div class="debug-box">
                            <p><strong>TWILIO_AUTH_TOKEN:</strong></p>
                            <code class="mono d-block mb-2">
                                <?php 
                                if (defined('TWILIO_AUTH_TOKEN')) {
                                    echo substr(TWILIO_AUTH_TOKEN, 0, 8) . '...' . substr(TWILIO_AUTH_TOKEN, -8);
                                } else {
                                    echo 'NO DEFINIDO';
                                }
                                ?>
                            </code>
                            <small>
                                <?php 
                                if (defined('TWILIO_AUTH_TOKEN')) {
                                    echo "✓ Longitud: " . strlen(TWILIO_AUTH_TOKEN) . " caracteres";
                                    if (strlen(TWILIO_AUTH_TOKEN) === 32) {
                                        echo " <span class='success-text'>(✓ Formato correcto)</span>";
                                    } else {
                                        echo " <span class='error-text'>(✗ Debe tener 32 caracteres, tiene " . strlen(TWILIO_AUTH_TOKEN) . ")</span>";
                                    }
                                } else {
                                    echo "<span class='error-text'>NO CONFIGURADO</span>";
                                }
                                ?>
                            </small>
                        </div>

                        <div class="debug-box">
                            <p><strong>TWILIO_WHATSAPP_FROM:</strong></p>
                            <code class="mono d-block mb-2">
                                <?php echo defined('TWILIO_WHATSAPP_FROM') ? TWILIO_WHATSAPP_FROM : 'NO DEFINIDO'; ?>
                            </code>
                            <small>
                                <?php 
                                if (defined('TWILIO_WHATSAPP_FROM') && strpos(TWILIO_WHATSAPP_FROM, '+') !== false) {
                                    echo "<span class='success-text'>✓ Número con prefijo</span>";
                                } else {
                                    echo "<span class='error-text'>✗ Debe tener formato: whatsapp:+14155238886</span>";
                                }
                                ?>
                            </small>
                        </div>

                        <hr>

                        <h5 class="mb-3">🔐 Prueba de Base64 (Authentication Header)</h5>
                        
                        <?php
                        if (defined('TWILIO_ACCOUNT_SID') && defined('TWILIO_AUTH_TOKEN')) {
                            $account_sid = TWILIO_ACCOUNT_SID;
                            $auth_token = TWILIO_AUTH_TOKEN;
                            $combined = $account_sid . ':' . $auth_token;
                            $base64 = base64_encode($combined);
                            ?>
                            
                            <div class="debug-box">
                                <p><strong>String a codificar (SID:TOKEN):</strong></p>
                                <code class="mono d-block small mb-2">
                                    <?php echo htmlspecialchars($combined); ?>
                                </code>
                            </div>

                            <div class="debug-box">
                                <p><strong>Base64 Codificado (para header Authorization):</strong></p>
                                <code class="mono d-block small mb-2">
                                    <?php echo $base64; ?>
                                </code>
                                <small>Header que se envía: <code>Authorization: Basic <?php echo $base64; ?></code></small>
                            </div>

                            <?php
                        } else {
                            echo '<div class="alert alert-warning">⚠️ Faltan credenciales para hacer la prueba</div>';
                        }
                        ?>

                        <hr>

                        <h5 class="mb-3">🌐 Prueba de Conexión Real</h5>
                        
                        <?php
                        if (defined('TWILIO_ACCOUNT_SID') && defined('TWILIO_AUTH_TOKEN')) {
                            $account_sid = TWILIO_ACCOUNT_SID;
                            $auth_token = TWILIO_AUTH_TOKEN;
                            $auth = base64_encode($account_sid . ':' . $auth_token);
                            $url = 'https://api.twilio.com/2010-04-01/Accounts/' . $account_sid . '.json';
                            
                            echo "<p><strong>URL de solicitud:</strong></p>";
                            echo "<code class='mono d-block small mb-3'>" . htmlspecialchars($url) . "</code>";
                            
                            echo "<p><strong>Headers HTTP:</strong></p>";
                            echo "<code class='mono d-block small mb-3'>Authorization: Basic " . substr($auth, 0, 20) . "..." . "</code>";
                            
                            $ch = curl_init($url);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                            curl_setopt($ch, CURLOPT_VERBOSE, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                'Authorization: Basic ' . $auth,
                                'Accept: application/json'
                            ]);
                            
                            $verbose = fopen('php://temp', 'r+');
                            curl_setopt($ch, CURLOPT_STDERR, $verbose);
                            
                            $response = curl_exec($ch);
                            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            $curl_error = curl_error($ch);
                            $curl_info = curl_getinfo($ch);
                            curl_close($ch);
                            
                            echo "<div class='debug-box'>";
                            echo "<p><strong>Respuesta HTTP:</strong></p>";
                            echo "<p>HTTP Code: <span class='mono error-text'>" . $http_code . "</span></p>";
                            
                            if ($http_code === 200) {
                                echo "<div class='alert alert-success'><strong>✅ AUTENTICACIÓN EXITOSA</strong></div>";
                                $result = json_decode($response, true);
                                if ($result) {
                                    echo "<p><strong>Datos de la cuenta:</strong></p>";
                                    echo "<ul>";
                                    echo "<li>Friendly Name: " . ($result['friendly_name'] ?? 'N/A') . "</li>";
                                    echo "<li>Status: " . ($result['status'] ?? 'N/A') . "</li>";
                                    echo "<li>Type: " . ($result['type'] ?? 'N/A') . "</li>";
                                    echo "<li>Date Created: " . ($result['date_created'] ?? 'N/A') . "</li>";
                                    echo "</ul>";
                                }
                            } elseif ($http_code === 401) {
                                echo "<div class='alert alert-danger'>";
                                echo "<strong>❌ ERROR 401 - AUTENTICACIÓN FALLIDA</strong><br>";
                                echo "Las credenciales son INVÁLIDAS o están EXPIRADAS<br>";
                                echo "<hr>";
                                echo "<p><strong>Pasos para solucionar:</strong></p>";
                                echo "<ol>";
                                echo "<li>Ve a <a href='https://www.twilio.com/console' target='_blank'>https://www.twilio.com/console</a></li>";
                                echo "<li>En la dashboard, busca <strong>'Account SID'</strong></li>";
                                echo "<li>Copia el Account SID completo (comienza con AC)</li>";
                                echo "<li>Busca el <strong>'Auth Token'</strong> (Click en el ojo para mostrarlo)</li>";
                                echo "<li>Copia el Auth Token completo (32 caracteres)</li>";
                                echo "<li>Reemplaza en config.php:<br>";
                                echo "<code class='mono d-block' style='background: #fff3cd; padding: 10px;'>";
                                echo "define('TWILIO_ACCOUNT_SID', 'NUEVO_SID_AQUI');<br>";
                                echo "define('TWILIO_AUTH_TOKEN', 'NUEVO_TOKEN_AQUI');<br>";
                                echo "</code>";
                                echo "</li>";
                                echo "<li>Guarda el archivo</li>";
                                echo "<li>Recarga esta página</li>";
                                echo "</ol>";
                                echo "</div>";
                            } else {
                                echo "<div class='alert alert-warning'>";
                                echo "HTTP Code: " . $http_code . "<br>";
                                echo "Error: " . ($curl_error ?: "Ver respuesta abajo");
                                echo "</div>";
                            }
                            
                            if ($response && $http_code !== 200) {
                                echo "<p><strong>Respuesta del servidor:</strong></p>";
                                echo "<pre class='bg-dark text-light p-3 rounded' style='overflow-x: auto;'>";
                                echo htmlspecialchars($response);
                                echo "</pre>";
                            }
                            echo "</div>";
                            
                        } else {
                            echo '<div class="alert alert-danger">❌ Faltan credenciales en config.php</div>';
                        }
                        ?>

                        <hr class="my-4">

                        <h5 class="mb-3">💾 Archivo config.php Actual</h5>
                        <p><strong>Ubicación:</strong> <code>/config/config.php</code></p>
                        <p><strong>Busca estas líneas y verifica:</strong></p>
                        
                        <pre class="bg-dark text-light p-3 rounded mono small"><code><?php
// Mostrar las líneas relevantes de config.php
$config_lines = file(realpath(__DIR__ . '/config/config.php'));
$in_twilio = false;
foreach ($config_lines as $i => $line) {
    if (strpos($line, 'TWILIO') !== false || strpos($line, 'WHATSAPP_API_TYPE') !== false) {
        echo htmlspecialchars($line);
    }
}
?></code></pre>

                        <hr class="my-4">

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> <strong>Nota:</strong> Si ves Error 401, tus credenciales NO son válidas. 
                            Cópialas nuevamente desde Twilio Console.
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
