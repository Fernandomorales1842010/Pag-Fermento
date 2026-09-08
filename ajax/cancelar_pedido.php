<?php
// ajax/cancelar_pedido.php
// Gap 1+2: Permite al cliente cancelar un pedido pendiente.
// En la misma transacción: cancela el pedido, restaura el stock
// y revierte el uso del cupón si se había aplicado uno.

session_start();
require '../includes/db.php';
require '../includes/config.php';

header('Content-Type: application/json');

// ── 1. El usuario debe estar autenticado ────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Debes iniciar sesión para cancelar un pedido.']);
    exit;
}

// ── 2. Leer body JSON ───────────────────────────────────────────────────────
$data      = json_decode(file_get_contents('php://input'), true) ?? [];
$pedido_id = isset($data['pedido_id']) ? (int)$data['pedido_id'] : 0;
$csrf      = $data['csrf_token'] ?? '';

if ($pedido_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de pedido inválido.']);
    exit;
}

// ── 3. Verificar CSRF (token de un solo uso reutilizado del mismo mecanismo) ─
$esperado = $_SESSION['csrf_token'] ?? '';
if (!$csrf || !$esperado || !hash_equals($esperado, $csrf)) {
    unset($_SESSION['csrf_token']);
    echo json_encode(['success' => false, 'error' => 'Token de seguridad inválido. Recarga la página.']);
    exit;
}
// Invalidar el token después de uso exitoso
unset($_SESSION['csrf_token']);

// ── 4. Cargar el pedido y verificar propiedad + estado ─────────────────────
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ? AND usuario_id = ?");
$stmt->execute([$pedido_id, $_SESSION['user_id']]);
$pedido = $stmt->fetch();

if (!$pedido) {
    echo json_encode(['success' => false, 'error' => 'Pedido no encontrado o no te pertenece.']);
    exit;
}

if (strtolower($pedido['estado']) !== 'pendiente') {
    echo json_encode([
        'success' => false,
        'error'   => 'Solo puedes cancelar pedidos en estado "Pendiente". Este pedido ya está ' . ucfirst($pedido['estado']) . '.'
    ]);
    exit;
}

// ── 5. Cargar detalles del pedido (necesarios para restaurar stock) ─────────
$stmtDet = $pdo->prepare("SELECT * FROM detalles_pedido WHERE pedido_id = ?");
$stmtDet->execute([$pedido_id]);
$detalles = $stmtDet->fetchAll();

// ── 6. Transacción atómica: cancelar + restaurar stock + revertir cupón ────
try {
    $pdo->beginTransaction();

    // 6a. Marcar el pedido como cancelado
    $pdo->prepare("UPDATE pedidos SET estado = 'cancelado' WHERE id = ?")->execute([$pedido_id]);

    // 6b. Restaurar stock de cada producto/variante
    $sqlRestVar  = "UPDATE producto_variantes SET stock = stock + ? WHERE id = ?";
    $sqlRestProd = "UPDATE productos SET stock = stock + ? WHERE id = ?";
    $stmtVar  = $pdo->prepare($sqlRestVar);
    $stmtProd = $pdo->prepare($sqlRestProd);

    foreach ($detalles as $d) {
        if (!empty($d['variante_id'])) {
            // Producto con variante: restaurar stock de la variante
            $stmtVar->execute([$d['cantidad'], $d['variante_id']]);
        } else {
            // Producto simple: restaurar stock del producto
            $stmtProd->execute([$d['cantidad'], $d['producto_id']]);
        }
    }

    // 6c. Revertir uso del cupón si se aplicó uno en este pedido
    if (!empty($pedido['cupon_id'])) {
        $pdo->prepare(
            "UPDATE cupones SET usos_actuales = GREATEST(0, usos_actuales - 1) WHERE id = ?"
        )->execute([$pedido['cupon_id']]);
        // GREATEST(0, ...) evita que usos_actuales quede negativo por cualquier razón
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'msg'     => "Tu pedido #{$pedido_id} ha sido cancelado. El stock de los productos ha sido restaurado."
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("cancelar_pedido error (pedido #{$pedido_id}): " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno al cancelar el pedido. Intenta de nuevo.']);
}
?>
