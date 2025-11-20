<?php
require_once '../config/config.php';
requireRole(['asistente']);

// Redirigir a funciones de admin (asistente tiene acceso limitado)
header('Location: ../admin/entregas.php');
exit;
