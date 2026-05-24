<?php
require_once __DIR__ . '/config/config.php';

use Stripe\Stripe;
use Stripe\Checkout\Session;

header('Content-Type: text/html');

// Check if we have a session ID
$session_id = $_GET['session_id'] ?? '';

if (empty($session_id)) {
    header('Location: /dashboard/');
    exit;
}

$mode = get_env_var('STRIPE_MODE', 'test');
$stripe_secret = ($mode === 'live') ? get_env_var('STRIPE_LIVE_SECRET_KEY') : get_env_var('STRIPE_TEST_SECRET_KEY');

Stripe::setApiKey($stripe_secret);

try {
    $session = Session::retrieve($session_id);
    $plan_slug = $session->metadata->plan_slug ?? 'growth';
    $user_id = $session->metadata->user_id ?? null;
    $tenant_id = $session->metadata->tenant_id ?? null;

    if ($session->payment_status === 'paid') {
        if ($user_id && $tenant_id) {
            // Existing User: Update the database immediately
            $pdo = getDB();
            $stmt = $pdo->prepare("UPDATE tenants SET plan = ?, status = 'active' WHERE id = ?");
            $stmt->execute([$plan_slug, $tenant_id]);
            
            // Update session info
            if (isset($_SESSION['tenant_id']) && $_SESSION['tenant_id'] == $tenant_id) {
                $_SESSION['plan'] = $plan_slug;
            }
        } else {
            // Guest User: Redirect to signup with the plan and session info
            header("Location: /auth/signup.php?plan=" . urlencode($plan_slug) . "&session_id=" . urlencode($session_id) . "&paid=true");
            exit;
        }
    }
} catch (Exception $e) {
    // Log error or handle failure
    error_log("Checkout Success Error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upgrade Successful | <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/app/custom.css">
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>
<body class="bg-slate-50 h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white rounded-[40px] p-12 shadow-2xl shadow-slate-200 border border-slate-100 text-center">
        <div class="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-8 border border-green-100">
            <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
        </div>
        
        <h1 class="text-3xl font-black text-slate-900 tracking-tight mb-4">Upgrade Successful!</h1>
        <p class="text-slate-500 font-medium tracking-tight mb-10 leading-relaxed">
            Congratulations! Your account has been upgraded to the <span class="text-slate-900 font-bold"><?= ucfirst($plan_slug) ?></span> plan. Your new features are now active.
        </p>

        <a href="/dashboard/" class="block w-full bg-slate-900 text-white py-5 rounded-2xl font-bold tracking-tight shadow-xl hover:bg-black transition-all active:scale-[0.98] text-lg">
            Go to Dashboard
        </a>
        
        <p class="mt-8 text-[11px] text-slate-400 font-bold uppercase tracking-widest">
            A confirmation email has been sent to your inbox.
        </p>
    </div>
</body>
</html>
