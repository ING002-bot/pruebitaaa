# HERMES EXPRESS LOGISTIC
# Instalación y Configuración

## Paso 1: Importar la Base de Datos

1. Abre phpMyAdmin (http://localhost/phpmyadmin)
2. Crea una nueva base de datos llamada `hermes_express`
3. Selecciona la base de datos
4. Ve a la pestaña "Importar"
5. Selecciona el archivo `database/schema.sql`
6. Haz clic en "Continuar"

## Paso 2: Configurar API de Google Maps

1. Ve a https://console.cloud.google.com/
2. Crea un nuevo proyecto o selecciona uno existente
3. Habilita las siguientes APIs:
   - Maps JavaScript API
   - Geocoding API
   - Directions API
4. Crea credenciales (API Key)
5. Copia tu API Key
6. Abre `config/config.php`
7. Reemplaza `TU_API_KEY_AQUI` con tu API Key real

```php
define('GOOGLE_MAPS_API_KEY', 'AIzaSy...');  // Tu API Key aquí
```

## Paso 3: Configurar Permisos de Carpetas

Ejecuta en la terminal (PowerShell como Administrador):

```powershell
# Crear carpetas necesarias
New-Item -Path "C:\xampp\htdocs\NUEVOOO\uploads\entregas" -ItemType Directory -Force
New-Item -Path "C:\xampp\htdocs\NUEVOOO\uploads\perfiles" -ItemType Directory -Force
New-Item -Path "C:\xampp\htdocs\NUEVOOO\assets\img" -ItemType Directory -Force

# Dar permisos (Windows)
icacls "C:\xampp\htdocs\NUEVOOO\uploads" /grant Everyone:F /T
```

## Paso 4: Configurar el Importador de SAVAR (Opcional)

1. Instala Python 3.8+ desde https://www.python.org/downloads/
2. Abre PowerShell y navega a la carpeta del proyecto:

```powershell
cd C:\xampp\htdocs\NUEVOOO\python
pip install -r requirements.txt
```

3. Descarga ChromeDriver desde https://chromedriver.chromium.org/
4. Coloca `chromedriver.exe` en la carpeta `python/`
5. Edita `python/savar_importer.py` con tus credenciales de SAVAR

## Paso 5: Crear Avatar por Defecto

Coloca una imagen llamada `default-avatar.png` en `assets/img/`

## Paso 6: Iniciar el Sistema

1. Asegúrate de que XAMPP esté corriendo (Apache y MySQL)
2. Abre tu navegador
3. Ve a: http://localhost/NUEVOOO/

## Credenciales de Acceso

**Administrador:**
- Email: admin@hermesexpress.com
- Password: password123

**Asistente:**
- Email: asistente@hermesexpress.com
- Password: password123

**Repartidor:**
- Email: carlos.r@hermesexpress.com
- Password: password123

**⚠️ IMPORTANTE:** Cambia estas contraseñas inmediatamente después del primer acceso.

## Configuración de Tarifas

Edita `config/config.php` para ajustar las tarifas:

```php
define('TARIFA_POR_PAQUETE', 3.50);  // Tarifa normal
define('TARIFA_URGENTE', 5.00);      // Tarifa urgente
define('TARIFA_EXPRESS', 7.50);      // Tarifa express
```

## Solución de Problemas

### Error: "Call to undefined function password_hash()"
- Actualiza PHP a versión 7.4 o superior

### Error: "Class 'PDO' not found"
- Habilita la extensión PDO en php.ini
- Quita el punto y coma (;) antes de `extension=pdo_mysql`

### Error al subir imágenes
- Verifica permisos de la carpeta `uploads/`
- Aumenta `upload_max_filesize` en php.ini

### Google Maps no carga
- Verifica tu API Key
- Asegúrate de haber habilitado las APIs necesarias
- Revisa la consola del navegador para errores

### El importador de Python no funciona
- Verifica que ChromeDriver sea compatible con tu versión de Chrome
- Ajusta los selectores CSS según el HTML real de SAVAR
- Verifica las credenciales de SAVAR

## Automatizar Importación de SAVAR

**Windows (Task Scheduler):**

```powershell
schtasks /create /sc daily /tn "SAVAR Import" /tr "python C:\xampp\htdocs\NUEVOOO\python\savar_importer.py" /st 06:00
```

Esto ejecutará la importación automáticamente todos los días a las 6:00 AM.

## Contacto y Soporte

Sistema desarrollado para HERMES EXPRESS LOGISTIC
© 2025 - Todos los derechos reservados
