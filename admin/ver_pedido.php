<?php
$currentPage = 'pedidos';
require '../includes/db.php';
require '../includes/config.php';
require 'includes/auth_admin.php';   // ← verifica sesión + rol admin/supervisor

if (!isset($_GET['id'])) { header("Location: pedidos.php"); exit; }
$id = (int)$_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_estado'])) {
    csrf_verify("ver_pedido.php?id={$id}");

    $allowed     = ['pendiente', 'completado', 'cancelado', 'en_camino'];
    $nuevoEstado = in_array($_POST['nuevo_estado'], $allowed) ? $_POST['nuevo_estado'] : 'pendiente';

    // Obtener estado anterior para decidir si restaurar stock
    $stmtAnterior = $pdo->prepare("SELECT estado FROM pedidos WHERE id = ?");
    $stmtAnterior->execute([$id]);
    $estadoAnterior = $stmtAnterior->fetchColumn();

    try {
        $pdo->beginTransaction();

        // Actualizar estado del pedido
        $pdo->prepare("UPDATE pedidos SET estado = ? WHERE id = ?")->execute([$nuevoEstado, $id]);

        // Restaurar stock solo al cancelar y si el estado anterior no era ya 'cancelado'
        if ($nuevoEstado === 'cancelado' && $estadoAnterior !== 'cancelado') {
            $stmtItems = $pdo->prepare("SELECT producto_id, variante_id, cantidad FROM detalles_pedido WHERE pedido_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            foreach ($items as $item) {
                if ($item['variante_id']) {
                    // Restaurar stock de variante
                    $pdo->prepare("UPDATE producto_variantes SET stock = stock + ? WHERE id = ?")
                        ->execute([$item['cantidad'], $item['variante_id']]);
                    // Sincronizar stock total del producto padre
                    $pdo->prepare("UPDATE productos SET stock = (SELECT SUM(stock) FROM producto_variantes WHERE producto_id = productos.id) WHERE id = ?")
                        ->execute([$item['producto_id']]);
                } else {
                    // Restaurar stock de producto simple
                    $pdo->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?")
                        ->execute([$item['cantidad'], $item['producto_id']]);
                }
            }
        }

        $pdo->commit();

        // Log de actividad
        adminLog(
            'cambiar_estado_pedido',
            'pedidos',
            $id,
            "Estado: {$estadoAnterior} → {$nuevoEstado}"
            . ($nuevoEstado === 'cancelado' ? ' (stock restaurado)' : '')
        );

        header("Location: pedidos.php?msg=estado_ok"); exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('ver_pedido cambio estado error: ' . $e->getMessage());
        header("Location: ver_pedido.php?id={$id}&err=estado"); exit;
    }
}

$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmt->execute([$id]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$pedido) { header("Location: pedidos.php"); exit; }

$stmt2 = $pdo->prepare("SELECT * FROM detalles_pedido WHERE pedido_id = ?");
$stmt2->execute([$id]);
$items = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Pedido #' . $id;
include 'includes/admin_header.php';
include 'includes/admin_nav.php';

$estadoConfig = [
    'pendiente'  => ['color'=>'#c0980a','bg'=>'#fff8e1','label'=>'⏳ Pendiente',  'badge'=>'badge-pending'],
    'completado' => ['color'=>'#27ae60','bg'=>'#eafaf1','label'=>'✅ Completado', 'badge'=>'badge-success'],
    'cancelado'  => ['color'=>'#e74c3c','bg'=>'#ffebee','label'=>'❌ Cancelado',  'badge'=>'badge-danger'],
    'en_camino'  => ['color'=>'#3498db','bg'=>'#ebf5fb','label'=>'🚚 En Camino', 'badge'=>'badge-pending'],
];
$cfg = $estadoConfig[$pedido['estado']] ?? $estadoConfig['pendiente'];

// Datos para la hoja (JSON para JavaScript)
$datosHoja = [
    'id'       => $id,
    'fecha'    => date('d/m/Y — H:i', strtotime($pedido['fecha'])),
    'impreso'  => date('d/m/Y — H:i'),
    'items'    => array_map(fn($it) => [
        'nombre'   => $it['nombre_producto'],
        'cantidad' => $it['cantidad'],
        'fecha'    => date('d/m/Y', strtotime($pedido['fecha'])),
        'hora'     => date('H:i', strtotime($pedido['fecha'])),
    ], $items),
    'total_uds' => array_sum(array_column($items,'cantidad')),
];
?>

