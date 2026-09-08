<?php
// includes/db.php

// ── Cargar .env si existe (cargador nativo, sin Composer) ─────────────────
// Las variables de entorno del sistema/servidor siempre tienen prioridad.
$_envFile = dirname(__DIR__) . '/.env';
if (file_exists($_envFile)) {
    $lines = file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $_line) {
        $_line = trim($_line);
        if ($_line === '' || $_line[0] === '#') continue;
        if (strpos($_line, '=') === false) continue;
        [$_k, $_v] = explode('=', $_line, 2);
        $_k = trim($_k); $_v = trim($_v);
        if (!array_key_exists($_k, $_ENV) && !getenv($_k)) {
            putenv("$_k=$_v");
            $_ENV[$_k] = $_v;
        }
    }
    unset($_lines, $_line, $_k, $_v);
}
unset($_envFile);

/*/ ── Parámetros de conexión ────────────────────────────────────────────────
$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'DB_fermento';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS');
*/
// Fallo seguro: si no hay credencial configurada, detener sin exponer datos
if ($pass === false) {
    error_log('FERMENTO: DB_PASS no está configurado en el entorno (.env o variables del servidor).');
    // Gap 3: Redirige a la página de error amigable en lugar de mostrar texto plano
    if (!headers_sent()) {
        header('Location: /error_servidor.html');
    }
    exit;
}
$charset = 'utf8mb4';

$dsn     = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // No exponemos detalles de la conexión al usuario
    error_log('FERMENTO DB Connection failed: ' . $e->getMessage());
    // Gap 3: Redirige a la página de error amigable con diseño de marca
    // en lugar del die() de texto plano anterior.
    if (!headers_sent()) {
        header('HTTP/1.1 503 Service Unavailable');
        header('Location: /error_servidor.html');
    }
    exit;
}
?>