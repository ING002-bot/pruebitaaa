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
    
    $total_paquetes = $db->query("SELECT COUNT(*) FROM paquetes WHERE DATE(fecha_recepcion) BETWEEN '$fecha_desde' AND '$fecha_hasta'")->fetchColumn();
    $paquetes_entregados = $db->query("SELECT COUNT(*) FROM paquetes WHERE estado='entregado' AND DATE(fecha_entrega) BETWEEN '$fecha_desde' AND '$fecha_hasta'")->fetchColumn();
    $total_ingresos = $db->query("SELECT COALESCE(SUM(monto), 0) FROM ingresos WHERE DATE(fecha_ingreso) BETWEEN '$fecha_desde' AND '$fecha_hasta'")->fetchColumn();
    $total_gastos = $db->query("SELECT COALESCE(SUM(monto), 0) FROM gastos WHERE DATE(fecha_gasto) BETWEEN '$fecha_desde' AND '$fecha_hasta'")->fetchColumn();
    
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
    
    $repartidores = $db->query("
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
    ")->fetchAll();
    
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
    
    $entregas = $db->query("
        SELECT e.fecha_entrega, p.codigo_seguimiento, p.destinatario_nombre,
               u.nombre, u.apellido, e.tipo_entrega, i.monto
        FROM entregas e
        INNER JOIN paquetes p ON e.paquete_id = p.id
        LEFT JOIN usuarios u ON e.repartidor_id = u.id
        LEFT JOIN ingresos i ON i.paquete_id = p.id
        WHERE DATE(e.fecha_entrega) BETWEEN '$fecha_desde' AND '$fecha_hasta'
        ORDER BY e.fecha_entrega DESC
    ")->fetchAll();
    
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
}
