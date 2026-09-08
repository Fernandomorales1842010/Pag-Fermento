<?php
// admin/actualizar.php
require '../includes/db.php';
require '../includes/config.php';
require 'includes/auth_admin.php';   // ← verifica sesión + rol admin/supervisor

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

csrf_verify('index.php');

$id   = (int)$_POST['id'];
$nombre      = trim($_POST['nombre'] ?? '');
$desc        = trim($_POST['descripcion'] ?? '');
$maridaje    = trim($_POST['maridaje'] ?? '');
$precio      = (float)($_POST['precio'] ?? 0);
$stock       = (int)($_POST['stock'] ?? 0);
$minimo      = max(1, (int)($_POST['minimo_compra'] ?? 1));
$cat         = trim($_POST['categoria'] ?? '');
$sku         = trim($_POST['sku'] ?? '');
$precio_dist = (isset($_POST['precio_distribuidor']) && $_POST['precio_distribuidor'] !== '')
               ? (float)$_POST['precio_distribuidor'] : null;

// Checkboxes (si no están marcados, no se envían)
$destacado = isset($_POST['destacado']) ? 1 : 0;
$oferta    = isset($_POST['oferta'])    ? 1 : 0;

// VARIANTES
$var_ids     = $_POST['var_id']     ?? [];
$var_tamanos = $_POST['var_tamano'] ?? [];
$var_sabores = $_POST['var_sabor']  ?? [];
$var_precios = $_POST['var_precio'] ?? [];
$var_stocks  = $_POST['var_stock']  ?? [];
$var_minimos = $_POST['var_minimo'] ?? [];

$var_skus    = $_POST['var_sku']    ?? [];

$tieneVariantes = count($var_precios) > 0
               && !empty(array_filter($var_precios, fn($p) => (float)$p > 0));

if ($tieneVariantes) {
    $precio = min(array_map('floatval', $var_precios));
    $stock  = array_sum(array_map('intval', $var_stocks));
}

// --- MANEJO DE IMÁGENES ---
function subirImagenUpdate($campo, $actual) {
    if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] == 0) {
        $archivo = $_FILES[$campo];
        $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $nombre = "pan_" . time() . "_" . bin2hex(random_bytes(2)) . "." . $ext;
            if (move_uploaded_file($archivo['tmp_name'], "../assets/img/" . $nombre)) {
                return $nombre;
            }
        }
    }
    return $actual;
}

$img1 = subirImagenUpdate('imagen',   $_POST['imagen_actual']   ?? '');
$img2 = subirImagenUpdate('imagen_2', $_POST['imagen_actual_2'] ?? '');
$img3 = subirImagenUpdate('imagen_3', $_POST['imagen_actual_3'] ?? '');

// --- ACTUALIZAR EN BASE DE DATOS ---
try {
    $pdo->beginTransaction();

    $sql = "UPDATE productos SET
                nombre = ?,
                descripcion = ?,
                maridaje = ?,
                precio = ?,
                stock = ?,
                minimo_compra = ?,
                categoria = ?,
                imagen = ?,
                imagen_2 = ?,
                imagen_3 = ?,
                destacado = ?,
                oferta = ?,
                sku = ?,
                precio_distribuidor = ?,
                tiene_variantes = ?
                WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $nombre, $desc, $maridaje ?: null, $precio, $stock, $minimo, $cat,
        $img1, $img2, $img3, $destacado, $oferta, $sku, $precio_dist,
        $tieneVariantes ? 1 : 0, $id
    ]);

    // Manejar variantes
    $ids_mantenidos = [];
    foreach ($var_ids as $v_id) {
        if ($v_id > 0) $ids_mantenidos[] = (int)$v_id;
    }

    if (empty($ids_mantenidos)) {
        $pdo->prepare("DELETE FROM producto_variantes WHERE producto_id = ?")->execute([$id]);
    } else {
        $inQuery = implode(',', $ids_mantenidos);
        $pdo->prepare("DELETE FROM producto_variantes WHERE producto_id = $id AND id NOT IN ($inQuery)")->execute();
    }

    $stmtInsertVar = $pdo->prepare(
        "INSERT INTO producto_variantes (producto_id, tamano, sabor, nombre, precio, stock, minimo_compra, sku) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmtUpdateVar = $pdo->prepare(
        "UPDATE producto_variantes SET tamano=?, sabor=?, nombre=?, precio=?, stock=?, minimo_compra=?, sku=? WHERE id=?"
    );

    for ($i = 0; $i < count($var_precios); $i++) {
        $vId     = (int)($var_ids[$i]     ?? 0);
        $vTamano = trim($var_tamanos[$i]  ?? '');
        $vSabor  = trim($var_sabores[$i]  ?? '');
        $vSku    = trim($var_skus[$i]     ?? '');
        $vPrecio = (float)$var_precios[$i];
        $vStock  = (int)($var_stocks[$i]  ?? 0);
        $vMin    = (isset($var_minimos[$i]) && $var_minimos[$i] !== '') ? (int)$var_minimos[$i] : null;

        $partes  = array_filter([$vTamano, $vSabor]);
        $vNombre = !empty($partes) ? implode(' / ', $partes) : "Variante " . ($i + 1);

        if ($vPrecio > 0) {
            if ($vId === 0) {
                $stmtInsertVar->execute([$id, $vTamano ?: null, $vSabor ?: null, $vNombre, $vPrecio, $vStock, $vMin, $vSku ?: null]);
            } else {
                $stmtUpdateVar->execute([$vTamano ?: null, $vSabor ?: null, $vNombre, $vPrecio, $vStock, $vMin, $vSku ?: null, $vId]);
            }
        }
    }

    $pdo->commit();

    // Log de actividad
    adminLog('editar_producto', 'productos', $id, $nombre);

    header("Location: productos.php?msg=actualizado");

} catch (Exception $e) {
    $pdo->rollBack();
    error_log('actualizar.php error: ' . $e->getMessage());
    header("Location: editar.php?id={$id}&err=bd");
}
?>