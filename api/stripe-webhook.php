<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Set your webhook secret from Stripe dashboard
$endpoint_secret = get_env_var('STRIPE_WEBHOOK_SECRET', '');

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$event = null;

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, $endpoint_secret
    );
} catch(\UnexpectedValueException $e) {
    // Invalid payload
    http_response_code(400);
    exit;
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    http_response_code(400);
    exit;
}

// Handle the event
if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;
    
    // Check if this is a domain purchase
    if (isset($session->metadata->type) && $session->metadata->type === 'domain_purchase') {
        $tenant_id = $session->metadata->tenant_id;
        $domain = $session->metadata->domain;
        
        $pdo = getDB();
        
        // Update tenant with new domain and registration info
        $expiry = date('Y-m-d H:i:s', strtotime('+1 year'));
        $stmt = $pdo->prepare("UPDATE tenants SET 
            custom_domain = ?, 
            custom_domain_status = 'active',
            domain_registration_source = 'platform',
            domain_expires_at = ?
            WHERE id = ?");
        $stmt->execute([$domain, $expiry, $tenant_id]);
        
        // Optionally: Trigger actual domain registration via registrar API here
    }
}

http_response_code(200);
