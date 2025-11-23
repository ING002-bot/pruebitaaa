# Sistema de Importación Masiva con WhatsApp y Alertas de Entrega

## 📌 Descripción General

Sistema completo para importar paquetes masivamente desde archivos Excel (provenientes de SAVAR u otros proveedores), con notificaciones automáticas por WhatsApp a clientes y alertas de tiempo a repartidores.

## ✨ Características Principales

### 1. Importación Masiva desde Excel
- ✅ Importar cientos de paquetes en segundos
- ✅ Validación automática de datos
- ✅ Detección de duplicados
- ✅ Historial completo de importaciones
- ✅ Registro de errores detallado

### 2. Sistema de Tiempo Límite
- ✅ **2 días** de plazo automático al asignar paquete
- ✅ Contador regresivo visible
- ✅ Alertas a 24 horas del vencimiento
- ✅ Marcado de paquetes vencidos

### 3. Notificaciones WhatsApp
- ✅ Mensaje automático al **cliente** cuando se asigna repartidor
- ✅ Alerta automática al **repartidor** 24 horas antes del vencimiento
- ✅ Registro completo de mensajes enviados
- ✅ Soporte para múltiples APIs (Twilio, WhatsApp Business Cloud, etc.)

### 4. Sistema de Alertas Automáticas
- ✅ Verificación automática cada hora (cron job)
- ✅ Notificaciones en el panel web
- ✅ Registro en base de datos
- ✅ Log de ejecución completo

## 🗂️ Archivos Creados/Modificados

### Base de Datos
```
database/add_importacion_notificaciones.sql
```
**Tablas nuevas:**
- `importaciones_archivos` - Historial de archivos Excel importados
- `notificaciones_whatsapp` - Log de mensajes WhatsApp enviados
- `alertas_entrega` - Registro de alertas de tiempo enviadas

**Campos nuevos en `paquetes`:**
- `archivo_importacion` - ID del archivo de donde proviene
- `fecha_limite_entrega` - Fecha/hora límite (2 días)
- `alerta_enviada` - Si ya se envió alerta de 24 horas
- `notificacion_whatsapp_enviada` - Si se notificó al cliente

### Interfaz de Usuario
```
admin/importar_excel.php
```
- Modal de subida de archivos
- Tabla de historial de importaciones
- Estadísticas de éxito/error
- Botón para procesar importación

### Procesamiento Backend
```
admin/importar_excel_procesar.php
```
- Manejo de subida de archivos
- Lectura de Excel con PhpSpreadsheet
- Validación de datos
- Inserción en base de datos
- Registro de errores por fila

### Integración WhatsApp
```
config/whatsapp_helper.php
```
**Clase: `WhatsAppNotificaciones`**

**Métodos:**
- `notificarAsignacion($paquete_id)` - Envía mensaje al cliente
- `enviarAlerta24Horas($paquete_id, $repartidor_id)` - Alerta al repartidor
- `enviarMensaje($telefono, $mensaje, $tipo)` - Método genérico
- `limpiarTelefono($telefono)` - Normaliza números

**Soporte para APIs:**
- Twilio
- WhatsApp Business Cloud (Meta)
- API personalizada

### Asignación de Paquetes
```
admin/paquetes_asignar.php (modificado)
```
- Calcula `fecha_limite_entrega = NOW() + 2 días`
- Llama a `WhatsAppNotificaciones::notificarAsignacion()`
- Crea notificación en el sistema para el repartidor
- Registra en tabla `notificaciones_whatsapp`

### Sistema de Alertas Automáticas
```
cron/verificar_alertas_entrega.php
```
- Script ejecutable vía cron job o Programador de Tareas
- Busca paquetes con menos de 24 horas restantes
- Envía alerta WhatsApp al repartidor
- Marca `alerta_enviada = 1`
- Registra en `alertas_entrega`
- Genera log detallado en `alertas_log.txt`

