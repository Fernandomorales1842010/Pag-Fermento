<?php
// admin/includes/auth_admin.php
// Incluir en TODOS los archivos del panel admin al inicio

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?msg=sesion");
    exit;
}

if (!isset($_SESSION['user_rol']) || !in_array($_SESSION['user_rol'], ['admin', 'supervisor'])) {
    // Tiene sesión pero no es admin → redirigir a la tienda con mensaje
    header("Location: ../index.php?msg=acceso_denegado");
    exit;
}
