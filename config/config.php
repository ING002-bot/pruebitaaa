<?php
// Configuración general del sistema
session_start();

// Zona horaria
date_default_timezone_set('America/Lima');

// Configuración de la aplicación
define('APP_NAME', 'HERMES EXPRESS LOGISTIC');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/pruebitaaa/');
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
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $usuario_id = $_SESSION['usuario_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $stmt->bind_param("isiiiss", $usuario_id, $accion, $tabla, $registro_id, $detalles, $ip, $user_agent);
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Error al registrar actividad: " . $e->getMessage());
    }
}

function createNotification($usuario_id, $tipo, $titulo, $mensaje) {
    try {
        $db = Database::getInstance()->getConnection();
        $sql = "INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("isss", $usuario_id, $tipo, $titulo, $mensaje);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error al crear notificación: " . $e->getMessage());
        return false;
    }
}

// ====================================================================
// FUNCIONES DE SEGURIDAD
// ====================================================================

/**
 * Generar token CSRF
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validar token CSRF
 */
function csrf_verify() {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/**
 * Campo oculto con token CSRF para formularios
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Validación mejorada de imágenes
 */
function validar_imagen($file, $max_size = MAX_UPLOAD_SIZE) {
    $permitidos = ALLOWED_IMAGE_TYPES;
    
    // Verificar que el archivo existe
    if (!isset($file['tmp_name']) || !file_exists($file['tmp_name'])) {
        throw new Exception('No se recibió ningún archivo');
    }
    
    // Verificar tipo MIME
    if (!in_array($file['type'], $permitidos)) {
        throw new Exception('Formato no permitido. Solo JPG, JPEG y PNG');
    }
    
    // Verificar tamaño
    if ($file['size'] > $max_size) {
        $max_mb = round($max_size / 1048576, 2);
        throw new Exception("Archivo muy grande (máx {$max_mb}MB)");
    }
    
    // Verificar que es imagen real usando getimagesize
    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        throw new Exception('El archivo no es una imagen válida');
    }
    
    // Verificar dimensiones mínimas
    if ($info[0] < 50 || $info[1] < 50) {
        throw new Exception('La imagen es demasiado pequeña (mínimo 50x50px)');
    }
    
    return true;
}

/**
 * Rate Limiting para intentos de login
 */
function check_rate_limit($identifier, $max_intentos = 5, $ventana = 900) {
    // $ventana en segundos (900s = 15 minutos)
    
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
    
    $now = time();
    
    // Limpiar intentos antiguos
    foreach ($_SESSION['rate_limit'] as $key => $data) {
        if ($now - $data['first_attempt'] > $ventana) {
            unset($_SESSION['rate_limit'][$key]);
        }
    }
    
    // Inicializar si no existe
    if (!isset($_SESSION['rate_limit'][$identifier])) {
        $_SESSION['rate_limit'][$identifier] = [
            'count' => 0,
            'first_attempt' => $now
        ];
    }
    
    $attempts = &$_SESSION['rate_limit'][$identifier];
    
    // Resetear si pasó la ventana de tiempo
    if ($now - $attempts['first_attempt'] > $ventana) {
        $attempts['count'] = 0;
        $attempts['first_attempt'] = $now;
    }
    
    // Verificar límite
    if ($attempts['count'] >= $max_intentos) {
        $tiempo_restante = $ventana - ($now - $attempts['first_attempt']);
        $minutos = ceil($tiempo_restante / 60);
        throw new Exception("Demasiados intentos. Intenta de nuevo en {$minutos} minutos");
    }
    
    // Incrementar contador
    $attempts['count']++;
    
    return true;
}

/**
 * Resetear rate limit (después de login exitoso)
 */
function reset_rate_limit($identifier) {
    if (isset($_SESSION['rate_limit'][$identifier])) {
        unset($_SESSION['rate_limit'][$identifier]);
    }
}

/**
 * Sanitizar nombre de archivo
 */
function sanitize_filename($filename) {
    // Eliminar caracteres peligrosos
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    // Prevenir directory traversal
    $filename = basename($filename);
    return $filename;
}

/**
 * Generar nombre único para archivo
 */
function generate_unique_filename($original_name, $prefix = '') {
    $extension = pathinfo($original_name, PATHINFO_EXTENSION);
    $name = pathinfo($original_name, PATHINFO_FILENAME);
    $name = sanitize_filename($name);
    
    if ($prefix) {
        $prefix = sanitize_filename($prefix) . '_';
    }
    
    return $prefix . time() . '_' . uniqid() . '.' . $extension;
}
