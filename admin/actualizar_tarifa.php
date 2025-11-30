<?php
require_once '../config/config.php';

// Verificar autenticación y rol de administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'administrador') {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => 'No tienes permisos para realizar esta acción'
    ];
    header("Location: tarifas.php");
    exit();
}

if ($_POST) {
    try {
        $db = Database::getInstance()->getConnection();
        
        $id = (int)$_POST['id'];
        $categoria = trim($_POST['categoria']);
        $nombre_zona = trim($_POST['nombre_zona']);
        $costo_cliente = (float)$_POST['costo_cliente'];
        $tarifa_repartidor = (float)$_POST['tarifa_repartidor'];
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        // Validaciones
        if (empty($categoria) || empty($nombre_zona)) {
            throw new Exception("Todos los campos son requeridos");
        }
        
        if ($costo_cliente <= 0 || $tarifa_repartidor <= 0) {
            throw new Exception("Las tarifas deben ser mayores a cero");
        }
        
        if ($costo_cliente <= $tarifa_repartidor) {
            throw new Exception("La tarifa al cliente debe ser mayor que la tarifa al repartidor para tener ganancia");
        }
        
        // Actualizar la tarifa
        $stmt = $db->prepare("
            UPDATE zonas_tarifas 
            SET categoria = ?, 
                nombre_zona = ?, 
                costo_cliente = ?, 
                tarifa_repartidor = ?, 
                activo = ?, 
                fecha_actualizacion = NOW() 
            WHERE id = ?
        ");
        
        $stmt->bind_param("ssddii", $categoria, $nombre_zona, $costo_cliente, $tarifa_repartidor, $activo, $id);
        
        if ($stmt->execute()) {
            $ganancia = $costo_cliente - $tarifa_repartidor;
            $margen = (($ganancia / $costo_cliente) * 100);
            
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => "Tarifa actualizada correctamente. Ganancia: S/ " . number_format($ganancia, 2) . " (" . number_format($margen, 1) . "% margen)"
            ];
        } else {
            throw new Exception("Error al actualizar la tarifa: " . $stmt->error);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => $e->getMessage()
        ];
    }
}

header("Location: tarifas.php");
exit();
?>