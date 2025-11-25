<?php
/**
 * Script de Mantenimiento Post-Instalación
 * Ejecutar después de reinstalar la base de datos
 */

echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║       🔧 HERMES EXPRESS - Mantenimiento Post-Instalación 🔧      ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

$errores = 0;
$advertencias = 0;
$exitoso = 0;

// =================================================================
// 1. CREAR DIRECTORIOS NECESARIOS
// =================================================================
echo "📁 Verificando directorios...\n";
echo str_repeat("-", 70) . "\n";

$dirs = [
    'uploads/perfiles',
    'uploads/usuarios', 
    'uploads/entregas',
    'uploads/gastos',
    'uploads/caja_chica',
    'backups',
    'logs',
    'cache'
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0777, true)) {
            echo "  ✓ Creado: $dir\n";
            $exitoso++;
        } else {
            echo "  ✗ Error al crear: $dir\n";
            $errores++;
        }
    } else {
        echo "  ✓ Existe: $dir\n";
        $exitoso++;
    }
}

echo "\n";

// =================================================================
// 2. CREAR IMÁGENES POR DEFECTO
// =================================================================
echo "🖼️  Creando imágenes por defecto...\n";
echo str_repeat("-", 70) . "\n";

// default.png
$default_png = 'uploads/perfiles/default.png';
if (!file_exists($default_png)) {
    $width = 200;
    $height = 200;
    $image = imagecreatetruecolor($width, $height);
    
    $bg_color = imagecolorallocate($image, 108, 117, 125);
    $text_color = imagecolorallocate($image, 255, 255, 255);
    
    imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);
    imagefilledellipse($image, $width/2, $height/3, 80, 80, $text_color);
    imagefilledellipse($image, $width/2, $height - 40, 140, 140, $text_color);
    
    if (imagepng($image, $default_png)) {
        echo "  ✓ Creado: $default_png\n";
        $exitoso++;
    } else {
        echo "  ✗ Error al crear: $default_png\n";
        $errores++;
    }
    imagedestroy($image);
} else {
    echo "  ✓ Ya existe: $default_png\n";
    $exitoso++;
}

// default-avatar.svg
$default_svg = 'uploads/perfiles/default-avatar.svg';
if (!file_exists($default_svg)) {
    $svg = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="200" height="200" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
    <rect width="200" height="200" fill="#6c757d"/>
    <circle cx="100" cy="70" r="40" fill="#ffffff"/>
    <ellipse cx="100" cy="160" rx="70" ry="70" fill="#ffffff"/>
</svg>';
    
    if (file_put_contents($default_svg, $svg)) {
        echo "  ✓ Creado: $default_svg\n";
        $exitoso++;
    } else {
        echo "  ✗ Error al crear: $default_svg\n";
        $errores++;
    }
} else {
    echo "  ✓ Ya existe: $default_svg\n";
    $exitoso++;
}

echo "\n";

// =================================================================
// 3. VERIFICAR DEPENDENCIAS
// =================================================================
echo "📦 Verificando dependencias...\n";
echo str_repeat("-", 70) . "\n";

if (file_exists('vendor/autoload.php')) {
    echo "  ✓ Composer: vendor/autoload.php encontrado\n";
    $exitoso++;
} else {
    echo "  ⚠️  Composer: vendor/autoload.php NO encontrado\n";
    echo "     Para usar importación de Excel, ejecuta: composer install\n";
    $advertencias++;
}

// Verificar extensiones de PHP
$extensiones_requeridas = ['gd', 'mysqli', 'json', 'mbstring'];
foreach ($extensiones_requeridas as $ext) {
    if (extension_loaded($ext)) {
        echo "  ✓ PHP $ext: Instalada\n";
        $exitoso++;
    } else {
        echo "  ✗ PHP $ext: NO instalada\n";
        $errores++;
    }
}

echo "\n";

// =================================================================
// 4. VERIFICAR BASE DE DATOS
// =================================================================
echo "🗄️  Verificando base de datos...\n";
echo str_repeat("-", 70) . "\n";

