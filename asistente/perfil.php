<?php
require_once '../config/config.php';
requireRole(['asistente']);

$db = Database::getInstance()->getConnection();
$asistente_id = $_SESSION['usuario_id'];

// Obtener información del asistente
$stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $asistente_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

if (!$usuario) {
    $_SESSION['error'] = 'Usuario no encontrado';
    header('Location: dashboard.php');
    exit;
}

$pageTitle = "Mi Perfil";
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
                <h1><i class="bi bi-person-circle"></i> <?php echo $pageTitle; ?></h1>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Información Personal -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <?php if (!empty($usuario['foto_perfil']) && file_exists("../uploads/perfiles/{$usuario['foto_perfil']}")): ?>
                                    <img src="../uploads/perfiles/<?php echo $usuario['foto_perfil']; ?>" 
                                         class="rounded-circle" 
                                         style="width: 150px; height: 150px; object-fit: cover;"
                                         alt="Foto de perfil">
                                <?php else: ?>
                                    <div class="rounded-circle bg-warning text-white d-inline-flex align-items-center justify-content-center"
                                         style="width: 150px; height: 150px; font-size: 3rem;">
                                        <?php echo strtoupper(substr($usuario['nombre'], 0, 1) . substr($usuario['apellido'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <h4><?php echo $usuario['nombre'] . ' ' . $usuario['apellido']; ?></h4>
                            <p class="text-muted mb-2">
                                <i class="bi bi-person-badge"></i> Asistente Administrativo
                            </p>
                            <span class="badge bg-<?php echo $usuario['estado'] == 'activo' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($usuario['estado']); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Información de Cuenta -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-info-circle"></i> Información de Cuenta</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <small class="text-muted">Fecha de Registro:</small><br>
                                <strong><?php echo date('d/m/Y H:i', strtotime($usuario['fecha_registro'])); ?></strong>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted">Último Acceso:</small><br>
                                <strong>
                                    <?php echo $usuario['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_acceso'])) : 'Nunca'; ?>
                                </strong>
                            </div>
                            <div>
                                <small class="text-muted">ID Usuario:</small><br>
                                <strong>#<?php echo $usuario['id']; ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Datos Personales -->
                <div class="col-md-8 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-person-lines-fill"></i> Datos Personales</h5>
                        </div>
                        <div class="card-body">
                            <form action="perfil_actualizar.php" method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nombre *</label>
                                        <input type="text" class="form-control" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Apellido *</label>
                                        <input type="text" class="form-control" name="apellido" value="<?php echo htmlspecialchars($usuario['apellido']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email *</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Teléfono</label>
                                        <input type="text" class="form-control" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Foto de Perfil</label>
                                        <input type="file" class="form-control" name="foto_perfil" accept="image/*">
                                        <small class="text-muted">Formatos permitidos: JPG, PNG. Tamaño máximo: 2MB</small>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <h6 class="mb-3"><i class="bi bi-key"></i> Cambiar Contraseña</h6>
                                <p class="text-muted small">Deja estos campos en blanco si no deseas cambiar tu contraseña</p>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nueva Contraseña</label>
                                        <input type="password" class="form-control" name="nueva_password" minlength="6" placeholder="Mínimo 6 caracteres">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Confirmar Nueva Contraseña</label>
                                        <input type="password" class="form-control" name="confirmar_password" placeholder="Repetir contraseña">
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Guardar Cambios
                                    </button>
                                    <a href="dashboard.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> Cancelar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
