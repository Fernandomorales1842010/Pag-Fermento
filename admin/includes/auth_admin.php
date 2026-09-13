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

// ─────────────────────────────────────────────────────────────────────────────
// SISTEMA DE PERMISOS GRANULAR — Fermento v1.1
// Uso: can('editar_pedido') → true/false
// ─────────────────────────────────────────────────────────────────────────────

$_PERMISOS = [
    'admin' => [
        // Dashboard
        'ver_dashboard'      => true,
        // Pedidos
        'ver_pedidos'        => true,
        'editar_estado_pedido' => true,
        'modificar_pedido'   => true,   // F2 — editar ítems del pedido
        // Productos / Inventario
        'ver_productos'      => true,
        'editar_productos'   => true,
        // Zonas de envío
        'ver_zonas'          => true,
        'editar_zonas'       => true,
        // Categorías
        'ver_categorias'     => true,
        'editar_categorias'  => true,
        // Cupones
        'ver_cupones'        => true,
        'editar_cupones'     => true,
        // Usuarios
        'ver_usuarios'       => true,
        'editar_usuarios'    => true,
        'cambiar_roles'      => true,
        // Logs
        'ver_logs'           => true,
        // Configuración
        'ver_configuracion'  => true,
        'editar_configuracion' => true,
        // Feriados (F1)
        'gestionar_feriados' => true,
    ],
    'supervisor' => [
        // Dashboard — solo lectura
        'ver_dashboard'      => true,
        // Pedidos — puede ver y cambiar estado, también modificar (F2)
        'ver_pedidos'        => true,
        'editar_estado_pedido' => true,
        'modificar_pedido'   => true,   // F2
        // Productos — solo lectura (puede ver stock)
        'ver_productos'      => true,
        'editar_productos'   => false,
        // Zonas — solo lectura
        'ver_zonas'          => true,
        'editar_zonas'       => false,
        // Categorías — solo lectura
        'ver_categorias'     => true,
        'editar_categorias'  => false,
        // Cupones — sin acceso
        'ver_cupones'        => false,
        'editar_cupones'     => false,
        // Usuarios — sin acceso
        'ver_usuarios'       => false,
        'editar_usuarios'    => false,
        'cambiar_roles'      => false,
        // Logs — sin acceso
        'ver_logs'           => false,
        // Configuración — sin acceso
        'ver_configuracion'  => false,
        'editar_configuracion' => false,
        // Feriados (F1) — sin acceso
        'gestionar_feriados' => false,
    ],
];

/**
 * Verifica si el usuario actual tiene un permiso específico.
 * Uso: if (can('editar_productos')) { ... }
 */
if (!function_exists('can')) {
    function can(string $permiso): bool {
        global $_PERMISOS;
        $rol = $_SESSION['user_rol'] ?? 'supervisor';
        return $_PERMISOS[$rol][$permiso] ?? false;
    }
}

/**
 * Lanza un error 403 si el usuario no tiene el permiso.
 * Uso: require_can('editar_cupones');
 */
if (!function_exists('require_can')) {
    function require_can(string $permiso, string $redirect = 'pedidos.php'): void {
        if (!can($permiso)) {
            http_response_code(403);
            // Incluir header si ya se cargó, mostrar página de acceso denegado
            echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
            <meta http-equiv="refresh" content="3;url=' . htmlspecialchars($redirect) . '">
            <title>Acceso Denegado</title>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
        <style>
          body { font-family: Poppins, sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; background:#f8f9fa; }
          .box { text-align:center; padding:50px 40px; background:white; border-radius:20px; box-shadow:0 10px 40px rgba(0,0,0,.1); max-width:420px; }
          .icon { font-size:3.5rem; margin-bottom:16px; }
          h2 { color:#1F1F1F; margin-bottom:10px; }
          p { color:#888; font-size:.9rem; line-height:1.6; }
          a { color:#D98C45; font-weight:700; text-decoration:none; }
        </style></head><body>
        <div class="box">
          <div class="icon">🔒</div>
          <h2>Acceso Denegado</h2>
          <p>No tienes permisos para acceder a esta sección.<br>
          Redirigiendo en 3 segundos... <a href="' . htmlspecialchars($redirect) . '">Volver</a></p>
        </div></body></html>';
        exit;
    }
}
}
