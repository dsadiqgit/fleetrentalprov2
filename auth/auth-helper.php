<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

use Auth0\SDK\Auth0;
use Auth0\SDK\Configuration\SdkConfiguration;

$configuration = new SdkConfiguration(
    domain: $_ENV['AUTH0_DOMAIN'] ?? '',
    clientId: $_ENV['AUTH0_CLIENT_ID'] ?? '',
    clientSecret: $_ENV['AUTH0_CLIENT_SECRET'] ?? '',
    redirectUri: $_ENV['AUTH0_REDIRECT_URI'] ?? '',
    cookieSecret: $_ENV['AUTH0_COOKIE_SECRET'] ?? 'a-very-long-and-secure-cookie-secret'
);

$auth0 = new Auth0($configuration);

function require_auth() {
    global $auth0;
    $session = $auth0->getCredentials();
    
    if ($session === null) {
        header('Location: ' . $auth0->login());
        exit;
    }
    
    return $session->user;
}

function get_auth_user() {
    global $auth0;
    $session = $auth0->getCredentials();
    return $session ? $session->user : null;
}
