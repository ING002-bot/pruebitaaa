# Guía de Configuración - Notificaciones WhatsApp

## Descripción
El sistema está configurado para enviar notificaciones automáticas por WhatsApp cuando:
- ✅ Se asigna un repartidor a un paquete
- ✅ Se entrega un paquete exitosamente
- ✅ Hay problemas en la entrega
- ✅ Faltan 24 horas para el vencimiento de entrega

## Instalación Inicial

### Paso 1: Crear las Tablas en la BD
1. Inicia sesión como administrador
2. Accede a: `http://localhost/pruebitaaa/crear_tablas_whatsapp.php`
3. El script creará automáticamente las tablas necesarias

O ejecuta manualmente en MySQL:
```sql
-- Ver archivo: database/crear_tabla_whatsapp.sql
```

## Configuración por Tipo de API

### Opción 1: Modo Simulado (ACTUAL - Por defecto)
**Para desarrollo/testing**

- Los mensajes se simulan y aparecen en los logs
- Perfecto para probar sin costo
- Se registran en la base de datos

En `config/config.php`:
```php
define('WHATSAPP_API_TYPE', 'simulado');
```

Ver logs en: `php error_log` (configurado en php.ini)

---

### Opción 2: Twilio (RECOMENDADO PARA PRODUCCIÓN)

#### Instalación de dependencias:
```bash
composer require twilio/sdk
```

#### Configuración:
1. Crea cuenta en https://www.twilio.com
2. Obtén tus credenciales:
   - Account SID
   - Auth Token
   - Número de Twilio para WhatsApp

3. Agrega a `config/config.php`:
```php
define('WHATSAPP_API_TYPE', 'twilio');
define('TWILIO_ACCOUNT_SID', 'tu_account_sid');
define('TWILIO_AUTH_TOKEN', 'tu_auth_token');
define('TWILIO_WHATSAPP_FROM', 'whatsapp:+14155238886'); // Número Twilio
define('WHATSAPP_API_TOKEN', 'tu_auth_token');
```

#### Implementación en `whatsapp_helper.php`:
- Descomenta la sección de Twilio en el método `enviarConTwilio()`
- Requiere instancia de Twilio Client

---

### Opción 3: WhatsApp Business Cloud API

#### Configuración:
1. Accede a https://www.whatsapp.com/business/
2. Obtén tu:
   - Business Account ID
   - Phone Number ID
   - Access Token (API Token)
   - Business Phone Number

3. Agrega a `config/config.php`:
```php
define('WHATSAPP_API_TYPE', 'whatsapp_cloud');
define('WHATSAPP_API_URL', 'https://graph.instagram.com/v18.0/YOUR_PHONE_NUMBER_ID/messages');
define('WHATSAPP_API_TOKEN', 'tu_access_token');
define('WHATSAPP_NUMERO_EMPRESA', '+51XXXXXXXXX');
```

#### Características:
- Integración oficial de Meta/WhatsApp
- Mejor soporte y documentación
- Mayor confiabilidad

---

## Estructura de Datos

### Tabla: `notificaciones_whatsapp`
Registra todos los intentos de envío de mensajes

```
id                      - ID único
paquete_id              - Referencia al paquete
telefono                - Número destinatario
mensaje                 - Contenido del mensaje
tipo                    - Tipo: asignacion, alerta_24h, entrega_exitosa, problema_entrega
estado                  - pendiente, enviado, fallido
respuesta_api           - Respuesta del proveedor
intentos                - Número de intentos
fecha_envio             - Timestamp del envío
fecha_creacion          - Timestamp de creación
```

### Tabla: `alertas_entrega`
Registra alertas de entrega

```
id                      - ID único
paquete_id              - Referencia al paquete
repartidor_id           - Referencia al repartidor
tipo_alerta             - Tipo de alerta (24_horas, vencida, etc)
mensaje                 - Contenido del mensaje
estado                  - enviada, leída, etc
fecha_creacion          - Timestamp
```

### Tabla: `logs_whatsapp`
Registro detallado de eventos

```
id                      - ID único
paquete_id              - Paquete relacionado
usuario_id              - Usuario que ejecutó la acción
tipo_evento             - intento_envio, fallo, reintento, exito
detalles                - Información adicional
fecha_evento            - Timestamp
```

