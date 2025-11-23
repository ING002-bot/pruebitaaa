# Formato de Archivo Excel para Importación

## 📄 Estructura Requerida

El archivo Excel debe tener **exactamente 6 columnas** en el siguiente orden:

| Columna | Nombre | Descripción | Ejemplo |
|---------|--------|-------------|---------|
| **A** | Código de Seguimiento | Código único del paquete | HE-2024-00123 |
| **B** | Nombre del Destinatario | Nombre completo | Juan Pérez García |
| **C** | Teléfono del Destinatario | Número con código de país | +591 70123456 o 70123456 |
| **D** | Dirección de Entrega | Dirección completa | Av. Arce #2350, Edif. Torre Azul, Piso 5 |
| **E** | Zona | Zona de entrega (debe existir en tarifas) | Zona Sur, Centro, Miraflores, etc. |
| **F** | Descripción del Contenido | Qué contiene el paquete | Documentos, Ropa, Electrónicos, etc. |

## ✅ Ejemplo de Datos Válidos

```
HE-2024-00001 | María López | 77889900 | Av. 6 de Agosto #1234 | Centro | Documentos legales
HE-2024-00002 | Pedro Gómez | +591 71234567 | Calle Potosí #567 | Miraflores | Ropa deportiva
HE-2024-00003 | Ana Torres | 60987654 | Zona Villa Victoria, Calle 8 #45 | Villa Victoria | Electrónicos
HE-2024-00004 | Carlos Ruiz | +591 78456123 | Av. Ballivián #890, Torre B | Calacoto | Medicamentos
```

## ❌ Errores Comunes

### 1. Código de Seguimiento Duplicado
```
❌ HE-2024-00001 (ya existe en la base de datos)
✅ HE-2024-00999 (código único y nuevo)
```

### 2. Teléfono Inválido
```
❌ 123 (muy corto)
❌ abc123 (contiene letras)
✅ 70123456
✅ +591 71234567
```

### 3. Zona No Existe
```
❌ Zona Inexistente (no está en la tabla tarifas)
✅ Centro (debe existir previamente en tarifas)
```

**Importante:** Antes de importar, verifica que todas las zonas mencionadas en el Excel ya existan en el sistema (Gestión → Tarifas por Zona).

### 4. Campos Vacíos
```
❌ HE-2024-00001 |  | 70123456 | Dirección | Zona | Descripción
                    ↑ nombre vacío
✅ HE-2024-00001 | Juan Pérez | 70123456 | Dirección | Zona | Descripción
```

### 5. Formato de Archivo Incorrecto
```
❌ archivo.csv (debe ser .xlsx o .xls)
❌ archivo.txt (debe ser Excel)
✅ paquetes_enero_2024.xlsx
✅ importacion_savar.xls
```

## 📊 Ejemplo Completo de Archivo

Crea un archivo Excel con esta estructura:

| A | B | C | D | E | F |
|---|---|---|---|---|---|
| HE-2024-00001 | María López Vega | 77889900 | Av. 6 de Agosto #1234, Edif. Central | Centro | Documentos legales |
| HE-2024-00002 | Pedro Gómez Ríos | +591 71234567 | Calle Potosí #567, Casa Azul | Miraflores | Ropa deportiva |
| HE-2024-00003 | Ana Torres Cruz | 60987654 | Zona Villa Victoria, Calle 8 #45 | Villa Victoria | Electrónicos varios |
| HE-2024-00004 | Carlos Ruiz Mendoza | +591 78456123 | Av. Ballivián #890, Torre B Piso 10 | Calacoto | Medicamentos |
| HE-2024-00005 | Sofía Flores Luna | 69871234 | Calle Comercio #234, Local 5 | Sopocachi | Libros y revistas |

## 🎯 Recomendaciones

### Preparación del Archivo

1. **Usa la primera fila para encabezados** (opcional, será ignorada automáticamente si no es un código válido)
2. **Evita celdas fusionadas** - cada celda debe tener un solo valor
3. **No uses fórmulas** - solo valores de texto planos
4. **Guarda como .xlsx** - formato moderno de Excel
5. **Prueba con pocos registros primero** - importa 5-10 paquetes para verificar

### Zonas Válidas

Antes de importar, ve a **Gestión → Tarifas por Zona** y anota las zonas disponibles. Algunos ejemplos comunes:

- Centro
- Zona Sur
- Miraflores
- Calacoto
- Sopocachi
- Villa Victoria
- San Miguel
- Obrajes
- Achumani

### Códigos de Seguimiento

- **Prefijo recomendado:** HE-AAAA-NNNNN
  - HE = Hermes Express
  - AAAA = Año (2024, 2025, etc.)
  - NNNNN = Número secuencial (00001, 00002, etc.)

- **Ejemplos válidos:**
  - HE-2024-00001
  - SAVAR-2024-123
  - PKG-JAN-2024-001
  - LP-240115-001

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

1. ✅ Que el código de seguimiento no exista en la BD
2. ✅ Que todos los campos estén completos
3. ✅ Que el teléfono tenga al menos 7 caracteres numéricos
4. ✅ Que la zona exista en la tabla de tarifas
5. ✅ Que el archivo sea Excel válido (.xlsx o .xls)

## 📥 Pasos para Importar

1. **Preparar archivo Excel** según la estructura descrita
2. **Ir a:** Admin → Sistema → Importar Excel
3. **Click en** "Subir Nuevo Archivo"
4. **Seleccionar archivo** desde tu computadora
5. **Click en** "Procesar Importación"
6. **Revisar resultados:**
   - ✅ Registros exitosos (aparecerán en la tabla de paquetes)
   - ❌ Errores (se mostrarán en pantalla con la razón del fallo)

## 💾 Archivo de Ejemplo

Puedes descargar un archivo de ejemplo desde:

**[Próximamente: plantilla_importacion.xlsx]**

O crear uno manualmente siguiendo la estructura de la tabla anterior.

## 🆘 Solución de Problemas

### "Error al procesar el archivo"
- Verifica que sea un archivo Excel válido (.xlsx o .xls)
- Abre el archivo en Excel y guárdalo nuevamente
- Asegúrate de que tenga al menos 1 fila de datos

### "Código de seguimiento duplicado"
- Cambia el código de seguimiento por uno único
- Verifica en la tabla de paquetes si ya existe

### "Zona no encontrada en tarifas"
- Ve a Gestión → Tarifas por Zona
- Agrega la zona faltante con su tarifa correspondiente
- O corrige el nombre de la zona en el Excel para que coincida exactamente

### "Teléfono inválido"
- Verifica que tenga al menos 7 dígitos
- Quita espacios, guiones o caracteres especiales innecesarios
- Formato recomendado: 70123456 o +591 70123456

### "Importación procesada pero 0 registros exitosos"
- Revisa el historial de importaciones para ver los errores específicos
- Verifica que la primera fila no sea un encabezado mal formado
- Asegúrate de que todas las columnas tengan datos

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
