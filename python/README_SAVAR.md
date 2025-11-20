# 🚀 Importador SAVAR - HERMES EXPRESS

Script profesional de Python con Selenium para extracción automatizada de datos desde SAVAR Express.

## 📋 Características Principales

✅ **Login Automatizado** - Inicio de sesión robusto con múltiples estrategias de fallback  
✅ **Navegación Inteligente** - Búsqueda automática del módulo "Control de Almacenes"  
✅ **Gestión de Fechas** - Configuración precisa de rangos con datepickers complejos  
✅ **Extracción por Categorías** - TOTAL, EN ALMACEN, TRANSF. POR RECEPCIONAR, etc.  
✅ **Exportación Excel** - Descarga automática de archivos .xlsx desde modales  
✅ **Manejo de Overlays** - Cierre automático de alertas, spinners y datepickers  
✅ **Screenshots Debug** - Capturas automáticas en cada paso del proceso  
✅ **Geocodificación** - Conversión de direcciones a coordenadas GPS  

## 🔧 Instalación

```bash
cd c:\xampp\htdocs\NUEVOOO\python
pip install -r requirements.txt
```

## ⚙️ Configuración

**1. Credenciales SAVAR** (línea 2906-2907):
```python
usuario = "CHI.HER"          # Tu usuario SAVAR
contrasena = "123456789"     # Tu contraseña
```

**2. Base de datos MySQL** (línea 2220-2229):
```python
connection = mysql.connector.connect(
    host='localhost',
    user='root',
    password='',
    database='hermes_express'
)
```

**3. Directorio de descargas** (automático):
```python
download_dir = './downloads'  # Se crea automáticamente
```

## 🚀 Uso Básico

### Ejecución rápida (extrae de ayer a hoy):
```bash
python savar_importer.py
```

### Personalizar fechas:
```python
# Editar líneas 2916-2920
fecha_inicio = '2025-11-19'  # Formato YYYY-MM-DD
fecha_fin = '2025-11-20'
```

## 📊 Flujo del Proceso

```
1. setup_driver() 
   └─ Configura ChromeDriver con opciones headless

2. login_and_fetch_saver()
   ├─ Navega a https://app.savarexpress.com.pe/sso/Inicio/
   ├─ Completa credenciales con reintentos
   └─ Valida sesión exitosa

3. open_control_almacenes_and_open_category()
   ├─ Busca "Control de Almacenes" en menú
   ├─ Marca checkbox "Fecha de Recepción"
   ├─ Configura rango de fechas
   ├─ Clic en "Consultar" (con 6 estrategias fallback)
   └─ Espera a que termine procesamiento

4. abrir_modal_y_extraer_datos()
   ├─ Identifica columna/categoría objetivo
   ├─ Clic en celda numérica (con retries)
   └─ Espera apertura del modal

5. click_export_excel_in_open_modal()
   ├─ Busca botón "Exportar Excel"
   ├─ Inicia descarga
   └─ Espera archivo completo (sin .crdownload)

6. Guardar en MySQL (opcional)
   └─ Inserta datos en tabla paquetes
```

## 🎯 Funciones Principales

### 1. Setup del navegador
```python
driver = setup_driver(headless=True, download_dir='./downloads')
```
- `headless=False` para ver el navegador
- `download_dir` especifica carpeta de descargas

### 2. Login en SAVAR
```python
login_and_fetch_saver(
    driver, 
    usuario="CHI.HER", 
    contrasena="123456789",
    fecha_inicio="2025-11-19",
    fecha_fin="2025-11-20",
    timeout=30
)
```

### 3. Abrir categoría específica
```python
open_control_almacenes_and_open_category(
    driver,
    fecha_inicio="2025-11-19",
    fecha_fin="2025-11-20",
    categoria="TRANSF. POR RECEPCIONAR",  # o "TOTAL", "EN ALMACEN", etc.
    timeout=20
)
```

### 4. Extraer datos del modal
```python
datos = extract_data(
    driver, 
    timeout=20,
    use_excel_export=True,  # Descarga Excel automáticamente
    download_dir='./downloads'
)
```

### 5. Exportar Excel (método directo)
```python
ruta_excel = exportar_excel_despues_de_modal(
    driver,
    download_dir='./downloads',
    timeout=30,
    button_text="Exportar Excel",
    file_pattern="*.xls*"
)
```

## 🔍 Estrategias de Extracción

### Opción A: Por texto de categoría
```python
abrir_modal_y_extraer_datos(driver, categoria="TOTAL")
```

### Opción B: Por encabezado de columna
```python
abrir_modal_y_extraer_datos(
    driver, 
    column_label="EN ALMACEN RECEPCIONAR"
)
```

### Opción C: Navegación completa
```python
# Login
login_and_fetch_saver(driver, user, pwd, fecha_ini, fecha_fin)

# Abrir modal
open_control_almacenes_and_open_category(
    driver, fecha_ini, fecha_fin, "TOTAL"
)

# Descargar Excel
ruta = exportar_excel_despues_de_modal(driver)
print(f"Excel guardado en: {ruta}")
```

## 📁 Archivos Generados

### Screenshots automáticos:
- `screenshot_login_page.png` - Página de login cargada
- `screenshot_after_login.png` - Después de autenticación
- `step_control_almacenes_loaded.png` - Módulo Control de Almacenes
- `step_dates_typed.png` - Fechas configuradas
- `step_after_consultar.png` - Después de consultar
- `step_modal_opened.png` - Modal de detalles abierto

