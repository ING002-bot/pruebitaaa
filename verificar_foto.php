<?php
/**
 * Script para verificar la configuración de fotos de perfil
 */
require_once 'config/config.php';
requireLogin();

echo "<h2>Verificación de Foto de Perfil</h2>";
echo "<hr>";

echo "<h3>Información de Sesión</h3>";
echo "<pre>";
echo "Usuario ID: " . $_SESSION['usuario_id'] . "\n";
echo "Nombre: " . $_SESSION['nombre'] . "\n";
echo "Foto en sesión: " . ($_SESSION['foto_perfil'] ?? 'NO DEFINIDA') . "\n";
echo "</pre>";

echo "<h3>Información de Base de Datos</h3>";
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT id, nombre, apellido, foto_perfil, estado FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch();

echo "<pre>";
print_r($usuario);
echo "</pre>";

echo "<h3>Rutas de Archivos</h3>";
$foto_perfil = $usuario['foto_perfil'] ?? 'default-avatar.svg';
$ruta_completa = __DIR__ . '/uploads/perfiles/' . $foto_perfil;

echo "<p><strong>Foto en BD:</strong> $foto_perfil</p>";
echo "<p><strong>Ruta completa:</strong> $ruta_completa</p>";
echo "<p><strong>Existe archivo:</strong> " . (file_exists($ruta_completa) ? '✓ SÍ' : '✗ NO') . "</p>";

echo "<h3>URL que debería mostrarse</h3>";
$foto_url = !empty($usuario['foto_perfil']) && $usuario['foto_perfil'] != 'default-avatar.svg' 
    ? 'uploads/perfiles/' . $usuario['foto_perfil'] 
    : 'assets/img/default-avatar.svg';
echo "<p><strong>URL:</strong> $foto_url</p>";

echo "<h3>Vista Previa</h3>";
echo "<img src='$foto_url' alt='Foto de perfil' style='width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #667eea;' onerror=\"this.src='assets/img/default-avatar.svg'\">";

echo "<hr>";
echo "<p><a href='" . ($_SESSION['rol'] ?? 'admin') . "/dashboard.php'>← Volver al Dashboard</a></p>";
