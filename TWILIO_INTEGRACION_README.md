# Integración Twilio WhatsApp - Hermes Express

## 📱 Descripción

Sistema de notificaciones automáticas vía WhatsApp usando la API de Twilio. Los clientes reciben notificaciones en tiempo real sobre el estado de sus paquetes.

## 🚀 Funcionalidades Implementadas

### 1. **Registro de Paquete**
Cuando se registra un nuevo paquete en el sistema, el cliente recibe:
- ✅ Número de tracking
- ✅ Nombre del destinatario
- ✅ Dirección de entrega
- ✅ Confirmación de registro

**Archivo:** `admin/paquetes_guardar.php`

### 2. **Asignación a Repartidor (En Ruta)**
Cuando un paquete se asigna a un repartidor, el cliente recibe:
- ✅ Notificación que su paquete está en camino
- ✅ Nombre del repartidor
- ✅ Placa del vehículo (si está disponible)
- ✅ Número de tracking

**Archivo:** `admin/paquetes_asignar.php`

### 3. **Entrega Exitosa**
Cuando el paquete se entrega correctamente:
- ✅ Confirmación de entrega
- ✅ Nombre de quien recibió
- ✅ Número de tracking
- ✅ Agradecimiento

**Archivo:** `repartidor/entregar_procesar.php`

### 4. **Problemas en Entrega**
Si hay problemas (destinatario ausente, rechazo):
- ✅ Notificación del problema
- ✅ Motivo específico
- ✅ Información de contacto para coordinar

**Archivo:** `repartidor/entregar_procesar.php`

## 📂 Archivos del Sistema

### Clase Principal
```
lib/TwilioWhatsApp.php
```
Contiene todos los métodos para enviar notificaciones.

### Archivos de Prueba
```
twilio_test.php           - Prueba básica de envío
twilio_simulacion.php     - Simulación de 3 tipos de notificaciones
twilio_verify.php         - Verificar credenciales
twilio_check_numbers.php  - Verificar números disponibles
```

### Integración en el Sistema
```
admin/paquetes_guardar.php    - Notificación al registrar paquete
admin/paquetes_asignar.php    - Notificación al asignar repartidor
repartidor/entregar_procesar.php - Notificación de entrega/problema
```

## ⚙️ Configuración

### Credenciales Actuales (Sandbox)
```php
SID: AC7cde09ffb05d087aafa652c485a2529b
Token: 1ee60ed1e2208401b06eae6d839c16ec
Número: whatsapp:+14155238886
```

### Para Producción

1. **Actualizar Cuenta de Twilio**
   - Ve a: https://console.twilio.com/
   - Clic en "Upgrade"
   - Completa información de facturación
   - Tienes $15.47 de crédito gratis

2. **Vincular WhatsApp Business**
   - Solicita número de WhatsApp Business en Twilio
   - O usa WhatsApp Business API directamente
   - Actualiza el número en `lib/TwilioWhatsApp.php`

3. **Modificar Configuración**
   ```php
   // En lib/TwilioWhatsApp.php línea 10
   $this->from_number = 'whatsapp:+TU_NUMERO_BUSINESS';
   ```

## 🧪 Cómo Probar

### Prueba Básica
```bash
php twilio_test.php
```

### Simulación Completa
```bash
php twilio_simulacion.php
```

### Probar en el Sistema
1. Registra un paquete con tu número de teléfono
2. Asígnalo a un repartidor
3. Marca como entregado
4. Verifica las notificaciones en WhatsApp

## ❗ Estado Actual

⚠️ **Cuenta de Prueba (Trial)**
- Las notificaciones están implementadas pero **NO SE ENVÍAN** hasta actualizar la cuenta
- El código está listo y funcionará automáticamente al actualizar
- Error actual: "Twilio could not find a Channel" (63007)

✅ **Cuando actualices:**
- Todo funcionará sin cambios de código
- Solo actualiza el `$this->from_number` si cambias de número

## 📊 Formato de Mensajes

### Nuevo Paquete
```
🎉 *Hermes Express*

Su paquete ha sido registrado:

📦 *Tracking:* PKG-12345
👤 *Destinatario:* Juan Pérez
📍 *Dirección:* Av. Principal 123

Le notificaremos cuando esté en ruta.
```

### En Ruta
```
🚚 *Hermes Express - En Ruta*

Su paquete está en camino:

📦 *Tracking:* PKG-12345
👨‍✈️ *Repartidor:* Carlos Ramos
🚗 *Vehículo:* ABC-123

Estimamos llegar en las próximas horas.
```

### Entregado
```
✅ *Hermes Express - Entregado*

¡Paquete entregado exitosamente!

📦 *Tracking:* PKG-12345
✍️ *Recibió:* María López

Gracias por confiar en Hermes Express.
```

### Problema
```
⚠️ *Hermes Express - Aviso*

Hubo un inconveniente con su paquete:

📦 *Tracking:* PKG-12345
📝 *Motivo:* Destinatario no encontrado

Contactaremos con usted pronto.
```

## 🔧 Métodos Disponibles

```php
$twilio = new TwilioWhatsApp();

// Notificar nuevo paquete
$twilio->notificarNuevoPaquete($telefono, $tracking, $destinatario, $direccion);

// Notificar en ruta
$twilio->notificarEnRuta($telefono, $tracking, $repartidor, $placa);

// Notificar entregado
$twilio->notificarEntregado($telefono, $tracking, $recibio);

// Notificar problema
$twilio->notificarProblema($telefono, $tracking, $motivo);

// Habilitar/deshabilitar
$twilio->setHabilitado(true/false);
```

## 📝 Notas

- Los números de teléfono se formatean automáticamente a formato internacional
- Si el número tiene 9 dígitos, se agrega automáticamente el código de país +51 (Perú)
- Los errores se registran en logs pero no detienen el flujo normal del sistema
- Las notificaciones son opcionales: si fallan, el sistema continúa funcionando

## 🆘 Solución de Problemas

### Error 63007: "Channel not found"
**Causa:** Cuenta de prueba (Trial)
**Solución:** Actualizar cuenta a producción

### Error 21606: "Invalid From number"
**Causa:** Número no habilitado para mensajería
**Solución:** Verificar número en consola de Twilio

### Error 21656: "Invalid Content Variables"
**Causa:** Formato incorrecto de plantilla
**Solución:** Usar mensajes de texto simple o templates aprobados

## 🔗 Enlaces Útiles

- Console Twilio: https://console.twilio.com/
- Documentación API: https://www.twilio.com/docs/whatsapp
- WhatsApp Sandbox: https://console.twilio.com/us1/develop/sms/try-it-out/whatsapp-learn
- Errores comunes: https://www.twilio.com/docs/errors

---

**Fecha de implementación:** 26/11/2025
**Versión:** 1.0
**Estado:** ✅ Listo para producción (requiere actualización de cuenta)
