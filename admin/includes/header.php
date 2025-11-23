<!-- Header Include para Admin -->
<script>
// Prevenir acceso mediante botón de retroceso después de logout
(function() {
    if (window.history && window.history.pushState) {
        window.history.pushState(null, null, window.location.href);
        window.onpopstate = function() {
            window.history.pushState(null, null, window.location.href);
        };
    }
})();
</script>
<div class="top-header">
    <div class="header-left">
        <button class="menu-toggle" onclick="toggleSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <div class="welcome-message">
            <h5 class="mb-0">Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre'] . ' ' . ($_SESSION['apellido'] ?? '')); ?></h5>
        </div>
    </div>
    
    <div class="header-right">
        <div class="dropdown">
            <div class="header-icon" id="notificacionesDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;" title="Notificaciones">
                <i class="bi bi-bell"></i>
                <span class="badge" id="notificaciones-count">0</span>
            </div>
            <ul class="dropdown-menu dropdown-menu-end notificaciones-dropdown" aria-labelledby="notificacionesDropdown" style="width: 350px; max-height: 400px; overflow-y: auto;">
                <li class="dropdown-header d-flex justify-content-between align-items-center">
                    <span>Notificaciones</span>
                    <a href="#" class="text-primary small" onclick="marcarTodasLeidas(); return false;">Marcar todas como leídas</a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <div id="notificaciones-lista">
                    <li class="dropdown-item text-center text-muted">
                        <small>No hay notificaciones</small>
                    </li>
                </div>
            </ul>
        </div>
        <div class="user-profile">
            <?php 
            $foto_url = !empty($_SESSION['foto_perfil']) && $_SESSION['foto_perfil'] != 'default-avatar.svg' 
                ? '../uploads/perfiles/' . $_SESSION['foto_perfil'] 
                : '../assets/img/default-avatar.svg';
            ?>
            <img src="<?php echo $foto_url; ?>" alt="Avatar" onerror="this.src='../assets/img/default-avatar.svg'">
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
                <span class="user-role">Administrador</span>
            </div>
        </div>
    </div>
</div>
