# 🧪 Twilio Sandbox - Guía Práctica

## Estado Actual

✅ **Credenciales de SANDBOX** configuradas
- Tipo: Credenciales de **PRUEBA** (para testing, no producción)
- Limitación: Solo puedes enviar a números que AGREGUES a una lista blanca
- Costo: **GRATIS**

---

## 🚀 Cómo Empezar

### Opción 1: Página Web (Recomendado)
```
http://localhost/pruebitaaa/sandbox_configuracion.php
```

En esta página puedes:
- ✅ Ver configuración actual
- ✅ Leer instrucciones paso a paso
- ✅ Agregar números permitidos
- ✅ Probar envío de mensajes

### Opción 2: Script CLI
```
C:\xampp\php\php.exe c:\xampp\htdocs\pruebitaaa\test_sandbox.php
```

---

## ⚡ Pasos Rápidos para Probar

### 1. Agregar Número a Lista Blanca
```
1. Ve a: https://www.twilio.com/console/sms/sandbox
2. Busca "Participant phone numbers"
3. Haz clic "Add participant phone number"
4. Ingresa: +51987654321 (cambia al número del cliente)
5. Click "Add"
```

### 2. Probar Envío
```
1. Ve a: http://localhost/pruebitaaa/sandbox_configuracion.php
2. En "Prueba de Envío", ingresa: +51987654321
3. Click "Enviar Prueba"
4. ¡Espera ~5 segundos y recibe el WhatsApp!
```

### 3. Ver Mensajes Registrados
```
http://localhost/pruebitaaa/test_whatsapp.php
```

---

## 📝 Formato de Números

**CORRECTO:**
- `+51987654321` (con código de país)
- `+51 987 654 321` (con espacios)

**INCORRECTO:**
- `987654321` (sin código)
- `0987654321` (con cero)

---

## 🔧 Integración en el Sistema

El WhatsApp se envía **automáticamente** cuando:

1. **Admin asigna repartidor** a un paquete
   - Archivo: `admin/paquetes.php`
   - Se ejecuta: `admin/paquete_actualizar.php`
   - Envía: Notificación al cliente

### Código de Integración
```php
// En admin/paquete_actualizar.php
require_once '../config/whatsapp_helper.php';
$whatsapp = new WhatsAppNotificaciones();

if ($repartidor_anterior !== $repartidor_id) {
    // Solo envía si el repartidor CAMBIÓ
    $whatsapp->notificarAsignacion($paquete_id);
}
```

---

## ⚠️ Limitaciones de Sandbox

| Limitación | Descripción |
|-----------|------------|
| 📱 Números limitados | Solo a números que APRUEBES en Sandbox |
| 🏷️ Prefijo en mensajes | Pueden agregar "[Sandbox]" al mensaje |
| 💰 No hay costo | Perfecto para testing |
| 🚀 No es producción | Para production, actualiza la cuenta |

---

## ✅ Próximos Pasos

### Para Testing Inmediato:
1. ✅ Agrega tu número a Sandbox
2. ✅ Prueba envío desde la página
3. ✅ Asigna un repartidor a un paquete
4. ✅ Verifica que recibas el WhatsApp

### Para Producción (Luego):
1. Actualiza cuenta Twilio a producción
2. Obtén número de WhatsApp Business
3. Actualiza credenciales en config.php
4. ¡Listo para enviar a cualquier cliente!

---

## 🔗 Links Útiles

- **Sandbox Console:** https://www.twilio.com/console/sms/sandbox
- **Documentación:** https://www.twilio.com/docs/whatsapp
- **Página de Prueba:** http://localhost/pruebitaaa/sandbox_configuracion.php
- **Diagnóstico:** http://localhost/pruebitaaa/diagnostico_twilio.php

---

## 📊 Monitoreo

### En la BD
```sql
SELECT * FROM notificaciones_whatsapp 
ORDER BY fecha_envio DESC 
LIMIT 10;
```

### En Twilio Console
```
https://www.twilio.com/console/messages
```
Verás todos tus envíos de prueba

---

## ❓ Troubleshooting

| Problema | Solución |
|----------|----------|
| ❌ No recibe mensaje | Verifica que el número esté en lista blanca Sandbox |
| ❌ Error 401 | Credenciales inválidas (pero si llegas aquí, ya las verificaste) |
| ❌ "Número no válido" | Usa formato: +51987654321 |
| ❌ No aparece en BD | Verifica que tabla `notificaciones_whatsapp` exista |

---

## 🎯 Resumen Técnico

- **API:** Twilio REST v2010-04-01
- **Método:** POST a `/Accounts/{SID}/Messages.json`
- **Auth:** HTTP Basic (SID:Token en base64)
- **Transporte:** HTTPS cURL
- **Respuesta:** JSON con Message SID

---

**¡Sistema listo para probar!** 🚀

Próximo paso: Agrega tu número a Sandbox y prueba el envío.
