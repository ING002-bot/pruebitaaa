# HERMES EXPRESS - Script de Python para SAVAR

## Instalación

1. Asegúrate de tener Python 3.8+ instalado
2. Instala las dependencias:

```bash
pip install -r requirements.txt
```

3. Descarga ChromeDriver compatible con tu versión de Chrome:
   https://chromedriver.chromium.org/

## Configuración

Edita el archivo `savar_importer.py` y configura:

- `SAVAR_URL`: URL del sistema SAVAR
- `SAVAR_USERNAME`: Tu usuario de SAVAR
- `SAVAR_PASSWORD`: Tu contraseña de SAVAR
- `DB_CONFIG`: Configuración de la base de datos MySQL

**IMPORTANTE**: Debes ajustar los selectores CSS/XPath según la estructura HTML real del sistema SAVAR.

## Uso

Ejecuta el script:

```bash
python savar_importer.py
```

El script:
1. Iniciará sesión en SAVAR
2. Extraerá los paquetes pendientes
3. Geocodificará las direcciones
4. Guardará todo en la base de datos de HERMES EXPRESS

## Automatización

Para ejecutar automáticamente cada día, puedes usar:

**Windows (Task Scheduler):**
```bash
schtasks /create /sc daily /tn "SAVAR Import" /tr "python C:\xampp\htdocs\NUEVOOO\python\savar_importer.py" /st 06:00
```

**Linux (Cron):**
```bash
0 6 * * * cd /var/www/html/NUEVOOO/python && python3 savar_importer.py
```

## Personalización

Ajusta los selectores en la función `extract_packages()` según tu sistema SAVAR:

```python
# Ejemplo de selectores comunes:
# Por ID: (By.ID, "elemento-id")
# Por clase: (By.CLASS_NAME, "nombre-clase")
# Por CSS: (By.CSS_SELECTOR, ".clase #id")
# Por XPath: (By.XPATH, "//div[@class='ejemplo']")
```
