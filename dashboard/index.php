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

// Fetch vehicles for dashboard preview
$dashboardVehicles = [];
try {
    $stmt = $pdo->prepare("SELECT id, brand, model, year, price_per_day, availability, images, transmission, fuel_type, mileage_limit FROM vehicles WHERE tenant_id = ? ORDER BY created_at DESC LIMIT 6");
    $stmt->execute([$_SESSION['tenant_id']]);
    $dashboardVehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Vehicles table may not exist yet
}

// ========== SCHEDULE BOARD LOGIC (from vehicles.php) ==========
$vehicle_search = trim($_GET['vehicle_search'] ?? '');

// Get all vehicles for this tenant
$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE tenant_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['tenant_id']]);
$vehicles = $stmt->fetchAll();

// Helpers for schedule view
if (!function_exists('minutes_from_time')) {
    function minutes_from_time(?string $time): ?int
    {
        if (!$time) {
            return null;
        }
        [$hour, $minute] = array_pad(explode(':', $time), 2, '00');
        return (int)$hour * 60 + (int)$minute;
    }
}

$selected_schedule_date = $_GET['schedule_date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_schedule_date)) {
    $selected_schedule_date = date('Y-m-d');
}

$schedule_view = $_GET['schedule_view'] ?? 'day';
if (!in_array($schedule_view, ['day', 'week', 'month'])) {
    $schedule_view = 'day';
}

if ($schedule_view === 'month') {
    $prevScheduleDate = date('Y-m-d', strtotime($selected_schedule_date . ' -1 month'));
    $nextScheduleDate = date('Y-m-d', strtotime($selected_schedule_date . ' +1 month'));
} elseif ($schedule_view === 'week') {
    $prevScheduleDate = date('Y-m-d', strtotime($selected_schedule_date . ' -7 days'));
    $nextScheduleDate = date('Y-m-d', strtotime($selected_schedule_date . ' +7 days'));
} else {
    $prevScheduleDate = date('Y-m-d', strtotime($selected_schedule_date . ' -1 day'));
    $nextScheduleDate = date('Y-m-d', strtotime($selected_schedule_date . ' +1 day'));
}

$timelineStartHour = 8;
$timelineEndHour = 18;
$timelineStartMinutes = $timelineStartHour * 60;
$timelineEndMinutes = $timelineEndHour * 60;
$totalTimelineMinutes = max(60, $timelineEndMinutes - $timelineStartMinutes);
$hourColumns = max(1, $timelineEndHour - $timelineStartHour);

if ($schedule_view === 'week') {
    $start_date_ts = strtotime($selected_schedule_date);
    $monday_ts = strtotime('monday this week', $start_date_ts);
    $query_start_date = date('Y-m-d', $monday_ts);
    $query_end_date = date('Y-m-d', strtotime('+6 days', $monday_ts));
    $week_days = [];
    for ($d = 0; $d < 7; $d++) {
        $week_days[] = date('Y-m-d', strtotime("+$d days", $monday_ts));
    }
} elseif ($schedule_view === 'month') {
    $month_start_date = date('Y-m-01', strtotime($selected_schedule_date));
    $days_in_month = date('t', strtotime($selected_schedule_date));
    $query_start_date = $month_start_date;
    $query_end_date = date('Y-m-' . $days_in_month, strtotime($selected_schedule_date));
    $month_days = [];
    for ($d = 0; $d < $days_in_month; $d++) {
        $month_days[] = date('Y-m-d', strtotime("+$d days", strtotime($month_start_date)));
    }
    $month_today = date('Y-m-d');
    $month_dow_labels = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
} else {
    $query_start_date = $selected_schedule_date;
    $query_end_date = $selected_schedule_date;
}

$assignmentStatusColors = [
    'pending' => 'bg-blue-100 text-blue-900 border-blue-200',
    'confirmed' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
    'active' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
    'completed' => 'bg-gray-100 text-gray-700 border-gray-200',
];

