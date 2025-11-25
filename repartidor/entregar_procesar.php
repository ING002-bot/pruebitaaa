<?php
require_once '../config/config.php';
require_once '../config/notificaciones_helper.php';
requireRole('repartidor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . 'repartidor/entregar.php');
}

$db = Database::getInstance()->getConnection();
$repartidor_id = $_SESSION['usuario_id'];

try {
    // Validar datos
    $paquete_id = (int)$_POST['paquete_id'];
    $tipo_entrega = sanitize($_POST['tipo_entrega']);
    $receptor_nombre = sanitize($_POST['receptor_nombre'] ?? '');
    $receptor_dni = sanitize($_POST['receptor_dni'] ?? '');
    $observaciones = sanitize($_POST['observaciones'] ?? '');
    $latitud = (float)($_POST['latitud_entrega'] ?? 0);
    $longitud = (float)($_POST['longitud_entrega'] ?? 0);
    
    // Verificar que el paquete pertenece al repartidor
    $stmt = $db->prepare("SELECT * FROM paquetes WHERE id = ? AND repartidor_id = ?");
    $stmt->bind_param("ii", $paquete_id, $repartidor_id);
    $stmt->execute();
    $paquete = $stmt->get_result()->fetch_assoc();
    
    if (!$paquete) {
        setFlashMessage('danger', 'Paquete no encontrado o no autorizado');
        redirect(APP_URL . 'repartidor/entregar.php');
    }
    
    // CALCULAR Y ACTUALIZAR costo_envio si no existe o es 0
    if (empty($paquete['costo_envio']) || $paquete['costo_envio'] == 0) {
        $costo_calculado = 3.50; // Valor por defecto
        
        // Extraer distrito de ciudad (última parte después del último " - ")
        if (!empty($paquete['ciudad'])) {
            $partes = array_map('trim', explode(' - ', $paquete['ciudad']));
            $distrito = trim(end($partes));
            
            // Buscar tarifa por distrito
            $stmt_tarifa = $db->prepare("SELECT tarifa_repartidor FROM zonas_tarifas 
                                         WHERE UPPER(TRIM(nombre_zona)) = UPPER(TRIM(?)) AND activo = 1 
                                         LIMIT 1");
            if ($stmt_tarifa) {
                $stmt_tarifa->bind_param("s", $distrito);
                $stmt_tarifa->execute();
                $result = $stmt_tarifa->get_result();
                if ($row = $result->fetch_assoc()) {
                    $costo_calculado = $row['tarifa_repartidor'];
                }
            }
        }
        
        // Si no encontró, buscar por provincia
        if ($costo_calculado == 3.50 && !empty($paquete['provincia'])) {
            $stmt_tarifa2 = $db->prepare("SELECT tarifa_repartidor FROM zonas_tarifas 
                                          WHERE UPPER(TRIM(nombre_zona)) = UPPER(TRIM(?)) AND activo = 1 
                                          LIMIT 1");
            if ($stmt_tarifa2) {
                $stmt_tarifa2->bind_param("s", $paquete['provincia']);
                $stmt_tarifa2->execute();
                $result2 = $stmt_tarifa2->get_result();
                if ($row2 = $result2->fetch_assoc()) {
                    $costo_calculado = $row2['tarifa_repartidor'];
                }
            }
        }
        
        // Actualizar el paquete con el costo calculado
        $stmt_update_costo = $db->prepare("UPDATE paquetes SET costo_envio = ? WHERE id = ?");
        if ($stmt_update_costo) {
            $stmt_update_costo->bind_param("di", $costo_calculado, $paquete_id);
            $stmt_update_costo->execute();
            // Actualizar el array local para usar el valor correcto
            $paquete['costo_envio'] = $costo_calculado;
        }
    }
    
    // Procesar foto principal
    $foto_entrega = null;
    if (!empty($_POST['foto_entrega_data'])) {
        // Foto desde cámara (base64)
        $img_data = $_POST['foto_entrega_data'];
        $img_data = str_replace('data:image/jpeg;base64,', '', $img_data);
        $img_data = str_replace(' ', '+', $img_data);
        $data = base64_decode($img_data);
        
        $filename = 'entrega_' . $paquete_id . '_' . time() . '.jpg';
        $filepath = UPLOADS_DIR . 'entregas/' . $filename;
        
        if (!file_exists(UPLOADS_DIR . 'entregas/')) {
            mkdir(UPLOADS_DIR . 'entregas/', 0777, true);
        }
        
        file_put_contents($filepath, $data);
        $foto_entrega = $filename;
    } elseif (isset($_FILES['foto_entrega']) && $_FILES['foto_entrega']['error'] === UPLOAD_ERR_OK) {
        // Foto desde archivo con validación mejorada
        $file = $_FILES['foto_entrega'];
        
        try {
            // Validar imagen
            validar_imagen($file);
            
            // Generar nombre único y seguro
            $filename = generate_unique_filename($file['name'], 'entrega_' . $paquete_id);
            $filepath = UPLOADS_DIR . 'entregas/' . $filename;
            
            if (!file_exists(UPLOADS_DIR . 'entregas/')) {
                mkdir(UPLOADS_DIR . 'entregas/', 0777, true);
            }
            
            move_uploaded_file($file['tmp_name'], $filepath);
            $foto_entrega = $filename;
        } catch (Exception $e) {
            throw new Exception('Error en foto principal: ' . $e->getMessage());
        }
    }
    
    // Procesar fotos adicionales con validación
    $foto_adicional_1 = null;
    if (isset($_FILES['foto_adicional_1']) && $_FILES['foto_adicional_1']['error'] === UPLOAD_ERR_OK) {
        try {
            $file = $_FILES['foto_adicional_1'];
            validar_imagen($file);
            $filename = generate_unique_filename($file['name'], 'entrega_' . $paquete_id . '_adicional1');
            $filepath = UPLOADS_DIR . 'entregas/' . $filename;
            move_uploaded_file($file['tmp_name'], $filepath);
            $foto_adicional_1 = $filename;
        } catch (Exception $e) {
            // Foto adicional es opcional, solo registrar error
            error_log('Error en foto adicional 1: ' . $e->getMessage());
        }
    }
    
    $foto_adicional_2 = null;
    if (isset($_FILES['foto_adicional_2']) && $_FILES['foto_adicional_2']['error'] === UPLOAD_ERR_OK) {
        try {
            $file = $_FILES['foto_adicional_2'];
            validar_imagen($file);
            $filename = generate_unique_filename($file['name'], 'entrega_' . $paquete_id . '_adicional2');
            $filepath = UPLOADS_DIR . 'entregas/' . $filename;
            move_uploaded_file($file['tmp_name'], $filepath);
            $foto_adicional_2 = $filename;
        } catch (Exception $e) {
            // Foto adicional es opcional, solo registrar error
            error_log('Error en foto adicional 2: ' . $e->getMessage());
        }
    }
    
    // Iniciar transacción
    $db->autocommit(false);
    
    // Registrar entrega
    $sql = "INSERT INTO entregas (paquete_id, repartidor_id, receptor_nombre, receptor_dni, 
            foto_entrega, foto_adicional_1, foto_adicional_2, latitud_entrega, longitud_entrega, 
            tipo_entrega, observaciones) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param("iisssssddss", 
        $paquete_id,
        $repartidor_id,
        $receptor_nombre,
        $receptor_dni,
        $foto_entrega,
        $foto_adicional_1,
        $foto_adicional_2,
        $latitud,
        $longitud,
        $tipo_entrega,
        $observaciones
    );
    $stmt->execute();
    
    // Actualizar estado del paquete
    if ($tipo_entrega === 'exitosa') {
        $nuevo_estado = 'entregado';
        
        // Si el paquete estaba rezagado, marcarlo como solucionado
        $sql_resolver_rezagado = "UPDATE paquetes_rezagados 
                                  SET solucionado = 1, 
                                      fecha_solucion = NOW() 
                                  WHERE paquete_id = ? AND solucionado = 0";
        $stmt_resolver = $db->prepare($sql_resolver_rezagado);
        $stmt_resolver->bind_param("i", $paquete_id);
        $stmt_resolver->execute();
        
    } elseif ($tipo_entrega === 'no_encontrado' || $tipo_entrega === 'rechazada') {
        $nuevo_estado = 'rezagado';
        
        // Registrar en tabla de rezagados (nuevo intento fallido)
        $motivo = $tipo_entrega === 'no_encontrado' ? 'destinatario_ausente' : 'rechazo';
        $sql_rezagado = "INSERT INTO paquetes_rezagados (paquete_id, motivo, descripcion_motivo) 
                         VALUES (?, ?, ?)";
        $stmt_rezagado = $db->prepare($sql_rezagado);
        $stmt_rezagado->bind_param("iss", $paquete_id, $motivo, $observaciones);
        $stmt_rezagado->execute();
    } else {
        $nuevo_estado = 'entregado';
    }
    
    $sql_update = "UPDATE paquetes SET estado = ?, fecha_entrega = NOW(), 
                   intentos_entrega = intentos_entrega + 1 
                   WHERE id = ?";
    $stmt_update = $db->prepare($sql_update);
    $stmt_update->bind_param("si", $nuevo_estado, $paquete_id);
    $stmt_update->execute();
    
    // Registrar ingreso si la entrega fue exitosa
    if ($tipo_entrega === 'exitosa') {
        // Usar el costo_envio ya calculado del paquete
        $monto = !empty($paquete['costo_envio']) ? $paquete['costo_envio'] : 3.50;
        
        $sql_ingreso = "INSERT INTO ingresos (tipo, concepto, monto, paquete_id, registrado_por) 
                        VALUES ('envio', ?, ?, ?, ?)";
        $stmt_ingreso = $db->prepare($sql_ingreso);
        if ($stmt_ingreso) {
            $zona_info = !empty($paquete['ciudad']) ? $paquete['ciudad'] : ($paquete['provincia'] ?? 'Sin zona');
            $concepto = 'Entrega de paquete ' . $paquete['codigo_seguimiento'] . ' - ' . $zona_info;
            $stmt_ingreso->bind_param("sdii", $concepto, $monto, $paquete_id, $repartidor_id);
            $stmt_ingreso->execute();
        }
    }
    
    // Actualizar ruta si existe
    $sql_ruta = "UPDATE ruta_paquetes SET estado = ? 
                 WHERE paquete_id = ? AND estado = 'pendiente'";
    $stmt_ruta = $db->prepare($sql_ruta);
    if ($stmt_ruta) {
        $estado_ruta = $tipo_entrega === 'exitosa' ? 'entregado' : 'fallido';
        $stmt_ruta->bind_param("si", $estado_ruta, $paquete_id);
        $stmt_ruta->execute();
    }
    
    // Registrar actividad
    logActivity('Registro de entrega', 'entregas', $paquete_id, "Tipo: $tipo_entrega");
    
    // Crear notificaciones
    if ($tipo_entrega === 'exitosa') {
        $admins = obtenerAdministradores();
        $repartidor_nombre = $_SESSION['nombre'] . ' ' . $_SESSION['apellido'];
        notificarEntregaExitosa($admins, $paquete['codigo_seguimiento'], $repartidor_nombre);
    } elseif (in_array($tipo_entrega, ['rechazada', 'no_encontrado'])) {
        $admins = obtenerAdministradores();
        notificarPaqueteRezagado($admins, $paquete_id, $paquete['codigo_seguimiento']);
    }
    
    // Commit
    $db->commit();
    $db->autocommit(true);
    
    setFlashMessage('success', 'Entrega registrada exitosamente');
    redirect(APP_URL . 'repartidor/dashboard.php');
    
} catch (Exception $e) {
    $db->rollback();
    $db->autocommit(true);
    
    error_log("Error al registrar entrega: " . $e->getMessage());
    setFlashMessage('danger', 'Error al registrar la entrega. Por favor, intente nuevamente.');
    redirect(APP_URL . 'repartidor/entregar.php');
}
