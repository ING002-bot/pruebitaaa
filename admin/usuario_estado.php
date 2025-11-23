<?php
require_once '../config/config.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$estado = $_POST['estado'] ?? '';

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

// Proteger admin principal
if ($id == 1) {
    http_response_code(403);
    echo json_encode(['error' => 'Usuario protegido']);
    exit;
}

if (!in_array($estado, ['activo', 'inactivo', 'suspendido'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Estado inválido']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
    $stmt->bind_param("si", $estado, $id);
    
    if (!$stmt->execute()) {
        throw new Exception('Error al actualizar estado');
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
