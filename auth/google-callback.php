<?php
/**
 * Google OAuth Callback Handler
 * Processes the Google OAuth response and creates/updates user account
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Verify state parameter to prevent CSRF
if (!isset($_GET['state']) || !isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    unset($_SESSION['oauth_state']);
    die('Invalid state parameter');
}

unset($_SESSION['oauth_state']);

// Google OAuth configuration
$google_client_id = get_env_var('GOOGLE_OAUTH_CLIENT_ID');
$google_client_secret = get_env_var('GOOGLE_OAUTH_CLIENT_SECRET');
$redirect_uri = SITE_URL . '/auth/google-callback.php';

if (empty($google_client_id) || empty($google_client_secret)) {
    error_log('Google OAuth callback accessed without configured credentials.');
    http_response_code(500);
    die('Google Sign-In is not configured. Please contact the administrator.');
}

// Exchange authorization code for access token
if (!isset($_GET['code'])) {
    die('Authorization code not found');
}

$token_url = 'https://oauth2.googleapis.com/token';
$token_data = [
    'code' => $_GET['code'],
    'client_id' => $google_client_id,
    'client_secret' => $google_client_secret,
    'redirect_uri' => $redirect_uri,
    'grant_type' => 'authorization_code'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
$response = curl_exec($ch);
curl_close($ch);

$token_response = json_decode($response, true);

if (!isset($token_response['access_token'])) {
    die('Failed to obtain access token');
}

$access_token = $token_response['access_token'];

// Get user info from Google
$user_info_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $user_info_url . '?access_token=' . $access_token);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$user_response = curl_exec($ch);
curl_close($ch);

$google_user = json_decode($user_response, true);

if (!$google_user || !isset($google_user['email'])) {
    die('Failed to obtain user information');
}

$pdo = getDB();
$email = $google_user['email'];
$full_name = $google_user['name'] ?? '';
$google_id = $google_user['id'] ?? '';

try {
    // Check if this is a signup flow
    $is_signup = isset($_GET['signup']) && $_GET['signup'] === 'true';
    
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    // For signup flow, store Google user data in session and redirect back
    if ($is_signup && !$user) {
        $_SESSION['google_user_data'] = $google_user;
        header('Location: /auth/signup.php?signup=true');
        exit;
    }

    if ($user) {
        // Existing user - update Google ID if not set
        if (!$user['google_id']) {
            $update = $pdo->prepare("UPDATE users SET google_id = ?, updated_at = NOW() WHERE id = ?");
            $update->execute([$google_id, $user['id']]);
        }
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['tenant_id'] = $user['tenant_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['oauth_provider'] = 'google';
        $_SESSION['ob_reset'] = true;
        
        // Redirect based on role
        if ($user['role'] === 'customer') {
            header('Location: /dashboard/customer.php');
        } else {
            header('Location: /dashboard/');
        }
        exit;
    } else {
        // New user - determine tenant and create account
        $tenant_id = null;
        
        // Check if this is a subdomain (tenant-specific login)
        if (IS_SUBDOMAIN) {
            $tenant_id = CURRENT_TENANT_ID;
        } else {
            // For main domain, check if there's a default tenant or if user should be admin
            // For now, we'll require tenant selection or admin invitation
            die('Account not found. Please contact your rental company administrator.');
        }
        
        // Determine role based on context
        $role = 'customer'; // Default to customer for new Google signups
        
        // Create new user
        $stmt = $pdo->prepare("
            INSERT INTO users (tenant_id, email, full_name, google_id, role, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$tenant_id, $email, $full_name, $google_id, $role]);
        
        $user_id = $pdo->lastInsertId();
        
        // Set session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['tenant_id'] = $tenant_id;
        $_SESSION['role'] = $role;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $full_name;
        $_SESSION['oauth_provider'] = 'google';
        $_SESSION['ob_reset'] = true;
        
        // Redirect based on role
        if ($role === 'customer') {
            header('Location: /dashboard/customer.php');
        } else {
            header('Location: /dashboard/');
        }
        exit;
    }
} catch (PDOException $e) {
    error_log("Google OAuth error: " . $e->getMessage());
    die('Database error. Please try again.');
}
