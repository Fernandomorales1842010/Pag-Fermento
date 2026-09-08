<style>
    .site-footer {
        background-color: #1F1F1F;
        color: #ccc;
        padding: 0;
        margin-top: auto;
    }
    .footer-main {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1.5fr;
        gap: 40px;
        max-width: 1200px;
        margin: 0 auto;
        padding: 60px 20px 40px;
    }
    .footer-brand h3 {
        font-family: 'Merriweather', serif;
        font-size: 1.5rem;
        color: #fff;
        letter-spacing: 2px;
        margin-bottom: 15px;
    }
    .footer-brand p {
        font-size: 0.9rem;
        line-height: 1.7;
        color: #999;
    }
    .footer-col h4 {
        font-family: 'Merriweather', serif;
        color: #fff;
        font-size: 1rem;
        margin-bottom: 20px;
        position: relative;
        padding-bottom: 10px;
    }
    .footer-col h4::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 30px;
        height: 2px;
        background: #D98C45;
    }
    .footer-col ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .footer-col ul li {
        margin-bottom: 10px;
    }
    .footer-col ul li a {
        color: #999;
        text-decoration: none;
        font-size: 0.9rem;
        transition: color 0.3s, padding-left 0.3s;
    }
    .footer-col ul li a:hover {
        color: #D98C45;
        padding-left: 5px;
    }
    .footer-contact-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 15px;
        font-size: 0.9rem;
        color: #999;
    }
    .footer-contact-item i {
        color: #D98C45;
        margin-top: 3px;
        width: 16px;
        text-align: center;
    }
    .footer-social {
        display: flex;
        gap: 12px;
        margin-top: 20px;
    }
    .footer-social a {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        border: 1px solid #444;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #999;
        text-decoration: none;
        transition: all 0.3s;
        font-size: 0.9rem;
    }
    .footer-social a:hover {
        border-color: #D98C45;
        color: #D98C45;
        transform: translateY(-3px);
    }
    .footer-bottom {
        border-top: 1px solid #333;
        padding: 20px;
        text-align: center;
        font-size: 0.8rem;
        color: #666;
    }
    @media (max-width: 768px) {
        .footer-main {
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
    }
    @media (max-width: 480px) {
        .footer-main {
            grid-template-columns: 1fr;
            gap: 25px;
        }
    }
</style>

<footer class="site-footer">
    <div class="footer-main">
        <div class="footer-brand">
            <h3>FERMENTO</h3>
            <p>Panadería artesanal dedicada a ofrecer pan, pasteles y repostería de la más alta calidad. Cada producto es elaborado con ingredientes seleccionados y el amor de nuestros panaderos.</p>
            <div class="footer-social">
                <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="https://wa.me/50239754421" target="_blank" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
            </div>
        </div>
        
        <div class="footer-col">
            <h4>Navegación</h4>
            <ul>
                <li><a href="index.php">Inicio</a></li>
                <li><a href="catalogo.php">Catálogo</a></li>
                <li><a href="login.php">Mi Cuenta</a></li>
            </ul>
        </div>
        
        <div class="footer-col">
            <h4>Categorías</h4>
            <ul>
                <li><a href="catalogo.php">Pan Salado</a></li>
                <li><a href="catalogo.php">Pan Dulce</a></li>
                <li><a href="catalogo.php">Temporada</a></li>
            </ul>
        </div>
        
        <div class="footer-col">
            <h4>Contacto</h4>
            <div class="footer-contact-item">
                <i class="fab fa-whatsapp"></i>
                <span>+502 3975-4421</span>
                
            </div>
            <div class="footer-contact-item">
                <i class="fas fa-map-marker-alt"></i>
                <span>Guatemala, Guatemala</span>
            </div>
            <div class="footer-contact-item">
                <i class="fas fa-truck"></i>
                <span>Envío a domicilio disponible</span>
            </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <p>&copy; <?php echo date("Y"); ?> Fermento Panadería Artesanal. Todos los derechos reservados.</p>
    </div>
</footer>

    <script src="assets/js/main.js"></script>
</body>
</html>