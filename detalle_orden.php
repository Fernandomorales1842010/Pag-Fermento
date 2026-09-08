<?php
$pageTitle = "Detalle de Orden | Fermento";
require 'includes/db.php';
include 'includes/header.php';
include 'includes/nav.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: mis_pedidos.php");
    exit;
}

$pedido_id = (int)$_GET['id'];
$uid = $_SESSION['user_id'];

// 1. Consultar Pedido (CON SEGURIDAD: Solo si es de mi usuario)
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ? AND usuario_id = ?");
$stmt->execute([$pedido_id, $uid]);
$pedido = $stmt->fetch();

if (!$pedido) {
    echo "<div class='container section'><p>Pedido no encontrado o no tienes permiso para verlo.</p></div>";
    include 'includes/footer.php';
    exit;
}

// 2. Consultar Items
$stmt2 = $pdo->prepare("SELECT * FROM detalles_pedido WHERE pedido_id = ?");
$stmt2->execute([$pedido_id]);
$items = $stmt2->fetchAll();
?>

<div class="container section">
    <a href="mis_pedidos.php" style="text-decoration: none; color: #666; margin-bottom: 20px; display: inline-block;">
        ← Volver a mis pedidos
    </a>

    <div style="background: white; border: 1px solid #eee; border-radius: 10px; padding: 30px; max-width: 800px; margin: 0 auto;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f4f4f4; padding-bottom: 20px; margin-bottom: 20px;">
            
            <div style="display: flex; align-items: center; gap: 15px;">
                <h2 style="margin: 0;">Orden #<?php echo $pedido['id']; ?></h2>
                
                <a href="recibo_pdf.php?id=<?php echo $pedido['id']; ?>" target="_blank" style="text-decoration: none; font-size: 0.85rem; color: #D98C45; border: 1px solid #D98C45; padding: 5px 10px; border-radius: 5px; transition: 0.3s;">
                    <i class="fas fa-file-pdf"></i> Descargar Recibo
                </a>
            </div>
            
            <span style="background: #f4f4f4; padding: 5px 10px; border-radius: 5px; font-weight: bold; font-size: 0.9rem;">
                <?php echo date('d/m/Y', strtotime($pedido['fecha'])); ?>
            </span>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <div>
                <h4 style="color: #888; text-transform: uppercase; font-size: 0.8rem;">Entregado a:</h4>
                <p><strong><?php echo htmlspecialchars($pedido['nombre_cliente']); ?></strong></p>
                <p><?php echo htmlspecialchars($pedido['direccion_envio']); ?></p>
            </div>
            <div style="text-align: right;">
                <h4 style="color: #888; text-transform: uppercase; font-size: 0.8rem;">Estado:</h4>
                <p style="font-weight: bold; text-transform: uppercase; color: var(--text-black);">
                    <?php echo $pedido['estado']; ?>
                </p>
            </div>
        </div>

        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #fafafa; border-bottom: 1px solid #eee;">
                    <th style="text-align: left; padding: 10px;">Producto</th>
                    <th style="padding: 10px;">Cant.</th>
                    <th style="text-align: right; padding: 10px;">Precio</th>
                    <th style="text-align: right; padding: 10px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $item): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px;"><?php echo htmlspecialchars($item['nombre_producto']); ?></td>
                    <td style="padding: 10px; text-align: center;"><?php echo $item['cantidad']; ?></td>
                    <td style="padding: 10px; text-align: right;">Q<?php echo number_format($item['precio_unitario'], 2); ?></td>
                    <td style="padding: 10px; text-align: right;">Q<?php echo number_format($item['precio_unitario'] * $item['cantidad'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
                
                <tr>
                    <td colspan="3" style="text-align: right; padding: 20px 10px; font-weight: bold;">TOTAL:</td>
                    <td style="text-align: right; padding: 20px 10px; font-weight: bold; color: var(--accent-toast); font-size: 1.2rem;">
                        Q<?php echo number_format($pedido['total'], 2); ?>
                    </td>
                </tr>
            </tbody>
        </table>

    </div>
</div>

<?php include 'includes/footer.php'; ?>