<?php
/**
 * ajax/modificar_pedido.php
 * Endpoint para que el supervisor/admin modifique los ítems de un pedido (F2).
 *
 * Recibe (POST JSON):
 * - pedido_id (int)
 * - motivo (string)
 * - items (array):
 *   [
 *     { id_detalle: 1, cantidad: 2 },
 *     { id_detalle: 2, cantidad: 0 } // 0 = eliminar
 *   ]
 * - nuevos_items (array) [opcional]:
 *   [
 *     { producto_id: 1, variante_id: null, cantidad: 1 },
 *     { producto_id: 2, variante_id: 5, cantidad: 2 }
 *   ]
 */

require '../includes/db.php';
require '../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar sesión
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_rol'], ['admin', 'supervisor'])) {
    echo json_encode(['success' => false, 'error' => 'No tienes permisos.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos.']);
    exit;
}

$pedido_id = (int)($input['pedido_id'] ?? 0);
$motivo = trim($input['motivo'] ?? '');
$items = is_array($input['items'] ?? null) ? $input['items'] : [];
$nuevos_items = is_array($input['nuevos_items'] ?? null) ? $input['nuevos_items'] : [];

if ($pedido_id <= 0 || empty($motivo) || (empty($items) && empty($nuevos_items))) {
    echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios o motivo.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Obtener pedido actual
    $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        throw new Exception('Pedido no encontrado.');
    }

    if ($pedido['estado'] === 'cancelado' || $pedido['estado'] === 'completado') {
        throw new Exception('No se puede modificar un pedido cancelado o completado.');
    }

    // Obtener detalles actuales
    $stmt_detalles = $pdo->prepare("SELECT * FROM detalles_pedido WHERE pedido_id = ?");
    $stmt_detalles->execute([$pedido_id]);
    $detalles_actuales = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);
    $detalles_indexados = [];
    foreach ($detalles_actuales as $d) {
        $detalles_indexados[$d['id']] = $d;
    }

    $cambios = [];
    $nuevo_subtotal = 0;

    // 1. Procesar ítems existentes
    foreach ($items as $item) {
        $id_detalle = (int)$item['id_detalle'];
        $nueva_cant = (int)$item['cantidad'];

        if (!isset($detalles_indexados[$id_detalle])) continue;
        
        $det = $detalles_indexados[$id_detalle];
        $vieja_cant = (int)$det['cantidad'];

        if ($nueva_cant === 0) {
            // Eliminar
            $pdo->prepare("DELETE FROM detalles_pedido WHERE id = ?")->execute([$id_detalle]);
            $cambios[] = "- Eliminado: " . $det['nombre_producto'] . ($det['variante_nombre'] ? ' ('.$det['variante_nombre'].')' : '') . " (Era: $vieja_cant uds)";
            
            // Devolver stock (opcional, en este punto el stock ya está descontado, se devuelve)
            if ($det['variante_id']) {
                $pdo->prepare("UPDATE producto_variantes SET stock = stock + ? WHERE id = ?")->execute([$vieja_cant, $det['variante_id']]);
                $pdo->prepare("UPDATE productos SET stock = (SELECT SUM(stock) FROM producto_variantes WHERE producto_id = productos.id) WHERE id = ?")->execute([$det['producto_id']]);
            } else {
                $pdo->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?")->execute([$vieja_cant, $det['producto_id']]);
            }
        } elseif ($nueva_cant !== $vieja_cant) {
            // Modificar cantidad
            // TODO: si se desea validar stock al subir cantidad, habría que hacerlo aquí.
            $diferencia = $nueva_cant - $vieja_cant;
            
            // Ajustar stock
            if ($det['variante_id']) {
                $pdo->prepare("UPDATE producto_variantes SET stock = stock - ? WHERE id = ?")->execute([$diferencia, $det['variante_id']]);
                $pdo->prepare("UPDATE productos SET stock = (SELECT SUM(stock) FROM producto_variantes WHERE producto_id = productos.id) WHERE id = ?")->execute([$det['producto_id']]);
            } else {
                $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?")->execute([$diferencia, $det['producto_id']]);
            }

            $pdo->prepare("UPDATE detalles_pedido SET cantidad = ? WHERE id = ?")->execute([$nueva_cant, $id_detalle]);
            $cambios[] = "~ Modificado: " . $det['nombre_producto'] . " de $vieja_cant a $nueva_cant uds";
            $nuevo_subtotal += $det['precio_unitario'] * $nueva_cant;
        } else {
            // Sin cambio
            $nuevo_subtotal += $det['precio_unitario'] * $vieja_cant;
        }
    }

    // 2. Procesar nuevos ítems
    foreach ($nuevos_items as $ni) {
        $p_id = (int)$ni['producto_id'];
        $v_id = !empty($ni['variante_id']) ? (int)$ni['variante_id'] : null;
        $cant = (int)$ni['cantidad'];

        if ($p_id <= 0 || $cant <= 0) continue;

        // Obtener info del producto/variante
        if ($v_id) {
            $st_p = $pdo->prepare("SELECT p.nombre, v.nombre as v_nombre, v.precio, v.stock FROM productos p JOIN producto_variantes v ON p.id = v.producto_id WHERE p.id = ? AND v.id = ?");
            $st_p->execute([$p_id, $v_id]);
        } else {
            $st_p = $pdo->prepare("SELECT nombre, '' as v_nombre, precio, stock FROM productos WHERE id = ?");
            $st_p->execute([$p_id]);
        }
        $pinfo = $st_p->fetch(PDO::FETCH_ASSOC);

        if ($pinfo) {
            $pdo->prepare("INSERT INTO detalles_pedido (pedido_id, producto_id, nombre_producto, precio_unitario, cantidad, variante_id, variante_nombre) VALUES (?, ?, ?, ?, ?, ?, ?)")
                ->execute([$pedido_id, $p_id, $pinfo['nombre'], $pinfo['precio'], $cant, $v_id, $pinfo['v_nombre']]);
            
            $nombre_completo = $pinfo['nombre'] . ($pinfo['v_nombre'] ? ' ('.$pinfo['v_nombre'].')' : '');
            $cambios[] = "+ Agregado: $nombre_completo x $cant uds";
            $nuevo_subtotal += $pinfo['precio'] * $cant;

            // Restar stock
            if ($v_id) {
                $pdo->prepare("UPDATE producto_variantes SET stock = stock - ? WHERE id = ?")->execute([$cant, $v_id]);
                $pdo->prepare("UPDATE productos SET stock = (SELECT SUM(stock) FROM producto_variantes WHERE producto_id = productos.id) WHERE id = ?")->execute([$p_id]);
            } else {
                $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?")->execute([$cant, $p_id]);
            }
        }
    }

    // Si no hubo cambios reales
    if (empty($cambios)) {
        throw new Exception("No se detectaron cambios.");
    }

    // 3. Recalcular descuento y total
    $descuento = 0;
    if ($pedido['cupon_id']) {
        // En una tienda real, re-consultamos el cupón para ver si es porcentaje o fijo, pero aquí solo calcularemos en base a lo que se pagó proporcional o consultamos el cupón.
        $st_c = $pdo->prepare("SELECT tipo, valor FROM cupones WHERE id = ?");
        $st_c->execute([$pedido['cupon_id']]);
        $cupon = $st_c->fetch(PDO::FETCH_ASSOC);
        if ($cupon) {
            if ($cupon['tipo'] === 'porcentaje') {
                $descuento = $nuevo_subtotal * ($cupon['valor'] / 100);
            } else {
                $descuento = $cupon['valor'];
            }
            if ($descuento > $nuevo_subtotal) $descuento = $nuevo_subtotal;
        }
    }

    $nuevo_total = $nuevo_subtotal - $descuento + $pedido['costo_envio'];

    // 4. Actualizar pedido a estado 'pendiente_confirmacion'
    $pdo->prepare("UPDATE pedidos SET subtotal = ?, descuento = ?, total = ?, estado = 'pendiente_confirmacion' WHERE id = ?")
        ->execute([$nuevo_subtotal, $descuento, $nuevo_total, $pedido_id]);

    // 5. Registrar en pedido_historial
    $cambios_txt = implode("\n", $cambios);
    $resumen_cambios = "Subtotal anterior: Q".number_format($pedido['subtotal'],2)." -> Nuevo: Q".number_format($nuevo_subtotal,2)."\n";
    $resumen_cambios .= "Total anterior: Q".number_format($pedido['total'],2)." -> Nuevo: Q".number_format($nuevo_total,2)."\n";
    $resumen_cambios .= "Cambios:\n" . $cambios_txt;

    $stmt_hist = $pdo->prepare("INSERT INTO pedido_historial (pedido_id, usuario_id, campo_modificado, valor_anterior, valor_nuevo, motivo) VALUES (?, ?, 'items_y_total', ?, ?, ?)");
    $stmt_hist->execute([$pedido_id, $_SESSION['user_id'], "Total: Q".$pedido['total'], $resumen_cambios, $motivo]);

    $pdo->commit();

    echo json_encode(['success' => true, 'msg' => 'Pedido modificado exitosamente. Pendiente de confirmación del cliente.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
