/**
 * Bloquear navegación hacia atrás (botón back del navegador)
 * para evitar que usuarios autenticados vuelvan al login
 */

(function() {
    // Método 1: History manipulation
    history.pushState(null, document.title, location.href);
    
    window.addEventListener('popstate', function (event) {
        history.pushState(null, document.title, location.href);
    });
    
    // Método 2: Prevenir eventos del navegador
    window.addEventListener('beforeunload', function() {
        history.pushState(null, document.title, location.href);
    });
    
    // Método 3: Bloquear gestos de navegación
    document.addEventListener('keydown', function(event) {
        // Bloquear Alt + Flecha Izquierda (atrás)
        if (event.altKey && event.key === 'ArrowLeft') {
            event.preventDefault();
            return false;
        }
        // Bloquear Backspace fuera de inputs
        if (event.key === 'Backspace' && 
            !['INPUT', 'TEXTAREA'].includes(event.target.tagName) && 
            !event.target.isContentEditable) {
            event.preventDefault();
            return false;
        }
    });
    
    // Método 4: Detectar navegación y redirigir
    if (window.performance) {
        if (performance.navigation.type === 2) {
            // TYPE_BACK_FORWARD = 2
            location.reload();
        }
    }
    
    // Método 5: Bloquear eventos de mouse de navegación
    document.addEventListener('mousedown', function(event) {
        // Botón 3 = botón atrás del mouse
        // Botón 4 = botón adelante del mouse
        if (event.button === 3 || event.button === 4) {
            event.preventDefault();
            return false;
        }
    });
    
    // Método 6: Mantener el historial constantemente actualizado
    setInterval(function() {
        if (window.history.state === null) {
            history.pushState(null, document.title, location.href);
        }
    }, 100);
})();

// Prevenir caché de la página
window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        window.location.reload();
    }
});

// Detectar si viene del botón atrás
window.addEventListener('load', function() {
    if (performance.navigation.type === 2) {
        // Viene del botón atrás, recargar
        window.location.reload();
    }
});
