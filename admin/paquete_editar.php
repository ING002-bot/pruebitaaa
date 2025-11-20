<?php
require_once '../config/config.php';
requireRole(['admin']);

$id = $_GET['id'] ?? 0;
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT * FROM paquetes WHERE id = ?");
$stmt->execute([$id]);
$paquete = $stmt->fetch();

if (!$paquete) {
    echo '<div class="alert alert-danger">Paquete no encontrado</div>';
    exit;
}

$repartidores = $db->query("SELECT id, nombre, apellido FROM usuarios WHERE rol = 'repartidor' AND estado = 'activo' ORDER BY nombre")->fetchAll();
?>

<form action="paquete_actualizar.php" method="POST">
    <input type="hidden" name="id" value="<?php echo $paquete['id']; ?>">
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Código de Seguimiento</label>
            <input type="text" class="form-control" name="codigo_seguimiento" value="<?php echo htmlspecialchars($paquete['codigo_seguimiento']); ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Código SAVAR</label>
            <input type="text" class="form-control" name="codigo_savar" value="<?php echo htmlspecialchars($paquete['codigo_savar']); ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Nombre Destinatario</label>
            <input type="text" class="form-control" name="destinatario_nombre" value="<?php echo htmlspecialchars($paquete['destinatario_nombre']); ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Teléfono Destinatario</label>
            <input type="text" class="form-control" name="destinatario_telefono" value="<?php echo htmlspecialchars($paquete['destinatario_telefono']); ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Email Destinatario</label>
            <input type="email" class="form-control" name="destinatario_email" value="<?php echo htmlspecialchars($paquete['destinatario_email']); ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Estado</label>
            <select class="form-select" name="estado">
                <option value="pendiente" <?php echo $paquete['estado'] === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                <option value="en_ruta" <?php echo $paquete['estado'] === 'en_ruta' ? 'selected' : ''; ?>>En Ruta</option>
                <option value="entregado" <?php echo $paquete['estado'] === 'entregado' ? 'selected' : ''; ?>>Entregado</option>
                <option value="rezagado" <?php echo $paquete['estado'] === 'rezagado' ? 'selected' : ''; ?>>Rezagado</option>
                <option value="devuelto" <?php echo $paquete['estado'] === 'devuelto' ? 'selected' : ''; ?>>Devuelto</option>
                <option value="cancelado" <?php echo $paquete['estado'] === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
            </select>
        </div>
        <div class="col-12 mb-3">
            <label class="form-label">Dirección Completa</label>
            <textarea class="form-control" name="direccion_completa" rows="2" required><?php echo htmlspecialchars($paquete['direccion_completa']); ?></textarea>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Ciudad</label>
            <input type="text" class="form-control" name="ciudad" value="<?php echo htmlspecialchars($paquete['ciudad']); ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Provincia</label>
            <input type="text" class="form-control" name="provincia" value="<?php echo htmlspecialchars($paquete['provincia']); ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Peso (kg)</label>
            <input type="number" step="0.01" class="form-control" name="peso" value="<?php echo $paquete['peso']; ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Valor Declarado</label>
            <input type="number" step="0.01" class="form-control" name="valor_declarado" value="<?php echo $paquete['valor_declarado']; ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Costo Envío</label>
            <input type="number" step="0.01" class="form-control" name="costo_envio" value="<?php echo $paquete['costo_envio']; ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Prioridad</label>
            <select class="form-select" name="prioridad">
                <option value="normal" <?php echo $paquete['prioridad'] === 'normal' ? 'selected' : ''; ?>>Normal</option>
                <option value="urgente" <?php echo $paquete['prioridad'] === 'urgente' ? 'selected' : ''; ?>>Urgente</option>
                <option value="express" <?php echo $paquete['prioridad'] === 'express' ? 'selected' : ''; ?>>Express</option>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Asignar Repartidor</label>
            <select class="form-select" name="repartidor_id">
                <option value="">Sin asignar</option>
                <?php foreach($repartidores as $rep): ?>
                    <option value="<?php echo $rep['id']; ?>" <?php echo $paquete['repartidor_id'] == $rep['id'] ? 'selected' : ''; ?>>
                        <?php echo $rep['nombre'] . ' ' . $rep['apellido']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 mb-3">
            <label class="form-label">Descripción del Contenido</label>
            <textarea class="form-control" name="descripcion" rows="2"><?php echo htmlspecialchars($paquete['descripcion']); ?></textarea>
        </div>
        <div class="col-12 mb-3">
            <label class="form-label">Notas</label>
            <textarea class="form-control" name="notas" rows="2"><?php echo htmlspecialchars($paquete['notas']); ?></textarea>
        </div>
    </div>
    
    <div class="text-end">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Actualizar Paquete</button>
    </div>
</form>
