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

// Redirect customers to their dashboard
if ($_SESSION['role'] === 'customer') {
    redirect('/dashboard/customer.php');
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

// Get tenant settings
$stmt = $pdo->prepare("SELECT * FROM tenant_settings WHERE tenant_id = ?");
$stmt->execute([$_SESSION['tenant_id']]);
$settings = $stmt->fetch();

// Calculate stats for initial automatic onboarding check
$stmt = $pdo->prepare("SELECT COUNT(*) FROM vehicles WHERE tenant_id = ?");
$stmt->execute([$_SESSION['tenant_id']]);
$total_vehicles = (int)$stmt->fetchColumn();

// Check if Stripe is configured
$has_stripe = !empty($settings['stripe_publishable_key']) || !empty($settings['stripe_test_publishable_key']);

// Check if plan is chosen (not trial)
$has_plan = isset($tenant['plan']) && $tenant['plan'] !== 'trial';

// Has custom template or logo?
$has_website = !empty($tenant['website_template']) || !empty($tenant['logo']);

// Booking Stats for dashboard cards
$available_count = 0;
$rented_count = 0;
$maintenance_count = 0;
$next_24h_booked = 0;
$next_7d_booked = 0;

try {
    $now = date('Y-m-d H:i:s');
    $plus_24h = date('Y-m-d H:i:s', strtotime('+24 hours'));
    $plus_7d = date('Y-m-d H:i:s', strtotime('+7 days'));

    // Available vehicles
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM vehicles WHERE tenant_id = ? AND availability = 1");
    $stmt->execute([$_SESSION['tenant_id']]);
    $available_count = (int)$stmt->fetchColumn();

    // Rented = vehicles currently booked (active or confirmed) that are unavailable
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT b.vehicle_id) FROM bookings b
        JOIN vehicles v ON b.vehicle_id = v.id
        WHERE b.tenant_id = ? AND b.status IN ('confirmed', 'active')
        AND b.pickup_date <= ? AND b.return_date >= ?
        AND v.availability = 0
    ");
    $stmt->execute([$_SESSION['tenant_id'], $now, $now]);
    $rented_count = (int)$stmt->fetchColumn();

    // Maintenance = unavailable vehicles not currently rented
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM vehicles v
        WHERE v.tenant_id = ? AND v.availability = 0
        AND v.id NOT IN (
            SELECT DISTINCT b.vehicle_id FROM bookings b
            WHERE b.tenant_id = ? AND b.status IN ('confirmed', 'active')
            AND b.pickup_date <= ? AND b.return_date >= ?
        )
    ");
    $stmt->execute([$_SESSION['tenant_id'], $_SESSION['tenant_id'], $now, $now]);
    $maintenance_count = (int)$stmt->fetchColumn();

    // Next 24h booked
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE tenant_id = ? AND status IN ('confirmed', 'active') AND pickup_date BETWEEN ? AND ?");
    $stmt->execute([$_SESSION['tenant_id'], $now, $plus_24h]);
    $next_24h_booked = (int)$stmt->fetchColumn();

    // Next 7d booked
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE tenant_id = ? AND status IN ('confirmed', 'active') AND pickup_date BETWEEN ? AND ?");
    $stmt->execute([$_SESSION['tenant_id'], $now, $plus_7d]);
    $next_7d_booked = (int)$stmt->fetchColumn();
}
catch (PDOException $e) {
    // Tables may not exist yet - use defaults
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Getting Started - <?= htmlspecialchars($tenant['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="/app/custom.css">
    <style>
        .sidebar-item {
            transition: all 0.2s;
        }

        .sidebar-item:hover {
            background-color: #f3f4f6;
        }

        .sidebar-item.active {
            background-color: #eff6ff;
            color: #3b82f6;
        }

        .sidebar-item.active svg {
            color: #3b82f6;
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
                        <span class="text-gray-900">Getting Started</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-gray-900">Getting Started</h1>
                    <p class="text-sm text-gray-600 mt-1">Complete these steps to set up and launch your car rental business.</p>
                </div>
            </div>
        </header>

        <!-- Onboarding Panel (Alpine.js) -->
        <main class="flex-1 overflow-y-auto bg-white" x-data="gettingStarted">
            <div class="grid grid-cols-1 lg:grid-cols-12 h-full min-h-[600px]">
                
                <!-- Left Menu (Checklist) -->
                <div x-show="!allCompleted()" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="lg:col-span-4 border-r border-gray-200 p-6 sm:p-8 bg-gray-50/50">
                    <h2 class="text-lg font-bold text-gray-900 mb-6 uppercase tracking-wider text-xs text-gray-400">Onboarding Checklist</h2>
                    
                    <div class="space-y-3">
                        <!-- Step 1: Create your account -->
                        <div class="flex items-center justify-between p-3.5 rounded-xl transition duration-150 bg-white shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-3">
                                <span class="text-green-500 bg-green-50 p-1.5 rounded-full">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </span>
                                <span class="font-medium text-sm text-gray-400 line-through">Create your account</span>
                            </div>
                            <span class="text-xs text-green-600 font-semibold bg-green-100/50 px-2 py-0.5 rounded">Completed</span>
                        </div>

                        <!-- Step 2: Set up operating hours -->
                        <button @click="activeStep = 'operating_hours'"
                            :class="activeStep === 'operating_hours' ? 'bg-white shadow-md border-blue-200 ring-1 ring-blue-100' : 'bg-transparent border-transparent hover:bg-gray-100/70'"
                            class="w-full flex items-center justify-between p-3.5 rounded-xl border transition text-left group">
                            <div class="flex items-center space-x-3">
                                <template x-if="completedSteps.operating_hours">
                                    <span class="text-green-500 bg-green-50 p-1.5 rounded-full">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </span>
                                </template>
                                <template x-if="!completedSteps.operating_hours">
                                    <span :class="activeStep === 'operating_hours' ? 'border-blue-500 text-blue-600 bg-blue-50' : 'border-gray-300 text-gray-400'"
                                        class="w-8 h-8 rounded-full border-2 flex items-center justify-center text-xs font-bold transition flex-shrink-0">
                                        2
                                    </span>
                                </template>
                                <span :class="completedSteps.operating_hours ? 'text-gray-400 line-through' : 'text-gray-700 font-semibold'" class="text-sm transition-colors">Set up operating hours</span>
                            </div>
                        </button>

                        <!-- Step 3: Add a listing -->
                        <button @click="activeStep = 'add_listing'"
                            :class="activeStep === 'add_listing' ? 'bg-white shadow-md border-blue-200 ring-1 ring-blue-100' : 'bg-transparent border-transparent hover:bg-gray-100/70'"
                            class="w-full flex items-center justify-between p-3.5 rounded-xl border transition text-left group">
                            <div class="flex items-center space-x-3">
                                <template x-if="completedSteps.add_listing">
                                    <span class="text-green-500 bg-green-50 p-1.5 rounded-full">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </span>
                                </template>
                                <template x-if="!completedSteps.add_listing">
                                    <span :class="activeStep === 'add_listing' ? 'border-blue-500 text-blue-600 bg-blue-50' : 'border-gray-300 text-gray-400'"
                                        class="w-8 h-8 rounded-full border-2 flex items-center justify-center text-xs font-bold transition flex-shrink-0">
                                        3
                                    </span>
                                </template>
                                <span :class="completedSteps.add_listing ? 'text-gray-400 line-through' : 'text-gray-700 font-semibold'" class="text-sm transition-colors">Add a listing</span>
                            </div>
                        </button>

                        <!-- Step 4: Set up payment methods -->
                        <button @click="activeStep = 'payment_methods'"
                            :class="activeStep === 'payment_methods' ? 'bg-white shadow-md border-blue-200 ring-1 ring-blue-100' : 'bg-transparent border-transparent hover:bg-gray-100/70'"
                            class="w-full flex items-center justify-between p-3.5 rounded-xl border transition text-left group">
                            <div class="flex items-center space-x-3">
                                <template x-if="completedSteps.payment_methods">
                                    <span class="text-green-500 bg-green-50 p-1.5 rounded-full">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </span>
                                </template>
                                <template x-if="!completedSteps.payment_methods">
                                    <span :class="activeStep === 'payment_methods' ? 'border-blue-500 text-blue-600 bg-blue-50' : 'border-gray-300 text-gray-400'"
                                        class="w-8 h-8 rounded-full border-2 flex items-center justify-center text-xs font-bold transition flex-shrink-0">
                                        4
                                    </span>
                                </template>
                                <span :class="completedSteps.payment_methods ? 'text-gray-400 line-through' : 'text-gray-700 font-semibold'" class="text-sm transition-colors">Set up payment methods</span>
                            </div>
                        </button>

                        <!-- Step 5: Setup website -->
                        <button @click="activeStep = 'setup_website'"
                            :class="activeStep === 'setup_website' ? 'bg-white shadow-md border-blue-200 ring-1 ring-blue-100' : 'bg-transparent border-transparent hover:bg-gray-100/70'"
                            class="w-full flex items-center justify-between p-3.5 rounded-xl border transition text-left group">
                            <div class="flex items-center space-x-3">
                                <template x-if="completedSteps.setup_website">
                                    <span class="text-green-500 bg-green-50 p-1.5 rounded-full">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </span>
                                </template>
                                <template x-if="!completedSteps.setup_website">
                                    <span :class="activeStep === 'setup_website' ? 'border-blue-500 text-blue-600 bg-blue-50' : 'border-gray-300 text-gray-400'"
                                        class="w-8 h-8 rounded-full border-2 flex items-center justify-center text-xs font-bold transition flex-shrink-0">
                                        5
                                    </span>
                                </template>
                                <span :class="completedSteps.setup_website ? 'text-gray-400 line-through' : 'text-gray-700 font-semibold'" class="text-sm transition-colors">Setup website</span>
                            </div>
                        </button>

                        <!-- Step 6: Choose a plan -->
                        <button @click="activeStep = 'choose_plan'"
                            :class="activeStep === 'choose_plan' ? 'bg-white shadow-md border-blue-200 ring-1 ring-blue-100' : 'bg-transparent border-transparent hover:bg-gray-100/70'"
                            class="w-full flex items-center justify-between p-3.5 rounded-xl border transition text-left group">
                            <div class="flex items-center space-x-3">
                                <template x-if="completedSteps.choose_plan">
                                    <span class="text-green-500 bg-green-50 p-1.5 rounded-full">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </span>
                                </template>
                                <template x-if="!completedSteps.choose_plan">
                                    <span :class="activeStep === 'choose_plan' ? 'border-blue-500 text-blue-600 bg-blue-50' : 'border-gray-300 text-gray-400'"
                                        class="w-8 h-8 rounded-full border-2 flex items-center justify-center text-xs font-bold transition flex-shrink-0">
                                        6
                                    </span>
                                </template>
                                <span :class="completedSteps.choose_plan ? 'text-gray-400 line-through' : 'text-gray-700 font-semibold'" class="text-sm transition-colors">Choose a plan</span>
                            </div>
                        </button>
                    </div>

                    <!-- Visual Progress Bar -->
                    <div class="mt-8 bg-white p-4 rounded-xl border border-gray-150 shadow-sm">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Setup Progress</span>
                            <span class="text-xs font-bold text-blue-600" x-text="getProgressPct() + '%'"></span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" :style="'width: ' + getProgressPct() + '%'"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2" x-text="getProgressMessage()"></p>
                    </div>
                </div>

                <!-- Right Pane (Details & Interactive Mockup) -->
                <div :class="allCompleted() ? 'lg:col-span-12' : 'lg:col-span-8'"
                    class="p-6 sm:p-8 lg:p-12 flex flex-col justify-between overflow-y-auto bg-white transition-all duration-300">
                    
                    <!-- Content Block -->
                    <div class="my-auto">
                        <!-- Congrats banner when all done -->
                        <div x-show="allCompleted()" class="text-center py-8 mb-8" x-transition>
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <h1 class="text-3xl font-bold text-gray-900 mb-2">You're all set!</h1>
                            <p class="text-gray-600 text-base max-w-md mx-auto">Your onboarding is complete. Use the sidebar to manage your fleet, bookings, and settings.</p>
                        </div>

                        <!-- Active Step: Operating Hours -->
                        <div x-show="activeStep === 'operating_hours'" class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                            <div class="md:col-span-6 space-y-6">
                                <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Set up <span class="border-b-2 border-blue-500 pb-1">operating</span> hours</h1>
                                <p class="text-gray-600 leading-relaxed text-base">
                                    Great! The most important aspect of a rental business is setting up your rental periods. Fleetwire gives you tons of options that you can customize to fit the needs of your business.
                                </p>
                                <div class="flex flex-wrap gap-3 pt-2">
                                    <a href="/dashboard/settings.php?tab=booking" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm transition shadow-md hover:shadow-lg text-center uppercase tracking-wide">
                                        Set Up
                                    </a>
                                    <button @click="toggleComplete('operating_hours')" class="px-6 py-3 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-xl font-bold text-sm transition text-center uppercase tracking-wide shadow-sm" x-text="completedSteps.operating_hours ? 'Mark as Incomplete' : 'Mark as Complete'">
                                    </button>
                                </div>
                            </div>
                            <div class="md:col-span-6">
                                <!-- Mockup Screen -->
                                <div class="bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden text-xs max-w-sm mx-auto">
                                    <div class="bg-gray-50 border-b border-gray-200 px-4 py-2.5 flex items-center justify-between font-bold text-gray-700">
                                        <span class="text-blue-600 font-extrabold tracking-wide">FLEETRENTAL.PRO</span>
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                                    </div>
                                    <div class="p-4 space-y-3.5">
                                        <p class="text-gray-400 text-[10px]">Customize the hours of operation for your business. <span class="text-blue-500 font-semibold cursor-pointer">LEARN MORE ↗</span></p>
                                        <div class="border border-gray-200 rounded-lg p-2.5 bg-gray-50">
                                            <p class="text-[9px] text-gray-500 font-medium mb-1">Default Pickup Hours</p>
                                            <div class="bg-white border border-gray-300 rounded px-2 py-1 flex items-center justify-between text-gray-800 font-semibold">
                                                <span>8:00 AM</span>
                                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <input type="checkbox" checked disabled class="rounded text-blue-600 focus:ring-blue-500 h-3.5 w-3.5">
                                            <span class="font-semibold text-gray-700 text-[11px]">Enable Operating Hours</span>
                                        </div>
                                        <p class="text-[9px] text-gray-400 -mt-2">Only allow pickups and returns within your hours of operation.</p>
                                        <div class="border border-gray-150 rounded-lg overflow-hidden bg-white">
                                            <div class="bg-gray-100/75 px-3 py-1.5 font-bold text-gray-700 text-[10px]">Business Hours</div>
                                            <div class="p-2.5 flex justify-between items-center border-b border-gray-100">
                                                <span class="font-medium text-gray-700">Everyday</span>
                                                <span class="text-gray-600 font-semibold">08:00:00 – 17:00:00</span>
                                                <svg class="w-3.5 h-3.5 text-red-400 cursor-pointer hover:text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </div>
                                            <div class="p-1.5 bg-gray-50 text-center text-[10px] text-blue-600 font-bold cursor-pointer hover:bg-gray-100 transition">
                                                + ADD HOURS
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Active Step: Add Listing -->
                        <div x-show="activeStep === 'add_listing'" class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                            <div class="md:col-span-6 space-y-6">
                                <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Add a <span class="border-b-2 border-blue-500 pb-1">vehicle</span> listing</h1>
                                <p class="text-gray-600 leading-relaxed text-base">
                                    List your vehicles with photos, descriptions, brand, and rental price. Your clients can search, filter, and book these vehicles directly from your website!
                                </p>
                                <div class="flex flex-wrap gap-3 pt-2">
                                    <a href="/dashboard/vehicles.php?action=add" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm transition shadow-md hover:shadow-lg text-center uppercase tracking-wide">
                                        Add Vehicle
                                    </a>
                                    <button @click="toggleComplete('add_listing')" class="px-6 py-3 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-xl font-bold text-sm transition text-center uppercase tracking-wide shadow-sm" x-text="completedSteps.add_listing ? 'Mark as Incomplete' : 'Mark as Complete'">
                                    </button>
                                </div>
                            </div>
                            <div class="md:col-span-6">
                                <!-- Mockup Screen -->
                                <div class="bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden text-xs max-w-sm mx-auto">
                                    <div class="bg-blue-600 text-white p-4">
                                        <p class="text-[10px] uppercase font-bold tracking-widest text-blue-200">Active Listing</p>
                                        <h3 class="text-lg font-bold">Tesla Model Y</h3>
                                    </div>
                                    <div class="p-4 space-y-4">
                                        <div class="h-32 bg-gray-100 rounded-xl overflow-hidden relative flex items-center justify-center border border-gray-200">
                                            <img src="https://images.unsplash.com/photo-1563720223185-11003d516935?auto=format&fit=crop&w=600&q=80" alt="Tesla" class="object-cover w-full h-full">
                                            <span class="absolute top-2 right-2 bg-green-500 text-white font-extrabold text-[9px] px-2 py-0.5 rounded-full uppercase tracking-wider shadow">Available</span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2 text-center text-[11px]">
                                            <div class="bg-gray-50 border border-gray-150 rounded-lg p-2 font-bold text-gray-700">
                                                <p class="text-[9px] text-gray-400 uppercase">Transmission</p>
                                                <p class="mt-0.5 text-gray-900">Automatic</p>
                                            </div>
                                            <div class="bg-gray-50 border border-gray-150 rounded-lg p-2 font-bold text-gray-700">
                                                <p class="text-[9px] text-gray-400 uppercase">Fuel Type</p>
                                                <p class="mt-0.5 text-gray-900">Electric</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between border-t border-gray-100 pt-3">
                                            <div>
                                                <p class="text-[9px] text-gray-400 font-bold uppercase">Daily Pricing</p>
                                                <p class="text-base font-extrabold text-blue-600">$120.00 / day</p>
                                            </div>
                                            <button disabled class="px-3.5 py-1.5 bg-blue-50 text-blue-600 rounded-lg font-bold hover:bg-blue-100 transition text-[10px]">Edit Vehicle</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Active Step: Setup Payment Methods -->
                        <div x-show="activeStep === 'payment_methods'" class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                            <div class="md:col-span-6 space-y-6">
                                <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Set up <span class="border-b-2 border-blue-500 pb-1">payment</span> methods</h1>
                                <p class="text-gray-600 leading-relaxed text-base">
                                    Accept credit cards, security deposits, and booking payments online. Connect with Stripe instantly to securely authorize and capture customer payments.
                                </p>
                                <div class="flex flex-wrap gap-3 pt-2">
                                    <a href="/dashboard/settings.php?tab=payments" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm transition shadow-md hover:shadow-lg text-center uppercase tracking-wide">
                                        Set Up
                                    </a>
                                    <button @click="toggleComplete('payment_methods')" class="px-6 py-3 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-xl font-bold text-sm transition text-center uppercase tracking-wide shadow-sm" x-text="completedSteps.payment_methods ? 'Mark as Incomplete' : 'Mark as Complete'">
                                    </button>
                                </div>
                            </div>
                            <div class="md:col-span-6">
                                <!-- Mockup Screen -->
                                <div class="bg-gradient-to-br from-indigo-900 to-slate-900 rounded-2xl p-6 text-white shadow-xl max-w-sm mx-auto relative overflow-hidden aspect-[1.58/1]">
                                    <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-indigo-500/20 rounded-full blur-2xl"></div>
                                    <div class="flex justify-between items-start mb-6">
                                        <div class="font-black text-sm tracking-wide">SECURE GATEWAY</div>
                                        <span class="bg-green-500 text-white text-[9px] px-2 py-0.5 font-bold uppercase rounded-md tracking-wider">STRIPE INTEGRATED</span>
                                    </div>
                                    <div class="space-y-4">
                                        <div class="flex space-x-1.5">
                                            <div class="w-8 h-5 bg-white/20 rounded"></div>
                                            <div class="w-2.5 h-2.5 bg-yellow-500 rounded-full my-auto"></div>
                                        </div>
                                        <div>
                                            <p class="text-[9px] text-indigo-200 tracking-widest uppercase">Card Number</p>
                                            <p class="font-mono text-base tracking-widest mt-0.5">••••  ••••  ••••  4242</p>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <p class="text-[8px] text-indigo-200 uppercase tracking-wider">Status</p>
                                                <p class="font-bold text-green-400 text-xs uppercase tracking-wide">Connected</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-[8px] text-indigo-200 uppercase tracking-wider">Mode</p>
                                                <p class="font-bold text-white text-xs uppercase">Live & Test Enabled</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Active Step: Setup Website -->
                        <div x-show="activeStep === 'setup_website'" class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                            <div class="md:col-span-6 space-y-6">
                                <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Set up your <span class="border-b-2 border-blue-500 pb-1">website</span></h1>
                                <p class="text-gray-600 leading-relaxed text-base">
                                    Configure your visual layout, logo, navigation links, hero sections, and choose your favorite template design to provide a premium reservation flow.
                                </p>
                                <div class="flex flex-wrap gap-3 pt-2">
                                    <a href="/dashboard/website-builder.php" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm transition shadow-md hover:shadow-lg text-center uppercase tracking-wide">
                                        Set Up
                                    </a>
                                    <button @click="toggleComplete('setup_website')" class="px-6 py-3 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-xl font-bold text-sm transition text-center uppercase tracking-wide shadow-sm" x-text="completedSteps.setup_website ? 'Mark as Incomplete' : 'Mark as Complete'">
                                    </button>
                                </div>
                            </div>
                            <div class="md:col-span-6">
                                <!-- Mockup Screen -->
                                <div class="bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden text-xs max-w-sm mx-auto">
                                    <div class="bg-gray-900 text-white px-4 py-2 flex justify-between items-center font-bold">
                                        <span class="text-xs">WEBSITE PREVIEW</span>
                                        <span class="bg-blue-500 text-white text-[9px] px-1.5 py-0.5 rounded font-extrabold">LIVE</span>
                                    </div>
                                    <div class="relative h-44 flex items-center justify-center bg-gray-100 overflow-hidden">
                                        <img src="https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=800&q=80" alt="Hero Background" class="absolute inset-0 object-cover w-full h-full brightness-50">
                                        <div class="relative text-center text-white p-4 space-y-2">
                                            <h4 class="text-sm font-extrabold uppercase tracking-wide">Premium Car Rental Fleet</h4>
                                            <p class="text-[9px] text-gray-300 max-w-[200px] mx-auto">Rent luxury and economy cars directly from our online listing.</p>
                                            <button disabled class="px-4 py-1.5 bg-blue-600 text-white text-[10px] font-bold rounded-lg uppercase shadow">Book Now</button>
                                        </div>
                                    </div>
                                    <div class="p-3 bg-gray-50 flex items-center justify-between border-t border-gray-150">
                                        <span class="text-gray-500 text-[10px] font-medium">Custom branding and theme active</span>
                                        <span class="text-blue-600 font-bold hover:underline cursor-pointer">Preview ↗</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Active Step: Choose a Plan -->
                        <div x-show="activeStep === 'choose_plan'" class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                            <div class="md:col-span-6 space-y-6">
                                <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Choose a <span class="border-b-2 border-blue-500 pb-1">plan</span></h1>
                                <p class="text-gray-600 leading-relaxed text-base">
                                    Activate your subscription to unlock unlimited vehicles, unlimited customized contracts, custom domains, visual templates, and live payment gates.
                                </p>
                                <div class="flex flex-wrap gap-3 pt-2">
                                    <a href="/dashboard/settings.php?tab=billing" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm transition shadow-md hover:shadow-lg text-center uppercase tracking-wide">
                                        Set Up
                                    </a>
                                    <button @click="toggleComplete('choose_plan')" class="px-6 py-3 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-xl font-bold text-sm transition text-center uppercase tracking-wide shadow-sm" x-text="completedSteps.choose_plan ? 'Mark as Incomplete' : 'Mark as Complete'">
                                    </button>
                                </div>
                            </div>
                            <div class="md:col-span-6">
                                <!-- Mockup Screen -->
                                <div class="bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden p-4 text-xs max-w-sm mx-auto space-y-4">
                                    <div class="text-center">
                                        <h3 class="text-sm font-extrabold text-blue-600 uppercase tracking-widest">Select Premium Plan</h3>
                                        <p class="text-[10px] text-gray-400 mt-1">Unlock all key services for your car rental business.</p>
                                    </div>
                                    <div class="border-2 border-blue-500 rounded-xl p-3.5 bg-blue-50/50 flex justify-between items-center relative">
                                        <div class="absolute -top-2.5 right-3 bg-blue-500 text-white text-[8px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">Most Popular</div>
                                        <div>
                                            <h4 class="font-extrabold text-gray-900 text-sm">Professional Plan</h4>
                                            <p class="text-[9px] text-gray-500 mt-1">✓ Unlimited fleet entries</p>
                                            <p class="text-[9px] text-gray-500">✓ Fully customized contracts</p>
                                            <p class="text-[9px] text-gray-500">✓ Secure online Stripe checkout</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xl font-black text-blue-600">$99.00</p>
                                            <p class="text-[9px] text-gray-400 uppercase font-medium">/ month</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Help Center Button -->
                    <div class="mt-12 pt-6 border-t border-gray-100 flex justify-start">
                        <a href="/documentation" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 border border-gray-200 rounded-full text-xs font-bold text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition uppercase tracking-wider bg-white shadow-sm">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                            Visit Help Center
                        </a>
                    </div>

                </div>

            </div>

            <!-- Fleet Overview (Separate Section below onboarding) -->
            <div class="p-6 sm:p-8 lg:px-12 lg:py-10 bg-gray-50 border-t border-gray-200">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Fleet Overview</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Available -->
                    <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Available</p>
                        <p class="text-4xl font-light text-green-600 mb-2"><?= number_format($available_count) ?></p>
                        <p class="text-[11px] text-gray-400">0 vs yesterday · 0 vs last week</p>
                    </div>
                    <!-- Rented -->
                    <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Rented</p>
                        <p class="text-4xl font-light text-blue-600 mb-2"><?= number_format($rented_count) ?></p>
                        <p class="text-[11px] text-gray-400">0 vs yesterday · 0 vs last week</p>
                    </div>
                    <!-- Maintenance -->
                    <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Maintenance</p>
                        <p class="text-4xl font-light text-orange-500 mb-2"><?= number_format($maintenance_count) ?></p>
                        <p class="text-[11px] text-gray-400">0 vs yesterday · 0 vs last week</p>
                    </div>
                </div>

                <!-- Upcoming Bookings Badges -->
                <div class="flex flex-wrap gap-2 mt-4">
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold border border-blue-100">
                        Next 24h booked: <?= number_format($next_24h_booked) ?>
                    </span>
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold border border-blue-100">
                        Next 7d booked: <?= number_format($next_7d_booked) ?>
                    </span>
                </div>
            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('gettingStarted', () => ({
                activeStep: 'operating_hours',
                completedSteps: {
                    create_account: true,
                    operating_hours: false,
                    add_listing: false,
                    payment_methods: false,
                    setup_website: false,
                    choose_plan: false
                },

                init() {
                    const saved = localStorage.getItem('fleetrental_onboarding_completed');
                    if (saved) {
                        try {
                            const parsed = JSON.parse(saved);
                            this.completedSteps = {
                                ...this.completedSteps,
                                ...parsed,
                                create_account: true
                            };
                        } catch (e) {}
                    }

                    // Set default active step as the first incomplete onboarding item
                    const steps = ['operating_hours', 'add_listing', 'payment_methods', 'setup_website', 'choose_plan'];
                    for (const s of steps) {
                        if (!this.completedSteps[s]) {
                            this.activeStep = s;
                            break;
                        }
                    }
                },

                toggleComplete(step) {
                    if (step === 'create_account') return;
                    this.completedSteps[step] = !this.completedSteps[step];
                    localStorage.setItem('fleetrental_onboarding_completed', JSON.stringify(this.completedSteps));
                },

                getProgressPct() {
                    const keys = ['create_account', 'operating_hours', 'add_listing', 'payment_methods', 'setup_website', 'choose_plan'];
                    const count = keys.reduce((sum, key) => sum + (this.completedSteps[key] ? 1 : 0), 0);
                    return Math.round((count / keys.length) * 100);
                },

                getProgressMessage() {
                    const pct = this.getProgressPct();
                    if (pct === 100) return "Hurrah! You are 100% ready to launch your car rental business.";
                    if (pct >= 60) return "You're doing fantastic! Just a couple of steps left to go live.";
                    return "Get started by customizing your account and settings to match your brand.";
                },

                allCompleted() {
                    const keys = ['create_account', 'operating_hours', 'add_listing', 'payment_methods', 'setup_website', 'choose_plan'];
                    return keys.every(key => this.completedSteps[key]);
                }
            }));
        });

        // Sidebar mobile navigation helpers
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const mobileBtn = document.getElementById('mobile-menu-btn');

            if (mobileBtn && sidebar && overlay) {
                mobileBtn.addEventListener('click', function () {
                    sidebar.classList.toggle('-translate-x-full');
                    overlay.classList.toggle('hidden');
                });

                overlay.addEventListener('click', function () {
                    sidebar.classList.add('-translate-x-full');
                    overlay.classList.add('hidden');
                });
            }
        });
    </script>
</body>

</html>
