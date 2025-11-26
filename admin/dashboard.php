<?php
require_once '../config/config.php';
requireRole('admin');

// Obtener estadísticas
$db = Database::getInstance()->getConnection();

// Total de paquetes
$stmt = $db->query("SELECT COUNT(*) as total FROM paquetes");
$totalPaquetes = $stmt->fetch_assoc()['total'];

// Paquetes entregados hoy
$stmt = $db->query("SELECT COUNT(*) as total FROM paquetes WHERE DATE(fecha_entrega) = CURDATE() AND estado = 'entregado'");
$paquetesHoy = $stmt->fetch_assoc()['total'];

// Paquetes en ruta
$stmt = $db->query("SELECT COUNT(*) as total FROM paquetes WHERE estado = 'en_ruta'");
$paquetesEnRuta = $stmt->fetch_assoc()['total'];

// Paquetes rezagados
$stmt = $db->query("SELECT COUNT(*) as total FROM paquetes WHERE estado = 'rezagado'");
$paquetesRezagados = $stmt->fetch_assoc()['total'];

// Ingresos del mes actual
$stmt = $db->query("SELECT COALESCE(SUM(monto), 0) as total FROM ingresos WHERE MONTH(fecha_ingreso) = MONTH(CURDATE()) AND YEAR(fecha_ingreso) = YEAR(CURDATE())");
$ingresosMes = $stmt->fetch_assoc()['total'];

// Gastos del mes actual
$stmt = $db->query("SELECT COALESCE(SUM(monto), 0) as total FROM gastos WHERE MONTH(fecha_gasto) = MONTH(CURDATE()) AND YEAR(fecha_gasto) = YEAR(CURDATE())");
$gastosMes = $stmt->fetch_assoc()['total'];

// Repartidores activos
$stmt = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'repartidor' AND estado = 'activo'");
$repartidoresActivos = $stmt->fetch_assoc()['total'];

// Últimos paquetes
$stmt = $db->query("SELECT p.*, u.nombre, u.apellido FROM paquetes p LEFT JOIN usuarios u ON p.repartidor_id = u.id ORDER BY p.fecha_recepcion DESC LIMIT 10");
$ultimosPaquetes = Database::getInstance()->fetchAll($stmt);

// Paquetes por estado (para gráfico)
$stmt = $db->query("SELECT estado, COUNT(*) as total FROM paquetes GROUP BY estado");
$paquetesPorEstado = Database::getInstance()->fetchAll($stmt);

// Ingresos por día del mes actual (para gráfico)
$stmt = $db->query("SELECT DATE(fecha_ingreso) as fecha, SUM(monto) as total FROM ingresos WHERE MONTH(fecha_ingreso) = MONTH(CURDATE()) AND YEAR(fecha_ingreso) = YEAR(CURDATE()) GROUP BY DATE(fecha_ingreso) ORDER BY fecha");
$ingresosPorDia = Database::getInstance()->fetchAll($stmt);

// Repartidores con más entregas del mes
$stmt = $db->query("SELECT u.nombre, u.apellido, COUNT(e.id) as entregas FROM entregas e JOIN usuarios u ON e.repartidor_id = u.id WHERE MONTH(e.fecha_entrega) = MONTH(CURDATE()) AND YEAR(e.fecha_entrega) = YEAR(CURDATE()) GROUP BY u.id ORDER BY entregas DESC LIMIT 5");
$topRepartidores = Database::getInstance()->fetchAll($stmt);

