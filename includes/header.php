<?php
// Iniciar sesión si no está iniciada (Solo una vez)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : htmlspecialchars(getConfig('tienda_nombre', 'Fermento')) . ' | Panadería Artesanal'; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@300;700;900&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="cart-overlay" id="cartOverlay" onclick="toggleCart()"></div>
    
    <div class="cart-sidebar" id="cartSidebar">
        
        <div class="cart-header-panel">
            <h3>Tu Canasta</h3>
            <button class="close-cart" onclick="toggleCart()">&times;</button>
        </div>
        
        <div class="cart-items-container" id="cartItemsContainer">
            <p class="empty-msg" style="padding:20px; text-align:center; color:#999;">Cargando carrito...</p>
        </div>

        <div class="cart-footer-panel">
            <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:0.9rem;color:#666;">
                <span>Subtotal:</span>
                <span id="cartSubtotalValue">Q0.00</span>
            </div>
            <div style="display:flex;justify-content:space-between;margin-bottom:12px;font-size:0.9rem;color:#666;">
                <span>Envío:</span>
                <span id="cartEnvioValue">Q0.00</span>
            </div>
            <div class="cart-total" style="border-top:1px solid #eee;padding-top:12px;">
                <span>Total:</span>
                <span id="cartTotalValue" style="color:var(--accent-toast);">Q0.00</span>
            </div>
            <!-- Alerta de mínimo de compra (oculta por defecto) -->
            <div id="cartMinimoAlert" style="display:none; background:#fff3cd; border:1px solid #f0a500; border-radius:8px; padding:10px 12px; margin-top:12px; font-size:0.78rem; color:#856404; line-height:1.5;"></div>

            <button id="btnIrCheckout" onclick="window.location.href='checkout.php'" class="btn-primary full-width" style="text-align:center; margin-top:15px; width:100%; border:none; cursor:pointer;">
                Finalizar Compra
            </button>
        </div>
    </div>

  