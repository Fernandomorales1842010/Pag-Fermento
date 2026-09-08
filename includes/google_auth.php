<?php
// includes/google_auth.php
require_once __DIR__ . '/config.php';

function getGoogleLoginUrl() {
    $clientId = getConfig('google_client_id');
    $redirectUri = getConfig('google_redirect_uri');
    
    if (!$clientId || !$redirectUri) {
        return false;
    }
    
    $params = [
        'client_id'     => $clientId,
        'redirect_uri'  => $redirectUri,
        'response_type' => 'code',
        'scope'         => 'email profile',
        'access_type'   => 'online',
        'state'         => bin2hex(random_bytes(16))
    ];
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['google_oauth_state'] = $params['state'];
    
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}
?>
