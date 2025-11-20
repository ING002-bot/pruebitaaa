<?php
require_once 'config/config.php';

// Si ya está logueado, redirigir según el rol
if (isLoggedIn()) {
    switch ($_SESSION['rol']) {
        case 'admin':
            redirect(APP_URL . 'admin/dashboard.php');
            break;
        case 'asistente':
            redirect(APP_URL . 'asistente/dashboard.php');
            break;
        case 'repartidor':
            redirect(APP_URL . 'repartidor/dashboard.php');
            break;
    }
}

// Si no está logueado, redirigir al login
redirect(APP_URL . 'auth/login.php');
