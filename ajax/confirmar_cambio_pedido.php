<?php
/**
 * ajax/confirmar_cambio_pedido.php
 * Endpoint para que el cliente confirme o rechace un cambio hecho por el supervisor (F2/F3).
 *
 * POST:
 * - pedido_id
 * - accion: 'confirmar' o 'rechazar'
 */

require '../includes/db.php';
require '../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Sesión expirada.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos.']);
    exit;
}

$pedido_id = (int)($input['pedido_id'] ?? 0);
$accion = $input['accion'] ?? '';

if ($pedido_id <= 0 || !in_array($accion, ['confirmar', 'rechazar'])) {
    echo json_encode(['success' => false, 'error' => 'Acción inválida.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Obtener pedido
    $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ? AND usuario_id = ? AND estado = 'pendiente_confirmacion'");
    $stmt->execute([$pedido_id, $_SESSION['user_id']]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        throw new Exception('Pedido no encontrado o ya fue procesado.');
    }

    if ($accion === 'confirmar') {
        // Pasa a estado 'pendiente' (aprobado por el cliente)
        $pdo->prepare("UPDATE pedidos SET estado = 'pendiente' WHERE id = ?")->execute([$pedido_id]);
        
        $pdo->prepare("INSERT INTO pedido_historial (pedido_id, usuario_id, campo_modificado, valor_anterior, valor_nuevo, motivo) VALUES (?, ?, 'estado', 'pendiente_confirmacion', 'pendiente', 'Cliente confirmó los cambios')")->execute([$pedido_id, $_SESSION['user_id']]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'msg' => '✅ Cambios confirmados. Tu pedido está en preparación.']);
    } else {
        // Rechazar -> Cancelar el pedido
        $pdo->prepare("UPDATE pedidos SET estado = 'cancelado' WHERE id = ?")->execute([$pedido_id]);
        
        $pdo->prepare("INSERT INTO pedido_historial (pedido_id, usuario_id, campo_modificado, valor_anterior, valor_nuevo, motivo) VALUES (?, ?, 'estado', 'pendiente_confirmacion', 'cancelado', 'Cliente rechazó los cambios')")->execute([$pedido_id, $_SESSION['user_id']]);

        $pdo->commit();
        echo json_encode(['success' => true, 'msg' => '❌ Pedido cancelado exitosamente.']);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