<style>
.order-grid { display:grid; grid-template-columns:1fr 340px; gap:28px; }
@media(max-width:900px) { .order-grid { grid-template-columns:1fr; } }
.items-card { background:white; border-radius:20px; box-shadow:var(--shadow); overflow:hidden; }
.items-card-header { padding:22px 28px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #f0f0f0; }
.items-card-header h2 { font-family:'Merriweather'; font-size:1.1rem; }
.items-table { width:100%; border-collapse:collapse; }
.items-table thead th { padding:12px 20px; background:#fafafa; font-size:.7rem; text-transform:uppercase; color:#aaa; font-weight:700; text-align:left; border-bottom:1px solid #f0f0f0; }
.items-table tbody td { padding:15px 20px; border-bottom:1px solid #f8f8f8; font-size:.9rem; vertical-align:middle; }
.items-table tbody tr:last-child td { border-bottom:none; }
.qty-chip { background:#f4f4f4; color:#333; border-radius:50%; width:32px; height:32px; display:inline-flex; align-items:center; justify-content:center; font-weight:800; font-size:.85rem; }
.total-row td { padding:18px 20px !important; background:var(--bg-cream); font-weight:700; font-size:1.1rem; }
.side-card { background:white; border-radius:20px; box-shadow:var(--shadow); overflow:hidden; margin-bottom:20px; }
.side-card-header { padding:18px 22px; border-bottom:1px solid #f0f0f0; display:flex; align-items:center; gap:10px; font-weight:700; font-family:'Merriweather'; font-size:.9rem; }
.side-card-body { padding:20px 22px; }
.info-row { display:flex; align-items:flex-start; gap:12px; margin-bottom:14px; }
.info-row:last-child { margin-bottom:0; }
.info-icon { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.info-content small { display:block; font-size:.7rem; color:#aaa; text-transform:uppercase; font-weight:700; }
.info-content span  { font-size:.9rem; font-weight:600; }
.status-selector { display:flex; flex-direction:column; gap:10px; padding:20px 22px; }
.status-btn { padding:12px 16px; border-radius:12px; border:2px solid #eee; background:white; cursor:pointer; font-family:'Poppins'; font-size:.85rem; font-weight:600; text-align:left; transition:all .2s; display:flex; align-items:center; gap:10px; }
.status-btn:hover { border-color:var(--accent-toast); background:#fdf8f2; }
.status-btn.selected { border-color:var(--accent-toast); background:linear-gradient(135deg,#fffdf8,#fff8ee); }
.status-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
</style>

<!-- DATOS OCULTOS PARA LA HOJA -->
<script>
const HOJA_DATA = <?php echo json_encode([$datosHoja], JSON_UNESCAPED_UNICODE); ?>;
<?php
$_lp  = __DIR__ . '/../assets/img/logo_fermento.png';
$_lb64 = file_exists($_lp) ? 'data:image/png;base64,'.base64_encode(file_get_contents($_lp)) : '';
?>
window.FERMENTO_LOGO = <?php echo json_encode($_lb64); ?>;
</script>

<div class="page-header">
    <div class="page-title">
        <a href="pedidos.php" style="text-decoration:none;color:#888;font-size:.85rem;display:flex;align-items:center;gap:6px;">
            <i class="fas fa-arrow-left"></i> Volver a Pedidos
        </a>
        <h1 style="margin-top:8px;">Pedido <span style="color:var(--accent-toast);">#<?php echo $id; ?></span></h1>
        <p style="margin-top:3px;"><?php echo date('d \d\e F, Y — H:i', strtotime($pedido['fecha'])); ?> hrs</p>
    </div>
    <div style="display:flex;gap:12px;align-items:center;">
        <span class="badge <?php echo $cfg['badge']; ?>" style="padding:10px 18px;font-size:.85rem;"><?php echo $cfg['label']; ?></span>
        <button onclick="imprimirHoja(HOJA_DATA)" class="btn-new" style="padding:10px 20px;">
            <i class="fas fa-print"></i> Hoja de Producción
        </button>
    </div>
</div>

<div class="order-grid">

    <div class="items-card">
        <div class="items-card-header">
            <h2>Productos del Pedido</h2>
            <span style="font-size:.8rem;color:#aaa;"><?php echo count($items); ?> ítem(s)</span>
        </div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th style="text-align:center;">Cant.</th>
                    <th>Precio Unit.</th>
                    <th style="text-align:right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $item):
                    $subtotal = $item['precio_unitario'] * $item['cantidad']; ?>
                <tr>
                    <td data-label="Producto"><div style="font-weight:700;"><?php echo htmlspecialchars($item['nombre_producto']); ?></div></td>
                    <td data-label="Cant." style="text-align:center;"><span class="qty-chip"><?php echo $item['cantidad']; ?></span></td>
                    <td data-label="Precio Unit.">Q<?php echo number_format($item['precio_unitario'],2); ?></td>
                    <td data-label="Subtotal" style="text-align:right;font-weight:700;">Q<?php echo number_format($subtotal,2); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3" style="text-align:right;font-size:1rem;">TOTAL DEL PEDIDO</td>
                    <td style="text-align:right;font-size:1.4rem;color:var(--accent-toast);font-weight:900;">
                        Q<?php echo number_format($pedido['total'],2); ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div>
        <div class="side-card">
            <div class="side-card-header">
                <i class="fas fa-user" style="color:var(--accent-toast);"></i> Información del Cliente
            </div>
            <div class="side-card-body">
                <div class="info-row">
                    <div class="info-icon" style="background:#fdf5e8;color:var(--accent-toast);"><i class="fas fa-user"></i></div>
                    <div class="info-content"><small>Cliente</small><span><?php echo htmlspecialchars($pedido['nombre_cliente']); ?></span></div>
                </div>
                <div class="info-row">
                    <div class="info-icon" style="background:#eafaf1;color:#27ae60;"><i class="fas fa-phone"></i></div>
                    <div class="info-content"><small>Teléfono</small><span><?php echo htmlspecialchars($pedido['telefono']); ?></span></div>
                </div>
                <?php if(!empty($pedido['direccion_envio'])): ?>
                <div class="info-row">
                    <div class="info-icon" style="background:#ebf5fb;color:#3498db;"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="info-content"><small>Dirección</small><span><?php echo nl2br(htmlspecialchars($pedido['direccion_envio'])); ?></span></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="side-card">
            <div class="side-card-header">
                <i class="fas fa-exchange-alt" style="color:var(--accent-toast);"></i> Cambiar Estado
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="status-selector">
                    <?php foreach([
                        'pendiente'  => ['#c0980a', '⏳ Pendiente — En espera'],
                        'en_camino'  => ['#3498db', '🚚 En Camino — En ruta'],
                        'completado' => ['#27ae60', '✅ Completado — Entregado'],
                        'cancelado'  => ['#e74c3c', '❌ Cancelado'],
                    ] as $val => [$color, $label]):
                        $sel = ($pedido['estado'] === $val) ? 'selected' : ''; ?>
                    <label class="status-btn <?php echo $sel; ?>">
                        <input type="radio" name="nuevo_estado" value="<?php echo $val; ?>" <?php echo $sel ? 'checked' : ''; ?> style="display:none;" onchange="this.form.submit()">
                        <span class="status-dot" style="background:<?php echo $color; ?>;"></span>
                        <?php echo $label; ?>
                        <?php if($sel): ?><i class="fas fa-check" style="margin-left:auto;color:var(--accent-toast);"></i><?php endif; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <div style="padding:0 22px 20px;">
                    <button type="submit" class="btn-new" style="width:100%;justify-content:center;padding:12px;">
                        <i class="fas fa-save"></i> Guardar Cambio
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script src="assets/js/hoja_produccion.js"></script>
<script>
// Auto-ejecutar si viene param ?print=1
<?php if(isset($_GET['print'])): ?>
window.addEventListener('load', () => setTimeout(() => imprimirHoja(HOJA_DATA), 400));
<?php endif; ?>
</script>

<?php include 'includes/admin_footer.php'; ?>