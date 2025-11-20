# HERMES EXPRESS LOGISTIC - Guía de Uso

## 📱 Acceso por Roles

### 👨‍💼 ADMINISTRADOR
**Acceso completo al sistema**

**Funcionalidades:**
- ✅ Ver dashboard con todas las estadísticas e ingresos
- ✅ Gestionar paquetes (crear, editar, eliminar, asignar)
- ✅ Crear y asignar rutas a repartidores
- ✅ Gestionar usuarios (crear admin, asistentes, repartidores)
- ✅ Ver y aprobar pagos a repartidores
- ✅ Gestionar ingresos y gastos de la empresa
- ✅ Importar datos desde SAVAR
- ✅ Generar reportes completos
- ✅ Configuración general del sistema

**URL de acceso:** `/admin/dashboard.php`

---

### 👨‍💻 ASISTENTE
**Acceso limitado para operaciones diarias**

**Puede hacer:**
- ✅ Ver dashboard con estadísticas operativas
- ✅ Gestionar paquetes
- ✅ Asignar rutas a repartidores
- ✅ Ver entregas realizadas
- ✅ Gestionar paquetes rezagados
- ✅ Importar datos desde SAVAR
- ✅ Generar reportes operativos

**NO puede hacer:**
- ❌ Crear o eliminar usuarios
- ❌ Ver ingresos totales de la empresa
- ❌ Procesar pagos a repartidores
- ❌ Modificar configuración del sistema

**URL de acceso:** `/asistente/dashboard.php`

---

### 🚚 REPARTIDOR
**Acceso móvil-first para trabajo en campo**

**Funcionalidades:**
- ✅ Ver sus paquetes asignados
- ✅ Ver sus rutas del día
- ✅ **Mapa en tiempo real** con ubicación GPS
- ✅ **Registrar entregas con:**
  - 📸 Fotos (cámara o galería)
  - 📍 Geolocalización automática
  - ✍️ Datos del receptor (nombre, DNI)
  - 📝 Observaciones
- ✅ Marcar paquetes como rezagados
- ✅ Ver historial de entregas
- ✅ Ver sus ingresos mensuales
- ✅ Tracking GPS en tiempo real

**NO puede hacer:**
- ❌ Ver paquetes de otros repartidores
- ❌ Asignar paquetes
- ❌ Ver ingresos de la empresa
- ❌ Gestionar usuarios

**URL de acceso:** `/repartidor/dashboard.php`

---

## 🔥 Funcionalidades Principales

### 📦 Gestión de Paquetes

**Estados de paquetes:**
1. **Pendiente** - Recién ingresado, sin asignar
2. **En Ruta** - Asignado a un repartidor
3. **Entregado** - Entrega exitosa
4. **Rezagado** - No se pudo entregar
5. **Devuelto** - Retornado al origen
6. **Cancelado** - Cancelado por el cliente

**Prioridades:**
- **Normal** - Entrega estándar
- **Urgente** - Alta prioridad
- **Express** - Máxima prioridad

**Importar desde SAVAR:**
1. Ve a "Importar de SAVAR"
2. Ejecuta el script Python: `python python/savar_importer.py`
3. Los paquetes se importarán automáticamente con geocodificación

### 🗺️ Sistema de Rutas

**Crear una ruta:**
1. Admin/Asistente va a "Rutas" → "Nueva Ruta"
2. Selecciona repartidor
3. Agrega paquetes a la ruta
4. El sistema optimiza el orden automáticamente
5. Guarda la ruta

**El repartidor verá:**
- Lista de paquetes ordenados
- Mapa con todos los puntos
- Navegación turn-by-turn
- Progreso en tiempo real

### 📸 Registro de Entregas (REPARTIDOR)

**Proceso paso a paso:**

1. **Seleccionar paquete** de la lista de paquetes en ruta
2. **Tomar fotos:**
   - Activar cámara desde el celular
   - Capturar foto del paquete entregado
   - Agregar fotos adicionales (opcional)
3. **Obtener ubicación:**
   - Presionar "Obtener Mi Ubicación Actual"
   - Sistema captura GPS automáticamente
4. **Datos del receptor:**
   - Nombre de quien recibe
   - DNI (opcional)
   - Observaciones
5. **Tipo de entrega:**
   - Exitosa
   - Rechazada
   - Destinatario no encontrado
6. **Confirmar entrega**

**Resultado:**
- Paquete marcado como entregado
- Fotos guardadas en el servidor
- Ubicación registrada
- Ingreso generado automáticamente

### 🌍 Mapa en Tiempo Real

**Características:**
- Ver todos los paquetes en el mapa
- Ver ruta optimizada
- Tracking GPS del repartidor
- Distancia y tiempo estimado
- Navegación a cada punto

**Activar Tracking:**
1. Ir a "Mapa en Tiempo Real"
2. Presionar "Activar Tracker"
3. La ubicación se actualiza cada 5 segundos
4. Admin puede ver la ubicación en vivo

### 💰 Sistema de Pagos

**Cálculo de pagos a repartidores:**
```
Pago = (Entregas Exitosas × Tarifa) + Bonificaciones - Deducciones
```

