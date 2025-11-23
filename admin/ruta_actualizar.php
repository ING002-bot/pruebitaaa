<?php
require_once '../config/config.php';
requireRole(['admin', 'asistente']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = Database::getInstance()->getConnection();
    
    try {
        // Obtener datos del formulario
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $zona = $_POST['zona'];
        $ubicaciones = isset($_POST['ubicaciones']) && is_array($_POST['ubicaciones']) 
            ? implode(', ', $_POST['ubicaciones']) 
            : '';
        $nombre = $_POST['nombre'];
        $fecha_ruta = $_POST['fecha_ruta'];
        $repartidor_id = !empty($_POST['repartidor_id']) ? (int)$_POST['repartidor_id'] : null;
        $descripcion = $_POST['descripcion'];
        
        // Verificar que la ruta existe y está en estado planificada
        $stmt = $db->prepare("SELECT estado FROM rutas WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Error al preparar consulta: " . $db->error);
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $ruta = $result->fetch_assoc();
        $stmt->close();
        
        if (!$ruta) {
            header('Location: rutas.php?error=no_encontrada');
            exit;
        }
        
        if ($ruta['estado'] != 'planificada') {
            header('Location: rutas.php?error=no_editable');
            exit;
        }
        
        // Actualizar ruta
        $sql = "UPDATE rutas SET 
                zona = ?,
                ubicaciones = ?,
                nombre = ?,
                fecha_ruta = ?,
                repartidor_id = ?,
                descripcion = ?
                WHERE id = ?";
        
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar consulta: " . $db->error);
        }
        
        $stmt->bind_param(
            "ssssiis",
            $zona,
            $ubicaciones,
            $nombre,
            $fecha_ruta,
            $repartidor_id,
            $descripcion,
            $id
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar consulta: " . $stmt->error);
        }
        $stmt->close();
        
        header('Location: ruta_detalle.php?id=' . $id . '&success=actualizada');
        exit;
        
    } catch (Exception $e) {
        error_log("Error al actualizar ruta: " . $e->getMessage());
        header('Location: ruta_editar.php?id=' . $id . '&error=db');
        exit;
    }
} else {
    header('Location: rutas.php');
    exit;
}
