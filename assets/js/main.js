/* --- LÓGICA DEL CARRITO DE COMPRAS --- */

// 1. Abrir / Cerrar Carrito
function toggleCart() {
    const sidebar = document.getElementById('cartSidebar');
    const overlay = document.getElementById('cartOverlay');

    sidebar.classList.toggle('open');
    overlay.classList.toggle('active');

    if (sidebar.classList.contains('open')) {
        refreshCartDisplay();
    }
}

// 2. Agregar al Carrito (Llamada AJAX)
function addToCart(id, cantidad = 1, varianteId = null) {
    fetch('ajax/carrito.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion: 'agregar', id: id, cantidad: cantidad, varianteId: varianteId })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartUI(data);
                toggleCart();
            } else if (data.error) {
                alert(data.error);
            }
        });
}

// 3. Eliminar Item
function removeCartItem(id) {
    fetch('ajax/carrito.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion: 'eliminar', id: id })
    })
        .then(response => response.json())
        .then(data => updateCartUI(data));
}

// 4. Actualizar Cantidad (+ / -) desde el carrito
function updateCartItem(id, nuevaCantidad) {
    if (nuevaCantidad < 1) return;

    fetch('ajax/carrito.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion: 'actualizar', id: id, cantidad: nuevaCantidad })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartUI(data);
            } else if (data.error) {
                // No se puede bajar del mínimo: mostrar un aviso suave sin bloquear
                alert('⚠ ' + data.error);
            }
        });
}

// 5. Refrescar solo visualización (útil al cargar página)
function refreshCartDisplay() {
    fetch('ajax/carrito.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion: 'leer' })
    })
        .then(response => response.json())
        .then(data => updateCartUI(data));
}

// Función auxiliar para pintar los datos en el carrito lateral
function updateCartUI(data) {
    const container = document.getElementById('cartItemsContainer');
    if (container) container.innerHTML = data.html;

    if (document.getElementById('cartSubtotalValue')) document.getElementById('cartSubtotalValue').innerText = data.subtotal || data.total;
    if (document.getElementById('cartEnvioValue')) document.getElementById('cartEnvioValue').innerText = data.envio || 'Q0.00';
    if (document.getElementById('cartTotalValue')) document.getElementById('cartTotalValue').innerText = data.total;

    // Actualizar burbuja del menú
    const countBadge = document.getElementById('cart-count');
    if (countBadge) countBadge.innerText = data.count;

    // ── BLOQUEAR EL BOTÓN DE CHECKOUT si hay mínimos no cumplidos ──
    const btnCheckout = document.getElementById('btnIrCheckout');
    const alertaMinimo = document.getElementById('cartMinimoAlert');

    if (btnCheckout && data.tiene_errores_minimo && data.minimo_violations && data.minimo_violations.length > 0) {
        btnCheckout.disabled = true;
        btnCheckout.style.opacity = '0.5';
        btnCheckout.style.cursor = 'not-allowed';

        if (alertaMinimo) {
            let msgs = data.minimo_violations.map(v => v.parcial
                ? `<strong>${v.nombre}</strong>: debe ser múltiplo de ${v.minimo} (lote completo) — tienes ${v.actual}`
                : `<strong>${v.nombre}</strong>: necesitas ${v.minimo} unidades (tienes ${v.actual})`
            ).join('<br>');
            alertaMinimo.innerHTML = '⚠ Ajusta la cantidad para completar el lote:<br>' + msgs;
            alertaMinimo.style.display = 'block';
        }
    } else if (btnCheckout) {
        btnCheckout.disabled = false;
        btnCheckout.style.opacity = '1';
        btnCheckout.style.cursor = 'pointer';
        if (alertaMinimo) alertaMinimo.style.display = 'none';
    }
}

// Cargar carrito al iniciar la web
document.addEventListener("DOMContentLoaded", () => {
    refreshCartDisplay();
});