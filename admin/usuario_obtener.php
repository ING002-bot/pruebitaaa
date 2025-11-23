<?php
require_once '../config/config.php';
requireRole(['admin']);

header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

// Proteger admin principal
if ($id == 1) {
    echo json_encode(['error' => 'Usuario protegido']);
    exit;
}

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT id, nombre, apellido, email, telefono, rol, estado FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

if (!$usuario) {
    echo json_encode(['error' => 'Usuario no encontrado']);
    exit;
}

echo json_encode($usuario);
