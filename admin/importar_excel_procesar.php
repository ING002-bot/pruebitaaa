<?php
require_once '../config/config.php';
require_once '../vendor/autoload.php'; // PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

requireRole(['admin', 'asistente']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: importar_excel.php');
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    // Validar archivo
    if (!isset($_FILES['archivo_excel']) || $_FILES['archivo_excel']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir el archivo');
    }
    
    $archivo = $_FILES['archivo_excel'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, ['xlsx', 'xls'])) {
        throw new Exception('Formato de archivo no válido. Use .xlsx o .xls');
    }
    
    if ($archivo['size'] > 10 * 1024 * 1024) { // 10MB
        throw new Exception('El archivo es demasiado grande (máx. 10MB)');
    }
    
    // Crear directorio si no existe
    $upload_dir = '../uploads/importaciones/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Guardar archivo
    $nombre_archivo = 'importacion_' . date('Ymd_His') . '.' . $extension;
    $ruta_destino = $upload_dir . $nombre_archivo;
    
    if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
        throw new Exception('Error al guardar el archivo');
    }
    
    // Registrar importación
    $observaciones = trim($_POST['observaciones'] ?? '');
    $usuario_id = $_SESSION['usuario_id'];
    
    $stmt = $db->prepare("INSERT INTO importaciones_archivos (nombre_archivo, ruta_archivo, procesado_por, observaciones, estado) VALUES (?, ?, ?, ?, 'procesando')");
    $stmt->bind_param("ssis", $nombre_archivo, $ruta_destino, $usuario_id, $observaciones);
    $stmt->execute();
    $importacion_id = $db->insert_id;
    
    // Procesar Excel
    $spreadsheet = IOFactory::load($ruta_destino);
    $worksheet = $spreadsheet->getActiveSheet();
    $highestRow = $worksheet->getHighestRow();
    
    $total_registros = $highestRow - 1; // -1 por la cabecera
    $importados = 0;
    $fallidos = 0;
    $errores = [];
    
    // Actualizar total de registros
    $db->query("UPDATE importaciones_archivos SET total_registros = $total_registros WHERE id = $importacion_id");
    
    // Procesar filas (asumiendo que fila 1 es cabecera)
    for ($row = 2; $row <= $highestRow; $row++) {
        try {
            $codigo_seguimiento = trim($worksheet->getCell('A' . $row)->getValue());
            $destinatario_nombre = trim($worksheet->getCell('B' . $row)->getValue());
            $telefono = trim($worksheet->getCell('C' . $row)->getValue());
            $direccion = trim($worksheet->getCell('D' . $row)->getValue());
            $ciudad = trim($worksheet->getCell('E' . $row)->getValue() ?? '');
            $provincia = trim($worksheet->getCell('F' . $row)->getValue() ?? '');
            
            // Validaciones básicas
            if (empty($codigo_seguimiento) || empty($destinatario_nombre) || empty($direccion)) {
                throw new Exception("Fila $row: Datos incompletos");
            }
            
            // Verificar si el código ya existe
            $check = $db->prepare("SELECT id FROM paquetes WHERE codigo_seguimiento = ?");
            $check->bind_param("s", $codigo_seguimiento);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                throw new Exception("Fila $row: Código $codigo_seguimiento ya existe");
            }
            
            // Insertar paquete
            $stmt = $db->prepare("INSERT INTO paquetes (
                codigo_seguimiento, 
                destinatario_nombre, 
                destinatario_telefono, 
                direccion_completa, 
                ciudad, 
                provincia, 
                archivo_importacion,
                estado, 
                prioridad
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente', 'normal')");
            
            $stmt->bind_param("sssssss", 
                $codigo_seguimiento,
                $destinatario_nombre,
                $telefono,
                $direccion,
                $ciudad,
                $provincia,
                $nombre_archivo
            );
            
            if ($stmt->execute()) {
                $importados++;
            } else {
                throw new Exception("Fila $row: Error al insertar - " . $stmt->error);
            }
            
        } catch (Exception $e) {
            $fallidos++;
            $errores[] = $e->getMessage();
        }
    }
    
    // Actualizar estado de importación
    $estado_final = ($fallidos > 0) ? 'completado' : 'completado';
    $errores_json = json_encode($errores);
    
    $stmt = $db->prepare("UPDATE importaciones_archivos SET 
        registros_importados = ?, 
        registros_fallidos = ?, 
        estado = ?,
        observaciones = CONCAT(COALESCE(observaciones, ''), '\n\nErrores: ', ?)
        WHERE id = ?");
    $stmt->bind_param("iissi", $importados, $fallidos, $estado_final, $errores_json, $importacion_id);
    $stmt->execute();
    
    $_SESSION['success_message'] = "Importación completada: $importados paquetes importados" . 
        ($fallidos > 0 ? ", $fallidos fallidos" : "");
    
} catch (Exception $e) {
    // Actualizar a error si hay importacion_id
    if (isset($importacion_id)) {
        $db->query("UPDATE importaciones_archivos SET estado = 'error' WHERE id = $importacion_id");
    }
    $_SESSION['error_message'] = $e->getMessage();
}

header('Location: importar_excel.php');
exit;
