<?php
// admin/api_check_orders.php
header('Content-Type: application/json');
require '../includes/db.php';

try {
    // Obtenemos el ID del pedido más reciente
    $stmt = $pdo->query("SELECT id, nombre_cliente, total FROM pedidos ORDER BY id DESC LIMIT 1");
    $lastOrder = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'last_id' => $lastOrder ? (int)$lastOrder['id'] : 0,
        'cliente' => $lastOrder ? $lastOrder['nombre_cliente'] : '',
        'monto'   => $lastOrder ? $lastOrder['total'] : 0
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
