<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$domain = strtolower(trim($data['domain'] ?? ''));

if (empty($domain)) {
    echo json_encode(['error' => 'Domain is required']);
    exit;
}

// Use global Stripe keys for domain registration (revenue goes to platform owner)
$stripe_secret = get_env_var('STRIPE_MODE') === 'live' 
    ? get_env_var('STRIPE_LIVE_SECRET_KEY') 
    : get_env_var('STRIPE_TEST_SECRET_KEY');

if (empty($stripe_secret)) {
    echo json_encode(['error' => 'Platform Stripe keys are not configured in .env']);
    exit;
}

\Stripe\Stripe::setApiKey($stripe_secret);

try {
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => 'Domain Registration: ' . $domain,
                    'description' => '1 Year Registration for ' . $domain,
                ],
                'unit_amount' => 1499, // $14.99
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => SITE_URL . '/dashboard/dealership.php?tab=domain&success=domain_purchased',
        'cancel_url' => SITE_URL . '/dashboard/dealership.php?tab=domain',
        'metadata' => [
            'tenant_id' => $_SESSION['tenant_id'],
            'domain' => $domain,
            'type' => 'domain_purchase'
        ]
    ]);

    echo json_encode(['id' => $session->id, 'url' => $session->url]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
