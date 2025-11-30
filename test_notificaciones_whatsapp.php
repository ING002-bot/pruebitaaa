<?php
/**
 * Test de notificaciones WhatsApp simuladas
 * Para verificar que el sistema funciona mientras esperamos FlexBis
 */

require_once 'config/config.php';
require_once 'config/whatsapp_helper.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test WhatsApp Simulado - HERMES EXPRESS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container my-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h4><i class="fab fa-whatsapp me-2"></i>Test WhatsApp Simulado</h4>
                        <small>Verificando notificaciones mientras esperamos documentación FlexBis</small>
                    </div>
                    <div class="card-body">

<?php
if ($_POST) {
    echo '<div class="alert alert-info">';
    echo '<h6><i class="fas fa-cog me-2"></i>Ejecutando test...</h6>';
    
    try {
        $whatsapp = new WhatsAppNotificaciones();
        
        $telefono = $_POST['telefono'] ?? '+51987654321';
        $mensaje = $_POST['mensaje'] ?? 'Test de notificación WhatsApp desde HERMES EXPRESS';
        
        echo "<strong>📱 Teléfono:</strong> $telefono<br>";
        echo "<strong>💬 Mensaje:</strong> " . htmlspecialchars($mensaje) . "<br><br>";
        
        $resultado = $whatsapp->enviarMensajeDirecto($telefono, $mensaje);
        
        if ($resultado !== 'error') {
            echo '<div class="alert alert-success mt-3">';
            echo '<i class="fas fa-check-circle me-2"></i>';
            echo "<strong>✅ Notificación simulada correctamente!</strong><br>";
            echo "ID/Resultado: <code>$resultado</code><br>";
            echo "Modo actual: <strong>" . (defined('WHATSAPP_API_TYPE') ? WHATSAPP_API_TYPE : 'simulado') . "</strong>";
            echo '</div>';
            
            // Mostrar información adicional en modo híbrido
            if (defined('WHATSAPP_API_TYPE') && WHATSAPP_API_TYPE === 'hibrido') {
                echo '<div class="alert alert-warning">';
                echo '<i class="fas fa-info-circle me-2"></i>';
                echo '<strong>Modo Híbrido:</strong> Mensaje simulado + test FlexBis en background<br>';
                echo 'Revisa los logs de PHP para ver el resultado del test FlexBis';
                echo '</div>';
            }
            
        } else {
            echo '<div class="alert alert-danger mt-3">';
            echo '<i class="fas fa-exclamation-triangle me-2"></i>';
            echo "<strong>❌ Error al enviar notificación</strong>";
            echo '</div>';
        }
        
    } catch (Exception $e) {
        echo '<div class="alert alert-danger mt-3">';
        echo '<i class="fas fa-bug me-2"></i>';
        echo "<strong>❌ Error:</strong> " . $e->getMessage();
        echo '</div>';
    }
    
    echo '</div>';
}
?>

                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">📱 Número de teléfono</label>
                                    <input type="text" class="form-control" name="telefono" 
                                           value="<?= htmlspecialchars($_POST['telefono'] ?? '+51987654321') ?>"
                                           placeholder="+51987654321" required>
                                    <div class="form-text">Formato: +51 + número de 9 dígitos</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">⚡ Acción</label>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-success btn-lg">
                                            <i class="fab fa-whatsapp me-2"></i>
                                            Probar Notificación
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-12">
                                    <label class="form-label">💬 Mensaje de prueba</label>
                                    <textarea class="form-control" name="mensaje" rows="3" required><?= htmlspecialchars($_POST['mensaje'] ?? 'Test de notificación WhatsApp desde HERMES EXPRESS - ' . date('d/m/Y H:i:s')) ?></textarea>
                                </div>
                            </div>
                        </form>

                        <hr class="my-4">

                        <!-- Estado del Sistema -->
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-info-circle me-2"></i>Estado del Sistema</h6>
                                <ul class="list-unstyled">
                                    <li><strong>API Type:</strong> 
                                        <span class="badge bg-<?= (defined('WHATSAPP_API_TYPE') && WHATSAPP_API_TYPE === 'flexbis') ? 'success' : 'warning' ?>">
                                            <?= defined('WHATSAPP_API_TYPE') ? WHATSAPP_API_TYPE : 'simulado' ?>
                                        </span>
                                    </li>
                                    <li><strong>FlexBis SID:</strong> 
                                        <?= defined('FLEXBIS_API_SID') && FLEXBIS_API_SID ? '✅ Configurado' : '❌ No configurado' ?>
                                    </li>
                                    <li><strong>FlexBis Key:</strong> 
                                        <?= defined('FLEXBIS_API_KEY') && FLEXBIS_API_KEY ? '✅ Configurado' : '❌ No configurado' ?>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-tools me-2"></i>Enlaces Útiles</h6>
                                <div class="d-grid gap-2">
                                    <a href="configurar_flexbis.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-cog me-1"></i>Configurar FlexBis
                                    </a>
                                    <a href="test_flexbis.php" class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-vial me-1"></i>Test FlexBis Completo
                                    </a>
                                </div>
                            </div>
                        </div>

                        <?php if (defined('WHATSAPP_API_TYPE') && WHATSAPP_API_TYPE !== 'flexbis'): ?>
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-clock me-2"></i>
                            <strong>Estado actual:</strong> Sistema en modo simulado/híbrido.<br>
                            Las notificaciones se registran pero no se envían realmente por WhatsApp.<br>
                            <small>Esperando documentación oficial de FlexBis para activar envíos reales.</small>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>