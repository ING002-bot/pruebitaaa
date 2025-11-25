<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twilio Sandbox - Configuración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">

                <!-- Encabezado -->
                <div class="card mb-4 border-0 shadow">
                    <div class="card-header bg-warning text-dark">
                        <h2 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Modo Sandbox - Credenciales de Prueba</h2>
                    </div>
                </div>

                <!-- Información de Estado -->
                <div class="card mb-4 border-0 shadow">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">📋 Estado Actual</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        require_once 'config/config.php';
                        $sid = defined('TWILIO_ACCOUNT_SID') ? TWILIO_ACCOUNT_SID : '';
                        $is_sandbox = strpos($sid, 'AC7cde') === 0; // Sandbox siempre empieza igual
                        ?>
                        <div class="alert <?php echo $is_sandbox ? 'alert-warning' : 'alert-success'; ?>">
                            <strong>
                                <i class="bi bi-<?php echo $is_sandbox ? 'flask' : 'rocket'; ?>"></i>
                                <?php echo $is_sandbox ? 'Modo SANDBOX (Pruebas)' : 'Modo PRODUCCIÓN'; ?>
                            </strong>
                        </div>
                        <table class="table table-sm">
                            <tr>
                                <th>Configuración</th>
                                <th>Valor</th>
                                <th>Estado</th>
                            </tr>
                            <tr>
                                <td><code>WHATSAPP_API_TYPE</code></td>
                                <td><?php echo WHATSAPP_API_TYPE; ?></td>
                                <td><span class="badge bg-success">✓</span></td>
                            </tr>
                            <tr>
                                <td><code>TWILIO_ACCOUNT_SID</code></td>
                                <td><code><?php echo substr($sid, 0, 10) . '...'; ?></code></td>
                                <td><?php echo $is_sandbox ? '<span class="badge bg-warning">Sandbox</span>' : '<span class="badge bg-info">Producción</span>'; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- ⚠️ Limitaciones de Sandbox -->
                <div class="card mb-4 border-0 shadow">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="bi bi-cone"></i> ⚠️ Limitaciones de Sandbox</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <strong>En modo SANDBOX (credenciales de prueba):</strong>
                        </div>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <i class="bi bi-x-circle text-danger"></i> 
                                <strong>No puedes enviar a números aleatorios</strong><br>
                                <small>Solo a números que AGREGUES a la lista blanca</small>
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-x-circle text-danger"></i> 
                                <strong>Los mensajes pueden tener marca "Sandbox"</strong><br>
                                <small>Aparecerá un prefijo en los mensajes</small>
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-check-circle text-success"></i> 
                                <strong>Puedes probar la funcionalidad</strong><br>
                                <small>Perfecto para desarrollo y testing</small>
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-check-circle text-success"></i> 
                                <strong>No hay costo</strong><br>
                                <small>Los mensajes de prueba son gratis</small>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- 🔧 Cómo Configurar Sandbox -->
                <div class="card mb-4 border-0 shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-gear"></i> Cómo Configurar Sandbox</h5>
                    </div>
                    <div class="card-body">
                        <h6>Paso 1: Acceder a Sandbox</h6>
                        <p>Ve a: <a href="https://www.twilio.com/console/sms/sandbox" target="_blank" class="btn btn-sm btn-primary">
                            <i class="bi bi-box-arrow-up-right"></i> Twilio Sandbox Console
                        </a></p>

                        <h6 class="mt-3">Paso 2: Agregar Números Permitidos</h6>
                        <ol>
                            <li>En el dashboard, busca <strong>"Participant phone numbers"</strong></li>
                            <li>Haz clic en <strong>"Add participant phone number"</strong></li>
                            <li>Ingresa el número del cliente (con código de país)<br>
                                <strong>Ejemplo:</strong> <code>+51987654321</code></li>
                            <li>Haz clic en <strong>"Add"</strong></li>
                            <li>¡Listo! Ahora puedes enviar a ese número</li>
                        </ol>

                        <h6 class="mt-3">Paso 3: Formato de Números</h6>
                        <div class="alert alert-info">
                            <strong>Importante:</strong> Los números deben incluir código de país
                            <ul class="mb-0 mt-2">
                                <li><strong>✓ Correcto:</strong> <code>+51987654321</code></li>
                                <li><strong>✓ Correcto:</strong> <code>+51 987 654 321</code></li>
                                <li><strong>✗ Incorrecto:</strong> <code>987654321</code> (sin código de país)</li>
                                <li><strong>✗ Incorrecto:</strong> <code>0987654321</code> (con cero)</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- 🧪 Prueba de Envío -->
                <div class="card mb-4 border-0 shadow">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-play-circle"></i> Prueba de Envío</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <div class="col-md-8">
                                <label for="numero" class="form-label">Número del Cliente</label>
                                <input type="tel" class="form-control" id="numero" name="numero" 
                                       placeholder="+51987654321" required>
                                <small class="form-text text-muted">Con código de país (+51)</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" name="test_send" class="btn btn-success w-100">
                                    <i class="bi bi-send"></i> Enviar Prueba
                                </button>
                            </div>
                        </form>

                        <?php
                        if (isset($_POST['test_send'])) {
                            require_once 'config/whatsapp_helper.php';
                            
                            $numero = $_POST['numero'] ?? '';
                            $whatsapp = new WhatsAppNotificaciones();
                            
                            echo '<div class="alert alert-info mt-3">';
                            echo '<strong>Enviando a: ' . htmlspecialchars($numero) . '</strong><br>';
                            
                            $mensaje = "🧪 Mensaje de prueba desde Hermes Express\n";
                            $mensaje .= "Fecha: " . date('d/m/Y H:i:s') . "\n";
                            $mensaje .= "Sistema: Twilio Sandbox";
                            
                            // Usar método privado
                            $reflection = new ReflectionClass($whatsapp);
                            $method = $reflection->getMethod('enviarConTwilio');
                            $method->setAccessible(true);
                            
                            $result = $method->invoke($whatsapp, $numero, $mensaje);
                            
                            if ($result !== 'error') {
                                echo '<br><span class="badge bg-success">✅ Enviado exitosamente</span>';
                                echo '<br><small>Message SID: ' . htmlspecialchars($result) . '</small>';
                            } else {
                                echo '<br><span class="badge bg-danger">❌ Error al enviar</span>';
                                echo '<br><small>Verifica que el número esté aprobado en Sandbox</small>';
                            }
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>

                <!-- 📈 Próximos Pasos -->
                <div class="card mb-4 border-0 shadow">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bi bi-arrow-right"></i> Próximos Pasos</h5>
                    </div>
                    <div class="card-body">
                        <h6>Para Producción (cuando estés listo):</h6>
                        <ol>
                            <li><strong>Actualiza la cuenta Twilio a PRODUCCIÓN</strong>
                                <ul>
                                    <li>Ve a: <a href="https://www.twilio.com/console/account/upgrade" target="_blank">
                                        https://www.twilio.com/console/account/upgrade
                                    </a></li>
                                    <li>Sigue los pasos de verificación</li>
                                </ul>
                            </li>
                            <li><strong>Solicita acceso a WhatsApp Business API</strong>
                                <ul>
                                    <li>Ve a: <a href="https://www.twilio.com/console/messaging/senders" target="_blank">
                                        Senders
                                    </a></li>
                                    <li>Agrega un número de WhatsApp Business</li>
                                </ul>
                            </li>
                            <li><strong>Actualiza config.php con nuevos SID y Token</strong></li>
                        </ol>
                    </div>
                </div>

                <!-- Links Útiles -->
                <div class="card border-0 shadow">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-link-45deg"></i> Links Útiles</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <a href="https://www.twilio.com/console" target="_blank" class="list-group-item list-group-item-action">
                                <i class="bi bi-box-arrow-up-right"></i> Twilio Console (Dashboard)
                            </a>
                            <a href="https://www.twilio.com/console/sms/sandbox" target="_blank" class="list-group-item list-group-item-action">
                                <i class="bi bi-box-arrow-up-right"></i> Sandbox Configuración
                            </a>
                            <a href="https://www.twilio.com/docs/whatsapp" target="_blank" class="list-group-item list-group-item-action">
                                <i class="bi bi-box-arrow-up-right"></i> Documentación WhatsApp
                            </a>
                            <a href="http://localhost/pruebitaaa/diagnostico_twilio.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-speedometer2"></i> Diagnóstico Completo
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>
</html>
