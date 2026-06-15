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
    if ($trial_days_remaining > 30)
        $trial_days_remaining = 30;
    $trial_percentage = ($trial_days_remaining / 30) * 100;
}

// Get view and date parameters
$current_view = isset($_GET['view']) ? $_GET['view'] : 'month';
$current_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$current_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$current_day = isset($_GET['day']) ? intval($_GET['day']) : date('j');

// Validate parameters
if ($current_month < 1 || $current_month > 12) {
    $current_month = date('n');
}
if ($current_year < 2000 || $current_year > 2100) {
    $current_year = date('Y');
}
if (!in_array($current_view, ['month', 'week', 'day'])) {
    $current_view = 'month';
}

// Get statistics for this tenant
$stats = [];

// Total vehicles
$stmt = $pdo->prepare("SELECT COUNT(*) FROM vehicles WHERE tenant_id = ?");
$stmt->execute([$_SESSION['tenant_id']]);
$stats['total_vehicles'] = $stmt->fetchColumn();

// Initialize default values
$stats['total_bookings'] = 0;
$stats['active_bookings'] = 0;
$stats['total_customers'] = 0;
$all_bookings = [];
$recent_bookings = [];

// Try to get bookings data - table may not exist yet
try {
    // Total bookings
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE tenant_id = ?");
    $stmt->execute([$_SESSION['tenant_id']]);
    $stats['total_bookings'] = $stmt->fetchColumn();

    // Active bookings
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE tenant_id = ? AND status IN ('confirmed', 'active')");
    $stmt->execute([$_SESSION['tenant_id']]);
    $stats['active_bookings'] = $stmt->fetchColumn();

    // Get all bookings for calendar (exclude cancelled)
    $stmt = $pdo->prepare("
        SELECT b.*, v.brand, v.model, v.category
        FROM bookings b
        LEFT JOIN vehicles v ON b.vehicle_id = v.id
        WHERE b.tenant_id = ? AND b.status != 'cancelled'
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$_SESSION['tenant_id']]);
    $all_bookings = $stmt->fetchAll();

    // Get manually blocked vehicle dates
    $stmt = $pdo->prepare("SELECT id, name, unavailable_dates FROM vehicles WHERE tenant_id = ? AND unavailable_dates IS NOT NULL AND unavailable_dates != ''");
    $stmt->execute([$_SESSION['tenant_id']]);
    $blocked_vehicles = $stmt->fetchAll();

    $blocked_dates = [];
    foreach ($blocked_vehicles as $v) {
        if (!empty($v['unavailable_dates'])) {
            $dates = explode(', ', $v['unavailable_dates']);
            foreach ($dates as $d) {
                $d = trim($d);
                if (!empty($d)) {
                    if (!isset($blocked_dates[$d])) {
                        $blocked_dates[$d] = [];
                    }
                    $blocked_dates[$d][] = $v;
                }
            }
        }
    }

    // Get recent bookings for list
    $stmt = $pdo->prepare("
        SELECT b.*, v.brand, v.model, v.category
        FROM bookings b
        LEFT JOIN vehicles v ON b.vehicle_id = v.id
        WHERE b.tenant_id = ?
        ORDER BY b.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['tenant_id']]);
    $recent_bookings = $stmt->fetchAll();
}
catch (PDOException $e) {
// Bookings table doesn't exist yet or has schema issues - use defaults
}

// Total customers
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE tenant_id = ? AND role = 'customer'");
    $stmt->execute([$_SESSION['tenant_id']]);
    $stats['total_customers'] = $stmt->fetchColumn();
}
catch (PDOException $e) {
    // Users table may not have role column yet
    $stats['total_customers'] = 0;
}

// Get recent contract activity for the activity feed
$recent_activity = [];
try {
    $stmt = $pdo->prepare("
        SELECT c.signed_at, c.signature_typed, c.contract_status, c.booking_id,
               b.customer_name, b.customer_email
        FROM contracts c
        JOIN bookings b ON c.booking_id = b.id
        WHERE c.tenant_id = ? AND c.contract_status = 'signed'
        ORDER BY c.signed_at DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['tenant_id']]);
    $recent_activity = $stmt->fetchAll();
}
catch (PDOException $e) {
// Contracts table might not have contract_status column yet
}

// Onboarding checklist data
$ob_has_vehicle = $stats['total_vehicles'] > 0;
$ob_settings = $settings ?? [];
$ob_has_company = !empty($ob_settings['company_address']) || !empty($ob_settings['company_phone']) || !empty($ob_settings['pickup_location']);
$ob_has_contract = false;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM contract_templates WHERE tenant_id = ?");
    $stmt->execute([$_SESSION['tenant_id']]);
    $ob_has_contract = $stmt->fetchColumn() > 0;
}
catch (PDOException $e) {
}

// "View your vehicles" is always a shortcut — mark done once they have any vehicles to view
$ob_has_viewed = $ob_has_vehicle;

$ob_steps_done = array_sum([$ob_has_vehicle ? 1 : 0, $ob_has_company ? 1 : 0, $ob_has_contract ? 1 : 0]);
$ob_total_steps = 3; // "View vehicles" is a shortcut, not a task
$ob_pct = $ob_total_steps > 0 ? round(($ob_steps_done / $ob_total_steps) * 100) : 0;
$ob_first_name = explode(' ', trim($_SESSION['full_name'] ?? 'there'))[0];

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar -
        <?= htmlspecialchars($tenant['name'])?>
    </title>
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

        .calendar-cell {
            border-right: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
        }

        .calendar-cell:nth-child(7n) {
            border-right: none;
        }
    </style>
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>

<body class="bg-gray-50 flex h-screen overflow-hidden">
    <!-- Mobile Header -->
    <header
        class="lg:hidden fixed top-0 left-0 right-0 bg-white border-b border-gray-200 px-4 py-3 z-40 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <button id="mobile-menu-btn" class="p-1 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16">
                    </path>
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                    </path>
                </svg>
            </button>
            <button
                class="w-8 h-8 rounded-full bg-black text-white flex items-center justify-center text-xs font-semibold">
                FL
            </button>
        </div>
    </header>

    <!-- Sidebar Overlay -->
    <div id="sidebar-overlay"
        class="lg:hidden fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-30 hidden transition-all duration-300">
    </div>

    <!-- Sidebar -->
    <aside id="sidebar"
        class="fixed lg:static top-14 lg:top-0 bottom-0 left-0 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out z-40 lg:flex">
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
                        <span class="text-gray-900">Calendar</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-gray-900">Calendar</h1>
                    <p class="text-sm text-gray-600 mt-1">Manage your bookings and schedule</p>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto bg-gray-50 p-4 sm:p-6 lg:p-8">

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Total Vehicles -->
                <div
                    class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Vehicles</p>
                        <h3 class="text-3xl font-light text-gray-900">
                            <?= number_format($stats['total_vehicles'])?>
                        </h3>
                    </div>
                    <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 13l2 2m0 0l2 2m-2-2l2-2m-2 2l-2 2m11-3h1m1 0h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4M4 7h16M4 7a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V7z">
                            </path>
                        </svg>
                    </div>
                </div>

                <!-- Active Bookings -->
                <div class="bg-[#1a1f2b] p-6 rounded-2xl shadow-sm flex items-center justify-between text-white">
                    <div>
                        <p class="text-sm font-light text-gray-400 mb-1">Active Bookings</p>
                        <h3 class="text-3xl font-light">
                            <?= number_format($stats['active_bookings'])?>
                        </h3>
                    </div>
                    <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center text-blue-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                </div>

                <!-- Total Customers -->
                <div
                    class="bg-gray-100 p-6 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">Registered Customers</p>
                        <h3 class="text-3xl font-light text-gray-900">
                            <?= number_format($stats['total_customers'])?>
                        </h3>
                    </div>
                    <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-gray-600 shadow-sm">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Feed -->
            <?php if (!empty($recent_activity) && !isset($_COOKIE['hide_recent_activity'])): ?>
            <div id="recent-activity-section" class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Recent Activity
                    </h3>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-gray-400">Contract signings</span>
                        <button onclick="dismissRecentActivity()"
                            class="p-1 hover:bg-gray-100 rounded-lg transition-colors text-gray-400 hover:text-gray-600"
                            title="Close">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="divide-y divide-gray-50">
                    <?php foreach ($recent_activity as $activity): ?>
                    <div class="px-4 py-3 flex items-center gap-3 hover:bg-gray-50 transition-colors">
                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900">
                                <span class="font-semibold">
                                    <?= htmlspecialchars($activity['customer_name'])?>
                                </span>
                                signed contract for Booking <span class="font-semibold">#
                                    <?= str_pad($activity['booking_id'], 5, '0', STR_PAD_LEFT)?>
                                </span>
                            </p>
                        </div>
                        <span class="text-xs text-gray-400 flex-shrink-0">
                            <?php
        $signed = new DateTime($activity['signed_at']);
        $diff = $now->diff($signed);
        if ($diff->days == 0) {
            if ($diff->h == 0)
                echo $diff->i . 'm ago';
            else
                echo $diff->h . 'h ago';
        }
        elseif ($diff->days == 1) {
            echo 'Yesterday';
        }
        else {
            echo date('M j', strtotime($activity['signed_at']));
        }
