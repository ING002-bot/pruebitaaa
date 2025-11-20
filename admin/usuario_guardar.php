<?php
require_once '../config/config.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . 'admin/usuarios.php');
    exit;
}

$nombre = sanitize($_POST['nombre']);
$apellido = sanitize($_POST['apellido']);
$email = sanitize($_POST['email']);
$telefono = sanitize($_POST['telefono']);
$rol = sanitize($_POST['rol']);
$password = password_hash($_POST['password'], PASSWORD_BCRYPT);

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar email único
    $check = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        setFlashMessage('danger', 'El email ya está registrado');
        redirect(APP_URL . 'admin/usuarios.php');
        exit;
    }
    
    $sql = "INSERT INTO usuarios (nombre, apellido, email, telefono, password, rol, estado) 
            VALUES (?, ?, ?, ?, ?, ?, 'activo')";
    $stmt = $db->prepare($sql);
    $stmt->execute([$nombre, $apellido, $email, $telefono, $password, $rol]);
    
    logActivity("Usuario creado: $email", 'usuarios', $db->lastInsertId());
    setFlashMessage('success', 'Usuario creado exitosamente');
    
} catch (Exception $e) {
    setFlashMessage('danger', 'Error: ' . $e->getMessage());
}

redirect(APP_URL . 'admin/usuarios.php');