### Navegación
```
admin/includes/sidebar.php (modificado)
```
- Nuevo enlace: **Sistema → Importar Excel**
- Ícono: `bi-file-earmark-excel`

### Documentación
```
FORMATO_EXCEL_IMPORTACION.md
INSTALAR_PHPSPREADSHEET.md
cron/CONFIGURAR_CRON.md
SISTEMA_IMPORTACION_WHATSAPP.md (este archivo)
```

## 📊 Flujo Completo del Sistema

```
1. IMPORTACIÓN
   │
   ├─ Admin sube archivo Excel → admin/importar_excel.php
   │
   ├─ Sistema procesa archivo → admin/importar_excel_procesar.php
   │  ├─ Lee columnas A-F (código, nombre, teléfono, dirección, zona, descripción)
   │  ├─ Valida cada fila
   │  └─ Inserta en tabla paquetes (estado: pendiente)
   │
   └─ Guarda registro en importaciones_archivos

2. ASIGNACIÓN
   │
   ├─ Admin asigna repartidor → admin/paquetes_asignar.php
   │
   ├─ Sistema calcula: fecha_limite = NOW() + 2 días
   │
   ├─ Envía WhatsApp al CLIENTE
   │  ├─ "Su paquete [CÓDIGO] ha sido asignado"
   │  ├─ "Repartidor: [NOMBRE] - [TELÉFONO]"
   │  └─ "Será entregado antes del [FECHA]"
   │
   ├─ Crea notificación para REPARTIDOR en sistema
   │  └─ "Nuevo paquete asignado: [CÓDIGO]"
   │
   └─ Registra en notificaciones_whatsapp

3. ALERTA AUTOMÁTICA (24 HORAS ANTES)
   │
   ├─ Cron ejecuta cada hora → cron/verificar_alertas_entrega.php
   │
   ├─ Busca paquetes:
   │  ├─ Estado = 'en_ruta'
   │  ├─ fecha_limite <= NOW() + 24 horas
   │  └─ alerta_enviada = 0
   │
   ├─ Para cada paquete encontrado:
   │  ├─ Envía WhatsApp al REPARTIDOR
   │  │  ├─ "⚠️ ALERTA: Quedan 24 horas"
   │  │  ├─ "Paquete: [CÓDIGO]"
   │  │  ├─ "Cliente: [NOMBRE] - [TELÉFONO]"
   │  │  └─ "Dirección: [DIRECCIÓN COMPLETA]"
   │  │
   │  ├─ Marca alerta_enviada = 1
   │  ├─ Registra en alertas_entrega
   │  ├─ Crea notificación en sistema
   │  └─ Log en alertas_log.txt
   │
   └─ Resumen en log: procesados / exitosos / errores

4. ENTREGA
   │
   └─ Repartidor marca como entregado → estado = 'entregado'
```

## 🔧 Instalación y Configuración

### Paso 1: Base de Datos
```sql
-- Ejecutar en phpMyAdmin o MySQL CLI
source C:\xampp\htdocs\pruebitaaa\database\add_importacion_notificaciones.sql;
```

### Paso 2: PhpSpreadsheet
```powershell
cd C:\xampp\htdocs\pruebitaaa
composer require phpoffice/phpspreadsheet
```

Ver detalles en: `INSTALAR_PHPSPREADSHEET.md`

### Paso 3: Crear Carpeta de Uploads
```powershell
# Windows
New-Item -ItemType Directory -Path "C:\xampp\htdocs\pruebitaaa\uploads\excel" -Force

# Linux
mkdir -p /var/www/html/pruebitaaa/uploads/excel
chmod 777 /var/www/html/pruebitaaa/uploads/excel
```

### Paso 4: Configurar WhatsApp API

Edita `config/whatsapp_helper.php`:

