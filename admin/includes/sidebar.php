<!-- Sidebar Include para Admin -->
<div class="sidebar active" id="sidebar">
    <div class="sidebar-header">
        <i class="bi bi-box-seam"></i>
        <h3>HERMES EXPRESS</h3>
        <p>LOGISTIC</p>
    </div>
    
    <div class="sidebar-menu">
        <div class="menu-section">
            <div class="menu-section-title">Principal</div>
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
            <div class="menu-section-title">Gestión</div>
            <a href="usuarios.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'active' : ''; ?>">
                <i class="bi bi-people"></i>
                <span>Usuarios</span>
            </a>
            <a href="tarifas.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'tarifas.php' ? 'active' : ''; ?>">
                <i class="bi bi-cash-coin"></i>
                <span>Tarifas por Zona</span>
            </a>
            <a href="caja_chica.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'caja_chica.php' ? 'active' : ''; ?>">
                <i class="bi bi-wallet2"></i>
                <span>Caja Chica</span>
            </a>
            <a href="pagos.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'pagos.php' ? 'active' : ''; ?>">
                <i class="bi bi-cash-stack"></i>
                <span>Pagos</span>
            </a>
            <a href="ingresos.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'ingresos.php' ? 'active' : ''; ?>">
                <i class="bi bi-graph-up-arrow"></i>
                <span>Ingresos</span>
            </a>
            <a href="gastos.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'gastos.php' ? 'active' : ''; ?>">
                <i class="bi bi-graph-down-arrow"></i>
                <span>Gastos</span>
            </a>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">Sistema</div>
            <a href="importar.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'importar.php' ? 'active' : ''; ?>">
                <i class="bi bi-cloud-upload"></i>
                <span>Importar de SAVAR</span>
            </a>
            <a href="importar_excel.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'importar_excel.php' ? 'active' : ''; ?>">
                <i class="bi bi-file-earmark-excel"></i>
                <span>Importar Excel</span>
            </a>
            <a href="reportes.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'reportes.php' ? 'active' : ''; ?>">
                <i class="bi bi-file-earmark-bar-graph"></i>
                <span>Reportes</span>
            </a>
            <a href="configuracion.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'configuracion.php' ? 'active' : ''; ?>">
                <i class="bi bi-gear"></i>
                <span>Configuración</span>
            </a>
            <a href="../auth/logout.php" class="menu-item">
                <i class="bi bi-box-arrow-right"></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </div>
</div>
