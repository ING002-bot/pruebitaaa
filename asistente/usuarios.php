<?php
require_once '../config/config.php';
requireRole(['asistente']);

$pageTitle = 'Gestión de Usuarios';

$db = Database::getInstance()->getConnection();
$usuarios = Database::getInstance()->fetchAll($db->query("SELECT * FROM usuarios ORDER BY fecha_registro DESC"));
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
                <h1><i class="bi bi-people"></i> <?php echo $pageTitle; ?></h1>
                <span class="badge bg-info">Solo lectura y edición</span>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Último Acceso</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td>
                                        <img src="../uploads/perfiles/<?php echo $user['foto_perfil'] ?: 'default.png'; ?>" width="30" height="30" class="rounded-circle me-2" onerror="this.src='../uploads/perfiles/default.png'">
                                        <?php echo $user['nombre'] . ' ' . $user['apellido']; ?>
                                    </td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td><?php echo $user['telefono'] ?: '-'; ?></td>
                                    <td>
                                        <?php
                                        $badges = ['admin' => 'danger', 'asistente' => 'warning', 'repartidor' => 'info'];
                                        ?>
                                        <span class="badge bg-<?php echo $badges[$user['rol']]; ?>">
                                            <?php echo ucfirst($user['rol']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $estados = ['activo' => 'success', 'inactivo' => 'secondary', 'suspendido' => 'danger'];
                                        ?>
                                        <span class="badge bg-<?php echo $estados[$user['estado']]; ?>">
                                            <?php echo ucfirst($user['estado']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $user['ultimo_acceso'] ? formatDate($user['ultimo_acceso']) : 'Nunca'; ?></td>
                                    <td>
                                        <?php if ($user['id'] == 1 && $user['rol'] == 'admin'): ?>
                                            <span class="badge bg-secondary">
                                                <i class="bi bi-shield-lock"></i> Protegido
                                            </span>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-warning" onclick="editarUsuario(<?php echo $user['id']; ?>)">
                                                <i class="bi bi-pencil"></i> Editar
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Editar Usuario -->
    <div class="modal fade" id="modalEditar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="usuario_actualizar.php">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre *</label>
                                <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Apellido *</label>
                                <input type="text" name="apellido" id="edit_apellido" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" id="edit_telefono" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estado *</label>
                            <select name="estado" id="edit_estado" class="form-select" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nueva Contraseña</label>
                            <input type="password" name="password" class="form-control" minlength="6">
                            <small class="text-muted">Dejar en blanco para no cambiar</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        function editarUsuario(id) {
            // Proteger admin principal
            if (id == 1) {
                alert('El usuario administrador principal está protegido y no puede ser modificado.');
                return;
            }
            
            fetch(`usuario_obtener.php?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    
                    document.getElementById('edit_id').value = data.id;
                    document.getElementById('edit_nombre').value = data.nombre;
                    document.getElementById('edit_apellido').value = data.apellido;
                    document.getElementById('edit_email').value = data.email;
                    document.getElementById('edit_telefono').value = data.telefono || '';
                    document.getElementById('edit_estado').value = data.estado;
                    
                    new bootstrap.Modal(document.getElementById('modalEditar')).show();
                })
                .catch(err => {
                    alert('Error al cargar datos del usuario');
                });
        }
    </script>
</body>
</html>
