# 🚀 GUÍA DE IMPLEMENTACIÓN - Sistema de Importación Excel + WhatsApp

## ⚡ INSTALACIÓN RÁPIDA (30 minutos)

### Paso 1: Actualizar Base de Datos ✅

```sql
-- Abrir phpMyAdmin → Base de datos hermes_express → pestaña SQL
-- Copiar y ejecutar el contenido completo de:
```

**Archivo:** `database/add_importacion_notificaciones.sql`

Esto creará:
- ✅ Tabla `importaciones_archivos` 
- ✅ Tabla `notificaciones_whatsapp`
- ✅ Tabla `alertas_entrega`
- ✅ 4 nuevos campos en tabla `paquetes`

---

### Paso 2: Instalar PhpSpreadsheet ✅

**Descargar Composer:**
https://getcomposer.org/download/

**Instalar librería:**
```powershell
cd C:\xampp\htdocs\pruebitaaa
composer require phpoffice/phpspreadsheet
```

**Verificar:** Debe existir la carpeta `vendor/`

---

### Paso 3: Crear Carpeta de Uploads ✅

```powershell
cd C:\xampp\htdocs\pruebitaaa
New-Item -ItemType Directory -Path "uploads\excel" -Force
icacls "uploads" /grant Everyone:(OI)(CI)F
```

---

### Paso 4: Probar Importación ✅

1. **Crear Excel de prueba:**

| A | B | C | D | E | F |
|---|---|---|---|---|---|
| TEST-001 | Juan Pérez | 70123456 | Av. Test #123 | Centro | Prueba |

2. **Guardar como:** `prueba.xlsx`

3. **Ir a:** Admin → Sistema → Importar Excel

4. **Subir y procesar el archivo**

---

## 📚 DOCUMENTACIÓN COMPLETA

- **`SISTEMA_IMPORTACION_WHATSAPP.md`** - Documentación técnica completa
- **`FORMATO_EXCEL_IMPORTACION.md`** - Formato del archivo Excel
- **`INSTALAR_PHPSPREADSHEET.md`** - Instalación detallada de Composer
- **`cron/CONFIGURAR_CRON.md`** - Configurar alertas automáticas

---

## ✅ CHECKLIST

### Obligatorio
- [ ] SQL ejecutado ✅
- [ ] PhpSpreadsheet instalado ✅
- [ ] Carpeta uploads creada ✅
- [ ] Importación de prueba exitosa ✅

### Opcional (después)
- [ ] Configurar WhatsApp API
- [ ] Configurar cron job para alertas

---

## 🎯 USO DIARIO

1. **Importar paquetes:** Admin → Sistema → Importar Excel
2. **Asignar repartidor:** Se establece automáticamente fecha límite de 2 días
3. **Alertas automáticas:** El sistema envía notificación 24h antes del vencimiento

---

**Tiempo de instalación:** ~30 minutos  
**Documentación completa:** Ver archivos .md en la raíz del proyecto
