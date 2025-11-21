<?php
/**
 * API para marcar notificación(es) como leída(s)
 */
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $usuario_id = $_SESSION['usuario_id'];
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['todas']) && $data['todas'] === true) {
        // Marcar todas como leídas
        $stmt = $db->prepare("UPDATE notificaciones SET leida = 1 WHERE usuario_id = ? AND leida = 0");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Todas las notificaciones marcadas como leídas'
        ]);
    } elseif (isset($data['notificacion_id'])) {
        // Marcar una específica como leída
        $stmt = $db->prepare("UPDATE notificaciones SET leida = 1 WHERE id = ? AND usuario_id = ?");
        $stmt->bind_param("ii", $data['notificacion_id'], $usuario_id);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Notificación marcada como leída'
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Parámetros inválidos'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
