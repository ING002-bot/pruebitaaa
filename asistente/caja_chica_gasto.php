<?php
require_once '../config/config.php';
requireRole('asistente');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: caja_chica.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$asistente_id = $_SESSION['usuario_id'];

try {
    $db->beginTransaction();

    // Validar datos
    $asignacion_id = filter_input(INPUT_POST, 'asignacion_id', FILTER_VALIDATE_INT);
    $monto = filter_input(INPUT_POST, 'monto', FILTER_VALIDATE_FLOAT);
    $concepto = trim($_POST['concepto']);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha_operacion = $_POST['fecha_operacion'];

    if (!$asignacion_id || !$monto || empty($concepto) || !$fecha_operacion) {
        throw new Exception('Datos incompletos');
    }

    // Verificar que la asignación existe y pertenece al asistente
    $stmt = $db->prepare("
        SELECT monto, concepto,
               (monto - (
                   SELECT COALESCE(SUM(monto), 0) 
                   FROM caja_chica 
                   WHERE asignacion_padre_id = ? AND tipo = 'gasto'
               )) as disponible
        FROM caja_chica
        WHERE id = ? AND asignado_a = ? AND tipo = 'asignacion'
    ");
    $stmt->execute([$asignacion_id, $asignacion_id, $asistente_id]);
    $asignacion = $stmt->fetch();

    if (!$asignacion) {
        throw new Exception('Asignación no encontrada o no autorizada');
    }

    if ($monto > $asignacion['disponible']) {
        throw new Exception('El monto excede el saldo disponible');
    }

    // Manejar foto del comprobante
    $foto_nombre = null;
    if (isset($_FILES['foto_comprobante']) && $_FILES['foto_comprobante']['error'] === UPLOAD_ERR_OK) {
        $archivo = $_FILES['foto_comprobante'];
        $permitidos = ['image/jpeg', 'image/jpg', 'image/png'];
        
        if (!in_array($archivo['type'], $permitidos)) {
            throw new Exception('Formato de imagen no permitido. Use JPG o PNG');
        }
        
        if ($archivo['size'] > 5 * 1024 * 1024) { // 5MB
            throw new Exception('La imagen no debe superar 5MB');
        }

        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $foto_nombre = 'comprobante_' . time() . '_' . uniqid() . '.' . $extension;
        $ruta_destino = '../uploads/caja_chica/' . $foto_nombre;

        if (!is_dir('../uploads/caja_chica')) {
            mkdir('../uploads/caja_chica', 0755, true);
        }

        if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
            throw new Exception('Error al guardar el comprobante');
        }
    }

    // Registrar el gasto
    $stmt = $db->prepare("
        INSERT INTO caja_chica 
        (asignado_a, asignado_por, asignacion_padre_id, tipo, monto, concepto, descripcion, foto_comprobante, fecha_operacion, registrado_por)
        VALUES (?, ?, ?, 'gasto', ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $asistente_id,
        null, // No hay asignado_por en gastos
        $asignacion_id,
        $monto,
        $concepto,
        $descripcion,
        $foto_nombre,
        $fecha_operacion,
        $asistente_id
    ]);

    // Notificar al admin que asignó el dinero
    $stmt = $db->prepare("SELECT asignado_por FROM caja_chica WHERE id = ?");
    $stmt->execute([$asignacion_id]);
    $admin_id = $stmt->fetchColumn();

    if ($admin_id) {
        require_once '../config/notificaciones_helper.php';
        crearNotificacion(
            $admin_id,
            'caja_chica_gasto',
            null,
            "Gasto registrado en Caja Chica",
            "El asistente registró un gasto de S/ " . number_format($monto, 2) . " para: " . $concepto
        );
    }

    // Log de actividad
    logActivity($asistente_id, 'gasto_caja_chica', 'Se registró gasto: ' . $concepto . ' - S/ ' . number_format($monto, 2));

    $db->commit();

    $_SESSION['flash_message'] = [
        'type' => 'success',
        'message' => 'Gasto registrado exitosamente'
    ];

} catch (Exception $e) {
    $db->rollBack();
    
    // Eliminar foto si se subió pero hubo error
    if (isset($foto_nombre) && file_exists('../uploads/caja_chica/' . $foto_nombre)) {
        unlink('../uploads/caja_chica/' . $foto_nombre);
    }

    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => 'Error al registrar gasto: ' . $e->getMessage()
    ];
}

header('Location: caja_chica.php');
exit;
