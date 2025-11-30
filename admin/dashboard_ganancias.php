<?php
session_start();
require_once '../config/config.php';
require_once '../config/tarifas_helper.php';
verificarAutenticacion();

// Obtener estadísticas de ganancias
$estadisticas = obtenerEstadisticasTarifas();
$zonas_rentables = obtenerZonasMasRentables(15);

// Calcular totales por prioridad para diferentes tipos de paquete
$simulacion_ganancias = [];
foreach (['normal', 'urgente', 'express'] as $prioridad) {
    $total_ganancia = 0;
    $contador = 0;
    foreach ($zonas_rentables as $zona) {
        $ganancia_info = calcularGananciaReal($zona['nombre_zona'], $prioridad);
        if ($ganancia_info) {
            $total_ganancia += $ganancia_info['ganancia_bruta'];
            $contador++;
        }
    }
    $simulacion_ganancias[$prioridad] = [
        'promedio' => $contador > 0 ? $total_ganancia / $contador : 0,
        'total_15_paquetes' => $total_ganancia
    ];
}

$pageTitle = "Dashboard de Ganancias";
include '../admin/includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">💰 Dashboard de Ganancias</h1>
                    <p class="text-muted">Análisis de rentabilidad por zona y tipo de envío</p>
                </div>
                <div>
                    <a href="paquetes.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Crear Paquete
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Resumen por Prioridad -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h5 class="card-title text-success">📦 Normal</h5>
                    <h2 class="text-success">S/ <?php echo number_format($simulacion_ganancias['normal']['promedio'], 2); ?></h2>
                    <p class="mb-0">Ganancia promedio por paquete</p>
                    <small class="text-muted">15 paquetes = S/ <?php echo number_format($simulacion_ganancias['normal']['total_15_paquetes'], 2); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h5 class="card-title text-warning">🚀 Urgente (+50%)</h5>
                    <h2 class="text-warning">S/ <?php echo number_format($simulacion_ganancias['urgente']['promedio'], 2); ?></h2>
                    <p class="mb-0">Ganancia promedio por paquete</p>
                    <small class="text-muted">15 paquetes = S/ <?php echo number_format($simulacion_ganancias['urgente']['total_15_paquetes'], 2); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <h5 class="card-title text-danger">⚡ Express (+100%)</h5>
                    <h2 class="text-danger">S/ <?php echo number_format($simulacion_ganancias['express']['promedio'], 2); ?></h2>
                    <p class="mb-0">Ganancia promedio por paquete</p>
                    <small class="text-muted">15 paquetes = S/ <?php echo number_format($simulacion_ganancias['express']['total_15_paquetes'], 2); ?></small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Estadísticas por Categoría -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">📊 Ganancias por Categoría</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($estadisticas as $stat): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3 p-3 border rounded">
                        <div>
                            <h6 class="mb-1"><?php echo $stat['categoria']; ?></h6>
                            <small class="text-muted"><?php echo $stat['total_zonas']; ?> zonas</small>
                        </div>
                        <div class="text-end">
                            <h6 class="mb-1 text-success">S/ <?php echo number_format($stat['ganancia_promedio'], 2); ?></h6>
                            <small class="text-muted"><?php echo $stat['margen_porcentaje']; ?>% margen</small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Top Zonas Más Rentables -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">🏆 Top 15 Zonas Más Rentables</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Zona</th>
                                    <th>Cliente</th>
                                    <th>Repartidor</th>
                                    <th>Ganancia</th>
                                    <th>Margen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($zonas_rentables as $index => $zona): ?>
                                <tr>
                                    <td>
                                        <?php if ($index < 3): ?>
                                            <?php echo ['🥇', '🥈', '🥉'][$index]; ?>
                                        <?php endif; ?>
                                        <strong><?php echo $zona['nombre_zona']; ?></strong><br>
                                        <small class="text-muted"><?php echo $zona['categoria']; ?></small>
                                    </td>
                                    <td>S/ <?php echo number_format($zona['costo_cliente'], 2); ?></td>
                                    <td>S/ <?php echo number_format($zona['tarifa_repartidor'], 2); ?></td>
                                    <td>
                                        <span class="badge <?php echo $zona['ganancia'] >= 5 ? 'bg-success' : ($zona['ganancia'] >= 2.5 ? 'bg-warning' : 'bg-danger'); ?>">
                                            S/ <?php echo number_format($zona['ganancia'], 2); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $zona['margen_porcentaje']; ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Consejos de Rentabilidad -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title">💡 Consejos para Maximizar Ganancias:</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-success">
                                <strong>🎯 Zonas de Alta Rentabilidad:</strong><br>
                                <small>Enfócate en EXCOPERATIVAS, FERREÑAFE y COOPERATIVAS para máximas ganancias (S/ 5.50 - S/ 6.00)</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-warning">
                                <strong>⚡ Prioridad Express:</strong><br>
                                <small>Los envíos express duplican tu ganancia. Promúevelos en zonas rentables.</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-info">
                                <strong>📊 Optimización:</strong><br>
                                <small>Considera ajustar tarifas en zonas URBANO (ganancia baja: S/ 1.20 - S/ 1.50)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Actualizar cada 5 minutos
setTimeout(function() {
    location.reload();
}, 300000);
</script>

<?php include '../admin/includes/footer.php'; ?>