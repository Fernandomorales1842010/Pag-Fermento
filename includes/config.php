<?php
/**
 * Helper de Configuración - Fermento
 * Carga y cachea valores de la tabla `configuracion`
 * 
 * Uso: 
 *   require 'includes/config.php';
 *   $envio = getConfig('costo_envio');  // '30.00'
 *   $ws = getConfig('whatsapp_numero'); // '50239754421'
 */

// Asegurar que db.php ya fue cargado
if (!isset($pdo)) {
    require __DIR__ . '/db.php';
}

// Cache estático para no repetir queries
$_configCache = null;

/**
 * Obtener un valor de configuración
 */
function getConfig($clave, $default = '') {
    global $pdo, $_configCache;
    
    // Cargar todo en cache la primera vez
    if ($_configCache === null) {
        try {
            $stmt = $pdo->query("SELECT clave, valor FROM configuracion");
            $_configCache = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) {
            $_configCache = [];
        }
    }
    
    return isset($_configCache[$clave]) ? $_configCache[$clave] : $default;
}

/**
 * Actualizar un valor de configuración
 */
function setConfig($clave, $valor) {
    global $pdo, $_configCache;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO configuracion (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = ?");
        $stmt->execute([$clave, $valor, $valor]);
        
        // Actualizar cache
        if ($_configCache !== null) {
            $_configCache[$clave] = $valor;
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Obtener todas las zonas de envío activas
 */
function getZonasEnvio() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM zonas_envio WHERE activa = 1 ORDER BY nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Obtener todas las categorías activas
 */
function getCategorias() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM categorias WHERE activa = 1 ORDER BY orden ASC, nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Generar link de WhatsApp
 */
function getWhatsAppLink($mensaje = '') {
    $numero = getConfig('whatsapp_numero', '50239754421');
    return "https://wa.me/{$numero}?text=" . urlencode($mensaje);
}

// ── PROTECCIÓN CSRF ────────────────────────────────────────────────────────

/**
 * Genera o recupera el token CSRF de la sesión actual.
 * Úsalo en formularios: <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
 */
function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica el token CSRF enviado en el POST.
 * Si falla, redirige a $redirect con un parámetro de error y termina.
 */
function csrf_verify(string $redirect = '../index.php'): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $token     = $_POST['csrf_token'] ?? '';
    $esperado  = $_SESSION['csrf_token'] ?? '';
    if (!$token || !$esperado || !hash_equals($esperado, $token)) {
        // Invalidar el token para forzar regeneración en el próximo intento
        unset($_SESSION['csrf_token']);
        header("Location: {$redirect}?err=csrf");
        exit;
    }
    // B-02: TOKEN DE UN SOLO USO — Se invalida inmediatamente después de
    // una verificación exitosa. Esto previene dos escenarios críticos:
    // 1) Doble-clic que pase a pesar del debounce de JS
    // 2) Ventana post-COMMIT donde el carrito sigue en sesión y el usuario
    //    podría reenviar el formulario antes de que la sesión se limpie.
    // El siguiente csrf_token() generará automáticamente uno nuevo.
    unset($_SESSION['csrf_token']);
}

// ── LOGS DE ACTIVIDAD ADMIN ───────────────────────────────────────────────

/**
 * Registra una acción en la tabla admin_logs.
 * Silencia errores para no interrumpir el flujo normal.
 *
 * @param string $accion     Código de acción (ej: 'crear_producto')
 * @param string $entidad    Tabla/entidad afectada (ej: 'productos')
 * @param int|null $eid      ID del registro afectado
 * @param string $detalle    Descripción legible (ej: 'Pan de Centeno')
 */
function adminLog(string $accion, string $entidad = '', ?int $eid = null, string $detalle = ''): void {
    global $pdo;
    if (!isset($pdo)) return;
    try {
        $uid    = $_SESSION['user_id']     ?? null;
        $nombre = $_SESSION['user_nombre'] ?? 'desconocido';
        $ip     = $_SERVER['REMOTE_ADDR']  ?? null;
        $stmt   = $pdo->prepare(
            "INSERT INTO admin_logs (usuario_id, usuario_nombre, accion, entidad, entidad_id, detalle, ip)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$uid, $nombre, $accion, $entidad ?: null, $eid, $detalle ?: null, $ip]);
    } catch (Exception $e) {
        // No interrumpir el flujo por un fallo de log
        error_log('adminLog error: ' . $e->getMessage());
    }
}
?>
