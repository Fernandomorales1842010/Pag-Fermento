<?php
// admin/procesar_usuario.php
require '../includes/db.php';
require '../includes/config.php';
include 'includes/auth_admin.php';

// Solo el rol 'admin' puede acceder a este procesamiento
if ($_SESSION['user_rol'] !== 'admin') {
    header("Location: index.php?msg=denegado");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: usuarios.php");
    exit;
}

csrf_verify('usuarios.php');

$accion = $_POST['accion'] ?? '';
$id     = (int)($_POST['id'] ?? 0);

if ($accion === 'eliminar') {
    if ($id === $_SESSION['user_id']) {
        // No se puede eliminar a sí mismo
        header("Location: usuarios.php?err=bd");
        exit;
    }
    try {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        adminLog('eliminar_usuario', 'usuarios', $id, 'Usuario eliminado');
        header("Location: usuarios.php?msg=eliminado");
    } catch (Exception $e) {
        header("Location: usuarios.php?err=bd");
    }
    exit;
}

// Crear o Editar
$nombre   = trim($_POST['nombre'] ?? '');
$email    = trim($_POST['email'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$rol      = trim($_POST['rol'] ?? 'cliente');
$password = $_POST['password'] ?? '';

if ($accion === 'crear') {
    // Validar email único
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        header("Location: usuarios.php?err=email_existe");
        exit;
    }

    try {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO usuarios (nombre, email, telefono, rol, password) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $email, $telefono ?: null, $rol, $hash]);
        $new_id = $pdo->lastInsertId();
        
        adminLog('crear_usuario', 'usuarios', $new_id, "Usuario: $nombre, Rol: $rol");
        header("Location: usuarios.php?msg=creado");
    } catch (Exception $e) {
        header("Location: usuarios.php?err=bd");
    }
    exit;
}

if ($accion === 'editar') {
    // Validar email único (excluyendo el usuario actual)
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);
    if ($stmt->rowCount() > 0) {
        header("Location: usuarios.php?err=email_existe");
        exit;
    }

    try {
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET nombre = ?, email = ?, telefono = ?, rol = ?, password = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $email, $telefono ?: null, $rol, $hash, $id]);
        } else {
            $sql = "UPDATE usuarios SET nombre = ?, email = ?, telefono = ?, rol = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $email, $telefono ?: null, $rol, $id]);
        }

        adminLog('editar_usuario', 'usuarios', $id, "Usuario: $nombre actualizado");
        header("Location: usuarios.php?msg=actualizado");
    } catch (Exception $e) {
        header("Location: usuarios.php?err=bd");
    }
    exit;
}

header("Location: usuarios.php");
exit;
