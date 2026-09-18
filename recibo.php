<?php
// recibo.php
$pageTitle = "Recibo del Pedido | Fermento";
require 'includes/db.php';
require 'includes/config.php';

// Validar ID y Token
if (!isset($_GET['id']) || !isset($_GET['token'])) {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h3>Acceso Denegado</h3><p>Faltan parámetros de seguridad.</p></div>");
}

$id_pedido = (int)$_GET['id'];
$token_recibido = $_GET['token'];

// Consultar pedido
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmt->execute([$id_pedido]);
$pedido = $stmt->fetch();

if (!$pedido) {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h3>Pedido No Encontrado</h3></div>");
}

// Generar Token Esperado
$secret = 'fermento_secure_token_2026';
$token_esperado = substr(hash('sha256', $pedido['id'] . $pedido['fecha'] . $secret), 0, 10);

if ($token_recibido !== $token_esperado) {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif; color:red;'><h3>Error de Seguridad</h3><p>El token del recibo no es válido o ha sido alterado.</p></div>");
}

// Consultar Detalles con Imagen de Producto
$sql_detalles = "
    SELECT dp.*, p.imagen 
    FROM detalles_pedido dp 
    LEFT JOIN productos p ON dp.producto_id = p.id 
    WHERE dp.pedido_id = ?
";
$stmt2 = $pdo->prepare($sql_detalles);
$stmt2->execute([$id_pedido]);
$detalles = $stmt2->fetchAll();

// Mapeo de Colores de Estado
$estado = strtolower($pedido['estado']);
$statusColor = '#f39c12'; // Pendiente (Naranja)
$statusIcon = 'fa-clock';
$statusText = 'Pendiente';

if ($estado == 'completado') {
    $statusColor = '#27ae60';
    $statusIcon = 'fa-check-circle';
    $statusText = 'Completado';
} elseif ($estado == 'en_camino') {
    $statusColor = '#3498db';
    $statusIcon = 'fa-truck';
    $statusText = 'En Camino';
} elseif ($estado == 'cancelado') {
    $statusColor = '#e74c3c';
    $statusIcon = 'fa-times-circle';
    $statusText = 'Cancelado';
}

