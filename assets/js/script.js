// Animación al hacer Scroll (Intersection Observer)
document.addEventListener('DOMContentLoaded', () => {

    const observerOptions = {
        threshold: 0.2 // El elemento se activa cuando el 20% es visible
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, observerOptions);

    const elementsToReveal = document.querySelectorAll('.scroll-reveal');
    elementsToReveal.forEach(el => observer.observe(el));
});


// Reemplaza la función updateQty vieja por esta:
function updateQty(change) {
    let qtyInput = document.getElementById('qty');
    let maxStock = parseInt(qtyInput.getAttribute('max')); // Leemos el máximo permitido
    let newVal = parseInt(qtyInput.value) + change;
    
    // Validamos que sea mayor a 1 Y menor o igual al stock
    if (newVal >= 1 && newVal <= maxStock) {
        qtyInput.value = newVal;
    } else if (newVal > maxStock) {
        alert("¡Solo quedan " + maxStock + " unidades disponibles!");
    }
}


// Mensaje simple al enviar formulario (Simulación)
const form = document.querySelector('.contact-form');
if (form) {
    form.addEventListener('submit', (e) => {
        e.preventDefault(); // Evita recarga para el demo
        alert('¡Gracias! Hemos recibido tu solicitud de cotización. Te contactaremos pronto.');
        form.reset();
    });
}