<?php
require_once '../config/config.php';
requireRole(['asistente']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: reportes.php');
    exit;
}

$fecha_inicio = $_POST['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$fecha_fin = $_POST['fecha_fin'] ?? date('Y-m-d');
$estado = $_POST['estado'] ?? '';
$zona = $_POST['zona'] ?? '';
$formato = $_POST['formato'] ?? 'excel';

$db = Database::getInstance()->getConnection();

// Construir query
$where = ["DATE(p.fecha_recepcion) BETWEEN ? AND ?"];
$params = [$fecha_inicio, $fecha_fin];
$types = "ss";

if (!empty($estado)) {
    $where[] = "p.estado = ?";
    $params[] = $estado;
    $types .= "s";
}

if (!empty($zona)) {
    $where[] = "r.zona = ?";
    $params[] = $zona;
    $types .= 's';
}

$whereClause = implode(' AND ', $where);

// Obtener paquetes
$sql = "SELECT p.id, p.codigo_seguimiento, p.destinatario_nombre, p.direccion_completa, 
        p.destinatario_telefono, p.estado, p.fecha_recepcion as fecha_registro,
        r.nombre as ruta_nombre, r.zona, u.nombre as repartidor_nombre, u.apellido as repartidor_apellido
        FROM paquetes p
        LEFT JOIN ruta_paquetes rp ON p.id = rp.paquete_id
        LEFT JOIN rutas r ON rp.ruta_id = r.id
        LEFT JOIN usuarios u ON p.repartidor_id = u.id
        WHERE $whereClause
        ORDER BY p.fecha_recepcion DESC";

$stmt = $db->prepare($sql);
if (!$stmt) {
    die('Error en la consulta SQL: ' . $db->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$paquetes = Database::getInstance()->fetchAll($result);

if ($formato === 'excel') {
    // Exportar a Excel
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="reporte_paquetes_' . date('Y-m-d') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo "\xEF\xBB\xBF"; // BOM UTF-8
    
    echo "<table border='1'>";
    echo "<thead>";
    echo "<tr style='background-color: #007bff; color: white; font-weight: bold;'>";
    echo "<th>Código Seguimiento</th>";
    echo "<th>Destinatario</th>";
    echo "<th>Dirección</th>";
    echo "<th>Teléfono</th>";
    echo "<th>Zona</th>";
    echo "<th>Estado</th>";
    echo "<th>Repartidor</th>";
    echo "<th>Ruta</th>";
    echo "<th>Fecha Registro</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    foreach ($paquetes as $p) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($p['codigo_seguimiento']) . "</td>";
        echo "<td>" . htmlspecialchars($p['destinatario_nombre']) . "</td>";
        echo "<td>" . htmlspecialchars($p['direccion_completa']) . "</td>";
        echo "<td>" . htmlspecialchars($p['destinatario_telefono'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($p['zona'] ?? '') . "</td>";
        echo "<td>" . ucfirst(str_replace('_', ' ', $p['estado'])) . "</td>";
        echo "<td>" . ($p['repartidor_nombre'] ? htmlspecialchars($p['repartidor_nombre'] . ' ' . $p['repartidor_apellido']) : '-') . "</td>";
        echo "<td>" . htmlspecialchars($p['ruta_nombre'] ?? '-') . "</td>";
        echo "<td>" . date('d/m/Y H:i', strtotime($p['fecha_registro'])) . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
    
} elseif ($formato === 'pdf') {
    // Exportar a PDF (HTML simple)
    header('Content-Type: text/html; charset=UTF-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reporte de Paquetes</title>
        <style>
            body { font-family: Arial, sans-serif; font-size: 10px; }
            h1 { text-align: center; color: #007bff; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th { background-color: #007bff; color: white; padding: 8px; text-align: left; }
            td { border: 1px solid #ddd; padding: 6px; }
            tr:nth-child(even) { background-color: #f2f2f2; }
            .header-info { text-align: center; margin-bottom: 20px; }
        </style>
    </head>
    <body onload="window.print();">
        <h1>REPORTE DE PAQUETES</h1>
        <div class="header-info">
            <p><strong>Período:</strong> <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> - <?php echo date('d/m/Y', strtotime($fecha_fin)); ?></p>
            <?php if ($estado): ?>
                <p><strong>Estado:</strong> <?php echo ucfirst(str_replace('_', ' ', $estado)); ?></p>
            <?php endif; ?>
            <?php if ($zona): ?>
                <p><strong>Zona:</strong> <?php echo $zona; ?></p>
            <?php endif; ?>
            <p><strong>Total Paquetes:</strong> <?php echo count($paquetes); ?></p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Destinatario</th>
                    <th>Dirección</th>
                    <th>Zona</th>
                    <th>Estado</th>
                    <th>Repartidor</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($paquetes as $p): ?>
                <tr>
                    <td><?php echo htmlspecialchars($p['codigo_seguimiento']); ?></td>
                    <td><?php echo htmlspecialchars($p['destinatario_nombre']); ?></td>
                    <td><?php echo htmlspecialchars($p['direccion_completa']); ?></td>
                    <td><?php echo htmlspecialchars($p['zona'] ?? '-'); ?></td>
                    <td><?php echo ucfirst(str_replace('_', ' ', $p['estado'])); ?></td>
                    <td><?php echo $p['repartidor_nombre'] ? htmlspecialchars($p['repartidor_nombre'] . ' ' . $p['repartidor_apellido']) : '-'; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($p['fecha_registro'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
}
exit;
