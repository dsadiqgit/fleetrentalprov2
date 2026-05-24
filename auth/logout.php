<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth-helper.php';

// Clear session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

$auth0_configured = 
    !empty($_ENV['AUTH0_DOMAIN']) && 
    strpos($_ENV['AUTH0_DOMAIN'], 'your-tenant') === false &&
    !empty($_ENV['AUTH0_CLIENT_ID']) && 
    $_ENV['AUTH0_CLIENT_ID'] !== 'your-client-id' &&
    !empty($_ENV['AUTH0_CLIENT_SECRET']) && 
    $_ENV['AUTH0_CLIENT_SECRET'] !== 'your-client-secret';

if ($auth0_configured && isset($auth0)) {
    header('Location: ' . $auth0->logout());
} else {
    // If Auth0 isn't fully set up, just redirect to the home page after clearing local session
    header('Location: /');
}
exit;
?>
