<?php
/**
 * Script de prueba para API del Chatbot
 */

// Simular sesión activa
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['rol'] = 'admin';

// Cargar configuración
require_once 'config/config.php';

echo "=== TEST API CHATBOT ===\n\n";

// Test 1: Verificar conexión a BD
echo "1. Probando conexión a BD...\n";
try {
    $db = Database::getInstance()->getConnection();
    if ($db) {
        echo "✅ Conexión exitosa\n\n";
    } else {
        echo "❌ Error: No se pudo obtener conexión\n\n";
        exit;
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
    exit;
}

// Test 2: Simular peticiones al chatbot
echo "2. Probando consultas del chatbot...\n\n";

// Simular POST
$_POST['action'] = 'chat';
$_POST['input'] = '';

// Array de preguntas de prueba
$preguntas_test = [
    '¿Cuántos paquetes hay?',
    'Paquetes pendientes',
    'Paquetes entregados',
    'Dame un resumen',
    '¿Cuánto ganamos hoy?',
    'Total de clientes',
    '¿Cuántos repartidores activos?'
];

// Incluir API del chatbot
require_once 'admin/api_chatbot.php';
