<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Webhook Twilio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light p-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">

                <div class="card border-0 shadow mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0"><i class="bi bi-gear"></i> Configurar Webhook Sandbox</h4>
                    </div>
                    <div class="card-body">
                        
                        <div class="alert alert-info">
                            <strong>ℹ️ Webhook URL:</strong><br>
                            <code class="d-block bg-light p-2 rounded mt-2">
                                http://localhost/pruebitaaa/webhook_whatsapp.php
                            </code>
                        </div>

                        <h5 class="mt-4">📋 Pasos para Configurar</h5>
                        
                        <ol>
                            <li>
                                <strong>Ve a Twilio Console:</strong><br>
                                <a href="https://www.twilio.com/console/sms/sandbox" target="_blank" class="btn btn-sm btn-primary mt-2">
                                    <i class="bi bi-box-arrow-up-right"></i> Abrir Sandbox
                                </a>
                            </li>

                            <li class="mt-3">
                                <strong>Busca la sección "Incoming Messages"</strong><br>
                                <small class="text-muted">En la configuración del Sandbox de WhatsApp</small>
                            </li>

                            <li class="mt-3">
                                <strong>En el campo "When a message comes in":</strong>
                                <div class="bg-light p-3 rounded mt-2">
                                    <p>Ingresa esta URL:</p>
                                    <code class="d-block">http://localhost/pruebitaaa/webhook_whatsapp.php</code>
                                </div>
                            </li>

                            <li class="mt-3">
                                <strong>Método HTTP:</strong>
                                <div class="bg-light p-3 rounded mt-2">
                                    Selecciona: <strong>POST</strong>
                                </div>
                            </li>

                            <li class="mt-3">
                                <strong>Haz clic en "Save"</strong>
                            </li>
                        </ol>

                        <div class="alert alert-success mt-4">
                            <strong>✅ Después de configurar:</strong>
                            <ol class="mb-0 mt-2">
                                <li>Envía un mensaje a <strong>+1 415 523 8886</strong> con: <code>unirse a fabricación-entero</code></li>
                                <li>Espera la confirmación</li>
                                <li>Luego prueba enviando desde: <a href="sandbox_configuracion.php">sandbox_configuracion.php</a></li>
                            </ol>
                        </div>

                    </div>
                </div>

                <!-- Verificación -->
                <div class="card border-0 shadow">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-check-circle"></i> Verificación</h5>
                    </div>
                    <div class="card-body">
                        <p>Después de configurar el webhook, puedes verificar si funciona:</p>
                        
                        <form method="POST" class="mt-3">
                            <div class="mb-3">
                                <label class="form-label">Envía un SMS de prueba a:</label>
                                <input type="text" class="form-control" value="+1 415 523 8886" readonly>
                                <small class="form-text text-muted">Con mensaje: <code>unirse a fabricación-entero</code></small>
                            </div>
                            
                            <div class="alert alert-info">
                                <strong>Los webhooks se registran en:</strong>
                                <br><code>c:\xampp\logs\php_error_log</code>
                                <br><small>Busca logs con "[WEBHOOK TWILIO RECIBIDO]"</small>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
