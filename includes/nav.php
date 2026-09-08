<style>
    /* 1. RESET CRÍTICO: Elimina el espacio blanco del navegador */
    body {
        margin: 0;
        padding: 0;
        width: 100%;
    }

    /* 2. BARRA DE NAVEGACIÓN */
    .nav-wrapper {
        background-color: #fff;
        border-bottom: 1px solid #eee;
        padding: 15px 0;
        position: sticky; /* Se queda pegada al bajar */
        top: 0;
        left: 0;
        width: 100%;
        z-index: 1000;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-top: 0 !important; /* Fuerza bruta para quitar margen superior */
    }
    
    .nav-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Logo */
    .brand-logo {
        font-family: 'Merriweather', serif;
        font-weight: 900;
        font-size: 1.5rem;
        color: #1F1F1F;
        text-decoration: none;
        letter-spacing: 2px;
        text-transform: uppercase;
        display: flex;
        align-items: center;
    }

    /* Menú */
    .nav-menu {
        display: flex;
        list-style: none;
        margin: 0;
        padding: 0;
        gap: 25px;
        align-items: center;
    }

    .nav-menu li a {
        text-decoration: none;
        color: #333;
        font-family: 'Poppins', sans-serif;
        font-size: 0.95rem;
        font-weight: 500;
        transition: color 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .nav-menu li a:hover {
        color: #D98C45;
    }

    /* Contador Carrito */
    #cart-count {
        background: #D98C45;
        color: white;
        font-size: 0.75rem;
        padding: 2px 6px;
        border-radius: 10px;
        font-weight: bold;
    }

    /* Nombre de usuario */
    .nav-user-name {
        font-size: 0.85rem;
        color: #D98C45;
        font-weight: 600;
    }

    /* Hamburger Button */
    .nav-hamburger {
        display: none;
        background: none;
        border: none;
        font-size: 1.4rem;
        cursor: pointer;
        color: #1F1F1F;
        padding: 5px;
        line-height: 1;
    }

    @media (max-width: 768px) {
        .nav-hamburger { display: block; }
        
        .nav-menu {
            display: none;
            flex-direction: column;
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            background: #fff;
            padding: 20px;
            gap: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
            border-top: 1px solid #eee;
            animation: slideDown 0.3s ease;
        }
        
        .nav-menu.mobile-open {
            display: flex;
        }
        
        .nav-menu li a {
            padding: 8px 0;
            font-size: 1rem;
        }
        
        .nav-container {
            position: relative;
        }

        .brand-logo { font-size: 1.2rem; }
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="nav-wrapper">
    <div class="nav-container">
        
        <a href="index.php" class="brand-logo">FERMENTO</a>

        <button class="nav-hamburger" onclick="toggleMobileMenu()" aria-label="Menú">
            <i class="fas fa-bars" id="hamburgerIcon"></i>
        </button>

        <ul class="nav-menu" id="navMenu">
            <li><a href="index.php">Inicio</a></li>
            <li><a href="catalogo.php">Catálogo</a></li>

            <?php if(isset($_SESSION['user_id'])): ?>
                <li>
                    <span class="nav-user-name">
                        <i class="fas fa-user-circle"></i> 
                        <?php echo isset($_SESSION['user_nombre']) ? htmlspecialchars(explode(' ', $_SESSION['user_nombre'])[0]) : 'Mi Cuenta'; ?>
                    </span>
                </li>
                <?php if (isset($_SESSION['user_rol']) && in_array($_SESSION['user_rol'], ['admin', 'supervisor'])): ?>
                <li>
                    <a href="admin/index.php" style="color: #fff; background: #D98C45; padding: 6px 12px; border-radius: 6px; font-weight: bold; align-items: center; justify-content: center; text-decoration: none;">
                        <i class="fas fa-tools"></i> 
                        <span>Panel Admin</span>
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a href="mis_pedidos.php" style="color: #D98C45;">
                        <i class="fas fa-receipt"></i> 
                        <span>Mis Pedidos</span>
                    </a>
                </li>
                <li>
                    <a href="logout.php" title="Salir" style="color: #d63031;">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="hide-desktop">Cerrar Sesión</span>
                    </a>
                </li>
            <?php else: ?>
                <li>
                    <a href="login.php">
                        <i class="fas fa-user-circle"></i> 
                        <span>Login</span>
                    </a>
                </li>
            <?php endif; ?>

            <li>
                <a href="#" onclick="event.preventDefault(); toggleCart();" title="Ver Carrito">
                    <i class="fas fa-shopping-basket" style="font-size: 1.2rem;"></i> 
                    <span id="cart-count">0</span>
                </a>
            </li>
        </ul>

    </div>
</div>

<script>
function toggleMobileMenu() {
    const menu = document.getElementById('navMenu');
    const icon = document.getElementById('hamburgerIcon');
    menu.classList.toggle('mobile-open');
    
    if (menu.classList.contains('mobile-open')) {
        icon.className = 'fas fa-times';
    } else {
        icon.className = 'fas fa-bars';
    }
}
</script>