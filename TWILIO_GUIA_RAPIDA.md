# 🚀 Guía Rápida - Twilio WhatsApp Configurado

## ✅ Estado Actual

Tu sistema **ya está configurado con Twilio** y listo para enviar WhatsApp REALES.

```
✅ Account SID: AC8ccfd5ecd15ff03826bb86724f5747e6
✅ Auth Token: 23ea2f2d07def6bb9b9f1b9fa7b02b3b
✅ Número: +14155238886
✅ Tipo de API: twilio (ACTIVO)
```

---

## 🧪 Prueba Rápida en 3 Pasos

### Paso 1: Verifica la Configuración
```
URL: http://localhost/pruebitaaa/test_twilio.php
```

Deberías ver:
- ✅ Autenticación exitosa
- ✅ Datos de tu cuenta Twilio
- ✅ Botón para enviar prueba

### Paso 2: Prueba de Envío
1. Selecciona un paquete que TENGA repartidor asignado
2. Haz clic "Enviar WhatsApp"
3. ¡Deberías recibir el mensaje en segundos!

### Paso 3: Usa en Producción
```
admin/paquetes.php
1. Asigna un repartidor
2. Haz clic "Guardar"
3. ¡El WhatsApp se envía automáticamente!
```

---

## 📱 Dónde recibirás los mensajes

Los mensajes se envían al número de **destinatario_telefono** del paquete.

**Formato válido:**
- `987654321` (9 dígitos)
- `+51987654321` (con código país)
- `0987654321` (con 0 al inicio)

---

## 🔍 Verificar Envíos

### En el Sistema
```
http://localhost/pruebitaaa/test_whatsapp.php
```

Verás una tabla con:
- Código de paquete
- Tipo: "asignacion"
- Estado: "enviado"
- Teléfono
- Fecha/hora

### En Base de Datos
```sql
SELECT * FROM notificaciones_whatsapp 
WHERE estado = 'enviado'
ORDER BY fecha_envio DESC;
```

### En Twilio Console
```
1. Inicia sesión en https://www.twilio.com/console
2. Ve a "Messages"
3. Verifica tus envíos
```

---

## ⚙️ Cómo Funciona Técnicamente

1. **Admin asigna repartidor** → `admin/paquetes.php`
2. **POST a** → `admin/paquete_actualizar.php`
3. **Ejecuta** → `$whatsapp->notificarAsignacion($paquete_id)`
4. **Conecta a** → Twilio API via cURL
5. **Envía a** → Número del cliente
6. **Registra en** → Tabla `notificaciones_whatsapp`

---

## 📊 Mensaje que se Envía

```
🚚 *HERMES EXPRESS*
─────────────────────

¡Hola *[NOMBRE CLIENTE]*! 👋

Tu paquete ha sido asignado para entrega

📦 *Código:* [CODIGO]
🚘 *Repartidor:* [NOMBRE REPARTIDOR]
📅 *Fecha estimada:* [FECHA]
📍 *Dirección:* [DIRECCION]

📱 *Contacto repartidor:* [TELEFONO]

Gracias por confiar en nosotros! 🙏
HERMES EXPRESS LOGISTIC
```

---

## ⚠️ Importante

### Antes de usar en Producción

1. **Verifica el número de teléfono del cliente**
   - Debe estar en formato válido
   - Debe ser número de WhatsApp real

2. **Comprueba saldo en Twilio**
   - Ir a: https://www.twilio.com/console
   - Ver "Account Balance"
   - Cargar créditos si es necesario

3. **Límites de Twilio**
   - Trial account: Números pre-aprobados
   - Producción: Ilimitado (con saldo)

### Costos Aproximados
- WhatsApp mensajes: ~$0.002-0.01 USD por mensaje
- Mejor precio que SMS

---

## 🔧 Solucionar Problemas

### No aparece en logs
```
1. Verifica config.php tenga las constantes
2. Busca "❌" en error_log
3. Revisa en test_twilio.php si hay error de auth
```

### Error 401 en test_twilio.php
```
→ Account SID o Auth Token incorrecto
→ Revisa las credenciales en config.php
→ Cópialas exactamente de Twilio Console
```

### Mensaje no llega
```
1. Verifica que el teléfono sea válido
2. Verifica que sea un número WhatsApp
3. Revisa saldo en Twilio Console
4. Ver respuesta exacta en error_log
```

### "Tabla no existe"
```
Ejecuta: http://localhost/pruebitaaa/crear_tablas_whatsapp.php
```

---

## 📞 Links Útiles

- **Twilio Console:** https://www.twilio.com/console
- **Twilio API Docs:** https://www.twilio.com/docs/whatsapp
- **Números de Prueba:** https://www.twilio.com/console/phone-numbers/verified

---

## ✨ Ahora Qué Puedes Hacer

### Opciones Avanzadas

1. **Personalizar mensajes**
   - Editar métodos en `config/whatsapp_helper.php`
   - Cambiar emojis, texto, formato

2. **Enviar a repartidor**
   - Crear método: `notificarRepartidor()`
   - Enviarle alertas

3. **Registrar confirmación**
   - Implementar webhooks de Twilio
   - Saber si se leyó el mensaje

4. **Automático cada 24h**
   - Crear cronjob que envíe alertas
   - Recordatorio de entrega

---

## 🎯 Status Final

```
✅ Configuración: COMPLETADA
✅ Credenciales: AGREGADAS
✅ Código: IMPLEMENTADO
✅ Pruebas: LISTAS
✅ Producción: HABILITADA
```

**¡Listo para enviar mensajes de WhatsApp reales!** 🚀

---

*Última actualización: 25/11/2025*
