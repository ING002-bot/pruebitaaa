# INSTRUCCIONES PARA ACTUALIZAR LA BASE DE DATOS

## Ejecuta estos comandos en MySQL para agregar las zonas y ubicaciones a las rutas:

1. Abre MySQL desde la línea de comandos:
```bash
cd c:\xampp\mysql\bin
mysql -u root -p
```

2. Selecciona la base de datos:
```sql
USE hermes_express;
```

3. Ejecuta las siguientes consultas:

```sql
-- Agregar campo zona
ALTER TABLE rutas 
ADD COLUMN zona VARCHAR(50) AFTER nombre;

-- Agregar campo ubicaciones
ALTER TABLE rutas 
ADD COLUMN ubicaciones TEXT AFTER zona;

-- Verificar la estructura
DESCRIBE rutas;
```

4. Verifica que se agregaron correctamente:
```sql
SELECT * FROM rutas LIMIT 1;
```

## O ejecuta directamente el archivo SQL:

```bash
cd c:\xampp\htdocs\NUEVOOO\database
..\..\..\mysql\bin\mysql -u root -p hermes_express < update_rutas.sql
```

---

## Zonas Configuradas:

### URBANO
- Chiclayo
- Leonardo Ortiz
- La Victoria
- Santa Victoria

### PUEBLOS
- Lambayeque
- Mochumi
- Tucume
- Illimo
- Nueva Arica
- Jayanca
- Pucara
- Morrope
- Motupe
- Olmos
- Salas

### PLAYAS
- San Jose
- Santa Rosa
- Pimentel
- Reque
- Monsefu
- Eten
- Puerto Eten

### COOPERATIVAS
- Pomalca
- Tuman
- Patapo
- Pucala
- Saltur
- Chongoyape

### EXCOOPERATIVAS
- Ucupe
- Mocupe
- Zaña
- Cayalti
- Oyotun
- Lagunas

### FERREÑAFE
- Ferreñafe
- Picsi
- Pitipo
- Motupillo
- Pueblo Nuevo
