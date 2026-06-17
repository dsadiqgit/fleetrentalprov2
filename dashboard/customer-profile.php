<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/auth/login.php');
}

// Only customers can access this page
if ($_SESSION['role'] !== 'customer') {
    redirect('/dashboard/');
}

if (!$_SESSION['tenant_id']) {
    die('Error: No tenant associated with this account.');
}

$pdo = getDB();
$tenant_id = $_SESSION['tenant_id'];
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

// Get tenant info
$stmt = $pdo->prepare("SELECT * FROM tenants WHERE id = ?");
$stmt->execute([$tenant_id]);
$tenant = $stmt->fetch();

if (!$tenant) {
    die('Error: Tenant not found.');
}

// Add address columns to users table if not exists
try {
    $columns = ['address_line1', 'address_line2', 'city', 'postcode', 'country'];
    foreach ($columns as $col) {
        $stmt_col = $pdo->query("SHOW COLUMNS FROM users LIKE '$col'");
        if ($stmt_col->rowCount() == 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN $col VARCHAR(255) DEFAULT ''");
        }
    }
} catch (Exception $e) {}

// Initialize mock card in session if not set
if (empty($_SESSION['mock_card'])) {
    $_SESSION['mock_card'] = [
        'last4' => '4242',
        'brand' => 'Visa',
        'expiry' => '12/28',
        'holder' => ''
    ];
}

// Fetch user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND tenant_id = ?");
$stmt->execute([$user_id, $tenant_id]);
$user = $stmt->fetch();

if (empty($_SESSION['mock_card']['holder']) && $user) {
    $_SESSION['mock_card']['holder'] = $user['full_name'];
}

// Handle Profile and Card updates
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $first_name = sanitize($_POST['first_name'] ?? '');
        $last_name = sanitize($_POST['last_name'] ?? '');
        $full_name = trim($first_name . ' ' . $last_name);
        $phone = sanitize($_POST['phone'] ?? '');
        $address_line1 = sanitize($_POST['address_line1'] ?? '');
        $address_line2 = sanitize($_POST['address_line2'] ?? '');
        $city = sanitize($_POST['city'] ?? '');
        $postcode = sanitize($_POST['postcode'] ?? '');
        $country = sanitize($_POST['country'] ?? '');

        if (empty($full_name)) {
            $error_msg = 'Full name is required.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET full_name = ?, phone = ?, 
                        address_line1 = ?, address_line2 = ?, city = ?, postcode = ?, country = ?,
                        updated_at = NOW() 
                    WHERE id = ? AND tenant_id = ?
                ");
                $stmt->execute([
                    $full_name, $phone, 
                    $address_line1, $address_line2, $city, $postcode, $country,
                    $user_id, $tenant_id
                ]);
                
                // Update local session mock card holder if unchanged
                if ($_SESSION['mock_card']['holder'] === $user['full_name']) {
                    $_SESSION['mock_card']['holder'] = $full_name;
                }
                
                $success_msg = 'Profile information updated successfully!';
                
                // Re-fetch user
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$user_id, $tenant_id]);
                $user = $stmt->fetch();
            } catch (Exception $e) {
                $error_msg = 'Database error updating profile.';
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update_card') {
        $number = sanitize($_POST['card_number'] ?? '');
        $expiry = sanitize($_POST['card_expiry'] ?? '');
        $holder = sanitize($_POST['card_holder'] ?? '');
        
        // Clean card number to last 4 digits
        $clean_number = preg_replace('/[^0-9]/', '', $number);
        $last4 = substr($clean_number, -4);
        
        // Detect brand basic check
        $brand = 'Visa';
        if (strpos($clean_number, '5') === 0 || strpos($clean_number, '2') === 0) {
            $brand = 'Mastercard';
        } elseif (strpos($clean_number, '3') === 0) {
            $brand = 'Amex';
        }

        if (strlen($last4) < 4 || empty($expiry) || empty($holder)) {
            $error_msg = 'Please enter valid credit card details.';
        } else {
            $_SESSION['mock_card'] = [
                'last4' => $last4,
                'brand' => $brand,
                'expiry' => $expiry,
                'holder' => $holder
            ];
            $success_msg = 'Card details updated successfully!';
        }
    }
}

// Parse full name into first and last name
$name_parts = explode(' ', $user['full_name'] ?? '', 2);
$user_first_name = $name_parts[0] ?? '';
$user_last_name = $name_parts[1] ?? '';

