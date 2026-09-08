<?php
$currentPage = 'productos';
$pageTitle = 'Inventario';
require '../includes/db.php';

// MENSAJES DE NOTIFICACIÓN
$msg = $_GET['msg'] ?? '';
$msgMap = [
    'creado'     => ['text' => '✅ Producto creado correctamente.',  'type' => 'success'],
    'actualizado'=> ['text' => '✅ Producto actualizado.',           'type' => 'success'],
    'eliminado'  => ['text' => '🗑️ Producto eliminado.',             'type' => 'warning'],
    'error'      => ['text' => '❌ Ocurrió un error.',               'type' => 'danger'],
];

// BÚSQUEDA + FILTROS
$search   = trim($_GET['buscar'] ?? '');
$catFilt  = $_GET['cat'] ?? 'todas';
$stockFilt = $_GET['stock_f'] ?? 'todos'; // 'todos' | 'agotado' | 'bajo' | 'disponible'

$params = [];
$where  = [];
if ($search !== '') {
    $where[]  = "(nombre LIKE ? OR descripcion LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($catFilt !== 'todas') {
    $where[]  = "categoria = ?";
    $params[] = $catFilt;
}
if ($stockFilt === 'agotado') {
    $where[] = "stock = 0";
} elseif ($stockFilt === 'bajo') {
    $where[] = "stock > 0 AND stock < 10";
} elseif ($stockFilt === 'disponible') {
    $where[] = "stock >= 10";
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Paginación
$perPage = 20;
$page    = max(1, (int)($_GET['pag'] ?? 1));
$offset  = ($page - 1) * $perPage;

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM productos $whereSQL");
$stmtCount->execute($params);
$totalRegistros = (int)$stmtCount->fetchColumn();
$totalPaginas   = (int)ceil($totalRegistros / $perPage);

$stmt = $pdo->prepare("SELECT * FROM productos $whereSQL ORDER BY id DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);


// KPIs globales (sin filtro)
$totalP     = $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
$agotados   = $pdo->query("SELECT COUNT(*) FROM productos WHERE stock = 0")->fetchColumn();
$bajoStock  = $pdo->query("SELECT COUNT(*) FROM productos WHERE stock > 0 AND stock < 10")->fetchColumn();
$disponibles= $pdo->query("SELECT COUNT(*) FROM productos WHERE stock >= 10")->fetchColumn();
$valorInv   = $pdo->query("SELECT SUM(precio * stock) FROM productos")->fetchColumn() ?: 0;

// Helper para construir URL de filtro conservando parámetros actuales
function kpiUrl($stock_f, $current) {
    $q = $_GET;
    unset($q['msg']);
    $q['stock_f'] = ($current === $stock_f) ? 'todos' : $stock_f;  // toggle
    return '?' . http_build_query($q);
}

// Categorías únicas para el filtro
$cats = $pdo->query("SELECT DISTINCT categoria FROM productos ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);

include 'includes/admin_header.php';
include 'includes/admin_nav.php';
?>

<style>
/* ---- TOAST DE NOTIFICACIÓN ---- */
.toast-notify {
    position: fixed; top: 25px; right: 25px; z-index: 9999;
    background: white; border-radius: 14px; padding: 16px 22px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    display: flex; align-items: center; gap: 12px;
    font-size: 0.9rem; font-weight: 600;
    animation: toastIn 0.4s cubic-bezier(0.34,1.26,0.64,1) forwards;
    border-left: 4px solid var(--accent-toast);
    max-width: 360px;
}
@keyframes toastIn {
    from { opacity:0; transform: translateX(60px); }
    to   { opacity:1; transform: translateX(0); }
}
.toast-out { animation: toastOut 0.3s ease forwards; }
@keyframes toastOut {
    to { opacity:0; transform: translateX(60px); }
}

/* ---- BARRA BÚSQUEDA ---- */
.search-bar {
    display: flex; gap: 10px; flex-wrap: wrap; align-items: center;
    background: white; border-radius: 14px; padding: 14px 20px;
    box-shadow: var(--shadow); margin-bottom: 25px;
}
.search-input {
    flex: 1; min-width: 200px; padding: 10px 16px;
    border: 1px solid #eee; border-radius: 10px;
    font-family: 'Poppins'; font-size: 0.9rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.search-input:focus { outline:none; border-color: var(--accent-toast); box-shadow: 0 0 0 3px rgba(217,140,69,.15); }

.cat-filter-btn {
    padding: 8px 16px; border-radius: 50px;
    border: 1px solid #eee; background: white;
    font-family: 'Poppins'; font-size: 0.8rem; font-weight: 600;
    cursor: pointer; color: #888;
    transition: all 0.2s ease;
}
.cat-filter-btn:hover, .cat-filter-btn.active {
    background: var(--text-black); color: white; border-color: var(--text-black);
}

/* ---- CARD DE PRODUCTO ---- */
.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}
.product-card {
    background: white; border-radius: 20px;
    box-shadow: var(--shadow); overflow: hidden;
    transition: transform 0.25s cubic-bezier(0.34,1.26,0.64,1), box-shadow 0.25s ease;
    display: flex; flex-direction: column;
}
.product-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 18px 45px rgba(0,0,0,0.12);
}
.product-img-wrap {
    position: relative; height: 170px; overflow: hidden; background: var(--bg-cream);
}
.product-img-wrap img {
    width: 100%; height: 100%; object-fit: cover;
    transition: transform 0.4s ease;
}
.product-card:hover .product-img-wrap img { transform: scale(1.06); }

.prod-badge-overlay {
    position: absolute; top: 10px; left: 10px;
    display: flex; gap: 6px; flex-wrap: wrap;
}
.prod-actions-overlay {
    position: absolute; top: 10px; right: 10px;
    display: flex; gap: 6px; opacity: 0;
    transition: opacity 0.25s ease;
}
.product-card:hover .prod-actions-overlay { opacity: 1; }

.icon-btn {
    width: 34px; height: 34px; border-radius: 50%;
    border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem;
    transition: transform 0.2s ease;
    text-decoration: none;
}
.icon-btn:hover { transform: scale(1.15); }
.icon-btn-edit   { background: white; color: var(--accent-toast); }
.icon-btn-delete { background: #ffebee; color: #e74c3c; }

.product-body { padding: 16px; flex: 1; display: flex; flex-direction: column; }
.product-body h3 {
    font-family: 'Merriweather', serif; font-size: 0.95rem;
    margin-bottom: 6px; line-height: 1.3;
}
.product-body .prod-desc {
    color: #999; font-size: 0.78rem; line-height: 1.4;
    flex: 1;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    overflow: hidden; line-clamp: 2;
}
.product-footer {
    display: flex; justify-content: space-between; align-items: center;
    padding: 12px 16px; border-top: 1px solid #f8f8f8;
    background: #fdfdfd;
}
.product-price { font-weight: 800; font-size: 1.1rem; color: var(--text-black); }
.product-price span { color: var(--accent-toast); }

/* KPI clicables */
.kpi-card-link {
    text-decoration: none; color: inherit; display: flex; align-items: center;
    width: 100%; gap: 15px;
}
.kpi-card[data-filter] { cursor: pointer; }
.kpi-card[data-filter]:hover {
    border: 2px solid var(--accent-toast);
    box-shadow: 0 8px 30px rgba(217,140,69,0.15);
}
.kpi-card.filter-active {
    border: 2px solid var(--accent-toast);
    background: linear-gradient(135deg, #fffdf9, #fff8ee);
    box-shadow: 0 8px 30px rgba(217,140,69,0.2);
}
.kpi-card.filter-active .kpi-icon { transform: scale(1.1); }
.kpi-filter-hint {
    font-size: 0.65rem; color: #bbb; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.5px;
    margin-top: 2px;
}
.kpi-card.filter-active .kpi-filter-hint { color: var(--accent-toast); }

.confirm-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.6); z-index: 4000;
    display: none; align-items: center; justify-content: center;
    backdrop-filter: blur(4px);
}
.confirm-box {
    background: white; border-radius: 20px; padding: 35px;
    max-width: 420px; width: 90%; text-align: center;
    box-shadow: 0 25px 60px rgba(0,0,0,0.25);
    animation: modalIn 0.4s cubic-bezier(0.34,1.26,0.64,1);
}
@keyframes modalIn {
    from { opacity:0; transform: scale(0.85); }
    to   { opacity:1; transform: scale(1); }
}
.confirm-icon { font-size: 3rem; margin-bottom: 15px; }
.confirm-box h3 { font-family: 'Merriweather'; margin-bottom: 8px; }
.confirm-box p  { color: #999; font-size: 0.9rem; margin-bottom: 25px; }
.confirm-actions { display: flex; gap: 12px; justify-content: center; }
</style>

<?php if($msg && isset($msgMap[$msg])): ?>
<div class="toast-notify" id="toastMsg">
    <?php echo $msgMap[$msg]['text']; ?>
</div>
<script>
    setTimeout(() => {
        const t = document.getElementById('toastMsg');
        t.classList.add('toast-out');
        setTimeout(() => t.remove(), 300);
    }, 3500);
</script>
<?php endif; ?>

<div class="page-header">
    <div class="page-title">
        <h1>Inventario</h1>
        <p>Gestiona tus panes artesanales.</p>
    </div>
    <a href="agregar.php" class="btn-new">
        <i class="fas fa-plus"></i> Nuevo Producto
    </a>
</div>

<!-- KPIs del Inventario (clicables como filtros) -->
<div class="kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px,1fr));">

    <!-- Total: resetea filtro de stock -->
    <a href="?cat=<?php echo urlencode($catFilt); ?>&buscar=<?php echo urlencode($search); ?>&stock_f=todos"
       style="text-decoration:none;color:inherit;">
        <div class="kpi-card <?php echo $stockFilt==='todos'?'filter-active':''; ?>" data-filter="todos">
            <div class="kpi-icon"><i class="fas fa-bread-slice"></i></div>
            <div class="kpi-info">
                <h3>Total Productos</h3>
                <p><?php echo $totalP; ?></p>
                <div class="kpi-filter-hint"><?php echo $stockFilt==='todos'?'✓ Filtro activo':'Clic para ver todos'; ?></div>
            </div>
        </div>
    </a>

    <!-- Agotados -->
    <a href="<?php echo kpiUrl('agotado', $stockFilt); ?>" style="text-decoration:none;color:inherit;">
        <div class="kpi-card <?php echo $stockFilt==='agotado'?'filter-active':''; ?>" data-filter="agotado">
            <div class="kpi-icon" style="color:#e74c3c;background:#ffebee;"><i class="fas fa-times-circle"></i></div>
            <div class="kpi-info">
                <h3>Agotados</h3>
                <p><?php echo $agotados; ?></p>
                <div class="kpi-filter-hint"><?php echo $stockFilt==='agotado'?'✓ Filtro activo':'Clic para filtrar'; ?></div>
            </div>
        </div>
    </a>

    <!-- Bajo Stock -->
    <a href="<?php echo kpiUrl('bajo', $stockFilt); ?>" style="text-decoration:none;color:inherit;">
        <div class="kpi-card <?php echo $stockFilt==='bajo'?'filter-active':''; ?>" data-filter="bajo">
            <div class="kpi-icon" style="color:#f39c12;background:#fff8e1;"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="kpi-info">
                <h3>Bajo Stock</h3>
                <p><?php echo $bajoStock; ?></p>
                <div class="kpi-filter-hint"><?php echo $stockFilt==='bajo'?'✓ Filtro activo':'Clic para filtrar'; ?></div>
            </div>
        </div>
    </a>

    <!-- Disponibles -->
    <a href="<?php echo kpiUrl('disponible', $stockFilt); ?>" style="text-decoration:none;color:inherit;">
        <div class="kpi-card <?php echo $stockFilt==='disponible'?'filter-active':''; ?>" data-filter="disponible">
            <div class="kpi-icon" style="color:#27ae60;background:#eafaf1;"><i class="fas fa-check-circle"></i></div>
            <div class="kpi-info">
                <h3>Disponibles</h3>
                <p><?php echo $disponibles; ?></p>
                <div class="kpi-filter-hint"><?php echo $stockFilt==='disponible'?'✓ Filtro activo':'Clic para filtrar'; ?></div>
            </div>
        </div>
    </a>

</div>


<!-- BARRA DE BÚSQUEDA Y FILTROS -->
<form method="GET" class="search-bar" id="filterForm">
    <!-- Preservar filtro de stock al buscar/filtrar por categoria -->
    <input type="hidden" name="stock_f" value="<?php echo htmlspecialchars($stockFilt); ?>">
    <i class="fas fa-search" style="color:#ccc;"></i>
    <input type="text" name="buscar" id="searchInput" value="<?php echo htmlspecialchars($search); ?>"
           class="search-input" placeholder="Buscar por nombre o descripción..."
           autocomplete="off">

    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <a href="?cat=todas&stock_f=<?php echo $stockFilt; ?>" class="cat-filter-btn <?php echo $catFilt==='todas'?'active':''; ?>">Todos</a>
        <?php foreach($cats as $c): ?>
        <a href="?cat=<?php echo urlencode($c); ?>&buscar=<?php echo urlencode($search); ?>&stock_f=<?php echo $stockFilt; ?>"
           class="cat-filter-btn <?php echo $catFilt===$c?'active':''; ?>"><?php echo $c; ?></a>
        <?php endforeach; ?>
    </div>

    <span id="resultsCount" style="color:#ccc;font-size:0.8rem;margin-left:auto;"><?php echo count($productos); ?> resultado(s)</span>
</form>

<!-- GRID DE PRODUCTOS -->
<?php if(empty($productos)): ?>
<div style="text-align:center;padding:80px;color:#ccc;">
    <i class="fas fa-inbox" style="font-size:3rem;display:block;margin-bottom:15px;"></i>
    No hay productos que coincidan con tu búsqueda.
</div>
<?php else: ?>
<div class="product-grid">
    <?php foreach($productos as $prod): ?>
    <div class="product-card">

        <div class="product-img-wrap">
            <img src="../assets/img/<?php echo htmlspecialchars($prod['imagen']); ?>" alt="<?php echo htmlspecialchars($prod['nombre']); ?>">
            
            <div class="prod-badge-overlay">
                <?php if($prod['destacado']): ?>
                    <span class="badge" style="background:rgba(255,255,255,.9);color:#d98c45;font-size:0.65rem;">★ INICIO</span>
                <?php endif; ?>
                <?php if(!empty($prod['oferta'])): ?>
                    <span class="badge" style="background:#e74c3c;color:white;font-size:0.65rem;">🔥 OFERTA</span>
                <?php endif; ?>
            </div>

            <div class="prod-actions-overlay">
                <a href="editar.php?id=<?php echo $prod['id']; ?>" class="icon-btn icon-btn-edit" title="Editar">
                    <i class="fas fa-pencil-alt"></i>
                </a>
                <button class="icon-btn icon-btn-delete" onclick="confirmarEliminar(<?php echo $prod['id']; ?>, '<?php echo addslashes($prod['nombre']); ?>')" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>

        <div class="product-body">
            <div style="margin-bottom:6px;">
                <span class="badge" style="background:#f4f4f4;color:#888;font-size:0.65rem;"><?php echo strtoupper($prod['categoria']); ?></span>
            </div>
            <h3><?php echo htmlspecialchars($prod['nombre']); ?></h3>
            <p class="prod-desc"><?php echo htmlspecialchars($prod['descripcion']); ?></p>
        </div>

        <div class="product-footer">
            <div class="product-price"><span>Q</span><?php echo number_format($prod['precio'],2); ?></div>
            <?php
                $s = (int)$prod['stock'];
                if($s <= 0) echo '<span class="badge badge-danger">AGOTADO</span>';
                elseif($s < 10) echo '<span class="badge badge-pending">⚠ '.$s.' uds</span>';
                else echo '<span class="badge badge-success">'.$s.' uds</span>';
            ?>
        </div>

    </div>
    <?php endforeach; ?>
</div>

<?php if($totalPaginas > 1): ?>
<div style="display:flex;justify-content:center;align-items:center;gap:8px;margin-top:25px;margin-bottom:30px;flex-wrap:wrap;">
    <?php
        $baseUrl = "?cat=".urlencode($catFilt)."&buscar=".urlencode($search)."&stock_f=".urlencode($stockFilt);
        
        $startPage = max(1, $page - 2);
        $endPage = min($totalPaginas, $page + 2);
        
        if ($page > 1):
    ?>
        <a href="<?php echo $baseUrl.'&pag='.($page - 1); ?>" class="btn-new btn-outline" style="padding:8px 14px; font-size:0.85rem;"><i class="fas fa-chevron-left"></i> Anterior</a>
    <?php endif; ?>
    
    <?php if ($startPage > 1): ?>
        <a href="<?php echo $baseUrl.'&pag=1'; ?>" style="padding:8px 14px;border-radius:10px;text-decoration:none;font-weight:700;font-size:0.85rem;background:white;color:#888;border:1.5px solid #eee;">1</a>
        <?php if ($startPage > 2): ?>
            <span style="color:#aaa;padding:8px 4px;">...</span>
        <?php endif; ?>
    <?php endif; ?>

    <?php
        for($i = $startPage; $i <= $endPage; $i++):
            $isActive = ($i === $page);
    ?>
    <a href="<?php echo $baseUrl.'&pag='.$i; ?>"
       style="padding:8px 14px;border-radius:10px;text-decoration:none;font-weight:700;font-size:0.85rem;
              <?php echo $isActive ? 'background:var(--text-black);color:white;box-shadow:var(--shadow);' : 'background:white;color:#888;border:1.5px solid #eee;'; ?>">
        <?php echo $i; ?>
    </a>
    <?php endfor; ?>
    
    <?php if ($endPage < $totalPaginas): ?>
        <?php if ($endPage < $totalPaginas - 1): ?>
            <span style="color:#aaa;padding:8px 4px;">...</span>
        <?php endif; ?>
        <a href="<?php echo $baseUrl.'&pag='.$totalPaginas; ?>" style="padding:8px 14px;border-radius:10px;text-decoration:none;font-weight:700;font-size:0.85rem;background:white;color:#888;border:1.5px solid #eee;"><?php echo $totalPaginas; ?></a>
    <?php endif; ?>
    
    <?php if ($page < $totalPaginas): ?>
        <a href="<?php echo $baseUrl.'&pag='.($page + 1); ?>" class="btn-new btn-outline" style="padding:8px 14px; font-size:0.85rem;">Siguiente <i class="fas fa-chevron-right"></i></a>
    <?php endif; ?>

    <span style="color:#aaa;font-size:0.8rem;margin-left:10px;">(<?php echo $totalRegistros; ?> resultados)</span>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- CONFIRM MODAL -->
<div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-box">
        <div class="confirm-icon">🗑️</div>
        <h3>¿Eliminar producto?</h3>
        <p id="confirmProdName">¿Estás seguro de que deseas eliminar este producto? Esta acción no se puede deshacer.</p>
        <div class="confirm-actions">
            <button onclick="closeConfirm()" class="btn-new btn-outline" style="padding:10px 20px;">Cancelar</button>
            <form action="borrar.php" method="POST" style="margin:0;">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" id="confirmDeleteId" value="">
                <button type="submit" class="btn-new" style="padding:10px 20px;background:#e74c3c;border:none;">Sí, eliminar</button>
            </form>
        </div>
    </div>
</div>

<script>
// ── BÚSQUEDA CON DEBOUNCE (sin perder foco) ────────────────────────────────
(function() {
    const input = document.getElementById('searchInput');
    if (!input) return;

    let timer = null;

    input.addEventListener('input', function() {
        clearTimeout(timer);
        timer = setTimeout(() => {
            // Construir URL conservando los filtros actuales
            const form   = document.getElementById('filterForm');
            const params = new URLSearchParams(new FormData(form));
            params.set('buscar', input.value);
            params.delete('pag'); // Volver a página 1 al buscar

            // Actualizar la URL sin recargar (para que el botón Atrás funcione)
            const newUrl = window.location.pathname + '?' + params.toString();
            history.replaceState(null, '', newUrl);

            // Fetch de los resultados filtrados
            fetch(newUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc    = parser.parseFromString(html, 'text/html');

                    // Reemplazar la cuadrícula de productos
                    const newGrid  = doc.querySelector('.product-grid, .admin-empty-state, [data-products-container]');
                    const curGrid  = document.querySelector('.product-grid');

                    // Reemplazar desde el grid hasta antes de la paginación
                    const main = document.querySelector('.main-wrapper') || document.querySelector('main') || document.body;

                    // Extraer bloque entre el form y el confirm-overlay
                    const newBlock  = doc.getElementById('productsBlock');
                    const curBlock  = document.getElementById('productsBlock');
                    if (newBlock && curBlock) {
                        curBlock.innerHTML = newBlock.innerHTML;
                    }

                    // Actualizar contador
                    const newCount  = doc.getElementById('resultsCount');
                    const curCount  = document.getElementById('resultsCount');
                    if (newCount && curCount) curCount.textContent = newCount.textContent;
                })
                .catch(() => {
                    // Fallback: submit normal si fetch falla
                    document.getElementById('filterForm').submit();
                });
        }, 350); // 350ms debounce
    });

    // Al pulsar Enter, enviar el formulario normal
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            clearTimeout(timer);
            document.getElementById('filterForm').submit();
        }
    });
})();

// ── CONFIRMAR ELIMINAR ────────────────────────────────────────────────────
function confirmarEliminar(id, nombre) {
    document.getElementById('confirmProdName').innerText = `¿Eliminar "${nombre}"? Esta acción no se puede deshacer.`;
    document.getElementById('confirmDeleteId').value = id;
    const ov = document.getElementById('confirmOverlay');
    ov.style.display = 'flex';
}
function closeConfirm() {
    document.getElementById('confirmOverlay').style.display = 'none';
}
document.getElementById('confirmOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeConfirm();
});
document.addEventListener('keydown', e => { if(e.key === 'Escape') closeConfirm(); });
</script>

<?php include 'includes/admin_footer.php'; ?>