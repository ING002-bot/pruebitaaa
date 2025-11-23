<?php
require_once '../config/config.php';
requireRole(['admin']);

$tipo = $_GET['tipo'] ?? 'excel';
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');

$db = Database::getInstance()->getConnection();

if ($tipo === 'excel') {
    // Exportar a Excel (CSV)
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=reporte_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // Encabezados
    fputcsv($output, ['REPORTE DE HERMES EXPRESS LOGISTIC']);
    fputcsv($output, ['Periodo: ' . $fecha_desde . ' al ' . $fecha_hasta]);
    fputcsv($output, []);
    
    // Estadísticas generales
    fputcsv($output, ['ESTADÍSTICAS GENERALES']);
    fputcsv($output, ['Concepto', 'Valor']);
    
    // Función helper para obtener un valor único
    function obtenerValor($db, $sql) {
        $result = $db->query($sql);
        if ($result) {
            $row = $result->fetch_row();
            return $row[0] ?? 0;
        }
        return 0;
    }
    
    $total_paquetes = obtenerValor($db, "SELECT COUNT(*) FROM paquetes WHERE DATE(fecha_recepcion) BETWEEN '$fecha_desde' AND '$fecha_hasta'");
    $paquetes_entregados = obtenerValor($db, "SELECT COUNT(*) FROM paquetes WHERE estado='entregado' AND DATE(fecha_entrega) BETWEEN '$fecha_desde' AND '$fecha_hasta'");
    $total_ingresos = obtenerValor($db, "SELECT COALESCE(SUM(monto), 0) FROM ingresos WHERE DATE(fecha_ingreso) BETWEEN '$fecha_desde' AND '$fecha_hasta'");
    $total_gastos = obtenerValor($db, "SELECT COALESCE(SUM(monto), 0) FROM gastos WHERE DATE(fecha_gasto) BETWEEN '$fecha_desde' AND '$fecha_hasta'");
    
    fputcsv($output, ['Total Paquetes', $total_paquetes]);
    fputcsv($output, ['Paquetes Entregados', $paquetes_entregados]);
    fputcsv($output, ['Tasa de Entrega', round(($paquetes_entregados / max($total_paquetes, 1)) * 100, 2) . '%']);
    fputcsv($output, ['Total Ingresos', 'S/ ' . number_format($total_ingresos, 2)]);
    fputcsv($output, ['Total Gastos', 'S/ ' . number_format($total_gastos, 2)]);
    fputcsv($output, ['Utilidad Neta', 'S/ ' . number_format($total_ingresos - $total_gastos, 2)]);
    fputcsv($output, []);
    
    // Top repartidores
    fputcsv($output, ['TOP REPARTIDORES']);
    fputcsv($output, ['Repartidor', 'Total Entregas', 'Exitosas', 'Tasa Éxito', 'Ingresos Generados']);
    
    $result_reps = $db->query("
        SELECT u.nombre, u.apellido, 
               COUNT(e.id) as total_entregas,
               SUM(CASE WHEN e.tipo_entrega='exitosa' THEN 1 ELSE 0 END) as exitosas,
               COALESCE(SUM(i.monto), 0) as total_ingresos
        FROM usuarios u
        LEFT JOIN entregas e ON u.id = e.repartidor_id AND DATE(e.fecha_entrega) BETWEEN '$fecha_desde' AND '$fecha_hasta'
        LEFT JOIN ingresos i ON i.paquete_id IN (SELECT paquete_id FROM entregas WHERE repartidor_id = u.id)
        WHERE u.rol = 'repartidor'
        GROUP BY u.id
        ORDER BY total_entregas DESC
        LIMIT 10
    ");
    $repartidores = [];
    if ($result_reps) {
        while ($row = $result_reps->fetch_assoc()) {
            $repartidores[] = $row;
        }
    }
    
    foreach ($repartidores as $rep) {
        $tasa = $rep['total_entregas'] > 0 ? round(($rep['exitosas'] / $rep['total_entregas']) * 100, 2) : 0;
        fputcsv($output, [
            $rep['nombre'] . ' ' . $rep['apellido'],
            $rep['total_entregas'],
            $rep['exitosas'],
            $tasa . '%',
            'S/ ' . number_format($rep['total_ingresos'], 2)
        ]);
    }
    
    fputcsv($output, []);
    
    // Entregas detalladas
    fputcsv($output, ['DETALLE DE ENTREGAS']);
    fputcsv($output, ['Fecha', 'Código', 'Destinatario', 'Repartidor', 'Tipo', 'Monto']);
    
    $result_ent = $db->query("
        SELECT e.fecha_entrega, p.codigo_seguimiento, p.destinatario_nombre,
               u.nombre, u.apellido, e.tipo_entrega, i.monto
        FROM entregas e
        INNER JOIN paquetes p ON e.paquete_id = p.id
        LEFT JOIN usuarios u ON e.repartidor_id = u.id
        LEFT JOIN ingresos i ON i.paquete_id = p.id
        WHERE DATE(e.fecha_entrega) BETWEEN '$fecha_desde' AND '$fecha_hasta'
        ORDER BY e.fecha_entrega DESC
    ");
    $entregas = [];
    if ($result_ent) {
        while ($row = $result_ent->fetch_assoc()) {
            $entregas[] = $row;
        }
    }
    
    foreach ($entregas as $ent) {
        fputcsv($output, [
            date('d/m/Y H:i', strtotime($ent['fecha_entrega'])),
            $ent['codigo_seguimiento'],
            $ent['destinatario_nombre'],
            $ent['nombre'] . ' ' . $ent['apellido'],
            $ent['tipo_entrega'],
            'S/ ' . number_format($ent['monto'], 2)
        ]);
    }
    
    fclose($output);
    exit;
    
} elseif ($tipo === 'pdf') {
    // Exportar a PDF usando HTML y CSS
    ob_start();
    
    // Función helper para obtener un valor único
    function obtenerValorPDF($db, $sql) {
        $result = $db->query($sql);
        if ($result) {
            $row = $result->fetch_row();
            return $row[0] ?? 0;
        }
        return 0;
    }
    
    $total_paquetes = obtenerValorPDF($db, "SELECT COUNT(*) FROM paquetes WHERE DATE(fecha_recepcion) BETWEEN '$fecha_desde' AND '$fecha_hasta'");
    $paquetes_entregados = obtenerValorPDF($db, "SELECT COUNT(*) FROM paquetes WHERE estado='entregado' AND DATE(fecha_entrega) BETWEEN '$fecha_desde' AND '$fecha_hasta'");
    $total_ingresos = obtenerValorPDF($db, "SELECT COALESCE(SUM(monto), 0) FROM ingresos WHERE DATE(fecha_ingreso) BETWEEN '$fecha_desde' AND '$fecha_hasta'");
    $total_gastos = obtenerValorPDF($db, "SELECT COALESCE(SUM(monto), 0) FROM gastos WHERE DATE(fecha_gasto) BETWEEN '$fecha_desde' AND '$fecha_hasta'");
    
    // Obtener top repartidores
    $result_reps = $db->query("
        SELECT u.nombre, u.apellido, 
               COUNT(e.id) as total_entregas,
               SUM(CASE WHEN e.tipo_entrega='exitosa' THEN 1 ELSE 0 END) as exitosas
        FROM usuarios u
        LEFT JOIN entregas e ON u.id = e.repartidor_id AND DATE(e.fecha_entrega) BETWEEN '$fecha_desde' AND '$fecha_hasta'
        WHERE u.rol = 'repartidor'
        GROUP BY u.id
        HAVING total_entregas > 0
        ORDER BY total_entregas DESC
        LIMIT 5
    ");
    $repartidores = [];
    if ($result_reps) {
        while ($row = $result_reps->fetch_assoc()) {
            $repartidores[] = $row;
        }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reporte - Hermes Express</title>
        <style>
            @page { margin: 20mm; }
            @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
            body { font-family: Arial, sans-serif; font-size: 11pt; margin: 0; padding: 20px; }
            h1 { color: #2c3e50; text-align: center; margin-bottom: 5px; font-size: 18pt; }
            h2 { color: #34495e; font-size: 14pt; margin-top: 20px; border-bottom: 2px solid #3498db; padding-bottom: 5px; }
            .periodo { text-align: center; color: #7f8c8d; margin-bottom: 20px; font-size: 10pt; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th { background-color: #3498db; color: white; padding: 10px; text-align: left; font-size: 10pt; }
            td { padding: 8px; border-bottom: 1px solid #ddd; font-size: 10pt; }
            tr:nth-child(even) { background-color: #f8f9fa; }
            .stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
            .stat-box { background: #f8f9fa; border-left: 4px solid #3498db; padding: 10px; }
            .stat-label { font-size: 9pt; color: #7f8c8d; margin-bottom: 3px; }
            .stat-value { font-size: 16pt; font-weight: bold; color: #2c3e50; }
            .success { color: #27ae60; }
            .danger { color: #e74c3c; }
            .footer { text-align: center; color: #95a5a6; font-size: 8pt; margin-top: 30px; border-top: 1px solid #ddd; padding-top: 10px; }
        </style>
    </head>
    <body>
        <h1>REPORTE DE HERMES EXPRESS LOGISTIC</h1>
        <p class="periodo">Periodo: <?php echo date('d/m/Y', strtotime($fecha_desde)); ?> al <?php echo date('d/m/Y', strtotime($fecha_hasta)); ?></p>
        
        <h2>Estadísticas Generales</h2>
        <div class="stat-grid">
            <div class="stat-box">
                <div class="stat-label">Total Paquetes</div>
                <div class="stat-value"><?php echo number_format($total_paquetes); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Paquetes Entregados</div>
                <div class="stat-value success"><?php echo number_format($paquetes_entregados); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Tasa de Entrega</div>
                <div class="stat-value"><?php echo $total_paquetes > 0 ? round(($paquetes_entregados / $total_paquetes) * 100, 2) : 0; ?>%</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Total Ingresos</div>
                <div class="stat-value success">S/ <?php echo number_format($total_ingresos, 2); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Total Gastos</div>
                <div class="stat-value danger">S/ <?php echo number_format($total_gastos, 2); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Utilidad Neta</div>
                <div class="stat-value <?php echo ($total_ingresos - $total_gastos) >= 0 ? 'success' : 'danger'; ?>">
                    S/ <?php echo number_format($total_ingresos - $total_gastos, 2); ?>
                </div>
            </div>
        </div>
        
        <?php if (!empty($repartidores)): ?>
        <h2>Top Repartidores</h2>
        <table>
            <thead>
                <tr>
                    <th>Repartidor</th>
                    <th style="text-align: center;">Total Entregas</th>
                    <th style="text-align: center;">Exitosas</th>
                    <th style="text-align: center;">Tasa de Éxito</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($repartidores as $rep): 
                    $tasa = $rep['total_entregas'] > 0 ? round(($rep['exitosas'] / $rep['total_entregas']) * 100, 2) : 0;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($rep['nombre'] . ' ' . $rep['apellido']); ?></td>
                    <td style="text-align: center;"><?php echo $rep['total_entregas']; ?></td>
                    <td style="text-align: center;"><?php echo $rep['exitosas']; ?></td>
                    <td style="text-align: center;"><?php echo $tasa; ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        
        <div class="footer">
            Generado el <?php echo date('d/m/Y H:i:s'); ?> | Hermes Express Logistic | Sistema de Gestión de Paquetería
        </div>
        
        <script>
            // Auto-imprimir al cargar
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        </script>
    </body>
    </html>
    <?php
    $html = ob_get_clean();
    echo $html;
    exit;
}