?>
                        </span>
                    </div>
                    <?php
    endforeach; ?>
                </div>
            </div>
            <?php
endif; ?>

            <!-- Calendar -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <!-- Calendar Header -->
                <div class="p-4 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="flex items-center space-x-2 w-full sm:w-auto">
                        <button onclick="goToToday()"
                            class="px-4 py-1.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium">Today</button>
                        <div class="flex space-x-1">
                            <button onclick="navigateMonth(-1)"
                                class="w-8 h-8 flex items-center justify-center bg-gray-900 text-white rounded-lg hover:bg-gray-800">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                            <button onclick="navigateMonth(1)"
                                class="w-8 h-8 flex items-center justify-center bg-gray-900 text-white rounded-lg hover:bg-gray-800">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <?php
// Build header text based on view
if ($current_view === 'week') {
    $header_target = new DateTime("$current_year-$current_month-$current_day");
    $header_week_start = clone $header_target;
    // Explicit ISO week Monday calc: subtract (day_of_week - 1) days
    $dow = (int)$header_week_start->format('N'); // 1=Mon, 7=Sun
    $header_week_start->modify('-' . ($dow - 1) . ' days');
    $header_week_end = clone $header_week_start;
    $header_week_end->modify('+6 days');
    $header_text = $header_week_start->format('M j') . ' – ' . $header_week_end->format('M j, Y');
}
elseif ($current_view === 'day') {
    $header_text = date('l, F j, Y', mktime(0, 0, 0, $current_month, $current_day, $current_year));
}
else {
    $header_text = date('F Y', mktime(0, 0, 0, $current_month, 1, $current_year));
}
?>
                    <h2 id="currentMonthYear" class="text-lg font-semibold text-gray-900">
                        <?= $header_text?>
                    </h2>

                    <div class="flex bg-gray-100 rounded-lg p-1 w-full sm:w-auto">
                        <button id="monthViewBtn" onclick="setView('month')"
                            class="flex-1 sm:flex-none px-4 py-1.5 <?= $current_view === 'month' ? 'bg-white text-gray-900 rounded-md shadow-sm' : 'text-gray-600 hover:text-gray-900'?> text-sm font-medium">Month</button>
                        <button id="weekViewBtn" onclick="setView('week')"
                            class="flex-1 sm:flex-none px-4 py-1.5 <?= $current_view === 'week' ? 'bg-white text-gray-900 rounded-md shadow-sm' : 'text-gray-600 hover:text-gray-900'?> text-sm font-medium">Week</button>
                        <button id="dayViewBtn" onclick="setView('day')"
                            class="flex-1 sm:flex-none px-4 py-1.5 <?= $current_view === 'day' ? 'bg-white text-gray-900 rounded-md shadow-sm' : 'text-gray-600 hover:text-gray-900'?> text-sm font-medium">Day</button>
                    </div>
                </div>

                <!-- Calendar Grid -->
                <div class="border-t border-gray-200">
                    <?php
// Group bookings by date - needed for all views
$bookings_by_date = [];
foreach ($all_bookings as $booking) {
    $pickup = $booking['pickup_date'];
    $return = $booking['return_date'];

    // Add booking to all dates in range
    $start = new DateTime($pickup);
    $end = new DateTime($return);
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end->modify('+1 day'));

    foreach ($period as $date) {
        $date_str = $date->format('Y-m-d');
        if (!isset($bookings_by_date[$date_str])) {
            $bookings_by_date[$date_str] = [];
        }
        $bookings_by_date[$date_str][] = $booking;
    }
}
?>
                    <?php if ($current_view === 'month'): ?>
                    <div class="grid grid-cols-7 border-b border-gray-200">
                        <!-- Day Headers -->
                        <?php
    $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    foreach ($days as $day):
?>
                        <div
                            class="py-2 sm:py-3 text-center text-xs sm:text-sm font-medium text-gray-500 calendar-cell border-b-0">
                            <?= $day?>
                        </div>
                        <?php
    endforeach; ?>
                    </div>

                    <div class="grid grid-cols-7">
                        <!-- Calendar Days -->
                        <?php
    // Use the month and year from URL parameters

    // Get first day of month and number of days
    $first_day = new DateTime("$current_year-$current_month-01");
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);

    // Get day of week (1=Monday, 7=Sunday)
    $start_day = $first_day->format('N');

    // Calculate previous month days to show
    $prev_month = $current_month - 1;
    $prev_year = $current_year;
    if ($prev_month < 1) {
        $prev_month = 12;
        $prev_year--;
    }
    $prev_month_days = cal_days_in_month(CAL_GREGORIAN, $prev_month, $prev_year);

    // Build calendar array
    $calendar_cells = [];

    // Previous month days
    for ($i = $start_day - 1; $i > 0; $i--) {
        $day_num = $prev_month_days - $i + 1;
        $date = sprintf('%04d-%02d-%02d', $prev_year, $prev_month, $day_num);
        $calendar_cells[] = ['day' => $day_num, 'date' => $date, 'other_month' => true];
    }

    // Current month days
    for ($i = 1; $i <= $days_in_month; $i++) {
        $date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $i);
        $calendar_cells[] = ['day' => $i, 'date' => $date, 'other_month' => false];
    }

    // Next month days to fill grid
    $next_month = $current_month + 1;
    $next_year = $current_year;
    if ($next_month > 12) {
        $next_month = 1;
        $next_year++;
    }
    $remaining = 42 - count($calendar_cells);
    for ($i = 1; $i <= $remaining; $i++) {
        $date = sprintf('%04d-%02d-%02d', $next_year, $next_month, $i);
        $calendar_cells[] = ['day' => $i, 'date' => $date, 'other_month' => true];
    }

    // Render calendar cells
    $today = date('Y-m-d');
    for ($i = 0; $i < 42; $i++):
        $cell = $calendar_cells[$i];
        $is_today = $cell['date'] === $today;
        $cell_bookings = $bookings_by_date[$cell['date']] ?? [];
