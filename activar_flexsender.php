<?php
/**
 * Interface para activar FlexSender cuando esté listo
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
    <title>Activación FlexSender - HERMES EXPRESS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .status-ok { color: #28a745; }
        .status-warning { color: #ffc107; }
        .status-error { color: #dc3545; }
    </style>
</head>
<body class="bg-light">
    <div class="container my-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3><i class="fab fa-whatsapp me-2"></i>Estado FlexSender - HERMES EXPRESS</h3>
                    </div>
                    <div class="card-body">

<?php if ($_POST && $_POST['action'] === 'test'): ?>
                        <div class="alert alert-info">
                            <h6><i class="fas fa-cog me-2"></i>Probando FlexSender...</h6>
                            
                            <?php
                            try {
                                $flexbis = new FlexBisClient();
                                $test = $flexbis->testConnection();
                                
                                if ($test['success']) {
                                    echo '<div class="alert alert-success">';
                                    echo '<i class="fas fa-check-circle me-2"></i>';
                                    echo '<strong>✅ FlexSender ACTIVO!</strong><br>';
                                    echo 'La API responde correctamente.';
                                    echo '</div>';
                                    
                                    // Mostrar botón para activar modo real
                                    echo '<form method="POST" class="d-inline">';
                                    echo '<input type="hidden" name="action" value="activate">';
                                    echo '<button type="submit" class="btn btn-success btn-lg">';
                                    echo '<i class="fas fa-rocket me-2"></i>Activar Modo Real';
                                    echo '</button>';
                                    echo '</form>';
                                    
                                } else {
                                    echo '<div class="alert alert-warning">';
                                    echo '<i class="fas fa-clock me-2"></i>';
                                    echo '<strong>⏳ Aún procesando pago</strong><br>';
                                    echo 'Error: ' . htmlspecialchars($test['error']) . '<br>';
                                    echo '<small>El pago puede tardar hasta 24 horas en activarse.</small>';
                                    echo '</div>';
                                }
                                
                            } catch (Exception $e) {
                                echo '<div class="alert alert-danger">';
                                echo '<i class="fas fa-exclamation-triangle me-2"></i>';
                                echo '<strong>Error:</strong> ' . htmlspecialchars($e->getMessage());
                                echo '</div>';
                            }
                            ?>
                        </div>
<?php endif; ?>

<?php if ($_POST && $_POST['action'] === 'activate'): ?>
                        <div class="alert alert-success">
                            <h6><i class="fas fa-rocket me-2"></i>Activando modo real...</h6>
                            
                            <?php
                            // Cambiar a modo real en .env
                            $env_content = file_get_contents('.env');
                            $env_content = preg_replace('/WHATSAPP_API_TYPE=.*/', 'WHATSAPP_API_TYPE=flexbis', $env_content);
                            file_put_contents('.env', $env_content);
                            
                            echo '<div class="alert alert-success">';
                            echo '<i class="fas fa-check me-2"></i>';
                            echo '<strong>✅ MODO REAL ACTIVADO!</strong><br>';
                            echo 'HERMES EXPRESS ahora enviará WhatsApp reales usando FlexSender.';
                            echo '</div>';
                            ?>
                        </div>
<?php endif; ?>

                        <!-- Estado Actual -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5><i class="fas fa-info-circle me-2"></i>Estado del Sistema</h5>
                                <ul class="list-unstyled">
                                    <li><strong>Modo WhatsApp:</strong> 
                                        <span class="badge bg-<?= (defined('WHATSAPP_API_TYPE') && WHATSAPP_API_TYPE === 'flexbis') ? 'success' : 'warning' ?>">
                                            <?= defined('WHATSAPP_API_TYPE') ? WHATSAPP_API_TYPE : 'simulado' ?>
                                        </span>
                                    </li>
                                    <li><strong>FlexSender SID:</strong> 
                                        <span class="<?= defined('FLEXBIS_API_SID') && FLEXBIS_API_SID ? 'status-ok' : 'status-error' ?>">
                                            <?= defined('FLEXBIS_API_SID') && FLEXBIS_API_SID ? '✅ serhsznr' : '❌ No configurado' ?>
                                        </span>
                                    </li>
                                    <li><strong>FlexSender Token:</strong> 
                                        <span class="<?= defined('FLEXBIS_API_KEY') && FLEXBIS_API_KEY ? 'status-ok' : 'status-error' ?>">
                                            <?= defined('FLEXBIS_API_KEY') && FLEXBIS_API_KEY ? '✅ Configurado' : '❌ No configurado' ?>
                                        </span>
                                    </li>
                                    <li><strong>Pago:</strong> 
                                        <span class="status-ok">✅ PAGADO hasta 30/12/2025</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-clipboard-check me-2"></i>Checklist</h5>
                                <ul class="list-unstyled">
                                    <li>✅ Credenciales FlexSender configuradas</li>
                                    <li>✅ API correctamente implementada</li>
                                    <li>✅ Suscripción pagada</li>
                                    <li>⏳ Esperando activación del servicio</li>
                                    <li>⏳ Conexión WhatsApp pendiente</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Acciones -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <i class="fas fa-vial me-2"></i>Probar Estado
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="test">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-play me-1"></i>Probar FlexSender
                                            </button>
                                        </form>
                                        <p class="small text-muted mt-2">
                                            Verificar si FlexSender ya está activo
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <i class="fas fa-cog me-2"></i>Panel FlexSender
                                    </div>
                                    <div class="card-body">
                                        <a href="#" onclick="window.open('https://panel.flexbis.com', '_blank')" class="btn btn-success">
                                            <i class="fas fa-external-link-alt me-1"></i>Abrir Panel
                                        </a>
                                        <p class="small text-muted mt-2">
                                            Conectar WhatsApp en tu panel
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información -->
                        <div class="alert alert-info mt-4">
                            <h6><i class="fas fa-lightbulb me-2"></i>Pasos siguientes:</h6>
                            <ol class="mb-0">
                                <li>Ve a tu <strong>panel FlexSender</strong></li>
                                <li>Haz clic en <strong>"CONEXIÓN A WHATSAPP"</strong></li>
                                <li>Escanea el código QR con tu WhatsApp Business</li>
                                <li>Vuelve aquí y prueba el estado</li>
                                <li>Cuando esté activo, activa el modo real</li>
                            </ol>
                        </div>

                        <?php if (defined('WHATSAPP_API_TYPE') && WHATSAPP_API_TYPE !== 'flexbis'): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Modo actual:</strong> <?= WHATSAPP_API_TYPE ?><br>
                            Las notificaciones se simulan hasta que actives FlexSender.
                        </div>
                        <?php endif; ?>

                    </div>
                    <div class="card-footer text-muted">
                        <small>
                            <i class="fas fa-clock me-1"></i>
                            Última verificación: <?= date('d/m/Y H:i:s') ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>