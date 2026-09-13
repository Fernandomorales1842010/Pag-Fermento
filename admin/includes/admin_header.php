<?php
// admin/includes/admin_header.php
require_once __DIR__ . '/auth_admin.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Panel Admin'; ?> | Fermento</title>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700;900&family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="theme-color" content="#D98C45">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="../assets/icons/icon-192.png">
    <script>
    // Registro de PWA y Service Worker
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('service-worker.js')
          .then(reg => console.log('SW listo', reg))
          .catch(err => console.log('SW error', err));
      });
    }

    // --- MONITOREO DE PEDIDOS EN TIEMPO REAL ---
    let lastOrderId = localStorage.getItem('lastKnownOrder');
    
    async function checkNewOrders() {
        try {
            const resp = await fetch('api_check_orders.php');
            const data = await resp.json();
            
            if (data.success && data.last_id > 0) {
                // Si es la primera vez que cargamos, guardamos el ID actual y no notificamos
                if (!lastOrderId) {
                    localStorage.setItem('lastKnownOrder', data.last_id);
                    lastOrderId = data.last_id;
                    return;
                }
                
                // Si el nuevo ID es mayor al que conocemos... ¡HAY VENTA! 🍞🔥
                if (data.last_id > lastOrderId) {
                    localStorage.setItem('lastKnownOrder', data.last_id);
                    lastOrderId = data.last_id;
                    
                    // Disparar Notificación con Vibración
                    if (Notification.permission === 'granted') {
                        navigator.serviceWorker.ready.then(reg => {
                            reg.showNotification('¡NUEVO PEDIDO! 🍞🔥', {
                                body: 'Cliente: ' + data.cliente + '\nTotal: Q' + parseFloat(data.monto).toFixed(2),
                                icon: '../assets/icons/icon-192.png',
                                badge: '../assets/icons/icon-192.png',
                                vibrate: [300, 150, 300, 150, 500, 150, 800], // Patrón rítmico
                                data: { url: 'pedidos.php' },
                                tag: 'nuevo-pedido-' + data.last_id // Evita duplicadas
                            });
                        });
                    }
                }
            }
        } catch (e) { console.warn('Error monitoreando pedidos:', e); }
    }

    // Revisar cada 20 segundos
    setInterval(checkNewOrders, 20000);
    checkNewOrders(); // Ejecutar una vez al inicio
    </script>
    <link rel="stylesheet" href="assets/css/admin-new.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <!-- Header para Moviles -->
    <header class="mobile-header">
        <div class="mobile-brand">FERMENTO ADMIN</div>
        <button class="mobile-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
    </header>

    <!-- Overlay para cerrar el menu al tocar fuera -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <script>
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        
        // Prevenir scroll en el body cuando el menu esta abierto
        if(sidebar.classList.contains('active')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
    </script>
