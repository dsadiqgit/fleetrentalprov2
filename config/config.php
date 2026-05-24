<?php
// Load environment variables
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    if (file_exists(dirname(__DIR__) . '/.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
        $dotenv->load();
    }
}

/**
 * Robust environment variable retrieval
 */
function get_env_var($name, $default = '') {
    $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);
    return ($value !== false && $value !== null && $value !== '') ? $value : $default;
}

// Site Settings
define('SITE_NAME', get_env_var('SITE_NAME', 'Fleet Rental Pro'));
define('SITE_URL', get_env_var('SITE_URL', 'https://fleetrentalpro.com'));
define('ROOT_DOMAIN', get_env_var('ROOT_DOMAIN', 'localhost'));
define('PORT', get_env_var('PORT', '8888')); 

// Security Headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Include Essential Core Files
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/security-helper.php';

// Sanitize globally (GET, POST, COOKIE)
$_GET = sanitize_input($_GET);
$_POST = sanitize_input($_POST);
$_COOKIE = sanitize_input($_COOKIE);

// Apply Global Rate Limiting (optional: only for API if needed, or globally here)
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
    check_rate_limit('api_global', 60, 60); // 60 req per minute for APIs
}

// Paths
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads/');
define('TENANT_PATH', BASE_PATH . '/tenants/');

// Security
define('SESSION_LIFETIME', 3600); // 1 hour
define('HASH_ALGO', PASSWORD_BCRYPT);

// Tenant Plans
define('PLANS', [
    'trial' => ['name' => 'Trial', 'vehicles' => 5, 'price' => 0],
    'starter' => ['name' => 'Starter', 'vehicles' => 10, 'price' => 29],
    'growth' => ['name' => 'Growth', 'vehicles' => 50, 'price' => 79],
    'pro' => ['name' => 'Pro', 'vehicles' => 200, 'price' => 199],
    'enterprise' => ['name' => 'Enterprise', 'vehicles' => -1, 'price' => 499]
]);

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('UTC');

// Tenant Detection
function get_current_tenant() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $host_parts = explode('.', str_replace(':' . PORT, '', $host));
    
    if (count($host_parts) >= 2 && $host_parts[0] !== 'localhost' && $host_parts[0] !== 'www' && $host_parts[0] !== ROOT_DOMAIN) {
        $subdomain = $host_parts[0];
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM tenants WHERE subdomain = ? AND status = 'active'");
        $stmt->execute([$subdomain]);
        return $stmt->fetch();
    }
    return null;
}

$current_tenant = get_current_tenant();
define('CURRENT_TENANT_ID', $current_tenant ? $current_tenant['id'] : null);
define('IS_SUBDOMAIN', CURRENT_TENANT_ID !== null);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
