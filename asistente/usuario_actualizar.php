<?php
require_once '../config/config.php';
requireRole(['asistente']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        $_SESSION['error'] = 'ID inválido';
        header('Location: usuarios.php');
        exit;
    }
    
    // Proteger admin principal
    if ($id == 1) {
        $_SESSION['error'] = 'El usuario administrador principal está protegido y no puede ser modificado';
        header('Location: usuarios.php');
        exit;
    }
    
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $estado = $_POST['estado'] ?? 'activo';
    $password = trim($_POST['password'] ?? '');
    
    if (empty($nombre) || empty($apellido) || empty($email)) {
        $_SESSION['error'] = 'Datos incompletos';
        header('Location: usuarios.php');
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    $db->autocommit(false);
    
    try {
        // Verificar email único
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('El email ya está en uso');
        }
        
        if (!empty($password)) {
            // Actualizar con contraseña
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, telefono = ?, estado = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssssssi", $nombre, $apellido, $email, $telefono, $estado, $password_hash, $id);
        } else {
            // Actualizar sin contraseña
            $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, telefono = ?, estado = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $nombre, $apellido, $email, $telefono, $estado, $id);
        }
        
        $stmt->execute();
        
        $db->commit();
        $_SESSION['success'] = 'Usuario actualizado correctamente';
        
    } catch (Exception $e) {
        $db->rollback();
        $_SESSION['error'] = 'Error: ' . $e->getMessage();
    }
    
    $db->autocommit(true);
    header('Location: usuarios.php');
    exit;
}
