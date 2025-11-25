# 🤖 Chatbot IA - Guía de Prueba

## 🚀 Cómo Acceder

```
http://localhost/pruebitaaa/admin/chatbot.php
```

**Requisitos:**
- ✅ Estar logueado como admin
- ✅ Navegador moderno (Chrome, Firefox, Edge, Safari)

---

## 💬 Características

### 1️⃣ **Entrada por Texto**
- Escribe preguntas en el campo de entrada
- Ejemplos: "Cuántos paquetes hay", "Ingresos totales", "Resumen"

### 2️⃣ **Entrada por Voz**
- Haz clic en el botón 🎤 (micrófono)
- El chatbot escuchará tu pregunta
- Soporta español

### 3️⃣ **Salida por Voz**
- Haz clic en el botón 🔊 (sonido) para activar
- El chatbot responderá hablando
- Desactiva cuando no lo necesites

### 4️⃣ **Queries a Base de Datos**
- El chatbot accede automáticamente a la BD
- Procesa preguntas sobre: paquetes, clientes, repartidores, ingresos, reportes

---

## 📋 Comandos Disponibles

### PAQUETES
```
- "Cuántos paquetes hay"
- "Paquetes pendientes"
- "Paquetes entregados"
- "Paquetes hoy"
- "Paquetes de [nombre repartidor]"
```

### CLIENTES
```
- "Cuántos clientes hay"
- "Clientes activos"
- "Clientes en [ciudad]"
```

### REPARTIDORES
```
- "Cuántos repartidores hay"
- "Repartidores activos"
```

### INGRESOS
```
- "Ingresos totales"
- "Ingresos hoy"
- "Ingresos del mes"
```

### REPORTES
```
- "Resumen" o "Reporte general"
- "Problemas de entrega"
```

---

## 🎯 Ejemplo de Flujo

### Escenario 1: Pregunta por Texto
1. Escribe: "Cuántos paquetes hay"
2. Presiona Enter o click en ➤
3. Chatbot responde: "📦 Total de paquetes: **45**"

### Escenario 2: Pregunta por Voz + Respuesta en Voz
1. Haz click en 🔊 (sonido) para activar
2. Haz click en 🎤 (micrófono)
3. Di: "¿Cuántos ingresos hay hoy?"
4. Chatbot procesa y responde en voz: "Ingresos de hoy: dieciséis soles con cincuenta centavos"

### Escenario 3: Usar Preguntas Rápidas
1. En la sección "Prueba estos comandos", click en "Paquetes totales"
2. La pregunta se autocompleta
3. Chatbot responde automáticamente

---

## 🎚️ Controles

| Botón | Función |
|-------|---------|
| 🎤 Micrófono | Activar reconocimiento de voz |
| 🔊 Sonido | Activar/desactivar respuestas en voz |
| ➤ Enviar | Enviar pregunta (Enter también funciona) |

---

## ⚙️ Cómo Funciona

```
FLUJO DEL CHATBOT:
1. Usuario hace pregunta (texto o voz)
2. Si es por voz: Convierte a texto
3. API procesa la pregunta
4. Busca coincidencia de patrones
5. Ejecuta query a BD
6. Retorna respuesta
7. Muestra en chat
8. Si sonido está activo: Habla la respuesta
```

---

## 🔧 Arquitectura

### Backend (`api_chatbot.php`)
```php
class ChatbotIA {
    - Reconoce patrones de preguntas
    - Ejecuta queries a BD
    - Retorna respuestas en JSON
}
```

### Frontend (`chatbot.php`)
```javascript
- Web Speech API para voz
- Fetch API para conectar con backend
- Speech Synthesis para habla
```

---

## 🐛 Troubleshooting

### ❌ El micrófono no funciona
- Verifica permisos de navegador
- Recarga la página
- Intenta en Chrome/Edge

### ❌ El sonido no funciona
- Verifica que no esté mutizado
- Haz click en 🔊 para activar
- Comprueba volumen del sistema

### ❌ No recibe respuesta del servidor
- Verifica que estés logueado como admin
- Revisa la consola (F12 → Console)
- Intenta recargar la página

### ❌ Las respuestas son genéricas
- Usa palabras clave específicas
- Intenta con palabras sugeridas
- El chatbot entiende: cuántos, total, activos, hoy, etc.

---

## 📊 Ejemplos de Preguntas Reales

```
✅ "¿Cuántos paquetes tenemos?"
✅ "Dame el total de ingresos"
✅ "¿Cuántos repartidores activos hay?"
✅ "Dame un resumen"
✅ "Paquetes sin entregar"
✅ "¿Cuánto ganamos hoy?"
✅ "Problemas de entrega"

❌ "Hola" (muy genérica)
❌ "Qué hay" (no entiende contexto)
❌ "Dame todo" (muy vaga)
```

---

## 🎓 Casos de Uso

1. **Quick Dashboard**: Preguntas rápidas sin abrir reports
2. **Voice Control**: Manos libres mientras trabajas
3. **Accesibilidad**: Audio para usuarios con problemas de visión
4. **Mobile**: Más fácil hablar que escribir
5. **Automatización**: Integrar con otros sistemas

---

## 📈 Próximas Mejoras (Opcionales)

- [ ] Aprender de conversaciones previas
- [ ] Historial de chat persistente
- [ ] Más tipos de consultas
- [ ] Predicciones basadas en histórico
- [ ] Exportar conversaciones
- [ ] Integración con Whatsapp

---

## ✅ Estado

- ✅ Reconocimiento de voz (Web Speech API)
- ✅ Síntesis de voz (Speech Synthesis)
- ✅ Queries a BD automáticas
- ✅ Interfaz responsive
- ✅ Manejo de errores
- ✅ Indicadores de estado

---

## 🔗 URLs de Acceso

| URL | Propósito |
|-----|-----------|
| `/admin/chatbot.php` | Chatbot completo |
| `/admin/api_chatbot.php` | API backend |
| `/admin/chatbot_acceso.php` | Acceso verificado |

---

**¡Listo para probar! 🚀**

Accede a: http://localhost/pruebitaaa/admin/chatbot.php
