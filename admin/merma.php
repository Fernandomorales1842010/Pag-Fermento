<?php
$currentPage = 'merma';
$pageTitle   = 'Reporte de Merma';
require '../includes/db.php';
require '../includes/config.php';
include 'includes/admin_header.php';
require_can('crear_merma');

// Cargar todos los productos con sus variantes
$productos = $pdo->query(
    "SELECT p.*, 
            (SELECT COUNT(*) FROM producto_variantes WHERE producto_id = p.id) as num_variantes
     FROM productos p 
     ORDER BY p.categoria ASC, p.nombre ASC"
)->fetchAll(PDO::FETCH_ASSOC);

// Cargar variantes de todos los productos de una sola vez
$allVariantes = [];
if (!empty($productos)) {
    $ids = implode(',', array_column($productos, 'id'));
    $vars = $pdo->query(
        "SELECT id, producto_id, nombre, precio FROM producto_variantes WHERE producto_id IN ($ids) ORDER BY nombre ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
    foreach ($vars as $v) {
        $allVariantes[$v['producto_id']][] = $v;
    }
}

// Historial de mermas recientes (últimas 10)
$historial = $pdo->query(
    "SELECT m.*, COUNT(md.id) as num_items
     FROM mermas m
     LEFT JOIN merma_detalles md ON md.merma_id = m.id
     GROUP BY m.id
     ORDER BY m.fecha DESC LIMIT 10"
)->fetchAll(PDO::FETCH_ASSOC);

// Agrupar productos por categoría
$porCategoria = [];
foreach ($productos as $p) {
    $porCategoria[$p['categoria']][] = $p;
}

include 'includes/admin_nav.php';
?>

<style>
/* ── Layout Principal ── */
.merma-layout {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 24px;
    align-items: start;
}
@media (max-width: 1024px) {
    .merma-layout { grid-template-columns: 1fr; }
}

/* ── Buscador ── */
.merma-search-bar {
    background: white;
    border-radius: 16px;
    padding: 14px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: var(--shadow);
    margin-bottom: 20px;
}
.merma-search-bar input {
    border: none;
    outline: none;
    font-family: 'Poppins', sans-serif;
    font-size: 0.95rem;
    width: 100%;
    background: transparent;
    color: #1F1F1F;
}
.merma-search-bar i { color: #ccc; font-size: 1rem; }

/* ── Categorías ── */
.cat-tabs {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 18px;
}
.cat-tab {
    padding: 7px 16px;
    border-radius: 30px;
    border: 1.5px solid #eee;
    background: white;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    color: #666;
}
.cat-tab:hover, .cat-tab.active {
    background: var(--text-black);
    color: white;
    border-color: var(--text-black);
}

/* ── Grid de productos ── */
.productos-merma-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 14px;
}
@media (max-width: 600px) {
    .productos-merma-grid { grid-template-columns: repeat(2, 1fr); }
}

/* ── Tarjeta producto ── */
.prod-merma-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: var(--shadow);
    border: 2px solid transparent;
    transition: all 0.2s;
    cursor: pointer;
}
.prod-merma-card:hover {
    border-color: var(--accent-toast);
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(217,140,69,0.15);
}
.prod-merma-card.in-cart {
    border-color: #27ae60;
    background: #f8fffe;
}
.prod-merma-img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    background: #f5f5f5;
    display: block;
}
.prod-merma-body {
    padding: 12px;
}
.prod-merma-name {
    font-weight: 700;
    font-size: 0.85rem;
    color: #1F1F1F;
    line-height: 1.3;
    margin-bottom: 4px;
}
.prod-merma-cat {
    font-size: 0.68rem;
    color: #aaa;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}
.prod-merma-price {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--accent-toast);
    margin-bottom: 10px;
}
.qty-row {
    display: flex;
    align-items: center;
    gap: 6px;
}
.qty-btn {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: 1.5px solid #eee;
    background: white;
    cursor: pointer;
    font-size: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
    flex-shrink: 0;
    color: #555;
    font-weight: 700;
}
.qty-btn:hover { background: var(--accent-toast); color: white; border-color: var(--accent-toast); }
.qty-input {
    width: 44px;
    height: 28px;
    text-align: center;
    border: 1.5px solid #eee;
    border-radius: 8px;
    font-family: 'Poppins', sans-serif;
    font-size: 0.85rem;
    font-weight: 700;
    color: #1F1F1F;
    background: #fafafa;
    outline: none;
}
.qty-input:focus { border-color: var(--accent-toast); background: white; }

