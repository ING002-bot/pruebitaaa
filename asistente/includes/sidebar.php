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
            <div class="menu-section-title">GESTIÓN</div>
            <a href="caja_chica.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'caja_chica.php' ? 'active' : ''; ?>">
                <i class="bi bi-wallet2"></i>
                <span>Caja Chica</span>
            </a>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">SISTEMA</div>
            <a href="../auth/logout.php" class="menu-item">
                <i class="bi bi-box-arrow-right"></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </div>
</div>
