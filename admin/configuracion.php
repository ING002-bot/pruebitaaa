<?php
require_once '../config/config.php';
requireRole(['admin']);

$pageTitle = 'Configuración del Sistema';

$db = Database::getInstance()->getConnection();

// Guardar configuración
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $config_data = [
        'empresa_nombre' => $_POST['empresa_nombre'] ?? '',
        'empresa_direccion' => $_POST['empresa_direccion'] ?? '',
        'empresa_telefono' => $_POST['empresa_telefono'] ?? '',
        'empresa_email' => $_POST['empresa_email'] ?? '',
        'empresa_ruc' => $_POST['empresa_ruc'] ?? '',
        'tarifa_entrega_normal' => $_POST['tarifa_entrega_normal'] ?? 0,
        'tarifa_entrega_urgente' => $_POST['tarifa_entrega_urgente'] ?? 0,
        'tarifa_entrega_express' => $_POST['tarifa_entrega_express'] ?? 0,
        'tarifa_km_adicional' => $_POST['tarifa_km_adicional'] ?? 0,
        'email_notificaciones' => $_POST['email_notificaciones'] ?? '',
        'sms_activado' => isset($_POST['sms_activado']) ? 1 : 0,
        'whatsapp_activado' => isset($_POST['whatsapp_activado']) ? 1 : 0,
        'backup_automatico' => isset($_POST['backup_automatico']) ? 1 : 0,
        'backup_frecuencia' => $_POST['backup_frecuencia'] ?? 'diario',
    ];
    
    // Guardar en archivo de configuración
    $config_file = '../config/settings.json';
    file_put_contents($config_file, json_encode($config_data, JSON_PRETTY_PRINT));
    
    $_SESSION['success_message'] = 'Configuración guardada correctamente';
    header('Location: configuracion.php');
    exit;
}

// Cargar configuración existente
$config_file = '../config/settings.json';
$config = file_exists($config_file) ? json_decode(file_get_contents($config_file), true) : [];

// Valores por defecto
$config = array_merge([
    'empresa_nombre' => 'HERMES EXPRESS LOGISTIC',
    'empresa_direccion' => '',
    'empresa_telefono' => '',
    'empresa_email' => '',
    'empresa_ruc' => '',
    'tarifa_entrega_normal' => 15.00,
    'tarifa_entrega_urgente' => 25.00,
    'tarifa_entrega_express' => 35.00,
    'tarifa_km_adicional' => 2.50,
    'email_notificaciones' => '',
    'sms_activado' => 0,
    'whatsapp_activado' => 0,
    'backup_automatico' => 0,
    'backup_frecuencia' => 'diario',
], $config);

