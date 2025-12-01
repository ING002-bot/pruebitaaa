<!-- Sidebar Include para Repartidor -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <i class="bi bi-box-seam"></i>
        <h3>HERMES EXPRESS</h3>
        <p>REPARTIDOR</p>
    </div>
    
    <div class="sidebar-menu">
        <div class="menu-section">
            <a href="dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
            <a href="mis_paquetes.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'mis_paquetes.php' ? 'active' : ''; ?>">
                <i class="bi bi-box"></i>
                <span>Mis Paquetes</span>
            </a>
            <a href="entregar.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'entregar.php' ? 'active' : ''; ?>">
                <i class="bi bi-check-circle"></i>
                <span>Entregar Paquete</span>
            </a>
            <a href="historial.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'historial.php' ? 'active' : ''; ?>">
                <i class="bi bi-clock-history"></i>
                <span>Historial</span>
            </a>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">Herramientas</div>
            <a href="rezagados.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'rezagados.php' ? 'active' : ''; ?>">
                <i class="bi bi-exclamation-triangle"></i>
                <span>Paquetes Rezagados</span>
            </a>
            <a href="mis_ingresos.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'mis_ingresos.php' ? 'active' : ''; ?>">
                <i class="bi bi-cash-stack"></i>
                <span>Mis Ingresos</span>
            </a>
            <a href="tarifas.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'tarifas.php' ? 'active' : ''; ?>">
                <i class="bi bi-currency-dollar"></i>
                <span>Tarifas por Zona</span>
            </a>
        </div>
        
        <div class="menu-section">
            <a href="perfil.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'perfil.php' ? 'active' : ''; ?>">
                <i class="bi bi-person"></i>
                <span>Mi Perfil</span>
            </a>
            <a href="../auth/logout.php" class="menu-item">
                <i class="bi bi-box-arrow-right"></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </div>
</div>
