# Instalación de PhpSpreadsheet con Composer

## 📦 ¿Qué es PhpSpreadsheet?

PhpSpreadsheet es una librería de PHP que permite leer y escribir archivos de Excel (.xlsx, .xls). Es necesaria para el sistema de importación masiva de paquetes desde archivos Excel.

## 🔧 Requisitos Previos

- PHP 7.4 o superior (XAMPP ya lo incluye)
- Composer (gestor de paquetes para PHP)

## 📥 Paso 1: Instalar Composer

### En Windows (XAMPP)

#### Opción A: Descarga automática

1. Descarga el instalador desde: https://getcomposer.org/download/
2. Ejecuta `Composer-Setup.exe`
3. En la ventana de selección de PHP, elige: `C:\xampp\php\php.exe`
4. Continúa con la instalación (siguiente, siguiente, instalar)
5. Una vez instalado, abre PowerShell o CMD y verifica:

```powershell
composer --version
```

Deberías ver algo como:
```
Composer version 2.6.5 2023-10-06
```

#### Opción B: Instalación manual

Si el instalador no funciona, instala manualmente:

```powershell
# Descargar Composer
cd C:\xampp\htdocs\pruebitaaa
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"

# Instalar
php composer-setup.php

# Limpiar
php -r "unlink('composer-setup.php');"

# Ahora puedes usar Composer con:
php composer.phar --version
```

### En Linux/Ubuntu

```bash
# Actualizar sistema
sudo apt update

# Instalar dependencias
sudo apt install php-cli php-zip unzip curl

# Descargar e instalar Composer
cd ~
curl -sS https://getcomposer.org/installer -o composer-setup.php
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer

# Verificar instalación
composer --version
```

## 📚 Paso 2: Instalar PhpSpreadsheet

### Navega a la carpeta del proyecto

**Windows (PowerShell):**
```powershell
cd C:\xampp\htdocs\pruebitaaa
```

**Linux:**
```bash
cd /var/www/html/pruebitaaa
```

### Instalar PhpSpreadsheet

**Si tienes Composer global:**
```bash
composer require phpoffice/phpspreadsheet
```

**Si usas composer.phar (manual):**
```bash
php composer.phar require phpoffice/phpspreadsheet
```

Este comando:
- Creará un archivo `composer.json`
- Creará un archivo `composer.lock`
- Creará una carpeta `vendor/` con todas las librerías
- Descargará PhpSpreadsheet y sus dependencias

### Salida esperada

Verás algo como:

```
Using version ^1.29 for phpoffice/phpspreadsheet
./composer.json has been created
Running composer update phpoffice/phpspreadsheet
Loading composer repositories with package information
Updating dependencies
Lock file operations: 11 installs, 0 updates, 0 removals
  - Locking maennchen/zipstream-php (2.4.0)
  - Locking markbaker/complex (3.0.2)
  - Locking markbaker/matrix (3.0.1)
  - Locking phpoffice/phpspreadsheet (1.29.0)
  ...
Writing lock file
Installing dependencies from lock file
Package operations: 11 installs, 0 updates, 0 removals
  - Installing psr/http-client (1.0.3): Extracting archive
  - Installing psr/http-factory (1.0.2): Extracting archive
  ...
Generating autoload files
11 packages you are using are looking for funding.
Use the `composer fund` command to find out more!
```

## ✅ Paso 3: Verificar Instalación

### Verificar estructura de archivos

Después de la instalación, deberías tener:

```
C:\xampp\htdocs\pruebitaaa\
├── vendor/               ← Nueva carpeta con librerías
│   ├── autoload.php     ← Archivo principal de carga automática
│   ├── phpoffice/
│   │   └── phpspreadsheet/
│   ├── composer/
│   └── ...
├── composer.json         ← Archivo de configuración
├── composer.lock         ← Archivo de versiones bloqueadas
└── ... (resto de archivos)
```

### Probar PhpSpreadsheet

Crea un archivo de prueba:

**test_phpspreadsheet.php:**
```php
<?php
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Crear una hoja de cálculo simple
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', '¡PhpSpreadsheet funciona!');
$sheet->setCellValue('A2', 'Fecha de prueba:');
$sheet->setCellValue('B2', date('Y-m-d H:i:s'));

// Guardar archivo
$writer = new Xlsx($spreadsheet);
$writer->save('test_excel.xlsx');

echo "✅ PhpSpreadsheet instalado correctamente!\n";
echo "Se creó el archivo test_excel.xlsx\n";
```

**Ejecutar la prueba:**

```powershell
cd C:\xampp\htdocs\pruebitaaa
php test_phpspreadsheet.php
```

Si ves `✅ PhpSpreadsheet instalado correctamente!`, todo está bien.

## 🚀 Paso 4: Actualizar Código (Ya Hecho)

