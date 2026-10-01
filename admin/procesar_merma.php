<?php
// admin/procesar_merma.php
require '../includes/db.php';
require '../includes/config.php';
include 'includes/auth_admin.php';
require_can('crear_merma');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$pedido_id = (int)($data['pedido_id'] ?? 0);
$notas     = trim($data['notas'] ?? '');
$items     = $data['items'] ?? [];
$confirmado = (bool)($data['confirmado'] ?? false);

// Validaciones
if (!$confirmado) {
    echo json_encode(['ok' => false, 'msg' => 'Debes confirmar que la merma debe existir.']);
    exit;
}
if ($pedido_id <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'El número de pedido es obligatorio.']);
    exit;
}
if (empty($items)) {
    echo json_encode(['ok' => false, 'msg' => 'Debes agregar al menos un producto a la merma.']);
    exit;
}

// Verificar que el pedido existe
$stmtPed = $pdo->prepare("SELECT id, nombre_cliente FROM pedidos WHERE id = ?");
$stmtPed->execute([$pedido_id]);
$pedido = $stmtPed->fetch(PDO::FETCH_ASSOC);
if (!$pedido) {
    echo json_encode(['ok' => false, 'msg' => "El pedido #$pedido_id no existe en el sistema."]);
    exit;
}

// Calcular total
$total = 0;
foreach ($items as $item) {
    $total += (float)($item['precio'] ?? 0) * (int)($item['cantidad'] ?? 0);
}

// Insertar merma
try {
    $pdo->beginTransaction();

    $stmtMerma = $pdo->prepare(
        "INSERT INTO mermas (pedido_id, supervisor_id, supervisor_nombre, notas, confirmado, total_merma, fecha)
         VALUES (?, ?, ?, ?, 1, ?, NOW())"
    );
    $stmtMerma->execute([
        $pedido_id,
        $_SESSION['user_id'],
        $_SESSION['user_nombre'] ?? 'Supervisor',
        $notas ?: null,
        $total
    ]);
    $merma_id = $pdo->lastInsertId();

    $stmtDet = $pdo->prepare(
        "INSERT INTO merma_detalles (merma_id, producto_id, nombre_producto, variante_id, variante_nombre, precio_unitario, cantidad)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    foreach ($items as $item) {
        $stmtDet->execute([
            $merma_id,
            $item['producto_id'] ?? null,
            $item['nombre'],
            $item['variante_id'] ?? null,
            $item['variante_nombre'] ?? null,
            (float)($item['precio'] ?? 0),
            (int)($item['cantidad'] ?? 1),
        ]);
    }

    $pdo->commit();

    adminLog('registrar_merma', 'mermas', $merma_id,
        "Pedido #{$pedido_id} — {$pedido['nombre_cliente']} — Q" . number_format($total, 2));

    echo json_encode([
        'ok'      => true,
        'msg'     => "✅ Merma registrada correctamente (ID #{$merma_id}).",
        'merma_id' => $merma_id,
        'total'   => $total,
        'fecha'   => date('d/m/Y H:i:s'),
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log('procesar_merma error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Error al guardar la merma. Intenta de nuevo.']);
}
