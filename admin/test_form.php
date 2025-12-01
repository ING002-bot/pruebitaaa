<?php
require_once '../config/config.php';
requireRole('admin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h3>Prueba de Formulario</h3>
        
        <!-- Formulario simple para probar -->
        <form action="pago_procesar.php" method="POST" id="testForm">
            <div class="mb-3">
                <label>Repartidor ID:</label>
                <input type="number" name="repartidor_id" value="1" required>
            </div>
            <div class="mb-3">
                <label>Período:</label>
                <input type="text" name="periodo" value="<?php echo date('F Y'); ?>" required>
            </div>
            <div class="mb-3">
                <label>Método de Pago:</label>
                <select name="metodo_pago" required>
                    <option value="efectivo">Efectivo</option>
                </select>
            </div>
            <div class="mb-3">
                <label>Total Paquetes:</label>
                <input type="number" name="total_paquetes" value="1" required>
            </div>
            <div class="mb-3">
                <label>Monto por Paquete:</label>
                <input type="number" step="0.01" name="monto_por_paquete" value="2.50" required>
            </div>
            <div class="mb-3">
                <label>Total a Pagar:</label>
                <input type="number" step="0.01" name="total_pagar" value="2.50" required>
            </div>
            
            <input type="hidden" name="bonificaciones" value="0">
            <input type="hidden" name="deducciones" value="0">
            <input type="hidden" name="notas" value="Prueba">
            
            <button type="submit" class="btn btn-primary">Enviar Prueba</button>
        </form>
        
        <script>
            document.getElementById('testForm').addEventListener('submit', function(e) {
                console.log('Formulario de prueba enviado');
                if (!confirm('¿Enviar formulario de prueba?')) {
                    e.preventDefault();
                }
            });
        </script>
    </div>
</body>
</html>