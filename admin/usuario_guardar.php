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
    if (!$check) {
        throw new Exception("Error al preparar consulta: " . $db->error);
    }
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    
    if ($check->num_rows > 0) {
        $check->close();
        setFlashMessage('danger', 'El email ya está registrado');
        redirect(APP_URL . 'admin/usuarios.php');
        exit;
    }
    $check->close();
    
    $sql = "INSERT INTO usuarios (nombre, apellido, email, telefono, password, rol, estado) 
            VALUES (?, ?, ?, ?, ?, ?, 'activo')";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . $db->error);
    }
    
    $stmt->bind_param("ssssss", $nombre, $apellido, $email, $telefono, $password, $rol);
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar consulta: " . $stmt->error);
    }
    
    $usuario_id = $db->insert_id;
    $stmt->close();
    
    logActivity("Usuario creado: $email", 'usuarios', $usuario_id);
    setFlashMessage('success', 'Usuario creado exitosamente');
    
} catch (Exception $e) {
    setFlashMessage('danger', 'Error: ' . $e->getMessage());
}

redirect(APP_URL . 'admin/usuarios.php');
