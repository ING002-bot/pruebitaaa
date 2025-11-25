<?php
/**
 * Script de prueba para verificar sistema de WhatsApp
 * Acceder desde: http://localhost/pruebitaaa/test_whatsapp.php
 */

require_once 'config/config.php';

// Verificar que sea admin
if (!isLoggedIn() || $_SESSION['rol'] !== 'admin') {
    die('❌ Solo administradores pueden acceder');
}

$db = Database::getInstance()->getConnection();
$resultado = [];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba Sistema WhatsApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .status-ok { color: #28a745; }
        .status-warning { color: #ffc107; }
        .status-error { color: #dc3545; }
    </style>
</head>
<body class="bg-light p-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-phone"></i> Verificación del Sistema WhatsApp</h4>
                    </div>
                    <div class="card-body">
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6>1. Estado de Configuración</h6>
                                <div class="list-group list-group-sm">
                                    <div class="list-group-item d-flex justify-content-between">
                                        <span>Tipo de API:</span>
                                        <span class="status-ok">
                                            <strong><?php echo defined('WHATSAPP_API_TYPE') ? WHATSAPP_API_TYPE : 'simulado'; ?></strong>
                                        </span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between">
                                        <span>Token configurado:</span>
                                        <span class="<?php echo defined('WHATSAPP_API_TOKEN') ? 'status-ok' : 'status-warning'; ?>">
                                            <?php echo defined('WHATSAPP_API_TOKEN') ? '✓ Sí' : '⚠ No (OK para simulado)'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6>2. Estado de Base de Datos</h6>
                                <div class="list-group list-group-sm">
                                    <?php
                                    // Verificar tabla
                                    $result = $db->query("SHOW TABLES LIKE 'notificaciones_whatsapp'");
                                    $tabla_existe = $result && $result->num_rows > 0;
                                    ?>
                                    <div class="list-group-item d-flex justify-content-between">
                                        <span>Tabla notificaciones_whatsapp:</span>
                                        <span class="<?php echo $tabla_existe ? 'status-ok' : 'status-error'; ?>">
                                            <?php echo $tabla_existe ? '✓ Existe' : '✗ No existe'; ?>
                                        </span>
                                    </div>
                                    <?php
                                    // Contar registros
                                    if ($tabla_existe) {
                                        $count_result = $db->query("SELECT COUNT(*) as total FROM notificaciones_whatsapp");
                                        $count = $count_result->fetch_assoc()['total'];
                                        ?>
                                        <div class="list-group-item d-flex justify-content-between">
                                            <span>Registros de prueba:</span>
                                            <span class="status-ok"><?php echo $count; ?></span>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h6>3. Últimas Notificaciones</h6>
                        <?php
                        if ($tabla_existe) {
                            $stmt = $db->prepare("
                                SELECT nw.*, p.codigo_seguimiento, p.destinatario_nombre
                                FROM notificaciones_whatsapp nw
                                LEFT JOIN paquetes p ON nw.paquete_id = p.id
                                ORDER BY nw.fecha_envio DESC
                                LIMIT 5
                            ");
                            
                            if ($stmt) {
                                $stmt->execute();
                                $resultado = $stmt->get_result();
                                
                                if ($resultado->num_rows > 0) {
                                    ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Código</th>
                                                    <th>Tipo</th>
                                                    <th>Estado</th>
                                                    <th>Teléfono</th>
                                                    <th>Fecha</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($row = $resultado->fetch_assoc()): ?>
                                                <tr>
                                                    <td><small><?php echo $row['codigo_seguimiento'] ?? 'N/A'; ?></small></td>
                                                    <td><small><span class="badge bg-info"><?php echo $row['tipo']; ?></span></small></td>
                                                    <td>
                                                        <small>
                                                            <span class="badge <?php echo $row['estado'] == 'enviado' ? 'bg-success' : ($row['estado'] == 'fallido' ? 'bg-danger' : 'bg-warning'); ?>">
                                                                <?php echo ucfirst($row['estado']); ?>
                                                            </span>
                                                        </small>
                                                    </td>
                                                    <td><small><?php echo substr($row['telefono'], -9); ?></small></td>
                                                    <td><small><?php echo date('d/m H:i', strtotime($row['fecha_envio'])); ?></small></td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php
                                } else {
                                    echo '<div class="alert alert-info">No hay registros aún. Asigna un repartidor a un paquete para generar un envío.</div>';
                                }
                                $stmt->close();
                            }
                        } else {
                            echo '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> Tabla no existe. <a href="crear_tablas_whatsapp.php">Haz clic aquí para crearla</a></div>';
                        }
                        ?>
                        
                        <hr>
                        
                        <h6>4. Prueba Manual de Envío</h6>
                        <?php
                        if (isset($_POST['test_envio']) && $tabla_existe) {
                            require_once 'config/whatsapp_helper.php';
                            $whatsapp = new WhatsAppNotificaciones();
                            
                            $paquete_id = (int)$_POST['paquete_id'];
                            
                            // Obtener info del paquete
                            $stmt = $db->prepare("SELECT * FROM paquetes WHERE id = ?");
                            $stmt->bind_param("i", $paquete_id);
                            $stmt->execute();
                            $paquete = $stmt->get_result()->fetch_assoc();
                            $stmt->close();
                            
                            if ($paquete) {
                                $resultado_envio = $whatsapp->notificarAsignacion($paquete_id);
                                ?>
                                <div class="alert <?php echo $resultado_envio ? 'alert-success' : 'alert-warning'; ?>">
                                    <i class="bi bi-<?php echo $resultado_envio ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                                    <?php echo $resultado_envio ? 'Envío simulado correctamente' : 'Problema en el envío'; ?>
                                </div>
                                <?php
                                
                                // Mostrar lo que se envió
                                $stmt = $db->prepare("
                                    SELECT * FROM notificaciones_whatsapp 
                                    WHERE paquete_id = ? 
                                    ORDER BY fecha_envio DESC 
                                    LIMIT 1
                                ");
                                $stmt->bind_param("i", $paquete_id);
                                $stmt->execute();
                                $notif = $stmt->get_result()->fetch_assoc();
                                $stmt->close();
                                
                                if ($notif) {
                                    ?>
                                    <div class="card bg-light">
                                        <div class="card-header">
                                            <strong>Mensaje Enviado:</strong>
                                        </div>
                                        <div class="card-body">
                                            <pre style="white-space: pre-wrap; word-break: break-word;"><?php echo htmlspecialchars($notif['mensaje']); ?></pre>
                                            <hr class="my-2">
                                            <small class="text-muted">
                                                📱 A: <?php echo $notif['telefono']; ?><br>
                                                ⏰ Hora: <?php echo date('d/m/Y H:i:s', strtotime($notif['fecha_envio'])); ?><br>
                                                📊 Estado: <?php echo ucfirst($notif['estado']); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <?php
                                }
                            } else {
                                echo '<div class="alert alert-danger">Paquete no encontrado</div>';
                            }
                        }
                        ?>
                        
                        <form method="POST" class="mt-3">
                            <div class="row g-2">
                                <div class="col-md-8">
                                    <select name="paquete_id" class="form-select" required>
                                        <option value="">Selecciona un paquete con repartidor asignado...</option>
                                        <?php
                                        if ($tabla_existe) {
                                            $stmt = $db->prepare("
                                                SELECT p.id, p.codigo_seguimiento, p.destinatario_nombre, p.destinatario_telefono
                                                FROM paquetes p
                                                WHERE p.repartidor_id IS NOT NULL
                                                ORDER BY p.id DESC
                                                LIMIT 10
                                            ");
                                            $stmt->execute();
                                            $paquetes = $stmt->get_result();
                                            
                                            while ($pkg = $paquetes->fetch_assoc()) {
                                                echo "<option value='{$pkg['id']}'>{$pkg['codigo_seguimiento']} - {$pkg['destinatario_nombre']}</option>";
                                            }
                                            $stmt->close();
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" name="test_envio" class="btn btn-primary w-100">
                                        <i class="bi bi-play-circle"></i> Probar Envío
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <hr>
                        
                        <h6>5. Información Útil</h6>
                        <div class="alert alert-info">
                            <ul class="mb-0">
                                <li><strong>Modo actual:</strong> <?php echo defined('WHATSAPP_API_TYPE') ? WHATSAPP_API_TYPE : 'simulado'; ?></li>
                                <li><strong>Para producción:</strong> Edita <code>config/config.php</code> y agrega credenciales de API</li>
                                <li><strong>Ver logs:</strong> Busca "📱 [WhatsApp" en el error_log de PHP</li>
                                <li><strong>Documentación:</strong> Lee <code>WHATSAPP_SETUP.md</code></li>
                            </ul>
                        </div>
                        
                    </div>
                    
                    <div class="card-footer">
                        <a href="admin/paquetes.php" class="btn btn-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Volver a Paquetes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