?>
                        <div onclick="openDateBookingsModal('<?= $cell['date']?>')"
                            class="bg-white p-1 sm:p-2 lg:p-3 min-h-[50px] sm:min-h-[80px] lg:min-h-[120px] <?= $cell['other_month'] ? 'text-gray-400 bg-gray-50' : 'text-gray-700'?> hover:bg-gray-100 cursor-pointer transition calendar-cell relative <?= $i >= 35 ? 'border-b-0' : ''?>">
                            <div class="h-6">
                                <div
                                    class="font-medium inline-flex <?= $is_today ? 'bg-blue-600 text-white w-5 h-5 sm:w-6 sm:h-6 rounded-full items-center justify-center text-xs' : 'text-xs sm:text-sm'?>">
                                    <?= $cell['day']?>
                                </div>
                            </div>

                            <!-- Booking Count Badge -->
                            <?php if (!empty($cell_bookings)): ?>
                            <div class="mt-2 text-center">
                                <span
                                    class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-blue-600 bg-blue-50 rounded-lg border border-blue-100 w-full mb-1">
                                    <?= count($cell_bookings)?> Booking<?= count($cell_bookings) > 1 ? 's' : ''?>
                                </span>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Blocked Count Badge -->
                            <?php if (!empty($blocked_dates[$cell['date']])): ?>
                            <div class="mt-1 text-center">
                                <span
                                    class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-gray-600 bg-gray-100 rounded-lg border border-gray-200 w-full">
                                    <?= count($blocked_dates[$cell['date']])?> Blocked
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php
    endfor; ?>
                    </div>
                    <?php
elseif ($current_view === 'week'): ?>
                    <!-- Week View -->
                    <div class="grid grid-cols-7 border-b border-gray-200">
                        <?php
    $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    foreach ($days as $day):
?>
                        <div
                            class="py-2 sm:py-3 text-center text-xs sm:text-sm font-medium text-gray-500 calendar-cell border-b-0">
                            <?= $day?>
                        </div>
                        <?php
    endforeach; ?>
                    </div>

                    <div class="grid grid-cols-7">
                        <?php
    // Calculate the week containing the current date
    $target_date = new DateTime("$current_year-$current_month-$current_day");
    $week_start = clone $target_date;
    // Explicit ISO week Monday calc: subtract (day_of_week - 1) days
    $dow = (int)$week_start->format('N'); // 1=Mon, 7=Sun
    $week_start->modify('-' . ($dow - 1) . ' days');

    // Build week cells array
    $week_cells = [];
    for ($i = 0; $i < 7; $i++) {
        $day_date = clone $week_start;
        $day_date->modify("+$i days");
        $week_cells[] = [
            'date' => $day_date->format('Y-m-d'),
            'day' => $day_date->format('j'),
            'is_today' => $day_date->format('Y-m-d') === date('Y-m-d')
        ];
    }

    // Render week cells
    for ($i = 0; $i < 7; $i++):
        $cell = $week_cells[$i];
        $cell_bookings = $bookings_by_date[$cell['date']] ?? [];
?>
                        <div onclick="openDateBookingsModal('<?= $cell['date']?>')"
                            class="bg-white p-2 sm:p-3 min-h-[150px] <?= $cell['is_today'] ? 'bg-blue-50' : ''?> hover:bg-gray-100 cursor-pointer transition calendar-cell relative">
                            <div class="h-6 mb-2">
                                <div
                                    class="font-medium inline-flex <?= $cell['is_today'] ? 'bg-blue-600 text-white w-6 h-6 rounded-full items-center justify-center text-sm' : 'text-sm'?>">
                                    <?= $cell['day']?>
                                </div>
                            </div>

                            <!-- Booking Count Badge -->
                            <?php if (!empty($cell_bookings)): ?>
                            <div class="mt-4 text-center">
                                <span
                                    class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-bold leading-none text-blue-600 bg-blue-100 rounded-lg border border-blue-100 mb-1">
                                    <?= count($cell_bookings)?> Booking<?= count($cell_bookings) > 1 ? 's' : ''?>
                                </span>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Blocked Count Badge -->
                            <?php if (!empty($blocked_dates[$cell['date']])): ?>
                            <div class="mt-1 text-center">
                                <span
                                    class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-bold leading-none text-gray-600 bg-gray-200 rounded-lg border border-gray-300 w-full">
                                    <?= count($blocked_dates[$cell['date']])?> Blocked
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php
    endfor; ?>
                    </div>
                    <?php
elseif ($current_view === 'day'): ?>
                    <!-- Day View -->
                    <?php
    $target_date = new DateTime("$current_year-$current_month-$current_day");
    $date_str = $target_date->format('Y-m-d');
    $is_today = $date_str === date('Y-m-d');
    $day_bookings = $bookings_by_date[$date_str] ?? [];
?>

                    <div class="p-6 bg-gray-50">
                        <div class="mb-6">
                            <h3 class="text-2xl font-bold text-gray-900">
                                <?= $target_date->format('l, F j, Y')?>
                            </h3>
                            <?php if ($is_today): ?>
                            <p class="text-sm text-blue-600 font-medium mt-1">Today</p>
                            <?php
    endif; ?>
                        </div>

                        <?php if (empty($day_bookings)): ?>
                        <div class="bg-white rounded-lg border border-gray-200 text-center py-12">
                            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                </path>
                            </svg>
                            <p class="text-gray-500 text-lg">No bookings for this day</p>
                        </div>
                        <?php
    else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($day_bookings as $booking):
            $is_pickup = date('Y-m-d', strtotime($booking['pickup_date'])) === $date_str;
            $is_return = date('Y-m-d', strtotime($booking['return_date'])) === $date_str;
?>
                            <div onclick="openBookingModal(<?= $booking['id']?>)"
                                class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition cursor-pointer">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-900 truncate">
                                            <?= htmlspecialchars($booking['customer_name'])?>
                                        </h4>
                                        <p class="text-xs text-gray-600 truncate">
                                            <?= htmlspecialchars($booking['customer_email'])?>
                                        </p>
                                    </div>
                                    <div class="flex flex-col gap-1 ml-2">
                                        <?php if ($is_pickup): ?>
                                        <span
                                            class="px-2 py-0.5 bg-green-100 text-green-800 text-xs font-semibold rounded">Pickup</span>
                                        <?php
            endif; ?>
                                        <?php if ($is_return): ?>
                                        <span
                                            class="px-2 py-0.5 bg-orange-100 text-orange-800 text-xs font-semibold rounded">Return</span>
                                        <?php
            endif; ?>
                                    </div>
                                </div>

                                <div class="bg-blue-50 rounded-lg p-3 mb-3">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-6 h-6 text-blue-600 flex-shrink-0" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                        </svg>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-gray-900 text-sm truncate">
                                                <?= htmlspecialchars($booking['brand'] . ' ' . $booking['model'])?>
                                            </p>
                                            <p class="text-xs text-gray-600 truncate">
                                                <?= htmlspecialchars($booking['category'])?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <p class="text-gray-500">Pickup</p>
                                        <p class="font-medium text-gray-900">
                                            <?= date('M j', strtotime($booking['pickup_date']))?>
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500">Return</p>
                                        <p class="font-medium text-gray-900">
                                            <?= date('M j', strtotime($booking['return_date']))?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <?php
        endforeach; ?>
                        </div>
                        <?php
    endif; ?>
                    </div>
                    <?php
endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Support Chat Button -->
    <button
        class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 w-12 h-12 sm:w-14 sm:h-14 bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-lg flex items-center justify-center transition z-30">
        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
            </path>
        </svg>
    </button>

    <!-- Booking Details Modal -->
    <div id="bookingModal"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-[2rem] w-full max-w-4xl max-h-[95vh] overflow-hidden shadow-2xl flex flex-col relative"
            onclick="event.stopPropagation()">
            <div
                class="p-8 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white/80 backdrop-blur-md z-10">
                <div>
                    <h3 class="text-xl font-black text-gray-900 uppercase tracking-tighter">Management</h3>
                    <p class="text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] mt-1">Booking Operations
                    </p>
                </div>
                <button onclick="closeBookingModal()"
                    class="w-10 h-10 flex items-center justify-center text-gray-400 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <div id="bookingModalContent" class="p-8 overflow-y-auto flex-1">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Date Bookings Modal -->
    <div id="dateBookingsModal"
        class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[85vh] flex flex-col"
            onclick="event.stopPropagation()">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-white rounded-t-2xl">
                <div>
                    <h3 class="text-xl font-bold text-gray-900" id="dateModalTitle">Bookings</h3>
                    <p class="text-xs text-gray-500 mt-0.5" id="dateModalSubtitle">Daily Overview</p>
                </div>
                <button onclick="closeDateBookingsModal()"
                    class="w-10 h-10 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <div id="dateBookingsContent" class="p-4 overflow-y-auto flex-1 bg-gray-50 rounded-b-2xl">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Mobile Menu Overlay -->


    <script>
        // Calendar navigation variables and functions - MUST BE FIRST
        window.currentView = '<?= $current_view?>';
        window.currentMonth = <?= $current_month?>;
        window.currentYear = <?= $current_year?>;
        window.currentDay = <?= $current_day?>;

        // Calendar navigation functions
        function setView(view) {
            console.log('setView called with:', view);
            window.location.href = '?view=' + view + '&month=' + window.currentMonth + '&year=' + window.currentYear + '&day=' + window.currentDay;
        }

        function navigateMonth(direction) {
            console.log('navigateMonth called with direction:', direction, 'view:', window.currentView);

            if (window.currentView === 'day') {
                // Navigate by day
                var currentDate = new Date(window.currentYear, window.currentMonth - 1, window.currentDay);
                currentDate.setDate(currentDate.getDate() + direction);
                var newDay = currentDate.getDate();
                var newMonth = currentDate.getMonth() + 1;
                var newYear = currentDate.getFullYear();
                console.log('Navigating to day:', newDay, newMonth, newYear);
                window.location.href = '?view=day&month=' + newMonth + '&year=' + newYear + '&day=' + newDay;
            } else if (window.currentView === 'week') {
                // Navigate by week (7 days)
                var currentDate = new Date(window.currentYear, window.currentMonth - 1, window.currentDay);
                currentDate.setDate(currentDate.getDate() + (direction * 7));
                var newDay = currentDate.getDate();
                var newMonth = currentDate.getMonth() + 1;
                var newYear = currentDate.getFullYear();
                console.log('Navigating to week:', newDay, newMonth, newYear);
                window.location.href = '?view=week&month=' + newMonth + '&year=' + newYear + '&day=' + newDay;
            } else {
                // Navigate by month
                var newMonth = window.currentMonth + direction;
                var newYear = window.currentYear;

                if (newMonth > 12) {
                    newMonth = 1;
                    newYear++;
                } else if (newMonth < 1) {
                    newMonth = 12;
                    newYear--;
                }

                console.log('Navigating to month:', newMonth, newYear);
                window.location.href = '?view=month&month=' + newMonth + '&year=' + newYear + '&day=' + window.currentDay;
            }
        }

        function goToToday() {
            console.log('goToToday called');
            var today = new Date();
            var todayMonth = today.getMonth() + 1;
            var todayYear = today.getFullYear();
            var todayDay = today.getDate();
            console.log('Going to today:', todayMonth, todayYear, todayDay);
            window.location.href = '?view=' + window.currentView + '&month=' + todayMonth + '&year=' + todayYear + '&day=' + todayDay;
        }

        console.log('Calendar initialized with:', {
            view: window.currentView,
            month: window.currentMonth,
            year: window.currentYear,
            day: window.currentDay
        });

        // Booking modal functions
        function openBookingModal(bookingId) {
            const modal = document.getElementById('bookingModal');
            const content = document.getElementById('bookingModalContent');

            // Show modal
            modal.classList.remove('hidden');

            // Show loading state
            content.innerHTML = `
                <div class="flex items-center justify-center py-20">
                    <div class="flex flex-col items-center gap-4">
                        <div class="animate-spin rounded-full h-12 w-12 border-[3px] border-blue-600/20 border-t-blue-600"></div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Retrieving Data</p>
                    </div>
                </div>
            `;

            // Fetch booking details
            fetch('get-booking-details.php?id=' + bookingId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayBookingDetails(data.booking, data.condition_reports, data.contract, data.available_templates);
                    } else {
                        content.innerHTML = `
                            <div class="text-center py-12">
                                <p class="text-red-600 font-bold">${data.message || 'Failed to load booking details'}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    content.innerHTML = `
                        <div class="text-center py-12">
                            <p class="text-red-600 font-bold">Error loading booking details</p>
                            <p class="text-[10px] text-gray-400 mt-2">${error.message}</p>
                        </div>
                    `;
                });
        }

        function displayBookingDetails(booking, reports, contract, available_templates = []) {
            const content = document.getElementById('bookingModalContent');
            const pickupReport = reports && reports.pickup ? reports.pickup : null;
            const returnReport = reports && reports.return ? reports.return : null;

            const statusMap = {
                'pending': 'bg-amber-50 text-amber-600 border border-amber-100',
                'confirmed': 'bg-blue-50 text-blue-600 border border-blue-100',
                'active': 'bg-green-50 text-green-600 border border-green-100',
                'completed': 'bg-gray-50 text-gray-600 border border-gray-100',
                'cancelled': 'bg-red-50 text-red-600 border border-red-100'
            };

            const paymentMap = {
                'unpaid': 'bg-amber-100 text-amber-800',
                'partial': 'bg-blue-100 text-blue-800',
                'paid': 'bg-green-100 text-green-800',
                'refunded': 'bg-red-100 text-red-800'
            };

            const prepareMiscPhotos = (report) => {
                let html = '';
                try {
                    const misc = (report && report.misc_photos) ? JSON.parse(report.misc_photos) : [];
                    for (let i = 0; i < 4; i++) {
                        if (misc[i]) {
                            html += `<div class="aspect-square rounded-xl bg-gray-100 overflow-hidden shadow-sm border border-gray-100 group relative">
                                <img src="${misc[i]}" class="w-full h-full object-cover">
                            </div>`;
                        } else {
                            html += `<div class="aspect-square rounded-xl bg-gray-50 border-2 border-dashed border-gray-200 flex flex-col items-center justify-center p-2 text-center text-gray-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="2" /></svg>
                            </div>`;
                        }
                    }
                } catch (e) {
                    for (let i = 0; i < 4; i++) html += `<div class="aspect-square rounded-xl bg-gray-50 border-2 border-dashed border-gray-200 flex items-center justify-center"><svg class="w-5 h-5 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="2.5"/></svg></div>`;
                }
                return html;
            };

            const renderPhotoField = (id, label, icon, currentPath, type) => {
                const hasPhoto = currentPath && currentPath !== '';
                const isRequired = !hasPhoto;
                return `
                    <div class="relative group condition-photo-wrapper" data-id="${id}" data-has-photo="${hasPhoto}">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">${label}${isRequired ? ' <span class="text-red-500">*</span>' : ''}</label>
                        <div onclick="this.querySelector('input').click()" class="relative aspect-video rounded-2xl bg-gray-50 border-2 border-dashed ${isRequired ? 'border-red-100/50' : 'border-gray-200'} hover:border-blue-300 transition-all cursor-pointer overflow-hidden flex items-center justify-center group/photo">
                            ${hasPhoto ?
                        `<img src="${currentPath}" class="w-full h-full object-cover transition-transform group-hover/photo:scale-110">
                         <div class="absolute inset-0 bg-black/40 opacity-0 group-hover/photo:opacity-100 transition-opacity flex items-center justify-center">
                            <span class="text-[10px] text-white font-bold uppercase tracking-widest">Replace Photo</span>
                         </div>` :
                        `<div class="text-center">
                                    <svg class="w-8 h-8 text-gray-300 mb-1 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-[10px] text-gray-400 font-medium">Click to upload</span>
                                 </div>`
                    }
                            <input type="file" name="${id}" class="hidden" ${isRequired ? 'required' : ''} onchange="previewConditionPhoto(this)">
                        </div>
                    </div>
                `;
            };

            content.innerHTML = `
                <div class="flex items-center justify-center mb-8">
                    <div class="flex h-11 w-fit max-w-full items-center rounded-full border border-gray-100 bg-gray-50/50 p-1 select-none backdrop-blur-sm">
                        <button onclick="switchModalTab('details')" id="tab-btn-details" class="flex cursor-pointer items-center gap-2 rounded-full px-5 py-1.5 font-bold whitespace-nowrap transition-all text-[11px] uppercase tracking-widest bg-white shadow-sm text-blue-600">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Information
                        </button>
                        <button onclick="switchModalTab('condition')" id="tab-btn-condition" class="flex cursor-pointer items-center gap-2 rounded-full px-5 py-1.5 font-bold whitespace-nowrap transition-all text-[11px] uppercase tracking-widest text-[#4b5058] hover:text-black">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Condition
                        </button>
                        <button onclick="switchModalTab('contract')" id="tab-btn-contract" class="flex cursor-pointer items-center gap-2 rounded-full px-5 py-1.5 font-bold whitespace-nowrap transition-all text-[11px] uppercase tracking-widest text-[#4b5058] hover:text-black">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Contract
                        </button>
                    </div>
                </div>

                <div id="tab-content-details" class="tab-pane space-y-8 animate-fade-in-up">
                    <div class="grid md:grid-cols-12 gap-6">
                        <!-- Left Column: Details -->
                        <div class="md:col-span-8 space-y-6">
                            <div class="bg-white rounded-[2rem] p-8 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(0,0,0,0.03)]">
                                <div class="flex items-center justify-between mb-6">
                                    <h4 class="text-[11px] font-black text-gray-400 uppercase tracking-[0.2em]">Rental Period</h4>
                                    <div class="flex gap-2">
                                        <span class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-tighter ${statusMap[booking.status] || 'bg-gray-100 text-gray-800'}">
                                            ${booking.status}
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-6 p-4 bg-gray-50/50 rounded-2xl border border-gray-100/50 mb-8">
                                    <div class="flex-1 text-center">
                                        <p class="text-[9px] font-black text-blue-500 uppercase tracking-widest mb-1">Pickup</p>
                                        <p class="text-base font-black text-gray-900">${new Date(booking.pickup_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}</p>
                                    </div>
                                    <div class="flex flex-col items-center gap-1">
                                        <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 8l4 4m0 0l-4 4m4-4H3" stroke-width="2.5" /></svg>
                                        <span class="text-[9px] font-black text-gray-300 uppercase tracking-tighter">${booking.total_days} Days</span>
                                    </div>
                                    <div class="flex-1 text-center">
                                        <p class="text-[9px] font-black text-orange-500 uppercase tracking-widest mb-1">Return</p>
                                        <p class="text-base font-black text-gray-900">${new Date(booking.return_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="p-5 bg-gray-50/30 rounded-2xl border border-gray-100/50 group hover:bg-white hover:border-blue-100 transition-all">
                                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-[0.2em] mb-2">Vehicle</p>
                                        <p class="text-sm font-black text-gray-900 leading-tight small-vechicle-fix">${booking.brand} ${booking.model}</p>
                                        <p class="text-[10px] text-gray-400 mt-1">${booking.year} • ${booking.category.toUpperCase()}</p>
                                    </div>
                                    <div class="p-5 bg-gray-50/30 rounded-2xl border border-gray-100/50 group hover:bg-white hover:border-blue-100 transition-all">
                                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-[0.2em] mb-2">Customer</p>
                                        <p class="text-sm font-black text-gray-900 leading-tight truncate">${booking.customer_name}</p>
                                        <p class="text-[10px] text-gray-400 mt-1 truncate">${booking.customer_email}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Actions -->
                        <div class="md:col-span-4 flex flex-col gap-4">
                            <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Quick Actions</h4>
                            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(0,0,0,0.03)] space-y-3">
                                <button onclick="updateBookingStatus(${booking.id}, 'confirmed')" class="w-full py-4 bg-gray-900 text-white rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-xl shadow-gray-200 hover:bg-black hover:-translate-y-0.5 transition-all">Confirm</button>
                                <button onclick="updateBookingStatus(${booking.id}, 'active')" class="w-full py-4 bg-blue-600 text-white rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-xl shadow-blue-200 hover:bg-blue-700 hover:-translate-y-0.5 transition-all">Start Trip</button>
                                <div class="pt-4 mt-2 border-t border-gray-50">
                                    <button onclick="updateBookingStatus(${booking.id}, 'cancelled')" class="w-full py-3 bg-white text-red-500 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] border border-red-50 hover:bg-red-50 transition-all">Void Agreement</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Guarantee Section -->
                    <div class="space-y-6">
                        <div class="flex items-center justify-between ml-2">
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                <h4 class="text-[11px] font-black text-gray-900 uppercase tracking-widest">Financial Guarantee</h4>
                            </div>
                            <div class="px-3 py-1 bg-blue-50 rounded-full border border-blue-100 text-[9px] font-black text-blue-600 uppercase tracking-tighter shadow-sm">Securing Assets</div>
                        </div>
                        
                        <div class="bg-white rounded-[2.5rem] border border-gray-100 shadow-[0_15px_40px_-20px_rgba(0,0,0,0.08)] p-8 space-y-10 border-t-2 border-t-blue-500/10">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                                <div class="space-y-3">
                                    <label class="block text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] ml-2">Amount Secured</label>
                                    <div class="relative group">
                                        <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none text-gray-900 font-black text-sm">£</div>
                                        <input type="number" id="deposit-amount" value="${booking.security_deposit || 0}" 
                                               class="w-full bg-gray-50/50 border border-transparent rounded-3xl pl-10 pr-6 py-5 text-sm font-black focus:bg-white focus:border-blue-500/20 focus:ring-8 focus:ring-blue-500/5 transition-all outline-none shadow-inner">
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <label class="block text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] ml-2">Status Tracking</label>
                                    <div class="relative">
                                        <select id="deposit-status" class="w-full bg-gray-50/50 border border-transparent rounded-3xl pl-6 pr-10 py-5 text-[10px] font-black uppercase tracking-[0.2em] focus:bg-white focus:border-blue-500/20 transition-all outline-none appearance-none shadow-inner cursor-pointer">
                                            <option value="unpaid" ${booking.security_deposit_status === 'unpaid' ? 'selected' : ''}>⚠️ Outstanding</option>
                                            <option value="paid" ${booking.security_deposit_status === 'paid' ? 'selected' : ''}>💎 Fully Paid</option>
                                            <option value="refunded" ${booking.security_deposit_status === 'refunded' ? 'selected' : ''}>🔄 Refunded</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-5 flex items-center pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="3" /></svg>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <label class="block text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] ml-2">Payment Channel</label>
                                    <div class="relative">
                                        <select id="deposit-method" class="w-full bg-gray-50/50 border border-transparent rounded-3xl pl-6 pr-10 py-5 text-[10px] font-black uppercase tracking-[0.2em] focus:bg-white focus:border-blue-500/20 transition-all outline-none appearance-none shadow-inner cursor-pointer">
                                            <option value="" ${!booking.security_deposit_method ? 'selected' : ''}>Not Set</option>
                                            <option value="cash" ${booking.security_deposit_method === 'cash' ? 'selected' : ''}>💵 Cash Deposit</option>
                                            <option value="card" ${booking.security_deposit_method === 'card' ? 'selected' : ''}>💳 Terminal</option>
                                            <option value="stripe" ${booking.security_deposit_method === 'stripe' ? 'selected' : ''}>🌍 Online Payment</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-5 flex items-center pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="3" /></svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <button onclick="updateSecurityDeposit(${booking.id})" 
                                    class="w-full py-6 bg-blue-600 text-white rounded-[2rem] font-black text-[12px] uppercase tracking-[0.4em] shadow-2xl shadow-blue-500/20 hover:bg-blue-700 hover:scale-[1.01] active:scale-95 transition-all flex items-center justify-center gap-5 group/btn">
                                <span class="bg-white/20 p-2 rounded-xl group-hover/btn:rotate-12 transition-transform">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-width="3" /></svg>
                                </span>
                                Synchronize Deposit Data
                            </button>
                        </div>
                    </div>
                </div>


                <div id="tab-content-condition" class="tab-pane hidden space-y-6">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-xs font-black text-gray-900 uppercase tracking-tighter">Condition Report</h4>
                        <div class="flex p-1 bg-gray-100 rounded-xl">
                            <button onclick="switchConditionTab('pickup')" id="tab-pickup-btn" class="px-4 py-1.5 text-[10px] font-black rounded-lg transition-all bg-white shadow-sm text-blue-600 uppercase">Pickup</button>
                            <button onclick="switchConditionTab('return')" id="tab-return-btn" class="px-4 py-1.5 text-[10px] font-black rounded-lg transition-all text-gray-500 hover:text-gray-700 uppercase">Return</button>
                        </div>
                    </div>

                    <div id="section-pickup" class="space-y-6">
                        <form onsubmit="submitConditionReport(event, 'pickup', ${booking.id})">
                            <div class="bg-gray-50 rounded-3xl p-6 space-y-8 border border-gray-100">
                                <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
                                     <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Mileage at Pickup</label>
                                     <input type="number" name="mileage" value="${pickupReport ? pickupReport.mileage : ''}" required class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm font-bold focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none" placeholder="00,000">
                                </div>

                                <div>
                                    <p class="text-[10px] font-black text-gray-900 uppercase tracking-widest mb-4 ml-1">Vehicle Photos</p>
                                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                                        ${renderPhotoField('photo_front', 'Front View', '', pickupReport ? pickupReport.photo_front : '', 'pickup')}
                                        ${renderPhotoField('photo_back', 'Rear View', '', pickupReport ? pickupReport.photo_back : '', 'pickup')}
                                        ${renderPhotoField('photo_left', 'Left Side', '', pickupReport ? pickupReport.photo_left : '', 'pickup')}
                                        ${renderPhotoField('photo_right', 'Right Side', '', pickupReport ? pickupReport.photo_right : '', 'pickup')}
                                    </div>
                                </div>

                                <div>
                                    <p class="text-[10px] font-black text-gray-900 uppercase tracking-widest mb-4 ml-1">Alloys & Rims</p>
                                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                                        ${renderPhotoField('photo_rim1', 'Front Left', '', pickupReport ? pickupReport.photo_rim1 : '', 'pickup')}
                                        ${renderPhotoField('photo_rim2', 'Front Right', '', pickupReport ? pickupReport.photo_rim2 : '', 'pickup')}
                                        ${renderPhotoField('photo_rim3', 'Rear Left', '', pickupReport ? pickupReport.photo_rim3 : '', 'pickup')}
                                        ${renderPhotoField('photo_rim4', 'Rear Right', '', pickupReport ? pickupReport.photo_rim4 : '', 'pickup')}
                                    </div>
                                </div>

                                <div class="pt-6 border-t border-gray-200">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4 ml-1 italic">Additional Photos (up to 4)</p>
                                    <div class="grid grid-cols-4 gap-4 mb-4" id="misc-pickup-preview">
                                        ${prepareMiscPhotos(pickupReport)}
                                    </div>
                                    <label class="block">
                                        <span class="sr-only">Upload images</span>
                                        <input type="file" name="misc_photos[]" multiple accept="image/*" onchange="previewMiscPhotos(this, 'pickup')" class="block w-full text-[10px] text-gray-400 file:mr-4 file:py-2 file:px-6 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:bg-gray-900 file:text-white hover:file:bg-black transition-all cursor-pointer">
                                    </label>
                                </div>

                                <button type="submit" class="w-full py-4 bg-gray-900 text-white rounded-3xl font-black text-xs uppercase tracking-[0.2em] shadow-2xl shadow-gray-200 hover:scale-[1.02] transition-all">Update Pickup Status</button>
                            </div>
                        </form>
                    </div>

                    <div id="section-return" class="hidden space-y-6">
                        <form onsubmit="submitConditionReport(event, 'return', ${booking.id})">
                            <div class="bg-gray-50 rounded-3xl p-6 space-y-8 border border-gray-100">
                                <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
                                     <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Mileage at Return</label>
                                     <input type="number" name="mileage" value="${returnReport ? returnReport.mileage : ''}" required class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm font-bold focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none" placeholder="00,000">
                                </div>

                                <div>
                                    <p class="text-[10px] font-black text-gray-900 uppercase tracking-widest mb-4 ml-1">Vehicle Photos</p>
                                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                                        ${renderPhotoField('photo_front', 'Front View', '', returnReport ? returnReport.photo_front : '', 'return')}
                                        ${renderPhotoField('photo_back', 'Rear View', '', returnReport ? returnReport.photo_back : '', 'return')}
                                        ${renderPhotoField('photo_left', 'Left Side', '', returnReport ? returnReport.photo_left : '', 'return')}
                                        ${renderPhotoField('photo_right', 'Right Side', '', returnReport ? returnReport.photo_right : '', 'return')}
                                    </div>
                                </div>

                                <div>
                                    <p class="text-[10px] font-black text-gray-900 uppercase tracking-widest mb-4 ml-1">Alloys & Rims</p>
                                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                                        ${renderPhotoField('photo_rim1', 'Front Left', '', returnReport ? returnReport.photo_rim1 : '', 'return')}
                                        ${renderPhotoField('photo_rim2', 'Front Right', '', returnReport ? returnReport.photo_rim2 : '', 'return')}
                                        ${renderPhotoField('photo_rim3', 'Rear Left', '', returnReport ? returnReport.photo_rim3 : '', 'return')}
                                        ${renderPhotoField('photo_rim4', 'Rear Right', '', returnReport ? returnReport.photo_rim4 : '', 'return')}
                                    </div>
                                </div>

                                <div class="pt-6 border-t border-gray-200">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4 ml-1 italic">Additional Photos (up to 4)</p>
                                    <div class="grid grid-cols-4 gap-4 mb-4" id="misc-return-preview">
                                        ${prepareMiscPhotos(returnReport)}
                                    </div>
                                    <label class="block">
                                        <span class="sr-only">Upload images</span>
                                        <input type="file" name="misc_photos[]" multiple accept="image/*" onchange="previewMiscPhotos(this, 'return')" class="block w-full text-[10px] text-gray-400 file:mr-4 file:py-2 file:px-6 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:bg-gray-900 file:text-white hover:file:bg-black transition-all cursor-pointer">
                                    </label>
                                </div>

                                <button type="submit" class="w-full py-4 bg-blue-600 text-white rounded-3xl font-black text-xs uppercase tracking-[0.2em] shadow-2xl shadow-blue-200 hover:scale-[1.02] transition-all">Finalize Return</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="tab-content-contract" class="tab-pane hidden space-y-6">
                    <div class="bg-gray-50 rounded-3xl p-8 border border-gray-100 text-center">
                        ${contract ? `
                            <div class="w-20 h-20 bg-white rounded-3xl shadow-xl flex items-center justify-center mx-auto mb-6 border border-gray-100 relative">
                                <div class="absolute -top-2 -right-2 w-6 h-6 ${contract.contract_status === 'signed' ? 'bg-green-500' : 'bg-amber-500 animate-pulse'} rounded-full border-4 border-gray-50"></div>
                                <svg class="w-10 h-10 ${contract.contract_status === 'signed' ? 'text-green-500' : 'text-amber-500'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="2.5" />
                                </svg>
                            </div>
                            <h4 class="text-xl font-black text-gray-900 uppercase tracking-tighter mb-1">Rental Agreement</h4>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-8">Status: <span class="${contract.contract_status === 'signed' ? 'text-green-600 border-green-200 bg-green-50' : 'text-amber-600 border-amber-200 bg-amber-50'} px-2 py-0.5 rounded border ml-1 font-black">${contract.contract_status.toUpperCase()}</span></p>
                            
                            <div class="grid grid-cols-1 gap-3 max-w-xs mx-auto">
                                <button onclick="viewContractPDF(${booking.id})" 
                                        class="flex items-center justify-center gap-3 px-6 py-4 bg-white border border-gray-200 text-gray-900 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-sm hover:bg-gray-50 transition-all group">
                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="2.5" /><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-width="2.5" /></svg>
                                    View Agreement
                                </button>
                                <a href="/api/download-contract.php?booking_id=${booking.id}" target="_blank" 
                                   class="flex items-center justify-center gap-3 px-6 py-4 bg-gray-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-2xl shadow-gray-200 hover:scale-[1.02] transition-all">
                                   <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 4v12m0 0l-4-4m4 4l4-4" stroke-width="2.5" /></svg>
                                   Export Official PDF
                                </a>
                                ${contract.contract_status !== 'signed' ? `
                                    <button onclick="copyContractLink(this, '${window.location.origin}/templates/contract-sign.php?booking_id=${booking.id}&token=${contract.signing_token}')" 
                                            class="flex items-center justify-center gap-3 px-6 py-4 bg-white border border-gray-200 text-gray-900 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-sm hover:bg-gray-50 transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" stroke-width="2.5" /></svg>
                                        Copy Sign Link
                                    </button>
                                ` : ''}
                            </div>
                        ` : `
                            <div class="py-12 px-4">
                                <div class="w-24 h-24 bg-gray-100 rounded-[2rem] flex items-center justify-center mx-auto mb-8 shadow-inner border border-gray-50/50">
                                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="2" /></svg>
                                </div>
                                <h4 class="text-lg font-black text-gray-900 uppercase tracking-tighter mb-3">No active contract</h4>
                                <p class="text-xs text-gray-400 mb-10 max-w-[280px] mx-auto font-bold leading-relaxed">Select a template below to initialize and send an electronic rental agreement.</p>
                                
                                ${available_templates.length > 0 ? `
                                    <div class="max-w-xs mx-auto space-y-4">
                                        <div class="text-left">
                                            <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Choose Template</label>
                                            <select id="selected-template-id" class="w-full bg-white border border-gray-200 rounded-2xl px-5 py-4 text-xs font-black outline-none focus:ring-4 focus:ring-blue-500/10 transition-all">
                                                ${available_templates.map(tmpl => `<option value="${tmpl.id}">${tmpl.name}</option>`).join('')}
                                            </select>
                                        </div>
                                        <button onclick="generateManualContract(${booking.id})" 
                                                class="w-full py-4 bg-gray-900 text-white rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-xl shadow-gray-200 flex items-center justify-center gap-3">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" stroke-width="2.5" /></svg>
                                            Deploy Invitation
                                        </button>
                                    </div>
                                ` : '<p class="inline-flex px-4 py-2 bg-red-50 text-red-600 text-[10px] font-black rounded-lg border border-red-100 uppercase tracking-widest">No templates published.</p>'}
                            </div>
                        `}
                    </div>
                </div>
            `;

            // Initialize custom selects for the newly injected content
            if (window.initCustomSelects) {
                setTimeout(() => window.initCustomSelects(), 0);
            }
        }

        function closeBookingModal() {
            document.getElementById('bookingModal').classList.add('hidden');
        }

        function closeBookingModalOnOutsideClick(event) {
            if (event.target === event.currentTarget) {
                closeBookingModal();
            }
        }

        function switchModalTab(tabId) {
            document.querySelectorAll('.tab-pane').forEach(el => {
                el.classList.add('hidden');
                el.classList.remove('animate-fade-in-up');
            });
            
            const target = document.getElementById('tab-content-' + tabId);
            target.classList.remove('hidden');
            target.classList.add('animate-fade-in-up');
            
            document.querySelectorAll('[id^="tab-btn-"]').forEach(btn => {
                btn.classList.add('text-[#4b5058]', 'hover:text-black');
                btn.classList.remove('bg-white', 'shadow-sm', 'text-blue-600');
            });
            
            const activeBtn = document.getElementById('tab-btn-' + tabId);
            activeBtn.classList.remove('text-[#4b5058]', 'hover:text-black');
            activeBtn.classList.add('bg-white', 'shadow-sm', 'text-blue-600');
        }

        function switchConditionTab(type) {
            const pickupBtn = document.getElementById('tab-pickup-btn');
            const returnBtn = document.getElementById('tab-return-btn');
            const pickupSec = document.getElementById('section-pickup');
            const returnSec = document.getElementById('section-return');
            
            // Remove animation from both first
            pickupSec.classList.remove('animate-fade-in-up');
            returnSec.classList.remove('animate-fade-in-up');

            if (type === 'pickup') {
                pickupBtn.className = "flex-1 py-1.5 text-[10px] font-black rounded-lg transition-all bg-white shadow-sm text-blue-600 uppercase";
                returnBtn.className = "flex-1 py-1.5 text-[10px] font-black rounded-lg transition-all text-[#4b5058] hover:text-black uppercase";
                pickupSec.classList.remove('hidden');
                returnSec.classList.add('hidden');
                void pickupSec.offsetWidth; // Trigger reflow
                pickupSec.classList.add('animate-fade-in-up');
            } else {
                returnBtn.className = "flex-1 py-1.5 text-[10px] font-black rounded-lg transition-all bg-white shadow-sm text-blue-600 uppercase";
                pickupBtn.className = "flex-1 py-1.5 text-[10px] font-black rounded-lg transition-all text-[#4b5058] hover:text-black uppercase";
                returnSec.classList.remove('hidden');
                pickupSec.classList.add('hidden');
                void returnSec.offsetWidth; // Trigger reflow
                returnSec.classList.add('animate-fade-in-up');
            }
        }

        function previewConditionPhoto(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                const container = input.parentElement;
                reader.onload = function (e) {
                    let img = container.querySelector('img');
                    if (!img) {
                        const placeholder = container.querySelector('.text-center');
                        if (placeholder) placeholder.classList.add('hidden');
                        img = document.createElement('img');
                        img.className = 'w-full h-full object-cover';
                        container.appendChild(img);
                    }
                    img.src = e.target.result;
                    const wrapper = container.closest('.condition-photo-wrapper');
                    if (wrapper) wrapper.dataset.hasPhoto = 'true';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        async function generateManualContract(bookingId) {
            const templateId = document.getElementById('selected-template-id').value;
            if (!templateId) return showNotification('Please select a template', 'error');

            showNotification('Generating agreement...', 'info');

            fetch('generate-contract.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ booking_id: bookingId, template_id: templateId })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Contract generated successfully');
                        openBookingModal(bookingId);
                    } else {
                        showNotification(data.message, 'error');
                    }
                });
        }

        function copyContractLink(btn, url) {
            navigator.clipboard.writeText(url).then(() => {
                const originalSvg = btn.innerHTML;
                const originalText = btn.innerText;

                btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="3" /></svg> Link Copied!';
                btn.classList.add('!bg-green-500', '!text-white', '!border-green-500');

                setTimeout(() => {
                    btn.innerHTML = originalSvg;
                    btn.classList.remove('!bg-green-500', '!text-white', '!border-green-500');
                }, 2000);
            });
        }

        function previewMiscPhotos(input, type) {
            const container = document.getElementById(`misc-${type}-preview`);
            if (!container) return;

            const files = Array.from(input.files).slice(0, 4);
            container.innerHTML = '';

            for (let i = 0; i < 4; i++) {
                const div = document.createElement('div');
                div.className = 'aspect-square rounded-xl bg-gray-50 border-2 border-dashed border-gray-200 flex items-center justify-center overflow-hidden';

                if (files[i]) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        div.className = 'aspect-square rounded-xl bg-gray-100 overflow-hidden shadow-sm border border-gray-100';
                        div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                    };
                    reader.readAsDataURL(files[i]);
                    container.appendChild(div);
                } else {
                    div.innerHTML = `<svg class="w-5 h-5 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="2.5" stroke-linecap="round"/></svg>`;
                    container.appendChild(div);
                }
            }
        }

        function submitConditionReport(event, type, bookingId) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            formData.append('booking_id', bookingId);
            formData.append('report_type', type);
            fetch('upload-condition-report.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Condition report saved!');
                        openBookingModal(bookingId);
                    } else {
                        showNotification(data.message, 'error');
                    }
                });
        }

        function updateSecurityDeposit(bookingId) {
            const amount = document.getElementById('deposit-amount').value;
            const status = document.getElementById('deposit-status').value;
            const method = document.getElementById('deposit-method').value;

            fetch('update-booking-deposit.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    booking_id: bookingId,
                    amount: amount,
                    status: status,
                    method: method
                })
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Security deposit updated successfully');
                        // Optional: refresh details or UI
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(err => showNotification('Error updating deposit', 'error'));
        }

        // Event listeners for closing modals on outside click
        document.getElementById('bookingModal').addEventListener('click', closeBookingModalOnOutsideClick);
        document.getElementById('dateBookingsModal').addEventListener('click', closeDateBookingsModalOnOutsideClick);

        function updateBookingStatus(bookingId, status) {
            showConfirmation(
                'Update Booking Status',
                'Are you sure you want to update this booking status?',
                () => {
                    fetch('update-booking-status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ booking_id: bookingId, status: status })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification('Booking status updated successfully', 'success');
                                closeBookingModal();
                                setTimeout(() => location.reload(), 1000);
                            } else {
                                showNotification('Error: ' + data.message, 'error');
                            }
                        })
                        .catch(error => {
                            showNotification('Error updating booking status', 'error');
                        });
                },
                'Update Status',
                'bg-blue-600 hover:bg-blue-700'
            );
        }

        // Close modal when clicking outside
        document.getElementById('bookingModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeBookingModal();
            }
        });

        function openDateBookingsModal(date) {
            const modal = document.getElementById('dateBookingsModal');
            const content = document.getElementById('dateBookingsContent');
            const title = document.getElementById('dateModalTitle');
            const subtitle = document.getElementById('dateModalSubtitle');

            // Format nice date for title
            const dateObj = new Date(date);
            title.textContent = dateObj.toLocaleDateString('en-GB', { day: 'numeric', month: 'long' });
            subtitle.textContent = dateObj.getFullYear();

            // Show modal
            modal.classList.remove('hidden');

            // Show loading state
            content.innerHTML = `
                <div class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div>
                </div>
            `;

            // Fetch bookings for this date
            fetch('get-bookings-by-date.php?date=' + date)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.bookings.length > 0) {
                        renderDateBookings(data.bookings);
                    } else {
                        content.innerHTML = `
                            <div class="text-center py-12 bg-white rounded-xl border border-dashed border-gray-200">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <p class="text-gray-500 font-medium">No bookings for this date</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    content.innerHTML = `
                        <div class="text-center py-12">
                            <p class="text-red-500">Error loading bookings</p>
                        </div>
                    `;
                });
        }

        function renderDateBookings(bookings) {
            const content = document.getElementById('dateBookingsContent');
            let html = '<div class="space-y-3">';

            bookings.forEach(booking => {
                html += `
                    <div onclick="openBookingModal(${booking.id})" class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm hover:border-blue-200 hover:shadow-md transition-all cursor-pointer group">
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 group-hover:text-blue-500 transition-colors">Booking #${String(booking.id).padStart(5, '0')}</span>
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-tight ${getBookingStatusClass(booking.status)}">
                                ${booking.status}
                            </span>
                        </div>
                        <h4 class="text-sm font-bold text-gray-900 mb-1">${booking.customer_name}</h4>
                        <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" stroke-width="2"/><path d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 01-1 1h1m8-1a1 1 0 01-1 1H9m4-1a1 1 0 001 1h1m4-1a1 1 0 001 1h1m-1-5h1m-5 0h1m-1-4h1m-5 1a3 3 0 013 3v1H7V7a3 3 0 013-3z" stroke-width="2"/></svg>
                            <span>${booking.brand} ${booking.model}</span>
                        </div>
                        <div class="flex items-center justify-between pt-3 border-t border-gray-50">
                            <div class="flex items-center gap-1.5 text-blue-600">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span class="text-xs font-bold">
                                    ${new Date(booking.pickup_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })} - 
                                    ${new Date(booking.return_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })}
                                </span>
                            </div>
                            <span class="text-blue-600 group-hover:translate-x-1 transition-transform">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </span>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            content.innerHTML = html;
        }

        function getBookingStatusClass(status) {
            const classes = {
                'pending': 'bg-yellow-50 text-yellow-600',
                'confirmed': 'bg-blue-50 text-blue-600',
                'active': 'bg-green-50 text-green-600',
                'completed': 'bg-gray-50 text-gray-600',
                'cancelled': 'bg-red-50 text-red-600'
            };
            return classes[status] || 'bg-blue-50 text-blue-600';
        }

        function closeDateBookingsModal() {
            document.getElementById('dateBookingsModal').classList.add('hidden');
        }

        function closeDateBookingsModalOnOutsideClick(event) {
            if (event.target === event.currentTarget) {
                closeDateBookingsModal();
            }
        }



        function dismissRecentActivity() {
            const section = document.getElementById('recent-activity-section');
            if (section) {
                // Fade out animation
                section.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                section.style.opacity = '0';
                section.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    section.style.display = 'none';
                }, 300);
            }
            // Set cookie for 30 days
            const d = new Date();
            d.setTime(d.getTime() + (30 * 24 * 60 * 60 * 1000));
            document.cookie = "hide_recent_activity=true; expires=" + d.toUTCString() + "; path=/";
        }
        function viewContractPDF(bookingId) {
            const modal = document.getElementById('contractPreviewModal');
            const content = document.getElementById('contractPreviewContent');

            modal.classList.remove('hidden');
            content.innerHTML = `
                <div class="w-full h-full min-h-[600px] flex flex-col">
                    <iframe src="/dashboard/preview-contract.php?booking_id=${bookingId}" class="w-full flex-1 border-0 rounded-xl" style="height: 60vh;"></iframe>
                </div>
            `;
        }

        function closeContractPreviewModal() {
            document.getElementById('contractPreviewModal').classList.add('hidden');
        }
    </script>

    <!-- Contract Preview Modal -->
    <div id="contractPreviewModal"
        class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden">
            <div
                class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-white text-gray-900">
                <div>
                    <h3 class="text-xl font-black uppercase tracking-tighter">Contract Preview</h3>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Agreement Details
                    </p>
                </div>
                <button onclick="closeContractPreviewModal()"
                    class="w-10 h-10 flex items-center justify-center text-gray-400 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="contractPreviewContent" class="p-8 overflow-y-auto bg-gray-50 flex-1">
                <!-- Content will be injected here -->
            </div>
            <div class="p-6 border-t border-gray-100 bg-white flex justify-end">
                <button onclick="closeContractPreviewModal()"
                    class="px-8 py-3 bg-gray-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-black transition-all shadow-xl shadow-gray-200">Close
                    Preview</button>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/confirmation-modal.php'; ?>

    <?php include __DIR__ . '/../includes/onboarding-widget.php'; ?>

</body>

</html>