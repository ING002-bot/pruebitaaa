<?php
require_once '../config/config.php';
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
    $stmt->execute([$paquete_id, $repartidor_id]);
    $paquete = $stmt->fetch();
    
    if (!$paquete) {
        setFlashMessage('danger', 'Paquete no encontrado o no autorizado');
        redirect(APP_URL . 'repartidor/entregar.php');
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
        // Foto desde archivo
        $file = $_FILES['foto_entrega'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'entrega_' . $paquete_id . '_' . time() . '.' . $ext;
        $filepath = UPLOADS_DIR . 'entregas/' . $filename;
        
        if (!file_exists(UPLOADS_DIR . 'entregas/')) {
            mkdir(UPLOADS_DIR . 'entregas/', 0777, true);
        }
        
        move_uploaded_file($file['tmp_name'], $filepath);
        $foto_entrega = $filename;
    }
    
    // Procesar fotos adicionales
    $foto_adicional_1 = null;
    if (isset($_FILES['foto_adicional_1']) && $_FILES['foto_adicional_1']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto_adicional_1'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'entrega_' . $paquete_id . '_adicional1_' . time() . '.' . $ext;
        $filepath = UPLOADS_DIR . 'entregas/' . $filename;
        move_uploaded_file($file['tmp_name'], $filepath);
        $foto_adicional_1 = $filename;
    }
    
    $foto_adicional_2 = null;
    if (isset($_FILES['foto_adicional_2']) && $_FILES['foto_adicional_2']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto_adicional_2'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'entrega_' . $paquete_id . '_adicional2_' . time() . '.' . $ext;
        $filepath = UPLOADS_DIR . 'entregas/' . $filename;
        move_uploaded_file($file['tmp_name'], $filepath);
        $foto_adicional_2 = $filename;
    }
    
    // Iniciar transacción
    $db->beginTransaction();
    
    // Registrar entrega
    $sql = "INSERT INTO entregas (paquete_id, repartidor_id, receptor_nombre, receptor_dni, 
            foto_entrega, foto_adicional_1, foto_adicional_2, latitud_entrega, longitud_entrega, 
            tipo_entrega, observaciones) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
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
    ]);
    
    // Actualizar estado del paquete
    if ($tipo_entrega === 'exitosa') {
        $nuevo_estado = 'entregado';
        
        // Si el paquete estaba rezagado, marcarlo como solucionado
        $sql_resolver_rezagado = "UPDATE paquetes_rezagados 
                                  SET solucionado = 1, 
                                      fecha_solucion = NOW() 
                                  WHERE paquete_id = ? AND solucionado = 0";
        $stmt_resolver = $db->prepare($sql_resolver_rezagado);
        $stmt_resolver->execute([$paquete_id]);
        
    } elseif ($tipo_entrega === 'no_encontrado' || $tipo_entrega === 'rechazada') {
        $nuevo_estado = 'rezagado';
        
        // Registrar en tabla de rezagados (nuevo intento fallido)
        $motivo = $tipo_entrega === 'no_encontrado' ? 'destinatario_ausente' : 'rechazo';
        $sql_rezagado = "INSERT INTO paquetes_rezagados (paquete_id, motivo, descripcion_motivo) 
                         VALUES (?, ?, ?)";
        $stmt_rezagado = $db->prepare($sql_rezagado);
        $stmt_rezagado->execute([$paquete_id, $motivo, $observaciones]);
    } else {
        $nuevo_estado = 'entregado';
    }
    
    $sql_update = "UPDATE paquetes SET estado = ?, fecha_entrega = NOW(), 
                   intentos_entrega = intentos_entrega + 1 
                   WHERE id = ?";
    $stmt_update = $db->prepare($sql_update);
    $stmt_update->execute([$nuevo_estado, $paquete_id]);
    
    // Registrar ingreso si la entrega fue exitosa
    if ($tipo_entrega === 'exitosa') {
        $sql_ingreso = "INSERT INTO ingresos (tipo, concepto, monto, paquete_id, registrado_por) 
                        VALUES ('envio', ?, ?, ?, ?)";
        $stmt_ingreso = $db->prepare($sql_ingreso);
        $stmt_ingreso->execute([
            'Entrega de paquete ' . $paquete['codigo_seguimiento'],
            $paquete['costo_envio'] ?? TARIFA_POR_PAQUETE,
            $paquete_id,
            $repartidor_id
        ]);
    }
    
    // Actualizar ruta si existe
    $sql_ruta = "UPDATE ruta_paquetes SET estado = ? 
                 WHERE paquete_id = ? AND estado = 'pendiente'";
    $stmt_ruta = $db->prepare($sql_ruta);
    $stmt_ruta->execute([
        $tipo_entrega === 'exitosa' ? 'entregado' : 'fallido',
        $paquete_id
    ]);
    
    // Registrar actividad
    logActivity('Registro de entrega', 'entregas', $paquete_id, "Tipo: $tipo_entrega");
    
    // Commit
    $db->commit();
    
    setFlashMessage('success', 'Entrega registrada exitosamente');
    redirect(APP_URL . 'repartidor/dashboard.php');
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Error al registrar entrega: " . $e->getMessage());
    setFlashMessage('danger', 'Error al registrar la entrega. Por favor, intente nuevamente.');
    redirect(APP_URL . 'repartidor/entregar.php');
}
