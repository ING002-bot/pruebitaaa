# 🛡️ Sistema de Validaciones Universal

Sistema completo de validación de entrada para todos los formularios del proyecto HERMES EXPRESS.

## 📋 Características Principales

### ✅ Validaciones Automáticas
- **Campos de texto**: Solo letras, espacios y acentos
- **Campos numéricos**: Solo números enteros o decimales
- **Teléfonos**: Números, espacios, guiones, paréntesis y +
- **Emails**: Formato de email válido
- **Códigos**: Solo alfanuméricos con guiones
- **Direcciones**: Texto amplio con números y símbolos básicos

### 🎯 Tipos de Validación

| Tipo | Patrón | Uso |
|------|--------|-----|
| `SOLO_TEXTO` | `[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+` | Nombres, apellidos |
| `SOLO_NUMEROS` | `[0-9]+` | DNI, códigos numéricos |
| `DECIMALES` | `[0-9]+\.?[0-9]*` | Precios, montos |
| `TELEFONO` | `[\+]?[0-9\s\-\(\)]+` | Números telefónicos |
| `EMAIL` | Email válido | Correos electrónicos |
| `ALFANUMERICO` | `[a-zA-Z0-9\-_]+` | Códigos de seguimiento |
| `DIRECCION` | Texto amplio | Direcciones, observaciones |

## 📁 Archivos del Sistema

```
assets/
├── js/
│   └── validaciones.js      # Lógica principal de validación
└── css/
    └── validaciones.css     # Estilos visuales para validaciones
```

## 🔧 Implementación Automática

### Campos Reconocidos por Nombre
```javascript
// Campos de solo texto
'nombre', 'apellido', 'destinatario_nombre', 'receptor_nombre'
'edit_nombre', 'edit_apellido', 'nombre_zona', 'tipo_envio'

// Campos numéricos  
'telefono', 'destinatario_telefono', 'edit_telefono'
'tarifa_repartidor', 'edit_tarifa_repartidor', 'costo_envio'
'monto', 'gastoMonto', 'receptor_dni'

// Campos de email
'email', 'destinatario_email', 'edit_email'

// Campos alfanuméricos
'codigo_seguimiento', 'codigo_savar'

// Direcciones y conceptos
'destinatario_direccion', 'direccion', 'concepto'
'observaciones', 'buscar', 'buscarPaquete'
```

## 🚀 Funcionalidades

### 1. Filtrado en Tiempo Real
- Previene la entrada de caracteres no válidos
- Muestra tooltips informativos temporales
- Permite teclas especiales (Backspace, Delete, flechas, etc.)

### 2. Validación al Salir del Campo
- Verifica el formato completo del campo
- Muestra mensajes de error persistentes
- Aplica estilos visuales de error

### 3. Validación al Enviar Formulario
- Valida todos los campos antes del envío
- Previene el envío si hay errores
- Hace scroll al primer campo con error

### 4. Retroalimentación Visual
- Bordes rojos para campos inválidos
- Bordes amarillos para campos requeridos
- Mensajes de error contextuales
- Tooltips temporales informativos

## 🎨 Estilos CSS Aplicados

```css
/* Campos con error */
.is-invalid {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

/* Campos requeridos */
.form-control[required] {
    border-left: 3px solid #ffc107;
}

/* Tooltips temporales */
.validation-tooltip {
    background: #dc3545;
    color: white;
    animation: fadeInOut 2s ease-in-out;
}
```

## 📱 Módulos Incluidos

### ✅ Admin
- Gestión de usuarios
- Configuración de tarifas  
- Edición de paquetes
- Gestión de rutas

### ✅ Asistente
- Gestión de usuarios
- Creación de paquetes
- Caja chica
- Perfil personal

### ✅ Repartidor
- Perfil personal
- Entrega de paquetes
- Búsqueda de paquetes

### ✅ Login
- Validación de email
- Campos de autenticación

## 🛠️ Uso Manual

### Agregar Validación a Nuevo Campo

1. **Por nombre del campo**:
```javascript
// En validaciones.js, agregar a CAMPOS_VALIDACION:
'mi_nuevo_campo': 'SOLO_TEXTO'
```

2. **Por atributos HTML**:
```html
<input type="text" name="nombre" 
       pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+" 
       title="Solo se permiten letras y espacios">
```

### Re-inicializar Validaciones
```javascript
// Después de cargar contenido dinámico
window.reinicializarValidaciones();
```

## 🔍 Mensajes de Error

| Tipo | Mensaje |
|------|---------|
| Texto | "Solo se permiten letras y espacios" |
| Números | "Solo se permiten números enteros" |
| Decimales | "Solo se permiten números decimales" |
| Teléfono | "Formato de teléfono inválido" |
| Email | "Formato de email inválido" |
| Alfanumérico | "Solo se permiten letras, números y guiones" |

## 📋 Checklist de Implementación

- [x] Script JavaScript de validaciones
- [x] Estilos CSS para retroalimentación visual
- [x] Inclusión en headers de admin, asistente y repartidor
- [x] Validaciones en página de login
- [x] Patrones HTML en campos principales
- [x] Tooltips informativos temporales
- [x] Validación completa de formularios
- [x] Filtrado en tiempo real
- [x] Soporte para campos dinámicos

## 🚨 Notas Importantes

1. **Compatibilidad**: Funciona en todos los navegadores modernos
2. **Performance**: Validaciones optimizadas, no afectan la velocidad
3. **Accesibilidad**: Mensajes descriptivos para screen readers
4. **Responsivo**: Tooltips adaptados para dispositivos móviles
5. **Mantenimiento**: Sistema centralizado, fácil de actualizar

## 🔄 Actualización Automática

El sistema se inicializa automáticamente cuando:
- Se carga el DOM
- Se llama a `window.reinicializarValidaciones()`
- Se cargan nuevos elementos dinámicamente

No requiere configuración adicional para funcionar en formularios existentes.

---

**Desarrollado para HERMES EXPRESS LOGISTIC** 📦  
*Sistema de Gestión de Paquetería - 2025*