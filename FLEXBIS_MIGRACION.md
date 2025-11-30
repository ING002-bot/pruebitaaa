# MIGRACIÓN DE TWILIO A FLEXBIS WHATSAPP API

## 🔄 Cambios Realizados

### 1. Configuración Actualizada (`config/config.php`)

```php
// ==================== CONFIGURACIÓN WHATSAPP FLEXBIS ====================
// Configurar para enviar WhatsApp real usando API de Flexbis
// NOTA: Agregar credenciales en variables de entorno
define('WHATSAPP_API_TYPE', getenv('WHATSAPP_API_TYPE') ?: 'simulado');
define('FLEXBIS_API_SID', getenv('FLEXBIS_API_SID') ?: '');
define('FLEXBIS_API_KEY', getenv('FLEXBIS_API_KEY') ?: '');
define('FLEXBIS_API_URL', getenv('FLEXBIS_API_URL') ?: 'https://api.flexbis.com/v1/');
define('FLEXBIS_WHATSAPP_FROM', getenv('FLEXBIS_WHATSAPP_FROM') ?: '');
define('WHATSAPP_API_TOKEN', getenv('WHATSAPP_API_TOKEN') ?: '');
```

### 2. Variables de Entorno (`.env`)

```bash
# ==================== FLEXBIS WHATSAPP API ====================
WHATSAPP_API_TYPE=flexbis
FLEXBIS_API_SID=tu_flexbis_sid_aqui
FLEXBIS_API_KEY=tu_flexbis_key_aqui
FLEXBIS_API_URL=https://api.flexbis.com/v1/
FLEXBIS_WHATSAPP_FROM=tu_numero_flexbis_aqui
```

### 3. WhatsApp Helper Actualizado (`config/whatsapp_helper.php`)

- ✅ Nuevo método `enviarConFlexbis()` implementado
- ✅ Soporte para múltiples APIs (Twilio, WhatsApp Cloud, Flexbis, Simulado)
- ✅ Compatibilidad hacia atrás mantenida
- ✅ Logging detallado para debugging

### 4. Nueva Interfaz de Pruebas (`test_flexbis.php`)

- ✅ Verificación de configuración
- ✅ Test de autenticación con Flexbis
- ✅ Envío de mensajes de prueba
- ✅ Información del sistema

## 📋 Pasos para Configurar Flexbis

### Paso 1: Configurar Variables de Entorno

1. Copia tu archivo `.env.example` a `.env`
2. Actualiza las credenciales de Flexbis:

```bash
cp .env.example .env
```

Edita `.env`:
```bash
WHATSAPP_API_TYPE=flexbis
FLEXBIS_API_SID=TU_SID_AQUI
FLEXBIS_API_KEY=TU_KEY_AQUI
FLEXBIS_API_URL=https://api.flexbis.com/v1/
FLEXBIS_WHATSAPP_FROM=+51XXXXXXXXX
```

### Paso 2: Verificar la Configuración

1. Ve a: `http://localhost/pruebitaaa/test_flexbis.php`
2. Haz clic en "Verificar Configuración"
3. Asegúrate de que todo esté en verde

### Paso 3: Probar la Autenticación

1. En la misma página, haz clic en "Probar Conexión"
2. Verifica que la respuesta HTTP sea 200-299

### Paso 4: Enviar Mensaje de Prueba

1. Usa la sección "Enviar Mensaje de Prueba"
2. Ingresa tu número de prueba
3. Envía un mensaje corto

## 🔧 Estructura del Método Flexbis

```php
private function enviarConFlexbis($telefono, $mensaje) {
    // Verificar credenciales
    $flexbis_sid = defined('FLEXBIS_API_SID') ? constant('FLEXBIS_API_SID') : '';
    $flexbis_key = defined('FLEXBIS_API_KEY') ? constant('FLEXBIS_API_KEY') : '';
    
    // Construir petición
    $url = rtrim($flexbis_url, '/') . '/messages/whatsapp';
    $post_data = [
        'sid' => $flexbis_sid,
        'to' => $telefono,
        'message' => $mensaje,
        'from' => $flexbis_from,
        'type' => 'text'
    ];
    
    // Enviar con cURL
    // ...
}
```

## 🚨 Notas Importantes

### Compatibilidad
- ✅ El sistema sigue funcionando con APIs anteriores
- ✅ Cambio transparente según `WHATSAPP_API_TYPE`
- ✅ Todos los métodos existentes mantienen su interfaz

### Endpoints Flexbis (Estimados)
- **Mensajes**: `POST /messages/whatsapp`
- **Auth Test**: `GET /auth/test`  
- **Webhook**: Configurar en panel de Flexbis

### Logging
Todos los envíos se registran en:
- **Logs de PHP**: `/var/log/apache2/error.log` o similar
- **Base de datos**: Tabla `logs_whatsapp`
- **Notificaciones**: Tabla `notificaciones_whatsapp`

### Costos
- ⚠️ **IMPORTANTE**: Los mensajes de prueba consumen créditos reales
- 📱 Usar solo para testing necesario
- 💡 Usar tipo `simulado` para desarrollo

## 🐛 Troubleshooting

### Error: "Credenciales no configuradas"
```bash
# Verificar que las variables estén cargadas
php -r "var_dump(getenv('FLEXBIS_API_SID'));"
```

### Error: "cURL Error"
```bash
# Verificar cURL
php -m | grep curl
# Verificar SSL
curl -I https://api.flexbis.com/
```

### Error HTTP 401/403
- Verificar SID y KEY correctos
- Verificar formato de autenticación Bearer
- Contactar soporte de Flexbis

### Error HTTP 400
- Verificar formato del número de teléfono
- Verificar longitud del mensaje
- Verificar campo `from` configurado

## 📞 Números de Prueba Sugeridos

```php
// Para testing (reemplaza con números reales)
$numeros_prueba = [
    '+51987654321',  // Tu número personal
    '+51912345678',  // Número de prueba secundario
];
```

## 🔄 Rollback a Twilio

Si necesitas volver a Twilio temporalmente:

```bash
# En .env
WHATSAPP_API_TYPE=twilio
TWILIO_ACCOUNT_SID=ACxxxxxxxxx
TWILIO_AUTH_TOKEN=xxxxxxxxx
TWILIO_WHATSAPP_FROM=whatsapp:+14155238886
```

## ✅ Checklist de Migración

- [ ] Variables de entorno configuradas
- [ ] Test de configuración ✅
- [ ] Test de autenticación ✅  
- [ ] Mensaje de prueba enviado ✅
- [ ] Webhook configurado (si aplica)
- [ ] Monitoreo de logs activo
- [ ] Notificaciones funcionando en producción

---

**Creado**: <?= date('Y-m-d H:i:s') ?>  
**Sistema**: HERMES EXPRESS v2.0  
**API**: Flexbis WhatsApp API