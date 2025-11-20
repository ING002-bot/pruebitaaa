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
        <div class="header-search">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Buscar paquetes, rutas...">
        </div>
    </div>
    
    <div class="header-right">
        <div class="header-icon" title="Notificaciones">
            <i class="bi bi-bell"></i>
            <span class="badge">0</span>
        </div>
        <div class="user-profile">
            <img src="../assets/img/<?php echo $_SESSION['foto_perfil']; ?>" alt="Avatar" onerror="this.src='../assets/img/default-avatar.svg'">
            <div class="user-info">
                <span class="user-name"><?php echo $_SESSION['nombre']; ?></span>
                <span class="user-role">Administrador</span>
            </div>
        </div>
    </div>
</div>
