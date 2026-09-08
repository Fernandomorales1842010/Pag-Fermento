<?php
// google_callback.php
session_start();
require 'includes/db.php';
require 'includes/config.php';

$clientId = getConfig('google_client_id');
$clientSecret = getConfig('google_client_secret');
$redirectUri = getConfig('google_redirect_uri');

if (!$clientId || !$clientSecret || !$redirectUri) {
    die('Google Login no está configurado.');
}

if (!isset($_GET['code']) || !isset($_GET['state']) || $_GET['state'] !== $_SESSION['google_oauth_state']) {
    header('Location: login.php?error=invalid_state');
    exit;
}

$code = $_GET['code'];

// 1. Obtener Access Token
$tokenUrl = 'https://oauth2.googleapis.com/token';
$tokenData = [
    'code'          => $code,
    'client_id'     => $clientId,
    'client_secret' => $clientSecret,
    'redirect_uri'  => $redirectUri,
    'grant_type'    => 'authorization_code'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $tokenUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
$tokenResponse = curl_exec($ch);
curl_close($ch);

$tokenJson = json_decode($tokenResponse, true);

if (!isset($tokenJson['access_token'])) {
    header('Location: login.php?error=token_failed');
    exit;
}

// 2. Obtener Info del Usuario
$userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $userInfoUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $tokenJson['access_token']]);
$userInfoResponse = curl_exec($ch);
curl_close($ch);

$userJson = json_decode($userInfoResponse, true);

if (!isset($userJson['email'])) {
    header('Location: login.php?error=user_info_failed');
    exit;
}

$email = $userJson['email'];
$nombre = $userJson['name'];
$google_id = $userJson['id'];

// 3. Autenticar o Registrar en DB
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

// Prevenir Session Fixation al autenticar con Google
session_regenerate_id(true);

if ($user) {
    // Usuario existe
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_nombre'] = $user['nombre'];
    $_SESSION['user_rol'] = isset($user['rol']) ? $user['rol'] : 'cliente';
} else {
    // Usuario nuevo
    $stmtInsert = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, 'cliente')");
    $stmtInsert->execute([$nombre, $email, 'OAUTH_' . bin2hex(random_bytes(10))]);
    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['user_nombre'] = $nombre;
    $_SESSION['user_rol'] = 'cliente';
}

// Limpiar estado
unset($_SESSION['google_oauth_state']);

header('Location: index.php');
exit;
?>