include 'includes/header.php';
?>
<style>
    body { background-color: #f4f6f8; }
    .receipt-container {
        max-width: 650px;
        margin: 40px auto;
        background: white;
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    .receipt-header {
        background: var(--bg-cream);
        padding: 40px 30px;
        text-align: center;
        border-bottom: 2px dashed #e0c09e;
        position: relative;
    }
    .verified-badge {
        position: absolute;
        top: 20px;
        right: 20px;
        background: #eafaf1;
        color: #27ae60;
        padding: 5px 12px;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 5px;
        border: 1px solid #c8e6c9;
    }
    .receipt-header h1 {
        font-family: 'Merriweather', serif;
        margin: 0;
        color: #333;
        font-size: 2rem;
    }
    .receipt-body {
        padding: 30px;
    }
    .status-card {
        background: <?php echo $statusColor; ?>22;
        color: <?php echo $statusColor; ?>;
        padding: 15px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 30px;
        border: 1px solid <?php echo $statusColor; ?>55;
    }
    .status-card i { font-size: 1.8rem; }
    .status-card div { flex: 1; }
    .status-card h4 { margin: 0 0 3px 0; font-size: 1.1rem; }
    .status-card p { margin: 0; font-size: 0.85rem; opacity: 0.9; }

    .receipt-info {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 30px;
    }
    .info-block label {
        display: block;
        font-size: 0.75rem;
        color: #888;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 5px;
    }
    .info-block p {
        margin: 0;
        font-weight: 600;
        color: #333;
        font-size: 0.95rem;
    }

    .item-list {
        margin-bottom: 30px;
    }
    .item-row {
        display: flex;
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px solid #eee;
        gap: 15px;
    }
    .item-img {
        width: 60px;
        height: 60px;
        border-radius: 10px;
        object-fit: cover;
        background: #f9f9f9;
        border: 1px solid #eaeaea;
    }
    .item-details { flex: 1; }
    .item-name { font-weight: 600; color: #333; margin-bottom: 5px; display: block; }
    .item-qty { font-size: 0.85rem; color: #888; }
    .item-total { font-weight: 700; color: #333; font-size: 1rem; }

    .receipt-totals {
        background: #fcfcfc;
        padding: 20px;
        border-radius: 12px;
        border: 1px solid #eee;
    }
    .tot-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        color: #666;
        font-size: 0.9rem;
    }
    .tot-row.grand-total {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #ddd;
        font-size: 1.3rem;
        font-weight: 900;
        color: var(--accent-toast);
    }
    
    .receipt-footer {
        text-align: center;
        padding: 20px;
        color: #aaa;
        font-size: 0.8rem;
        border-top: 1px solid #eee;
    }
</style>

<div class="container">
    <div class="receipt-container">
        
        <div class="receipt-header">
            <div class="verified-badge"><i class="fas fa-shield-alt"></i> Verificado</div>
            <i class="fas fa-receipt" style="font-size: 2.5rem; color: var(--accent-toast); margin-bottom: 15px;"></i>
            <h1>Recibo de Pedido</h1>
            <p style="margin: 5px 0 0 0; color: #777;">#<?php echo str_pad($pedido['id'], 6, "0", STR_PAD_LEFT); ?></p>
        </div>

        <div class="receipt-body">
            
            <div class="status-card">
                <i class="fas <?php echo $statusIcon; ?>"></i>
                <div>
                    <h4>Estado: <?php echo $statusText; ?></h4>
                    <p>Este es el estado actual de tu orden en nuestra tienda.</p>
                </div>
            </div>

            <div class="receipt-info">
                <div class="info-block">
                    <label>Cliente</label>
                    <p><?php echo htmlspecialchars($pedido['nombre_cliente']); ?></p>
                </div>
                <div class="info-block">
                    <label>Fecha de Orden</label>
                    <p><?php echo date('d/m/Y h:i A', strtotime($pedido['fecha'])); ?></p>
                </div>
                <div class="info-block" style="grid-column: 1 / -1;">
                    <label>Dirección / Entrega</label>
                    <p><?php echo htmlspecialchars($pedido['direccion_envio']); ?></p>
                </div>
            </div>

            <h3 style="font-family: 'Merriweather'; font-size: 1.2rem; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 0;">Detalle de Productos</h3>
            <div class="item-list">
                <?php foreach($detalles as $d): ?>
                <?php 
                    $img = !empty($d['imagen']) ? 'assets/img/'.$d['imagen'] : 'assets/img/default_pan.png'; 
                    $subt = $d['precio_unitario'] * $d['cantidad'];
                ?>
                <div class="item-row">
                    <img src="<?php echo $img; ?>" class="item-img" alt="Producto" onerror="this.onerror=null;this.src='assets/img/default_pan.png';">
                    <div class="item-details">
                        <span class="item-name"><?php echo htmlspecialchars($d['nombre_producto']); ?></span>
                        <span class="item-qty"><?php echo $d['cantidad']; ?>x Q<?php echo number_format($d['precio_unitario'], 2); ?></span>
                    </div>
                    <div class="item-total">
                        Q<?php echo number_format($subt, 2); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="receipt-totals">
                <div class="tot-row">
                    <span>Subtotal</span>
                    <span>Q<?php echo number_format($pedido['subtotal'], 2); ?></span>
                </div>
                
                <?php if(isset($pedido['descuento']) && $pedido['descuento'] > 0): ?>
                <div class="tot-row" style="color: #e74c3c;">
                    <span>Descuento (<?php echo htmlspecialchars($pedido['cupon_codigo'] ?? ''); ?>)</span>
                    <span>-Q<?php echo number_format($pedido['descuento'], 2); ?></span>
                </div>
                <?php endif; ?>

                <div class="tot-row">
                    <span>Costo de Envío</span>
                    <span><?php echo $pedido['zona_envio_id'] === null ? 'A coordinar' : 'Q'.number_format($pedido['costo_envio'], 2); ?></span>
                </div>
                
                <div class="tot-row grand-total">
                    <span><?php echo $pedido['zona_envio_id'] === null ? 'TOTAL <span style="font-size:0.75rem; color:#888; display:block; font-weight:normal; margin-top:2px;">(Envío se cobrará por separado)</span>' : 'TOTAL'; ?></span>
                    <span style="display:flex; align-items:center;">Q<?php echo number_format($pedido['total'], 2); ?></span>
                </div>
            </div>

        </div>

        <div class="receipt-footer">
            <p style="margin: 0;">Documento generado automáticamente por el sistema de <strong><?php echo htmlspecialchars(getConfig('tienda_nombre', 'Fermento')); ?></strong></p>
        </div>

    </div>
    
    <div style="text-align: center; margin-bottom: 40px;">
        <a href="index.php" class="btn-outline"><i class="fas fa-home"></i> Volver a la tienda</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