$assignmentStmt = $pdo->prepare("SELECT b.*, v.name AS vehicle_name, v.brand, v.model, v.category, v.images, v.license_plate
    FROM bookings b
    LEFT JOIN vehicles v ON b.vehicle_id = v.id
    WHERE b.tenant_id = ? AND b.status != 'cancelled' AND b.pickup_date <= ? AND b.return_date >= ?");
$assignmentStmt->execute([$_SESSION['tenant_id'], $query_end_date, $query_start_date]);
$vehicleAssignments = $assignmentStmt->fetchAll(PDO::FETCH_ASSOC);

$assignmentsByVehicle = [];
foreach ($vehicleAssignments as $assignment) {
    if (!isset($assignmentsByVehicle[$assignment['vehicle_id']])) {
        $assignmentsByVehicle[$assignment['vehicle_id']] = [];
    }
    $assignmentsByVehicle[$assignment['vehicle_id']][] = $assignment;
}

$vehicleAvatarPalette = [
    'bg-rose-100 text-rose-700',
    'bg-sky-100 text-sky-600',
    'bg-amber-100 text-amber-700',
    'bg-emerald-100 text-emerald-700',
    'bg-indigo-100 text-indigo-700',
    'bg-purple-100 text-purple-700',
    'bg-cyan-100 text-cyan-700',
    'bg-lime-100 text-lime-700',
];

if (!empty($vehicle_search)) {
    $filteredVehicles = array_values(array_filter($vehicles, function ($vehicle) use ($vehicle_search) {
        $haystack = strtolower(
            ($vehicle['brand'] ?? '') . ' ' .
            ($vehicle['model'] ?? '') . ' ' .
            ($vehicle['license_plate'] ?? '') . ' ' .
            ($vehicle['category'] ?? '')
        );
        return strpos($haystack, strtolower($vehicle_search)) !== false;
    }));
} else {
    $filteredVehicles = $vehicles;
}

$filteredVehicleCount = count($filteredVehicles);
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

        /* Schedule Board Styles (from vehicles.php) */
        .cal-month-header-cell {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 6px 0;
            border-left: 1px solid #f3f4f6;
            min-width: 0;
        }
        .cal-month-header-cell.weekend { background-color: #fafafa; }
        .cal-month-header-cell.today { background-color: #eff6ff; }
        .cal-month-header-cell .dow-label {
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #9ca3af;
            margin-bottom: 2px;
        }
        .cal-month-header-cell .day-num {
            font-size: 13px;
            font-weight: 700;
            color: #374151;
            line-height: 1;
        }
        .cal-month-header-cell.today .day-num { color: #2563eb; }
        .cal-month-header-cell.today .today-dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background-color: #2563eb;
            margin-top: 3px;
        }
        .cal-month-col {
            border-left: 1px solid #f3f4f6;
            min-height: 80px;
        }
        .cal-month-col.weekend { background-color: #fafafa; }
        .cal-month-col.today { background-color: #eff6ff; }
        .cal-month-col.week-start { border-left-color: #d1d5db; }
        .flatpickr-calendar:not(.inline) {
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.12);
            border: 1px solid #e5e7eb;
            margin-top: 8px;
        }
        .flatpickr-calendar:not(.inline) .flatpickr-months { padding: 8px 0; }
        .flatpickr-calendar:not(.inline) .flatpickr-current-month {
            font-size: 16px;
            font-weight: 600;
        }
        .flatpickr-calendar:not(.inline) .flatpickr-weekday {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            color: #9ca3af;
        }
        .flatpickr-calendar:not(.inline) .flatpickr-day {
            border-radius: 8px;
            font-size: 13px;
            color: #374151;
            height: 36px;
            width: 36px;
            line-height: 36px;
        }
        .flatpickr-calendar:not(.inline) .flatpickr-day:hover { background: #f3f4f6; }
        .flatpickr-calendar:not(.inline) .flatpickr-day.selected {
            background: transparent;
            border: 2px solid #1f2937;
            color: #1f2937;
            font-weight: 700;
        }
        .flatpickr-calendar:not(.inline) .flatpickr-day.today {
            background: #f3f4f6;
            color: #1f2937;
            font-weight: 600;
            border: none;
        }
        .flatpickr-calendar:not(.inline) .flatpickr-day.prevMonthDay,
        .flatpickr-calendar:not(.inline) .flatpickr-day.nextMonthDay { color: #d1d5db; }

        /* Page Preloader */
        #pagePreloader {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: opacity 0.35s ease, visibility 0.35s ease;
        }
        #pagePreloader.hidden-loader {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }
        #pagePreloader .preloader-logo {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        #pagePreloader .preloader-logo svg {
            width: 28px;
            height: 28px;
            color: white;
        }
        #pagePreloader .preloader-bar {
            width: 160px;
            height: 3px;
            background: #f3f4f6;
            border-radius: 999px;
            overflow: hidden;
            position: relative;
        }
        #pagePreloader .preloader-bar::after {
            content: '';
            position: absolute;
            left: -40%;
            top: 0;
            height: 100%;
            width: 40%;
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
            border-radius: 999px;
            animation: preloaderSlide 1s ease-in-out infinite;
        }
        @keyframes preloaderSlide {
            0% { left: -40%; }
            100% { left: 100%; }
        }
        #pagePreloader .preloader-text {
            margin-top: 14px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: #9ca3af;
        }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>

<body class="bg-gray-50 flex h-screen overflow-hidden">
    <!-- Page Preloader -->
    <div id="pagePreloader">
        <div class="preloader-logo">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
        </div>
        <div class="preloader-bar"></div>
        <div class="preloader-text">Loading</div>
    </div>

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

            <!-- Vehicle Schedule Board -->
            <div id="schedule-board" class="p-6 sm:p-8 lg:px-12 lg:py-10 bg-gray-50 border-t border-gray-200">
                <div class="space-y-6">
                    <!-- Schedule Header -->
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="relative">
                                <input type="text" name="vehicle_search" value="<?= htmlspecialchars($vehicle_search) ?>" placeholder="Search vehicles" class="pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" onkeydown="if(event.key==='Enter'){ window.location='/dashboard/index.php?vehicle_search='+encodeURIComponent(this.value)+'#schedule-board'; }">
                                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"></path>
                                </svg>
                            </div>
                            <button class="flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 hover:border-gray-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L15 12.414V19a1 1 0 01-1.447.894l-4-2A1 1 0 019 17v-4.586L3.293 6.707A1 1 0 013 6V4z"></path>
                                </svg>
                                Filters
                            </button>
                            <div class="flex items-center gap-3 bg-white border border-gray-200 rounded-lg px-4 py-2">
                                <button onclick="window.location='/dashboard/index.php?schedule_view=<?= $schedule_view ?>&schedule_date=<?= $prevScheduleDate ?>&vehicle_search=<?= urlencode($vehicle_search) ?>#schedule-board'" class="p-1 text-gray-500 hover:text-gray-900">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </button>
                                <button id="scheduleDateBtn" class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                                    <?php if ($schedule_view === 'month'): ?>
                                        <?= date('F Y', strtotime($selected_schedule_date)) ?>
                                    <?php elseif ($schedule_view === 'week'): ?>
                                        <?= date('M j', strtotime($week_days[0])) ?> – <?= date('M j, Y', strtotime($week_days[6])) ?>
                                    <?php else: ?>
                                        <?= date('F j, Y', strtotime($selected_schedule_date)) ?>
                                    <?php endif; ?>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                                <input type="text" id="scheduleDateInput" value="<?= htmlspecialchars($selected_schedule_date) ?>" style="position:absolute;opacity:0;width:0;height:0;pointer-events:none;" tabindex="-1">
                                <button onclick="window.location='/dashboard/index.php?schedule_view=<?= $schedule_view ?>&schedule_date=<?= $nextScheduleDate ?>&vehicle_search=<?= urlencode($vehicle_search) ?>#schedule-board'" class="p-1 text-gray-500 hover:text-gray-900">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                                <div class="w-px h-5 bg-gray-200 mx-1"></div>
                                <button onclick="window.location='/dashboard/index.php?schedule_view=<?= $schedule_view ?>&schedule_date=<?= date('Y-m-d') ?>&vehicle_search=<?= urlencode($vehicle_search) ?>#schedule-board'" class="text-xs font-semibold text-blue-600 hover:text-blue-800 px-1">Today</button>
                            </div>
                            <div class="flex items-center gap-2 border border-gray-200 rounded-lg p-1 bg-white text-sm">
                                <a href="/dashboard/index.php?schedule_view=day&schedule_date=<?= $selected_schedule_date ?>&vehicle_search=<?= urlencode($vehicle_search) ?>#schedule-board" class="px-3 py-1 rounded-md <?= $schedule_view === 'day' ? 'bg-gray-100 text-gray-700 font-semibold' : 'text-gray-500 hover:text-gray-900' ?>">Day</a>
                                <a href="/dashboard/index.php?schedule_view=week&schedule_date=<?= $selected_schedule_date ?>&vehicle_search=<?= urlencode($vehicle_search) ?>#schedule-board" class="px-3 py-1 rounded-md <?= $schedule_view === 'week' ? 'bg-gray-100 text-gray-700 font-semibold' : 'text-gray-500 hover:text-gray-900' ?>">Week</a>
                                <a href="/dashboard/index.php?schedule_view=month&schedule_date=<?= $selected_schedule_date ?>&vehicle_search=<?= urlencode($vehicle_search) ?>#schedule-board" class="px-3 py-1 rounded-md <?= $schedule_view === 'month' ? 'bg-gray-100 text-gray-700 font-semibold' : 'text-gray-500 hover:text-gray-900' ?>">Month</a>
                            </div>
                        </div>
                    </div>

                    <!-- Schedule Board -->
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm <?= $schedule_view === 'month' ? 'overflow-x-auto' : 'overflow-hidden' ?>">
                        <div class="flex border-b border-gray-100 bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider <?= $schedule_view === 'month' ? 'min-w-max' : '' ?>">
                            <div class="w-72 px-6 py-3 flex-shrink-0 <?= $schedule_view === 'month' ? 'sticky left-0 z-20 bg-gray-50' : '' ?>" style="<?= $schedule_view === 'month' ? 'box-shadow: 2px 0 4px rgba(0,0,0,0.04);' : '' ?>">Vehicles (<?= $filteredVehicleCount ?>)</div>
                            <?php if ($schedule_view === 'week'): ?>
                            <div class="flex-1 grid gap-0 text-center" style="grid-template-columns: repeat(7, minmax(0, 1fr));">
                                <?php foreach ($week_days as $day): ?>
                                <div class="py-3"><?= date('D d/m', strtotime($day)) ?></div>
                                <?php endforeach; ?>
                            </div>
                            <?php elseif ($schedule_view === 'month'): ?>
                            <div class="grid" style="grid-template-columns: repeat(<?= $days_in_month ?>, 42px); min-width: <?= $days_in_month * 42 ?>px;">
                                <?php foreach ($month_days as $idx => $day):
                                    $dow = (int)date('w', strtotime($day));
                                    $dow = ($dow + 6) % 7;
                                    $isWeekend = ($dow >= 5);
                                    $isToday = ($day === $month_today);
                                    $isWeekStart = ($dow === 0);
                                    $cellClass = 'cal-month-header-cell' . ($isWeekend ? ' weekend' : '') . ($isToday ? ' today' : '') . ($isWeekStart ? ' week-start' : '');
                                ?>
                                <div class="<?= $cellClass ?>">
                                    <span class="dow-label"><?= $month_dow_labels[$dow] ?></span>
                                    <span class="day-num"><?= date('j', strtotime($day)) ?></span>
                                    <?php if ($isToday): ?><span class="today-dot"></span><?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="flex-1 grid gap-0 text-center" style="grid-template-columns: repeat(<?= $hourColumns ?>, minmax(0, 1fr));">
                                <?php for ($hour = $timelineStartHour; $hour < $timelineEndHour; $hour++): ?>
                                <div class="py-3"><?= sprintf('%02d:00', $hour) ?></div>
                                <?php endfor; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <?php if (empty($filteredVehicles)): ?>
                            <div class="p-12 text-center text-gray-500 text-sm">No vehicles match your filters.</div>
                            <?php else: ?>
                            <?php foreach ($filteredVehicles as $index => $vehicle):
                                $palette = $vehicleAvatarPalette[$index % count($vehicleAvatarPalette)];
                                $vehicleImage = null;
                                if (!empty($vehicle['images'])) {
                                    $decoded = json_decode($vehicle['images'], true);
                                    if (is_array($decoded) && !empty($decoded)) {
                                        $vehicleImage = $decoded[0];
                                    } elseif (!is_array($decoded)) {
                                        $vehicleImage = $vehicle['images'];
                                    }
                                }
                                $vehicleBookings = $assignmentsByVehicle[$vehicle['id']] ?? [];
                            ?>
                            <div class="flex <?= $schedule_view === 'month' ? 'min-w-max' : '' ?>">
                                <div class="w-72 px-4 py-3.5 flex items-center gap-3 border-r border-gray-100 hover:bg-gray-50 transition-colors flex-shrink-0 <?= $schedule_view === 'month' ? 'sticky left-0 z-10 bg-white' : '' ?>" style="<?= $schedule_view === 'month' ? 'box-shadow: 2px 0 4px rgba(0,0,0,0.04);' : '' ?>">
                                    <?php if ($vehicleImage): ?>
                                    <img src="<?= htmlspecialchars($vehicleImage) ?>" alt="<?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?>" class="w-12 h-12 rounded-xl object-cover border border-gray-200 shadow-sm flex-shrink-0">
                                    <?php else: ?>
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-sm font-semibold <?= $palette ?> shadow-sm flex-shrink-0">
                                        <?= strtoupper(substr($vehicle['brand'] ?? 'V', 0, 1)) ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <a href="/dashboard/vehicles.php?action=edit&id=<?= (int)$vehicle['id'] ?>" class="text-sm font-semibold text-gray-900 hover:text-blue-600 transition-colors truncate" title="<?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?>">
                                                <?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?>
                                            </a>
                                            <a href="/dashboard/vehicles.php?action=edit&id=<?= (int)$vehicle['id'] ?>" class="p-1.5 hover:bg-blue-50 rounded-lg transition-colors flex-shrink-0 group/edit" title="Edit vehicle">
                                                <svg class="w-4 h-4 text-gray-400 group-hover/edit:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                                </svg>
                                            </a>
                                        </div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-xs text-gray-500 truncate">
                                                <?= htmlspecialchars($vehicle['license_plate'] ?? 'No plate') ?>
                                            </span>
                                            <?php if (($vehicle['availability'] ?? 1) == 1): ?>
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-emerald-50 text-emerald-700">
                                                Active
                                            </span>
                                            <?php else: ?>
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-600">
                                                Inactive
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-1 relative border-l border-gray-100 <?= $schedule_view === 'month' ? 'min-w-max' : '' ?>">
                                    <?php if ($schedule_view === 'week'): ?>
                                    <div class="grid text-xs text-gray-300" style="grid-template-columns: repeat(7, minmax(0, 1fr));">
                                        <?php for ($d = 0; $d < 7; $d++): ?>
                                        <div class="border-l border-gray-100 min-h-[80px]"></div>
                                        <?php endfor; ?>
                                    </div>
                                    <?php elseif ($schedule_view === 'month'): ?>
                                    <div class="grid text-xs text-gray-300" style="grid-template-columns: repeat(<?= $days_in_month ?>, 42px); min-width: <?= $days_in_month * 42 ?>px;">
                                        <?php foreach ($month_days as $idx => $day):
                                            $dow = (int)date('w', strtotime($day));
                                            $dow = ($dow + 6) % 7;
                                            $isWeekend = ($dow >= 5);
                                            $isToday = ($day === $month_today);
                                            $isWeekStart = ($dow === 0);
                                            $cellClass = 'cal-month-col' . ($isWeekend ? ' weekend' : '') . ($isToday ? ' today' : '') . ($isWeekStart ? ' week-start' : '');
                                        ?>
                                        <div class="<?= $cellClass ?> min-h-[80px]"></div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php else: ?>
                                    <div class="grid text-xs text-gray-300" style="grid-template-columns: repeat(<?= $hourColumns ?>, minmax(0, 1fr));">
                                        <?php for ($hour = $timelineStartHour; $hour < $timelineEndHour; $hour++): ?>
                                        <div class="border-l border-gray-100 min-h-[80px]"></div>
                                        <?php endfor; ?>
                                    </div>
                                    <?php endif; ?>

                                    <?php foreach ($vehicleBookings as $booking):
                                        $statusClass = $assignmentStatusColors[$booking['status']] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                                        if ($schedule_view === 'day') {
                                            if ($booking['pickup_date'] < $selected_schedule_date) {
                                                $clampedStart = $timelineStartMinutes;
                                            } else {
                                                $clampedStart = max($timelineStartMinutes, minutes_from_time($booking['pickup_time']) ?? $timelineStartMinutes);
                                            }
                                            if ($booking['return_date'] > $selected_schedule_date) {
                                                $clampedEnd = $timelineEndMinutes;
                                            } else {
                                                $clampedEnd = min($timelineEndMinutes, minutes_from_time($booking['return_time']) ?? $timelineEndMinutes);
                                            }
                                            $offsetPercent = (($clampedStart - $timelineStartMinutes) / $totalTimelineMinutes) * 100;
                                            $widthPercent = (($clampedEnd - $clampedStart) / $totalTimelineMinutes) * 100;
                                        } elseif ($schedule_view === 'week') {
                                            $booking_start_ts = strtotime($booking['pickup_date'] . ' ' . ($booking['pickup_time'] ?? '00:00'));
                                            $booking_end_ts = strtotime($booking['return_date'] . ' ' . ($booking['return_time'] ?? '23:59'));
                                            $timeline_start_ts = $monday_ts;
                                            $timeline_end_ts = strtotime("+7 days", $monday_ts);
                                            $clampedStartTS = max($timeline_start_ts, $booking_start_ts);
                                            $clampedEndTS = min($timeline_end_ts, $booking_end_ts);
                                            $total_seconds = 7 * 24 * 3600;
                                            $offsetPercent = (($clampedStartTS - $timeline_start_ts) / $total_seconds) * 100;
                                            $widthPercent = (($clampedEndTS - $clampedStartTS) / $total_seconds) * 100;
                                        } elseif ($schedule_view === 'month') {
                                            $booking_start_ts = strtotime($booking['pickup_date'] . ' ' . ($booking['pickup_time'] ?? '00:00'));
                                            $booking_end_ts = strtotime($booking['return_date'] . ' ' . ($booking['return_time'] ?? '23:59'));
                                            $timeline_start_ts = strtotime($month_start_date);
                                            $timeline_end_ts = strtotime("+$days_in_month days", strtotime($month_start_date));
                                            $clampedStartTS = max($timeline_start_ts, $booking_start_ts);
                                            $clampedEndTS = min($timeline_end_ts, $booking_end_ts);
                                            $total_seconds = $days_in_month * 24 * 3600;
                                            $offsetPercent = (($clampedStartTS - $timeline_start_ts) / $total_seconds) * 100;
                                            $widthPercent = (($clampedEndTS - $clampedStartTS) / $total_seconds) * 100;
                                        }
                                    ?>
                                    <button type="button" onclick="openBookingModal(<?= (int)$booking['id'] ?>)" class="absolute top-3 h-14 rounded-xl border px-4 py-2 flex flex-col justify-center text-left text-xs font-medium shadow-sm <?= $statusClass ?> hover:shadow-md hover:-translate-y-0.5 transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-white/60" style="left: <?= $offsetPercent ?>%; width: <?= max($widthPercent, 4) ?>%; min-width: <?= $schedule_view === 'month' ? '30px' : ($schedule_view === 'week' ? '80px' : '120px') ?>;">
                                        <?php if ($schedule_view !== 'month'): ?>
                                        <div class="flex items-center gap-2">
                                            <span class="truncate"><?= htmlspecialchars($booking['customer_name'] ?? 'Guest') ?> </span>
                                            <span class="text-[9px] uppercase text-gray-400 truncate"><?= htmlspecialchars($booking['status']) ?></span>
                                        </div>
                                        <p class="text-[10px] text-gray-500 truncate">
                                            <?= date('M d', strtotime($booking['pickup_date'])) ?> -
                                            <?= date('M d', strtotime($booking['return_date'])) ?>
                                        </p>
                                        <?php else: ?>
                                        <div class="text-[9px] text-center font-bold" title="<?= htmlspecialchars($booking['customer_name'] ?? 'Guest') ?> (<?= htmlspecialchars($booking['status']) ?>)">
                                            <?= strtoupper(substr($booking['customer_name'] ?? 'G', 0, 2)) ?>
                                        </div>
                                        <?php endif; ?>
                                    </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Booking Details Modal -->
                <div id="bookingModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[70] hidden flex items-center justify-center p-4">
                    <div class="bg-white rounded-3xl w-full max-w-5xl max-h-[92vh] overflow-hidden shadow-[0_25px_50px_-12px_rgba(0,0,0,0.25)] flex flex-col relative" onclick="event.stopPropagation()">
                        <div id="bookingModalHeader" class="px-8 py-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white/95 backdrop-blur-xl z-10">
                            <div class="flex items-center gap-4 min-w-0">
                                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h3 id="bookingModalTitle" class="text-lg font-bold text-gray-900 truncate">Booking Details</h3>
                                    <p id="bookingModalSubtitle" class="text-sm text-gray-500 mt-0.5 flex items-center gap-2">Loading...</p>
                                </div>
                            </div>
                            <button onclick="closeBookingModal()" class="w-9 h-9 flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-xl transition-all flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div id="bookingModalContent" class="px-8 py-6 overflow-y-auto flex-1 bg-gray-50/50">
                            <!-- Content will be loaded here -->
                        </div>
                    </div>
                </div>

                <!-- Contract Preview Modal -->
                <div id="contractPreviewModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-[80] flex items-center justify-center p-4">
                    <div class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden">
                        <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-white text-gray-900">
                            <div>
                                <h3 class="text-xl font-black uppercase tracking-tighter">Contract Preview</h3>
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Agreement Details</p>
                            </div>
                            <button onclick="closeContractPreviewModal()" class="w-10 h-10 flex items-center justify-center text-gray-400 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-all">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div id="contractPreviewContent" class="p-8 overflow-y-auto bg-gray-50 flex-1">
                            <!-- Content will be injected here -->
                        </div>
                        <div class="p-6 border-t border-gray-100 bg-white flex justify-end">
                            <button onclick="closeContractPreviewModal()" class="px-8 py-3 bg-gray-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-black transition-all shadow-xl shadow-gray-200">Close Preview</button>
                        </div>
                    </div>
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

        // Schedule Board JS (from vehicles.php)
        function openBookingModal(bookingId) {
            window.location.href = '/dashboard/vehicles.php?view=booking&id=' + bookingId;
        }
        function closeBookingModal() {
            const modal = document.getElementById('bookingModal');
            if (modal) modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
        function closeContractPreviewModal() {
            const modal = document.getElementById('contractPreviewModal');
            if (modal) modal.classList.add('hidden');
            document.body.style.overflow = '';
        }

        // Flatpickr for schedule date
        const scheduleDateInput = document.getElementById('scheduleDateInput');
        const scheduleDateBtn = document.getElementById('scheduleDateBtn');
        if (scheduleDateInput && scheduleDateBtn && typeof flatpickr !== 'undefined') {
            const scheduleFp = flatpickr(scheduleDateInput, {
                dateFormat: "Y-m-d",
                defaultDate: scheduleDateInput.value,
                locale: { firstDayOfWeek: 1 },
                monthSelectorType: 'static',
                onChange: function(selectedDates, dateStr) {
                    if (dateStr) {
                        window.location = '/dashboard/index.php?schedule_view=<?= $schedule_view ?>&schedule_date=' + dateStr + '&vehicle_search=<?= urlencode($vehicle_search) ?>' + '#schedule-board';
                    }
                }
            });
            scheduleDateBtn.addEventListener('click', function(e) {
                e.preventDefault();
                scheduleFp.open();
            });
        }

        // Show preloader on schedule navigation
        document.querySelectorAll('#schedule-board a, #schedule-board button[onclick]').forEach(function(el) {
            var original = el.getAttribute('onclick');
            if (original && original.includes('window.location')) {
                el.setAttribute('onclick', original.replace('window.location=', 'showPreloader(); window.location='));
            }
        });

        // Show preloader helper
        function showPreloader() {
            var preloader = document.getElementById('pagePreloader');
            if (preloader) preloader.classList.remove('hidden-loader');
        }

        // Hide preloader on page load
        window.addEventListener('load', function() {
            setTimeout(function() {
                var preloader = document.getElementById('pagePreloader');
                if (preloader) preloader.classList.add('hidden-loader');
            }, 250);
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</body>

</html>
