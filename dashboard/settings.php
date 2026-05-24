<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/auth/login.php');
}

// Redirect super admin to their dashboard
if ($_SESSION['role'] === 'super_admin') {
    redirect('/admin/super-admin.php');
}

// Check if user has a tenant
if (!$_SESSION['tenant_id']) {
    die('Error: No tenant associated with this account.');
}

$pdo = getDB();

// Get tenant information
$stmt = $pdo->prepare("SELECT * FROM tenants WHERE id = ?");
$stmt->execute([$_SESSION['tenant_id']]);
$tenant = $stmt->fetch();

if (!$tenant) {
    die('Error: Tenant not found.');
}

// Calculate trial days remaining
$trial_days_remaining = 0;
$trial_percentage = 0;

// Use trial_ends_at if set, otherwise calculate 30 days from created_at
$trial_start = new DateTime($tenant['created_at']);
$trial_end = $tenant['trial_ends_at'] ? new DateTime($tenant['trial_ends_at']) : (clone $trial_start)->modify('+30 days');
$now = new DateTime();

if ($now < $trial_end) {
    $interval = $now->diff($trial_end);
    $trial_days_remaining = $interval->days;
    // Cap at 30 days
    if ($trial_days_remaining > 30) $trial_days_remaining = 30;
    $trial_percentage = ($trial_days_remaining / 30) * 100;
}

// Get tenant settings
$stmt = $pdo->prepare("SELECT * FROM tenant_settings WHERE tenant_id = ?");
$stmt->execute([$_SESSION['tenant_id']]);
$settings = $stmt->fetch();

if (!$settings) {
    // Create default settings if they don't exist
    $stmt = $pdo->prepare("INSERT INTO tenant_settings (tenant_id) VALUES (?)");
    $stmt->execute([$_SESSION['tenant_id']]);
    $settings = ['tenant_id' => $_SESSION['tenant_id']];
}

$error = '';
$success = '';
$active_tab = $_GET['tab'] ?? 'general';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_general':
                $currency = sanitize($_POST['currency'] ?? 'GBP');
                $distance_unit = sanitize($_POST['distance_unit'] ?? 'Miles');
                $week_start = sanitize($_POST['week_start'] ?? 'Monday');
                $require_license_verification = isset($_POST['require_license_verification']) ? 1 : 0;
                
                try {
                    $stmt = $pdo->prepare("UPDATE tenant_settings SET currency = ?, distance_unit = ?, week_start_day = ?, require_license_verification = ? WHERE tenant_id = ?");
                    $stmt->execute([$currency, $distance_unit, $week_start, $require_license_verification, $_SESSION['tenant_id']]);
                    $success = 'Settings updated successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to update settings.';
                }
                break;
                
            case 'update_booking':
                $min_notice = intval($_POST['min_notice'] ?? 0);
                $notice_unit = sanitize($_POST['notice_unit'] ?? 'Hours');
                $buffer_time = intval($_POST['buffer_time'] ?? 0);
                $max_advance = intval($_POST['max_advance'] ?? 30);
                
                try {
                    $stmt = $pdo->prepare("UPDATE tenant_settings SET min_booking_notice = ?, booking_notice_unit = ?, buffer_time_hours = ?, max_booking_advance_days = ? WHERE tenant_id = ?");
                    $stmt->execute([$min_notice, $notice_unit, $buffer_time, $max_advance, $_SESSION['tenant_id']]);
                    $success = 'Booking settings updated successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to update booking settings.';
                }
                break;
                
            case 'update_payment':
                $deposit_amount = floatval($_POST['deposit_amount'] ?? 0);
                $deposit_mode = sanitize($_POST['deposit_mode'] ?? 'collection');
                
                try {
                    $stmt = $pdo->prepare("UPDATE tenant_settings SET deposit_amount = ?, deposit_payment_mode = ? WHERE tenant_id = ?");
                    $stmt->execute([$deposit_amount, $deposit_mode, $_SESSION['tenant_id']]);
                    $success = 'Payment settings updated successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to update payment settings.';
                }
                break;
        }
    }
}