**Tarifas configurables:**
- Normal: S/. 3.50 por paquete
- Urgente: S/. 5.00 por paquete
- Express: S/. 7.50 por paquete

**Generar pago (Admin):**
1. Ir a "Pagos" → "Generar Pago"
2. Seleccionar repartidor
3. Definir periodo (fechas)
4. Sistema calcula automáticamente
5. Agregar bonificaciones/deducciones
6. Guardar pago

**El repartidor puede ver:**
- Total de entregas del mes
- Ingresos acumulados
- Historial de pagos
- Gráficos de rendimiento

### 📊 Reportes y Estadísticas

**Dashboard Admin muestra:**
- Total de paquetes
- Entregas del día
- Paquetes en ruta
- Paquetes rezagados
- Ingresos del mes
- Gastos del mes
- Balance
- Gráficos de tendencias
- Top repartidores

**Reportes disponibles:**
- Entregas por periodo
- Rendimiento por repartidor
- Paquetes rezagados
- Análisis financiero
- Exportable a CSV/Excel

### 📱 Uso desde Móvil

**El sistema es 100% responsive:**
- Todos los repartidores pueden usar solo su celular
- Interfaz optimizada para pantallas pequeñas
- Botones grandes para fácil acceso
- Cámara integrada
- GPS integrado
- Llamadas directas desde el sistema

**Recomendaciones para repartidores:**
- Usar Chrome o Safari actualizado
- Permitir acceso a cámara y ubicación
- Tener datos móviles o WiFi
- Mantener el GPS activado

### 🔐 Seguridad

**El sistema incluye:**
- Contraseñas encriptadas (bcrypt)
- Protección CSRF
- Validación de sesiones
- Sanitización de datos
- Prepared statements (SQL Injection protection)
- Logs de todas las acciones
- Control de acceso por roles

**Cambiar contraseña:**
1. Ir a "Mi Perfil"
2. Sección "Cambiar Contraseña"
3. Ingresar contraseña actual
4. Nueva contraseña
5. Confirmar

---

## 🛠️ Configuración Avanzada

### Ajustar Tarifas

Editar `config/config.php`:
```php
define('TARIFA_POR_PAQUETE', 3.50);
define('TARIFA_URGENTE', 5.00);
define('TARIFA_EXPRESS', 7.50);
```

### Configurar Email (futuro)

Para notificaciones por correo, configura:
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'tu_email@gmail.com');
define('SMTP_PASS', 'tu_contraseña');
```

### Automatizar Importación SAVAR

**Windows Task Scheduler:**
```powershell
schtasks /create /sc daily /tn "SAVAR Import" /tr "python C:\xampp\htdocs\NUEVOOO\python\savar_importer.py" /st 06:00
```

Esto ejecuta la importación todos los días a las 6 AM.

---

## 📞 Flujo de Trabajo Típico

### Día a día:

**06:00 AM** - Importación automática de SAVAR
**08:00 AM** - Admin/Asistente asigna paquetes a repartidores
**08:30 AM** - Admin/Asistente crea rutas del día
**09:00 AM** - Repartidores reciben notificación de su ruta
**09:00 AM - 06:00 PM** - Repartidores entregan paquetes
- Usan GPS para navegar
- Registran entregas con foto y ubicación
- Marcan rezagados si es necesario
**06:00 PM** - Admin revisa entregas del día
**Fin de mes** - Admin genera pagos a repartidores

---

## 💡 Consejos y Mejores Prácticas

**Para Administradores:**
- Revisa el dashboard diariamente
- Asigna rutas optimizadas por zona
- Genera reportes semanales
- Revisa paquetes rezagados frecuentemente

**Para Repartidores:**
- Revisa tu ruta antes de salir
- Mantén el GPS activado
- Toma fotos claras de las entregas
- Marca ubicación exacta en cada entrega
- Reporta problemas inmediatamente

**Para Asistentes:**
- Coordina con repartidores
- Optimiza asignación de rutas
- Soluciona paquetes rezagados
- Mantén datos actualizados

---

## 🐛 Solución de Problemas Comunes

**No puedo tomar fotos:**
- Permite acceso a cámara en el navegador
- Usa HTTPS si es posible
- Intenta subir desde galería

**El mapa no carga:**
- Verifica la API Key de Google Maps
- Revisa tu conexión a internet
- Limpia caché del navegador

**No se registra mi ubicación:**
- Activa GPS en tu dispositivo
- Permite ubicación en el navegador
- Verifica conexión a internet

**Error al importar SAVAR:**
- Verifica credenciales
- Ajusta selectores CSS según el HTML de SAVAR
- Revisa ChromeDriver compatible

---

## 📈 Próximas Mejoras Sugeridas

- [ ] Notificaciones push a repartidores
- [ ] Chat interno entre admin y repartidores
- [ ] Firma digital del receptor
- [ ] Escaneo de códigos de barras/QR
- [ ] Integración con WhatsApp Business
- [ ] App móvil nativa (Android/iOS)
- [ ] Predicción de tiempos de entrega con IA
- [ ] Sistema de calificación de repartidores

---

**¡Gracias por usar HERMES EXPRESS LOGISTIC!**

Sistema diseñado para optimizar tu operación logística. 🚀📦
