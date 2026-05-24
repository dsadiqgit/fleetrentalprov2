<?php
/**
 * Indestructible Subscription API
 * Always returns JSON, even on fatal errors.
 */
header('Content-Type: application/json');

// Shield against HTML output from warnings/notices
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Register shutdown for fatals
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (ob_get_level()) ob_end_clean();
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'error' => 'CRITICAL_FATAL: ' . $error['message'],
            'trace' => 'Line ' . $error['line'] . ' in ' . basename($error['file'])
        ]);
        exit;
    }
});

try {
    // 1. Core Loader
    $root = dirname(__DIR__);
    if (!file_exists($root . '/vendor/autoload.php')) {
        throw new Exception("Composer autoload not found at " . $root);
    }
    require_once $root . '/vendor/autoload.php';
    require_once $root . '/config/config.php';

    // 2. Auth Check (Optional for Guest Checkout)
    $user_id = $_SESSION['user_id'] ?? null;
    $tenant_id = $_SESSION['tenant_id'] ?? null;
    $user_email = $_SESSION['user_email'] ?? null;

    // 3. Mode & Keys
    $mode = get_env_var('STRIPE_MODE', 'test');
    $stripe_secret = ($mode === 'live') ? get_env_var('STRIPE_LIVE_SECRET_KEY') : get_env_var('STRIPE_TEST_SECRET_KEY');

    if (empty($stripe_secret) || strpos($stripe_secret, 'sk_') !== 0) {
        throw new Exception("Stripe Secret Key is invalid or not found in Admin Settings for " . strtoupper($mode) . " mode.");
    }

    \Stripe\Stripe::setApiKey($stripe_secret);

    // 4. Input Processing
    $data = json_decode(file_get_contents('php://input'), true);
    $plan_slug = $data['plan'] ?? 'growth';

    $plans = [
        'growth' => ['name' => 'Growth Plan', 'amount' => 6000], 
        'core' => ['name' => 'Core Plan', 'amount' => 15000]
    ];

    if (!isset($plans[$plan_slug])) {
        throw new Exception("Invalid plan selected: " . $plan_slug);
    }

    $selected_plan = $plans[$plan_slug];

    // 5. Session Creation
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'gbp',
                'product_data' => [
                    'name' => 'Fleet Rental Pro: ' . $selected_plan['name'],
                    'description' => 'Platform Subscription Upgrade',
                ],
                'unit_amount' => $selected_plan['amount'],
                'recurring' => ['interval' => 'month'],
            ],
            'quantity' => 1,
        ]],
        'mode' => 'subscription',
        'success_url' => SITE_URL . '/checkout-success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => SITE_URL . '/checkout.php?plan=' . $plan_slug . '&cancelled=1',
        'customer_email' => $_SESSION['user_email'] ?? null,
        'metadata' => [
            'user_id' => $_SESSION['user_id'],
            'tenant_id' => $_SESSION['tenant_id'] ?? 0,
            'plan_slug' => $plan_slug
        ]
    ]);

    // 6. Response
    echo json_encode(['id' => $session->id]);

} catch (\Stripe\Exception\ApiErrorException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Stripe API Error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
