<?php
// admin/borrar.php
require '../includes/db.php';
require '../includes/config.php';
require 'includes/auth_admin.php';   // ← verifica sesión + rol admin/supervisor

// Borrar vía GET es inseguro; exigimos POST con CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: productos.php");
    exit;
}

csrf_verify('productos.php');

if (!isset($_POST['id'])) {
    header("Location: productos.php");
    exit;
}

$id = (int)$_POST['id'];

// Obtener nombre e imágenes antes de borrar
$stmt = $pdo->prepare("SELECT nombre, imagen, imagen_2, imagen_3 FROM productos WHERE id = ?");
$stmt->execute([$id]);
$prod = $stmt->fetch(PDO::FETCH_ASSOC);

if ($prod) {
    $del = $pdo->prepare("DELETE FROM productos WHERE id = ?");
    if ($del->execute([$id])) {
        // Limpiar imágenes del servidor (solo las que no son default)
        foreach (['imagen', 'imagen_2', 'imagen_3'] as $col) {
            if (!empty($prod[$col]) && $prod[$col] !== 'default.png') {
                $path = "../assets/img/" . $prod[$col];
                if (file_exists($path)) @unlink($path);
            }
        }
        // Log de actividad
        adminLog('eliminar_producto', 'productos', $id, $prod['nombre']);
        header("Location: productos.php?msg=eliminado");
    } else {
        header("Location: productos.php?msg=error");
    }
} else {
    header("Location: productos.php?msg=error");
}
?>