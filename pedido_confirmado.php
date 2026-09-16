<?php
$pageTitle = "¡Gracias por tu compra! | Fermento";
require 'includes/db.php';
require 'includes/config.php';
include 'includes/header.php';
include 'includes/nav.php';

// 1. Validaciones
if (!isset($_GET['id'])) { header("Location: index.php"); exit; }
$id_pedido = (int)$_GET['id'];

// 2. Obtener datos
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmt->execute([$id_pedido]);
$pedido = $stmt->fetch();

if (!$pedido) { header("Location: index.php"); exit; }

$stmt2 = $pdo->prepare("SELECT * FROM detalles_pedido WHERE pedido_id = ?");
$stmt2->execute([$id_pedido]);
$detalles = $stmt2->fetchAll();

// ── Detectar "pedido grande/especial" (IT mundi #7) ───────────────────────
// Si algún producto del pedido alcanza N veces su mínimo de compra (lote de
// producción), sugerimos al cliente coordinarlo directo por WhatsApp.
$multiplicadorPedidoGrande = max(2, (int)getConfig('multiplicador_pedido_grande', 3));
$esPedidoGrande = false;
if (!empty($detalles)) {
    $prodIds = array_unique(array_column($detalles, 'producto_id'));
    $placeholders = implode(',', array_fill(0, count($prodIds), '?'));
    $stmtMin = $pdo->prepare("SELECT id, minimo_compra FROM productos WHERE id IN ($placeholders)");
    $stmtMin->execute($prodIds);
    $minimosPorProducto = array_column($stmtMin->fetchAll(), 'minimo_compra', 'id');

    foreach ($detalles as $d) {
        $minProd = isset($minimosPorProducto[$d['producto_id']]) ? (int)$minimosPorProducto[$d['producto_id']] : 1;
        if ($minProd > 1 && $d['cantidad'] >= ($minProd * $multiplicadorPedidoGrande)) {
            $esPedidoGrande = true;
            break;
        }
    }
}

// 3. Generar Link Seguro y Mensaje de WhatsApp
$secret = 'fermento_secure_token_2026';
$token = substr(hash('sha256', $pedido['id'] . $pedido['fecha'] . $secret), 0, 10);

// Usar $_SERVER['HTTP_HOST'] para generar el link completo
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_dir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$url_recibo = "{$protocol}://{$host}{$base_dir}/recibo.php?id={$pedido['id']}&token={$token}";

// Obtener el nombre de la zona
$zona_nombre = "Envío a domicilio";
if (!empty($pedido['zona_envio_id'])) {
    $stmtZ = $pdo->prepare("SELECT nombre FROM zonas_envio WHERE id = ?");
    $stmtZ->execute([$pedido['zona_envio_id']]);
    $z = $stmtZ->fetchColumn();
    if ($z) $zona_nombre = $z;
} elseif ($pedido['zona_envio_id'] === 0 || $pedido['zona_envio_id'] === '0') {
    $zona_nombre = "Recoger en tienda (whatsapp)";
}

// ── Construir mensaje completo y automático ──────────────────────────────
$nombreCliente = explode(' ', $pedido['nombre_cliente'])[0];
$msj  = "🍞 *Nuevo Pedido Fermento #" . str_pad($pedido['id'], 6, '0', STR_PAD_LEFT) . "*\n";
$msj .= "────────────────────────\n";
$msj .= "👤 *Cliente:* " . $pedido['nombre_cliente'] . "\n";
$msj .= "📞 *Tel:* " . $pedido['telefono'] . "\n";
$msj .= "📍 *Dirección:* " . $pedido['direccion_envio'] . "\n";
$msj .= "🚚 *Zona:* " . $zona_nombre . "\n";

// Fecha y hora de entrega programada
if (!empty($pedido['fecha_envio_programada'])) {
    $msj .= "📅 *Entrega programada:* " . date('d/m/Y', strtotime($pedido['fecha_envio_programada']));
    if (!empty($pedido['hora_envio_programada'])) {
        list($hh, $mm) = explode(':', $pedido['hora_envio_programada']);
        $h12 = ((int)$hh % 12 ?: 12) . ':' . $mm . ((int)$hh < 12 ? ' AM' : ' PM');
        $msj .= " a las " . $h12;
    }
    $msj .= "\n";
}

// Detalle de productos
$msj .= "────────────────────────\n";
$msj .= "🛒 *Detalle del pedido:*\n";
foreach ($detalles as $d) {
    $msj .= "  • " . $d['cantidad'] . "x " . $d['nombre_producto'];
    $msj .= " — Q" . number_format($d['precio_unitario'] * $d['cantidad'], 2) . "\n";
}
$msj .= "────────────────────────\n";
$msj .= "💰 *Subtotal:* Q" . number_format($pedido['subtotal'], 2) . "\n";
$msj .= "🚚 *Envío:* " . ($pedido['zona_envio_id'] === null ? 'A coordinar' : 'Q' . number_format($pedido['costo_envio'], 2)) . "\n";
$msj .= "✅ *TOTAL A PAGAR: Q" . number_format($pedido['total'], 2) . "*\n";

