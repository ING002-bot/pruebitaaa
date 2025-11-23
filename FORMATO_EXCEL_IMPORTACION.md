# Formato de Archivo Excel para Importación

## 📄 Estructura Requerida

El archivo Excel debe tener las siguientes columnas en el orden especificado:

| Columna | Nombre | Se Usa | Descripción | Ejemplo |
|---------|--------|--------|-------------|---------|
| **A** | Código | ✅ SÍ | Código único del paquete | SVBFE00007 |
| **B** | Cliente | ❌ NO | Cliente emisor | Yucan-fulfill |
| **C** | Descripción | ❌ NO | Descripción del producto | Mobile power |
| **D** | Departamento | ✅ SÍ | Departamento de entrega | LA PAZ |
| **E** | Provincia | ✅ SÍ | Provincia de entrega | MURILLO |
| **F** | Distrito | ✅ SÍ | Distrito/Zona de entrega | TUMAN |
| **G** | Estado | ❌ NO | Estado del paquete | En almacén |
| **H** | Fecha Creación | ❌ NO | Fecha de creación | 25/10/2025 |
| **I** | Fecha Asignación | ❌ NO | Fecha de asignación | - |
| **J** | Consignado | ✅ SÍ | Nombre del destinatario | GRODO JHON |
| **K** | Dirección Consignado | ✅ SÍ | Dirección completa | Av. Yungas #123 |
| **L** | Conductor | ❌ NO | Conductor asignado | SIN DRIVER |
| **M** | Peso | ✅ SÍ | Peso del paquete en kg | 1.260 |
| **N** | Teléfono | ✅ SÍ | Teléfono del destinatario | 917584939 |
| **O+** | Otros | ❌ NO | Columnas adicionales | - |

## ✅ Datos que se Importan

El sistema **solo importa** estos datos:

1. **Código** (Columna A) → `codigo_seguimiento`
2. **Departamento** (Columna D) → Parte de `ciudad`
3. **Provincia** (Columna E) → `provincia`
4. **Distrito** (Columna F) → Parte de `ciudad`
5. **Consignado** (Columna J) → `destinatario_nombre`
6. **Dirección Consignado** (Columna K) → `direccion_completa`
7. **Peso** (Columna M) → `peso`
8. **Teléfono** (Columna N) → `destinatario_telefono`

## 📋 Ejemplo de Datos Válidos

```
Código: SVBFE00007
Departamento: LA PAZ
Provincia: MURILLO
Distrito: CHICLAYO
Consignado: María López García
Dirección: Av. 6 de Agosto #1234, Edif. Central
Peso: 1.260
Teléfono: 70123456
```

**Resultado en el sistema:**
- Código de seguimiento: `SVBFE00007`
- Destinatario: `María López García`
- Teléfono: `70123456`
- Dirección: `Av. 6 de Agosto #1234, Edif. Central`
- Ciudad: `LA PAZ - MURILLO - CHICLAYO`
- Provincia: `MURILLO`
- Peso: `1.26 kg`

## ❌ Errores Comunes

### 1. Código de Seguimiento Duplicado
```
❌ SVBFE00007 (ya existe en la base de datos)
✅ SVBFE00999 (código único y nuevo)
```

### 2. Teléfono Vacío o Inválido
```
❌ (vacío)
❌ abc123 (contiene letras)
✅ 70123456
✅ 917584939
```

### 3. Nombre del Consignado Vacío
```
❌ (vacío)
✅ María López García
✅ GRODO JHON
```

### 4. Dirección Vacía
```
❌ (vacío)
✅ Av. 6 de Agosto #1234
✅ Calle Potosí #567, Edif. Central
```

### 5. Formato de Archivo Incorrecto
```
❌ archivo.csv (debe ser .xlsx o .xls)
❌ archivo.txt (debe ser Excel)
✅ paquetes_savar.xlsx
✅ importacion_20250115.xls
```

## 📊 Ejemplo Completo de Archivo

El archivo Excel debe tener esta estructura (las columnas que no se usan pueden tener cualquier dato):

| A | B | C | D | E | F | G | H | I | J | K | L | M | N |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| SVBFE00001 | Yucan-fulfill | Mobile power | LA PAZ | MURILLO | TUMAN | En almacén | 27/10/2025 | - | GRODO JHON | Av. Yungas #123 | SIN DRIVER | 1.260 | 917584939 |
| SVBFE00002 | Yucan-fulfill | Wireless charger | LA PAZ | MURILLO | CAYATLI | En almacén | 27/10/2025 | - | Jackeline Yul | Calle Santa María | SIN DRIVER | 0.160 | 921144804 |
| SVBFE00003 | Yucan-fulfill | Photo paper | LA PAZ | LAMBAYEQUE | POMALCA | En almacén | 27/10/2025 | - | María Magdalena | Calle Chiclayo | SIN DRIVER | 0.620 | 980194970 |

