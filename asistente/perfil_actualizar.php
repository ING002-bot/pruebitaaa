<?php
require_once '../config/config.php';
requireRole(['asistente']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: perfil.php');
    exit;
}

$asistente_id = $_SESSION['usuario_id'];
$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$email = trim($_POST['email'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$nueva_password = trim($_POST['nueva_password'] ?? '');
$confirmar_password = trim($_POST['confirmar_password'] ?? '');

// Validaciones básicas
if (empty($nombre) || empty($apellido) || empty($email)) {
    $_SESSION['error'] = 'Nombre, apellido y email son obligatorios';
    header('Location: perfil.php');
    exit;
}

// Validar contraseña si se proporcionó
if (!empty($nueva_password)) {
    if (strlen($nueva_password) < 6) {
        $_SESSION['error'] = 'La contraseña debe tener al menos 6 caracteres';
        header('Location: perfil.php');
        exit;
    }
    
    if ($nueva_password !== $confirmar_password) {
        $_SESSION['error'] = 'Las contraseñas no coinciden';
        header('Location: perfil.php');
        exit;
    }
}

$db = Database::getInstance()->getConnection();
$db->autocommit(false);

try {
    // Verificar que el email no esté en uso por otro usuario
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $asistente_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('El email ya está en uso por otro usuario');
    }
    
    // Procesar foto de perfil si se subió
    $foto_perfil = null;
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/perfiles/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $extension = strtolower(pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($extension, $allowed)) {
            throw new Exception('Formato de imagen no permitido. Use JPG, PNG o GIF');
        }
        
        if ($_FILES['foto_perfil']['size'] > 2 * 1024 * 1024) { // 2MB
            throw new Exception('La imagen es demasiado grande. Máximo 2MB');
        }
        
        $foto_perfil = 'perfil_' . $asistente_id . '_' . time() . '.' . $extension;
        
        if (!move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $upload_dir . $foto_perfil)) {
            throw new Exception('Error al subir la foto de perfil');
        }
    }
    
    // Actualizar datos del usuario
    if (!empty($nueva_password)) {
        // Actualizar con contraseña
        $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
        
        if ($foto_perfil) {
            $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, telefono = ?, foto_perfil = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssssssi", $nombre, $apellido, $email, $telefono, $foto_perfil, $password_hash, $asistente_id);
        } else {
            $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, telefono = ?, password = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $nombre, $apellido, $email, $telefono, $password_hash, $asistente_id);
        }
    } else {
        // Actualizar sin contraseña
        if ($foto_perfil) {
            $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, telefono = ?, foto_perfil = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $nombre, $apellido, $email, $telefono, $foto_perfil, $asistente_id);
        } else {
            $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, telefono = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $nombre, $apellido, $email, $telefono, $asistente_id);
        }
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Error al actualizar el perfil');
    }
    
    $db->commit();
    
    // Actualizar nombre en sesión
    $_SESSION['usuario_nombre'] = $nombre . ' ' . $apellido;
    
    $_SESSION['success'] = 'Perfil actualizado correctamente';
    
} catch (Exception $e) {
    $db->rollback();
    $_SESSION['error'] = 'Error: ' . $e->getMessage();
    
    // Eliminar foto si se subió pero hubo error
    if ($foto_perfil && file_exists('../uploads/perfiles/' . $foto_perfil)) {
        unlink('../uploads/perfiles/' . $foto_perfil);
    }
}

$db->autocommit(true);
header('Location: perfil.php');
exit;
