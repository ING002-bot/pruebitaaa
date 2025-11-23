# Configuración del Sistema de Alertas Automáticas

Este sistema envía alertas automáticas a los repartidores cuando un paquete está a 24 horas o menos de su fecha límite de entrega.

## 📋 Requisitos

- PHP CLI habilitado
- Acceso al Programador de Tareas de Windows o crontab en Linux
- Permisos de escritura en la carpeta `cron/` para el archivo de log

## 🪟 Configuración en Windows (XAMPP)

### Paso 1: Verificar PHP CLI

Abre PowerShell o CMD y ejecuta:
```powershell
C:\xampp\php\php.exe -v
```

Deberías ver la versión de PHP instalada.

### Paso 2: Probar el script manualmente

```powershell
cd C:\xampp\htdocs\pruebitaaa\cron
C:\xampp\php\php.exe verificar_alertas_entrega.php
```

Revisa el archivo `alertas_log.txt` para verificar que funcionó correctamente.

### Paso 3: Configurar Programador de Tareas

1. Presiona `Win + R` y escribe `taskschd.msc`
2. Click en **"Crear tarea..."** (no "Crear tarea básica")
3. En la pestaña **General**:
   - Nombre: `Sistema Alertas Hermes Express`
   - Descripción: `Verificación automática de alertas de entrega cada hora`
   - Selecciona: **"Ejecutar tanto si el usuario inició sesión como si no"**
   - Marca: **"Ejecutar con los privilegios más altos"**

4. En la pestaña **Desencadenadores**:
   - Click en **"Nuevo..."**
   - Configurar la tarea: **"Según una programación"**
   - Iniciar: Fecha y hora actual
   - Configuración avanzada:
     - Marca: **"Repetir la tarea cada: 1 hora"**
     - Durante: **"Indefinidamente"**
   - Marca: **"Habilitado"**
   - Click en **"Aceptar"**

5. En la pestaña **Acciones**:
   - Click en **"Nueva..."**
   - Acción: **"Iniciar un programa"**
   - Programa o script: `C:\xampp\php\php.exe`
   - Agregar argumentos: `C:\xampp\htdocs\pruebitaaa\cron\verificar_alertas_entrega.php`
   - Click en **"Aceptar"**

6. En la pestaña **Condiciones**:
   - DESMARCA: **"Iniciar la tarea solo si el equipo está conectado a la energía de CA"**
   - DESMARCA: **"Detener si el equipo deja de estar conectado a la energía de CA"**

7. En la pestaña **Configuración**:
   - Marca: **"Permitir ejecutar la tarea a petición"**
   - Marca: **"Ejecutar la tarea lo antes posible después de perder un inicio programado"**
   - Si la tarea está en ejecución: **"No iniciar una nueva instancia"**

8. Click en **"Aceptar"** y guarda la tarea

### Paso 4: Probar la tarea programada

1. En el Programador de Tareas, busca tu tarea
2. Click derecho → **"Ejecutar"**
3. Revisa el archivo `C:\xampp\htdocs\pruebitaaa\cron\alertas_log.txt`

## 🐧 Configuración en Linux (Servidor de Producción)

### Paso 1: Dar permisos de ejecución

```bash
chmod +x /var/www/html/pruebitaaa/cron/verificar_alertas_entrega.php
chmod 777 /var/www/html/pruebitaaa/cron/alertas_log.txt
```

### Paso 2: Editar crontab

```bash
crontab -e
```

### Paso 3: Agregar la tarea (ejecutar cada hora)

```bash
0 * * * * /usr/bin/php /var/www/html/pruebitaaa/cron/verificar_alertas_entrega.php >> /var/www/html/pruebitaaa/cron/cron_output.log 2>&1
```

O cada 30 minutos para mayor frecuencia:

```bash
*/30 * * * * /usr/bin/php /var/www/html/pruebitaaa/cron/verificar_alertas_entrega.php >> /var/www/html/pruebitaaa/cron/cron_output.log 2>&1
```

### Paso 4: Guardar y verificar

```bash
# Guardar crontab (Ctrl + O, Enter, Ctrl + X en nano)

# Verificar que se agregó correctamente
crontab -l

# Ver logs
tail -f /var/www/html/pruebitaaa/cron/alertas_log.txt
```

## 📊 Monitoreo y Logs

### Archivo de Log

El sistema crea automáticamente `cron/alertas_log.txt` con el siguiente formato:

