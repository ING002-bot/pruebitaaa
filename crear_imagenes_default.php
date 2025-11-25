<?php
/**
 * Script para crear imagen default.png si no existe
 */

$upload_dirs = [
    'uploads/perfiles',
    'uploads/usuarios',
    'uploads/entregas',
    'uploads/gastos',
    'uploads/caja_chica'
];

// Crear directorios si no existen
foreach ($upload_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
        echo "✓ Directorio creado: $dir\n";
    }
}

// Crear imagen default.png
$default_image_path = 'uploads/perfiles/default.png';

if (!file_exists($default_image_path)) {
    // Crear imagen de 200x200 con fondo gris y texto
    $width = 200;
    $height = 200;
    $image = imagecreatetruecolor($width, $height);
    
    // Colores
    $bg_color = imagecolorallocate($image, 108, 117, 125); // Gris Bootstrap
    $text_color = imagecolorallocate($image, 255, 255, 255); // Blanco
    
    // Fondo
    imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);
    
    // Círculo en el centro (cabeza)
    $circle_x = $width / 2;
    $circle_y = $height / 3;
    $circle_radius = 40;
    imagefilledellipse($image, $circle_x, $circle_y, $circle_radius * 2, $circle_radius * 2, $text_color);
    
    // Cuerpo (medio círculo)
    $body_y = $height - 40;
    $body_radius = 70;
    imagefilledellipse($image, $circle_x, $body_y, $body_radius * 2, $body_radius * 2, $text_color);
    
    // Guardar
    imagepng($image, $default_image_path);
    imagedestroy($image);
    
    echo "✓ Imagen creada: $default_image_path\n";
} else {
    echo "✓ Imagen ya existe: $default_image_path\n";
}

// Crear también default-avatar.svg (referenciado en la BD)
$svg_path = 'uploads/perfiles/default-avatar.svg';
if (!file_exists($svg_path)) {
    $svg_content = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="200" height="200" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
    <rect width="200" height="200" fill="#6c757d"/>
    <circle cx="100" cy="70" r="40" fill="#ffffff"/>
    <ellipse cx="100" cy="160" rx="70" ry="70" fill="#ffffff"/>
</svg>';
    
    file_put_contents($svg_path, $svg_content);
    echo "✓ SVG creado: $svg_path\n";
} else {
    echo "✓ SVG ya existe: $svg_path\n";
}

echo "\n✅ Proceso completado\n";
?>