```php
// Línea 8-12
private $api_provider = 'twilio'; // o 'whatsapp_cloud' o 'custom'
private $twilio_sid = 'TU_ACCOUNT_SID_AQUI';
private $twilio_token = 'TU_AUTH_TOKEN_AQUI';
private $twilio_from = 'whatsapp:+14155238886'; // Tu número de Twilio
private $cloud_token = 'TU_WHATSAPP_CLOUD_TOKEN_AQUI';
```

**Opciones de API:**

#### Twilio (Recomendado)
1. Crear cuenta en: https://www.twilio.com/try-twilio
2. Activar WhatsApp Sandbox
3. Obtener Account SID, Auth Token y número de WhatsApp
4. Configurar en `whatsapp_helper.php`

#### WhatsApp Business Cloud (Meta)
1. Crear app en: https://developers.facebook.com/
2. Configurar WhatsApp Business API
3. Obtener token de acceso
4. Configurar en `whatsapp_helper.php`

#### Modo Simulación (Por defecto)
El sistema actualmente está en modo simulación. Los mensajes no se envían realmente pero se registran en la base de datos como `estado = 'simulado'`.

### Paso 5: Configurar Cron Job

Ver detalles completos en: `cron/CONFIGURAR_CRON.md`

**Windows - Programador de Tareas:**
- Programa: `C:\xampp\php\php.exe`
- Argumentos: `C:\xampp\htdocs\pruebitaaa\cron\verificar_alertas_entrega.php`
- Repetir cada: **1 hora**

**Linux - Crontab:**
```bash
crontab -e
# Agregar:
0 * * * * php /var/www/html/pruebitaaa/cron/verificar_alertas_entrega.php
```

## 📱 Ejemplos de Mensajes

### Mensaje al Cliente (Asignación)
```
📦 *Hermes Express - Notificación*

Su paquete ha sido asignado para entrega:

🔖 Código: HE-2024-00123
👤 Repartidor: Juan Pérez
📞 Teléfono: +591 70123456
⏰ Fecha límite: 17/01/2024 14:30

Su paquete será entregado antes de la fecha indicada.

¡Gracias por confiar en Hermes Express! 🚚
```

### Mensaje al Repartidor (Alerta 24h)
```
⚠️ *ALERTA DE TIEMPO*

Quedan *24 horas* para entregar:

📦 Código: *HE-2024-00123*
👤 Cliente: María López
📞 Teléfono: 77889900
📍 Dirección: Av. 6 de Agosto #1234, Edif. Central
⏰ Fecha límite: *17/01/2024 14:30*

Por favor, coordina la entrega lo antes posible.
```

## 📈 Base de Datos - Esquema

### Tabla: importaciones_archivos
```sql
CREATE TABLE importaciones_archivos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_archivo VARCHAR(255),
    ruta_archivo VARCHAR(500),
    fecha_importacion DATETIME,
    usuario_id INT,
    total_registros INT,
    registros_exitosos INT,
    registros_errores INT,
    estado ENUM('procesando', 'completado', 'error'),
    detalles_errores TEXT
);
```

### Tabla: notificaciones_whatsapp
```sql
CREATE TABLE notificaciones_whatsapp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paquete_id INT,
    telefono VARCHAR(20),
    mensaje TEXT,
    tipo ENUM('asignacion', 'alerta_24h', 'entrega', 'otro'),
    estado ENUM('enviado', 'error', 'pendiente', 'simulado'),
    fecha_envio DATETIME,
    respuesta_api TEXT,
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id)
);
```

### Tabla: alertas_entrega
```sql
CREATE TABLE alertas_entrega (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paquete_id INT,
    repartidor_id INT,
    fecha_envio DATETIME,
    fecha_limite DATETIME,
    horas_restantes INT,
    estado ENUM('enviada', 'error'),
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id),
    FOREIGN KEY (repartidor_id) REFERENCES usuarios(id)
);
```

## 🎯 Formato del Archivo Excel

