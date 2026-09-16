<?php 
    // 1. Conexión a Base de Datos
    require 'includes/db.php';
    require 'includes/config.php';

    $pageTitle = "Inicio | " . htmlspecialchars(getConfig('tienda_nombre', 'Fermento'));
    $page = "inicio";
    
    // 2. IMPORTANTE: Aquí se cargan el <head>, el CSS y el CARRITO HTML que pusimos en header.php
    include 'includes/header.php'; 
    
    // 3. Aquí se carga el Menú de Navegación
    include 'includes/nav.php'; 
?>

<header id="inicio" class="hero">
    <div class="hero-content fade-in">
        <span style="letter-spacing: 5px; text-transform: uppercase; font-size: 0.9rem;">Horneando desde 2025</span>
        <h1 style="font-size: 3.5rem; margin: 15px 0;"><?php echo htmlspecialchars(strtoupper(getConfig('tienda_nombre', 'FERMENTO'))); ?></h1>
        <p style="font-style: italic; font-family: 'Merriweather', serif;"><?php echo htmlspecialchars(getConfig('tienda_descripcion', 'Casa de Panaderos')); ?></p>
        <div class="divider-icon"><i class="fas fa-wheat"></i></div>
        <a href="#catalogo" class="btn-primary">Que panes me ofreces</a>
    </div>
</header>

<section class="section container" style="text-align: center; max-width: 700px;">
    <h2 class="section-title">Nuestra Filosofía</h2>
    <p style="font-size: 1.1rem; color: #555;">
        Creemos que el buen pan requiere solo tres cosas: harina de calidad, tiempo y dedicacion .
    </p>
    <div class="divider-icon"><i class="fas fa-bread-slice"></i></div>
</section>

<section id="catalogo" class="section" style="background-color: var(--bg-cream);">
    <div class="container">
        
        <div style="text-align: center; margin-bottom: 50px;">
            <span class="menu-category">Nuestros Productos</span>
            <h2 style="margin-top: 10px;">Recién salidos del horno</h2>
        </div>

        <?php
        $productos = [];
        if (isset($pdo)) {
            try {
                $stmt = $pdo->query("SELECT p.*, (SELECT COUNT(id) FROM producto_variantes v WHERE v.producto_id = p.id) as tiene_variantes FROM productos p WHERE p.destacado = 1");
                $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) { }
        }
        ?>

        <?php if(count($productos) > 0): ?>
            <div class="catalog-grid">
                <?php foreach($productos as $prod): ?>
                    <?php 
                        $agotado = (isset($prod['stock']) && $prod['stock'] <= 0); 
                        $enOferta = (isset($prod['oferta']) && $prod['oferta'] == 1 && !$agotado);
                    ?>

                    <div class="product-card-simple" 
                         onclick="window.location.href='producto.php?id=<?php echo $prod['id']; ?>'"
                         style="cursor: pointer; <?php echo $agotado ? 'opacity: 0.7; filter: grayscale(1);' : ''; ?>">
                        
                        <a href="producto.php?id=<?php echo $prod['id']; ?>" style="display: block; position: relative;">
                            <img src="assets/img/<?php echo htmlspecialchars($prod['imagen']); ?>" 
                                 alt="<?php echo htmlspecialchars($prod['nombre']); ?>"
                                 class="card-img-top"
                                 onerror="this.onerror=null;this.src='assets/img/default_pan.png';">
                            
                            <?php if($agotado): ?>
                                <div style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.8); color: white; padding: 4px 10px; font-size: 0.75rem; font-weight: bold; border-radius: 4px;">
                                    AGOTADO
                                </div>
                            <?php elseif($enOferta): ?>
                                <div style="position: absolute; top: 10px; left: 10px; background: var(--accent-toast); color: white; padding: 4px 10px; font-size: 0.75rem; font-weight: bold; border-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                                    ★ OFERTA
                                </div>
                            <?php endif; ?>
                        </a>

                        <div class="card-body-simple">
                            <a href="producto.php?id=<?php echo $prod['id']; ?>" style="text-decoration:none;">
                                <h3 class="card-title-simple"><?php echo htmlspecialchars($prod['nombre']); ?></h3>
                            </a>
                            
                            <p class="card-desc-simple">
                                <?php echo htmlspecialchars($prod['descripcion']); ?>
                            </p>

                            <div class="card-footer-simple">
                                <span class="card-price-simple"><?php echo ($prod['tiene_variantes'] > 0 ? 'Desde ' : ''); ?>Q<?php echo number_format($prod['precio'], 2); ?></span>
                                
                                <?php if($agotado): ?>
                                    <a href="producto.php?id=<?php echo $prod['id']; ?>" class="btn-icon-add btn-disabled" title="Ver (Agotado)">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="producto.php?id=<?php echo $prod['id']; ?>" class="btn-icon-add" title="Ver Detalle">
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center" style="padding: 60px; color: #888;">
                <p>No hay productos disponibles.</p>
            </div>
        <?php endif; ?>

    </div>
</section>

<section class="section banner-offer" style="background-image: url('assets/img/baguette.png'); background-size: cover; background-attachment: fixed; position: relative;">
    <div style="position: absolute; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.6);"></div>
    <div class="offer-content" style="position: relative; z-index: 2;">
        <h2 style="font-family: 'Merriweather'; font-style: italic;">¿Tienes alguna duda contactate con nosotros?</h2>
        <?php 
            $mensaje_ws = "Hola Fermento, me gustaría recibir atención personalizada para un pedido especial.";
            $telefono_ws = getConfig('whatsapp_numero', '50239754421');
            $link_ws = "https://wa.me/{$telefono_ws}?text=" . urlencode($mensaje_ws);
        ?>
        <a href="<?php echo $link_ws; ?>" target="_blank" class="btn-whatsapp">
            <i class="fab fa-whatsapp"></i> Contáctanos para atención personalizada
        </a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>