$primaryColor = $tenant['primary_color'] ?? '#3B82F6';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?= htmlspecialchars($tenant['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/app/custom.css">
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
    <script src="/app/custom-select.js" defer></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        :root {
            --primary-color: <?= $primaryColor ?>;
            --primary-hover: <?= $primaryColor ?>dd;
            --primary-light: <?= $primaryColor ?>10;
        }
        .text-brand { color: var(--primary-color); }
        .bg-brand { background-color: var(--primary-color); }
        .bg-brand-light { background-color: var(--primary-light); }
        .border-brand { border-color: var(--primary-color); }
        .focus\:ring-brand:focus { --tw-ring-color: var(--primary-color); }
    </style>
</head>
<body class="bg-slate-50/50 flex h-screen overflow-hidden text-slate-800">

    <!-- Mobile Header -->
    <header class="lg:hidden fixed top-0 left-0 right-0 bg-white border-b border-slate-100 px-6 py-4 z-40 flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-3">
            <button id="mobile-menu-btn" class="p-2 hover:bg-slate-50 rounded-xl transition-colors text-slate-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <?php if (!empty($tenant['logo'])): ?>
                <img src="<?= htmlspecialchars($tenant['logo']) ?>" alt="Logo" class="h-6 w-auto object-contain">
            <?php else: ?>
                <span class="text-lg font-bold text-slate-900 tracking-tight"><?= htmlspecialchars($tenant['name']) ?></span>
            <?php endif; ?>
        </div>
        <div class="w-9 h-9 rounded-xl bg-brand flex items-center justify-center text-white font-bold text-sm shadow-sm shadow-brand/20">
            <?= strtoupper(substr($user['full_name'] ?? 'C', 0, 1)) ?>
        </div>
    </header>

    <!-- Sidebar Overlay -->
    <div id="sidebar-overlay" class="lg:hidden fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-30 hidden transition-all duration-300"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed lg:static inset-y-0 left-0 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out z-40">
        <?php include __DIR__ . '/../includes/customer-sidebar.php'; ?>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden pt-16 lg:pt-0">
        <main class="flex-1 overflow-y-auto bg-slate-50/30 p-4 sm:p-6 lg:p-10">
            <!-- Header -->
            <div class="max-w-6xl mb-10">
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">My Profile</h1>
                <p class="text-slate-500 mt-2 font-medium text-sm sm:text-base">Manage your personal information and active payment methods.</p>
            </div>

            <!-- Notifications -->
            <?php if ($success_msg): ?>
                <div class="max-w-4xl mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl text-sm font-medium flex items-center gap-3">
                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <?= htmlspecialchars($success_msg) ?>
                </div>
            <?php endif; ?>

            <?php if ($error_msg): ?>
                <div class="max-w-4xl mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl text-sm font-medium flex items-center gap-3">
                    <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <?= htmlspecialchars($error_msg) ?>
                </div>
            <?php endif; ?>

            <div class="max-w-4xl grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
                
                <!-- Personal Information Panel -->
                <div class="bg-white border border-gray-100 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Personal Information</h2>
                        <p class="text-xs text-gray-400 mt-1 uppercase tracking-wider font-semibold">Your Contact Details</p>
                    </div>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">First Name</label>
                                <input type="text" name="first_name" value="<?= htmlspecialchars($user_first_name) ?>" required
                                       class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Last Name</label>
                                <input type="text" name="last_name" value="<?= htmlspecialchars($user_last_name) ?>" required
                                       class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Email Address</label>
                            <input type="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm text-gray-400 bg-gray-100/80 cursor-not-allowed">
                            <span class="text-[10px] text-gray-400 mt-1 block leading-tight">Email address cannot be changed. Please contact support if you need to update it.</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Mobile Number</label>
                            <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50">
                        </div>

                        <!-- Address Fields -->
                        <div class="border-t border-gray-100 pt-6 mt-6">
                            <h3 class="text-sm font-semibold text-gray-900 mb-4 uppercase tracking-wider text-xs">Address Details</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Address Line 1</label>
                                    <input type="text" name="address_line1" value="<?= htmlspecialchars($user['address_line1'] ?? '') ?>" placeholder="123 High Street"
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Address Line 2 (Optional)</label>
                                    <input type="text" name="address_line2" value="<?= htmlspecialchars($user['address_line2'] ?? '') ?>" placeholder="Apartment, suite, etc."
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50">
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">City</label>
                                        <input type="text" name="city" value="<?= htmlspecialchars($user['city'] ?? '') ?>" placeholder="London"
                                               class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Postcode</label>
                                        <input type="text" name="postcode" value="<?= htmlspecialchars($user['postcode'] ?? '') ?>" placeholder="SW1A 1AA"
                                               class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50 font-mono">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Country</label>
                                    <select name="country" class="custom-select w-full">
                                        <?php
                                        $saved_country = $user['country'] ?? 'GB';
                                        $country_options = [''=>'Select country…','GB'=>'United Kingdom','IE'=>'Ireland','US'=>'United States','AU'=>'Australia','CA'=>'Canada','FR'=>'France','DE'=>'Germany','ES'=>'Spain','IT'=>'Italy','NL'=>'Netherlands','Other'=>'Other'];
                                        foreach ($country_options as $code => $label): ?>
                                            <option value="<?= htmlspecialchars($code) ?>"<?= $saved_country === $code ? ' selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-3 bg-gray-900 hover:bg-black text-white text-sm font-semibold rounded-xl transition-all shadow-sm mt-4">
                            Save Changes
                        </button>
                    </form>
                </div>

                <!-- Card details and Billing Profile -->
                <div class="space-y-8">
                    
                    <!-- Gorgeous CSS Credit Card Mockup -->
                    <div class="relative w-full h-48 rounded-[1.5rem] p-6 text-white overflow-hidden shadow-2xl flex flex-col justify-between transform transition hover:scale-[1.02] duration-300" 
                         style="background: linear-gradient(135deg, #1e293b, #111827); box-shadow: 0 15px 30px -10px rgba(15, 23, 42, 0.4);">
                        
                        <!-- Card chip / brand -->
                        <div class="flex items-start justify-between">
                            <!-- Simulated chip -->
                            <div class="w-10 h-8 bg-gradient-to-br from-amber-300 to-amber-500 rounded-lg flex items-center justify-center shadow-inner relative opacity-85">
                                <div class="absolute inset-1.5 border border-amber-600/30 rounded"></div>
                            </div>
                            <!-- Card brand logo -->
                            <span class="text-lg italic font-black tracking-wider uppercase opacity-90">
                                <?= htmlspecialchars($_SESSION['mock_card']['brand']) ?>
                            </span>
                        </div>

                        <!-- Card number -->
                        <div class="text-xl sm:text-2xl font-semibold tracking-[0.2em] font-mono opacity-90">
                            •••• &nbsp; •••• &nbsp; •••• &nbsp; <?= htmlspecialchars($_SESSION['mock_card']['last4']) ?>
                        </div>

                        <!-- Card holder & expiry -->
                        <div class="flex justify-between items-end">
                            <div class="min-w-0">
                                <span class="text-[9px] text-gray-400 uppercase tracking-wider block">Card Holder</span>
                                <span class="text-xs font-semibold uppercase tracking-wide block truncate max-w-[180px]"><?= htmlspecialchars($_SESSION['mock_card']['holder'] ?: 'Your Name') ?></span>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <span class="text-[9px] text-gray-400 uppercase tracking-wider block">Expires</span>
                                <span class="text-xs font-semibold tracking-wide block"><?= htmlspecialchars($_SESSION['mock_card']['expiry']) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Update Card Panel -->
                    <div class="bg-white border border-gray-100 rounded-3xl p-6 shadow-sm space-y-6">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Credit Card Details</h2>
                            <p class="text-xs text-gray-400 mt-1 uppercase tracking-wider font-semibold">Update Active Card</p>
                        </div>

                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="update_card">
                            
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Card Number</label>
                                <input type="text" name="card_number" placeholder="•••• •••• •••• 4242" required maxlength="19"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\d{4})/g, '$1 ').trim()"
                                       class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50 font-mono">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Expiry Date</label>
                                    <input type="text" name="card_expiry" placeholder="12/28" required maxlength="5"
                                           oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\d{2})/, '$1/').trim()"
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50 font-mono">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Card Holder Name</label>
                                    <input type="text" name="card_holder" value="<?= htmlspecialchars($_SESSION['mock_card']['holder'] ?: '') ?>" required
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50 uppercase">
                                </div>
                            </div>

                            <button type="submit" class="w-full py-3 bg-gray-900 hover:bg-black text-white text-sm font-semibold rounded-xl transition-all shadow-sm mt-4">
                                Update Credit Card
                            </button>
                        </form>
                    </div>

                </div>

            </div>
        </main>
    </div>

    <script>
        // Mobile menu toggle script
        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('mobile-menu-btn');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            if (btn) btn.addEventListener('click', () => {
                sidebar.classList.toggle('-translate-x-full');
                overlay.classList.toggle('hidden');
            });
            if (overlay) overlay.addEventListener('click', () => {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            });
        });
    </script>
</body>
</html>
