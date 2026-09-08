<?php 
    // 1. Conexión y Lógica de Base de Datos
    require 'includes/db.php';
    
    // Iniciar sesión si no está activa (para el navbar)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 2. Validar ID del Producto
    // Si viene ?id=X usamos ese, si no, intentamos con el 1 por defecto
    $id_producto = isset($_GET['id']) ? (int)$_GET['id'] : 1;

    // 3. Consultar a la Base de Datos
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt->execute([$id_producto]);
    $producto = $stmt->fetch();

    $stmtVar = $pdo->prepare("SELECT * FROM producto_variantes WHERE producto_id = ?");
    $stmtVar->execute([$id_producto]);
    $variantes = $stmtVar->fetchAll(PDO::FETCH_ASSOC);
    
    // Si la variante no tiene un mínimo específico, hereda el del producto principal
    $min_padre = isset($producto['minimo_compra']) && $producto['minimo_compra'] > 1 ? (int)$producto['minimo_compra'] : 1;
    foreach ($variantes as &$v) {
        if (empty($v['minimo_compra']) || $v['minimo_compra'] < 1) {
            $v['minimo_compra'] = $min_padre;
        }
    }
    unset($v);

    $tieneVariantes = count($variantes) > 0;

    // 4. Si el producto no existe, redirigir al inicio para evitar errores
    if(!$producto) {
        header("Location: index.php");
        exit;
    }

    // Configurar título de la página dinámico
    $pageTitle = $producto['nombre'] . " | Fermento";
    $page = "producto";

    include 'includes/header.php'; 
    include 'includes/nav.php'; 
?>

<div class="breadcrumbs container">
    <a href="index.php">Inicio</a> <span>/</span> 
    <a href="#"><?php echo htmlspecialchars($producto['categoria']); ?></a> <span>/</span> 
    <?php echo htmlspecialchars($producto['nombre']); ?>
</div>

<section class="product-detail container">
    
    <div class="product-gallery">
        <div class="main-image-container">
            <img src="assets/img/<?php echo htmlspecialchars($producto['imagen']); ?>" id="mainImage" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
        </div>
        
        <div class="thumbnail-row">
            <!-- Imagen 1 -->
            <img src="assets/img/<?php echo htmlspecialchars($producto['imagen']); ?>" class="thumb active" onclick="changeImage(this)">
            
            <!-- Imagen 2 (Si existe) -->
            <?php if($producto['imagen_2']): ?>
                <img src="assets/img/<?php echo htmlspecialchars($producto['imagen_2']); ?>" class="thumb" onclick="changeImage(this)">
            <?php endif; ?>

            <!-- Imagen 3 (Si existe) -->
            <?php if($producto['imagen_3']): ?>
                <img src="assets/img/<?php echo htmlspecialchars($producto['imagen_3']); ?>" class="thumb" onclick="changeImage(this)">
            <?php endif; ?>
        </div>
    </div>

    <div class="product-info-block">
        <span class="badge-new">Un delicioso</span>
        
        <h1 class="product-title"><?php echo htmlspecialchars($producto['nombre']); ?></h1>
        
        <div class="price-rating">
            <span class="price" id="displayPrice">Q<?php echo number_format($producto['precio'], 2); ?></span>
            <div class="rating">
                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
            </div>
        </div>


        <?php if($tieneVariantes): ?>
            <?php
            // Organizar variantes por tamano y sabor únicos
            $tamanos_unicos = array_values(array_unique(array_filter(array_column($variantes, 'tamano'))));
            $sabores_unicos = array_values(array_unique(array_filter(array_column($variantes, 'sabor'))));
            $tiene_tamanos  = count($tamanos_unicos) > 0;
            $tiene_sabores  = count($sabores_unicos) > 0;
            ?>
            <script>
            window.VARIANTES_DATA = <?php echo json_encode($variantes); ?>;
            </script>

            <div class="variants-selector" style="margin:20px 0;background:#fdfdfd;border:1px solid #eee;border-radius:14px;padding:20px;">
                <?php if($tiene_tamanos): ?>
                <div style="margin-bottom:14px;">
                    <label style="display:block;font-weight:700;font-size:0.82rem;text-transform:uppercase;letter-spacing:0.5px;color:#555;margin-bottom:10px;">Tamaño</label>
                    <div id="tamanoButtons" style="display:flex;flex-wrap:wrap;gap:8px;">
                        <?php foreach($tamanos_unicos as $t): ?>
                        <button type="button" class="var-btn-tamano" data-value="<?php echo htmlspecialchars($t); ?>"
                            onclick="selectTamano(this)"
                            style="padding:8px 16px;border-radius:50px;border:2px solid #eee;background:white;cursor:pointer;font-family:'Poppins',sans-serif;font-size:0.85rem;font-weight:600;transition:all 0.2s;color:#555;">
                            <?php echo htmlspecialchars($t); ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if($tiene_sabores): ?>
                <div style="margin-bottom:14px;">
                    <label style="display:block;font-weight:700;font-size:0.82rem;text-transform:uppercase;letter-spacing:0.5px;color:#555;margin-bottom:10px;">Sabor</label>
                    <div id="saborButtons" style="display:flex;flex-wrap:wrap;gap:8px;">
                        <?php foreach($sabores_unicos as $s): ?>
                        <button type="button" class="var-btn-sabor" data-value="<?php echo htmlspecialchars($s); ?>"
                            onclick="selectSabor(this)"
                            style="padding:8px 16px;border-radius:50px;border:2px solid #eee;background:white;cursor:pointer;font-family:'Poppins',sans-serif;font-size:0.85rem;font-weight:600;transition:all 0.2s;color:#555;">
                            <?php echo htmlspecialchars($s); ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Si solo hay una dimensión (sin tamano ni sabor, solo nombre genérico) -->
                <?php if(!$tiene_tamanos && !$tiene_sabores): ?>
                <label style="display:block;font-weight:700;margin-bottom:8px;font-size:0.9rem;">Selecciona una opción:</label>
                <select id="varianteSelect" onchange="updateVariantPrice()" style="width:100%;padding:10px 15px;border-radius:8px;border:1.5px solid #ddd;font-family:'Poppins',sans-serif;">
                    <?php foreach($variantes as $v): ?>
                        <option value="<?php echo $v['id']; ?>" data-precio="<?php echo $v['precio']; ?>" data-stock="<?php echo $v['stock']; ?>" data-minimo="<?php echo (int)($v['minimo_compra'] ?? 1); ?>">
                            <?php echo htmlspecialchars($v['nombre']); ?> — Q<?php echo number_format($v['precio'],2); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>

                <div id="varianteSeleccionada" style="margin-top:12px;padding:10px 14px;background:#fff8ee;border-radius:8px;border:1px solid #f0dfc3;display:none;font-size:0.85rem;">
                    <span id="varianteResumen" style="font-weight:600;color:#D98C45;"></span>
                    <span id="varianteStock" style="float:right;color:#aaa;"></span>
                </div>
                <input type="hidden" id="varianteIdSeleccionada" value="">
            </div>
        <?php endif; ?>






