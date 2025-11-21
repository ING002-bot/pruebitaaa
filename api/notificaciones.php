<?php
/**
 * API para obtener notificaciones del usuario
 */
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    $usuario_id = $_SESSION['usuario_id'];
    
    // Obtener notificaciones no leídas
    $stmt = $db->prepare("SELECT * FROM notificaciones WHERE usuario_id = ? AND leida = 0 ORDER BY fecha_creacion DESC LIMIT 10");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $notificaciones = Database::getInstance()->fetchAll($stmt->get_result());
    
    // Contar total no leídas
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM notificaciones WHERE usuario_id = ? AND leida = 0");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['count'];
    
    echo json_encode([
        'success' => true,
        'count' => $count,
        'notificaciones' => $notificaciones
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
