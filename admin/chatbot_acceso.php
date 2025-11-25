<?php
/**
 * Página de acceso al Chatbot desde el panel admin
 * Verificar autenticación
 */

require_once '../config/config.php';

// Verificar acceso
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

if ($_SESSION['rol'] !== 'admin') {
    die('❌ Solo administradores pueden acceder al chatbot');
}

// Redirigir a chatbot.php
header('Location: chatbot.php');
exit;

?>
