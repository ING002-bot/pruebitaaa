/**
 * Sistema de Validaciones de Entrada Universal
 * Aplica validaciones automáticas a todos los campos de formulario
 */

// Patrones de validación
const PATRONES = {
    // Solo letras, espacios y acentos (nombres, apellidos)
    SOLO_TEXTO: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/,
    
    // Solo números enteros
    SOLO_NUMEROS: /^[0-9]+$/,
    
    // Solo números decimales
    DECIMALES: /^[0-9]+\.?[0-9]*$/,
    
    // Teléfono (números, espacios, guiones, paréntesis, +)
    TELEFONO: /^[\+]?[0-9\s\-\(\)]+$/,
    
    // Email válido
    EMAIL: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
    
    // Código alfanumérico (tracking, códigos)
    ALFANUMERICO: /^[a-zA-Z0-9\-_]+$/,
    
    // Dirección (letras, números, espacios, puntos, comas, guiones)
    DIRECCION: /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\.\,\-\#]+$/
};

// Mensajes de error
const MENSAJES = {
    SOLO_TEXTO: 'Solo se permiten letras y espacios',
    SOLO_NUMEROS: 'Solo se permiten números enteros',
    DECIMALES: 'Solo se permiten números decimales',
    TELEFONO: 'Formato de teléfono inválido',
    EMAIL: 'Formato de email inválido',
    ALFANUMERICO: 'Solo se permiten letras, números y guiones',
    DIRECCION: 'Caracteres no válidos en la dirección'
};

// Mapeo de campos por nombre/clase/tipo
const CAMPOS_VALIDACION = {
    // Campos de solo texto
    'nombre': 'SOLO_TEXTO',
    'apellido': 'SOLO_TEXTO',
    'destinatario_nombre': 'SOLO_TEXTO',
    'receptor_nombre': 'SOLO_TEXTO',
    'nombre_zona': 'SOLO_TEXTO',
    'tipo_envio': 'SOLO_TEXTO',
    'edit_nombre': 'SOLO_TEXTO',
    'edit_apellido': 'SOLO_TEXTO',
    
    // Campos numéricos
    'telefono': 'TELEFONO',
    'destinatario_telefono': 'TELEFONO',
    'edit_telefono': 'TELEFONO',
    'tarifa_repartidor': 'DECIMALES',
    'edit_tarifa_repartidor': 'DECIMALES',
    'costo_envio': 'DECIMALES',
    'monto': 'DECIMALES',
    'gastoMonto': 'DECIMALES',
    'receptor_dni': 'SOLO_NUMEROS',
    
    // Campos de email
    'email': 'EMAIL',
    'destinatario_email': 'EMAIL',
    'edit_email': 'EMAIL',
    
    // Campos alfanuméricos
    'codigo_seguimiento': 'ALFANUMERICO',
    'codigo_savar': 'ALFANUMERICO',
    
    // Direcciones y conceptos
    'destinatario_direccion': 'DIRECCION',
    'direccion': 'DIRECCION',
    'concepto': 'DIRECCION',
    'observaciones': 'DIRECCION',
    
    // Búsquedas (más permisivas)
    'buscar': 'DIRECCION',
    'buscarPaquete': 'DIRECCION'
};

/**
 * Valida un campo según su patrón asignado
 */
function validarCampo(campo, valor) {
    const tipoValidacion = CAMPOS_VALIDACION[campo.name] || 
                          CAMPOS_VALIDACION[campo.className] ||
                          CAMPOS_VALIDACION[campo.id] ||
                          (campo.type === 'number' ? 'DECIMALES' : null);
    
    if (!tipoValidacion) return true;
    
    const patron = PATRONES[tipoValidacion];
    
    // Permitir campo vacío si no es requerido
    if (!valor && !campo.required) return true;
    
    return patron.test(valor);
}

/**
 * Muestra mensaje de error en un campo
 */
