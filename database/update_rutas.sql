-- Agregar campos para zonas y ubicaciones en la tabla rutas
ALTER TABLE rutas 
ADD COLUMN zona VARCHAR(50) AFTER nombre,
ADD COLUMN ubicaciones TEXT AFTER zona;

-- Actualizar descripción de la columna
ALTER TABLE rutas 
MODIFY COLUMN descripcion TEXT COMMENT 'Detalles adicionales de la ruta';