**Nota:** Solo las columnas A, D, E, F, J, K, M, N son obligatorias. Las demás pueden estar vacías o con cualquier valor.

## 🎯 Recomendaciones

### Preparación del Archivo

1. **El archivo puede tener encabezados en la primera fila** (serán ignorados automáticamente)
2. **Solo importa las columnas necesarias** - el resto puede tener cualquier dato
3. **No uses fórmulas** - solo valores de texto planos
4. **Guarda como .xlsx** - formato moderno de Excel
5. **Prueba con pocos registros primero** - importa 5-10 paquetes para verificar

### Columnas Obligatorias

Estas columnas **deben** tener datos:

- **Columna A:** Código (único)
- **Columna J:** Consignado (nombre del destinatario)
- **Columna K:** Dirección Consignado

### Columnas Opcionales

Estas columnas pueden estar vacías:

- **Columna D:** Departamento
- **Columna E:** Provincia  
- **Columna F:** Distrito
- **Columna M:** Peso (si está vacío se asigna 0)
- **Columna N:** Teléfono

### Códigos de Seguimiento

- **Formato del cliente:** SVBFE + número
  - SVBFE00001
  - SVBFE00002
  - SVBFE99999

- **Otros formatos aceptados:**
  - HE-2024-00001
  - PKG-JAN-2024-001
  - LP-240115-001
  - Cualquier código único

### Teléfonos

- **Formatos aceptados:**
  - 70123456 (8 dígitos)
  - +591 70123456 (con código de país)
  - 591 70123456 (sin +)
  - 77889900, 60123456, 71234567, etc.

- **Operadoras comunes en Bolivia:**
  - Entel: 6, 7
  - Tigo: 7
  - Viva: 6

## 🔍 Proceso de Validación

El sistema verificará automáticamente:

1. ✅ Que el código de seguimiento (columna A) no esté vacío
2. ✅ Que el código no exista previamente en la base de datos
3. ✅ Que el nombre del consignado (columna J) no esté vacío
4. ✅ Que la dirección (columna K) no esté vacía
5. ✅ Que el archivo sea Excel válido (.xlsx o .xls)

**Nota:** El teléfono, departamento, provincia, distrito y peso son opcionales.

## 📥 Pasos para Importar

1. **Obtener archivo Excel** desde SAVAR o sistema externo
2. **Verificar columnas:** A, D, E, F, J, K, M, N con datos
3. **Ir a:** Admin → Sistema → Importar Excel
4. **Click en** "Subir Nuevo Archivo"
5. **Seleccionar archivo** desde tu computadora
6. **Click en** "Procesar Importación"
7. **Revisar resultados:**
   - ✅ Registros exitosos (aparecerán en la tabla de paquetes)
   - ❌ Errores (se mostrarán en pantalla con la razón del fallo)

## 💾 Archivo de Ejemplo

Puedes usar el archivo que te envía SAVAR directamente. El sistema está configurado para leer:

- **Columna A:** Código
- **Columna D:** Departamento
- **Columna E:** Provincia
- **Columna F:** Distrito
- **Columna J:** Consignado
- **Columna K:** Dirección Consignado
- **Columna M:** Peso
- **Columna N:** Teléfono

**Las demás columnas se ignoran automáticamente.**

## 🆘 Solución de Problemas

### "Error al procesar el archivo"
- Verifica que sea un archivo Excel válido (.xlsx o .xls)
- Abre el archivo en Excel y guárdalo nuevamente
- Asegúrate de que tenga al menos 1 fila de datos

### "Código de seguimiento duplicado"
- Cambia el código de seguimiento por uno único
- Verifica en la tabla de paquetes si ya existe
- Elimina filas duplicadas en el Excel

### "Código de seguimiento vacío"
- Verifica que la columna A tenga datos
- No debe haber filas con columna A vacía

### "Nombre del consignado vacío"
- Verifica que la columna J tenga datos
- Debe tener el nombre del destinatario

### "Dirección vacía"
- Verifica que la columna K tenga datos
- Debe tener la dirección completa de entrega

### "Importación procesada pero 0 registros exitosos"
- Revisa el historial de importaciones para ver los errores específicos
- Verifica que las columnas estén en el orden correcto
- Asegúrate de que la primera fila sea de encabezados o datos válidos

## 📞 Notificaciones WhatsApp

Después de la importación exitosa:

1. Los paquetes quedarán en estado **"pendiente"**
2. Al asignar un repartidor, se enviará automáticamente:
   - ✅ Notificación WhatsApp al cliente
   - ✅ Alerta al repartidor en el sistema
   - ✅ Se establecerá fecha límite de entrega (2 días)
3. A las 24 horas de la fecha límite:
   - ⏰ Alerta automática al repartidor vía WhatsApp
   - ⏰ Notificación en el panel del repartidor

**Nota:** Para que las notificaciones WhatsApp funcionen, el administrador debe configurar las credenciales del API en `config/whatsapp_helper.php`.