function mostrarError(campo, mensaje) {
    // Remover error previo
    removerError(campo);
    
    // Agregar clase de error
    campo.classList.add('is-invalid');
    
    // Crear mensaje de error
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = mensaje;
    errorDiv.setAttribute('data-validation-error', campo.name);
    
    // Insertar mensaje después del campo
    campo.parentNode.insertBefore(errorDiv, campo.nextSibling);
}

/**
 * Remueve mensaje de error de un campo
 */
function removerError(campo) {
    campo.classList.remove('is-invalid');
    
    const errorExistente = campo.parentNode.querySelector(`[data-validation-error="${campo.name}"]`);
    if (errorExistente) {
        errorExistente.remove();
    }
}

/**
 * Muestra un mensaje temporal al usuario
 */
function mostrarMensajeTemporalmente(campo, mensaje) {
    // Crear tooltip temporal
    const tooltip = document.createElement('div');
    tooltip.className = 'validation-tooltip';
    tooltip.textContent = mensaje;
    tooltip.style.cssText = `
        position: absolute;
        background: #dc3545;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        z-index: 1000;
        white-space: nowrap;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        animation: fadeInOut 2s ease-in-out;
    `;
    
    // Posicionar el tooltip
    const rect = campo.getBoundingClientRect();
    tooltip.style.left = rect.left + 'px';
    tooltip.style.top = (rect.bottom + 5) + 'px';
    
    document.body.appendChild(tooltip);
    
    // Remover después de 2 segundos
    setTimeout(() => {
        if (tooltip.parentNode) {
            tooltip.parentNode.removeChild(tooltip);
        }
    }, 2000);
    
    // Agregar animación CSS si no existe
    if (!document.getElementById('validation-styles')) {
        const style = document.createElement('style');
        style.id = 'validation-styles';
        style.textContent = `
            @keyframes fadeInOut {
                0% { opacity: 0; transform: translateY(-5px); }
                20% { opacity: 1; transform: translateY(0); }
                80% { opacity: 1; transform: translateY(0); }
                100% { opacity: 0; transform: translateY(-5px); }
            }
        `;
        document.head.appendChild(style);
    }
}

/**
 * Filtra caracteres mientras el usuario escribe
 */
function filtrarEntrada(event) {
    const campo = event.target;
    const valor = campo.value;
    const tipoValidacion = CAMPOS_VALIDACION[campo.name] || 
                          CAMPOS_VALIDACION[campo.className] ||
                          CAMPOS_VALIDACION[campo.id] ||
                          (campo.type === 'number' ? 'DECIMALES' : null);
    
    if (!tipoValidacion) return;
    
    // Para números, permitir teclas especiales
    if (event.key && (
        event.key === 'Backspace' || 
        event.key === 'Delete' || 
        event.key === 'Tab' || 
        event.key === 'ArrowLeft' || 
        event.key === 'ArrowRight' ||
        event.key === 'Home' ||
        event.key === 'End' ||
        event.ctrlKey || 
        event.metaKey
    )) {
        return;
    }
    
    // Validar el carácter individual que se está escribiendo
    if (event.key && tipoValidacion === 'SOLO_TEXTO') {
        if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]$/.test(event.key)) {
            event.preventDefault();
            mostrarMensajeTemporalmente(campo, 'Solo se permiten letras y espacios');
            return;
        }
    }
    
    if (event.key && tipoValidacion === 'SOLO_NUMEROS') {
        if (!/^[0-9]$/.test(event.key)) {
            event.preventDefault();
            mostrarMensajeTemporalmente(campo, 'Solo se permiten números');
            return;
        }
    }
    
    if (event.key && tipoValidacion === 'DECIMALES') {
        // Permitir punto decimal solo si no existe ya
        if (event.key === '.' && valor.includes('.')) {
            event.preventDefault();
            mostrarMensajeTemporalmente(campo, 'Solo se permite un punto decimal');
            return;
        }
        if (!/^[0-9\.]$/.test(event.key)) {
            event.preventDefault();
            mostrarMensajeTemporalmente(campo, 'Solo se permiten números y punto decimal');
            return;
        }
    }
    
    if (event.key && tipoValidacion === 'TELEFONO') {
        if (!/^[0-9\s\-\(\)\+]$/.test(event.key)) {
            event.preventDefault();
            mostrarMensajeTemporalmente(campo, 'Solo números, espacios y símbolos telefónicos');
            return;
        }
    }
    
    if (event.key && tipoValidacion === 'ALFANUMERICO') {
        if (!/^[a-zA-Z0-9\-_]$/.test(event.key)) {
            event.preventDefault();
            mostrarMensajeTemporalmente(campo, 'Solo letras, números y guiones');
            return;
        }
    }
    
    if (event.key && tipoValidacion === 'EMAIL') {
        if (!/^[a-zA-Z0-9@\.\-_]$/.test(event.key)) {
            event.preventDefault();
            mostrarMensajeTemporalmente(campo, 'Caracteres no válidos para email');
            return;
        }
    }
}

