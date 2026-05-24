<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in and is super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    redirect('/auth/login.php');
}

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_stripe'])) {
        $st_mode = $_POST['stripe_mode'] ?? 'test';
        $st_test_secret = $_POST['stripe_test_secret_key'] ?? '';
        $st_test_public = $_POST['stripe_test_public_key'] ?? '';
        $st_test_webhook = $_POST['stripe_test_webhook_secret'] ?? '';
        $st_live_secret = $_POST['stripe_live_secret_key'] ?? '';
        $st_live_public = $_POST['stripe_live_public_key'] ?? '';
        $st_live_webhook = $_POST['stripe_live_webhook_secret'] ?? '';

        // Update .env file
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $content = file_get_contents($envFile);
            
            $keys = [
                'STRIPE_MODE' => $st_mode,
                'STRIPE_TEST_SECRET_KEY' => $st_test_secret,
                'STRIPE_TEST_PUBLIC_KEY' => $st_test_public,
                'STRIPE_TEST_WEBHOOK_SECRET' => $st_test_webhook,
                'STRIPE_LIVE_SECRET_KEY' => $st_live_secret,
                'STRIPE_LIVE_PUBLIC_KEY' => $st_live_public,
                'STRIPE_LIVE_WEBHOOK_SECRET' => $st_live_webhook
            ];

            foreach ($keys as $key => $value) {
                if (preg_match("/^{$key}=/m", $content)) {
                    $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
                } else {
                    $content .= "\n{$key}={$value}";
                }
            }
            
            if (file_put_contents($envFile, trim($content) . "\n")) {
                $success = 'Stripe settings updated successfully!';
            } else {
                $error = 'Failed to write to .env file. Check permissions.';
            }
        }
    }
}

// Helper to get current env OR fallback
function get_env_setting($name, $fallback = '') {
    $val = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);
    return !empty($val) ? $val : $fallback;
}