<?php if($producto['stock'] > 0 || $tieneVariantes): ?>

    <?php
        $minimo_compra = isset($producto['minimo_compra']) ? (int)$producto['minimo_compra'] : 1;
        if ($minimo_compra < 1) $minimo_compra = 1;
        $qty_inicial = $minimo_compra;
        $qty_max = $tieneVariantes ? 9999 : $producto['stock'];
    ?>
    <div class="actions-group">
        <div class="quantity-selector">
            <button onclick="updateQty(-1)">-</button>
            <input type="number" id="qty"
                value="<?php echo $qty_inicial; ?>"
                min="<?php echo $minimo_compra; ?>"
                max="<?php echo $qty_max; ?>"
                data-minimo="<?php echo $minimo_compra; ?>"
                readonly>
            <button onclick="updateQty(1)">+</button>
        </div>
        <div id="minimoContainer" style="font-size:0.78rem; color:#888; margin-top:6px; margin-bottom:4px; display:<?php echo $minimo_compra > 1 ? 'block' : 'none'; ?>">
            <i class="fas fa-info-circle" style="color:#D98C45;"></i>
            Mínimo de compra: <strong id="minimoValue"><?php echo $minimo_compra; ?></strong> unidades
        </div>
        <button class="btn-primary add-cart" onclick="addCurrentToCart(<?php echo $producto['id']; ?>)">
            Añadir al Carrito
        </button>
    </div>

<?php else: ?>

    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center; border: 1px dashed #ccc; margin-top: 20px;">
        <i class="fas fa-store-slash" style="font-size: 2rem; color: #999; margin-bottom: 10px;"></i>
        <h3 style="margin-bottom: 5px;">Producto Agotado</h3>
        <p style="color: #666; font-size: 0.9rem;">Lo sentimos, este producto no está disponible por el momento.</p>
        <a href="index.php" class="btn-secondary" style="margin-top: 15px; display: inline-block;">Ver otros panes</a>
    </div>

