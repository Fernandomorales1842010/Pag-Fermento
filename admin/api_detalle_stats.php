<?php
require '../includes/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$tipo = $data['tipo'] ?? '';
$valor = $data['valor'] ?? '';
$desde = $data['desde'] ?? '2000-01-01';
$hasta = $data['hasta'] ?? '2099-12-31';
$min_costo = isset($data['min_costo']) && is_numeric($data['min_costo']) ? (float)$data['min_costo'] : 0;
$max_costo = isset($data['max_costo']) && is_numeric($data['max_costo']) ? (float)$data['max_costo'] : 999999;

$resultado = [];

// Helper para fecha y costos
$dateCond = " DATE(p.fecha) BETWEEN ? AND ? AND p.total >= ? AND p.total <= ? ";
$params = [$desde, $hasta, $min_costo, $max_costo];

if ($tipo === 'top_producto') {
    $sql = "SELECT p.id as Pedido, p.nombre_cliente as Cliente, p.fecha as Fecha, dp.cantidad as Cantidad, (dp.cantidad * dp.precio_unitario) as Subtotal
            FROM detalles_pedido dp
            JOIN pedidos p ON dp.pedido_id = p.id
            WHERE dp.nombre_producto = ? AND p.estado != 'cancelado' 
            AND $dateCond
            ORDER BY p.fecha DESC LIMIT 20";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$valor, ...$params]);
    $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($tipo === 'stock') {
    $sql = "SELECT nombre, precio, categoria FROM (
                SELECT nombre, precio, categoria FROM productos
                UNION ALL
                SELECT CONCAT(p.nombre, ' (', v.nombre, ')') as nombre, v.precio, p.categoria 
                FROM producto_variantes v JOIN productos p ON v.producto_id = p.id
            ) as all_prods
            WHERE nombre = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$valor]);
    $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($tipo === 'categoria') {
    $sql = "SELECT dp.nombre_producto as Producto, SUM(dp.cantidad) as Vendidos, SUM(dp.cantidad * dp.precio_unitario) as Total
            FROM detalles_pedido dp
            JOIN pedidos p ON dp.pedido_id = p.id
            JOIN productos prod ON dp.producto_id = prod.id
            WHERE prod.categoria = ? AND p.estado != 'cancelado'
            AND $dateCond
            GROUP BY dp.nombre_producto";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$valor, ...$params]);
    $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($tipo === 'ventas_diarias') {
    $sql = "SELECT id as Pedido, nombre_cliente as Cliente, estado as Estado, total as Total, fecha as Fecha
            FROM pedidos p
            WHERE DATE(fecha) = ? AND estado != 'cancelado' AND total >= ? AND total <= ?
            ORDER BY fecha DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$valor, $min_costo, $max_costo]);
    $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($tipo === 'pedidos_pendientes') {
    $sql = "SELECT id as Pedido, nombre_cliente as Cliente, total as Total, fecha as Fecha
            FROM pedidos p
            WHERE estado = 'pendiente'
            ORDER BY fecha ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($resultado);
?>