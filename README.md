# HERMES EXPRESS LOGISTIC
## Sistema de Gestión de Paquetería

Sistema completo para la gestión de entregas de paquetería, diseñado para empresas courier que trabajan con distribuidores como SAVAR y TEMU.

## 🚀 Características Principales

### Para Administradores
- Dashboard completo con gráficos y estadísticas
- Gestión completa de paquetes
- Asignación de rutas a repartidores
- Control de ingresos y gastos
- Gestión de usuarios
- Procesamiento de pagos a repartidores
- Reportes detallados
- Importación automática desde SAVAR (Selenium)

### Para Asistentes
- Dashboard con estadísticas limitadas
- Gestión de paquetes
- Asignación de rutas
- Seguimiento de entregas
- No puede: crear usuarios, ver ingresos totales, procesar pagos

### Para Repartidores
- Dashboard personal con sus métricas
- Visualización de rutas asignadas
- Mapa en tiempo real con Google Maps
- Registro de entregas con:
  - Captura de fotos (cámara o galería)
  - Geolocalización automática
  - Firma digital
  - Datos del receptor
- Historial de entregas
- Visualización de ingresos personales
- Gestión de paquetes rezagados

## 📋 Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Apache/Nginx
- Python 3.8+ (para importación SAVAR)
- Extensiones PHP: PDO, mysqli, GD, fileinfo
- Google Maps API Key

## 🛠️ Instalación

### 1. Configurar Base de Datos

```bash
# Importar el esquema SQL
mysql -u root -p < database/schema.sql
```

### 2. Configurar PHP

Edita `config/database.php` y `config/config.php` con tus credenciales:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'hermes_express');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
```

### 3. Configurar Google Maps

Obtén una API Key de Google Maps en: https://console.cloud.google.com/

Edita `config/config.php`:
```php
define('GOOGLE_MAPS_API_KEY', 'TU_API_KEY_AQUI');
```

### 4. Configurar Importador de SAVAR (Opcional)

```bash
cd python
pip install -r requirements.txt
```

Edita `python/savar_importer.py` con tus credenciales de SAVAR.

### 5. Permisos de Carpetas

```bash
chmod 777 uploads/
chmod 777 uploads/entregas/
chmod 777 uploads/perfiles/
```

## 👥 Acceso al Sistema

### Credenciales por Defecto

**Administrador:**
- Email: admin@hermesexpress.com
- Password: password123

**Asistente:**
- Email: asistente@hermesexpress.com
- Password: password123

**Repartidor:**
- Email: carlos.r@hermesexpress.com
- Password: password123

**⚠️ IMPORTANTE:** Cambia estas contraseñas después del primer inicio de sesión.

## 📱 Diseño Responsive

Todo el sistema está optimizado para dispositivos móviles. Los repartidores pueden usar el sistema completamente desde sus celulares para:
- Ver sus rutas
- Registrar entregas con fotos
- Ver mapas en tiempo real
- Marcar ubicaciones

## 🔧 Tecnologías Utilizadas

- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5
- **Backend:** PHP 8
- **Base de Datos:** MySQL 8
- **Mapas:** Google Maps JavaScript API
- **Gráficos:** Chart.js
- **Iconos:** Bootstrap Icons
- **Automatización:** Python + Selenium

## 📊 Estructura del Proyecto

```
NUEVOOO/
├── admin/              # Panel de administrador
├── asistente/          # Panel de asistente
├── repartidor/         # Panel de repartidor
├── api/                # APIs REST
├── assets/             # CSS, JS, imágenes
├── auth/               # Autenticación
├── config/             # Configuración
├── database/           # Esquemas SQL
├── python/             # Scripts Python
└── uploads/            # Archivos subidos
```

## 🔐 Seguridad

- Contraseñas encriptadas con password_hash()
- Protección CSRF
- Validación de sesiones
- Sanitización de datos
- Prepared statements (PDO)
- Logs de actividad

## 📝 Uso del Importador SAVAR

```bash
cd python
python savar_importer.py
```

El script:
1. Inicia sesión en SAVAR
2. Extrae paquetes pendientes
3. Geocodifica direcciones
4. Importa a la base de datos

## 🤝 Soporte

Para soporte o consultas sobre el sistema HERMES EXPRESS LOGISTIC, contacta al equipo de desarrollo.

## 📄 Licencia

Sistema propietario - HERMES EXPRESS LOGISTIC © 2025

---

**Desarrollado con ❤️ para optimizar la logística de entregas**
