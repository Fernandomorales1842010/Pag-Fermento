<?php
$currentPage = 'pedidos';
$pageTitle   = 'Gestión de Pedidos';
require '../includes/db.php';
include 'includes/admin_header.php';
include 'includes/admin_nav.php';

// ── Filtros vista principal ─────────────────────────────────────────────────
$fEstado = $_GET['estado'] ?? 'todos';
$search  = trim($_GET['buscar'] ?? '');
$allowed = ['todos','pendiente','completado','cancelado','en_camino','preparando','entregado','pendiente_confirmacion'];
if (!in_array($fEstado, $allowed)) $fEstado = 'todos';

// Ordenamiento por columna
$sortCols    = ['fecha' => 'fecha', 'total' => 'total', 'cliente' => 'nombre_cliente'];
$sortBy      = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sortCols) ? $_GET['sort'] : 'fecha';
$sortDir     = (isset($_GET['dir']) && $_GET['dir'] === 'asc') ? 'ASC' : 'DESC';
$sortDirNext = ($sortDir === 'DESC') ? 'asc' : 'desc'; // para toggle
$sortCol     = $sortCols[$sortBy];

// Paginación
$perPage  = 20;
$page     = max(1, (int)($_GET['pag'] ?? 1));
$offset   = ($page - 1) * $perPage;

