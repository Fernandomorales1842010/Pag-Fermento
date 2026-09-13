<?php
require '../includes/db.php';
header('Content-Type: application/json');

// 1. Obtener y validar parámetros de fecha
$desde = $_GET['desde'] ?? date('Y-m-d', strtotime('-30 days'));
$hasta = $_GET['hasta'] ?? date('Y-m-d');

// Validar formato YYYY-MM-DD para prevenir inyección SQL
$dateRegex = '/^\d{4}-\d{2}-\d{2}$/';
if (!preg_match($dateRegex, $desde) || !preg_match($dateRegex, $hasta)) {
    $desde = date('Y-m-d', strtotime('-30 days'));
    $hasta = date('Y-m-d');
}

// Parámetros de costo
$min_costo = isset($_GET['min_costo']) && is_numeric($_GET['min_costo']) ? (float)$_GET['min_costo'] : 0;
$max_costo = isset($_GET['max_costo']) && is_numeric($_GET['max_costo']) ? (float)$_GET['max_costo'] : 999999;

// -----------------------------------------
// SISTEMA DE CACHÉ
// -----------------------------------------
$cache_dir = '../cache/';
if (!is_dir($cache_dir)) {
    mkdir($cache_dir, 0755, true);
}
$cache_file = $cache_dir . 'stats_' . md5($desde . '_' . $hasta . '_' . $min_costo . '_' . $max_costo) . '.json';
$cache_ttl  = 300; // 5 minutos

if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_ttl) {
    header('X-Cache: HIT');
    echo file_get_contents($cache_file);
    exit;
}
header('X-Cache: MISS');
// -----------------------------------------

// Variables comunes para WHERE
$whereCostos = " AND total >= ? AND total <= ?";
$whereCostosP = " AND p.total >= ? AND p.total <= ?";
$params = [$desde, $hasta, $min_costo, $max_costo];

// 1. KPI: Total Ventas del Periodo
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total), 0) FROM pedidos WHERE DATE(fecha) BETWEEN ? AND ? AND estado != 'cancelado' $whereCostos");
$stmt->execute($params);
$ventasPeriodo = $stmt->fetchColumn() ?: 0;

// 2. KPI: Total Pedidos del Periodo
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE DATE(fecha) BETWEEN ? AND ? $whereCostos");
$stmt->execute($params);
$pedidosPeriodo = $stmt->fetchColumn() ?: 0;

// 3. KPI: Ticket Promedio
$ticketPromedio = ($pedidosPeriodo > 0) ? round($ventasPeriodo / $pedidosPeriodo, 2) : 0;

// 4. GRÁFICA: Ventas Diarias
$stmt = $pdo->prepare(
    "SELECT DATE(fecha) as dia, SUM(total) as total
     FROM pedidos
     WHERE DATE(fecha) BETWEEN ? AND ? AND estado != 'cancelado' $whereCostos
     GROUP BY dia ORDER BY dia ASC"
);
$stmt->execute($params);
$ventasDiarias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. GRÁFICA: Top Productos (En el periodo)
$stmt = $pdo->prepare(
    "SELECT nombre_producto, SUM(dp.cantidad) as cantidad
     FROM detalles_pedido dp
     JOIN pedidos p ON dp.pedido_id = p.id
     WHERE DATE(p.fecha) BETWEEN ? AND ? AND p.estado != 'cancelado' $whereCostosP
     GROUP BY nombre_producto
     ORDER BY cantidad DESC LIMIT 8"
);
$stmt->execute($params);
$topProductos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 6. GRÁFICA: Categorías (En el periodo)
$stmt = $pdo->prepare(
    "SELECT prod.categoria, SUM(dp.cantidad) as cantidad
     FROM detalles_pedido dp
     JOIN pedidos p ON dp.pedido_id = p.id
     JOIN productos prod ON dp.producto_id = prod.id
     WHERE DATE(p.fecha) BETWEEN ? AND ? AND p.estado != 'cancelado' $whereCostosP
     GROUP BY prod.categoria"
);
$stmt->execute($params);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 7. GRÁFICA: Estado de Pedidos (En el periodo)
$stmt = $pdo->prepare(
    "SELECT estado, COUNT(*) as cantidad
     FROM pedidos
     WHERE DATE(fecha) BETWEEN ? AND ? $whereCostos
     GROUP BY estado"
);
$stmt->execute($params);
$estados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 8. STOCK ACTUAL (Snapshot - No filtrable por fecha pero ordenado)
$sqlStock = "
    SELECT id, nombre, stock, 'simple' as tipo 
    FROM productos 
    WHERE stock < 15 AND tiene_variantes = 0
    UNION ALL
    SELECT v.id, CONCAT(p.nombre, ' (', v.nombre, ')') as nombre, v.stock, 'variante' as tipo
    FROM producto_variantes v
    JOIN productos p ON v.producto_id = p.id
    WHERE v.stock < 15
    ORDER BY stock ASC LIMIT 10
";
$stock = $pdo->query($sqlStock)->fetchAll(PDO::FETCH_ASSOC);

// 9. PRODUCTO MÁS VENDIDO — en el periodo (unidades + monto)
$stmt = $pdo->prepare(
    "SELECT dp.nombre_producto,
            SUM(dp.cantidad) as unidades,
            SUM(dp.cantidad * dp.precio_unitario) as monto
     FROM detalles_pedido dp
     JOIN pedidos p ON dp.pedido_id = p.id
     WHERE DATE(p.fecha) BETWEEN ? AND ? AND p.estado != 'cancelado' $whereCostosP
     GROUP BY dp.nombre_producto
     ORDER BY unidades DESC
     LIMIT 1"
);
$stmt->execute($params);
$productoTop = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['nombre_producto' => '—', 'unidades' => 0, 'monto' => 0];

// 11. VENTAS POR AÑO — todos los años con pedidos (no filtrado por rango)
$stmtAnual = $pdo->query(
    "SELECT YEAR(fecha) as anio,
            SUM(total) as total,
            COUNT(*) as pedidos
     FROM pedidos
     WHERE estado != 'cancelado'
     GROUP BY anio
     ORDER BY anio ASC"
);
$ventasPorAnio = $stmtAnual->fetchAll(PDO::FETCH_ASSOC);

// 12. HORARIO DE MÁS VENTA — pedidos por hora del día en el periodo
$stmt = $pdo->prepare(
    "SELECT HOUR(p.fecha) as hora,
            COUNT(*) as pedidos,
            SUM(p.total) as monto
     FROM pedidos p
     WHERE DATE(p.fecha) BETWEEN ? AND ? AND p.estado != 'cancelado' $whereCostos
     GROUP BY hora
     ORDER BY hora ASC"
);
$stmt->execute($params);
$horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$output = json_encode([
    'rango'           => ['desde' => $desde, 'hasta' => $hasta],
    'kpi_ventas'      => $ventasPeriodo,
    'kpi_pedidos'     => $pedidosPeriodo,
    'kpi_ticket'      => $ticketPromedio,
    'ventas_diarias'  => $ventasDiarias,
    'top_productos'   => $topProductos,
    'categorias'      => $categorias,
    'estados'         => $estados,
    'stock'           => $stock,
    'producto_top'    => $productoTop,
    'ventas_por_anio' => $ventasPorAnio,
    'horarios'        => $horarios,
]);

file_put_contents($cache_file, $output);
echo $output;
?>