<?php
/**
 * Script de prueba para verificar conexión con Twilio
 * Acceder desde: http://localhost/pruebitaaa/test_twilio.php
 */

require_once 'config/config.php';

// Verificar que sea admin
if (!isLoggedIn() || $_SESSION['rol'] !== 'admin') {
    die('❌ Solo administradores pueden acceder');
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba Twilio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light p-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-phone"></i> Prueba de Conexión Twilio</h4>
                    </div>
                    <div class="card-body">
                        
                        <h6>📋 Configuración Actual</h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm">
                                <tr>
                                    <th>Configuración</th>
                                    <th>Valor</th>
                                    <th>Estado</th>
                                </tr>
                                <tr>
                                    <td><code>WHATSAPP_API_TYPE</code></td>
                                    <td><strong><?php echo defined('WHATSAPP_API_TYPE') ? WHATSAPP_API_TYPE : 'No definido'; ?></strong></td>
                                    <td>
                                        <?php if (defined('WHATSAPP_API_TYPE') && WHATSAPP_API_TYPE === 'twilio'): ?>
                                            <span class="badge bg-success">✓ Correcto</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">⚠ No es Twilio</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>TWILIO_ACCOUNT_SID</code></td>
                                    <td><code><?php echo defined('TWILIO_ACCOUNT_SID') ? substr(TWILIO_ACCOUNT_SID, 0, 8) . '...' : 'No definido'; ?></code></td>
                                    <td>
                                        <?php if (defined('TWILIO_ACCOUNT_SID') && !empty(TWILIO_ACCOUNT_SID)): ?>
                                            <span class="badge bg-success">✓ Configurado</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">✗ Falta config</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>TWILIO_AUTH_TOKEN</code></td>
                                    <td><code><?php echo defined('TWILIO_AUTH_TOKEN') ? substr(TWILIO_AUTH_TOKEN, 0, 8) . '...' : 'No definido'; ?></code></td>
                                    <td>
                                        <?php if (defined('TWILIO_AUTH_TOKEN') && !empty(TWILIO_AUTH_TOKEN)): ?>
                                            <span class="badge bg-success">✓ Configurado</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">✗ Falta config</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>TWILIO_WHATSAPP_FROM</code></td>
                                    <td><code><?php echo defined('TWILIO_WHATSAPP_FROM') ? TWILIO_WHATSAPP_FROM : 'No definido'; ?></code></td>
                                    <td>
                                        <?php if (defined('TWILIO_WHATSAPP_FROM') && !empty(TWILIO_WHATSAPP_FROM)): ?>
                                            <span class="badge bg-success">✓ Configurado</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">✗ Falta config</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <h6>🧪 Prueba de Autenticación</h6>
                        <?php
                        $account_sid = defined('TWILIO_ACCOUNT_SID') ? TWILIO_ACCOUNT_SID : '';
                        $auth_token = defined('TWILIO_AUTH_TOKEN') ? TWILIO_AUTH_TOKEN : '';
                        
                        if (!empty($account_sid) && !empty($auth_token)) {
                            $auth = base64_encode($account_sid . ':' . $auth_token);
                            $url = 'https://api.twilio.com/2010-04-01/Accounts/' . $account_sid . '.json';
                            
                            $ch = curl_init($url);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                'Authorization: Basic ' . $auth
                            ]);
                            
                            $response = curl_exec($ch);
                            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            $curl_error = curl_error($ch);
                            curl_close($ch);
                            
                            if ($http_code === 200) {
                                $result = json_decode($response, true);
                                ?>
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle"></i> <strong>✅ Autenticación exitosa</strong><br>
                                    <small>
                                        Cuenta: <?php echo $result['friendly_name'] ?? 'N/A'; ?><br>
                                        Estado: <?php echo $result['status'] ?? 'N/A'; ?><br>
                                        Tipo: <?php echo $result['type'] ?? 'N/A'; ?>
                                    </small>
                                </div>
                                <?php
                            } else {
                                ?>
                                <div class="alert alert-danger">
                                    <i class="bi bi-x-circle"></i> <strong>❌ Error de autenticación</strong><br>
                                    <small>
                                        HTTP Code: <?php echo $http_code; ?><br>
                                        <?php if ($http_code === 401): ?>
                                            Credenciales inválidas. Verifica Account SID y Auth Token
                                        <?php else: ?>
                                            Error: <?php echo $curl_error ?: 'Ver logs'; ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <?php
                            }
                        } else {
                            ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> <strong>⚠️ Credenciales no configuradas</strong><br>
                                <small>Agrega las constantes de Twilio a config.php</small>
                            </div>
                            <?php
                        }
                        ?>

                        <h6 class="mt-4">📱 Prueba de Envío (Manual)</h6>
                        <?php
                        if (isset($_POST['test_send']) && $http_code === 200) {
                            require_once 'config/whatsapp_helper.php';
                            
                            $whatsapp = new WhatsAppNotificaciones();
                            $paquete_id = (int)$_POST['paquete_id'];
                            $resultado = $whatsapp->notificarAsignacion($paquete_id);
                            
                            if ($resultado !== 'error') {
                                ?>
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle"></i> <strong>✅ Mensaje enviado exitosamente</strong><br>
                                    <small>
                                        Message SID: <code><?php echo $resultado; ?></code><br>
                                        Verifica tu WhatsApp en pocos segundos
                                    </small>
                                </div>
                                <?php
                            } else {
                                ?>
                                <div class="alert alert-danger">
                                    <i class="bi bi-x-circle"></i> <strong>❌ Error al enviar mensaje</strong><br>
                                    <small>Revisa los logs para más detalles</small>
                                </div>
                                <?php
                            }
                        }
                        
                        if ($http_code === 200) {
                            // Obtener paquetes con repartidor
                            $db = Database::getInstance()->getConnection();
                            $stmt = $db->prepare("
                                SELECT p.id, p.codigo_seguimiento, p.destinatario_nombre, p.destinatario_telefono
                                FROM paquetes p
                                WHERE p.repartidor_id IS NOT NULL
                                ORDER BY p.id DESC
                                LIMIT 10
                            ");
                            $stmt->execute();
                            $paquetes = $stmt->get_result();
                            ?>
                            <form method="POST" class="mt-2">
                                <div class="row g-2">
                                    <div class="col-md-8">
                                        <select name="paquete_id" class="form-select" required>
                                            <option value="">Selecciona un paquete...</option>
                                            <?php while ($pkg = $paquetes->fetch_assoc()): ?>
                                            <option value="<?php echo $pkg['id']; ?>">
                                                <?php echo $pkg['codigo_seguimiento']; ?> - <?php echo $pkg['destinatario_nombre']; ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" name="test_send" class="btn btn-primary w-100">
                                            <i class="bi bi-send"></i> Enviar WhatsApp
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <small class="text-muted mt-2 d-block">
                                ⚠️ Esto enviará un mensaje real de WhatsApp. Asegúrate de tener un número válido en el paquete.
                            </small>
                            <?php
                            $stmt->close();
                        }
                        ?>

                        <hr class="my-4">

                        <h6>📖 Documentación</h6>
                        <ul class="small">
                            <li><strong>Configuración:</strong> Ver <code>config/config.php</code></li>
                            <li><strong>Implementación:</strong> Ver <code>config/whatsapp_helper.php</code></li>
                            <li><strong>Logs:</strong> Busca en <code>error_log</code></li>
                            <li><strong>Documentación Twilio:</strong> <a href="https://www.twilio.com/docs/whatsapp/api" target="_blank">Twilio WhatsApp API</a></li>
                        </ul>

                    </div>
                    
                    <div class="card-footer">
                        <a href="admin/paquetes.php" class="btn btn-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Volver a Paquetes
                        </a>
                        <a href="test_whatsapp.php" class="btn btn-info btn-sm">
                            <i class="bi bi-gear"></i> Ver Sistema Completo
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