---

## Uso en el Sistema

### Envío Automático al Asignar Repartidor
En `admin/paquetes_asignar.php` y `admin/paquete_actualizar.php`:
```php
$whatsapp = new WhatsAppNotificaciones();
$whatsapp->notificarAsignacion($paquete_id);
```

**Mensaje enviado:**
```
🚚 *HERMES EXPRESS*
─────────────────────

¡Hola *[CLIENTE]*! 👋

Tu paquete ha sido asignado para entrega

📦 *Código:* [CODIGO]
🚘 *Repartidor:* [NOMBRE REPARTIDOR]
📅 *Fecha estimada:* [FECHA]
📍 *Dirección:* [DIRECCION]
📱 *Contacto repartidor:* [TELEFONO]

Gracias por confiar en nosotros! 🙏
HERMES EXPRESS LOGISTIC
```

### Otros Métodos Disponibles

#### Notificar Entrega Exitosa
```php
$whatsapp->notificarEntregaExitosa($paquete_id, 'Juan López');
```

#### Notificar Problema en Entrega
```php
$whatsapp->notificarProblemaEntrega($paquete_id, 'no_encontrado');
// Motivos: 'no_encontrado', 'rechazada', 'destinatario_ausente'
```

#### Enviar Alerta 24h antes
```php
$whatsapp->enviarAlerta24Horas($paquete_id, $repartidor_id);
```

---

## Validaciones de Teléfono

El sistema automáticamente:
- ✅ Limpia caracteres especiales
- ✅ Agrega código de país (+51 para Perú)
- ✅ Valida formato de número

Ejemplos aceptados:
- `987654321` → `+51987654321`
- `0987654321` → `+51987654321`
- `+51987654321` → `+51987654321`
- `+1 (987) 654-321` → `+1987654321`

---

## Troubleshooting

### Los mensajes no se envían
1. Verifica que el paquete tenga repartidor asignado
2. Verifica que el cliente tenga número de teléfono válido
3. Revisa los logs en `php error_log`

### Error: "Tabla no encontrada"
1. Ejecuta: `http://localhost/pruebitaaa/crear_tablas_whatsapp.php`
2. Verifica conexión a BD

### No aparecen en BD
1. Verifica que la tabla `notificaciones_whatsapp` exista
2. Revisa permisos de BD

### Errores de API Real
1. Verifica credenciales configuradas
2. Comprueba saldo/cuota en tu proveedor
3. Revisa logs de error específicos

---

## Logs y Monitoreo

### Ver intentos de envío
```sql
SELECT * FROM notificaciones_whatsapp 
WHERE fecha_envio >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY fecha_envio DESC;
```

### Ver fallos
```sql
SELECT * FROM notificaciones_whatsapp 
WHERE estado = 'fallido'
ORDER BY fecha_envio DESC;
```

### Estadísticas
```sql
SELECT 
    tipo,
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'enviado' THEN 1 ELSE 0 END) as exitosos,
    SUM(CASE WHEN estado = 'fallido' THEN 1 ELSE 0 END) as fallidos
FROM notificaciones_whatsapp
GROUP BY tipo;
```

---

## Notas Importantes

⚠️ **MODO SIMULADO**: Por defecto está en modo simulado. Para producción:
1. Selecciona un proveedor (Twilio o WhatsApp Cloud)
2. Configura credenciales
3. Cambia `WHATSAPP_API_TYPE`
4. Prueba con mensajes reales

💡 **COSTOS**: 
- Twilio: ~$0.01 - $0.05 por mensaje
- WhatsApp Cloud: Variable según plan

🔒 **SEGURIDAD**: 
- Guarda credenciales en `.env` (no en código)
- Usa variables de entorno en producción
- Nunca commitees tokens a git

📱 **COMPATIBILIDAD**: 
- Funciona con cualquier número WhatsApp
- No requiere que el usuario esté en tu lista de contactos
- Mensajes solo de texto (actualmente)

---

## Próximas Mejoras
- [ ] Soporte para imágenes en mensajes
- [ ] Templates personalizados
- [ ] Confirmación de lectura
- [ ] Estadísticas en dashboard
- [ ] Reintentos automáticos
- [ ] Cola de mensajes (para volumen alto)
