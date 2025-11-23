<?php
require_once '../config/config.php';
requireRole(['asistente']);

$pageTitle = 'Importar Paquetes';

// Obtener últimas importaciones
$db = Database::getInstance()->getConnection();
$sql = "SELECT * FROM importaciones_savar ORDER BY fecha_importacion DESC LIMIT 10";
$stmt = $db->query($sql);
if ($stmt) {
    $importaciones = Database::getInstance()->fetchAll($stmt);
    $stmt->free_result();
} else {
    $importaciones = [];
}
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
                <h1><i class="bi bi-file-earmark-arrow-up"></i> <?php echo $pageTitle; ?></h1>
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

            <!-- Instrucciones -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Instrucciones de Importación</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-0">
                        <h6><i class="bi bi-exclamation-triangle"></i> Importante:</h6>
                        <ul class="mb-0">
                            <li>La importación automática desde SAVAR requiere configuración del script Python</li>
                            <li>Contacta al administrador para ejecutar importaciones desde el sistema SAVAR</li>
                            <li>Puedes revisar el historial de importaciones previas más abajo</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Historial de Importaciones -->
            <?php if (!empty($importaciones)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Historial de Importaciones</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha Importación</th>
                                    <th>Paquetes Importados</th>
                                    <th>Errores</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($importaciones as $imp): ?>
                                <tr>
                                    <td>#<?php echo $imp['id']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($imp['fecha_importacion'])); ?></td>
                                    <td>
                                        <span class="badge bg-success"><?php echo $imp['paquetes_importados'] ?? 0; ?></span>
                                    </td>
                                    <td>
                                        <?php if (($imp['errores'] ?? 0) > 0): ?>
                                            <span class="badge bg-danger"><?php echo $imp['errores']; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $estado = $imp['estado'] ?? 'completada';
                                        $badges = ['completada' => 'success', 'con_errores' => 'warning', 'fallida' => 'danger'];
                                        ?>
                                        <span class="badge bg-<?php echo $badges[$estado] ?? 'secondary'; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $estado)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (($imp['errores'] ?? 0) > 0): ?>
                                            <a href="../admin/importar_errores.php?id=<?php echo $imp['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="bi bi-exclamation-triangle"></i> Ver Errores
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                    <p class="text-muted mt-3">No hay importaciones registradas</p>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
