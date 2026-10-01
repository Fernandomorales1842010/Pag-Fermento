<?php
// admin/api_verificar_pedido.php
// Endpoint ligero para verificar si un pedido existe
require '../includes/db.php';
include 'includes/auth_admin.php';
require_can('crear_merma');

header('Content-Type: application/json');
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['ok' => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, nombre_cliente FROM pedidos WHERE id = ?");
$stmt->execute([$id]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if ($pedido) {
    echo json_encode(['ok' => true, 'cliente' => $pedido['nombre_cliente']]);
} else {
    echo json_encode(['ok' => false]);
}