### Archivos Excel:
- `downloads/*.xlsx` - Datos extraídos del modal

## 🛠️ Funciones de Utilidad

### Configurar fechas en inputs
```python
set_date_inputs_by_label(
    driver, 
    label_text='Fecha de Recepcion',
    start_date='2025-11-19',
    end_date='2025-11-20',
    timeout=15
)
```

### Esperar fin de procesamiento
```python
wait_until_not_processing(driver, timeout=30)
```

### Cerrar overlays/calendarios
```python
close_overlays_and_datepickers(driver)
```

### Activar/desactivar checkbox
```python
ensure_checkbox_by_label(
    driver, 
    label_text="Fecha de Recepción", 
    checked=True, 
    timeout=10
)
```

### Esperar descarga completa
```python
archivo = wait_for_download_completion(
    download_dir='./downloads',
    pattern='*.xlsx',
    timeout=90
)
```

## ⚠️ Solución de Problemas

### ❌ Error: "No se encontró campo de usuario"
**Causa**: URL incorrecta o página no cargada  
**Solución**:
```python
# Verificar URL correcta
driver.get("https://app.savarexpress.com.pe/sso/Inicio/")
time.sleep(5)  # Dar tiempo para carga completa
```

### ❌ Error: "No se pudo clicar Consultar"
**Causa**: Overlay bloqueando el botón  
**Solución**:
```python
close_overlays_and_datepickers(driver)
dismiss_error_dialog_if_any(driver)
# Reintentar clic
```

### ❌ Error: Descarga no inicia
**Causa**: Permisos de carpeta o timeout corto  
**Solución**:
```powershell
# Crear carpeta con permisos
New-Item -ItemType Directory -Force -Path "downloads"
icacls "downloads" /grant Users:F
```

### ❌ Error: Datepicker no se configura
**Causa**: Datepicker complejo (Flatpickr/Bootstrap)  
**Solución**: El script usa `_set_date_with_datepicker()` automáticamente como fallback

### ❌ Error: Modal no se abre
**Causa**: Celda incorrecta o categoría no existe  
**Solución**:
```python
# Tomar screenshot para verificar
driver.save_screenshot("debug_tabla.png")

# Probar con column_label en vez de categoria
abrir_modal_y_extraer_datos(
    driver, 
    column_label="NOMBRE_EXACTO_COLUMNA"
)
```

## 📸 Debug con Screenshots

El script guarda capturas automáticamente en cada paso. Para forzar una captura:

```python
driver.save_screenshot("mi_debug.png")
print("Screenshot guardado para análisis")
```

## 🔐 Seguridad

⚠️ **NUNCA subas credenciales a GitHub**

### Usar variables de entorno:
```python
import os

usuario = os.getenv('SAVAR_USER', 'default_user')
contrasena = os.getenv('SAVAR_PASS', 'default_pass')
```

### En Windows:
```powershell
$env:SAVAR_USER = "CHI.HER"
$env:SAVAR_PASS = "123456789"
python savar_importer.py
```

## 📅 Automatización

### Windows Task Scheduler (diario a las 6 AM):
```powershell
schtasks /create /tn "SAVAR Daily Import" `
  /tr "python C:\xampp\htdocs\NUEVOOO\python\savar_importer.py" `
  /sc daily /st 06:00 /ru SYSTEM
```

### Cron Linux/Mac:
```bash
0 6 * * * cd /ruta/python && python savar_importer.py >> logs.txt 2>&1
```

## 📦 Estructura de Datos Exportados

### JSON retornado por `extract_data()`:
```json
{
  "estado": "éxito",
  "origen": "excel_descargado",
  "ruta_excel": "./downloads/export_20251120_143055.xlsx",
  "fecha_consulta": "2025-11-20 14:30:55"
}
```

### Columnas típicas del Excel:
- Código de pedido
- Destinatario
- Dirección
- Teléfono
- Estado
- Fecha de creación
- Fecha de recepción

## 🔄 Integración con HERMES EXPRESS

El script puede insertar directamente en MySQL:

```python
def save_to_database(data: Dict[str, Any]) -> Dict[str, Any]:
    connection = mysql.connector.connect(**DB_CONFIG)
    cursor = connection.cursor()
    
    # Leer Excel y insertar
    import openpyxl
    wb = openpyxl.load_workbook(data['ruta_excel'])
    ws = wb.active
    
    for row in ws.iter_rows(min_row=2, values_only=True):
        sql = """INSERT INTO paquetes 
                 (codigo_seguimiento, destinatario_nombre, direccion_completa) 
                 VALUES (%s, %s, %s)"""
        cursor.execute(sql, (row[0], row[1], row[2]))
    
    connection.commit()
    return {"insertados": cursor.rowcount}
```

## 📞 Soporte

**Revisar logs**:
1. Screenshots en carpeta raíz
2. Mensajes de consola con `print()`
3. Archivos de error generados

**Contacto**: Revisa la documentación del sistema HERMES EXPRESS

## 🆕 Información de Versión

- **Versión**: 2.0 Professional
- **Última actualización**: 20 Noviembre 2025
- **Compatibilidad**: Python 3.8+
- **Navegador**: Chrome/Chromium
- **Sistema**: Windows/Linux/macOS

## 📄 Licencia

Uso exclusivo para HERMES EXPRESS LOGISTIC. Prohibida su distribución sin autorización.
