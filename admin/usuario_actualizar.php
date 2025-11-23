<?php
require_once '../config/config.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: usuarios.php');
    exit;
}

$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['error_message'] = 'ID de usuario inválido';
    header('Location: usuarios.php');
    exit;
}

// Proteger admin principal
if ($id == 1) {
    $_SESSION['error_message'] = 'El usuario administrador principal está protegido y no puede ser modificado';
    header('Location: usuarios.php');
    exit;
}

$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$email = trim($_POST['email'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$estado = $_POST['estado'] ?? 'activo';
$password = trim($_POST['password'] ?? '');

try {
    $db = Database::getInstance()->getConnection();
    
    // Validaciones
    if (empty($nombre) || empty($apellido) || empty($email)) {
        throw new Exception('Nombre, apellido y email son requeridos');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email inválido');
    }
    
    // Verificar email único (excepto el propio usuario)
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('El email ya está en uso por otro usuario');
    }
    
    // Actualizar usuario
    if (!empty($password)) {
        // Actualizar con nueva contraseña
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, telefono = ?, estado = ?, password = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $nombre, $apellido, $email, $telefono, $estado, $password_hash, $id);
    } else {
        // Actualizar sin cambiar contraseña
        $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, telefono = ?, estado = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $nombre, $apellido, $email, $telefono, $estado, $id);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Error al actualizar usuario: ' . $stmt->error);
    }
    
    $_SESSION['success_message'] = 'Usuario actualizado exitosamente';
    
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
}

header('Location: usuarios.php');
exit;