```
[2024-01-15 10:00:01] === Inicio de verificación de alertas ===
[2024-01-15 10:00:01] Paquetes encontrados: 3
[2024-01-15 10:00:02] Procesando paquete ID: 45, Código: HE-2024-00045
[2024-01-15 10:00:02] Horas restantes: 18
[2024-01-15 10:00:03] ✓ Alerta enviada correctamente para paquete HE-2024-00045
[2024-01-15 10:00:03] === Resumen de verificación ===
[2024-01-15 10:00:03] Paquetes procesados: 3
[2024-01-15 10:00:03] Alertas enviadas: 3
[2024-01-15 10:00:03] Errores: 0
[2024-01-15 10:00:03] === Fin de verificación ===
```

### Consulta Manual en Base de Datos

Para ver alertas registradas:

```sql
SELECT 
    a.*,
    p.codigo_seguimiento,
    u.nombre as repartidor
FROM alertas_entrega a
INNER JOIN paquetes p ON a.paquete_id = p.id
INNER JOIN usuarios u ON a.repartidor_id = u.id
ORDER BY a.fecha_envio DESC
LIMIT 50;
```

## ⚙️ Configuración del Sistema

### Cambiar el intervalo de alerta

Por defecto, las alertas se envían 24 horas antes. Para cambiarlo, edita `verificar_alertas_entrega.php`:

```php
// Línea 60 - Cambiar INTERVAL 24 HOUR a lo que necesites
AND p.fecha_limite_entrega <= DATE_ADD(NOW(), INTERVAL 48 HOUR)  // 48 horas
AND p.fecha_limite_entrega <= DATE_ADD(NOW(), INTERVAL 12 HOUR)  // 12 horas
```

### Cambiar tiempo límite de entrega

Por defecto, los paquetes tienen 2 días de plazo. Para cambiarlo, edita `admin/paquetes_asignar.php`:

```php
// Línea 14 - Cambiar +2 days a lo que necesites
$fecha_limite = date('Y-m-d H:i:s', strtotime('+3 days'));  // 3 días
$fecha_limite = date('Y-m-d H:i:s', strtotime('+1 day'));   // 1 día
```

## 🔧 Solución de Problemas

### El cron no se ejecuta

**Windows:**
- Verifica que el servicio "Programador de tareas" esté iniciado
- Revisa el Historial de la tarea (pestaña "Historial")
- Asegúrate de tener permisos de administrador

**Linux:**
- Verifica que el servicio cron esté activo: `systemctl status cron`
- Revisa los logs del sistema: `tail -f /var/log/syslog | grep CRON`
- Verifica permisos: `ls -la /var/www/html/pruebitaaa/cron/`

### No se envían las alertas WhatsApp

1. Verifica la configuración de WhatsApp en `config/whatsapp_helper.php`
2. Revisa las credenciales del API (Twilio, WhatsApp Business Cloud, etc.)
3. Consulta la tabla `notificaciones_whatsapp` para ver errores:

```sql
SELECT * FROM notificaciones_whatsapp WHERE estado = 'error' ORDER BY fecha_envio DESC LIMIT 10;
```

### El archivo de log no se crea

Verifica permisos de escritura:

**Windows:**
```powershell
icacls "C:\xampp\htdocs\pruebitaaa\cron" /grant Everyone:(OI)(CI)F
```

**Linux:**
```bash
chmod 777 /var/www/html/pruebitaaa/cron/
```

## 📱 Notificaciones que se envían

### 1. Asignación de Paquete
- **Cuándo:** Al asignar un paquete a un repartidor
- **Destinatario:** Cliente (vía WhatsApp)
- **Mensaje:** Confirmación de asignación con datos del repartidor

### 2. Alerta 24 Horas
- **Cuándo:** Cuando faltan 24 horas o menos para la fecha límite
- **Destinatario:** Repartidor (vía WhatsApp y sistema)
- **Mensaje:** Recordatorio de entrega pendiente con datos del paquete

### 3. Notificación en Sistema
- **Cuándo:** Al asignar paquete y al enviar alerta de 24 horas
- **Destinatario:** Repartidor (panel web)
- **Visible en:** Dashboard del repartidor con ícono de campana

## 🚀 Próximos Pasos

1. ✅ Instalar la tarea programada según tu sistema operativo
2. ✅ Configurar credenciales de API de WhatsApp en `config/whatsapp_helper.php`
3. ✅ Ejecutar el SQL `database/add_importacion_notificaciones.sql`
4. ✅ Probar importación de Excel desde el panel de admin
5. ✅ Verificar que los logs se generen correctamente
6. ✅ Monitorear las primeras 24 horas de funcionamiento

## 📞 Soporte

Para problemas o dudas, revisa:
- Archivo de log: `cron/alertas_log.txt`
- Notificaciones en base de datos: tabla `notificaciones_whatsapp`
- Alertas registradas: tabla `alertas_entrega`
