<!-- Sidebar Include para Asistente -->
<div class="sidebar active" id="sidebar" style="overflow-y: scroll !important; display: block !important;">
    <div class="sidebar-header">
        <i class="bi bi-box-seam"></i>
        <h3>HERMES EXPRESS</h3>
        <p>LOGISTIC</p>
    </div>
    
    <div class="sidebar-menu" style="height: auto !important; min-height: 100% !important;">
        <div class="menu-section">
            <div class="menu-section-title">PRINCIPAL</div>
            <a href="dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
            <a href="paquetes.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'paquetes.php' ? 'active' : ''; ?>">
                <i class="bi bi-box"></i>
                <span>Paquetes</span>
            </a>
            <a href="rutas.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'rutas.php' ? 'active' : ''; ?>">
                <i class="bi bi-map"></i>
                <span>Rutas</span>
            </a>
            <a href="entregas.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'entregas.php' ? 'active' : ''; ?>">
                <i class="bi bi-check-circle"></i>
                <span>Entregas</span>
            </a>
            <a href="rezagados.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'rezagados.php' ? 'active' : ''; ?>">
                <i class="bi bi-exclamation-triangle"></i>
                <span>Rezagados</span>
            </a>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">ADMINISTRACIÓN</div>
            <a href="usuarios.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'active' : ''; ?>">
                <i class="bi bi-people"></i>
                <span>Usuarios</span>
            </a>
            <a href="tarifas.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'tarifas.php' ? 'active' : ''; ?>">
                <i class="bi bi-tags"></i>
                <span>Tarifas por Zona</span>
            </a>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">GESTIÓN</div>
            <a href="caja_chica.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'caja_chica.php' ? 'active' : ''; ?>">
                <i class="bi bi-wallet2"></i>
                <span>Caja Chica</span>
            </a>
            <a href="importar.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'importar.php' ? 'active' : ''; ?>">
                <i class="bi bi-file-earmark-arrow-up"></i>
                <span>Importar Paquetes</span>
            </a>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">REPORTES</div>
            <a href="reportes.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'reportes.php' ? 'active' : ''; ?>">
                <i class="bi bi-file-earmark-text"></i>
                <span>Reportes de Paquetes</span>
            </a>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">SISTEMA</div>
            <a href="perfil.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'perfil.php' ? 'active' : ''; ?>">
                <i class="bi bi-person-circle"></i>
                <span>Mi Perfil</span>
            </a>
            <a href="../auth/logout.php" class="menu-item">
                <i class="bi bi-box-arrow-right"></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </div>
</div>
