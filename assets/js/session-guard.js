/**
 * Session Guard - Protección adicional de sesión
 * Verifica constantemente que la sesión esté activa
 */

(function() {
    'use strict';
    
    // Verificar sesión cada 30 segundos
    setInterval(function() {
        fetch(window.location.href, {
            method: 'HEAD',
            cache: 'no-store'
        }).catch(function() {
            // Si hay error de conexión, recargar
            window.location.reload();
        });
    }, 30000);
    
    // Interceptar todos los intentos de navegación
    window.addEventListener('popstate', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Forzar permanencia en la página actual
        history.pushState(null, '', window.location.href);
        
        // Mostrar mensaje opcional
        console.log('Navegación hacia atrás bloqueada. Use el botón de cerrar sesión.');
        
        return false;
    }, true);
    
    // Bloquear atajos de teclado de navegación
    document.addEventListener('keydown', function(e) {
        // Alt + Flecha Izquierda/Derecha
        if (e.altKey && (e.key === 'ArrowLeft' || e.key === 'ArrowRight')) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        
        // Comando + Flecha Izquierda/Derecha (Mac)
        if (e.metaKey && (e.key === 'ArrowLeft' || e.key === 'ArrowRight')) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        
        // Backspace fuera de campos de texto
        if (e.key === 'Backspace' && 
            !['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName) &&
            !e.target.isContentEditable) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    }, true);
    
    // Bloquear gestos del mouse
    document.addEventListener('mouseup', function(e) {
        // Botón 3 = Atrás
        // Botón 4 = Adelante
        if (e.button === 3 || e.button === 4) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    }, true);
    
    // Bloquear gestos táctiles de navegación (móvil)
    let touchStartX = 0;
    let touchEndX = 0;
    
    document.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    });
    
    document.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    });
    
    function handleSwipe() {
        // Swipe de derecha a izquierda (más de 50px) = atrás
        if (touchEndX < touchStartX - 50) {
            // Prevenir navegación
            history.pushState(null, '', window.location.href);
        }
        // Swipe de izquierda a derecha = adelante
        if (touchEndX > touchStartX + 50) {
            history.pushState(null, '', window.location.href);
        }
    }
    
    // Inicializar el estado del historial
    history.pushState(null, '', window.location.href);
    
})();
