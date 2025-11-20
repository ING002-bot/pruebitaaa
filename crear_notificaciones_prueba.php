<?php
/**
 * Script para crear notificaciones de prueba
 * Acceder via navegador: http://localhost/NUEVOOO/crear_notificaciones_prueba.php
 */
require_once 'config/config.php';
require_once 'config/notificaciones_helper.php';

// Solo para desarrollo
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    echo "<h2>Crear Notificaciones de Prueba</h2>";
    echo "<p>Este script creará notificaciones de ejemplo para todos los usuarios.</p>";
    echo "<a href='?confirm=yes' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Confirmar y Crear</a>";
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    // Obtener usuarios de cada rol
    $stmt = $db->prepare("SELECT id, rol, nombre FROM usuarios WHERE estado = 'activo'");
    $stmt->execute();
    $usuarios = $stmt->fetchAll();
    
    $creadas = 0;
    
    foreach ($usuarios as $usuario) {
        $usuario_id = $usuario['id'];
        $rol = $usuario['rol'];
        
        // Notificaciones según rol
        if ($rol === 'admin') {
            crearNotificacion($usuario_id, 'info', 'Bienvenido al Sistema', 'Sistema de notificaciones activado correctamente');
            crearNotificacion($usuario_id, 'alerta', 'Paquete Rezagado', 'El paquete #TRK123456 ha sido marcado como rezagado');
            crearNotificacion($usuario_id, 'info', 'Nueva Entrega', 'Repartidor Carlos completó la entrega del paquete #TRK789012');
            $creadas += 3;
            
        } elseif ($rol === 'repartidor') {
            crearNotificacion($usuario_id, 'info', 'Nuevo Paquete Asignado', 'Se te ha asignado el paquete #TRK456789 para entrega hoy');
            crearNotificacion($usuario_id, 'alerta', 'Recordatorio', 'Tienes 3 paquetes pendientes de entrega para hoy');
            crearNotificacion($usuario_id, 'info', 'Pago Registrado', 'Se ha registrado tu pago de S/ 250.00 correspondiente a Noviembre 2025');
            $creadas += 3;
            
        } elseif ($rol === 'asistente') {
            crearNotificacion($usuario_id, 'info', 'Sistema Actualizado', 'El sistema ha sido actualizado con nuevas funcionalidades');
            crearNotificacion($usuario_id, 'alerta', 'Paquetes Pendientes', 'Hay 5 paquetes pendientes de asignación');
            $creadas += 2;
        }
    }
    
    echo "<div style='font-family: Arial; padding: 20px;'>";
    echo "<h2 style='color: #28a745;'>✓ Notificaciones Creadas Exitosamente</h2>";
    echo "<p>Se crearon <strong>$creadas notificaciones</strong> para " . count($usuarios) . " usuarios.</p>";
    echo "<hr>";
    echo "<h3>Usuarios procesados:</h3>";
    echo "<ul>";
    foreach ($usuarios as $usuario) {
        echo "<li>{$usuario['nombre']} - <em>{$usuario['rol']}</em></li>";
    }
    echo "</ul>";
    echo "<br><a href='admin/dashboard.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Ir al Dashboard</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='font-family: Arial; padding: 20px; color: red;'>";
    echo "<h2>Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