$params = []; $where = [];
if ($fEstado !== 'todos') { $where[] = "estado = ?"; $params[] = $fEstado; }
if ($search !== '')       { $where[] = "(nombre_cliente LIKE ? OR telefono LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
$whereSQL = $where ? 'WHERE '.implode(' AND ',$where) : '';

// Total para paginación
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM pedidos $whereSQL");
$stmtCount->execute($params);
$totalRegistros = (int)$stmtCount->fetchColumn();
$totalPaginas   = (int)ceil($totalRegistros / $perPage);

$stmt = $pdo->prepare("SELECT * FROM pedidos $whereSQL ORDER BY $sortCol $sortDir LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── KPIs ──────────────────────────────────────────────────────────────────
$totalP         = $pdo->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();
$pendiente      = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado='pendiente'")->fetchColumn();
$completo       = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado='completado'")->fetchColumn();
$cancelado      = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado='cancelado'")->fetchColumn();
$ventasTot      = $pdo->query("SELECT COALESCE(SUM(total),0) FROM pedidos WHERE estado='completado'")->fetchColumn();
$pedsPendientes = (int)$pendiente;

// ── TODOS los pedidos para el selector del reporte ───────────────────────
// Se limita a los últimos 500 pedidos para no agotar la memoria del servidor.
$todosPedidos = $pdo->query("SELECT * FROM pedidos ORDER BY fecha DESC LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);
$reporteData  = [];

if (!empty($todosPedidos)) {
    // ── Solución N+1: traer TODOS los detalles en una sola query JOIN ─────
    $pedidoIds = implode(',', array_map('intval', array_column($todosPedidos, 'id')));
    $stmtDetalles = $pdo->query("SELECT * FROM detalles_pedido WHERE pedido_id IN ($pedidoIds)");
    $todosDetalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);

    // Agrupar detalles por pedido_id para acceso O(1)
    $detallesPorPedido = [];
    foreach ($todosDetalles as $det) {
        $detallesPorPedido[$det['pedido_id']][] = $det;
    }

    // Construir $reporteData sin ninguna query adicional
    foreach ($todosPedidos as $tp) {
        $tpItems = $detallesPorPedido[$tp['id']] ?? [];
        $reporteData[$tp['id']] = [
            'id'        => $tp['id'],
            'cliente'   => $tp['nombre_cliente'],
            'telefono'  => $tp['telefono']       ?? '—',
            'direccion' => $tp['direccion_envio'] ?? '—',
            'total'     => (float)$tp['total'],
            'estado'    => $tp['estado'],
            'fecha'     => date('d/m/Y', strtotime($tp['fecha'])),
            'hora'      => date('H:i',   strtotime($tp['fecha'])),
            'impreso'   => date('d/m/Y — H:i'),
            'items'     => array_map(fn($it) => [
                'nombre'   => $it['nombre_producto'],
                'cantidad' => (int)$it['cantidad'],
                'precio'   => (float)($it['precio_unitario'] ?? 0),
                'subtotal' => round(((float)($it['precio_unitario'] ?? 0) * (int)($it['cantidad'] ?? 0)), 2),
            ], $tpItems),
            'total_uds' => array_sum(array_column($tpItems, 'cantidad')),
        ];
    }
}

// ── Logo base64 ───────────────────────────────────────────────────────────
$_lp    = __DIR__ . '/../assets/img/logo_fermento.png';
$_lb64  = file_exists($_lp) ? 'data:image/png;base64,'.base64_encode(file_get_contents($_lp)) : '';

function estadoBadge($e) {
    $map = [
        'pendiente'             => ['badge-pending', '⏳ Pendiente'],
        'preparando'            => ['badge-pending', '🧑‍🍳 Preparando'],
        'en_camino'             => ['badge-pending', '🚚 En Camino'],
        'entregado'             => ['badge-success', '📦 Entregado'],
        'completado'            => ['badge-success', '✅ Completado'],
        'cancelado'             => ['badge-danger',  '❌ Cancelado'],
        'pendiente_confirmacion'=> ['badge-pending', '🔄 Por Confirmar']
    ];
    $d = $map[strtolower($e)] ?? ['badge-pending',$e];
    return "<span class=\"badge {$d[0]}\">{$d[1]}</span>";
}
?>

<?php
// ── Toast de notificación ─────────────────────────────────────────────────
$msg = $_GET['msg'] ?? '';
$msgMap = [
    'estado_ok'  => ['text' => '✅ Estado del pedido actualizado correctamente.', 'type' => 'success'],
    'ok'         => ['text' => '✅ Operación completada.',                          'type' => 'success'],
    'error'      => ['text' => '❌ Ocurrió un error. Intenta de nuevo.',             'type' => 'danger'],
];
?>

<?php if($msg && isset($msgMap[$msg])): ?>
<div class="toast-notify" id="toastMsg">
    <?php echo $msgMap[$msg]['text']; ?>
</div>
<script>
    setTimeout(() => {
        const t = document.getElementById('toastMsg');
        if(t) { t.classList.add('toast-out'); setTimeout(() => t.remove(), 300); }
    }, 4000);
</script>
<?php endif; ?>

<style>
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
@keyframes toastIn  { from { opacity:0; transform: translateX(60px); } to { opacity:1; transform: translateX(0); } }
.toast-out { animation: toastOut 0.3s ease forwards; }
@keyframes toastOut { to { opacity:0; transform: translateX(60px); } }
</style>

<style>
/* Estilos tabla principal */
.orders-topbar { display:flex; gap:12px; flex-wrap:wrap; align-items:center; background:white; border-radius:14px; padding:14px 20px; box-shadow:var(--shadow); margin-bottom:24px; }
.search-input { flex:1; min-width:200px; padding:10px 16px; border:1.5px solid #eee; border-radius:10px; font-family:'Poppins'; font-size:.9rem; transition:border-color .2s; }
.search-input:focus { outline:none; border-color:var(--accent-toast); box-shadow:0 0 0 3px rgba(217,140,69,.15); }
.state-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:24px; }
.state-tab { padding:9px 18px; border-radius:50px; font-size:.82rem; font-weight:700; text-decoration:none; color:#888; background:white; border:1.5px solid #eee; box-shadow:var(--shadow); transition:all .2s; display:flex; align-items:center; gap:7px; }
.state-tab:hover { transform:translateY(-2px); border-color:var(--accent-toast); color:var(--accent-toast); }
.state-tab.active-tab { background:var(--text-black); color:white; border-color:var(--text-black); }
.state-tab.active-pend { background:#f1c40f; color:#5d4e00; border-color:#f1c40f; }
.state-tab.active-comp { background:#27ae60; color:white; border-color:#27ae60; }
.state-tab.active-canc { background:#e74c3c; color:white; border-color:#e74c3c; }
.orders-card { background:white; border-radius:20px; overflow:hidden; box-shadow:var(--shadow); }
.orders-card table { width:100%; border-collapse:collapse; }
.orders-card thead th { padding:14px 18px; background:#fafafa; font-size:.68rem; text-transform:uppercase; letter-spacing:.8px; color:#aaa; font-weight:700; border-bottom:1px solid #f0f0f0; text-align:left; }
.orders-card tbody td { padding:14px 18px; border-bottom:1px solid #f8f8f8; font-size:.9rem; vertical-align:middle; }
.orders-card tbody tr:last-child td { border-bottom:none; }
.orders-card tbody tr:hover td { background:#fdf8f2; }
.order-num { font-weight:800; color:var(--accent-toast); font-size:1rem; }
.client-name { font-weight:700; }
.client-tel  { font-size:.78rem; color:#aaa; margin-top:2px; }
.order-total { font-weight:800; font-size:1.05rem; }

/* Modal selector */
.sel-overlay { position:fixed; inset:0; background:rgba(0,0,0,.65); z-index:4000; display:none; align-items:center; justify-content:center; backdrop-filter:blur(6px); }
@keyframes slideUp { from{opacity:0;transform:translateY(40px)} to{opacity:1;transform:translateY(0)} }
.sel-sheet { background:white; border-radius:22px; width:min(96vw,660px); max-height:90vh; display:flex; flex-direction:column; box-shadow:0 30px 80px rgba(0,0,0,.35); animation:slideUp .35s cubic-bezier(.34,1.26,.64,1); overflow:hidden; }
.sel-header { background:#1F1F1F; color:white; padding:20px 26px; display:flex; justify-content:space-between; align-items:center; flex-shrink:0; }
.sel-header h2 { font-family:'Merriweather'; font-size:1rem; }
.sel-header p  { color:#888; font-size:.75rem; margin-top:3px; }
.sel-toolbar { padding:14px 20px; border-bottom:1px solid #f0f0f0; display:flex; gap:10px; align-items:center; flex-shrink:0; flex-wrap:wrap; background:#fafafa; }
.sel-search { flex:1; min-width:160px; padding:8px 14px; border:1.5px solid #eee; border-radius:10px; font-family:'Poppins'; font-size:.84rem; }
.sel-search:focus { outline:none; border-color:#D98C45; }
.sel-filter-tabs { display:flex; gap:6px; flex-wrap:wrap; }
.sel-ftab { padding:5px 12px; border-radius:20px; border:1.5px solid #eee; font-size:.72rem; font-weight:700; cursor:pointer; background:white; font-family:'Poppins'; transition:all .2s; }
.sel-ftab:hover { border-color:#D98C45; color:#D98C45; }
.sel-ftab.act { background:#1F1F1F; color:white; border-color:#1F1F1F; }
.sel-body { overflow-y:auto; flex:1; padding:16px 20px; }
.sel-footer { padding:14px 20px; border-top:1px solid #f0f0f0; display:flex; justify-content:space-between; align-items:center; background:#fdfdfd; flex-shrink:0; gap:10px; flex-wrap:wrap; }

.sel-item { display:flex; align-items:center; gap:12px; padding:13px 14px; border:2px solid #eee; border-radius:12px; margin-bottom:9px; cursor:pointer; transition:all .18s; user-select:none; }
.sel-item:hover { border-color:#D98C45; background:#fdf8f2; }
.sel-item.active { border-color:#D98C45; background:#fff8ee; }
.sel-check { width:22px; height:22px; border:2px solid #ddd; border-radius:6px; display:flex; align-items:center; justify-content:center; transition:all .18s; flex-shrink:0; }
.sel-item.active .sel-check { background:#D98C45; border-color:#D98C45; }
.sel-order-badge { font-weight:800; color:#D98C45; font-size:.9rem; white-space:nowrap; }
.sel-client { font-weight:700; font-size:.86rem; }
.sel-meta { font-size:.72rem; color:#aaa; margin-top:2px; }
.sel-status { font-size:.68rem; font-weight:700; padding:3px 10px; border-radius:20px; white-space:nowrap; flex-shrink:0; }
.s-pend { background:#fff8e1; color:#c0980a; }
.s-comp { background:#eafaf1; color:#27ae60; }
.s-canc { background:#ffebee; color:#e74c3c; }
.s-cam  { background:#ebf5fb; color:#3498db; }
.sel-all-btn { font-size:.76rem; color:var(--accent-toast); font-weight:700; cursor:pointer; background:none; border:none; font-family:'Poppins'; padding:0; }
</style>

<!-- JSON para el JS -->
<script>
const REPORTE_DB = <?php echo json_encode($reporteData, JSON_UNESCAPED_UNICODE); ?>;
window.FERMENTO_LOGO = <?php echo json_encode($_lb64); ?>;
</script>

<!-- HEADER -->
<div class="page-header">
    <div class="page-title">
        <h1>Gestión de Pedidos</h1>
        <p>Controla las órdenes y genera reportes detallados.</p>
    </div>
    <button onclick="abrirSelector()" class="btn-new" style="gap:10px;">
        <i class="fas fa-file-alt"></i> Generar Reporte
        <?php if($pedsPendientes > 0): ?>
        <span style="background:white;color:var(--accent-toast);border-radius:50%;width:22px;height:22px;font-size:.72rem;display:flex;align-items:center;justify-content:center;font-weight:800;"><?php echo $pedsPendientes; ?></span>
        <?php endif; ?>
    </button>
</div>

<!-- KPIs -->
<div class="kpi-grid" style="grid-template-columns:repeat(auto-fit,minmax(185px,1fr));margin-bottom:24px;">
    <div class="kpi-card">
        <div class="kpi-icon"><i class="fas fa-receipt"></i></div>
        <div class="kpi-info"><h3>Total Pedidos</h3><p><?php echo $totalP; ?></p></div>
    </div>
    <a href="pedidos.php?estado=pendiente" style="text-decoration:none;color:inherit;">
        <div class="kpi-card" style="<?php echo $fEstado==='pendiente'?'border:2px solid #f1c40f;':'' ?>">
            <div class="kpi-icon" style="color:#c0980a;background:#fff8e1;"><i class="fas fa-hourglass-half"></i></div>
            <div class="kpi-info"><h3>Pendientes</h3><p><?php echo $pendiente; ?></p></div>
        </div>
    </a>
    <a href="pedidos.php?estado=completado" style="text-decoration:none;color:inherit;">
        <div class="kpi-card" style="<?php echo $fEstado==='completado'?'border:2px solid #27ae60;':'' ?>">
            <div class="kpi-icon" style="color:#27ae60;background:#eafaf1;"><i class="fas fa-check-double"></i></div>
            <div class="kpi-info"><h3>Completados</h3><p><?php echo $completo; ?></p></div>
        </div>
    </a>
    <div class="kpi-card">
        <div class="kpi-icon" style="color:#D98C45;background:#fdf5e8;"><i class="fas fa-coins"></i></div>
        <div class="kpi-info"><h3>Ingresos Totales</h3><p>Q<?php echo number_format($ventasTot,2); ?></p></div>
    </div>
</div>

<!-- BÚSQUEDA -->
<div class="orders-topbar">
    <form method="GET" style="display:contents;">
        <input type="hidden" name="estado" value="<?php echo htmlspecialchars($fEstado); ?>">
        <i class="fas fa-search" style="color:#ccc;"></i>
        <input type="text" name="buscar" value="<?php echo htmlspecialchars($search); ?>"
               class="search-input" placeholder="Buscar por cliente o teléfono..."
               oninput="this.form.submit()">
    </form>
    <span style="font-size:.8rem;color:#bbb;"><?php echo count($pedidos); ?> resultado(s)</span>
</div>

<!-- TABS ESTADO -->
<div class="state-tabs">
    <?php $tabs=['todos'=>['🗂','Todos',"($totalP)",''],'pendiente'=>['⏳','Pendientes',"($pendiente)",'active-pend'],'completado'=>['✅','Completados',"($completo)",'active-comp'],'cancelado'=>['❌','Cancelados',"($cancelado)",'active-canc']];
    foreach($tabs as $val=>[$icon,$label,$count,$ac]):
        $cls=($fEstado===$val)?'active-tab '.$ac:''; ?>
    <a href="pedidos.php?estado=<?php echo $val; ?>&buscar=<?php echo urlencode($search); ?>" class="state-tab <?php echo $cls; ?>">
        <?php echo $icon.' '.$label; ?> <span><?php echo $count; ?></span>
    </a>
    <?php endforeach; ?>
</div>

<!-- TABLA DE PEDIDOS -->
<div class="orders-card">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <?php
                    // Helper para generar URL de orden
                    function sortUrl($col, $sortBy, $sortDirNext, $fEstado, $search) {
                        $dir = ($sortBy === $col) ? $sortDirNext : 'desc';
                        return "pedidos.php?estado=$fEstado&buscar=".urlencode($search)."&sort=$col&dir=$dir";
                    }
                    function sortIcon($col, $sortBy, $sortDir) {
                        if ($sortBy !== $col) return '<i class="fas fa-sort" style="opacity:0.3"></i>';
                        return $sortDir === 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
                    }
                ?>
                <th>
                    <a href="<?php echo sortUrl('cliente', $sortBy, $sortDirNext, $fEstado, $search); ?>" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:5px;">
                        Cliente <?php echo sortIcon('cliente', $sortBy, $sortDir); ?>
                    </a>
                </th>
                <th>
                    <a href="<?php echo sortUrl('fecha', $sortBy, $sortDirNext, $fEstado, $search); ?>" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:5px;">
                        Fecha <?php echo sortIcon('fecha', $sortBy, $sortDir); ?>
                    </a>
                </th>
                <th>
                    <a href="<?php echo sortUrl('total', $sortBy, $sortDirNext, $fEstado, $search); ?>" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:5px;">
                        Total <?php echo sortIcon('total', $sortBy, $sortDir); ?>
                    </a>
                </th>
                <th>Estado</th>
                <th style="text-align:right;">Acción</th>
            </tr>
        </thead>
        <tbody>
        <?php if(empty($pedidos)): ?>
            <tr><td colspan="6" style="text-align:center;padding:80px;color:#ccc;">
                <i class="fas fa-inbox" style="font-size:2.5rem;display:block;margin-bottom:12px;"></i>
                No hay pedidos en esta sección.
            </td></tr>
        <?php else: foreach($pedidos as $p): ?>
            <tr style="cursor:pointer;" onclick="location.href='ver_pedido.php?id=<?php echo $p['id']; ?>'">
                <td data-label="Orden"><span class="order-num">#<?php echo $p['id']; ?></span></td>
                <td data-label="Cliente">
                    <div class="client-name"><?php echo htmlspecialchars($p['nombre_cliente']); ?></div>
                    <div class="client-tel" style="display:flex; align-items:center; gap:8px;">
                        <span><i class="fas fa-phone" style="font-size:.7rem;"></i> <?php echo htmlspecialchars($p['telefono']); ?></span>
                        <?php if(!empty($p['telefono'])): 
                            // Limpiar número
                            $tel_limpio = preg_replace('/[^0-9]/', '', $p['telefono']);
                            if(strlen($tel_limpio) == 8) $tel_limpio = '502'.$tel_limpio;
                            $msg_ws = "Hola ".explode(' ', $p['nombre_cliente'])[0].", vimos tu pedido #".$p['id']." en nuestra tienda. ¿Te ayudamos a completarlo?";
                        ?>
                        <a href="https://wa.me/<?php echo $tel_limpio; ?>?text=<?php echo urlencode($msg_ws); ?>" target="_blank" onclick="event.stopPropagation()" style="color:#25D366; text-decoration:none; padding:2px 6px; border-radius:4px; background:#eafaf1; border:1px solid #c8e6c9; transition:all 0.2s;" title="Contactar por WhatsApp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
                <td data-label="Fecha">
                    <div style="font-weight:600;"><?php echo date('d M, Y', strtotime($p['fecha'])); ?></div>
                    <small style="color:#aaa;"><?php echo date('H:i', strtotime($p['fecha'])); ?> hrs</small>
                </td>
                <td data-label="Total" class="order-total">Q<?php echo number_format($p['total'],2); ?></td>
                <td data-label="Estado"><?php echo estadoBadge($p['estado']); ?></td>
                <td style="text-align:right;" onclick="event.stopPropagation()">
                    <a href="ver_pedido.php?id=<?php echo $p['id']; ?>" class="btn-new btn-outline" style="padding:7px 14px;font-size:.8rem;">
                        Ver <i class="fas fa-arrow-right"></i>
                    </a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php if($totalPaginas > 1): ?>
<div style="display:flex;justify-content:center;align-items:center;gap:8px;margin-top:25px;flex-wrap:wrap;">
    <?php
        $baseUrl = "pedidos.php?estado=$fEstado&buscar=".urlencode($search)."&sort=$sortBy&dir=".strtolower($sortDir);
        
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

<!-- MODAL SELECTOR -->
<div class="sel-overlay" id="selOverlay" onclick="if(event.target===this)cerrarSelector()">
    <div class="sel-sheet">
        <div class="sel-header">
            <div>
                <h2>📄 Reporte de Pedidos</h2>
                <p id="selSubtitle">Selecciona los pedidos para el reporte</p>
            </div>
            <button onclick="cerrarSelector()" style="background:rgba(255,255,255,.1);border:none;color:white;width:34px;height:34px;border-radius:50%;cursor:pointer;">✕</button>
        </div>
        <div class="sel-toolbar">
            <input type="text" id="selBuscar" class="sel-search" placeholder="🔍 Buscar cliente o #pedido..." oninput="filtrarItems()">
            <div class="sel-filter-tabs">
                <button class="sel-ftab act" data-estado="todos" onclick="setFiltro(this,'todos')">Todos</button>
                <button class="sel-ftab" data-estado="pendiente" onclick="setFiltro(this,'pendiente')">Pendientes</button>
                <button class="sel-ftab" data-estado="completado" onclick="setFiltro(this,'completado')">Completados</button>
            </div>
        </div>
        <div class="sel-body" id="selBody">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <span id="selCount" style="font-size:.78rem;color:#aaa;">0 seleccionado(s)</span>
                <div style="display:flex;gap:8px;">
                    <button class="sel-all-btn" onclick="selTodos(true)">Todos visibles</button>
                    <span style="color:#ddd;">|</span>
                    <button class="sel-all-btn" onclick="selTodos(false)" style="color:#aaa;">Limpiar</button>
                </div>
            </div>
            <div id="listaPedidos">
                <?php foreach($todosPedidos as $tp):
                    $rd = $reporteData[$tp['id']] ?? null;
                    if(!$rd) continue;
                    $stCls = match($tp['estado']) { 'completado'=>'s-comp','cancelado'=>'s-canc','en_camino'=>'s-cam', default=>'s-pend' };
                    $stLbl = match($tp['estado']) { 'completado'=>'✅','cancelado'=>'❌','en_camino'=>'🚚', default=>'⏳' };
                ?>
                <div class="sel-item" data-id="<?php echo $tp['id']; ?>" data-estado="<?php echo $tp['estado']; ?>" data-search="<?php echo strtolower($tp['nombre_cliente']); ?>" onclick="toggleItem(this)">
                    <div class="sel-check"><svg viewBox="0 0 24 24" style="width:13px;display:none;" class="chk-ico" fill="none" stroke="white" stroke-width="3.5"><polyline points="20 6 9 17 4 12"/></svg></div>
                    <div style="flex:1;">
                        <span class="sel-order-badge">#<?php echo $tp['id']; ?></span>
                        <span class="sel-client"><?php echo htmlspecialchars($tp['nombre_cliente']); ?></span>
                        <div class="sel-meta"><?php echo $rd['fecha']; ?> · Q<?php echo number_format($rd['total'],2); ?></div>
                    </div>
                    <span class="sel-status <?php echo $stCls; ?>"><?php echo $stLbl; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <div id="selVacio" style="display:none;text-align:center;padding:40px;color:#ccc;">No se encontraron pedidos.</div>
        </div>
        <div class="sel-footer">
            <span id="selTotal" style="font-size:.78rem;color:#888;font-weight:600;">0 pedido(s) — Q0.00</span>
            <div style="display:flex;gap:10px;">
                <button onclick="cerrarSelector()" class="btn-new btn-outline">Cancelar</button>
                <button onclick="generarReporte()" class="btn-new" id="btnGenerar" disabled>Generar Reporte</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/hoja_produccion.js"></script>
<script>
let filtroEstado = 'todos';
function abrirSelector() { document.getElementById('selOverlay').style.display = 'flex'; filtrarItems(); }
function cerrarSelector() { document.getElementById('selOverlay').style.display = 'none'; }
function toggleItem(el) {
    el.classList.toggle('active');
    el.querySelector('.chk-ico').style.display = el.classList.contains('active') ? 'block' : 'none';
    actualizarConteo();
}
function setFiltro(btn, estado) {
    document.querySelectorAll('.sel-ftab').forEach(b => b.classList.remove('act'));
    btn.classList.add('act');
    filtroEstado = estado;
    filtrarItems();
}
function filtrarItems() {
    const q = document.getElementById('selBuscar').value.toLowerCase();
    let visibles = 0;
    document.querySelectorAll('.sel-item').forEach(it => {
        const show = (filtroEstado === 'todos' || it.dataset.estado === filtroEstado) && (q === '' || it.dataset.search.includes(q) || it.dataset.id.includes(q));
        it.style.display = show ? '' : 'none';
        if (show) visibles++;
    });
    document.getElementById('selVacio').style.display = visibles === 0 ? '' : 'none';
}
function selTodos(sel) {
    document.querySelectorAll('.sel-item').forEach(el => {
        if (el.style.display === 'none') return;
        sel ? el.classList.add('active') : el.classList.remove('active');
        el.querySelector('.chk-ico').style.display = sel ? 'block' : 'none';
    });
    actualizarConteo();
}
function actualizarConteo() {
    const selected = [...document.querySelectorAll('.sel-item.active')];
    const total = selected.reduce((acc, el) => acc + (REPORTE_DB[el.dataset.id]?.total || 0), 0);
    document.getElementById('selCount').textContent = selected.length + ' seleccionado(s)';
    document.getElementById('selTotal').textContent = selected.length + ' pedido(s) — Q' + total.toFixed(2);
    document.getElementById('btnGenerar').disabled = (selected.length === 0);
}
function generarReporte() {
    const ids = [...document.querySelectorAll('.sel-item.active')].map(el => el.dataset.id);
    const ahora = new Date().toLocaleString('es-GT',{day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'}).replace(',','');
    const peds = ids.map(id => {
        const d = { ...REPORTE_DB[id] };
        d.impreso = ahora;
        return d;
    });
    cerrarSelector();
    imprimirReporte(peds);
}
</script>

<?php include 'includes/admin_footer.php'; ?>