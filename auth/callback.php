<?php
require_once __DIR__ . '/auth-helper.php';

try {
    // Exchange a code for a token
    $auth0->exchange();

    // Catch any errors from Auth0
    if ($auth0->getCredentials() === null) {
        die("Authentication failed. Please try again.");
    }

    // Go to the dashboard
    header("Location: /dashboard/index.php");
    exit;
} catch (\Exception $e) {
    die("Error during authentication: " . $e->getMessage());
}
