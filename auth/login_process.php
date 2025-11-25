<?php
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!csrf_verify()) {
        setFlashMessage('danger', 'Token de seguridad inválido. Por favor, intenta de nuevo.');
        redirect(APP_URL . 'auth/login.php');
    }
    
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    // Rate Limiting por IP
    $ip = $_SERVER['REMOTE_ADDR'];
    try {
        check_rate_limit('login_' . $ip, 5, 900); // 5 intentos en 15 minutos
    } catch (Exception $e) {
        setFlashMessage('danger', $e->getMessage());
        logActivity('Rate limit excedido en login - IP: ' . $ip);
        redirect(APP_URL . 'auth/login.php');
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT * FROM usuarios WHERE email = ? AND estado = 'activo'";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();
        
        if ($usuario && password_verify($password, $usuario['password'])) {
            // Resetear rate limit en login exitoso
            reset_rate_limit('login_' . $ip);
            
            // Actualizar último acceso
            $updateSql = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->bind_param("i", $usuario['id']);
            $updateStmt->execute();
            
            // Crear sesión
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nombre'] = $usuario['nombre'];
            $_SESSION['apellido'] = $usuario['apellido'];
            $_SESSION['email'] = $usuario['email'];
            $_SESSION['rol'] = $usuario['rol'];
            $_SESSION['foto_perfil'] = $usuario['foto_perfil'];
            
            // Log de actividad
            logActivity('Inicio de sesión exitoso', 'usuarios', $usuario['id']);
            
            // Redireccionar según el rol
            switch ($usuario['rol']) {
                case 'admin':
                    redirect(APP_URL . 'admin/dashboard.php');
                    break;
                case 'asistente':
                    redirect(APP_URL . 'asistente/dashboard.php');
                    break;
                case 'repartidor':
                    redirect(APP_URL . 'repartidor/dashboard.php');
                    break;
                default:
                    redirect(APP_URL . 'index.php');
            }
        } else {
            setFlashMessage('danger', 'Email o contraseña incorrectos');
            logActivity('Intento de inicio de sesión fallido - Email: ' . $email);
            redirect(APP_URL . 'auth/login.php');
        }
    } catch (Exception $e) {
        setFlashMessage('danger', 'Error al iniciar sesión. Por favor, intente nuevamente.');
        error_log("Error en login: " . $e->getMessage());
        redirect(APP_URL . 'auth/login.php');
    }
} else {
    redirect(APP_URL . 'auth/login.php');
}