<?php endif; ?>



        
        <?php 
            require_once 'includes/config.php';
            $mensaje_ws = "Hola Fermento, estoy interesado en este producto: " . $producto['nombre'];
            $telefono_ws = getConfig('whatsapp_numero', '50239754421');
            $link_ws = "https://wa.me/{$telefono_ws}?text=" . urlencode($mensaje_ws);
        ?>
        <a href="<?php echo $link_ws; ?>" target="_blank" class="btn-whatsapp">
            <i class="fab fa-whatsapp"></i> Deseo agendar un pedido grande o especial
        </a>

        <div class="tabs-container">
            <div class="tab-headers">
                <button class="tab-btn active" onclick="openTab(event, 'descripcion')">Descripción</button>
                <button class="tab-btn" onclick="openTab(event, 'maridaje')">Perfecto para combinar</button>
            </div>
            
            <div id="descripcion" class="tab-content active">
                <p><?php echo nl2br(htmlspecialchars($producto['descripcion'] ?: 'Sin descripción disponible.')); ?></p>
            </div>
            
            <div id="maridaje" class="tab-content">
                <?php if (!empty($producto['maridaje'])): ?>
                    <p><?php echo nl2br(htmlspecialchars($producto['maridaje'])); ?></p>
                <?php else: ?>
                    <p style="color:#aaa;font-style:italic;">Aún no hemos definido sugerencias de maridaje para este producto. ¡Pronto agregaremos ideas!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
    // 1. Funcionalidad de Pestañas (Tabs)
    function openTab(evt, tabName) {
        let tabContents = document.getElementsByClassName("tab-content");
        for (let i = 0; i < tabContents.length; i++) {
            tabContents[i].style.display = "none";
            tabContents[i].classList.remove('active');
        }
        let tabLinks = document.getElementsByClassName("tab-btn");
        for (let i = 0; i < tabLinks.length; i++) {
            tabLinks[i].className = tabLinks[i].className.replace(" active", "");
        }
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.className += " active";
    }
    
    // 2. Control de Cantidad (+ / -) — respeta el mínimo de compra
    function updateQty(change) {
        const qtyInput = document.getElementById('qty');
        const minimo   = parseInt(qtyInput.getAttribute('data-minimo') || qtyInput.getAttribute('min') || '1');
        const newVal   = parseInt(qtyInput.value) + change;
        if (newVal >= minimo) {
            qtyInput.value = newVal;
        }
        // Efecto visual suave cuando intenta bajar del mínimo
        if (change < 0 && newVal < minimo) {
            qtyInput.classList.add('shake-no');
            setTimeout(() => qtyInput.classList.remove('shake-no'), 400);
        }
    }

    // 3. Galería de Imágenes
    function changeImage(element) {
        // Cambia la imagen grande por la de la miniatura clicada
        document.getElementById('mainImage').src = element.src;
        
        // Actualiza el borde activo
        document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
        element.classList.add('active');
    }

    // 4. Lógica de variantes con Tamaño / Sabor
    let selectedTamano = null;
    let selectedSabor  = null;

    function findVariante() {
        if (typeof window.VARIANTES_DATA === 'undefined') return null;
        
        const reqTamano = window.VARIANTES_DATA.some(v => v.tamano);
        const reqSabor  = window.VARIANTES_DATA.some(v => v.sabor);
        
        if (reqTamano && !selectedTamano) return null;
        if (reqSabor && !selectedSabor) return null;

        return window.VARIANTES_DATA.find(v => {
            const tamOk = v.tamano === selectedTamano || (!v.tamano && !selectedTamano);
            const sabOk = v.sabor === selectedSabor || (!v.sabor && !selectedSabor);
            return tamOk && sabOk;
        }) || null;
    }

    function updateVariantUI() {
        const v = findVariante();
        const resDiv = document.getElementById('varianteSeleccionada');
        const hiddenId = document.getElementById('varianteIdSeleccionada');
        const qtyInput = document.getElementById('qty');

        if (v) {
            // Precio
            document.getElementById('displayPrice').innerText = 'Q' + parseFloat(v.precio).toFixed(2);
            // Badge de stock
            const stockTxt = v.stock > 0 ? v.stock + ' disponibles' : '⚠ Agotado';
            document.getElementById('varianteResumen').innerText =
                [v.tamano, v.sabor].filter(Boolean).join(' / ') || v.nombre;
            document.getElementById('varianteStock').innerText = stockTxt;
            if (resDiv) resDiv.style.display = 'block';
            if (hiddenId) hiddenId.value = v.id;
            if (qtyInput) {
                const stockMax = v.stock > 0 ? v.stock : 9999;
                const minCompra = v.minimo_compra ? parseInt(v.minimo_compra) : 1;
                
                qtyInput.max = stockMax;
                qtyInput.min = minCompra;
                qtyInput.dataset.minimo = minCompra;
                
                let curVal = parseInt(qtyInput.value) || 1;
                if (curVal > stockMax && v.stock > 0) qtyInput.value = stockMax;
                if (curVal < minCompra) qtyInput.value = minCompra;
                
                const minContainer = document.getElementById('minimoContainer');
                const minValEl = document.getElementById('minimoValue');
                if (minContainer && minValEl) {
                    minValEl.innerText = minCompra;
                    minContainer.style.display = minCompra > 1 ? 'block' : 'none';
                }
            }
        } else if (resDiv && (selectedTamano || selectedSabor)) {
            document.getElementById('varianteResumen').innerText = '⚠ Combinación no disponible';
            document.getElementById('varianteStock').innerText = '';
            resDiv.style.display = 'block';
            if (hiddenId) hiddenId.value = '';
        }
    }

    function selectTamano(btn) {
        // Toggle si ya estaba seleccionado
        if (btn.classList.contains('var-selected')) {
            btn.classList.remove('var-selected');
            btn.style.cssText = btn.style.cssText.replace(/background:[^;]+;border-color:[^;]+;color:[^;]+;/, '');
            btn.style.background = 'white';
            btn.style.borderColor = '#eee';
            btn.style.color = '#555';
            selectedTamano = null;
        } else {
            document.querySelectorAll('.var-btn-tamano').forEach(b => {
                b.classList.remove('var-selected');
                b.style.background = 'white';
                b.style.borderColor = '#eee';
                b.style.color = '#555';
            });
            btn.classList.add('var-selected');
            btn.style.background = '#1F1F1F';
            btn.style.borderColor = '#1F1F1F';
            btn.style.color = 'white';
            selectedTamano = btn.dataset.value;
        }
        updateVariantUI();
    }

    function selectSabor(btn) {
        if (btn.classList.contains('var-selected')) {
            btn.classList.remove('var-selected');
            btn.style.background = 'white';
            btn.style.borderColor = '#eee';
            btn.style.color = '#555';
            selectedSabor = null;
        } else {
            document.querySelectorAll('.var-btn-sabor').forEach(b => {
                b.classList.remove('var-selected');
                b.style.background = 'white';
                b.style.borderColor = '#eee';
                b.style.color = '#555';
            });
            btn.classList.add('var-selected');
            btn.style.background = '#D98C45';
            btn.style.borderColor = '#D98C45';
            btn.style.color = 'white';
            selectedSabor = btn.dataset.value;
        }
        updateVariantUI();
    }

    // Selector genérico (fallback sin tamano/sabor)
    function updateVariantPrice() {
        let select = document.getElementById('varianteSelect');
        if (select) {
            let option = select.options[select.selectedIndex];
            let price = parseFloat(option.getAttribute('data-precio')).toFixed(2);
            document.getElementById('displayPrice').innerText = 'Q' + price;
            let qtyInput = document.getElementById('qty');
            if(qtyInput) {
                let stock = parseInt(option.getAttribute('data-stock'));
                let minCompra = parseInt(option.getAttribute('data-minimo') || 1);
                
                qtyInput.setAttribute('max', stock > 0 ? stock : 9999);
                qtyInput.setAttribute('min', minCompra);
                qtyInput.setAttribute('data-minimo', minCompra);
                
                let curVal = parseInt(qtyInput.value) || 1;
                if(curVal > stock && stock > 0) qtyInput.value = stock;
                if(curVal < minCompra) qtyInput.value = minCompra;
                
                const minContainer = document.getElementById('minimoContainer');
                const minValEl = document.getElementById('minimoValue');
                if (minContainer && minValEl) {
                    minValEl.innerText = minCompra;
                    minContainer.style.display = minCompra > 1 ? 'block' : 'none';
                }
            }
            const hiddenId = document.getElementById('varianteIdSeleccionada');
            if (hiddenId) hiddenId.value = option.value;
        }
    }

    // 5. Añadir al carrito — usa el hidden input para la variante seleccionada
    function addCurrentToCart(prodId) {
        let qty = document.getElementById('qty').value;

        // Intentar con el hidden input primero (botones Tamaño/Sabor)
        let hiddenId = document.getElementById('varianteIdSeleccionada');
        let varId = hiddenId ? hiddenId.value : null;

        // Fallback al select genérico
        if (!varId) {
            let select = document.getElementById('varianteSelect');
            varId = select ? select.value : null;
        }

        // Si tiene variantes pero no hay ninguna seleccionada, alertar
        if (typeof window.VARIANTES_DATA !== 'undefined' && window.VARIANTES_DATA.length > 0 && !varId) {
            alert('Por favor selecciona las opciones del producto (tamaño / sabor).');
            return;
        }

        addToCart(prodId, qty, varId || null);
    }

    document.addEventListener("DOMContentLoaded", function() {
        updateVariantPrice();
    });

</script>

<?php include 'includes/footer.php'; ?>