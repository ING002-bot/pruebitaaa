<?php
// Configuración general del sistema
session_start();

// Zona horaria
date_default_timezone_set('America/Lima');

// Configuración de la aplicación
define('APP_NAME', 'HERMES EXPRESS LOGISTIC');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/NUEVOOO/');
define('UPLOADS_DIR', __DIR__ . '/../uploads/');
define('UPLOADS_URL', APP_URL . 'uploads/');

// API de Google Maps (Reemplaza con tu API Key)
define('GOOGLE_MAPS_API_KEY', 'AIzaSyAhKq8glWDGij47iJZy2_RB8jan9D1V-Sk');

// Configuración de uploads
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);

// Tarifas por paquete (configurable)
define('TARIFA_POR_PAQUETE', 3.50);
define('TARIFA_URGENTE', 5.00);
define('TARIFA_EXPRESS', 7.50);

// Incluir base de datos
require_once __DIR__ . '/database.php';

// Funciones de utilidad
function isLoggedIn() {
    return isset($_SESSION['usuario_id']);
}

function getUsuarioActual() {
    if (!isLoggedIn()) return null;
    return $_SESSION;
}

function hasRole($roles) {
    if (!isLoggedIn()) return false;
    if (is_array($roles)) {
        return in_array($_SESSION['rol'], $roles);
    }
    return $_SESSION['rol'] === $roles;
}

function requireLogin() {
    if (!isLoggedIn()) {
        // Headers para prevenir cache
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Location: ' . APP_URL . 'auth/login.php');
        exit;
    }
}

function requireRole($roles) {
    requireLogin();
    if (!hasRole($roles)) {
        // Headers para prevenir cache
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Location: ' . APP_URL . 'auth/unauthorized.php');
        exit;
    }
    // Prevenir cache en páginas protegidas
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

function formatCurrency($amount) {
    return 'S/. ' . number_format($amount, 2);
}

function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

function logActivity($accion, $tabla = null, $registro_id = null, $detalles = null) {
    try {
        $db = Database::getInstance()->getConnection();
        $sql = "INSERT INTO logs_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles, ip_address, user_agent) 
                VALUES (:usuario_id, :accion, :tabla, :registro_id, :detalles, :ip, :user_agent)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $_SESSION['usuario_id'] ?? null,
            ':accion' => $accion,
            ':tabla' => $tabla,
            ':registro_id' => $registro_id,
            ':detalles' => $detalles,
            ':ip' => $_SERVER['REMOTE_ADDR'],
            ':user_agent' => $_SERVER['HTTP_USER_AGENT']
        ]);
    } catch (Exception $e) {
        error_log("Error al registrar actividad: " . $e->getMessage());
    }
}

function createNotification($usuario_id, $tipo, $titulo, $mensaje) {
    try {
        $db = Database::getInstance()->getConnection();
        $sql = "INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$usuario_id, $tipo, $titulo, $mensaje]);
    } catch (Exception $e) {
        error_log("Error al crear notificación: " . $e->getMessage());
        return false;
    }
}
