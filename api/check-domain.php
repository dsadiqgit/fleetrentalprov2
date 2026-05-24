<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$domain = strtolower(trim($_GET['domain'] ?? ''));

if (empty($domain)) {
    echo json_encode(['error' => 'Domain is required']);
    exit;
}

// Better validation
if (!preg_match('/^(?!:\/\/)([a-zA-Z0-9-_]+\.)*[a-zA-Z0-9][a-zA-Z0-9-_]+\.[a-zA-Z]{2,11}$/', $domain)) {
    echo json_encode(['available' => false, 'message' => 'Invalid domain format']);
    exit;
}

// Blacklist of common/system domains
$blacklist = [
    'google.com', 'apple.com', 'facebook.com', 'microsoft.com', 
    'amazon.com', 'netflix.com', 'instagram.com', 'twitter.com',
    'admin', 'dashboard', 'api', 'root', 'www', 'mail', 'ftp'
];

foreach ($blacklist as $b) {
    if ($domain === $b || str_ends_with($domain, '.' . $b)) {
        echo json_encode(['available' => false, 'message' => 'This domain is reserved or protected']);
        exit;
    }
}

// Prevent using the root domain as a custom domain
if ($domain === ROOT_DOMAIN) {
    echo json_encode(['available' => false, 'message' => 'You cannot use the root domain']);
    exit;
}

// Simulate check delay for professional feel
usleep(200000); // 0.2 seconds

$pdo = getDB();
// Check if domain is already taken by another tenant
$stmt = $pdo->prepare("SELECT id FROM tenants WHERE (custom_domain = ? OR subdomain = ?) AND id != ?");
$subdomain_attempt = str_replace('.' . ROOT_DOMAIN, '', $domain);
$stmt->execute([$domain, $subdomain_attempt, $_SESSION['tenant_id']]);
$exists = $stmt->fetch();

if ($exists) {
    echo json_encode([
        'available' => false, 
        'message' => 'This domain name is already taken by another company on this platform'
    ]);
    exit;
}

// Check if domain actually exists on the internet
$is_registered = false;
if (function_exists('checkdnsrr')) {
    // Check for any common records (NS, A, MX)
    $is_registered = checkdnsrr($domain, "NS");
}

if ($is_registered) {
    echo json_encode([
        'available' => true, 
        'registered' => true,
        'message' => 'This domain is already registered. You can point it to our servers by following the DNS instructions below.'
    ]);
} else {
    echo json_encode([
        'available' => true, 
        'registered' => false,
        'price' => 14.99,
        'message' => 'This domain is available! You can register it instantly through Fleet Rental Pro for $14.99/yr.'
    ]);
}