El archivo `admin/importar_excel_procesar.php` ya está configurado para usar PhpSpreadsheet:

```php
<?php
require_once '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
// ... resto del código
```

## 🔐 Paso 5: Configurar Permisos (Solo Linux)

Si estás en un servidor Linux, asegúrate de que Apache pueda escribir en la carpeta de uploads:

```bash
# Crear carpeta de uploads si no existe
mkdir -p /var/www/html/pruebitaaa/uploads/excel

# Dar permisos
sudo chown -R www-data:www-data /var/www/html/pruebitaaa/uploads
sudo chmod -R 755 /var/www/html/pruebitaaa/uploads

# Verificar permisos
ls -la /var/www/html/pruebitaaa/uploads
```

## 📝 Archivos Creados por Composer

### composer.json

Este archivo lista las dependencias del proyecto:

```json
{
    "require": {
        "phpoffice/phpspreadsheet": "^1.29"
    }
}
```

### composer.lock

Bloquea las versiones exactas de cada paquete instalado para garantizar consistencia entre diferentes instalaciones.

### vendor/autoload.php

Archivo que carga automáticamente todas las clases de las librerías instaladas. Solo necesitas incluirlo una vez:

```php
require_once 'vendor/autoload.php';
```

## 🛡️ Seguridad: .gitignore

Si usas Git, agrega esto a tu `.gitignore` para no subir la carpeta vendor:

```
vendor/
composer.lock
```

Esto permite que cada instalación descargue sus propias dependencias ejecutando:

```bash
composer install
```

## 🆘 Solución de Problemas

### Error: "composer: command not found"

**Windows:**
- Cierra y vuelve a abrir PowerShell/CMD
- Verifica la variable PATH: `echo $env:Path` debería incluir la ruta de Composer
- Usa la ruta completa: `C:\ProgramData\ComposerSetup\bin\composer.bat`

**Linux:**
- Instala Composer siguiendo la sección de instalación de Linux
- Verifica: `which composer`

### Error: "php: command not found"

**Windows:**
- Agrega PHP al PATH del sistema:
  1. Panel de Control → Sistema → Configuración avanzada
  2. Variables de entorno
  3. Variable PATH → Agregar: `C:\xampp\php`
- O usa la ruta completa: `C:\xampp\php\php.exe`

**Linux:**
```bash
sudo apt install php-cli
```

### Error: "Your requirements could not be resolved"

Verifica la versión de PHP:

```bash
php -v
```

PhpSpreadsheet requiere PHP 7.4+. Si tienes una versión anterior, actualiza PHP en XAMPP.

### Error: "failed to open stream: HTTP request failed"

Problemas de conexión a Internet o firewall. Prueba:

```bash
# Desactivar SSL temporalmente
composer config -g -- disable-tls true
composer require phpoffice/phpspreadsheet
composer config -g -- disable-tls false
```

### Error de permisos en Windows

Ejecuta PowerShell o CMD como Administrador:
1. Click derecho en PowerShell
2. "Ejecutar como administrador"
3. Vuelve a ejecutar el comando de instalación

### Carpeta vendor ya existe pero no funciona

Elimina y reinstala:

```powershell
# Windows
Remove-Item -Recurse -Force vendor
Remove-Item composer.lock
composer install

# Linux
rm -rf vendor composer.lock
composer install
```

## 🎯 Resumen de Comandos Rápidos

**Instalación completa desde cero (Windows):**

```powershell
# 1. Navegar al proyecto
cd C:\xampp\htdocs\pruebitaaa

# 2. Instalar PhpSpreadsheet
composer require phpoffice/phpspreadsheet

# 3. Verificar
dir vendor
php test_phpspreadsheet.php
```

**Instalación completa desde cero (Linux):**

```bash
# 1. Navegar al proyecto
cd /var/www/html/pruebitaaa

# 2. Instalar Composer si no lo tienes
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 3. Instalar PhpSpreadsheet
composer require phpoffice/phpspreadsheet

# 4. Permisos
sudo chown -R www-data:www-data vendor
```

## ✅ Verificación Final

Después de instalar, verifica que estos archivos existan:

```
✓ vendor/autoload.php
✓ vendor/phpoffice/phpspreadsheet/
✓ composer.json
✓ composer.lock
```

Ahora puedes usar la funcionalidad de importación de Excel desde el panel de administración:

**Admin → Sistema → Importar Excel**

## 🔄 Actualizar PhpSpreadsheet

Para actualizar a la última versión en el futuro:

```bash
composer update phpoffice/phpspreadsheet
```

## 📚 Documentación Adicional

- **PhpSpreadsheet:** https://phpspreadsheet.readthedocs.io/
- **Composer:** https://getcomposer.org/doc/
- **Packagist (repositorio):** https://packagist.org/packages/phpoffice/phpspreadsheet