// Refresh settings after update
$stmt = $pdo->prepare("SELECT * FROM tenant_settings WHERE tenant_id = ?");
$stmt->execute([$_SESSION['tenant_id']]);
$settings = $stmt->fetch();

$currency_code = $settings['currency'] ?? 'GBP';
$currency_symbols = ['GBP' => '£', 'USD' => '$', 'EUR' => '€'];
$currency_symbol = $currency_symbols[$currency_code] ?? $currency_code;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?= htmlspecialchars($tenant['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/app/custom.css">
    <script src="/app/custom-select.js" defer></script>
    <style>
        .sidebar-item {
            transition: all 0.2s;
        }
        .sidebar-item:hover {
            background-color: #f3f4f6;
        }
        .sidebar-item.active {
            background-color: #eff6ff;
            color: #3b82f5;
        }
        .sidebar-item.active svg {
            color: #3b82f5;
        }
        .tab-button {
            transition: all 0.2s;
        }
        .tab-button.active {
            color: #111827;
            border-bottom: 2px solid #111827;
        }
    </style>
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <!-- Mobile Header -->
    <header class="lg:hidden fixed top-0 left-0 right-0 bg-white border-b border-gray-200 px-4 py-3 z-40 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <button id="mobile-menu-btn" class="p-1 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <h1 class="text-lg font-semibold text-gray-900">Dashboard</h1>
        </div>
        <div class="flex items-center gap-3">
            <button class="p-1 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
            </button>
            <button class="p-1 hover:bg-gray-100 rounded-lg transition-colors relative">
                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
            </button>
            <button class="w-8 h-8 rounded-full bg-black text-white flex items-center justify-center text-xs font-semibold">
                FL
            </button>
        </div>
    </header>

    <!-- Sidebar Overlay -->
    <div id="sidebar-overlay" class="lg:hidden fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-30 hidden transition-all duration-300"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed lg:static top-14 lg:top-0 bottom-0 left-0 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out z-40 lg:flex">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden w-full lg:w-auto pt-14 lg:pt-0">
        <!-- Desktop Top Bar -->
        <header class="hidden lg:block bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <nav class="text-sm text-gray-500 mb-1">
                        <a href="/dashboard/" class="hover:text-gray-700">Dashboard</a>
                        <span class="mx-2">/</span>
                        <span class="text-gray-900">Settings</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
                    <p class="text-sm text-gray-600 mt-1">Control and customise your account</p>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto bg-gray-50 p-4 sm:p-6 lg:p-8">
            <div class="max-w-4xl">

                <!-- Tabs -->
                <div class="relative shrink-0 mb-6 sm:mb-8">
                    <div class="no-scrollbar flex h-9 w-fit max-w-full items-center rounded-full border border-[rgba(120,120,128,0.05)] bg-[rgba(120,120,128,0.05)] p-1 select-none">
                        <div class="flex items-center">
                            <a href="?tab=general" class="flex cursor-pointer items-center gap-1.5 rounded-full px-2.5 py-1 font-medium whitespace-nowrap transition-colors text-sm <?= $active_tab === 'general' ? 'text-blue-600 bg-[rgba(120,120,128,0.08)]' : 'text-[#4b5058] hover:text-black' ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                                    <path d="M21.4707 19V5C21.4707 3 20.4707 2 18.4707 2H14.4707C12.4707 2 11.4707 3 11.4707 5V19C11.4707 21 12.4707 22 14.4707 22H18.4707C20.4707 22 21.4707 21 21.4707 19Z"></path>
                                    <path d="M11.4707 6H16.4707"></path>
                                    <path d="M11.4707 18H15.4707"></path>
                                    <path d="M11.4707 13.9502L16.4707 14.0002"></path>
                                    <path d="M11.4707 10H14.4707"></path>
                                    <path d="M5.4893 2C3.8593 2 2.5293 3.33 2.5293 4.95V17.91C2.5293 18.36 2.7193 19.04 2.9493 19.43L3.7693 20.79C4.7093 22.36 6.2593 22.36 7.1993 20.79L8.0193 19.43C8.2493 19.04 8.4393 18.36 8.4393 17.91V4.95C8.4393 3.33 7.1093 2 5.4893 2Z"></path>
                                    <path d="M8.4393 7H2.5293"></path>
                                </svg>
                                General
                            </a>
                        </div>
                        <div class="flex items-center">
                            <div class="mx-px h-5 w-px <?= $active_tab === 'general' || $active_tab === 'booking' ? 'opacity-0' : 'bg-[#e6e6e6]' ?>"></div>
                            <a href="?tab=booking" class="flex cursor-pointer items-center gap-1.5 rounded-full px-2.5 py-1 font-medium whitespace-nowrap transition-colors text-sm <?= $active_tab === 'booking' ? 'text-blue-600 bg-[rgba(120,120,128,0.08)]' : 'text-[#4b5058] hover:text-black' ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                                    <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Booking
                            </a>
                        </div>
                        <div class="flex items-center">
                            <div class="mx-px h-5 w-px <?= $active_tab === 'booking' || $active_tab === 'payment' ? 'opacity-0' : 'bg-[#e6e6e6]' ?>"></div>
                            <a href="?tab=payment" class="flex cursor-pointer items-center gap-1.5 rounded-full px-2.5 py-1 font-medium whitespace-nowrap transition-colors text-sm <?= $active_tab === 'payment' ? 'text-blue-600 bg-[rgba(120,120,128,0.08)]' : 'text-[#4b5058] hover:text-black' ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                                    <path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                </svg>
                                Payment
                            </a>
                        </div>

                    </div>
                </div>

                <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
                    <?= htmlspecialchars($success) ?>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <!-- Tab Content -->
                <?php if ($active_tab === 'general'): ?>
                <!-- General Settings Tab -->
                <form method="POST" class="space-y-8">
                    <input type="hidden" name="action" value="update_general">
                    
                    <!-- Driver License Verification -->
                    <div>
                        <div class="flex items-start space-x-2 mb-3">
                            <h3 class="text-base font-semibold text-gray-900">Driver licence verification feature</h3>
                            <button type="button" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg mb-4">
                            <div class="flex items-center space-x-3">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="require_license_verification" value="1" class="sr-only peer" <?= ($settings['require_license_verification'] ?? 0) ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-gray-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                                </label>
                                <span class="text-sm text-gray-700">When turned on your customers will need to verify their driver licence before they can make a booking</span>
                            </div>
                        </div>
                    </div>

                    <!-- Currency -->
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 mb-2">Currency</h3>
                        <p class="text-sm text-gray-600 mb-4">You can change it until your first booking/payment.</p>
                        <div class="flex items-center space-x-3">
                            <select name="currency" class="custom-select">
                                <option value="GBP" <?= ($settings['currency'] ?? 'GBP') === 'GBP' ? 'selected' : '' ?>>GBP</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                            </select>
                            <button type="submit" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium">
                                Save currency
                            </button>
                        </div>
                    </div>

                    <!-- Distance Unit -->
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 mb-2">Distance unit</h3>
                        <p class="text-sm text-gray-600 mb-4">You can change this setting until your first booking/payment.</p>
                        <div class="flex items-center space-x-3">
                            <div class="toggle-select flex border border-gray-300 rounded-lg overflow-hidden bg-white">
                                <button type="button" class="px-4 py-2 text-sm font-medium transition-colors <?= ($settings['distance_unit'] ?? 'Miles') === 'Kilometres' ? 'bg-gray-100 text-gray-900 border-r border-gray-300' : 'text-gray-500 hover:text-gray-700' ?>" onclick="setDistanceUnit('Kilometres', this)">
                                    Kilometres
                                </button>
                                <button type="button" class="px-4 py-2 text-sm font-medium transition-colors <?= ($settings['distance_unit'] ?? 'Miles') === 'Miles' ? 'bg-gray-100 text-gray-900 border-l border-gray-300' : 'text-gray-500 hover:text-gray-700' ?>" onclick="setDistanceUnit('Miles', this)">
                                    Miles
                                </button>
                            </div>
                            <input type="hidden" name="distance_unit" id="distance_unit_input" value="<?= htmlspecialchars($settings['distance_unit'] ?? 'Miles') ?>">
                            <button type="submit" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium">
                                Save
                            </button>
                        </div>
                    </div>

                    <!-- Start Week On -->
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 mb-4">Start the week on</h3>
                        <div class="flex items-center space-x-3">
                            <select name="week_start" class="custom-select">
                                <option value="Monday" <?= ($settings['week_start_day'] ?? 'Monday') === 'Monday' ? 'selected' : '' ?>>Monday</option>
                                <option value="Sunday">Sunday</option>
                            </select>
                            <button type="submit" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium">
                                Save
                            </button>
                        </div>
                    </div>
                </form>

                <?php elseif ($active_tab === 'booking'): ?>
                <!-- Booking Settings Tab -->
                <form method="POST" class="space-y-8">
                    <input type="hidden" name="action" value="update_booking">
                    
                    <!-- Manual Approval -->
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 mb-2">Require manual approval</h3>
                        <p class="text-sm text-gray-600 mb-4">When enabled, you'll have 48h to approve bookings made online.</p>
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex items-center space-x-2 px-4 py-2 bg-gray-100 rounded-lg">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-sm font-medium text-gray-700">Manual approval is currently turned off</span>
                            </div>
                            <button type="button" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium">
                                Turn on manual approval
                            </button>
                        </div>
                    </div>

                    <!-- Minimum Notice -->
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 mb-2">Minimum notice before a booking</h3>
                        <p class="text-sm text-gray-600 mb-4">Minimum time required between the booking request and pickup.</p>
                        <div class="flex items-center space-x-3">
                            <input type="number" name="min_notice" value="<?= htmlspecialchars($settings['min_booking_notice'] ?? '48') ?>" placeholder="e.g. 48" class="w-32 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <div class="flex bg-gray-100 rounded-lg">
                                <button type="button" onclick="setNoticeUnit('Hours')" class="px-4 py-2 text-sm font-medium rounded-l-lg hover:bg-gray-200">Hours</button>
                                <button type="button" onclick="setNoticeUnit('Days')" class="px-4 py-2 text-sm font-medium rounded-r-lg hover:bg-gray-200">Days</button>
                            </div>
                            <input type="hidden" name="notice_unit" id="notice_unit" value="<?= htmlspecialchars($settings['booking_notice_unit'] ?? 'Hours') ?>">
                        </div>
                    </div>

                    <!-- Buffer Time -->
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 mb-2">Buffer time between bookings (hours)</h3>
                        <p class="text-sm text-gray-600 mb-4">Minimum time you need between two rentals for cleaning, inspection, or preparation.</p>
                        <input type="number" name="buffer_time" value="<?= htmlspecialchars($settings['buffer_time_hours'] ?? '6') ?>" placeholder="e.g. 6" class="w-32 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Maximum Booking Window -->
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 mb-2">Maximum booking window (days in advance)</h3>
                        <p class="text-sm text-gray-600 mb-4">How far ahead clients can book a car</p>
                        <input type="number" name="max_advance" value="<?= htmlspecialchars($settings['max_booking_advance_days'] ?? '30') ?>" placeholder="e.g. 30" class="w-32 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Save Button -->
                    <div class="pt-4">
                        <button type="submit" class="px-6 py-2.5 bg-black text-white rounded-lg hover:bg-gray-800 font-medium">
                            Save changes
                        </button>
                    </div>
                </form>

                <?php elseif ($active_tab === 'payment'): ?>
                <!-- Payment Methods Tab -->
                <div class="space-y-8">
                    <!-- Deposit Settings -->
                    <form method="POST" class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm space-y-6">
                        <input type="hidden" name="action" value="update_payment">
                        
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 mb-2">Deposit Settings</h3>
                            <p class="text-sm text-gray-600 mb-6">Manage how you take security deposits for your rentals.</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Deposit Amount</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm"><?= $currency_symbol ?? '£' ?></span>
                                        </div>
                                        <input type="number" name="deposit_amount" step="0.01" value="<?= htmlspecialchars($settings['deposit_amount'] ?? '0.00') ?>" 
                                            class="block w-full pl-7 pr-12 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="0.00">
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Payment Mode</label>
                                    <select name="deposit_mode" class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white">
                                        <option value="collection" <?= ($settings['deposit_payment_mode'] ?? 'collection') === 'collection' ? 'selected' : '' ?>>Pay on collection</option>
                                        <option value="online" <?= ($settings['deposit_payment_mode'] ?? 'collection') === 'online' ? 'selected' : '' ?>>Add to online payment</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="pt-4 border-t border-gray-100 italic text-xs text-gray-500">
                            * If "Add to payment" is selected, the deposit will be added to the total amount paid during checkout.
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="px-6 py-2.5 bg-black text-white rounded-lg hover:bg-gray-800 font-medium transition-colors">
                                Save Deposit Settings
                            </button>
                        </div>
                    </form>

                    <!-- Pay in Store -->
                    <div class="flex items-start justify-between p-4 border border-gray-200 rounded-lg">
                        <div class="flex items-start space-x-4">
                            <svg class="w-8 h-8 text-gray-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 mb-1">Pay in store</h3>
                                <p class="text-sm text-gray-600">Customers book online and pay in Store upon pickup</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" checked class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                        </label>
                    </div>

                    <!-- Stripe Settings -->
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Stripe settings</h2>
                        <?php if (!empty($settings['stripe_publishable_key']) && !empty($settings['stripe_secret_key'])): ?>
                            <div class="flex items-center gap-3 p-4 bg-green-50 border border-green-200 rounded-xl mb-6">
                                <div class="w-10 h-10 bg-green-100 text-green-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-green-900 uppercase tracking-tight">Stripe Integration is Active</p>
                                    <p class="text-xs text-green-700">Online payments and secure card storage are enabled.</p>
                                </div>
                            </div>
                            <button onclick="window.location.href='/dashboard/dealership.php?tab=payments'" class="px-6 py-2.5 bg-black text-white rounded-lg hover:bg-gray-800 font-medium flex items-center space-x-2 transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                <span>Manage Stripe Configuration</span>
                            </button>
                        <?php else: ?>
                            <p class="text-sm text-gray-600 mb-6">To be able to accept payments online or store customer's card on file, you must first setup Stripe Connect</p>
                            <button onclick="window.location.href='/dashboard/dealership.php?tab=payments'" class="px-6 py-2.5 bg-black text-white rounded-lg hover:bg-gray-800 font-medium flex items-center space-x-2 transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <span>Set up Stripe Connect</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>


            </div>
        </main>
    </div>
    
    <!-- Support Chat Button -->
    <button class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 w-12 h-12 sm:w-14 sm:h-14 bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-lg flex items-center justify-center transition z-30">
        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
        </svg>
    </button>

    <script>
        // Distance unit toggle
        function setDistanceUnit(unit, button) {
            // Update hidden input
            document.getElementById('distance_unit_input').value = unit;
            
            // Update active state classes
            const container = button.parentElement;
            const buttons = container.querySelectorAll('button');
            
            buttons.forEach(btn => {
                if (btn.innerText.trim() === unit) {
                    btn.className = 'px-4 py-2 text-sm font-medium transition-colors bg-gray-100 text-gray-900 border-' + (unit === 'Miles' ? 'l' : 'r') + ' border-gray-300';
                } else {
                    btn.className = 'px-4 py-2 text-sm font-medium transition-colors text-gray-500 hover:text-gray-700';
                }
            });
        }
        
        // Notice unit toggle
        function setNoticeUnit(unit) {
            document.getElementById('notice_unit').value = unit;
        }
    </script>
    <?php include __DIR__ . '/../includes/onboarding-widget.php'; ?>
</body>
</html>