// Recibo online
$msj .= "\n🧾 Ver recibo validado:\n" . $url_recibo;

$telefono_ws = getConfig('whatsapp_numero', '50239754421');
$link_ws = "https://wa.me/{$telefono_ws}?text=" . urlencode($msj);
?>

<style>
    /* ESTILOS EXCLUSIVOS PARA ESTA PÁGINA */
    body { background-color: #f9f9f9; }

    .confirmation-wrapper {
        min-height: 80vh;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 40px 20px;
    }

    .success-card {
        background: white;
        width: 100%;
        max-width: 550px;
        border-radius: 20px;
        box-shadow: 0 15px 40px rgba(0,0,0,0.08);
        overflow: hidden;
        text-align: center;
        position: relative;
        border-top: 5px solid var(--accent-toast); /* Borde naranja arriba */
    }

    /* Animación del Check */
    .icon-container {
        margin-top: -40px;
        background: white;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        position: relative;
        animation: popIn 0.6s cubic-bezier(0.68, -0.55, 0.27, 1.55);
    }
    
    @keyframes popIn {
        0% { transform: scale(0); opacity: 0; }
        100% { transform: scale(1); opacity: 1; }
    }

    .success-header {
        padding: 40px 30px 20px;
    }
    .success-header h1 {
        font-family: 'Merriweather', serif;
        color: #1F1F1F;
        font-size: 1.8rem;
        margin-bottom: 10px;
    }
    .order-number {
        background: #f0f0f0;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.9rem;
        color: #666;
        display: inline-block;
        font-weight: 600;
    }

    /* Caja de Resumen estilo "Ticket" */
    .receipt-box {
        background: #fffbf5; /* Crema muy suave */
        margin: 0 30px 30px;
        padding: 20px;
        border-radius: 10px;
        border: 1px dashed #e0c09e;
        text-align: left;
    }
    
    .receipt-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 0.9rem;
        color: #555;
    }
    .receipt-total {
        border-top: 1px solid #e0c09e;
        margin-top: 10px;
        padding-top: 10px;
        display: flex;
        justify-content: space-between;
        font-weight: 700;
        font-size: 1.1rem;
        color: var(--accent-toast);
    }

    /* Paso Final (Call to Action) */
    .final-step {
        padding: 30px;
        background: #f4fff4; /* Verde muy suave */
        border-top: 1px solid #eef;
    }
    .step-text {
        color: #2e7d32;
        font-weight: 600;
        margin-bottom: 15px;
        font-size: 0.95rem;
    }

    /* Botones */
    .btn-ws-large {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background-color: #25D366;
        color: white;
        text-decoration: none;
        padding: 15px;
        border-radius: 50px;
        font-weight: bold;
        font-size: 1.1rem;
        box-shadow: 0 5px 15px rgba(37, 211, 102, 0.3);
        transition: transform 0.2s;
        animation: pulseWs 2s infinite;
    }
    @keyframes pulseWs {
        0% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.7); }
        70% { box-shadow: 0 0 0 15px rgba(37, 211, 102, 0); }
        100% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0); }
    }
    .btn-ws-large:hover {
        transform: translateY(-3px);
        background-color: #20bd5a;
    }

    .secondary-actions {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-top: 20px;
    }
    .link-secondary {
        color: #777;
        text-decoration: none;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: color 0.2s;
    }
    .link-secondary:hover { color: var(--accent-toast); }

</style>

<div class="confirmation-wrapper">
    <div class="success-card">
        
        <div style="height: 40px;"></div> <div class="icon-container">
            <i class="fas fa-check" style="font-size: 2.5rem; color: var(--accent-toast);"></i>
        </div>

        <div class="success-header">
            <h1>¡Pedido Recibido!</h1>
            <p style="color: #666; margin-bottom: 10px;">Gracias por tu preferencia, <?php echo htmlspecialchars(explode(' ', $pedido['nombre_cliente'])[0]); ?>.</p>
            <span class="order-number">Orden #<?php echo str_pad($pedido['id'], 6, "0", STR_PAD_LEFT); ?></span>
        </div>

        <div class="receipt-box">
            <h4 style="margin-top: 0; margin-bottom: 15px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: #999;">Resumen de Compra</h4>
            
            <?php foreach($detalles as $d): ?>
            <div class="receipt-item">
                <span><?php echo $d['cantidad']; ?>x <?php echo $d['nombre_producto']; ?></span>
                <span>Q<?php echo number_format($d['precio_unitario'] * $d['cantidad'], 2); ?></span>
            </div>
            <?php endforeach; ?>

            <div class="receipt-item" style="border-top: 1px solid #e0c09e; margin-top:10px; padding-top:10px;">
                <span>Subtotal</span>
                <span>Q<?php echo number_format($pedido['subtotal'], 2); ?></span>
            </div>
            <div class="receipt-item">
                <span>Envío</span>
                <span><?php echo $pedido['zona_envio_id'] === null ? 'A coordinar' : 'Q'.number_format($pedido['costo_envio'], 2); ?></span>
            </div>

            <div class="receipt-total" style="align-items:center;">
                <span><?php echo $pedido['zona_envio_id'] === null ? 'TOTAL <span style="font-size:0.75rem; color:#888; display:block; font-weight:normal; margin-top:2px;">(Envío se cobrará por separado)</span>' : 'TOTAL A PAGAR'; ?></span>
                <span>Q<?php echo number_format($pedido['total'], 2); ?></span>
            </div>
        </div>

        <div class="final-step">
            <p class="step-text">
                <i class="fas fa-info-circle"></i> Último paso: Envíanos el pedido para coordinar.
            </p>

            <?php if ($esPedidoGrande): ?>
            <div style="background:#fff8ee;border:1.5px solid #D98C45;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:0.85rem;color:#7a4f1e;text-align:left;">
                <i class="fas fa-star" style="color:#D98C45;"></i>
                <strong>Detectamos que tu pedido es grande o especial.</strong>
                Te atenderemos de forma personalizada en WhatsApp para confirmar tiempos de producción.
            </div>
            <?php endif; ?>

            <a href="<?php echo $link_ws; ?>" target="_blank" class="btn-ws-large" id="btnWhatsappFinal">
                <i class="fab fa-whatsapp" style="font-size: 1.4rem;"></i> Continuar en WhatsApp
            </a>

            <div class="secondary-actions">
                <a href="index.php" class="link-secondary">
                    <i class="fas fa-home"></i> Volver al Inicio
                </a>
            </div>
        </div>

    </div>
