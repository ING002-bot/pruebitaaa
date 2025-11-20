// Dashboard JavaScript - HERMES EXPRESS

// Toggle Sidebar
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.classList.toggle('active');
    }
}

// Mostrar sidebar automáticamente en desktop
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar && window.innerWidth > 992) {
        sidebar.classList.add('active');
    }
});

// Mantener sidebar visible en desktop al redimensionar
window.addEventListener('resize', function() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar && window.innerWidth > 992) {
        sidebar.classList.add('active');
    }
});

// Cerrar sidebar al hacer click fuera (móvil)
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.querySelector('.menu-toggle');
    
    if (sidebar && menuToggle) {
        if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
            if (window.innerWidth <= 992) {
                sidebar.classList.remove('active');
            }
        }
    }
});

// Auto-hide flash messages
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

// Confirmación de eliminación
function confirmarEliminacion(mensaje = '¿Estás seguro de que deseas eliminar este elemento?') {
    return confirm(mensaje);
}

// Formatear moneda
function formatCurrency(amount) {
    return 'S/. ' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// Formatear fecha
function formatDate(dateString) {
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

// Formatear fecha y hora
function formatDateTime(dateString) {
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year} ${hours}:${minutes}`;
}

// Validar formularios
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    return form.checkValidity();
}

// Búsqueda en tiempo real en tablas
function searchTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    
    if (!input || !table) return;
    
    input.addEventListener('keyup', function() {
        const filter = input.value.toUpperCase();
        const rows = table.getElementsByTagName('tr');
        
        for (let i = 1; i < rows.length; i++) {
            let show = false;
            const cells = rows[i].getElementsByTagName('td');
            
            for (let j = 0; j < cells.length; j++) {
                const cell = cells[j];
                if (cell) {
                    const textValue = cell.textContent || cell.innerText;
                    if (textValue.toUpperCase().indexOf(filter) > -1) {
                        show = true;
                        break;
                    }
                }
            }
            
            rows[i].style.display = show ? '' : 'none';
        }
    });
}

// Copiar al portapapeles
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showToast('Copiado al portapapeles', 'success');
    }).catch(function(err) {
        console.error('Error al copiar: ', err);
    });
}

// Toast notifications
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer');
    
    if (!toastContainer) {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999;';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    document.getElementById('toastContainer').appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}

// Exportar tabla a CSV
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let row of rows) {
        const cols = row.querySelectorAll('td, th');
        const csvRow = [];
        
        for (let col of cols) {
            csvRow.push('"' + col.innerText.replace(/"/g, '""') + '"');
        }
        
        csv.push(csvRow.join(','));
    }
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

// Actualizar notificaciones
function actualizarNotificaciones() {
    fetch('../api/notificaciones.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.querySelector('.header-icon .badge');
            if (badge && data.count > 0) {
                badge.textContent = data.count;
                badge.style.display = 'block';
            }
        })
        .catch(error => console.error('Error al cargar notificaciones:', error));
}

// Obtener ubicación en tiempo real
function trackLocation(callback) {
    if ("geolocation" in navigator) {
        navigator.geolocation.watchPosition(
            function(position) {
                const coords = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    timestamp: new Date().toISOString()
                };
                
                if (callback) callback(coords);
                
                // Enviar al servidor
                fetch('../api/actualizar_ubicacion.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(coords)
                });
            },
            function(error) {
                console.error('Error de geolocalización:', error);
            },
            {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 0
            }
        );
    }
}

// Inicializar tooltips de Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Validación de archivos de imagen
function validateImageFile(input, maxSize = 5242880) { // 5MB por defecto
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const fileSize = file.size;
        const fileType = file.type;
        
        // Validar tipo
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!validTypes.includes(fileType)) {
            alert('Solo se permiten archivos JPG, JPEG o PNG');
            input.value = '';
            return false;
        }
        
        // Validar tamaño
        if (fileSize > maxSize) {
            alert(`El archivo es demasiado grande. Tamaño máximo: ${(maxSize / 1048576).toFixed(2)}MB`);
            input.value = '';
            return false;
        }
        
        return true;
    }
    return false;
}

// Preview de imagen
function previewImage(input, previewElementId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.getElementById(previewElementId);
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Actualizar notificaciones cada 30 segundos
setInterval(actualizarNotificaciones, 30000);
