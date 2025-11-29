<?php
require_once '../config/config.php';
require_once '../lib/SimpleXLSX.php'; // Lector de Excel ligero

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
    
    // Procesar Excel con SimpleXLSX
    try {
        if (!file_exists($ruta_destino)) {
            throw new Exception('El archivo no se encontró después de subirlo');
        }
        
        $xlsx = new SimpleXLSX($ruta_destino);
        
        if (!$xlsx) {
            throw new Exception('No se pudo leer el archivo Excel. Error: ' . SimpleXLSX::parseError());
        }
        
        $rows = $xlsx->rows();
        
        if (empty($rows)) {
            throw new Exception('El archivo Excel está vacío o no tiene datos');
        }
        
        $total_registros = count($rows) - 1; // -1 por la cabecera
        
        if ($total_registros <= 0) {
            throw new Exception('El archivo solo contiene la cabecera, no hay datos para importar');
        }
        
        $importados = 0;
        $fallidos = 0;
        $errores = [];
        
        // Actualizar total de registros
        $db->query("UPDATE importaciones_archivos SET total_registros = $total_registros WHERE id = $importacion_id");
        
        // Procesar filas (asumiendo que fila 1 es cabecera)
        $rowNum = 1;
        foreach ($rows as $rowIndex => $rowData) {
            $rowNum++;
            
            // Saltar la cabecera (primera fila)
            if ($rowNum <= 2) {
                continue;
            }
            
            try {
                // Leer columnas según el formato del Excel
                $codigo_seguimiento = trim($xlsx->getCell($rowIndex, 'A') ?? '');
                $departamento = trim($xlsx->getCell($rowIndex, 'D') ?? '');
                $provincia = trim($xlsx->getCell($rowIndex, 'E') ?? '');
                $distrito = trim($xlsx->getCell($rowIndex, 'F') ?? '');
                $destinatario_nombre = trim($xlsx->getCell($rowIndex, 'J') ?? '');
                $direccion = trim($xlsx->getCell($rowIndex, 'K') ?? '');
                $peso = trim($xlsx->getCell($rowIndex, 'M') ?? '0');
                $telefono = trim($xlsx->getCell($rowIndex, 'N') ?? '');
                
                // Validaciones básicas
                if (empty($codigo_seguimiento)) {
                    $errores[] = "Fila $rowNum: Código de seguimiento vacío (Columna A)";
                    $fallidos++;
                    continue;
                }
                
                if (empty($destinatario_nombre)) {
                    $errores[] = "Fila $rowNum: Nombre del consignado vacío (Columna J)";
                    $fallidos++;
                    continue;
                }
                
                if (empty($direccion)) {
                    throw new Exception("Fila $rowNum: Dirección vacía");
                }
                
                // Verificar si el código ya existe
                $check = $db->prepare("SELECT id FROM paquetes WHERE codigo_seguimiento = ?");
                $check->bind_param("s", $codigo_seguimiento);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    throw new Exception("Fila $rowNum: Código $codigo_seguimiento ya existe");
                }
                
                // Limpiar y convertir peso
                $peso_decimal = floatval(str_replace(',', '.', $peso));
                
                // Construir ciudad completa (Departamento - Provincia - Distrito)
                $ciudad_completa = trim("$departamento - $provincia - $distrito", ' -');
                if (empty($ciudad_completa)) {
                    $ciudad_completa = $departamento;
                }
                
                // Insertar paquete
                $stmt = $db->prepare("INSERT INTO paquetes (
                    codigo_seguimiento, 
                    destinatario_nombre, 
                    destinatario_telefono, 
                    direccion_completa, 
                    ciudad, 
                    provincia,
                    peso,
                    archivo_importacion,
                    estado, 
                    prioridad
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', 'normal')");
                
                $stmt->bind_param("ssssssds", 
                    $codigo_seguimiento,
                    $destinatario_nombre,
                    $telefono,
                    $direccion,
                    $ciudad_completa,
                    $provincia,
                    $peso_decimal,
                    $nombre_archivo
                );
                
                if ($stmt->execute()) {
                    $importados++;
                } else {
                    throw new Exception("Fila $rowNum: Error al insertar - " . $stmt->error);
                }
                
            } catch (Exception $e) {
                $fallidos++;
                $errores[] = $e->getMessage();
            }
        }
        
    } catch (Exception $e) {
        throw new Exception('Error al procesar Excel: ' . $e->getMessage());
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