/* Variante selector */
.var-select {
    width: 100%;
    padding: 6px 10px;
    border: 1.5px solid #eee;
    border-radius: 8px;
    font-family: 'Poppins', sans-serif;
    font-size: 0.78rem;
    margin-bottom: 8px;
    outline: none;
    background: #fafafa;
    color: #333;
    cursor: pointer;
}
.var-select:focus { border-color: var(--accent-toast); }

/* ── Carrito panel ── */
.carrito-panel {
    background: white;
    border-radius: 20px;
    box-shadow: var(--shadow);
    position: sticky;
    top: 80px;
    overflow: hidden;
}
.carrito-header {
    background: var(--text-black);
    padding: 18px 22px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: white;
}
.carrito-header i { color: var(--accent-toast); font-size: 1.1rem; }
.carrito-header span { font-weight: 700; font-size: 1rem; }
.cart-badge {
    background: var(--accent-toast);
    color: white;
    border-radius: 50%;
    width: 22px;
    height: 22px;
    font-size: 0.7rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: auto;
}
.carrito-items {
    max-height: 280px;
    overflow-y: auto;
    padding: 14px 16px;
}
.carrito-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid #f5f5f5;
    animation: slideIn 0.25s ease;
}
@keyframes slideIn {
    from { opacity:0; transform:translateX(10px); }
    to   { opacity:1; transform:translateX(0); }
}
.carrito-item:last-child { border-bottom: none; }
.ci-info { flex: 1; min-width: 0; }
.ci-name { font-weight: 700; font-size: 0.8rem; color:#1F1F1F; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.ci-var  { font-size: 0.68rem; color:#aaa; margin-top:1px; }
.ci-sub  { font-size: 0.75rem; color:var(--accent-toast); font-weight:700; margin-top:3px; }
.ci-qty  { font-size: 0.78rem; color:#888; white-space:nowrap; }
.ci-del  { cursor:pointer; color:#e74c3c; font-size:0.85rem; padding:4px; flex-shrink:0; opacity:0.6; transition:opacity 0.15s; }
.ci-del:hover { opacity:1; }

.carrito-empty {
    text-align: center;
    padding: 30px;
    color: #ccc;
}
.carrito-empty i { font-size: 2rem; display:block; margin-bottom:8px; }
.carrito-empty p { font-size: 0.82rem; }

.carrito-total {
    padding: 14px 18px;
    background: #fafafa;
    border-top: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 700;
    font-size: 1rem;
}
.carrito-total span:last-child { color: var(--accent-toast); font-size: 1.1rem; }

/* ── Formulario de confirmación ── */
.merma-form {
    padding: 16px 18px;
    border-top: 1px solid #f0f0f0;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.merma-field label {
    display: block;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: #666;
    margin-bottom: 5px;
}
.merma-field input[type=number],
.merma-field textarea {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid #eee;
    border-radius: 10px;
    font-family: 'Poppins', sans-serif;
    font-size: 0.85rem;
    color: #1F1F1F;
    outline: none;
    resize: vertical;
    transition: border-color 0.2s;
    box-sizing: border-box;
}
.merma-field input:focus,
.merma-field textarea:focus {
    border-color: var(--accent-toast);
    box-shadow: 0 0 0 3px rgba(217,140,69,0.1);
}
.pedido-status {
    font-size: 0.72rem;
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 600;
    display: none;
    margin-top: 4px;
}
.pedido-status.ok { background:#eafaf1; color:#27ae60; display:block; }
.pedido-status.err { background:#ffebee; color:#e74c3c; display:block; }

/* Checkbox */
.confirm-check {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    background: #fff8ee;
    border: 1.5px solid #f0c080;
    border-radius: 10px;
    padding: 10px 12px;
    cursor: pointer;
}
.confirm-check input[type=checkbox] {
    width: 18px;
    height: 18px;
    margin-top: 2px;
    flex-shrink: 0;
    accent-color: var(--accent-toast);
    cursor: pointer;
}
.confirm-check p {
    font-size: 0.78rem;
    color: #7a5010;
    line-height: 1.4;
    font-weight: 600;
    margin: 0;
}

.btn-registrar {
    background: var(--accent-toast);
    color: white;
    border: none;
    border-radius: 12px;
    padding: 13px;
    font-family: 'Poppins', sans-serif;
    font-size: 0.9rem;
    font-weight: 700;
    cursor: pointer;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
}
.btn-registrar:hover { background: #c47a30; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(217,140,69,0.35); }
.btn-registrar:disabled { background: #ccc; cursor: not-allowed; transform: none; box-shadow: none; }

/* ── Historial ── */
.historial-card {
    background: white;
    border-radius: 20px;
    box-shadow: var(--shadow);
    overflow: hidden;
    margin-top: 24px;
}
.historial-header {
    padding: 18px 22px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 700;
    font-size: 0.9rem;
    color: #1F1F1F;
}
.historial-header i { color: var(--accent-toast); }
.historial-table { width: 100%; border-collapse: collapse; }
.historial-table th {
    padding: 10px 16px;
    font-size: 0.68rem;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #aaa;
    font-weight: 700;
    background: #fafafa;
    text-align: left;
    border-bottom: 1px solid #f0f0f0;
}
.historial-table td {
    padding: 12px 16px;
    font-size: 0.82rem;
    border-bottom: 1px solid #f8f8f8;
    color: #444;
}
.historial-table tr:last-child td { border-bottom: none; }
.historial-table tr:hover td { background: #fafbff; }
@media (max-width: 640px) {
    .historial-table thead { display: none; }
    .historial-table td {
        display: block;
        padding: 6px 16px;
    }
    .historial-table td:first-child { padding-top: 14px; font-weight:700; }
    .historial-table td:last-child { padding-bottom: 14px; }
    .historial-table td::before {
        content: attr(data-label) ": ";
        font-weight: 700;
        color: #aaa;
        font-size: 0.7rem;
        text-transform: uppercase;
    }
    .historial-table tr { border-bottom: 1px solid #f0f0f0; display:block; }
}

/* ── Éxito modal ── */
.success-overlay {
    position: fixed; top:0; left:0; width:100%; height:100%;
    background: rgba(0,0,0,0.45);
    display: flex; align-items: center; justify-content: center;
    z-index: 9999;
    display: none;
    padding: 20px;
    box-sizing: border-box;
}
.success-box {
    background: white;
    border-radius: 24px;
    padding: 40px 36px;
    text-align: center;
    max-width: 400px;
    width: 100%;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    animation: popIn 0.35s cubic-bezier(0.34,1.26,0.64,1);
}
@keyframes popIn {
    from { opacity:0; transform:scale(0.8); }
    to   { opacity:1; transform:scale(1); }
}
.success-icon { font-size: 3.5rem; margin-bottom: 14px; }
.success-box h3 { font-family:'Merriweather'; color:#1F1F1F; margin-bottom:8px; font-size:1.2rem; }
.success-box p { color:#888; font-size:0.85rem; line-height:1.5; margin-bottom:20px; }
.success-details {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 14px;
    text-align: left;
    font-size: 0.82rem;
    color: #555;
    margin-bottom: 20px;
}
.success-details span { font-weight:700; color:#1F1F1F; }

/* ── Responsivo general ── */
@media (max-width: 480px) {
    .carrito-panel { border-radius: 16px; }
    .merma-form { padding: 14px; }
    .btn-registrar { font-size: 0.85rem; padding: 12px; }
}
</style>

<?php include 'includes/admin_nav.php'; ?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="fas fa-exclamation-triangle" style="color:var(--accent-toast);margin-right:10px;"></i>Reporte de Merma</h1>
        <p>Registra productos dañados o perdidos. Solo para supervisores y administradores.</p>
    </div>
</div>

<div class="merma-layout">

    <!-- ─── COLUMNA IZQUIERDA: Catálogo ─── -->
    <div>
        <!-- Buscador -->
        <div class="merma-search-bar">
            <i class="fas fa-search"></i>
            <input type="text" id="searchMerma" placeholder="Buscar producto por nombre..." oninput="filtrarProductos()">
        </div>

        <!-- Tabs de categoría -->
        <div class="cat-tabs" id="catTabs">
            <button class="cat-tab active" onclick="filtrarCat('todas', this)">Todas</button>
            <?php foreach (array_keys($porCategoria) as $cat): ?>
                <button class="cat-tab" onclick="filtrarCat('<?php echo htmlspecialchars($cat); ?>', this)">
                    <?php echo htmlspecialchars($cat); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Grid de productos -->
        <div class="productos-merma-grid" id="productosGrid">
            <?php foreach ($productos as $prod):
                $variantes = $allVariantes[$prod['id']] ?? [];
                $esSimple  = empty($variantes);
                $precio    = (float)$prod['precio'];
            ?>
            <div class="prod-merma-card"
                 id="card-<?php echo $prod['id']; ?>"
                 data-nombre="<?php echo htmlspecialchars(strtolower($prod['nombre'])); ?>"
                 data-cat="<?php echo htmlspecialchars($prod['categoria']); ?>">

                <img class="prod-merma-img"
                     src="../assets/img/<?php echo htmlspecialchars($prod['imagen'] ?: 'default_pan.png'); ?>"
                     onerror="this.src='../assets/img/default_pan.png'"
                     alt="<?php echo htmlspecialchars($prod['nombre']); ?>"
                     loading="lazy">

                <div class="prod-merma-body">
                    <div class="prod-merma-name"><?php echo htmlspecialchars($prod['nombre']); ?></div>
                    <div class="prod-merma-cat"><?php echo htmlspecialchars($prod['categoria']); ?></div>

                    <?php if (!$esSimple): ?>
                    <!-- Selector de variante -->
                    <select class="var-select" id="var-<?php echo $prod['id']; ?>"
                            onchange="actualizarPrecioVariante(<?php echo $prod['id']; ?>)">
                        <?php foreach ($variantes as $v): ?>
                            <option value="<?php echo $v['id']; ?>"
                                    data-precio="<?php echo $v['precio']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($v['nombre']); ?>">
                                <?php echo htmlspecialchars($v['nombre']); ?> — Q<?php echo number_format($v['precio'],2); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>

                    <div class="prod-merma-price" id="price-<?php echo $prod['id']; ?>">
                        Q<?php echo number_format($precio, 2); ?>
                    </div>

                    <div class="qty-row">
                        <button class="qty-btn" onclick="cambiarQty(<?php echo $prod['id']; ?>, -1)" title="Quitar uno">−</button>
                        <input class="qty-input" type="number" min="0" value="0"
                               id="qty-<?php echo $prod['id']; ?>"
                               oninput="sincronizarCarrito(<?php echo $prod['id']; ?>)"
                               data-pid="<?php echo $prod['id']; ?>"
                               data-nombre="<?php echo htmlspecialchars($prod['nombre']); ?>"
                               data-precio="<?php echo $precio; ?>"
                               data-simple="<?php echo $esSimple ? '1' : '0'; ?>"
                               data-variantes='<?php echo htmlspecialchars(json_encode($variantes)); ?>'>
                        <button class="qty-btn" onclick="cambiarQty(<?php echo $prod['id']; ?>, 1)" title="Agregar uno">+</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Historial reciente -->
        <?php if (!empty($historial)): ?>
        <div class="historial-card">
            <div class="historial-header">
                <i class="fas fa-history"></i>
                Últimas Mermas Registradas
            </div>
            <div style="overflow-x:auto;">
                <table class="historial-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pedido</th>
                            <th>Supervisor</th>
                            <th>Ítems</th>
                            <th>Total Merma</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historial as $h): ?>
                        <tr>
                            <td data-label="ID"><strong>#<?php echo $h['id']; ?></strong></td>
                            <td data-label="Pedido">Pedido #<?php echo $h['pedido_id']; ?></td>
                            <td data-label="Supervisor"><?php echo htmlspecialchars($h['supervisor_nombre']); ?></td>
                            <td data-label="Ítems"><?php echo $h['num_items']; ?> producto(s)</td>
                            <td data-label="Total Merma" style="color:#e74c3c;font-weight:700;">
                                Q<?php echo number_format($h['total_merma'], 2); ?>
                            </td>
                            <td data-label="Fecha" style="color:#aaa;">
                                <?php echo date('d/m/Y H:i', strtotime($h['fecha'])); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ─── COLUMNA DERECHA: Carrito + Formulario ─── -->
    <div>
        <div class="carrito-panel">
            <!-- Header carrito -->
            <div class="carrito-header">
                <i class="fas fa-trash-alt"></i>
                <span>Merma a Registrar</span>
                <div class="cart-badge" id="cartBadge">0</div>
            </div>

            <!-- Items del carrito -->
            <div class="carrito-items" id="carritoItems">
                <div class="carrito-empty" id="carritoEmpty">
                    <i class="fas fa-boxes"></i>
                    <p>Selecciona productos del catálogo para registrar la merma.</p>
                </div>
            </div>

            <!-- Total -->
            <div class="carrito-total">
                <span>Total Merma:</span>
                <span id="cartTotal">Q0.00</span>
            </div>

            <!-- Formulario de confirmación -->
            <div class="merma-form">

                <!-- Pedido -->
                <div class="merma-field">
                    <label><i class="fas fa-hashtag"></i> Número de Pedido *</label>
                    <input type="number" id="pedidoId" min="1" placeholder="Ej: 145"
                           oninput="verificarPedido()">
                    <div class="pedido-status" id="pedidoStatus"></div>
                </div>

                <!-- Notas -->
                <div class="merma-field">
                    <label><i class="fas fa-sticky-note"></i> Notas / Causa de la Merma</label>
                    <textarea id="notasMerma" rows="3" placeholder="Ej: Producto dañado en camino a entrega, caja de pan aplastada por accidente..."></textarea>
                </div>

                <!-- Checkbox confirmación -->
                <label class="confirm-check">
                    <input type="checkbox" id="checkConfirm" onchange="toggleBtn()">
                    <p>Confirmo que esta merma debe existir y que la información es correcta. Esta acción quedará registrada con mi nombre, fecha y hora.</p>
                </label>

                <!-- Botón -->
                <button class="btn-registrar" id="btnRegistrar" onclick="registrarMerma()" disabled>
                    <i class="fas fa-save"></i> Registrar Merma
                </button>
            </div>
        </div>
    </div>

</div>

<!-- Modal Éxito -->
<div class="success-overlay" id="successOverlay">
    <div class="success-box">
        <div class="success-icon">✅</div>
        <h3>¡Merma Registrada!</h3>
        <p>La merma ha sido guardada exitosamente en el sistema.</p>
        <div class="success-details" id="successDetails"></div>
        <button class="btn-registrar" onclick="cerrarExito()" style="background:var(--text-black);">
            <i class="fas fa-plus"></i> Registrar Otra Merma
        </button>
    </div>
</div>

<script>
// ── Estado global del carrito ──────────────────────────────────────────────
const carrito = {}; // { pid_varId: { prod_id, variante_id, nombre, variante_nombre, precio, cantidad } }
let pedidoValido = false;
let verificandoPedido = null;

// ── Actualizar precio al cambiar variante ──────────────────────────────────
function actualizarPrecioVariante(pid) {
    const sel = document.getElementById('var-' + pid);
    const opt = sel.options[sel.selectedIndex];
    const precio = parseFloat(opt.getAttribute('data-precio'));
    document.getElementById('price-' + pid).textContent = 'Q' + precio.toFixed(2);
    // Si ya está en el carrito, actualizar su precio/variante
    sincronizarCarrito(pid);
}

// ── Cambiar cantidad con botones ─────────────────────────────────────────
function cambiarQty(pid, delta) {
    const input = document.getElementById('qty-' + pid);
    const val   = Math.max(0, (parseInt(input.value) || 0) + delta);
    input.value = val;
    sincronizarCarrito(pid);
}

// ── Sincronizar carrito al cambiar input ──────────────────────────────────
function sincronizarCarrito(pid) {
    const input   = document.getElementById('qty-' + pid);
    const qty     = Math.max(0, parseInt(input.value) || 0);
    input.value   = qty;

    const esSimple = input.getAttribute('data-simple') === '1';
    let varId = null, varNombre = null, precio;

    if (!esSimple) {
        const sel   = document.getElementById('var-' + pid);
        const opt   = sel ? sel.options[sel.selectedIndex] : null;
        varId       = opt ? parseInt(opt.value) : null;
        varNombre   = opt ? opt.getAttribute('data-nombre') : null;
        precio      = opt ? parseFloat(opt.getAttribute('data-precio')) : parseFloat(input.getAttribute('data-precio'));
    } else {
        precio = parseFloat(document.getElementById('price-' + pid).textContent.replace('Q',''));
    }

    const key = pid + '_' + (varId ?? 0);

    if (qty > 0) {
        carrito[key] = {
            prod_id: pid,
            variante_id: varId,
            nombre: input.getAttribute('data-nombre'),
            variante_nombre: varNombre,
            precio: precio,
            cantidad: qty,
        };
    } else {
        delete carrito[key];
    }

    // Actualizar borde visual de la card
    const card = document.getElementById('card-' + pid);
    const tengoAlgo = Object.keys(carrito).some(k => k.startsWith(pid + '_'));
    card.classList.toggle('in-cart', tengoAlgo);

    renderCarrito();
}

// ── Renderizar el carrito ─────────────────────────────────────────────────
function renderCarrito() {
    const items   = Object.values(carrito);
    const wrapper = document.getElementById('carritoItems');
    const empty   = document.getElementById('carritoEmpty');
    const badge   = document.getElementById('cartBadge');
    const totalEl = document.getElementById('cartTotal');

    let total = 0;
    let html  = '';

    if (items.length === 0) {
        empty.style.display = 'block';
        wrapper.innerHTML   = '';
        wrapper.appendChild(empty);
        badge.textContent   = '0';
        totalEl.textContent = 'Q0.00';
        toggleBtn();
        return;
    }

    empty.style.display = 'none';

    items.forEach(item => {
        const sub = item.precio * item.cantidad;
        total    += sub;
        const key = item.prod_id + '_' + (item.variante_id ?? 0);
        html += `
            <div class="carrito-item" id="ci-${key}">
                <div class="ci-info">
                    <div class="ci-name">${item.nombre}</div>
                    ${item.variante_nombre ? `<div class="ci-var">→ ${item.variante_nombre}</div>` : ''}
                    <div class="ci-sub">Q${sub.toFixed(2)}</div>
                </div>
                <div class="ci-qty">×${item.cantidad}</div>
                <i class="fas fa-times ci-del" onclick="eliminarItem('${key}', ${item.prod_id})" title="Quitar"></i>
            </div>`;
    });

    wrapper.innerHTML = html;
    badge.textContent = items.length;
    totalEl.textContent = 'Q' + total.toFixed(2);
    toggleBtn();
}

// ── Eliminar ítem del carrito ─────────────────────────────────────────────
function eliminarItem(key, pid) {
    delete carrito[key];
    const input = document.getElementById('qty-' + pid);
    if (input) input.value = 0;
    const card = document.getElementById('card-' + pid);
    if (card) {
        const tengoAlgo = Object.keys(carrito).some(k => k.startsWith(pid + '_'));
        card.classList.toggle('in-cart', tengoAlgo);
    }
    renderCarrito();
}

// ── Verificar pedido con debounce ─────────────────────────────────────────
function verificarPedido() {
    clearTimeout(verificandoPedido);
    const pid = parseInt(document.getElementById('pedidoId').value);
    const statusEl = document.getElementById('pedidoStatus');

    if (!pid || pid <= 0) {
        statusEl.className = 'pedido-status';
        pedidoValido = false;
        toggleBtn();
        return;
    }

    statusEl.className = 'pedido-status';
    statusEl.textContent = '⏳ Verificando...';
    statusEl.style.display = 'block';

    verificandoPedido = setTimeout(async () => {
        try {
            const resp = await fetch('api_verificar_pedido.php?id=' + pid);
            const data = await resp.json();
            if (data.ok) {
                statusEl.className  = 'pedido-status ok';
                statusEl.textContent = `✓ Pedido #${pid} — ${data.cliente}`;
                pedidoValido = true;
            } else {
                statusEl.className  = 'pedido-status err';
                statusEl.textContent = `✗ El pedido #${pid} no existe`;
                pedidoValido = false;
            }
        } catch {
            statusEl.className  = 'pedido-status err';
            statusEl.textContent = 'Error al verificar';
            pedidoValido = false;
        }
        toggleBtn();
    }, 600);
}

// ── Habilitar/deshabilitar botón ──────────────────────────────────────────
function toggleBtn() {
    const btn     = document.getElementById('btnRegistrar');
    const checked = document.getElementById('checkConfirm').checked;
    const tieneItems = Object.keys(carrito).length > 0;
    btn.disabled  = !(checked && pedidoValido && tieneItems);
}

// ── Enviar merma ──────────────────────────────────────────────────────────
async function registrarMerma() {
    const btn   = document.getElementById('btnRegistrar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

    const items = Object.values(carrito).map(i => ({
        producto_id:    i.prod_id,
        variante_id:    i.variante_id,
        nombre:         i.nombre,
        variante_nombre: i.variante_nombre,
        precio:         i.precio,
        cantidad:       i.cantidad,
    }));

    try {
        const resp = await fetch('procesar_merma.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                pedido_id:  parseInt(document.getElementById('pedidoId').value),
                notas:      document.getElementById('notasMerma').value,
                confirmado: document.getElementById('checkConfirm').checked,
                items,
            })
        });
        const data = await resp.json();

        if (data.ok) {
            document.getElementById('successDetails').innerHTML = `
                <div style="margin-bottom:6px;">🆔 <span>Merma ID:</span> #${data.merma_id}</div>
                <div style="margin-bottom:6px;">💰 <span>Total:</span> Q${parseFloat(data.total).toFixed(2)}</div>
                <div>🕐 <span>Fecha:</span> ${data.fecha}</div>`;
            document.getElementById('successOverlay').style.display = 'flex';
        } else {
            alert('❌ ' + data.msg);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Registrar Merma';
        }
    } catch {
        alert('Error de conexión. Intenta de nuevo.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Registrar Merma';
    }
}

// ── Cerrar modal éxito y resetear ─────────────────────────────────────────
function cerrarExito() {
    document.getElementById('successOverlay').style.display = 'none';
    // Resetear todo
    Object.keys(carrito).forEach(k => delete carrito[k]);
    document.querySelectorAll('.qty-input').forEach(i => i.value = 0);
    document.querySelectorAll('.prod-merma-card').forEach(c => c.classList.remove('in-cart'));
    document.getElementById('pedidoId').value   = '';
    document.getElementById('notasMerma').value = '';
    document.getElementById('checkConfirm').checked = false;
    document.getElementById('pedidoStatus').className = 'pedido-status';
    pedidoValido = false;
    renderCarrito();
    // Recargar historial
    location.reload();
}

// ── Filtrar por categoría ─────────────────────────────────────────────────
function filtrarCat(cat, el) {
    document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    document.querySelectorAll('.prod-merma-card').forEach(card => {
        const match = cat === 'todas' || card.getAttribute('data-cat') === cat;
        card.style.display = match ? '' : 'none';
    });
}

// ── Filtrar por búsqueda ──────────────────────────────────────────────────
function filtrarProductos() {
    const q = document.getElementById('searchMerma').value.toLowerCase();
    document.querySelectorAll('.prod-merma-card').forEach(card => {
        const nombre = card.getAttribute('data-nombre') || '';
        card.style.display = nombre.includes(q) ? '' : 'none';
    });
}
</script>

<?php include 'includes/admin_footer.php'; ?>
