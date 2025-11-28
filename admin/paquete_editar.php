<?php
require_once '../config/config.php';
requireRole(['admin']);

$id = $_GET['id'] ?? 0;
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT * FROM paquetes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$paquete = $stmt->get_result()->fetch_assoc();

if (!$paquete) {
    echo '<div class="alert alert-danger">Paquete no encontrado</div>';
    exit;
}

$repartidores = Database::getInstance()->fetchAll($db->query("SELECT id, nombre, apellido FROM usuarios WHERE rol = 'repartidor' AND estado = 'activo' ORDER BY nombre"));
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
            <?php
            $estados_labels = [
                'pendiente' => 'Pendiente',
                'en_ruta' => 'En Ruta',
                'entregado' => 'Entregado',
                'rezagado' => 'Rezagado',
                'devuelto' => 'Devuelto',
                'cancelado' => 'Cancelado'
            ];
            $estado_badges = [
                'pendiente' => 'secondary',
                'en_ruta' => 'primary',
                'entregado' => 'success',
                'rezagado' => 'warning',
                'devuelto' => 'danger',
                'cancelado' => 'dark'
            ];
            ?>
            <div class="form-control bg-light" style="display: flex; align-items: center; height: 38px;">
                <span class="badge bg-<?php echo $estado_badges[$paquete['estado']]; ?>">
                    <?php echo $estados_labels[$paquete['estado']]; ?>
                </span>
                <small class="text-muted ms-2">(El estado cambia automáticamente según las entregas)</small>
            </div>
            <input type="hidden" name="estado" value="<?php echo $paquete['estado']; ?>">
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
            <?php 
            $repartidor_actual = '';
            foreach($repartidores as $rep) {
                if ($paquete['repartidor_id'] == $rep['id']) {
                    $repartidor_actual = $rep['nombre'] . ' ' . $rep['apellido'];
                    break;
                }
            }
            ?>
            <div class="autocomplete-wrapper" style="position: relative;">
                <input type="text" 
                       class="form-control autocomplete-repartidor" 
                       id="repartidor_editar_search" 
                       placeholder="Escriba el nombre del repartidor..."
                       value="<?php echo htmlspecialchars($repartidor_actual); ?>"
                       autocomplete="off">
                <input type="hidden" name="repartidor_id" id="repartidor_editar_id" value="<?php echo $paquete['repartidor_id']; ?>">
                <div class="autocomplete-suggestions" id="suggestions_editar" style="display: none;"></div>
            </div>
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

<script>
// Datos de repartidores
const repartidoresEditar = [
    <?php foreach($repartidores as $rep): ?>
    {
        id: '<?php echo $rep["id"]; ?>',
        nombre: '<?php echo addslashes($rep["nombre"] . " " . $rep["apellido"]); ?>'
    },
    <?php endforeach; ?>
];

$(document).ready(function() {
    const searchInput = document.getElementById('repartidor_editar_search');
    const hiddenInput = document.getElementById('repartidor_editar_id');
    const suggestionsDiv = document.getElementById('suggestions_editar');
    
    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
        const value = this.value.toLowerCase().trim();
        
        if (value.length === 0) {
            suggestionsDiv.style.display = 'none';
            suggestionsDiv.innerHTML = '';
            hiddenInput.value = '';
            return;
        }

        const filtered = repartidoresEditar.filter(rep => 
            rep.nombre.toLowerCase().includes(value)
        );

        if (filtered.length === 0) {
            suggestionsDiv.style.display = 'none';
            suggestionsDiv.innerHTML = '';
            return;
        }

        suggestionsDiv.innerHTML = filtered.map(rep => 
            `<div class="autocomplete-item" data-id="${rep.id}" data-nombre="${rep.nombre}">
                ${rep.nombre}
            </div>`
        ).join('');
        
        suggestionsDiv.style.display = 'block';

        suggestionsDiv.querySelectorAll('.autocomplete-item').forEach(item => {
            item.addEventListener('click', function() {
                searchInput.value = this.getAttribute('data-nombre');
                hiddenInput.value = this.getAttribute('data-id');
                suggestionsDiv.style.display = 'none';
                suggestionsDiv.innerHTML = '';
            });
        });
    });

    document.addEventListener('click', function(e) {
        if (e.target !== searchInput && !suggestionsDiv.contains(e.target)) {
            suggestionsDiv.style.display = 'none';
        }
    });

    searchInput.addEventListener('blur', function() {
        setTimeout(() => {
            const exactMatch = repartidoresEditar.find(rep => 
                rep.nombre.toLowerCase() === this.value.toLowerCase().trim()
            );
            
            if (exactMatch) {
                hiddenInput.value = exactMatch.id;
            } else if (this.value.trim() !== '' && hiddenInput.value === '') {
                this.value = '';
            }
        }, 200);
    });
});
</script>
