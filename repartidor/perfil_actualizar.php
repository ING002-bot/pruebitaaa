<?php
require_once '../config/config.php';
requireRole('repartidor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . 'repartidor/perfil.php');
}

$db = Database::getInstance()->getConnection();
$repartidor_id = $_SESSION['usuario_id'];

try {
    // Obtener datos del formulario
    $nombre = sanitize($_POST['nombre']);
    $apellido = sanitize($_POST['apellido']);
    $email = sanitize($_POST['email']);
    $telefono = sanitize($_POST['telefono']);
    $nueva_password = $_POST['nueva_password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';
    
    // Validar email único
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $repartidor_id);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        setFlashMessage('danger', 'El email ya está en uso por otro usuario');
        redirect(APP_URL . 'repartidor/perfil.php');
    }
    
    // Validar contraseña si se proporcionó
    if (!empty($nueva_password)) {
        if ($nueva_password !== $confirmar_password) {
            setFlashMessage('danger', 'Las contraseñas no coinciden');
            redirect(APP_URL . 'repartidor/perfil.php');
        }
        
        if (strlen($nueva_password) < 6) {
            setFlashMessage('danger', 'La contraseña debe tener al menos 6 caracteres');
            redirect(APP_URL . 'repartidor/perfil.php');
        }
    }
    
    // Procesar foto de perfil
    $foto_perfil = null;
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto_perfil'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        
        if (!in_array($file['type'], $allowed_types)) {
            setFlashMessage('danger', 'Solo se permiten imágenes JPG, JPEG o PNG');
            redirect(APP_URL . 'repartidor/perfil.php');
        }
        
        if ($file['size'] > 5 * 1024 * 1024) { // 5MB
            setFlashMessage('danger', 'La imagen no debe superar los 5MB');
            redirect(APP_URL . 'repartidor/perfil.php');
        }
        
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'perfil_' . $repartidor_id . '_' . time() . '.' . $ext;
        $filepath = UPLOADS_DIR . 'perfiles/' . $filename;
        
        if (!file_exists(UPLOADS_DIR . 'perfiles/')) {
            mkdir(UPLOADS_DIR . 'perfiles/', 0777, true);
        }
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $foto_perfil = $filename;
            
            // Eliminar foto anterior si existe
            $stmt = $db->prepare("SELECT foto_perfil FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $repartidor_id);
            $stmt->execute();
            $usuario_anterior = $stmt->get_result()->fetch_assoc();
            
            if ($usuario_anterior && !empty($usuario_anterior['foto_perfil']) && 
                $usuario_anterior['foto_perfil'] != 'default-avatar.svg') {
                $old_file = UPLOADS_DIR . 'perfiles/' . $usuario_anterior['foto_perfil'];
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }
        }
    }
    
    // Construir SQL de actualización
    $sql = "UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, telefono = ?";
    $params = [$nombre, $apellido, $email, $telefono];
    
    if ($foto_perfil) {
        $sql .= ", foto_perfil = ?";
        $params[] = $foto_perfil;
    }
    
    if (!empty($nueva_password)) {
        $sql .= ", password = ?";
        $params[] = password_hash($nueva_password, PASSWORD_DEFAULT);
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $repartidor_id;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    // Actualizar sesión
    $_SESSION['nombre'] = $nombre;
    $_SESSION['apellido'] = $apellido;
    if ($foto_perfil) {
        $_SESSION['foto_perfil'] = $foto_perfil;
    }
    
    setFlashMessage('success', 'Perfil actualizado exitosamente');
    redirect(APP_URL . 'repartidor/perfil.php');
    
} catch (Exception $e) {
    error_log("Error al actualizar perfil: " . $e->getMessage());
    setFlashMessage('danger', 'Error al actualizar el perfil. Por favor, intente nuevamente.');
    redirect(APP_URL . 'repartidor/perfil.php');
}
