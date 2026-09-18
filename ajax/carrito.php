<?php
session_start();
require '../includes/db.php';
require '../includes/config.php';

// Inicializar carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Leer la petición JSON que viene de JavaScript
$data = json_decode(file_get_contents('php://input'), true);
$accion = $data['accion'] ?? '';

$response = ['success' => false, 'html' => '', 'total' => 0, 'count' => 0];

// --- 1. AGREGAR PRODUCTO ---
if ($accion === 'agregar') {
    $id = (int)$data['id'];
    $cantidad = (int)$data['cantidad'];
    $varianteId = isset($data['varianteId']) && $data['varianteId'] ? (int)$data['varianteId'] : null;
    
    // Consultar datos reales del producto
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt->execute([$id]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($prod) {
        $minimo = isset($prod['minimo_compra']) ? (int)$prod['minimo_compra'] : 1;
        if ($minimo < 1) $minimo = 1;
        $unidadesPaquete = !empty($prod['unidades_paquete']) ? (int)$prod['unidades_paquete'] : null;
        $precio = $prod['precio'];
        $nombre = $prod['nombre'];

        if ($varianteId) {
            $stmtVar = $pdo->prepare("SELECT * FROM producto_variantes WHERE id = ? AND producto_id = ?");
            $stmtVar->execute([$varianteId, $id]);
            $var = $stmtVar->fetch(PDO::FETCH_ASSOC);
            if ($var) {
                $precio = $var['precio'];
                if (!empty($var['minimo_compra']) && $var['minimo_compra'] > 0) {
                    $minimo = (int)$var['minimo_compra'];
                }
                $partes = array_filter([
                    !empty($var['tamano']) ? $var['tamano'] : null,
                    !empty($var['sabor'])  ? $var['sabor']  : null,
                ]);
                $varLabel = !empty($partes) ? implode(' / ', $partes) : $var['nombre'];
                $nombre = $prod['nombre'] . ' (' . $varLabel . ')';
            } else {
                $varianteId = null;
            }
        }

        $key = $id . ($varianteId ? '_' . $varianteId : '');

        if (isset($_SESSION['carrito'][$key])) {
            $nuevaCantidad = $_SESSION['carrito'][$key]['cantidad'] + $cantidad;
            $_SESSION['carrito'][$key]['cantidad'] = $nuevaCantidad;
        } else {
            $_SESSION['carrito'][$key] = [
                'id'            => $prod['id'],
                'variante_id'   => $varianteId,
                'nombre'        => $nombre,
                'precio'        => $precio,
                'imagen'        => $prod['imagen'],
                'minimo_compra' => $minimo,
                'unidades_paquete' => $unidadesPaquete,
                'minimo_compra_padre' => $prod['minimo_compra'] > 1 ? $prod['minimo_compra'] : 1, // F4
                'cantidad'      => $cantidad // F4: Permitimos cantidad que envíe producto.php (puede ser 1)
            ];
        }
        $response['success'] = true;
    }
}

// --- 2. ELIMINAR PRODUCTO ---
if ($accion === 'eliminar') {
    $key = $data['id'];
    unset($_SESSION['carrito'][$key]);
    $response['success'] = true;
}

// --- 3. CAMBIAR CANTIDAD ---
if ($accion === 'actualizar') {
    $key = $data['id'];
    $cantidad = (int)$data['cantidad'];
    
    if (isset($_SESSION['carrito'][$key])) {
        $item = $_SESSION['carrito'][$key];
        $pId = $item['id'];
        $minimoPadre = $item['minimo_compra_padre'] ?? $item['minimo_compra'];
        if ($minimoPadre < 1) $minimoPadre = 1;

        if ($cantidad < 1) {
            unset($_SESSION['carrito'][$key]);
            $response['success'] = true;
        } else {
            // F4: Permitir armar combinaciones libremente. La validación estricta solo ocurre en el checkout.
            $_SESSION['carrito'][$key]['cantidad'] = $cantidad;
            $response['success'] = true;
        }
    }
}

// --- GENERAR HTML DEL CARRITO Y TOTALES ---
$totalGeneral = 0;
$totalItems = 0;
$html = '';
$minimo_violations = [];
$cantidadesPorProducto = [];
if (!empty($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $cItem) {
        $pId = $cItem['id'];
        $cantidadesPorProducto[$pId] = ($cantidadesPorProducto[$pId] ?? 0) + $cItem['cantidad'];
    }
}

if (!empty($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $key => $item) {
        $subtotal = $item['precio'] * $item['cantidad'];
        $totalGeneral += $subtotal;
        $totalItems += $item['cantidad'];
        
        $pId = $item['id'];
        $min = isset($item['minimo_compra_padre']) ? (int)$item['minimo_compra_padre'] : (isset($item['minimo_compra']) ? (int)$item['minimo_compra'] : 1);
        if ($min < 1) $min = 1;
        
        $sumaCombinada = $cantidadesPorProducto[$pId];
        // Se vende por lote completo: el total combinado debe ser múltiplo
        // exacto del mínimo, no solo "al menos" el mínimo.
        $esMultiplo = $min > 0 && ($sumaCombinada % $min === 0);
        $bajo_minimo = ($sumaCombinada < $min) || !$esMultiplo;

        if ($bajo_minimo) {
            // Guardamos el nombre base (sin el label de variante) para que no se duplique en la alerta si hay varios
            $nombreBase = explode(' (', $item['nombre'])[0];
            $minimo_violations[$pId] = [
                'nombre'  => $nombreBase,
                'minimo'  => $min,
                'actual'  => $sumaCombinada,
                'parcial' => $sumaCombinada >= $min,
            ];
        }

        // Paso de +/-: si el ítem NO se combina con variantes (minimo_compra propio > 1),
        // se compra por lote de producción — cada clic avanza un lote completo.
        $pasoCarrito = (isset($item['minimo_compra']) && (int)$item['minimo_compra'] > 1) ? (int)$item['minimo_compra'] : 1;
        $paquetesTxt = '';
        if (!empty($item['unidades_paquete']) && $item['unidades_paquete'] > 0 && $pasoCarrito > 1) {
            $paquetesItem = round($item['cantidad'] / $item['unidades_paquete']);
            $paquetesTxt = ' (' . $paquetesItem . ' paquetes de ' . $item['unidades_paquete'] . ' uds. c/u)';
        }

        $borde  = $bajo_minimo ? 'border-left:3px solid #e74c3c;padding-left:8px;' : '';
        $alerta = '';
        if ($bajo_minimo && $sumaCombinada < $min) {
            $alerta = '<div class="cart-item-meta cart-item-meta--warn">&#9888; Mínimo combinado: ' . $min . ' uds. (llevas ' . $sumaCombinada . ')' . $paquetesTxt . '</div>';
        } elseif ($bajo_minimo) {
            $alerta = '<div class="cart-item-meta cart-item-meta--warn">&#9888; Debe ser múltiplo de ' . $min . ' (lote completo). Llevas ' . $sumaCombinada . '.' . $paquetesTxt . '</div>';
        } elseif ($min > 1) {
            $alerta = '<div class="cart-item-meta">Mínimo: ' . $min . ' uds.' . $paquetesTxt . '</div>';
        }

        $img = !empty($item['imagen']) ? $item['imagen'] : 'default_pan.png';

        // Botón "-": nunca deshabilitado aquí, se permite bajar (y hasta borrar si llega a 0)
        $btn_menos = '<div class="btn-qty-mini" onclick="updateCartItem(\'' . $key . '\', ' . ($item['cantidad'] - $pasoCarrito) . ')">-</div>';

        $html .= '
        <div class="cart-item" style="' . $borde . '">
            <img src="assets/img/' . $img . '" alt="pan" onerror="this.onerror=null;this.src=\'assets/img/default_pan.png\';">
            <div class="cart-item-details">
                <div class="cart-item-title">' . htmlspecialchars($item['nombre']) . '</div>
                <div class="cart-item-price">Q' . number_format($item['precio'], 2) . '</div>
                ' . $alerta . '
                <div class="cart-controls">
                    ' . $btn_menos . '
                    <span>' . $item['cantidad'] . '</span>
                    <div class="btn-qty-mini" onclick="updateCartItem(\'' . $key . '\', ' . ($item['cantidad'] + $pasoCarrito) . ')">+</div>
                </div>
            </div>
            <div class="btn-delete-item" onclick="removeCartItem(\'' . $key . '\')">
                <i class="fas fa-trash"></i>
            </div>
        </div>';
    }
} else {
    $html = '<p class="empty-msg" style="text-align:center; margin-top:50px; color:#999;">Tu carrito está vacío <i class="fas fa-sad-tear"></i></p>';
}

$costoEnvio   = (float)getConfig('costo_envio', '30.00');
$totalConEnvio = $totalGeneral > 0 ? $totalGeneral + $costoEnvio : 0;

$response['html']                = $html;
$response['subtotal']            = 'Q' . number_format($totalGeneral, 2);
$response['envio']               = 'Q' . number_format($costoEnvio, 2);
$response['total']               = 'Q' . number_format($totalConEnvio, 2);
$response['count']               = $totalItems;
$response['tiene_errores_minimo'] = count($minimo_violations) > 0;
$response['minimo_violations']    = array_values($minimo_violations);

header('Content-Type: application/json');
echo json_encode($response);
?>