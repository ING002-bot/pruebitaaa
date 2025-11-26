-- Agregar columna distrito a la tabla paquetes
ALTER TABLE paquetes ADD COLUMN distrito VARCHAR(100) AFTER provincia;