try {
    require_once 'config/database.php';
    $db = Database::getInstance()->getConnection();
    
    echo "  ✓ Conexión a MySQL: OK\n";
    $exitoso++;
    
    // Verificar que la BD existe
    $result = $db->query("SELECT DATABASE() as db");
    $row = $result->fetch_assoc();
    
    if ($row['db'] === 'hermes_express') {
        echo "  ✓ Base de datos 'hermes_express': OK\n";
        $exitoso++;
        
        // Contar tablas
        $result = $db->query("SELECT COUNT(*) as total FROM information_schema.tables WHERE table_schema = 'hermes_express'");
        $row = $result->fetch_assoc();
        echo "  ✓ Tablas encontradas: {$row['total']}\n";
        $exitoso++;
        
        // Verificar tabla usuarios
        $result = $db->query("SELECT COUNT(*) as total FROM usuarios");
        $row = $result->fetch_assoc();
        echo "  ✓ Usuarios registrados: {$row['total']}\n";
        $exitoso++;
        
    } else {
        echo "  ✗ Base de datos 'hermes_express' NO encontrada\n";
        echo "     Ejecuta: Get-Content database\\install_complete.sql | mysql -u root\n";
        $errores++;
    }
    
} catch (Exception $e) {
    echo "  ✗ Error de BD: " . $e->getMessage() . "\n";
    $errores++;
}

echo "\n";

// =================================================================
// 5. CREAR ARCHIVO .htaccess DE SEGURIDAD
// =================================================================
echo "🔒 Verificando seguridad...\n";
echo str_repeat("-", 70) . "\n";

$htaccess_uploads = 'uploads/.htaccess';
if (!file_exists($htaccess_uploads)) {
    $content = '# Prevenir ejecución de scripts
<FilesMatch "\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$">
    Require all denied
</FilesMatch>

# Permitir solo imágenes y PDFs
<FilesMatch "\.(jpg|jpeg|png|gif|svg|pdf)$">
    Require all granted
</FilesMatch>';
    
    if (file_put_contents($htaccess_uploads, $content)) {
        echo "  ✓ Creado: $htaccess_uploads (protección de uploads)\n";
        $exitoso++;
    } else {
        echo "  ✗ Error al crear: $htaccess_uploads\n";
        $errores++;
    }
} else {
    echo "  ✓ Ya existe: $htaccess_uploads\n";
    $exitoso++;
}

echo "\n";

// =================================================================
// 6. CREAR ARCHIVO DE CONFIGURACIÓN DE EJEMPLO
// =================================================================
echo "⚙️  Verificando configuración...\n";
echo str_repeat("-", 70) . "\n";

if (file_exists('config/config.php')) {
    echo "  ✓ config/config.php: OK\n";
    $exitoso++;
} else {
    echo "  ✗ config/config.php: NO encontrado\n";
    $errores++;
}

if (file_exists('config/database.php')) {
    echo "  ✓ config/database.php: OK\n";
    $exitoso++;
} else {
    echo "  ✗ config/database.php: NO encontrado\n";
    $errores++;
}

echo "\n";

// =================================================================
// RESUMEN
// =================================================================
echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║                          📊 RESUMEN                               ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

echo "  ✓ Exitoso:      $exitoso\n";
echo "  ⚠️  Advertencias: $advertencias\n";
echo "  ✗ Errores:      $errores\n\n";

if ($errores === 0 && $advertencias === 0) {
    echo "╔════════════════════════════════════════════════════════════════════╗\n";
    echo "║               ✅ SISTEMA COMPLETAMENTE CONFIGURADO ✅              ║\n";
    echo "╚════════════════════════════════════════════════════════════════════╝\n\n";
    echo "El sistema está listo para usar:\n";
    echo "  👉 http://localhost/pruebitaaa/\n\n";
    echo "Credenciales por defecto:\n";
    echo "  Email:    admin@hermesexpress.com\n";
    echo "  Password: password123\n\n";
} elseif ($errores === 0) {
    echo "╔════════════════════════════════════════════════════════════════════╗\n";
    echo "║              ⚠️  CONFIGURADO CON ADVERTENCIAS ⚠️                   ║\n";
    echo "╚════════════════════════════════════════════════════════════════════╝\n\n";
    echo "El sistema está funcional pero revisa las advertencias arriba.\n\n";
} else {
    echo "╔════════════════════════════════════════════════════════════════════╗\n";
    echo "║                  ❌ ERRORES ENCONTRADOS ❌                         ║\n";
    echo "╚════════════════════════════════════════════════════════════════════╝\n\n";
    echo "Por favor, corrige los errores antes de continuar.\n\n";
}

echo "Documentación:\n";
echo "  📖 PROBLEMAS_RESUELTOS.md - Problemas comunes y soluciones\n";
echo "  📝 MEJORAS_APLICADAS.md    - Mejoras de seguridad implementadas\n";
echo "  🚀 INICIO_RAPIDO.txt       - Guía de inicio rápido\n\n";
?>
