<?php
/**
 * API para actualizar la ubicación en tiempo real del repartidor
 */
require_once '../config/config.php';
requireRole('repartidor');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $latitud = (float)($data['lat'] ?? 0);
    $longitud = (float)($data['lng'] ?? 0);
    $precision = (float)($data['accuracy'] ?? 0);
    $velocidad = (float)($data['speed'] ?? 0);
    $ruta_id = isset($data['ruta_id']) ? (int)$data['ruta_id'] : null;
    
    if ($latitud === 0 || $longitud === 0) {
        throw new Exception('Coordenadas inválidas');
    }
    
    $db = Database::getInstance()->getConnection();
    $repartidor_id = $_SESSION['usuario_id'];
    
    $sql = "INSERT INTO ubicaciones_tiempo_real 
            (repartidor_id, ruta_id, latitud, longitud, precision_metros, velocidad) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param("iidddd",
        $repartidor_id,
        $ruta_id,
        $latitud,
        $longitud,
        $precision,
        $velocidad
    );
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Ubicación actualizada',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