/**
 * Valida un campo cuando pierde el foco
 */
function validarEnBlur(event) {
    const campo = event.target;
    const valor = campo.value.trim();
    
    if (!validarCampo(campo, valor)) {
        const tipoValidacion = CAMPOS_VALIDACION[campo.name] || 
                              CAMPOS_VALIDACION[campo.className] ||
                              CAMPOS_VALIDACION[campo.id] ||
                              (campo.type === 'number' ? 'DECIMALES' : null);
        
        const mensaje = MENSAJES[tipoValidacion] || 'Formato inválido';
        mostrarError(campo, mensaje);
    } else {
        removerError(campo);
    }
}

/**
 * Valida todos los campos de un formulario
 */
function validarFormulario(formulario) {
    let esValido = true;
    const campos = formulario.querySelectorAll('input, textarea, select');
    
    campos.forEach(campo => {
        if (campo.type !== 'hidden' && campo.type !== 'file' && campo.type !== 'checkbox' && campo.type !== 'radio') {
            const valor = campo.value.trim();
            
            if (!validarCampo(campo, valor)) {
                const tipoValidacion = CAMPOS_VALIDACION[campo.name] || 
                                      CAMPOS_VALIDACION[campo.className] ||
                                      CAMPOS_VALIDACION[campo.id] ||
                                      (campo.type === 'number' ? 'DECIMALES' : null);
                
                const mensaje = MENSAJES[tipoValidacion] || 'Formato inválido';
                mostrarError(campo, mensaje);
                esValido = false;
            } else {
                removerError(campo);
            }
        }
    });
    
    return esValido;
}

/**
 * Inicializa las validaciones cuando se carga la página
 */
function inicializarValidaciones() {
    // Buscar todos los campos de input y textarea
    const campos = document.querySelectorAll('input, textarea');
    
    campos.forEach(campo => {
        if (campo.type !== 'hidden' && campo.type !== 'file' && campo.type !== 'checkbox') {
            // Filtrar entrada mientras escribe
            campo.addEventListener('keydown', filtrarEntrada);
            
            // Validar cuando pierde el foco
            campo.addEventListener('blur', validarEnBlur);
            
            // Limpiar error cuando empiece a escribir correctamente
            campo.addEventListener('input', function(event) {
                const campo = event.target;
                if (campo.classList.contains('is-invalid')) {
                    const valor = campo.value.trim();
                    if (validarCampo(campo, valor)) {
                        removerError(campo);
                    }
                }
            });
        }
    });
    
    // Interceptar envío de formularios
    const formularios = document.querySelectorAll('form');
    formularios.forEach(formulario => {
        formulario.addEventListener('submit', function(event) {
            if (!validarFormulario(formulario)) {
                event.preventDefault();
                
                // Hacer scroll al primer error
                const primerError = formulario.querySelector('.is-invalid');
                if (primerError) {
                    primerError.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                    primerError.focus();
                }
                
                // Mostrar alerta
                alert('Por favor, corrige los errores en el formulario antes de continuar.');
            }
        });
    });
}

// Inicializar cuando se carga el DOM
document.addEventListener('DOMContentLoaded', inicializarValidaciones);

// Re-inicializar cuando se cargan nuevos elementos dinámicamente
window.reinicializarValidaciones = inicializarValidaciones;