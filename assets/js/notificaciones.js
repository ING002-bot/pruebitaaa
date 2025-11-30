/**
 * Sistema de Notificaciones en Tiempo Real
 */

// Proteger contra múltiples cargas del script
if (typeof window.NotificacionesManager === 'undefined') {
    window.NotificacionesManager = {
        ultimaActualizacion: null,
        inicializado: false
    };
    
    var ultimaActualizacion = null; // Mantener compatibilidad
}

// Cargar notificaciones al iniciar (solo una vez)
if (!window.NotificacionesManager.inicializado) {
    document.addEventListener('DOMContentLoaded', function() {
        cargarNotificaciones();
        
        // Actualizar cada 30 segundos
        setInterval(cargarNotificaciones, 30000);
    });
    
    window.NotificacionesManager.inicializado = true;
}

/**
 * Cargar notificaciones del servidor
 */
function cargarNotificaciones() {
    fetch('../api/notificaciones.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                actualizarBadge(data.count);
                mostrarNotificaciones(data.notificaciones);
            }
        })
        .catch(error => console.error('Error al cargar notificaciones:', error));
}

/**
 * Actualizar badge con el contador
 */
function actualizarBadge(count) {
    // Badge en la campana del header (amarillo)
    const badge = document.getElementById('notificaciones-count');
    if (badge) {
        badge.textContent = count;
        badge.classList.remove('bg-danger');
        badge.classList.add('bg-warning');
        badge.style.display = count > 0 ? 'inline-block' : 'none';
    }
    
    // Badge en el menú de Paquetes (sidebar - amarillo)
    const badgePaquetes = document.getElementById('paquetes-notificaciones-badge');
    if (badgePaquetes) {
        badgePaquetes.textContent = count;
        
        // Solo mostrar si count > 0
        if (count > 0) {
            badgePaquetes.style.display = 'inline-block';
            badgePaquetes.style.visibility = 'visible';
        } else {
            badgePaquetes.style.display = 'none';
            badgePaquetes.style.visibility = 'hidden';
        }
    }
}

/**
 * Mostrar lista de notificaciones
 */
function mostrarNotificaciones(notificaciones) {
    const lista = document.getElementById('notificaciones-lista');
    if (!lista) return;
    
    if (notificaciones.length === 0) {
        lista.innerHTML = `
            <li class="dropdown-item text-center text-muted">
                <small>No hay notificaciones nuevas</small>
            </li>
        `;
        return;
    }
    
    lista.innerHTML = notificaciones.map(n => {
        const iconos = {
            'info': 'bi-info-circle text-primary',
            'alerta': 'bi-exclamation-triangle text-warning',
            'urgente': 'bi-exclamation-circle text-danger',
            'sistema': 'bi-gear text-secondary'
        };
        
        const icono = iconos[n.tipo] || iconos['info'];
        const fecha = new Date(n.fecha_creacion);
        const hace = calcularTiempo(fecha);
        
        return `
            <li>
                <a class="dropdown-item notificacion-item ${n.leida ? 'leida' : ''}" 
                   href="#" 
                   onclick="marcarLeida(${n.id}); return false;">
                    <div class="d-flex align-items-start">
                        <i class="bi ${icono} me-2 mt-1"></i>
                        <div class="flex-grow-1">
                            <strong class="d-block">${escapeHtml(n.titulo)}</strong>
                            <small class="text-muted d-block">${escapeHtml(n.mensaje)}</small>
                            <small class="text-muted"><i class="bi bi-clock"></i> ${hace}</small>
                        </div>
                    </div>
                </a>
            </li>
        `;
    }).join('');
}

/**
 * Marcar una notificación como leída
 */
function marcarLeida(notificacionId) {
    fetch('../api/marcar_notificacion_leida.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ notificacion_id: notificacionId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cargarNotificaciones();
        }
    })
    .catch(error => console.error('Error al marcar notificación:', error));
}

/**
 * Marcar todas las notificaciones como leídas
 */
function marcarTodasLeidas() {
    fetch('../api/marcar_notificacion_leida.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ todas: true })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cargarNotificaciones();
        }
    })
    .catch(error => console.error('Error al marcar todas como leídas:', error));
}

/**
 * Calcular tiempo transcurrido
 */
function calcularTiempo(fecha) {
    const ahora = new Date();
    const diff = Math.floor((ahora - fecha) / 1000); // segundos
    
    if (diff < 60) return 'Hace un momento';
    if (diff < 3600) return `Hace ${Math.floor(diff / 60)} min`;
    if (diff < 86400) return `Hace ${Math.floor(diff / 3600)} h`;
    if (diff < 604800) return `Hace ${Math.floor(diff / 86400)} días`;
    
    return fecha.toLocaleDateString('es-ES');
}

/**
 * Escapar HTML para prevenir XSS
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}