$pageTitle = "Dashboard";
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-box-seam"></i>
            <h3>HERMES EXPRESS</h3>
            <p>LOGISTIC</p>
        </div>
        
        <div class="sidebar-menu">
            <div class="menu-section">
                <div class="menu-section-title">Principal</div>
                <a href="dashboard.php" class="menu-item active">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="paquetes.php" class="menu-item">
                    <i class="bi bi-box"></i>
                    <span>Paquetes</span>
                    <?php if($paquetesEnRuta > 0): ?>
                        <span class="badge bg-warning"><?php echo $paquetesEnRuta; ?></span>
                    <?php endif; ?>
                </a>
                <a href="rutas.php" class="menu-item">
                    <i class="bi bi-map"></i>
                    <span>Rutas</span>
                </a>
                <a href="entregas.php" class="menu-item">
                    <i class="bi bi-check-circle"></i>
                    <span>Entregas</span>
                </a>
                <a href="rezagados.php" class="menu-item">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span>Rezagados</span>
                    <?php if($paquetesRezagados > 0): ?>
                        <span class="badge bg-danger"><?php echo $paquetesRezagados; ?></span>
                    <?php endif; ?>
                </a>
            </div>
            
            <div class="menu-section">
                <div class="menu-section-title">Gestión</div>
                <a href="usuarios.php" class="menu-item">
                    <i class="bi bi-people"></i>
                    <span>Usuarios</span>
                </a>
                <a href="pagos.php" class="menu-item">
                    <i class="bi bi-cash-stack"></i>
                    <span>Pagos</span>
                </a>
                <a href="ingresos.php" class="menu-item">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span>Ingresos</span>
                </a>
                <a href="gastos.php" class="menu-item">
                    <i class="bi bi-graph-down-arrow"></i>
                    <span>Gastos</span>
                </a>
            </div>
            
            <div class="menu-section">
                <div class="menu-section-title">Sistema</div>
                <a href="importar.php" class="menu-item">
                    <i class="bi bi-cloud-upload"></i>
                    <span>Importar de SAVAR</span>
                </a>
                <a href="importar_excel.php" class="menu-item">
                    <i class="bi bi-file-earmark-excel"></i>
                    <span>Importar Excel</span>
                </a>
                <a href="reportes.php" class="menu-item">
                    <i class="bi bi-file-earmark-bar-graph"></i>
                    <span>Reportes</span>
                </a>
                <a href="configuracion.php" class="menu-item">
                    <i class="bi bi-gear"></i>
                    <span>Configuración</span>
                </a>
                <a href="../auth/logout.php" class="menu-item">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <div class="top-header">
            <div class="header-left">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                <div style="margin-left: 20px; font-size: 16px; font-weight: 600; color: #333;">
                    Bienvenido, Admin Sistema
                </div>
            </div>
            
            <div class="header-right">
                <div class="header-icon">
                    <i class="bi bi-bell"></i>
                    <span class="badge">3</span>
                </div>
                <div class="user-profile">
                    <img src="../uploads/perfiles/<?php echo $_SESSION['foto_perfil'] ?? 'default.png'; ?>" alt="Avatar" onerror="this.onerror=null; this.src='../uploads/perfiles/default-avatar.svg';">
                    <div class="user-info">
                        <span class="user-name"><?php echo $_SESSION['nombre']; ?></span>
                        <span class="user-role">Administrador</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Page Content -->
        <div class="page-content">
            <div class="page-title">
                <h1>Dashboard General</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </nav>
            </div>
            
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3><?php echo number_format($totalPaquetes); ?></h3>
                            <p>Total Paquetes</p>
                        </div>
                        <div class="stat-icon primary">
                            <i class="bi bi-box"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3><?php echo number_format($paquetesHoy); ?></h3>
                            <p>Entregados Hoy</p>
                        </div>
                        <div class="stat-icon success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3><?php echo number_format($paquetesEnRuta); ?></h3>
                            <p>En Ruta</p>
                        </div>
                        <div class="stat-icon warning">
                            <i class="bi bi-truck"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3><?php echo number_format($paquetesRezagados); ?></h3>
                            <p>Rezagados</p>
                        </div>
                        <div class="stat-icon danger">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Financial Stats -->
            <div class="row mb-4">
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="text-muted mb-2">Ingresos del Mes</h5>
                            <h2 class="text-success"><?php echo formatCurrency($ingresosMes); ?></h2>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="text-muted mb-2">Gastos del Mes</h5>
                            <h2 class="text-danger"><?php echo formatCurrency($gastosMes); ?></h2>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="text-muted mb-2">Balance</h5>
                            <h2 class="<?php echo ($ingresosMes - $gastosMes) >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo formatCurrency($ingresosMes - $gastosMes); ?>
                            </h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="row mb-4">
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Ingresos del Mes</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="ingresosChart" height="80"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Paquetes por Estado</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="estadosChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tables -->
            <div class="row">
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title">Últimos Paquetes</h5>
                            <a href="paquetes.php" class="btn btn-sm btn-primary">Ver Todos</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Destinatario</th>
                                            <th>Repartidor</th>
                                            <th>Estado</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($ultimosPaquetes as $paquete): ?>
                                        <tr>
                                            <td><strong><?php echo $paquete['codigo_seguimiento']; ?></strong></td>
                                            <td><?php echo $paquete['destinatario_nombre']; ?></td>
                                            <td><?php echo $paquete['nombre'] ? $paquete['nombre'] . ' ' . $paquete['apellido'] : 'Sin asignar'; ?></td>
                                            <td>
                                                <?php
                                                $badgeClass = [
                                                    'pendiente' => 'bg-secondary',
                                                    'en_ruta' => 'bg-warning',
                                                    'entregado' => 'bg-success',
                                                    'rezagado' => 'bg-danger',
                                                    'devuelto' => 'bg-info',
                                                    'cancelado' => 'bg-dark'
                                                ];
                                                ?>
                                                <span class="badge <?php echo $badgeClass[$paquete['estado']] ?? 'bg-secondary'; ?>">
                                                    <?php echo ucfirst($paquete['estado']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDateTime($paquete['fecha_recepcion']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Top Repartidores del Mes</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach($topRepartidores as $index => $repartidor): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 <?php echo $index < count($topRepartidores) - 1 ? 'border-bottom' : ''; ?>">
                                <div>
                                    <h6 class="mb-0"><?php echo $repartidor['nombre'] . ' ' . $repartidor['apellido']; ?></h6>
                                    <small class="text-muted"><?php echo $repartidor['entregas']; ?> entregas</small>
                                </div>
                                <div class="badge bg-primary">#<?php echo $index + 1; ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/notificaciones.js"></script>
    <script src="../assets/js/prevent-back.js"></script>
    <script>
        // Toggle Sidebar
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
        
        // Ingresos Chart
        const ingresosData = <?php echo json_encode($ingresosPorDia); ?>;
        const ingresosLabels = ingresosData.map(item => {
            const date = new Date(item.fecha);
            return date.getDate() + '/' + (date.getMonth() + 1);
        });
        const ingresosValues = ingresosData.map(item => parseFloat(item.total));
        
        new Chart(document.getElementById('ingresosChart'), {
            type: 'line',
            data: {
                labels: ingresosLabels,
                datasets: [{
                    label: 'Ingresos (S/.)',
                    data: ingresosValues,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Estados Chart
        const estadosData = <?php echo json_encode($paquetesPorEstado); ?>;
        const estadosLabels = estadosData.map(item => item.estado.charAt(0).toUpperCase() + item.estado.slice(1));
        const estadosValues = estadosData.map(item => parseInt(item.total));
        
        new Chart(document.getElementById('estadosChart'), {
            type: 'doughnut',
            data: {
                labels: estadosLabels,
                datasets: [{
                    data: estadosValues,
                    backgroundColor: [
                        '#6c757d',
                        '#ffc107',
                        '#28a745',
                        '#dc3545',
                        '#17a2b8',
                        '#343a40'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
    
    <!-- Chatbot Widget -->
    <?php include 'includes/chatbot_widget.php'; ?>
</body>
</html>
