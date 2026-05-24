<?php
/**
 * Google OAuth Handler
 * Handles Google Sign-In flow
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Google OAuth configuration
$google_client_id = get_env_var('GOOGLE_OAUTH_CLIENT_ID');
$google_client_secret = get_env_var('GOOGLE_OAUTH_CLIENT_SECRET');
$redirect_uri = SITE_URL . '/auth/google-callback.php';

if (empty($google_client_id) || empty($google_client_secret)) {
    error_log('Google OAuth attempted without client credentials.');
    http_response_code(500);
    die('Google Sign-In is not configured. Please contact the administrator.');
}

// Generate Google OAuth URL
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$params = [
    'client_id' => $google_client_id,
    'redirect_uri' => $redirect_uri,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'access_type' => 'offline',
    'prompt' => 'select_account'
];

$oauth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

header('Location: ' . $oauth_url);
exit;
