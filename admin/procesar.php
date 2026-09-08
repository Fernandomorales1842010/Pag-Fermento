<?php
// admin/procesar.php - Crear producto
require '../includes/db.php';
require '../includes/config.php';
require 'includes/auth_admin.php';   // ← verifica sesión + rol admin/supervisor

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: agregar.php"); exit;
}

csrf_verify('agregar.php');

$nombre     = trim($_POST['nombre'] ?? '');
$desc       = trim($_POST['descripcion'] ?? '');
$maridaje   = trim($_POST['maridaje'] ?? '');
$precio     = (float)($_POST['precio'] ?? 0);
$stock      = max(0, (int)($_POST['stock'] ?? 0));
$minimo     = max(1, (int)($_POST['minimo_compra'] ?? 1));
$cat        = trim($_POST['categoria'] ?? 'Sin Categoría');
$destacado  = isset($_POST['destacado']) ? 1 : 0;
$oferta     = isset($_POST['oferta'])    ? 1 : 0;
$sku        = trim($_POST['sku'] ?? '');
$precio_dist = (isset($_POST['precio_distribuidor']) && $_POST['precio_distribuidor'] !== '')
               ? (float)$_POST['precio_distribuidor'] : null;

// VARIANTES
$var_tamanos = $_POST['var_tamano'] ?? [];
$var_sabores = $_POST['var_sabor']  ?? [];
$var_precios = $_POST['var_precio'] ?? [];
$var_stocks  = $_POST['var_stock']  ?? [];
$var_minimos = $_POST['var_minimo'] ?? [];

$var_skus    = $_POST['var_sku']    ?? [];

$tieneVariantes = count($var_precios) > 0 && array_filter($var_precios, fn($p) => (float)$p > 0);

if ($tieneVariantes) {
    $precio = min(array_map('floatval', $var_precios));
    $stock  = array_sum(array_map('intval', $var_stocks));
}

if ($nombre === '' || $precio <= 0) {
    header("Location: agregar.php?err=datos");
    exit;
}

function subirImagen($campo, $prefijo = 'pan_') {
    if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] === UPLOAD_ERR_OK) {
        $archivo = $_FILES[$campo];
        $ext     = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $nombre = $prefijo . time() . "_" . bin2hex(random_bytes(3)) . "." . $ext;
            $dir    = "../assets/img/";
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            if (move_uploaded_file($archivo['tmp_name'], $dir . $nombre)) {
                return $nombre;
            }
        }
    }
    return null;
}

$img1 = subirImagen('imagen') ?: 'default.png';
$img2 = subirImagen('imagen_2');
$img3 = subirImagen('imagen_3');

try {
    $pdo->beginTransaction();

    $sql  = "INSERT INTO productos
                (nombre, descripcion, maridaje, precio, stock, minimo_compra, categoria,
                 imagen, imagen_2, imagen_3, destacado, oferta, sku, precio_distribuidor, tiene_variantes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $nombre, $desc, $maridaje ?: null, $precio, $stock, $minimo, $cat,
        $img1, $img2, $img3, $destacado, $oferta, $sku, $precio_dist,
        $tieneVariantes ? 1 : 0
    ]);

    $producto_id = $pdo->lastInsertId();

    if ($tieneVariantes) {
        $sqlVar  = "INSERT INTO producto_variantes
                        (producto_id, tamano, sabor, nombre, precio, stock, minimo_compra, sku)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtVar = $pdo->prepare($sqlVar);

        for ($i = 0; $i < count($var_precios); $i++) {
            $vTamano = trim($var_tamanos[$i] ?? '');
            $vSabor  = trim($var_sabores[$i] ?? '');
            $vSku    = trim($var_skus[$i] ?? '');
            $vPrecio = (float)$var_precios[$i];
            $vStock  = (int)($var_stocks[$i]  ?? 0);
            $vMin    = (isset($var_minimos[$i]) && $var_minimos[$i] !== '') ? (int)$var_minimos[$i] : null;

            $partes  = array_filter([$vTamano, $vSabor]);
            $vNombre = !empty($partes) ? implode(' / ', $partes) : "Variante " . ($i + 1);

            if ($vPrecio > 0) {
                $stmtVar->execute([$producto_id, $vTamano ?: null, $vSabor ?: null, $vNombre, $vPrecio, $vStock, $vMin, $vSku ?: null]);
            }
        }
    }

    $pdo->commit();

    // Log de actividad
    adminLog('crear_producto', 'productos', (int)$producto_id, $nombre);

    header("Location: productos.php?msg=creado");

} catch (Exception $e) {
    $pdo->rollBack();
    error_log('procesar.php error: ' . $e->getMessage());
    header("Location: agregar.php?err=bd");
}
?>