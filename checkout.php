<?php
/**
 * Fast-Pass Checkout Redirector
 * Bypasses the intermediate checkout page and goes straight to Stripe.
 */
require_once __DIR__ . '/config/config.php';

// Check for plan
$plan_slug = isset($_GET['plan']) ? strtolower($_GET['plan']) : 'growth';

try {
    // 1. Determine environment and keys
    $mode = get_env_var('STRIPE_MODE', 'test');
    $stripe_secret = ($mode === 'live') ? get_env_var('STRIPE_LIVE_SECRET_KEY') : get_env_var('STRIPE_TEST_SECRET_KEY');

    if (empty($stripe_secret) || strpos($stripe_secret, 'sk_') !== 0) {
        throw new Exception("Stripe is not configured. Please add your keys in the Admin Settings.");
    }

    \Stripe\Stripe::setApiKey($stripe_secret);

    // 2. Define plan data
    $plans = [
        'growth'     => ['name' => 'Growth Plan', 'amount' => 6000], 
        'core'       => ['name' => 'Core Plan',   'amount' => 15000],
        'enterprise' => ['name' => 'Enterprise',  'amount' => 49900]
    ];

    if (!isset($plans[$plan_slug])) {
        throw new Exception("Invalid plan selected.");
    }

    $selected_plan = $plans[$plan_slug];

    // 3. Create Stripe Checkout Session
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'gbp',
                'product_data' => [
                    'name' => 'Fleet Rental Pro: ' . $selected_plan['name'],
                    'description' => 'Platform Subscription',
                ],
                'unit_amount' => $selected_plan['amount'],
                'recurring' => ['interval' => 'month'],
            ],
            'quantity' => 1,
        ]],
        'mode' => 'subscription',
        'success_url' => SITE_URL . '/checkout-success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'  => SITE_URL . '/upgrade.php?cancelled=1', // Go back to pricing
        'customer_email' => $_SESSION['user_email'] ?? null,
        'metadata' => [
            'user_id'   => $_SESSION['user_id'] ?? 0,
            'tenant_id' => $_SESSION['tenant_id'] ?? 0,
            'plan_slug' => $plan_slug
        ]
    ]);

    // 4. Direct Redirect to Stripe
    header("Location: " . $session->url);
    exit;

} catch (Exception $e) {
    // On error, show a clean message or redirect back with an error
    die("<h3>Checkout Error</h3><p>" . htmlspecialchars($e->getMessage()) . "</p><a href='/upgrade.php'>Go back</a>");
}