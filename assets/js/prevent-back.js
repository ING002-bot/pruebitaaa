/**
 * Bloquear navegación hacia atrás (botón back del navegador)
 * para evitar que usuarios autenticados vuelvan al login
 */

(function() {
    // Prevenir navegación hacia atrás
    if (window.history && window.history.pushState) {
        // Agregar estado al historial
        window.history.pushState('forward', null, window.location.href);
        
        // Detectar cuando el usuario intenta ir atrás
        window.addEventListener('popstate', function() {
            // Volver a empujar hacia adelante
            window.history.pushState('forward', null, window.location.href);
        });
    }
})();

// Alternativa adicional: desactivar caché de página
window.addEventListener('pageshow', function(event) {
    // Si la página viene del caché (botón atrás)
    if (event.persisted) {
        // Recargar la página
        window.location.reload();
    }
});

// Prevenir que F5/Ctrl+R muestren formularios antiguos
if (window.performance && window.performance.navigation.type === window.performance.navigation.TYPE_BACK_FORWARD) {
    window.location.reload();
}
