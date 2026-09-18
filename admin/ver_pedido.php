<?php
$currentPage = 'pedidos';
require '../includes/db.php';
require '../includes/config.php';
include 'includes/admin_header.php';


if (!isset($_GET['id'])) { header("Location: pedidos.php"); exit; }
$id = (int)$_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_estado'])) {
    csrf_verify("ver_pedido.php?id={$id}");

    $allowed     = ['pendiente', 'preparando', 'en_camino', 'completado', 'cancelado'];
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

// Productos disponibles para agregar al modificar (F2)
$stmtProd = $pdo->query("
    SELECT p.id as p_id, p.nombre as p_nombre, v.id as v_id, v.nombre as v_nombre, IFNULL(v.precio, p.precio) as precio 
    FROM productos p 
    LEFT JOIN producto_variantes v ON p.id = v.producto_id 
    ORDER BY p.nombre, v.nombre
");
$productosSelect = $stmtProd->fetchAll(PDO::FETCH_ASSOC);


$pageTitle = 'Pedido #' . $id;
include 'includes/admin_nav.php';

$estadoConfig = [
    'pendiente'  => ['color'=>'#c0980a','bg'=>'#fff8e1','label'=>'⏳ Pendiente',  'badge'=>'badge-pending'],
    'preparando' => ['color'=>'#d35400','bg'=>'#fbeee6','label'=>'🧑‍🍳 Preparando', 'badge'=>'badge-pending'],
    'en_camino'  => ['color'=>'#3498db','bg'=>'#ebf5fb','label'=>'🚚 En Camino', 'badge'=>'badge-pending'],
    'completado' => ['color'=>'#27ae60','bg'=>'#eafaf1','label'=>'✅ Completado', 'badge'=>'badge-success'],
    'cancelado'  => ['color'=>'#e74c3c','bg'=>'#ffebee','label'=>'❌ Cancelado',  'badge'=>'badge-danger'],
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

// ── Mensaje de WhatsApp para notificar al admin/Fernando ──────────────────
$secret_admin = 'fermento_secure_token_2026';
$token_admin  = substr(hash('sha256', $pedido['id'] . $pedido['fecha'] . $secret_admin), 0, 10);
$protocol_admin = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$url_recibo_admin = $protocol_admin . '://' . $_SERVER['HTTP_HOST']
    . rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/\\')
    . '/recibo.php?id=' . $pedido['id'] . '&token=' . $token_admin;

$zona_nombre_admin = 'Envío a domicilio';
if (!empty($pedido['zona_envio_id'])) {
    $stmtZA = $pdo->prepare("SELECT nombre FROM zonas_envio WHERE id = ?");
    $stmtZA->execute([$pedido['zona_envio_id']]);
    $zA = $stmtZA->fetchColumn();
    if ($zA) $zona_nombre_admin = $zA;
} elseif ($pedido['zona_envio_id'] === 0 || $pedido['zona_envio_id'] === '0') {
    $zona_nombre_admin = 'Recoger (WhatsApp)';
}

$msj_admin  = "🍞 *Pedido Fermento #" . str_pad($pedido['id'], 6, '0', STR_PAD_LEFT) . "*\n";
$msj_admin .= "────────────────────────\n";
$msj_admin .= "👤 *Cliente:* " . $pedido['nombre_cliente'] . "\n";
$msj_admin .= "📞 *Tel:* " . $pedido['telefono'] . "\n";
$msj_admin .= "📍 *Dirección:* " . $pedido['direccion_envio'] . "\n";
$msj_admin .= "🚚 *Zona:* " . $zona_nombre_admin . "\n";
if (!empty($pedido['fecha_envio_programada'])) {
    $msj_admin .= "📅 *Entrega:* " . date('d/m/Y', strtotime($pedido['fecha_envio_programada']));
    if (!empty($pedido['hora_envio_programada'])) {
        list($hh_a, $mm_a) = explode(':', $pedido['hora_envio_programada']);
        $h12_a = ((int)$hh_a % 12 ?: 12) . ':' . $mm_a . ((int)$hh_a < 12 ? ' AM' : ' PM');
        $msj_admin .= " a las " . $h12_a;
    }
    $msj_admin .= "\n";
}
$msj_admin .= "────────────────────────\n";
$msj_admin .= "🛒 *Detalle:*\n";
foreach ($items as $it) {
    $msj_admin .= "  • " . $it['cantidad'] . "x " . $it['nombre_producto'];
    $msj_admin .= " — Q" . number_format($it['precio_unitario'] * $it['cantidad'], 2) . "\n";
}
$msj_admin .= "────────────────────────\n";
$msj_admin .= "💰 *Subtotal:* Q" . number_format($pedido['subtotal'], 2) . "\n";
$msj_admin .= "🚚 *Envío:* Q" . number_format($pedido['costo_envio'], 2) . "\n";
$msj_admin .= "✅ *TOTAL: Q" . number_format($pedido['total'], 2) . "*\n";
$msj_admin .= "\n🧾 Recibo: " . $url_recibo_admin;

$telefono_admin = getConfig('whatsapp_numero', '50239754421');
$link_ws_admin  = "https://wa.me/{$telefono_admin}?text=" . urlencode($msj_admin);
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
    <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
        <span class="badge <?php echo $cfg['badge']; ?>" style="padding:10px 18px;font-size:.85rem;"><?php echo $cfg['label']; ?></span>
        <a href="<?php echo $link_ws_admin; ?>" target="_blank"
           class="btn-new"
           style="padding:10px 20px;background:#25D366;border-color:#25D366;text-decoration:none;display:flex;align-items:center;gap:8px;"
           title="Enviar resumen completo del pedido por WhatsApp">
            <i class="fab fa-whatsapp"></i> Enviar a WhatsApp
        </a>
        <button onclick="imprimirHoja(HOJA_DATA)" class="btn-new" style="padding:10px 20px;">
            <i class="fas fa-print"></i> Hoja de Producción
        </button>
    </div>
</div>

<div class="order-grid">

    <div class="items-card">
        <div class="items-card-header">
            <div style="display:flex;align-items:center;gap:15px;">
                <h2>Productos del Pedido</h2>
                <span style="font-size:.8rem;color:#aaa;"><?php echo count($items); ?> ítem(s)</span>
            </div>
            <?php if (can('modificar_pedido') && !in_array($pedido['estado'], ['cancelado', 'completado'])): ?>
            <button type="button" onclick="document.getElementById('modalEdicion').style.display='flex'" class="btn-new" style="padding:6px 14px;font-size:0.85rem;background:#fff;color:var(--accent-toast);border:1.5px solid var(--accent-toast);">
                <i class="fas fa-edit"></i> Modificar
            </button>
            <?php endif; ?>
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

        <?php if (!empty($pedido['fecha_envio_programada'])): ?>
        <div class="side-card" style="border:2px solid #6C63FF;">
            <div class="side-card-header" style="background:#f0f0ff;color:#6C63FF;">
                <i class="fas fa-calendar-check"></i> Entrega Programada
            </div>
            <div class="side-card-body">
                <div class="info-row">
                    <div class="info-icon" style="background:#eeebff;color:#6C63FF;"><i class="fas fa-calendar"></i></div>
                    <div class="info-content">
                        <small>Fecha</small>
                        <span><?php
                            $diasSem = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
                            $ts = strtotime($pedido['fecha_envio_programada']);
                            echo $diasSem[date('w',$ts)] . ', ' . date('d/m/Y', $ts);
                        ?></span>
                    </div>
                </div>
                <?php if (!empty($pedido['hora_envio_programada'])): ?>
                <div class="info-row">
                    <div class="info-icon" style="background:#eeebff;color:#6C63FF;"><i class="fas fa-clock"></i></div>
                    <div class="info-content">
                        <small>Hora</small>
                        <span><?php
                            list($hh,$mm) = explode(':', $pedido['hora_envio_programada']);
                            $h12 = ((int)$hh % 12 ?: 12) . ':' . $mm . ((int)$hh < 12 ? ' AM' : ' PM');
                            echo $h12;
                        ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="side-card">
            <div class="side-card-header">
                <i class="fas fa-exchange-alt" style="color:var(--accent-toast);"></i> Cambiar Estado
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="status-selector">
                    <?php foreach([
                        'pendiente'             => ['#c0980a', '⏳ Pendiente — En espera'],
                        'preparando'            => ['#d35400', '🧑‍🍳 Preparando — En producción'],
                        'en_camino'             => ['#3498db', '🚚 En Camino — En ruta'],
                        'completado'            => ['#27ae60', '✅ Completado — Cerrado'],
                        'cancelado'             => ['#e74c3c', '❌ Cancelado'],
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

<!-- MODAL EDICIÓN DE PEDIDO (F2) -->
<div id="modalEdicion" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:white;width:100%;max-width:600px;border-radius:15px;padding:30px;max-height:90vh;overflow-y:auto;">
        <h2 style="margin-bottom:20px;font-family:'Merriweather';color:#1F1F1F;">Modificar Pedido</h2>
        
        <div id="modalItemsContainer" style="margin-bottom:20px;">
            <label style="font-weight:700;display:block;margin-bottom:10px;">Ítems Actuales</label>
            <?php foreach($items as $it): ?>
            <div class="edit-item-row" data-id="<?= $it['id'] ?>" style="display:flex;gap:10px;align-items:center;margin-bottom:10px;background:#f9f9f9;padding:10px;border-radius:8px;">
                <div style="flex:1;font-size:0.9rem;font-weight:600;">
                    <?= htmlspecialchars($it['nombre_producto']) ?> <?= $it['variante_nombre'] ? '('.htmlspecialchars($it['variante_nombre']).')' : '' ?>
                </div>
                <div style="width:100px;">
                    <input type="number" class="edit-item-qty" min="0" value="<?= $it['cantidad'] ?>" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:5px;">
                </div>
                <button type="button" onclick="this.closest('.edit-item-row').querySelector('.edit-item-qty').value = 0" style="background:#ffebee;color:#c0392b;border:none;border-radius:5px;padding:8px 12px;cursor:pointer;"><i class="fas fa-trash"></i></button>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-bottom:20px;">
            <label style="font-weight:700;display:block;margin-bottom:10px;">Agregar Producto</label>
            <div style="display:flex;gap:10px;">
                <select id="selectNuevoProducto" style="flex:1;padding:10px;border:1px solid #ccc;border-radius:5px;">
                    <option value="">-- Seleccionar --</option>
                    <?php foreach($productosSelect as $ps): ?>
                    <option value="<?= $ps['p_id'] ?>" data-vid="<?= $ps['v_id'] ?? '' ?>">
                        <?= htmlspecialchars($ps['p_nombre']) ?> <?= $ps['v_nombre'] ? '('.htmlspecialchars($ps['v_nombre']).')' : '' ?> - Q<?= number_format($ps['precio'],2) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <input type="number" id="inputNuevaCant" min="1" value="1" style="width:70px;padding:10px;border:1px solid #ccc;border-radius:5px;">
                <button type="button" onclick="agregarNuevoItemUI()" style="background:#eafaf1;color:#27ae60;border:none;border-radius:5px;padding:10px 15px;cursor:pointer;"><i class="fas fa-plus"></i></button>
            </div>
            <div id="nuevosItemsList" style="margin-top:10px;"></div>
        </div>

        <div style="margin-bottom:20px;">
            <label style="font-weight:700;display:block;margin-bottom:10px;">Motivo del Cambio <span style="color:#c0392b;">*</span></label>
            <textarea id="editMotivo" required placeholder="Ej: Cliente solicitó agregar una dona de chocolate" style="width:100%;padding:10px;border:1px solid #ccc;border-radius:5px;"></textarea>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:10px;">
            <button type="button" onclick="document.getElementById('modalEdicion').style.display='none'" style="padding:10px 20px;border:1px solid #ccc;background:white;border-radius:8px;cursor:pointer;">Cancelar</button>
            <button type="button" onclick="guardarEdicionPedido(<?= $id ?>)" style="padding:10px 20px;border:none;background:var(--accent-toast);color:white;border-radius:8px;cursor:pointer;font-weight:700;">Guardar Cambios</button>
        </div>
    </div>
</div>

<script>
let nuevosItemsQueue = [];

function agregarNuevoItemUI() {
    const select = document.getElementById('selectNuevoProducto');
    const inputCant = document.getElementById('inputNuevaCant');
    if(!select.value || inputCant.value < 1) return;
    
    const opt = select.options[select.selectedIndex];
    const item = {
        producto_id: select.value,
        variante_id: opt.getAttribute('data-vid') || null,
        cantidad: parseInt(inputCant.value),
        nombre: opt.text
    };
    
    nuevosItemsQueue.push(item);
    
    const div = document.createElement('div');
    div.style.cssText = "display:flex;justify-content:space-between;background:#ebf5fb;padding:8px 10px;border-radius:5px;margin-bottom:5px;font-size:0.9rem;";
    div.innerHTML = `<span><span style="font-weight:bold;color:#3498db;">+ ${item.cantidad}x</span> ${item.nombre}</span>`;
    document.getElementById('nuevosItemsList').appendChild(div);
    
    select.value = '';
    inputCant.value = 1;
}

function guardarEdicionPedido(pedidoId) {
    const motivo = document.getElementById('editMotivo').value.trim();
    if(!motivo) { alert("Debes ingresar un motivo."); return; }
    
    const itemsExistentes = [];
    document.querySelectorAll('.edit-item-row').forEach(row => {
        itemsExistentes.push({
            id_detalle: row.getAttribute('data-id'),
            cantidad: row.querySelector('.edit-item-qty').value
        });
    });

    const data = {
        pedido_id: pedidoId,
        motivo: motivo,
        items: itemsExistentes,
        nuevos_items: nuevosItemsQueue
    };

    fetch('../ajax/modificar_pedido.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            alert(res.msg);
            window.location.reload();
        } else {
            alert("Error: " + res.error);
        }
    })
    .catch(err => alert("Error de red."));
}
</script>


<script src="assets/js/hoja_produccion.js"></script>
<script>
// Auto-ejecutar si viene param ?print=1
<?php if(isset($_GET['print'])): ?>
window.addEventListener('load', () => setTimeout(() => imprimirHoja(HOJA_DATA), 400));
<?php endif; ?>
</script>

<?php include 'includes/admin_footer.php'; ?>