// Current values
$current_mode = get_env_setting('STRIPE_MODE', 'test');
$test_secret = get_env_setting('STRIPE_TEST_SECRET_KEY');
$test_public = get_env_setting('STRIPE_TEST_PUBLIC_KEY');
$test_webhook = get_env_setting('STRIPE_TEST_WEBHOOK_SECRET');
$live_secret = get_env_setting('STRIPE_LIVE_SECRET_KEY');
$live_public = get_env_setting('STRIPE_LIVE_PUBLIC_KEY');
$live_webhook = get_env_setting('STRIPE_LIVE_WEBHOOK_SECRET');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/app/custom.css">
    <style>
        .toggle-checkbox:checked { right: 0; border-color: #0F172A; }
        .toggle-checkbox:checked + .toggle-label { background-color: #0F172A; }
    </style>
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Bar -->
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Platform Settings</h1>
                    <p class="text-sm text-gray-600 mt-1">Configure global platform integrations and preferences</p>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <main class="flex-1 overflow-y-auto p-8">
            <div class="max-w-4xl mx-auto pb-20">
                
                <?php if ($success): ?>
                    <div class="bg-green-50 border border-green-100 text-green-700 px-6 py-4 rounded-2xl mb-8 flex items-center gap-3 shadow-sm">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        <span class="font-bold text-sm"><?= $success ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-50 border border-red-100 text-red-700 px-6 py-4 rounded-2xl mb-8 flex items-center gap-3 shadow-sm">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414-1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                        <span class="font-bold text-sm"><?= $error ?></span>
                    </div>
                <?php endif; ?>

                <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden">
                    <div class="flex">
                        <!-- Settings Sidebar -->
                        <div class="w-64 bg-slate-50 border-r border-slate-100 p-6 space-y-2">
                            <button class="w-full text-left px-4 py-3 rounded-xl bg-white shadow-sm border border-slate-200 text-sm font-bold text-slate-900 flex items-center gap-3">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                Stripe Setup
                            </button>
                            <button class="w-full text-left px-4 py-3 rounded-xl text-slate-400 text-sm font-medium hover:bg-slate-100 transition flex items-center gap-3" disabled>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 00-2 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                Auth0 Integration
                            </button>
                        </div>

                        <!-- Settings Content -->
                        <div class="flex-1 p-10">
                            <form method="POST" class="space-y-12">
                                <div class="flex items-center justify-between p-6 bg-slate-900 rounded-3xl text-white">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                        </div>
                                        <div>
                                            <h3 class="font-black tracking-tight text-lg">Stripe Mode</h3>
                                            <p class="text-xs text-white/60 font-medium">Toggle between test and live transactions</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-bold uppercase tracking-widest <?= $current_mode === 'test' ? 'text-white' : 'text-white/40' ?>">Test</span>
                                        <div class="relative inline-block w-14 h-8 align-middle select-none transition duration-200 ease-in">
                                            <input type="checkbox" name="stripe_mode_toggle" id="stripe_mode_toggle" value="live" <?= $current_mode === 'live' ? 'checked' : '' ?> onchange="document.getElementById('stripe_mode_hidden').value = this.checked ? 'live' : 'test'" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer top-1 left-1 checked:left-7 transition-all duration-300"/>
                                            <label for="stripe_mode_toggle" class="toggle-label block overflow-hidden h-8 rounded-full bg-white/20 cursor-pointer transition-colors duration-300"></label>
                                            <input type="hidden" name="stripe_mode" id="stripe_mode_hidden" value="<?= $current_mode ?>">
                                        </div>
                                        <span class="text-xs font-bold uppercase tracking-widest <?= $current_mode === 'live' ? 'text-white' : 'text-white/40' ?>">Live</span>
                                    </div>
                                </div>

                                <div class="grid lg:grid-cols-2 gap-10">
                                    <!-- Test Keys -->
                                    <div class="space-y-6">
                                        <div class="flex items-center gap-2 mb-4">
                                            <span class="px-2 py-1 bg-amber-50 text-amber-600 text-[10px] font-black uppercase tracking-widest rounded-md border border-amber-100">Development</span>
                                            <h4 class="font-bold text-slate-900">Test Credentials</h4>
                                        </div>
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 mb-2">Test Public Key</label>
                                                <input type="text" name="stripe_test_public_key" value="<?= htmlspecialchars($test_public) ?>" placeholder="pk_test_..." class="w-full bg-slate-50 border border-slate-100 rounded-2xl p-4 text-slate-900 font-bold placeholder-slate-300 focus:border-slate-900 focus:ring-4 focus:ring-slate-50 transition-all outline-none">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 mb-2">Test Secret Key</label>
                                                <input type="password" name="stripe_test_secret_key" value="<?= htmlspecialchars($test_secret) ?>" placeholder="sk_test_..." class="w-full bg-slate-50 border border-slate-100 rounded-2xl p-4 text-slate-900 font-bold focus:border-slate-900 focus:ring-4 focus:ring-slate-50 transition-all outline-none">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 mb-2">Test Webhook Secret</label>
                                                <input type="text" name="stripe_test_webhook_secret" value="<?= htmlspecialchars($test_webhook) ?>" placeholder="whsec_..." class="w-full bg-slate-50 border border-slate-100 rounded-2xl p-4 text-slate-900 font-bold focus:border-slate-900 focus:ring-4 focus:ring-slate-50 transition-all outline-none">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Live Keys -->
                                    <div class="space-y-6">
                                        <div class="flex items-center gap-2 mb-4">
                                            <span class="px-2 py-1 bg-green-50 text-green-600 text-[10px] font-black uppercase tracking-widest rounded-md border border-green-100">Production</span>
                                            <h4 class="font-bold text-slate-900">Live Credentials</h4>
                                        </div>
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 mb-2">Live Public Key</label>
                                                <input type="text" name="stripe_live_public_key" value="<?= htmlspecialchars($live_public) ?>" placeholder="pk_live_..." class="w-full bg-slate-50 border border-slate-100 rounded-2xl p-4 text-slate-900 font-bold placeholder-slate-300 focus:border-slate-900 focus:ring-4 focus:ring-slate-50 transition-all outline-none">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 mb-2">Live Secret Key</label>
                                                <input type="password" name="stripe_live_secret_key" value="<?= htmlspecialchars($live_secret) ?>" placeholder="sk_live_..." class="w-full bg-slate-50 border border-slate-100 rounded-2xl p-4 text-slate-900 font-bold focus:border-slate-900 focus:ring-4 focus:ring-slate-50 transition-all outline-none">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 mb-2">Live Webhook Secret</label>
                                                <input type="text" name="stripe_live_webhook_secret" value="<?= htmlspecialchars($live_webhook) ?>" placeholder="whsec_..." class="w-full bg-slate-50 border border-slate-100 rounded-2xl p-4 text-slate-900 font-bold focus:border-slate-900 focus:ring-4 focus:ring-slate-50 transition-all outline-none">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-10 border-t border-slate-50 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-2 h-2 rounded-full <?= $current_mode === 'test' ? 'bg-amber-500 animate-pulse' : 'bg-green-500 animate-pulse' ?>"></div>
                                        <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Active Mode: <span class="<?= $current_mode === 'test' ? 'text-amber-600' : 'text-green-600' ?>"><?= strtoupper($current_mode) ?></span></span>
                                    </div>
                                    <button type="submit" name="update_stripe" class="bg-slate-900 text-white px-10 py-5 rounded-2xl font-black tracking-tight shadow-2xl hover:bg-black transition-all active:scale-[0.98] text-sm">
                                        Save All Configurations
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="mt-8 p-6 bg-blue-50 rounded-2xl border border-blue-100 flex items-start gap-4">
                    <div class="w-10 h-10 bg-white rounded-xl shadow-sm flex items-center justify-center flex-shrink-0 text-blue-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-blue-900 mb-1">Developer Tip</h4>
                        <p class="text-xs text-blue-700 font-medium leading-relaxed">Changes made here are directly written to your <code>.env</code> file. For security, ensure this file is not accessible via the browser and is included in your <code>.gitignore</code>.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
