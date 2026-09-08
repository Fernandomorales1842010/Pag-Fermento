<!-- admin/includes/admin_nav.php -->
<aside class="sidebar">
    <div class="sidebar-brand">
        FERMENTO<span>ADMIN</span>
    </div>

    <!-- Usuario activo -->
    <div style="padding: 12px 20px 16px; border-bottom: 1px solid rgba(255,255,255,0.08); display:flex; align-items:center; gap:10px;">
        <div style="width:36px; height:36px; border-radius:50%; background:var(--accent-toast); display:flex; align-items:center; justify-content:center; flex-shrink:0; font-weight:700; color:white; font-size:0.9rem;">
            <?php echo strtoupper(substr($_SESSION['user_nombre'] ?? $_SESSION['nombre'] ?? 'A', 0, 1)); ?>
        </div>
        <div style="overflow:hidden;">
            <div style="color:white; font-weight:600; font-size:0.82rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                <?php echo htmlspecialchars($_SESSION['user_nombre'] ?? $_SESSION['nombre'] ?? 'Administrador'); ?>
            </div>
            <div style="color:rgba(255,255,255,0.4); font-size:0.68rem; text-transform:uppercase; letter-spacing:0.5px;">
                <?php echo ucfirst($_SESSION['user_rol'] ?? 'admin'); ?>
            </div>
        </div>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="index.php" class="<?php echo ($currentPage == 'dashboard') ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="productos.php" class="<?php echo ($currentPage == 'productos') ? 'active' : ''; ?>">
                <i class="fas fa-bread-slice"></i>
                <span>Inventario</span>
            </a>
        </li>
        <li>
            <a href="pedidos.php" class="<?php echo ($currentPage == 'pedidos') ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-list"></i>
                <span>Pedidos</span>
            </a>
        </li>
        <li>
            <a href="javascript:void(0)" onclick="requestNotificationPermission()" id="notif-btn">
                <i class="fas fa-bell"></i>
                <span>Notificaciones</span>
            </a>
        </li>
        <li>
            <a href="zonas.php" class="<?php echo ($currentPage == 'zonas') ? 'active' : ''; ?>">
                <i class="fas fa-map-marked-alt"></i>
                <span>Zonas de Envío</span>
            </a>
        </li>
        <li>
            <a href="categorias.php" class="<?php echo ($currentPage == 'categorias') ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i>
                <span>Categorías</span>
            </a>
        </li>
        <li>
            <a href="cupones.php" class="<?php echo ($currentPage == 'cupones') ? 'active' : ''; ?>">
                <i class="fas fa-ticket-alt"></i>
                <span>Cupones</span>
            </a>
        </li>
        <?php if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] !== 'supervisor'): ?>
        <li>
            <a href="usuarios.php" class="<?php echo ($currentPage == 'usuarios') ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Usuarios</span>
            </a>
        </li>
        <li>
            <a href="logs.php" class="<?php echo ($currentPage == 'logs') ? 'active' : ''; ?>">
                <i class="fas fa-history"></i>
                <span>Actividad</span>
            </a>
        </li>
        <li>
            <a href="configuracion.php" class="<?php echo ($currentPage == 'configuracion') ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Configuración</span>
            </a>
        </li>
        <?php endif; ?>
        <li>
            <a href="../index.php" target="_blank">
                <i class="fas fa-eye"></i>
                <span>Ver Tienda</span>
            </a>
        </li>
    </ul>

    <script>
    async function requestNotificationPermission() {
        if (!('Notification' in window)) {
            alert('Este navegador no soporta notificaciones.');
            return;
        }
        
        let permission = await Notification.requestPermission();
        if (permission === 'granted') {
            const btn = document.getElementById('notif-btn');
            btn.innerHTML = '<i class="fas fa-check-circle" style="color:#27ae60"></i><span>Activas</span>';
            
            // Test Notification
            navigator.serviceWorker.ready.then(reg => {
                reg.showNotification('¡Notificaciones Activas! 🍞', {
                    body: 'Bienvenido a FERMENTO Admin. Te avisaremos cada vez que recibas un pedido.',
                    icon: '../assets/icons/icon-192.png',
                    vibrate: [200, 100, 200]
                });
            });
        }
    }

    // Check status at load
    if (Notification.permission === 'granted') {
        window.addEventListener('load', () => {
            const btn = document.getElementById('notif-btn');
            if(btn) btn.innerHTML = '<i class="fas fa-check-circle" style="color:#27ae60"></i><span>Activas</span>';
        });
    }
    </script>

    <div class="sidebar-footer">
        <a href="../logout.php">
             <i class="fas fa-sign-out-alt"></i> 
             <span>Cerrar Sesión</span>
        </a>
    </div>
</aside>
<main class="main-wrapper">
