<?php
require_once '../config/config.php';
requireRole(['admin', 'asistente']);

$pageTitle = 'Importar desde SAVAR';

// Obtener últimas importaciones
$db = Database::getInstance()->getConnection();
$sql = "SELECT * FROM importaciones_savar ORDER BY fecha_importacion DESC LIMIT 10";
$stmt = $db->query($sql);
$importaciones = Database::getInstance()->fetchAll($stmt);
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
                <h1><i class="bi bi-cloud-download"></i> Importar desde SAVAR</h1>
                <p class="text-muted">Extrae datos automáticamente del sistema SAVAR usando Python + Selenium</p>
            </div>

            <!-- Instrucciones -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-info-circle"></i> Instrucciones de Importación</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h6><i class="bi bi-exclamation-triangle"></i> Requisitos previos:</h6>
                                <ul class="mb-0">
                                    <li>Python 3.8+ instalado en el servidor</li>
                                    <li>Dependencias instaladas: <code>pip install -r python/requirements.txt</code></li>
                                    <li>Credenciales de SAVAR configuradas en <code>python/savar_importer.py</code></li>
                                    <li>Google Chrome instalado (para Selenium)</li>
                                </ul>
                            </div>

                            <h6 class="mt-3"><i class="bi bi-terminal"></i> Métodos de importación:</h6>
                            
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title"><i class="bi bi-1-circle-fill text-primary"></i> Desde Terminal (Recomendado)</h6>
                                            <p class="small text-muted">Ejecuta el script directamente desde la línea de comandos</p>
                                            <div class="bg-dark text-light p-3 rounded">
                                                <code>cd c:\xampp\htdocs\NUEVOOO\python</code><br>
                                                <code>python savar_importer.py</code>
                                            </div>
                                            <p class="small mt-2 mb-0">
                                                <i class="bi bi-check-circle text-success"></i> Más control sobre el proceso<br>
                                                <i class="bi bi-check-circle text-success"></i> Ver logs en tiempo real<br>
                                                <i class="bi bi-check-circle text-success"></i> Screenshots de depuración
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title"><i class="bi bi-2-circle-fill text-success"></i> Desde el Navegador</h6>
                                            <p class="small text-muted">Ejecuta el script mediante PHP exec() (requiere permisos)</p>
                                            <form method="POST" action="importar_procesar.php" onsubmit="return confirm('¿Iniciar importación desde SAVAR?\n\nEsto puede tardar varios minutos.');">
                                                <div class="mb-3">
                                                    <label class="form-label">Fecha de inicio:</label>
                                                    <input type="date" name="fecha_inicio" class="form-control" 
                                                           value="<?php echo date('Y-m-d', strtotime('-1 day')); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Fecha de fin:</label>
                                                    <input type="date" name="fecha_fin" class="form-control" 
                                                           value="<?php echo date('Y-m-d'); ?>" required>
                                                </div>
                                                <button type="submit" class="btn btn-success w-100">
                                                    <i class="bi bi-play-fill"></i> Ejecutar Importación
                                                </button>
                                            </form>
                                            <p class="small mt-2 mb-0 text-warning">
                                                <i class="bi bi-exclamation-triangle"></i> Puede no funcionar si exec() está deshabilitado en PHP
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-warning mt-3">
                                <i class="bi bi-lightbulb"></i> <strong>Tip:</strong> 
                                Para automatizar la importación diaria, configura una tarea programada (Task Scheduler en Windows o cron en Linux).
                                Ver documentación en <code>python/README_SAVAR.md</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Historial de Importaciones -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Historial de Importaciones</h5>
                            <button class="btn btn-sm btn-outline-primary" onclick="location.reload()">
                                <i class="bi bi-arrow-clockwise"></i> Actualizar
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (count($importaciones) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Fecha Importación</th>
                                                <th>Total Registros</th>
                                                <th>Procesados</th>
                                                <th>Fallidos</th>
                                                <th>Estado</th>
                                                <th>Procesado Por</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($importaciones as $imp): ?>
                                                <tr>
                                                    <td><?php echo $imp['id']; ?></td>
                                                    <td><?php echo formatDate($imp['fecha_importacion']); ?></td>
                                                    <td><span class="badge bg-primary"><?php echo $imp['total_registros']; ?></span></td>
                                                    <td><span class="badge bg-success"><?php echo $imp['registros_procesados']; ?></span></td>
                                                    <td><span class="badge bg-danger"><?php echo $imp['registros_fallidos']; ?></span></td>
                                                    <td>
                                                        <?php
                                                        $estadoClasses = [
                                                            'pendiente' => 'warning',
                                                            'procesando' => 'info',
                                                            'completado' => 'success',
                                                            'error' => 'danger'
                                                        ];
                                                        $estadoClass = $estadoClasses[$imp['estado']] ?? 'secondary';
                                                        ?>
                                                        <span class="badge bg-<?php echo $estadoClass; ?>">
                                                            <?php echo ucfirst($imp['estado']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        if ($imp['procesado_por']) {
                                                            $sqlUser = "SELECT nombre, apellido FROM usuarios WHERE id = ?";
                                                            $stmtUser = $db->prepare($sqlUser);
                                                            $stmtUser->execute([$imp['procesado_por']]);
                                                            $user = $stmtUser->fetch();
                                                            echo $user ? $user['nombre'] . ' ' . $user['apellido'] : 'Sistema';
                                                        } else {
                                                            echo 'Sistema';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-info" onclick="verDetalles(<?php echo $imp['id']; ?>)">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <?php if ($imp['errores']): ?>
                                                            <button class="btn btn-sm btn-warning" onclick="verErrores(<?php echo $imp['id']; ?>)">
                                                                <i class="bi bi-exclamation-triangle"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-inbox" style="font-size: 4rem; color: #ddd;"></i>
                                    <p class="text-muted mt-3">No hay importaciones registradas aún</p>
                                    <p class="small">Ejecuta el script <code>python/savar_importer.py</code> para comenzar</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documentación rápida -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-book"></i> Documentación</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <h6><i class="bi bi-file-earmark-code"></i> Archivos principales</h6>
                                    <ul class="small">
                                        <li><code>python/savar_importer.py</code> - Script principal</li>
                                        <li><code>python/requirements.txt</code> - Dependencias</li>
                                        <li><code>python/README_SAVAR.md</code> - Documentación completa</li>
                                    </ul>
                                </div>
                                <div class="col-md-4">
                                    <h6><i class="bi bi-gear"></i> Configuración</h6>
                                    <ul class="small">
                                        <li>Usuario SAVAR: <code>CHI.HER</code></li>
                                        <li>Base de datos: <code>hermes_express</code></li>
                                        <li>Tabla destino: <code>paquetes</code></li>
                                    </ul>
                                </div>
                                <div class="col-md-4">
                                    <h6><i class="bi bi-link-45deg"></i> Enlaces útiles</h6>
                                    <ul class="small">
                                        <li><a href="https://app.savarexpress.com.pe" target="_blank">Sistema SAVAR <i class="bi bi-box-arrow-up-right"></i></a></li>
                                        <li><a href="../python/README_SAVAR.md" target="_blank">Documentación completa</a></li>
                                        <li><a href="paquetes.php">Ver paquetes importados</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal de detalles -->
    <div class="modal fade" id="modalDetalles" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles de Importación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalDetallesContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function verDetalles(id) {
            const modal = new bootstrap.Modal(document.getElementById('modalDetalles'));
            modal.show();
            
            fetch(`importar_detalles.php?id=${id}`)
                .then(r => r.text())
                .then(html => {
                    document.getElementById('modalDetallesContent').innerHTML = html;
                })
                .catch(e => {
                    document.getElementById('modalDetallesContent').innerHTML = 
                        '<div class="alert alert-danger">Error al cargar detalles</div>';
                });
        }

        function verErrores(id) {
            const modal = new bootstrap.Modal(document.getElementById('modalDetalles'));
            modal.show();
            
            fetch(`importar_errores.php?id=${id}`)
                .then(r => r.text())
                .then(html => {
                    document.getElementById('modalDetallesContent').innerHTML = html;
                })
                .catch(e => {
                    document.getElementById('modalDetallesContent').innerHTML = 
                        '<div class="alert alert-danger">Error al cargar errores</div>';
                });
        }
    </script>
</body>
</html>
