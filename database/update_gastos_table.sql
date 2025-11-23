-- Actualización de la tabla gastos para agregar campos faltantes
-- Este script agrega los campos necesarios para el módulo de gastos

USE hermes_express;

-- Agregar columna descripcion (renombrando concepto a descripcion)
ALTER TABLE gastos 
ADD COLUMN IF NOT EXISTS descripcion VARCHAR(200) AFTER categoria;

-- Copiar datos de concepto a descripcion si existen
UPDATE gastos SET descripcion = concepto WHERE descripcion IS NULL OR descripcion = '';

-- Agregar columna numero_comprobante
ALTER TABLE gastos 
ADD COLUMN IF NOT EXISTS numero_comprobante VARCHAR(100) AFTER monto;

-- Agregar columna comprobante_archivo
ALTER TABLE gastos 
ADD COLUMN IF NOT EXISTS comprobante_archivo VARCHAR(255) AFTER numero_comprobante;

-- Hacer concepto opcional (puede ser NULL)
ALTER TABLE gastos 
MODIFY COLUMN concepto VARCHAR(200) NULL;

-- Agregar índice para número de comprobante
ALTER TABLE gastos 
ADD INDEX IF NOT EXISTS idx_numero_comprobante (numero_comprobante);

SELECT 'Tabla gastos actualizada exitosamente' as mensaje;
