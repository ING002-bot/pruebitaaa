# 🔧 PROBLEMAS COMUNES Y SOLUCIONES - HERMES EXPRESS

## 📋 ÍNDICE DE PROBLEMAS RESUELTOS

1. [Error: vendor/autoload.php no encontrado](#error-vendorautoloadphp)
2. [Error: default.png 404 Not Found](#error-defaultpng-404)
3. [Por qué ocurren al borrar la BD](#por-qué-ocurren)
4. [Script de mantenimiento](#script-de-mantenimiento)

---

## ❌ PROBLEMA 1: vendor/autoload.php

### Error:
```
Warning: require_once(../vendor/autoload.php): Failed to open stream: 
No such file or directory in importar_excel_procesar.php on line 3
```

### 🔍 POR QUÉ OCURRE:

**PhpSpreadsheet no está instalado**. Este paquete es necesario para leer archivos Excel (.xlsx, .xls).

Cuando borras la base de datos, NO afecta las dependencias de PHP, pero si borras la carpeta `vendor/` o clonas el proyecto sin ella, este error aparece.

### ✅ SOLUCIÓN APLICADA:

1. **Verificación automática** - El código ahora verifica si existe antes de requerirlo:
   ```php
   if (!file_exists('../vendor/autoload.php')) {
       setFlashMessage('danger', 'PhpSpreadsheet no está instalado. Por favor, ejecuta: composer install');
       header('Location: importar_excel.php');
       exit;
   }
   ```

2. **Instalar PhpSpreadsheet** (si quieres usar importación de Excel):
   ```bash
   # Opción 1: Con Composer (recomendado)
   composer install
   
   # Opción 2: Manualmente
   composer require phpoffice/phpspreadsheet
   ```

3. **Sin Composer** - Si no tienes Composer, el sistema ahora muestra un mensaje claro en lugar de fallar.

### 📌 PREVENCIÓN:

- **NO borrar** la carpeta `vendor/` al hacer mantenimiento
- Si clonas el proyecto, ejecuta `composer install` primero
- La carpeta `vendor/` debe estar en `.gitignore` pero las dependencias se instalan con `composer install`

---

## ❌ PROBLEMA 2: default.png 404 Not Found

### Error:
```
GET http://localhost/pruebitaaa/uploads/perfiles/default.png 404 (Not Found)
```

### 🔍 POR QUÉ OCURRE:

**Múltiples razones:**

1. **Archivos físicos no existen** - Al clonar/instalar, las carpetas de uploads están vacías
2. **Ruta incorrecta en BD** - La base de datos referencia `default-avatar.svg` pero el archivo se llama `default.png`
3. **Carpeta no creada** - El directorio `uploads/perfiles/` no existe

**IMPORTANTE:** Al borrar y recrear la base de datos, los registros se reinsertan con valores por defecto, pero los **archivos físicos no se recrean automáticamente**.

### ✅ SOLUCIÓN APLICADA:

1. **Script automático creado**: `crear_imagenes_default.php`
   - Crea todos los directorios necesarios
   - Genera `default.png` con PHP GD
   - Genera `default-avatar.svg` para compatibilidad

2. **Ejecutar el script**:
   ```bash
   php crear_imagenes_default.php
   ```

3. **Base de datos actualizada**:
   - Cambio en `install_complete.sql`: `default-avatar.svg` → `default.png`
   - Usuarios existentes actualizados con UPDATE

4. **Directorios creados**:
   ```
   uploads/
   ├── perfiles/
   │   ├── default.png ✅
   │   └── default-avatar.svg ✅
   ├── usuarios/
   ├── entregas/
   ├── gastos/
   └── caja_chica/
   ```

### 📌 PREVENCIÓN:

- **Ejecutar `crear_imagenes_default.php`** después de cada instalación limpia
- **NO borrar** la carpeta `uploads/` al hacer mantenimiento de BD
- La carpeta `uploads/` debe tener archivos base (default.png) versionados o generados automáticamente

---

## 🤔 POR QUÉ ESTOS ERRORES OCURREN AL BORRAR LA BD

### Concepto Importante:

**Base de Datos ≠ Archivos Físicos**

Cuando ejecutas:
```sql
DROP DATABASE hermes_express;
```

**LO QUE SE BORRA:**
- ✅ Tablas y estructura
- ✅ Datos (usuarios, paquetes, etc.)
- ✅ Configuraciones en BD

**LO QUE NO SE BORRA:**
- ❌ Archivos en `uploads/`
- ❌ Archivos en `vendor/`
- ❌ Archivos PHP del sistema
- ❌ Configuración de rutas

### El Problema:

1. **La BD se recrea** con valores por defecto
2. **Los usuarios en BD** tienen `foto_perfil = 'default.png'`
3. **PERO** el archivo físico `uploads/perfiles/default.png` no existe
4. **Resultado:** Error 404

### Analogía:

Es como tener un **catálogo de libros** (BD) que dice:
- "Libro A está en estante 3, fila 2"
- "Libro B está en estante 5, fila 1"

Si **borras el catálogo** (DROP DATABASE) y lo recreas:
- El nuevo catálogo dice dónde deberían estar los libros
- Pero los **libros físicos** no reaparecen mágicamente en los estantes
- Necesitas **volver a colocar los libros** (crear archivos)

---

## 🛠️ SCRIPT DE MANTENIMIENTO AUTOMÁTICO

### Crear: `mantenimiento.php`

```php
<?php
/**
 * Script de mantenimiento post-instalación
 * Ejecutar después de reinstalar la base de datos
 */

echo "🔧 HERMES EXPRESS - Mantenimiento Post-Instalación\n";
echo str_repeat("=", 60) . "\n\n";

// 1. Crear directorios
$dirs = [
    'uploads/perfiles',
    'uploads/usuarios', 
    'uploads/entregas',
    'uploads/gastos',
    'uploads/caja_chica',
    'backups',
    'logs'
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
        echo "✓ Creado: $dir\n";
    } else {
        echo "✓ Existe: $dir\n";
    }
}

// 2. Crear imágenes default
include 'crear_imagenes_default.php';

// 3. Verificar vendor
if (!file_exists('vendor/autoload.php')) {
    echo "\n⚠️  ADVERTENCIA: vendor/autoload.php no encontrado\n";
    echo "   Para usar importación de Excel, ejecuta: composer install\n";
}

echo "\n✅ Mantenimiento completado\n";
?>
```

### Uso:

```bash
# Después de reinstalar la BD
php mantenimiento.php
```

---

## 📝 CHECKLIST POST-INSTALACIÓN

Cada vez que reinstales la base de datos, ejecuta:

- [ ] `Get-Content database\install_complete.sql | mysql -u root`
- [ ] `php crear_imagenes_default.php`
- [ ] `php verificar_sistema.php`
- [ ] Verificar que `uploads/perfiles/default.png` existe
- [ ] Si usas Excel: verificar `vendor/autoload.php`

---

## 🔄 PROCESO CORRECTO DE REINSTALACIÓN

### PASO 1: Borrar BD
```bash
mysql -u root -e "DROP DATABASE IF EXISTS hermes_express;"
```

### PASO 2: Reinstalar BD
```bash
Get-Content database\install_complete.sql | mysql -u root
```

### PASO 3: Crear archivos base
```bash
php crear_imagenes_default.php
```

### PASO 4: Verificar
```bash
php verificar_sistema.php
```

---

## 🎯 RESUMEN DE CAUSAS RAÍZ

| Error | Causa | Solución |
|-------|-------|----------|
| **vendor/autoload.php** | Dependencias no instaladas | `composer install` o verificación en código |
| **default.png 404** | Archivo físico no existe | `crear_imagenes_default.php` |
| **Ruta incorrecta** | BD usa ruta diferente a archivo real | Actualizar BD o renombrar archivo |

---

## 💡 MEJORA IMPLEMENTADA

### Antes:
- ❌ Error fatal si falta vendor
- ❌ Error 404 continuo por imagen faltante
- ❌ Confusión sobre qué borrar y qué no

### Después:
- ✅ Verificación de vendor con mensaje claro
- ✅ Script automático para crear imágenes
- ✅ Documentación clara del problema
- ✅ Proceso de mantenimiento definido

---

## 🚀 PARA DESARROLLADORES

### Agregar al .gitignore:
```
vendor/
uploads/*
!uploads/.gitkeep
!uploads/perfiles/default.png
```

### Agregar al README:
```
## Instalación

1. Clonar repositorio
2. `composer install`
3. Importar BD: `mysql -u root < database/install_complete.sql`
4. Crear archivos: `php crear_imagenes_default.php`
5. Verificar: `php verificar_sistema.php`
```

---

**✅ Todos los problemas han sido resueltos y documentados.**
