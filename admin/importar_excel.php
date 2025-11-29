<?php
require_once '../config/config.php';
requireRole(['admin', 'asistente']);

$pageTitle = 'Importación Masiva de Paquetes';

$db = Database::getInstance()->getConnection();

// Obtener importaciones recientes
$importaciones = Database::getInstance()->fetchAll($db->query(
    "SELECT ia.*, u.nombre, u.apellido 
     FROM importaciones_archivos ia
     LEFT JOIN usuarios u ON ia.procesado_por = u.id
     ORDER BY ia.fecha_importacion DESC 
     LIMIT 20"
));
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
                    <p class="text-muted">Importar paquetes desde archivos Excel de SAVAR</p>
                </div>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalImportar">
                        <i class="bi bi-upload"></i> Nueva Importación
                    </button>
                </div>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Instrucciones -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Instrucciones de Importación</h5>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold">📋 Formato del Archivo Excel de SAVAR:</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Columnas que se importan:</strong></p>
                            <ul class="small">
                                <li><strong>Columna A:</strong> Código (ej: SVBFE00007)</li>
                                <li><strong>Columna D:</strong> Departamento</li>
                                <li><strong>Columna E:</strong> Provincia</li>
                                <li><strong>Columna F:</strong> Distrito</li>
                                <li><strong>Columna J:</strong> Consignado (nombre)</li>
                                <li><strong>Columna K:</strong> Dirección Consignado</li>
                                <li><strong>Columna M:</strong> Peso</li>
                                <li><strong>Columna N:</strong> Teléfono</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Datos que se muestran en el sistema:</strong></p>
                            <ul class="small">
                                <li>Código de seguimiento</li>
                                <li>Departamento, Provincia y Distrito</li>
                                <li>Nombre del consignado</li>
                                <li>Dirección completa</li>
                                <li>Peso en kg</li>
                                <li>Teléfono del destinatario</li>
                            </ul>
                        </div>
                    </div>
                    <div class="alert alert-warning small mb-0">
                        <i class="bi bi-exclamation-triangle"></i> 
                        <strong>Importante:</strong> El archivo Excel puede tener todas las columnas de SAVAR. 
                        El sistema solo lee las columnas A, D, E, F, J, K, M, N y las demás se ignoran.
                    </div>
                </div>
            </div>

            <!-- Historial de Importaciones -->
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
                                    <th>Archivo</th>
                                    <th>Fecha</th>
                                    <th>Total</th>
                                    <th>Importados</th>
                                    <th>Fallidos</th>
                                    <th>Estado</th>
                                    <th>Usuario</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($importaciones)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No hay importaciones registradas</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($importaciones as $imp): ?>
                                    <tr>
                                        <td><?php echo $imp['id']; ?></td>
                                        <td>
                                            <i class="bi bi-file-excel text-success"></i>
                                            <?php echo htmlspecialchars($imp['nombre_archivo']); ?>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($imp['fecha_importacion'])); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo $imp['total_registros']; ?></span></td>
                                        <td><span class="badge bg-success"><?php echo $imp['registros_importados']; ?></span></td>
                                        <td>
                                            <?php if ($imp['registros_fallidos'] > 0): ?>
                                                <span class="badge bg-danger"><?php echo $imp['registros_fallidos']; ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $badges = [
                                                'pendiente' => 'warning',
                                                'procesando' => 'info',
                                                'completado' => 'success',
                                                'error' => 'danger'
                                            ];
                                            ?>
                                            <span class="badge bg-<?php echo $badges[$imp['estado']]; ?>">
                                                <?php echo ucfirst($imp['estado']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $imp['nombre'] ? $imp['nombre'] . ' ' . $imp['apellido'] : '-'; ?></td>
                                        <td>
                                            <a href="../uploads/importaciones/<?php echo $imp['nombre_archivo']; ?>" class="btn btn-sm btn-info" download>
                                                <i class="bi bi-download"></i>
                                            </a>
                                            <?php if ($imp['registros_fallidos'] > 0): ?>
                                                <button class="btn btn-sm btn-warning" onclick="verErrores(<?php echo $imp['id']; ?>)">
                                                    <i class="bi bi-exclamation-triangle"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Importar -->
    <div class="modal fade" id="modalImportar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Importar Archivo Excel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="importar_excel_procesar.php" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="alert alert-info small">
                            <strong><i class="bi bi-info-circle"></i> Formato del Excel:</strong>
                            <div class="mt-2">
                                <table class="table table-sm table-bordered mb-0" style="font-size: 0.85rem;">
                                    <thead>
                                        <tr>
                                            <th>Columna</th>
                                            <th>Dato</th>
                                            <th>Requerido</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>A</strong></td>
                                            <td>Código de Seguimiento</td>
                                            <td><span class="badge bg-danger">Sí</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>D</strong></td>
                                            <td>Departamento</td>
                                            <td><span class="badge bg-secondary">No</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>E</strong></td>
                                            <td>Provincia</td>
                                            <td><span class="badge bg-secondary">No</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>F</strong></td>
                                            <td>Distrito</td>
                                            <td><span class="badge bg-secondary">No</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>J</strong></td>
                                            <td>Nombre Consignado</td>
                                            <td><span class="badge bg-danger">Sí</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>K</strong></td>
                                            <td>Dirección</td>
                                            <td><span class="badge bg-secondary">No</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>M</strong></td>
                                            <td>Peso (kg)</td>
                                            <td><span class="badge bg-secondary">No</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>N</strong></td>
                                            <td>Teléfono</td>
                                            <td><span class="badge bg-secondary">No</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Archivo Excel *</label>
                            <input type="file" name="archivo_excel" class="form-control" accept=".xlsx,.xls" required>
                            <small class="text-muted">Formatos: .xlsx, .xls (Máx. 10MB)</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2" placeholder="Notas sobre esta importación..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload"></i> Importar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        function verErrores(id) {
            window.location.href = 'importar_errores_ver.php?id=' + id;
        }
    </script>
</body>
</html>
