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
            // Respetar el mínimo también cuando ya está en el carrito
            if ($nuevaCantidad < $minimo) $nuevaCantidad = $minimo;
            $_SESSION['carrito'][$key]['cantidad'] = $nuevaCantidad;
        } else {
            $_SESSION['carrito'][$key] = [
                'id'            => $prod['id'],
                'variante_id'   => $varianteId,
                'nombre'        => $nombre,
                'precio'        => $precio,
                'imagen'        => $prod['imagen'],
                'minimo_compra' => $minimo,
                'cantidad'      => max($cantidad, $minimo)
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
        $minimo = isset($_SESSION['carrito'][$key]['minimo_compra']) ? (int)$_SESSION['carrito'][$key]['minimo_compra'] : 1;
        if ($minimo < 1) $minimo = 1;
        if ($cantidad >= $minimo) {
            $_SESSION['carrito'][$key]['cantidad'] = $cantidad;
            $response['success'] = true;
        } else {
            $response['error']  = "El mínimo para este producto es $minimo unidades.";
            $response['minimo'] = $minimo;
            $response['success'] = false;
        }
    }
}

// --- GENERAR HTML DEL CARRITO Y TOTALES ---
$totalGeneral = 0;
$totalItems = 0;
$html = '';
$minimo_violations = [];

if (!empty($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $key => $item) {
        $subtotal = $item['precio'] * $item['cantidad'];
        $totalGeneral += $subtotal;
        $totalItems += $item['cantidad'];
        
        $min = isset($item['minimo_compra']) ? (int)$item['minimo_compra'] : 1;
        if ($min < 1) $min = 1;
        $bajo_minimo = ($item['cantidad'] < $min);
        
        if ($bajo_minimo) {
            $minimo_violations[] = [
                'nombre' => $item['nombre'],
                'minimo' => $min,
                'actual' => $item['cantidad'],
            ];
        }

        $borde  = $bajo_minimo ? 'border-left:3px solid #e74c3c;padding-left:8px;' : '';
        $alerta = '';
        if ($bajo_minimo) {
            $alerta = '<div style="font-size:0.72rem;color:#c0392b;font-weight:600;margin-top:3px;">&#9888; Mínimo: ' . $min . ' unidades</div>';
        } elseif ($min > 1) {
            $alerta = '<div style="font-size:0.7rem;color:#888;">Mínimo: ' . $min . '</div>';
        }

        $img = !empty($item['imagen']) ? $item['imagen'] : 'default_pan.png';

        // Botón "-": se deshabilita visualmente si ya estamos en el mínimo
        $en_minimo = ($item['cantidad'] <= $min);
        if ($en_minimo) {
            $btn_menos = '<div class="btn-qty-mini" style="opacity:0.3;cursor:not-allowed;" title="Cantidad mínima alcanzada">-</div>';
        } else {
            $btn_menos = '<div class="btn-qty-mini" onclick="updateCartItem(\'' . $key . '\', ' . ($item['cantidad'] - 1) . ')">-</div>';
        }

        $html .= '
        <div class="cart-item" style="' . $borde . '">
            <img src="assets/img/' . $img . '" alt="pan">
            <div class="cart-item-details">
                <div class="cart-item-title">' . htmlspecialchars($item['nombre']) . '</div>
                <div class="cart-item-price">Q' . number_format($item['precio'], 2) . '</div>
                ' . $alerta . '
                <div class="cart-controls">
                    ' . $btn_menos . '
                    <span>' . $item['cantidad'] . '</span>
                    <div class="btn-qty-mini" onclick="updateCartItem(\'' . $key . '\', ' . ($item['cantidad'] + 1) . ')">+</div>
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
$response['minimo_violations']   = $minimo_violations;
$response['tiene_errores_minimo']= count($minimo_violations) > 0;

header('Content-Type: application/json');
echo json_encode($response);
?>