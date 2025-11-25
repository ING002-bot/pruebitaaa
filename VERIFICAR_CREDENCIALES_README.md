# ⚠️ Problema con Credenciales Twilio

## Situación
Las credenciales que proporcionaste fallan con error **401 - Authenticate (Código 20003)**

Esto significa que:
- ❌ Account SID: `ACd50c45f02d91629b452586d0b5aa7f21` - NO VÁLIDO
- ❌ Auth Token: `1ee60ed1e2208401b06eae6d839c16ec` - NO VÁLIDO

## Pasos para Obtener Credenciales Correctas

### 1️⃣ Acceder a Twilio Console
```
https://www.twilio.com/console
```

### 2️⃣ Localizar Account SID
- En la página principal, busca **"Account"** o **"Account SID"**
- Aparecerá como: `AC` seguido de 32 caracteres
- **Ejemplo:** `AC1234567890abcdefghijklmnopqrst`

### 3️⃣ Localizar Auth Token
- En el mismo panel, busca **"Auth Token"**
- Verás un botón con un **👁 (ojo)** para mostrarlo
- Haz clic en el ojo para revelarlo
- Es una cadena de 32 caracteres

### 4️⃣ Verificar Que Sean Correctos
- **Account SID** debe:
  - Comenzar con `AC`
  - Tener exactamente 34 caracteres (AC + 32)
  - Solo contener números y letras mayúsculas

- **Auth Token** debe:
  - Tener exactamente 32 caracteres
  - Ser alfanumérico
  - No tener espacios

### 5️⃣ Si Aún No Funcionan
**Es posible que:**
- La cuenta Twilio esté **suspendida o cancelada**
- El Account SID o Token sean de **otra cuenta**
- La cuenta no tenga **WhatsApp habilitado**

**Soluciones:**
1. Ve a https://www.twilio.com/console
2. Verifica que el Account tenga status **ACTIVE**
3. Ve a **Messaging > Services > Sandbox** para verificar WhatsApp
4. Si no funciona, **crea una nueva credencial** (API Key)

### 6️⃣ Opción: Generar Nuevas Credenciales (API Key)

Si los credenciales anteriores no funcionan, genera unas nuevas:

1. Ve a: https://www.twilio.com/console/project/settings
2. En **"API Keys & tokens"**, busca **"Create API Key"**
3. Selecciona **"Standard"**
4. Guarda el **SID** y **Secret** nuevos
5. Úsalos como TWILIO_ACCOUNT_SID y TWILIO_AUTH_TOKEN

---

## Qué Hacer Ahora

1. **Abre en navegador:** https://www.twilio.com/console
2. **Copia exactamente:**
   - Account SID (sin espacios)
   - Auth Token (sin espacios)
3. **Verifica que sean diferentes** de los anteriores
4. **Comparte conmigo** los nuevos valores

---

## ⚡ Test Rápido
Puedes verificar si tus credenciales funcionan aquí:
http://localhost/pruebitaaa/verificar_credenciales.php

Si ves **✅ AUTENTICACIÓN EXITOSA**, están correctas.
Si ves **❌ FALLO**, son inválidas o expiradas.
