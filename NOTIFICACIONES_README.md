# Sistema de Notificaciones en Tiempo Real

## 📋 Descripción
Sistema completo de notificaciones en tiempo real para Hermes Express que permite mantener informados a todos los usuarios (Admin, Repartidores y Asistentes) sobre eventos importantes del sistema.

## ✨ Características

### 🔔 Funcionalidades Principales
- **Notificaciones en tiempo real**: Actualización automática cada 30 segundos
- **Contador dinámico**: Badge que muestra el número de notificaciones no leídas
- **Dropdown interactivo**: Panel desplegable con lista de notificaciones
- **Tipos de notificación**: Info, Alerta, Urgente, Sistema (con iconos y colores diferenciados)
- **Marcar como leída**: Individual o todas a la vez
- **Timestamp relativo**: "Hace 5 min", "Hace 2 h", etc.

### 👥 Notificaciones por Rol

#### Administrador
- Entregas completadas por repartidores
- Paquetes marcados como rezagados
- Alertas del sistema
- Reportes importantes

#### Repartidor
- Nuevos paquetes asignados
- Recordatorios de entregas pendientes
- Confirmación de pagos registrados
- Alertas de paquetes urgentes

#### Asistente
- Paquetes pendientes de asignación
- Actualizaciones del sistema
- Alertas de operaciones

## 🛠️ Implementación Técnica

### Archivos Creados/Modificados

#### Nuevos Archivos
1. **`api/notificaciones.php`** - Endpoint para obtener notificaciones
2. **`api/marcar_notificacion_leida.php`** - Endpoint para marcar como leídas
3. **`assets/js/notificaciones.js`** - Lógica JavaScript del cliente
4. **`config/notificaciones_helper.php`** - Funciones helper para crear notificaciones
5. **`asistente/includes/header.php`** - Header con dropdown de notificaciones
6. **`crear_notificaciones_prueba.php`** - Script para crear notificaciones de prueba

#### Archivos Modificados
1. **`admin/includes/header.php`** - Agregado dropdown de notificaciones + corrección ruta foto
2. **`repartidor/includes/header.php`** - Agregado dropdown de notificaciones + corrección ruta foto
3. **`admin/dashboard.php`** - Incluido script notificaciones.js
4. **`repartidor/dashboard.php`** - Incluido script notificaciones.js
5. **`asistente/dashboard.php`** - Incluido script notificaciones.js + header include
6. **`repartidor/entregar_procesar.php`** - Integración para crear notificaciones automáticas
7. **`assets/css/dashboard.css`** - Estilos para dropdown de notificaciones

### Base de Datos

#### Tabla: `notificaciones`
```sql
CREATE TABLE notificaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    tipo ENUM('info', 'alerta', 'urgente', 'sistema') DEFAULT 'info',
    titulo VARCHAR(200) NOT NULL,
    mensaje TEXT NOT NULL,
    leida BOOLEAN DEFAULT FALSE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_leida (usuario_id, leida),
    INDEX idx_fecha (fecha_creacion)
);
```

## 🚀 Uso

### Crear Notificaciones Manualmente

```php
require_once 'config/notificaciones_helper.php';

// Notificación individual
crearNotificacion(
    $usuario_id,
    'info',  // tipo: info, alerta, urgente, sistema
    'Título de la notificación',
    'Mensaje descriptivo de la notificación'
);

// Notificación para todos de un rol
crearNotificacionPorRol(
    'repartidor',  // rol: admin, repartidor, asistente
    'alerta',
    'Mantenimiento programado',
    'El sistema estará en mantenimiento el domingo'
);
```

### Funciones Helper Disponibles

```php
// Asignación de paquete
notificarAsignacionPaquete($repartidor_id, $paquete_id, $tracking);

// Paquete rezagado
notificarPaqueteRezagado($admin_ids, $paquete_id, $tracking);

// Entrega exitosa
notificarEntregaExitosa($admin_ids, $tracking, $repartidor_nombre);

// Pago pendiente
notificarPagoPendiente($repartidor_id, $monto);

// Pago registrado
notificarPagoRegistrado($repartidor_id, $monto, $fecha);

// Obtener administradores
$admins = obtenerAdministradores();
```

### Integración Automática

El sistema ya está integrado en:
- ✅ **Proceso de entregas**: Notifica a admins cuando se completa o rezaga un paquete
- ✅ **Headers**: Todos los roles tienen el dropdown de notificaciones funcional
- ✅ **Auto-actualización**: Las notificaciones se cargan automáticamente cada 30 segundos

## 🎨 Personalización

### Tipos de Notificación

| Tipo | Icono | Color | Uso |
|------|-------|-------|-----|
| **info** | `bi-info-circle` | Azul | Información general |
| **alerta** | `bi-exclamation-triangle` | Amarillo | Advertencias |
| **urgente** | `bi-exclamation-circle` | Rojo | Acciones urgentes |
| **sistema** | `bi-gear` | Gris | Mensajes del sistema |

### CSS Personalizable

```css
.notificaciones-dropdown { }      /* Contenedor del dropdown */
.notificacion-item { }            /* Item individual */
.notificacion-item.leida { }      /* Item ya leído */
.header-icon .badge { }           /* Contador de notificaciones */
```

## 🧪 Testing

### Crear Notificaciones de Prueba
1. Acceder a: `http://localhost/NUEVOOO/crear_notificaciones_prueba.php`
2. Hacer clic en "Confirmar y Crear"
3. Se crearán notificaciones de ejemplo para todos los usuarios activos

### Verificar Funcionamiento
1. Iniciar sesión como cualquier rol
2. Verificar que aparezca el ícono de campana en el header
3. Debe aparecer un badge rojo con el número de notificaciones
4. Al hacer clic, se despliega el dropdown con las notificaciones
5. Hacer clic en "Marcar todas como leídas" para limpiar
6. El contador debe actualizarse automáticamente

## 🔧 Solución de Problemas

### Las notificaciones no aparecen
- ✅ Verificar que la tabla `notificaciones` existe en la BD
- ✅ Revisar que `notificaciones.js` se esté cargando
- ✅ Abrir consola del navegador para ver errores de JavaScript
- ✅ Verificar que el usuario tenga notificaciones en la BD

### El contador no se actualiza
- ✅ Verificar que el script se ejecuta cada 30 segundos (ver consola)
- ✅ Revisar que el endpoint `/api/notificaciones.php` responda correctamente
- ✅ Verificar que el ID `notificaciones-count` exista en el header

### Error al marcar como leída
- ✅ Verificar que `/api/marcar_notificacion_leida.php` sea accesible
- ✅ Revisar logs de PHP para errores de base de datos
- ✅ Confirmar que la sesión del usuario esté activa

## 📝 Próximas Mejoras

- [ ] Sonido de notificación para nuevas alertas
- [ ] Notificaciones push del navegador
- [ ] Filtros por tipo de notificación
- [ ] Historial completo de notificaciones
- [ ] Configuración de preferencias de notificación por usuario
- [ ] WebSocket para notificaciones instantáneas (sin polling)

## 🐛 Correcciones Incluidas

### Fotos de Perfil
- ✅ Corregida la ruta de fotos de perfil en todos los headers
- ✅ Ahora apunta correctamente a `../uploads/perfiles/`
- ✅ Fallback a `default-avatar.svg` si no hay foto
- ✅ Las fotos subidas en el perfil del repartidor aparecen en el navbar

### Visualización de Mensajes
- ✅ Corregido error "Array to string conversion" en mensajes flash
- ✅ Los mensajes de éxito/error ahora se muestran correctamente

## 📄 Licencia
Sistema desarrollado para Hermes Express © 2025
