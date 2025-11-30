<?php
require_once '../config/config.php';
requireRole(['asistente']);

$pageTitle = 'Importar Excel';

// Obtener últimas importaciones de Excel
$db = Database::getInstance()->getConnection();
$sql = "SELECT * FROM importaciones_excel ORDER BY fecha_importacion DESC LIMIT 10";
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
                <div>
                    <h1><i class="bi bi-file-earmark-excel"></i> <?php echo $pageTitle; ?></h1>
                </div>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalImportarExcel">
                        <i class="bi bi-upload"></i> Subir Archivo Excel
                    </button>
                </div>
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

            <!-- Historial de Importaciones Excel -->
            <?php if (!empty($importaciones)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Historial de Importaciones Excel</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Archivo</th>
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
                                    <td>
                                        <i class="bi bi-file-earmark-excel text-success"></i>
                                        <?php echo htmlspecialchars($imp['nombre_archivo'] ?? 'archivo.xlsx'); ?>
                                    </td>
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
                    <i class="bi bi-file-earmark-excel" style="font-size: 4rem; color: #ccc;"></i>
                    <p class="text-muted mt-3">No hay importaciones Excel registradas</p>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    
    <!-- Modal Importar Excel -->
    <div class="modal fade" id="modalImportarExcel" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-file-earmark-excel"></i> Importar Archivo Excel</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="../admin/importar_excel_procesar.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label"><strong>Selecciona archivo Excel *</strong></label>
                            <input type="file" name="archivo" class="form-control" accept=".xlsx,.xls" required>
                            <small class="text-muted">Formatos soportados: .xlsx, .xls | Tamaño máximo: 10MB</small>
                        </div>
                        
                        <div class="alert alert-info">
                            <h6><strong>📋 Formato esperado del Excel:</strong></h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="small mb-0">
                                        <li><strong>Columna A:</strong> Código SAVAR</li>
                                        <li><strong>Columna D:</strong> Departamento</li>
                                        <li><strong>Columna E:</strong> Provincia</li>
                                        <li><strong>Columna F:</strong> Distrito</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="small mb-0">
                                        <li><strong>Columna J:</strong> Consignado (nombre)</li>
                                        <li><strong>Columna K:</strong> Dirección</li>
                                        <li><strong>Columna M:</strong> Peso</li>
                                        <li><strong>Columna N:</strong> Teléfono</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <h6><strong>⚠️ Importante:</strong></h6>
                            <ul class="small mb-0">
                                <li>La primera fila debe contener los encabezados</li>
                                <li>No debe haber filas vacías entre los datos</li>
                                <li>Los códigos SAVAR no deben repetirse</li>
                                <li>El proceso puede tomar varios minutos dependiendo del tamaño</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-upload"></i> Procesar Archivo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>