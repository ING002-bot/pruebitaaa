-- Actualización de la tabla pagos para hacerla compatible con la interfaz
-- Este script modifica la tabla pagos para agregar campos simplificados

USE hermes_express;

-- Verificar si ya existe la columna 'concepto'
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'hermes_express'
    AND TABLE_NAME = 'pagos'
    AND COLUMN_NAME = 'concepto'
);

-- Agregar nuevas columnas si no existen
ALTER TABLE pagos 
ADD COLUMN IF NOT EXISTS concepto VARCHAR(200) AFTER repartidor_id,
ADD COLUMN IF NOT EXISTS periodo VARCHAR(100) AFTER concepto,
ADD COLUMN IF NOT EXISTS monto DECIMAL(10, 2) AFTER periodo,
ADD COLUMN IF NOT EXISTS registrado_por INT AFTER metodo_pago;

-- Hacer campos opcionales que antes eran obligatorios
ALTER TABLE pagos 
MODIFY COLUMN periodo_inicio DATE NULL,
MODIFY COLUMN periodo_fin DATE NULL,
MODIFY COLUMN total_pagar DECIMAL(10, 2) NULL;

-- Agregar índice a registrado_por si no existe
ALTER TABLE pagos 
ADD INDEX IF NOT EXISTS idx_registrado_por (registrado_por);

-- Agregar clave foránea a registrado_por si no existe
ALTER TABLE pagos
ADD CONSTRAINT fk_pagos_registrado_por
FOREIGN KEY IF NOT EXISTS (registrado_por) REFERENCES usuarios(id) ON DELETE SET NULL;

-- Actualizar registros existentes que tengan total_pagar pero no monto
UPDATE pagos 
SET monto = total_pagar 
WHERE monto IS NULL AND total_pagar IS NOT NULL;

-- Actualizar registros existentes para consolidar el concepto desde notas
UPDATE pagos 
SET concepto = SUBSTRING_INDEX(SUBSTRING_INDEX(notas, 'Concepto: ', -1), ' |', 1)
WHERE concepto IS NULL AND notas LIKE '%Concepto:%';

-- Actualizar el periodo desde notas
UPDATE pagos 
SET periodo = SUBSTRING_INDEX(SUBSTRING_INDEX(notas, 'Periodo: ', -1), '\n', 1)
WHERE periodo IS NULL AND notas LIKE '%Periodo:%';

-- Mensaje de confirmación
SELECT 'Tabla pagos actualizada exitosamente' as mensaje;