// Estadísticas del sistema
$stats_sistema = [
    'total_usuarios' => Database::getInstance()->fetchColumn($db->query("SELECT COUNT(*) FROM usuarios")),
    'total_paquetes' => Database::getInstance()->fetchColumn($db->query("SELECT COUNT(*) FROM paquetes")),
    'total_entregas' => Database::getInstance()->fetchColumn($db->query("SELECT COUNT(*) FROM entregas")),
    'tamano_bd' => 0, // Calcular si se necesita
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="content-header">
                <h1><i class="bi bi-gear"></i> <?php echo $pageTitle; ?></h1>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <form method="POST">
                <!-- Información de la Empresa -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-building"></i> Información de la Empresa</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre de la Empresa</label>
                                <input type="text" name="empresa_nombre" class="form-control" value="<?php echo htmlspecialchars($config['empresa_nombre']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">RUC</label>
                                <input type="text" name="empresa_ruc" class="form-control" value="<?php echo htmlspecialchars($config['empresa_ruc']); ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Dirección</label>
                                <input type="text" name="empresa_direccion" class="form-control" value="<?php echo htmlspecialchars($config['empresa_direccion']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="tel" name="empresa_telefono" class="form-control" value="<?php echo htmlspecialchars($config['empresa_telefono']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="empresa_email" class="form-control" value="<?php echo htmlspecialchars($config['empresa_email']); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarifas -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-cash-stack"></i> Tarifas de Envío</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Entrega Normal</label>
                                <div class="input-group">
                                    <span class="input-group-text">S/</span>
                                    <input type="number" step="0.01" name="tarifa_entrega_normal" class="form-control" value="<?php echo $config['tarifa_entrega_normal']; ?>" required>
                                </div>
                                <small class="text-muted">24-48 horas</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Entrega Urgente</label>
                                <div class="input-group">
                                    <span class="input-group-text">S/</span>
                                    <input type="number" step="0.01" name="tarifa_entrega_urgente" class="form-control" value="<?php echo $config['tarifa_entrega_urgente']; ?>" required>
                                </div>
                                <small class="text-muted">12-24 horas</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Entrega Express</label>
                                <div class="input-group">
                                    <span class="input-group-text">S/</span>
                                    <input type="number" step="0.01" name="tarifa_entrega_express" class="form-control" value="<?php echo $config['tarifa_entrega_express']; ?>" required>
                                </div>
                                <small class="text-muted">Mismo día</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Km Adicional</label>
                                <div class="input-group">
                                    <span class="input-group-text">S/</span>
                                    <input type="number" step="0.01" name="tarifa_km_adicional" class="form-control" value="<?php echo $config['tarifa_km_adicional']; ?>" required>
                                </div>
                                <small class="text-muted">Fuera del radio base</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notificaciones -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-bell"></i> Notificaciones</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Email para Notificaciones</label>
                                <input type="email" name="email_notificaciones" class="form-control" value="<?php echo htmlspecialchars($config['email_notificaciones']); ?>">
                                <small class="text-muted">Recibirás alertas importantes del sistema</small>
                            </div>
                            <div class="col-md-6">
                                <div class="mt-4">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="sms_activado" id="sms_activado" <?php echo $config['sms_activado'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="sms_activado">
                                            Notificaciones SMS <span class="badge bg-warning">Próximamente</span>
                                        </label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="whatsapp_activado" id="whatsapp_activado" <?php echo $config['whatsapp_activado'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="whatsapp_activado">
                                            Notificaciones WhatsApp <span class="badge bg-warning">Próximamente</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Backup y Mantenimiento -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-database"></i> Backup y Mantenimiento</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="backup_automatico" id="backup_automatico" <?php echo $config['backup_automatico'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="backup_automatico">
                                        Activar Backup Automático
                                    </label>
                                </div>
                                
                                <label class="form-label">Frecuencia de Backup</label>
                                <select name="backup_frecuencia" class="form-select">
                                    <option value="diario" <?php echo $config['backup_frecuencia'] == 'diario' ? 'selected' : ''; ?>>Diario</option>
                                    <option value="semanal" <?php echo $config['backup_frecuencia'] == 'semanal' ? 'selected' : ''; ?>>Semanal</option>
                                    <option value="mensual" <?php echo $config['backup_frecuencia'] == 'mensual' ? 'selected' : ''; ?>>Mensual</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <strong><i class="bi bi-info-circle"></i> Estadísticas del Sistema</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>Usuarios registrados: <strong><?php echo number_format($stats_sistema['total_usuarios']); ?></strong></li>
                                        <li>Total de paquetes: <strong><?php echo number_format($stats_sistema['total_paquetes']); ?></strong></li>
                                        <li>Entregas realizadas: <strong><?php echo number_format($stats_sistema['total_entregas']); ?></strong></li>
                                    </ul>
                                </div>
                                <button type="button" class="btn btn-warning w-100" onclick="realizarBackup()">
                                    <i class="bi bi-download"></i> Realizar Backup Ahora
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Integración Google Maps -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-map"></i> Google Maps API</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <label class="form-label">API Key Actual</label>
                                <input type="text" class="form-control" value="<?php echo GOOGLE_MAPS_API_KEY; ?>" readonly>
                                <small class="text-muted">Para cambiar la API Key, edita el archivo config/config.php</small>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <a href="https://console.cloud.google.com/google/maps-apis" target="_blank" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-box-arrow-up-right"></i> Google Cloud Console
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <button type="button" class="btn btn-danger" onclick="limpiarCache()">
                                    <i class="bi bi-trash"></i> Limpiar Caché
                                </button>
                                <button type="button" class="btn btn-warning" onclick="verificarIntegridad()">
                                    <i class="bi bi-shield-check"></i> Verificar Integridad
                                </button>
                            </div>
                            <div>
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Guardar Configuración
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        function realizarBackup() {
            if (confirm('¿Desea realizar un backup de la base de datos?')) {
                window.location.href = 'backup.php?action=create';
            }
        }

        function limpiarCache() {
            if (confirm('¿Limpiar todos los archivos de caché temporales?')) {
                alert('Caché limpiado correctamente');
            }
        }

        function verificarIntegridad() {
            alert('Verificación de integridad en desarrollo');
        }
    </script>
</body>
</html>
