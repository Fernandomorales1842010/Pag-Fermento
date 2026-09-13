<?php
session_start();
require 'includes/db.php';
require 'includes/config.php';

// ── Verificación CSRF ─────────────────────────────────────────────────────
// Rechaza cualquier POST que no traiga el token correcto (previene CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify('checkout.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['carrito'])) {

    // CAMBIO CRÍTICO: Manejar usuario invitado
    $uid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null; 

    $nombre = $_POST['nombre'];
    $tel = $_POST['telefono'];
    $notas = isset($_POST['notas']) ? trim($_POST['notas']) : '';
    $dir = $_POST['direccion'];
    if (!empty($notas)) {
        $dir .= " (" . $notas . ")";
    }

    $zona_envio_id = isset($_POST['zona_envio_id']) ? (int)$_POST['zona_envio_id'] : null;
    $metodo_contacto = ($zona_envio_id === 0) ? 'whatsapp' : 'normal';

    // F1: Fecha y hora de entrega programada
    $fecha_envio = !empty($_POST['fecha_envio_programada']) ? trim($_POST['fecha_envio_programada']) : null;
    $hora_envio  = !empty($_POST['hora_envio_programada'])  ? trim($_POST['hora_envio_programada'])  : null;
    // Validar formato (YYYY-MM-DD y HH:MM)
    if ($fecha_envio && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_envio)) $fecha_envio = null;
    if ($hora_envio  && !preg_match('/^\d{2}:\d{2}$/', $hora_envio))         $hora_envio  = null;

    
    // Calcular costo de envío: primero busca el costo propio de la zona, si no usa el global
    $costo_envio = 0;
    if ($zona_envio_id > 0) {
        $stmtZona = $pdo->prepare("SELECT costo_envio FROM zonas_envio WHERE id = ? AND activa = 1");
        $stmtZona->execute([$zona_envio_id]);
        $zonaData = $stmtZona->fetch(PDO::FETCH_ASSOC);
        if ($zonaData) {
            // Si la zona tiene costo propio, usarlo; si no, usar el global
            $costo_envio = ($zonaData['costo_envio'] !== null)
                ? (float)$zonaData['costo_envio']
                : (float)getConfig('costo_envio', '30.00');
        }
    }
    // Si zona es 0 (WhatsApp), costo = 0 y zona = NULL
    if ($zona_envio_id === 0) {
        $zona_envio_id = null;
    }

    // SEGURIDAD: Recalcular subtotal desde la BD (no confiar en POST)
    $subtotal = 0;
    foreach ($_SESSION['carrito'] as $item) {
        if (isset($item['variante_id']) && $item['variante_id']) {
            $stmt_precio = $pdo->prepare("SELECT precio FROM producto_variantes WHERE id = ? AND producto_id = ?");
            $stmt_precio->execute([$item['variante_id'], $item['id']]);
        } else {
            $stmt_precio = $pdo->prepare("SELECT precio FROM productos WHERE id = ?");
            $stmt_precio->execute([$item['id']]);
        }
        $precio_real = $stmt_precio->fetchColumn();
        if ($precio_real !== false) {
            $subtotal += $precio_real * $item['cantidad'];
        }
    }

    if ($subtotal <= 0) {
        echo "<script>alert('❌ Error: El carrito está vacío o los productos no son válidos.'); window.location.href='index.php';</script>";
        exit;
    }

    // VALIDACIÓN DE MÍNIMOS DE COMPRA COMBINADO (defensa server-side)
    $errores_minimo = [];
    $cantidadesPorProducto = [];
    foreach ($_SESSION['carrito'] as $item) {
        $pId = $item['id'];
        $cantidadesPorProducto[$pId] = ($cantidadesPorProducto[$pId] ?? 0) + $item['cantidad'];
    }

    foreach ($_SESSION['carrito'] as $item) {
        $pId = $item['id'];
        $min = isset($item['minimo_compra_padre']) ? (int)$item['minimo_compra_padre'] : (isset($item['minimo_compra']) ? (int)$item['minimo_compra'] : 1);
        if ($min < 1) $min = 1;
        
        $sumaCombinada = $cantidadesPorProducto[$pId];
        if ($sumaCombinada < $min) {
            $nombreBase = explode(' (', $item['nombre'])[0];
            $errores_minimo[$pId] = htmlspecialchars($nombreBase) . " (mínimo combinado: $min, tienes: $sumaCombinada)";
        }
    }
    if (!empty($errores_minimo)) {
        $msg = "❌ Mínimo de compra no cumplido:\\n" . implode("\\n", $errores_minimo);
        echo "<script>alert('$msg'); window.location.href='checkout.php';</script>";
        exit;
    }

    $descuento = 0;
    $cupon_id = null;
    $cupon_codigo = null;
    if (isset($_SESSION['cupon'])) {
        $c = $_SESSION['cupon'];
        $cupon_id = $c['id'];
        $cupon_codigo = $c['codigo'];
        if ($c['tipo'] === 'porcentaje') {
            $descuento = $subtotal * ($c['valor'] / 100);
        } else {
            $descuento = $c['valor'];
        }
        if ($descuento > $subtotal) $descuento = $subtotal;
    }

    $total = $subtotal - $descuento + $costo_envio;

    try {
        $pdo->beginTransaction();

        // 1. INSERTAR EN PEDIDOS (incluye fecha+hora de entrega programada — F1)
        $sql = "INSERT INTO pedidos (usuario_id, nombre_cliente, direccion_envio, notas, telefono,
                subtotal, descuento, cupon_id, cupon_codigo, costo_envio, total,
                zona_envio_id, metodo_contacto, fecha_envio_programada, hora_envio_programada)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$uid, $nombre, $dir, $notas, $tel, $subtotal, $descuento,
                        $cupon_id, $cupon_codigo, $costo_envio, $total,
                        $zona_envio_id, $metodo_contacto,
                        $fecha_envio, $hora_envio]);
        $pedido_id = $pdo->lastInsertId();

        // 2. INSERTAR DETALLES Y RESTAR STOCK ATÓMICAMENTE
        $sql_detalle = "INSERT INTO detalles_pedido (pedido_id, producto_id, nombre_producto, precio_unitario, cantidad, variante_id, variante_nombre) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_detalle = $pdo->prepare($sql_detalle);

        $sql_stock_prod = "UPDATE productos SET stock = stock - ? WHERE id = ? AND stock >= ?";
        $stmt_stock_prod = $pdo->prepare($sql_stock_prod);

        $sql_stock_var = "UPDATE producto_variantes SET stock = stock - ? WHERE id = ? AND stock >= ?";
        $stmt_stock_var = $pdo->prepare($sql_stock_var);

        foreach ($_SESSION['carrito'] as $item) {
            $varianteId = isset($item['variante_id']) && $item['variante_id'] ? $item['variante_id'] : null;

            if ($varianteId) {
                $stmt_p = $pdo->prepare("SELECT v.nombre as var_nombre, v.precio, p.nombre as prod_nombre FROM producto_variantes v JOIN productos p ON v.producto_id = p.id WHERE v.id = ?");
                $stmt_p->execute([$varianteId]);
                $prod_data = $stmt_p->fetch();
                if (!$prod_data) throw new Exception("Variante no encontrada.");
                
                $nombre_completo = $prod_data['prod_nombre'] . ' (' . $prod_data['var_nombre'] . ')';
                $stmt_detalle->execute([$pedido_id, $item['id'], $nombre_completo, $prod_data['precio'], $item['cantidad'], $varianteId, $prod_data['var_nombre']]);
                
                $stmt_stock_var->execute([$item['cantidad'], $varianteId, $item['cantidad']]);
                if ($stmt_stock_var->rowCount() === 0) {
                    throw new Exception("Stock insuficiente para: " . $nombre_completo);
                }
            } else {
                $stmt_p = $pdo->prepare("SELECT nombre, precio FROM productos WHERE id = ?");
                $stmt_p->execute([$item['id']]);
                $prod_data = $stmt_p->fetch();
                if (!$prod_data) throw new Exception("Producto no encontrado: ID " . $item['id']);
                
                $stmt_detalle->execute([$pedido_id, $item['id'], $prod_data['nombre'], $prod_data['precio'], $item['cantidad'], null, null]);
                
                $stmt_stock_prod->execute([$item['cantidad'], $item['id'], $item['cantidad']]);
                if ($stmt_stock_prod->rowCount() === 0) {
                    throw new Exception("Stock insuficiente para: " . $prod_data['nombre']);
                }
            }
        }

        // Actualizar usos del cupón si se usó
        if ($cupon_id) {
            $pdo->prepare("UPDATE cupones SET usos_actuales = usos_actuales + 1 WHERE id = ?")->execute([$cupon_id]);
        }

        $pdo->commit();

        unset($_SESSION['carrito']);
        unset($_SESSION['cupon']);

        header("Location: pedido_confirmado.php?id=" . $pedido_id);
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // Redirigir con error para que el usuario sepa qué pasó
        echo "<script>
            alert('❌ " . addslashes($e->getMessage()) . "');
            window.location.href = 'index.php';
        </script>";
        exit;
    }
} else {
    header("Location: index.php");
}
?>