</div>

<!-- ── Popup automático: recordatorio de enviar el pedido por WhatsApp (IT mundi #5) ── -->
<div id="overlayWhatsapp" class="ws-popup-overlay" role="dialog" aria-modal="true" aria-labelledby="wsPopupTitulo">
    <div class="ws-popup-card">
        <button type="button" class="ws-popup-close" onclick="cerrarPopupWhatsapp()" aria-label="Cerrar">
            <i class="fas fa-times"></i>
        </button>
        <div class="ws-popup-icon"><i class="fab fa-whatsapp"></i></div>
        <h3 id="wsPopupTitulo">¡Casi listo, <?php echo htmlspecialchars(explode(' ', $pedido['nombre_cliente'])[0]); ?>!</h3>
        <p>
            Tu pedido <strong>#<?php echo str_pad($pedido['id'], 6, '0', STR_PAD_LEFT); ?></strong> quedó registrado,
            pero <strong>todavía no lo hemos recibido</strong>. Para que empecemos a prepararlo,
            debes enviárnoslo por WhatsApp tocando el botón de abajo.
        </p>
        <a href="<?php echo $link_ws; ?>" target="_blank" class="btn-ws-large" onclick="cerrarPopupWhatsapp()">
            <i class="fab fa-whatsapp" style="font-size: 1.4rem;"></i> Enviar pedido por WhatsApp
        </a>
        <button type="button" class="ws-popup-later" onclick="cerrarPopupWhatsapp()">Ahora no</button>
    </div>
</div>

<style>
.ws-popup-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.55);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.ws-popup-overlay.visible { display: flex; }
.ws-popup-card {
    background: white;
    max-width: 400px;
    width: 100%;
    border-radius: 18px;
    padding: 32px 28px 26px;
    text-align: center;
    position: relative;
    box-shadow: 0 20px 50px rgba(0,0,0,0.25);
    animation: popIn 0.35s cubic-bezier(0.68, -0.55, 0.27, 1.55);
}
.ws-popup-icon {
    width: 64px; height: 64px;
    background: #eafaf1;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 16px;
    font-size: 1.8rem;
    color: #25D366;
}
.ws-popup-card h3 { font-family:'Merriweather',serif; margin-bottom: 10px; color:#1F1F1F; }
.ws-popup-card p { color:#666; font-size:0.9rem; line-height:1.6; margin-bottom:20px; }
.ws-popup-close {
    position: absolute; top: 14px; right: 14px;
    background: none; border: none; color: #aaa; font-size: 1rem; cursor: pointer;
    width: 30px; height: 30px; border-radius: 50%;
    transition: 0.2s;
}
.ws-popup-close:hover { background:#f0f0f0; color:#333; }
.ws-popup-later {
    display: block; margin: 14px auto 0; background: none; border: none;
    color: #999; font-size: 0.82rem; cursor: pointer; text-decoration: underline;
}
</style>

<script>
    function cerrarPopupWhatsapp() {
        document.getElementById('overlayWhatsapp').classList.remove('visible');
    }
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            document.getElementById('overlayWhatsapp').classList.add('visible');
        }, 700);
    });
    document.getElementById('overlayWhatsapp').addEventListener('click', function(e) {
        if (e.target === this) cerrarPopupWhatsapp();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') cerrarPopupWhatsapp();
    });
</script>

<!-- Auto-descarga del recibo -->
<iframe src="recibo_pdf.php?id=<?php echo $id_pedido; ?>" style="display:none;"></iframe>

<?php include 'includes/footer.php'; ?>