Ver detalles completos en: `FORMATO_EXCEL_IMPORTACION.md`

**Columnas requeridas (A-F):**

| Columna | Contenido | Ejemplo |
|---------|-----------|---------|
| A | Código de Seguimiento | HE-2024-00001 |
| B | Nombre Destinatario | Juan Pérez |
| C | Teléfono | 70123456 |
| D | Dirección | Av. Arce #2350 |
| E | Zona | Centro |
| F | Descripción | Documentos |

**Ejemplo completo:**
```
HE-2024-00001 | María López | 77889900 | Av. 6 de Agosto #1234 | Centro | Documentos
HE-2024-00002 | Pedro Gómez | 71234567 | Calle Potosí #567 | Miraflores | Ropa
```

## 📊 Consultas SQL Útiles

### Ver últimas importaciones
```sql
SELECT 
    i.*,
    u.nombre as usuario
FROM importaciones_archivos i
LEFT JOIN usuarios u ON i.usuario_id = u.id
ORDER BY i.fecha_importacion DESC
LIMIT 10;
```

### Ver paquetes próximos a vencer
```sql
SELECT 
    p.codigo_seguimiento,
    p.destinatario_nombre,
    p.fecha_limite_entrega,
    TIMESTAMPDIFF(HOUR, NOW(), p.fecha_limite_entrega) as horas_restantes,
    u.nombre as repartidor
FROM paquetes p
INNER JOIN usuarios u ON p.repartidor_id = u.id
WHERE p.estado = 'en_ruta'
AND p.fecha_limite_entrega > NOW()
ORDER BY p.fecha_limite_entrega ASC;
```

### Ver notificaciones WhatsApp enviadas hoy
```sql
SELECT 
    n.*,
    p.codigo_seguimiento
FROM notificaciones_whatsapp n
INNER JOIN paquetes p ON n.paquete_id = p.id
WHERE DATE(n.fecha_envio) = CURDATE()
ORDER BY n.fecha_envio DESC;
```

### Ver alertas de 24 horas enviadas
```sql
SELECT 
    a.*,
    p.codigo_seguimiento,
    u.nombre as repartidor
FROM alertas_entrega a
INNER JOIN paquetes p ON a.paquete_id = p.id
INNER JOIN usuarios u ON a.repartidor_id = u.id
WHERE a.estado = 'enviada'
ORDER BY a.fecha_envio DESC
LIMIT 20;
```

### Ver paquetes vencidos no entregados
```sql
SELECT 
    p.codigo_seguimiento,
    p.destinatario_nombre,
    p.fecha_limite_entrega,
    TIMESTAMPDIFF(HOUR, p.fecha_limite_entrega, NOW()) as horas_vencidas,
    u.nombre as repartidor,
    u.telefono as repartidor_tel
FROM paquetes p
INNER JOIN usuarios u ON p.repartidor_id = u.id
WHERE p.estado = 'en_ruta'
AND p.fecha_limite_entrega < NOW()
ORDER BY horas_vencidas DESC;
```

## 🔍 Monitoreo y Logs

### Archivo de Log del Cron
```
cron/alertas_log.txt
```

Formato:
```
[2024-01-15 10:00:01] === Inicio de verificación ===
[2024-01-15 10:00:01] Paquetes encontrados: 3
[2024-01-15 10:00:02] ✓ Alerta enviada para HE-2024-00045
[2024-01-15 10:00:03] === Resumen ===
[2024-01-15 10:00:03] Procesados: 3 | Exitosos: 3 | Errores: 0
```

### Verificar ejecución del cron
```powershell
# Ver últimas 20 líneas del log
Get-Content C:\xampp\htdocs\pruebitaaa\cron\alertas_log.txt -Tail 20
```

### Panel de Admin - Historial de Importaciones

Ir a: **Admin → Sistema → Importar Excel**

