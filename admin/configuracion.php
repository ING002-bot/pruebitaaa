<?php
require_once '../config/config.php';
requireRole(['admin']);

$pageTitle = 'Configuración del Sistema';

$db = Database::getInstance()->getConnection();

// Obtener información del admin
$admin_id = $_SESSION['usuario_id'];
$stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

// Actualizar perfil del admin
if (isset($_POST['actualizar_perfil'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $nueva_password = trim($_POST['nueva_password'] ?? '');
    
    $db->autocommit(false);
    try {
        // Verificar email único
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $admin_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('El email ya está en uso');
        }
        
        // Procesar foto de perfil
        $foto_perfil = null;
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/perfiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $extension = strtolower(pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                throw new Exception('Formato de imagen no permitido');
            }
            
            if ($_FILES['foto_perfil']['size'] > 2 * 1024 * 1024) {
                throw new Exception('La imagen es demasiado grande');
            }
            
            $foto_perfil = 'perfil_' . $admin_id . '_' . time() . '.' . $extension;
            if (!move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $upload_dir . $foto_perfil)) {
                throw new Exception('Error al subir la foto');
            }
        }
        
        // Actualizar datos
        if (!empty($nueva_password)) {
            $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
            if ($foto_perfil) {
                $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, telefono = ?, foto_perfil = ?, password = ? WHERE id = ?");
                $stmt->bind_param("ssssssi", $nombre, $apellido, $email, $telefono, $foto_perfil, $password_hash, $admin_id);
            } else {
                $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, telefono = ?, password = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $nombre, $apellido, $email, $telefono, $password_hash, $admin_id);
            }
        } else {
            if ($foto_perfil) {
                $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, telefono = ?, foto_perfil = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $nombre, $apellido, $email, $telefono, $foto_perfil, $admin_id);
            } else {
                $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, telefono = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $nombre, $apellido, $email, $telefono, $admin_id);
            }
        }
        
        $stmt->execute();
        $db->commit();
        
        $_SESSION['usuario_nombre'] = $nombre . ' ' . $apellido;
        $_SESSION['nombre'] = $nombre;
        $_SESSION['apellido'] = $apellido;
        if ($foto_perfil) {
            $_SESSION['foto_perfil'] = $foto_perfil;
        }
        $_SESSION['success_message'] = 'Perfil actualizado correctamente';
        header('Location: configuracion.php');
        exit;
        
    } catch (Exception $e) {
        $db->rollback();
        $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
        if ($foto_perfil && file_exists('../uploads/perfiles/' . $foto_perfil)) {
            unlink('../uploads/perfiles/' . $foto_perfil);
        }
    }
    $db->autocommit(true);
}

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

            <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-x-circle"></i> <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Tabs de Navegación -->
            <ul class="nav nav-tabs mb-4" id="configTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="perfil-tab" data-bs-toggle="tab" data-bs-target="#perfil" type="button">
                        <i class="bi bi-person-circle"></i> Mi Perfil
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="sistema-tab" data-bs-toggle="tab" data-bs-target="#sistema" type="button">
                        <i class="bi bi-gear"></i> Configuración del Sistema
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="configTabsContent">
                <!-- Tab Mi Perfil -->
                <div class="tab-pane fade show active" id="perfil" role="tabpanel">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <?php if (!empty($admin['foto_perfil']) && file_exists("../uploads/perfiles/{$admin['foto_perfil']}")): ?>
                                                <img src="../uploads/perfiles/<?php echo $admin['foto_perfil']; ?>" 
                                                     class="rounded-circle" 
                                                     style="width: 150px; height: 150px; object-fit: cover;"
                                                     alt="Foto de perfil">
                                            <?php else: ?>
                                                <div class="rounded-circle bg-danger text-white d-inline-flex align-items-center justify-content-center"
                                                     style="width: 150px; height: 150px; font-size: 3rem;">
                                                    <?php echo strtoupper(substr($admin['nombre'], 0, 1) . substr($admin['apellido'], 0, 1)); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <h4><?php echo $admin['nombre'] . ' ' . $admin['apellido']; ?></h4>
                                        <p class="text-muted mb-2">
                                            <i class="bi bi-shield-check"></i> Administrador
                                        </p>
                                        <span class="badge bg-success">Activo</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-8 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="bi bi-person-lines-fill"></i> Datos Personales</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Nombre *</label>
                                                <input type="text" class="form-control" name="nombre" value="<?php echo htmlspecialchars($admin['nombre']); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Apellido *</label>
                                                <input type="text" class="form-control" name="apellido" value="<?php echo htmlspecialchars($admin['apellido']); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Email *</label>
                                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Teléfono</label>
                                                <input type="text" class="form-control" name="telefono" value="<?php echo htmlspecialchars($admin['telefono'] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Foto de Perfil</label>
                                                <input type="file" class="form-control" name="foto_perfil" accept="image/*">
                                                <small class="text-muted">Formatos: JPG, PNG, GIF. Máximo 2MB</small>
                                            </div>
                                        </div>

                                        <hr class="my-4">

                                        <h6 class="mb-3"><i class="bi bi-key"></i> Cambiar Contraseña</h6>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Nueva Contraseña</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" name="nueva_password" id="nueva_password_admin" minlength="6" placeholder="Dejar en blanco para no cambiar">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('nueva_password_admin', this)">
                                                        <i class="bi bi-eye" id="eye-nueva_password_admin"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Confirmar Contraseña</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" name="confirmar_password" id="confirmar_password_admin" placeholder="Repetir contraseña">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirmar_password_admin', this)">
                                                        <i class="bi bi-eye" id="eye-confirmar_password_admin"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-end gap-2 mt-3">
                                            <button type="submit" name="actualizar_perfil" class="btn btn-primary">
                                                <i class="bi bi-save"></i> Guardar Cambios
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tab Configuración del Sistema -->
                <div class="tab-pane fade" id="sistema" role="tabpanel">
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
                        <p class="text-muted mb-3">
                            <i class="bi bi-info-circle"></i> Las tarifas de envío están gestionadas por zonas. 
                            <a href="tarifas.php" class="btn btn-sm btn-primary">
                                <i class="bi bi-tags"></i> Gestionar Tarifas por Zona
                            </a>
                        </p>
                        
                        <div class="alert alert-info">
                            <strong>Sistema de Tarifas por Zona Activo</strong>
                            <p class="mb-0 mt-2">El sistema utiliza tarifas diferenciadas según la zona de entrega. Puedes configurar las tarifas para cada repartidor por zona en el módulo de <strong>Tarifas por Zona</strong>.</p>
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
                </div>
            </div>

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
            if (confirm('¿Limpiar todos los archivos de caché temporales?\n\nEsto eliminará archivos temporales, sesiones antiguas y liberará espacio en disco.')) {
                window.location.href = 'limpiar_cache.php';
            }
        }

        function verificarIntegridad() {
            if (confirm('¿Verificar la integridad del sistema?\n\nEsto comprobará la base de datos, permisos de directorios y optimizará las tablas.')) {
                window.location.href = 'verificar_integridad.php';
            }
        }
        
        function togglePassword(inputId, button) {
            const passwordInput = document.getElementById(inputId);
            const eyeIcon = document.getElementById('eye-' + inputId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('bi-eye');
                eyeIcon.classList.add('bi-eye-slash');
                button.setAttribute('aria-label', 'Ocultar contraseña');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('bi-eye-slash');
                eyeIcon.classList.add('bi-eye');
                button.setAttribute('aria-label', 'Mostrar contraseña');
            }
        }
    </script>
    
    <!-- Chatbot Widget -->
    <?php include 'includes/chatbot_widget.php'; ?>
</body>
</html>

