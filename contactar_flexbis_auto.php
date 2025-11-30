<?php
/**
 * Script automático para contactar FlexBis
 * Genera mensaje y abre WhatsApp Web
 */

// Datos del contacto
$whatsapp_flexbis = '+51926420256';
$mensaje = urlencode("Hola! 

Soy desarrollador y acabo de adquirir su API de WhatsApp.

🔑 **Mis credenciales:**
- SID: serhsznr
- Token: H4vP1g837ZxKR0VMz3yD

📋 **Necesito documentación técnica:**
1. URL base correcta de la API
2. Endpoints disponibles (/send, /messages, etc.)
3. Formato de autenticación (Bearer, Basic, Headers)
4. Estructura JSON para envío de mensajes
5. Ejemplos de código

🎯 **Para integrar en:**
Sistema de delivery HERMES EXPRESS

¿Pueden proporcionarme la documentación oficial?

¡Muchas gracias!");

// URL para WhatsApp Web
$whatsapp_url = "https://wa.me/{$whatsapp_flexbis}?text={$mensaje}";

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contactar FlexBis - HERMES EXPRESS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container my-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="fab fa-whatsapp me-2"></i>Contacto Automático FlexBis</h4>
                    </div>
                    <div class="card-body">
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>¿Listo para contactar FlexBis?</strong><br>
                            Se abrirá WhatsApp Web con un mensaje pre-escrito solicitando la documentación API.
                        </div>

                        <div class="text-center mb-4">
                            <a href="<?= $whatsapp_url ?>" target="_blank" class="btn btn-success btn-lg">
                                <i class="fab fa-whatsapp me-2"></i>
                                Contactar FlexBis por WhatsApp
                            </a>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-phone me-2"></i>Datos de Contacto</h6>
                                <ul class="list-unstyled">
                                    <li><strong>WhatsApp:</strong> <?= $whatsapp_flexbis ?></li>
                                    <li><strong>Email:</strong> info@flexbis.com</li>
                                    <li><strong>Web:</strong> https://flexbis.com</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-key me-2"></i>Nuestras Credenciales</h6>
                                <ul class="list-unstyled">
                                    <li><strong>SID:</strong> <code>serhsznr</code></li>
                                    <li><strong>Token:</strong> <code>H4vP***yD</code></li>
                                </ul>
                            </div>
                        </div>

                        <hr>

                        <h6><i class="fas fa-envelope me-2"></i>Mensaje a Enviar</h6>
                        <div class="bg-light p-3 rounded">
                            <small><?= nl2br(htmlspecialchars(urldecode($mensaje))) ?></small>
                        </div>

                        <hr>

                        <div class="alert alert-warning">
                            <h6><i class="fas fa-clock me-2"></i>Mientras Esperamos Respuesta</h6>
                            <ul class="mb-0">
                                <li>✅ Sistema funciona en <strong>modo simulado</strong></li>
                                <li>✅ Notificaciones se registran correctamente</li>
                                <li>✅ No se consumen créditos reales</li>
                                <li>🕐 Cuando llegue la documentación, cambiaremos a modo real</li>
                            </ul>
                        </div>

                        <div class="d-grid gap-2 mt-3">
                            <a href="test_notificaciones_whatsapp.php" class="btn btn-outline-success">
                                <i class="fas fa-vial me-2"></i>Probar Notificaciones Simuladas
                            </a>
                            <a href="index.php" class="btn btn-outline-primary">
                                <i class="fas fa-home me-2"></i>Volver al Sistema
                            </a>
                        </div>

                    </div>
                    <div class="card-footer text-muted">
                        <small>
                            <i class="fas fa-info-circle me-1"></i>
                            Este mensaje se envía automáticamente con toda la información necesaria.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-abrir WhatsApp después de 2 segundos
        setTimeout(function() {
            if (confirm('¿Abrir WhatsApp Web para contactar FlexBis?')) {
                window.open('<?= $whatsapp_url ?>', '_blank');
            }
        }, 2000);
    </script>
</body>
</html>