Muestra:
- Fecha y hora de importación
- Nombre del archivo
- Usuario que importó
- Total de registros procesados
- Registros exitosos vs errores
- Detalles de errores (si los hay)

## 🛠️ Personalización

### Cambiar tiempo límite de entrega

`admin/paquetes_asignar.php` línea 14:
```php
// Cambiar de 2 días a 3 días:
$fecha_limite = date('Y-m-d H:i:s', strtotime('+3 days'));

// Cambiar a 1 día:
$fecha_limite = date('Y-m-d H:i:s', strtotime('+1 day'));
```

### Cambiar tiempo de alerta (24 horas)

`cron/verificar_alertas_entrega.php` línea 61:
```php
// Cambiar de 24 horas a 12 horas:
AND p.fecha_limite_entrega <= DATE_ADD(NOW(), INTERVAL 12 HOUR)

// Cambiar a 48 horas (2 días):
AND p.fecha_limite_entrega <= DATE_ADD(NOW(), INTERVAL 48 HOUR)
```

### Personalizar mensajes WhatsApp

`config/whatsapp_helper.php`:

**Mensaje de asignación (línea 47-57):**
```php
$mensaje = "📦 *Tu Empresa - Notificación*\n\n";
$mensaje .= "Mensaje personalizado aquí...\n";
```

**Mensaje de alerta (línea 101-111):**
```php
$mensaje = "⚠️ *ALERTA PERSONALIZADA*\n\n";
$mensaje .= "Mensaje personalizado aquí...\n";
```

## ✅ Checklist de Implementación

- [ ] Ejecutar SQL: `add_importacion_notificaciones.sql`
- [ ] Instalar PhpSpreadsheet con Composer
- [ ] Crear carpeta `uploads/excel/` con permisos
- [ ] Configurar credenciales de WhatsApp API
- [ ] Probar importación de Excel con archivo de ejemplo
- [ ] Configurar cron job / Programador de Tareas
- [ ] Probar asignación de paquete y verificar WhatsApp
- [ ] Verificar que el log del cron se genera correctamente
- [ ] Monitorear las primeras 24 horas de funcionamiento
- [ ] Agregar zona horaria en `php.ini`: `date.timezone = America/La_Paz`

## 🆘 Soporte y Problemas Comunes

### "Class 'PhpOffice\PhpSpreadsheet\IOFactory' not found"
**Solución:** Instalar PhpSpreadsheet con Composer
```bash
cd C:\xampp\htdocs\pruebitaaa
composer require phpoffice/phpspreadsheet
```

### "No se pudo subir el archivo"
**Solución:** Verificar permisos de la carpeta uploads
```powershell
New-Item -ItemType Directory -Path "uploads\excel" -Force
icacls "uploads" /grant Everyone:(OI)(CI)F
```

### "El cron no se ejecuta"
**Solución:** Verificar configuración del Programador de Tareas
- Ver: `cron/CONFIGURAR_CRON.md`
- Verificar: `cron/alertas_log.txt` debe tener registros cada hora

### "WhatsApp no se envía"
**Solución:**
1. Verificar credenciales en `config/whatsapp_helper.php`
2. Cambiar `$modo_simulacion = true;` a `false` en línea 18
3. Consultar tabla `notificaciones_whatsapp` para ver errores

### "Error al procesar Excel"
**Solución:** Verificar formato del archivo
- Debe ser .xlsx o .xls
- Debe tener 6 columnas (A-F)
- Ver: `FORMATO_EXCEL_IMPORTACION.md`

## 📞 Contacto y Documentación

- **Documentación de PhpSpreadsheet:** https://phpspreadsheet.readthedocs.io/
- **API de Twilio WhatsApp:** https://www.twilio.com/docs/whatsapp
- **WhatsApp Business Cloud:** https://developers.facebook.com/docs/whatsapp

---

**Sistema desarrollado para Hermes Express**  
**Versión:** 1.0  
**Fecha:** Enero 2024
