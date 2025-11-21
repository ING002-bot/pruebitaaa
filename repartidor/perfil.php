<?php
require_once '../config/config.php';
requireRole('repartidor');

$db = Database::getInstance()->getConnection();
$repartidor_id = $_SESSION['usuario_id'];

// Obtener información del repartidor
$stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $repartidor_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

if (!$usuario) {
    setFlashMessage('danger', 'Usuario no encontrado');
    redirect(APP_URL . 'repartidor/dashboard.php');
}

// Obtener estadísticas del repartidor
$stmt_stats = $db->prepare("
    SELECT 
        COUNT(DISTINCT e.id) as total_entregas,
        SUM(CASE WHEN e.tipo_entrega = 'exitosa' THEN 1 ELSE 0 END) as entregas_exitosas,
        SUM(CASE WHEN e.tipo_entrega = 'rechazada' THEN 1 ELSE 0 END) as entregas_rechazadas,
        COUNT(DISTINCT p.id) as paquetes_asignados,
        SUM(CASE WHEN p.estado = 'entregado' THEN 1 ELSE 0 END) as paquetes_entregados
    FROM usuarios u
    LEFT JOIN entregas e ON u.id = e.repartidor_id
    LEFT JOIN paquetes p ON u.id = p.repartidor_id
    WHERE u.id = ?
");
$stmt_stats->bind_param("i", $repartidor_id);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();

$pageTitle = "Mi Perfil";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - HERMES EXPRESS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="page-content">
            <div class="page-title">
                <h1><i class="bi bi-person-circle"></i> Mi Perfil</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Mi Perfil</li>
                    </ol>
                </nav>
            </div>

            <?php if (isset($_SESSION['flash_message'])): 
                $flash = $_SESSION['flash_message'];
                unset($_SESSION['flash_message']);
            ?>
                <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($flash['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Información Personal -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <?php if (!empty($usuario['foto_perfil']) && $usuario['foto_perfil'] != 'default-avatar.svg'): ?>
                                    <img src="../uploads/perfiles/<?php echo $usuario['foto_perfil']; ?>" 
                                         class="rounded-circle" 
                                         style="width: 150px; height: 150px; object-fit: cover;"
                                         alt="Foto de perfil">
                                <?php else: ?>
                                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center"
                                         style="width: 150px; height: 150px; font-size: 3rem;">
                                        <?php echo strtoupper(substr($usuario['nombre'], 0, 1) . substr($usuario['apellido'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <h4><?php echo $usuario['nombre'] . ' ' . $usuario['apellido']; ?></h4>
                            <p class="text-muted mb-2">
                                <i class="bi bi-person-badge"></i> Repartidor
                            </p>
                            <span class="badge bg-<?php echo $usuario['estado'] == 'activo' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($usuario['estado']); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Estadísticas Rápidas -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-graph-up"></i> Mis Estadísticas</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">Total Entregas</span>
                                <strong class="text-primary"><?php echo $stats['total_entregas']; ?></strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">Entregas Exitosas</span>
                                <strong class="text-success"><?php echo $stats['entregas_exitosas']; ?></strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">Entregas Rechazadas</span>
                                <strong class="text-danger"><?php echo $stats['entregas_rechazadas']; ?></strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Paquetes Entregados</span>
                                <strong class="text-info"><?php echo $stats['paquetes_entregados']; ?></strong>
                            </div>
                            <?php if ($stats['total_entregas'] > 0): ?>
                                <?php $tasa_exito = round(($stats['entregas_exitosas'] / $stats['total_entregas']) * 100, 1); ?>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Tasa de Éxito</span>
                                    <strong class="text-success"><?php echo $tasa_exito; ?>%</strong>
                                </div>
                            <?php endif; ?>
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
                                        <label class="form-label">Nombre</label>
                                        <input type="text" class="form-control" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Apellido</label>
                                        <input type="text" class="form-control" name="apellido" value="<?php echo htmlspecialchars($usuario['apellido']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Teléfono</label>
                                        <input type="text" class="form-control" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono']); ?>">
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Foto de Perfil</label>
                                        <input type="file" class="form-control" name="foto_perfil" accept="image/*">
                                        <small class="text-muted">Deja en blanco si no deseas cambiar la foto</small>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <h6 class="mb-3"><i class="bi bi-key"></i> Cambiar Contraseña</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nueva Contraseña</label>
                                        <input type="password" class="form-control" name="nueva_password" placeholder="Dejar en blanco para no cambiar">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Confirmar Contraseña</label>
                                        <input type="password" class="form-control" name="confirmar_password" placeholder="Confirmar nueva contraseña">
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

                    <!-- Información de Cuenta -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-info-circle"></i> Información de Cuenta</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <small class="text-muted">Fecha de Registro:</small><br>
                                    <strong><?php echo date('d/m/Y H:i', strtotime($usuario['fecha_registro'])); ?></strong>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <small class="text-muted">Último Acceso:</small><br>
                                    <strong>
                                        <?php echo $usuario['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_acceso'])) : 'Nunca'; ?>
                                    </strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
