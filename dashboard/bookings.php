<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/auth/login.php');
}

if ($_SESSION['role'] === 'super_admin') {
    redirect('/admin/super-admin.php');
}

if (!$_SESSION['tenant_id']) {
    die('Error: No tenant associated with this account.');
}

$pdo = getDB();

// Create bookings table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL,
        vehicle_id INT NOT NULL,
        customer_name VARCHAR(255) NOT NULL,
        customer_email VARCHAR(255) NOT NULL,
        customer_phone VARCHAR(50),
        customer_license VARCHAR(100),
        pickup_date DATE NOT NULL,
        return_date DATE NOT NULL,
        pickup_time TIME,
        return_time TIME,
        total_days INT NOT NULL,
        price_per_day DECIMAL(10,2) NOT NULL,
        total_price DECIMAL(10,2) NOT NULL,
        security_deposit DECIMAL(10,2) DEFAULT 0,
        status ENUM('pending', 'confirmed', 'active', 'completed', 'cancelled') DEFAULT 'pending',
        payment_status ENUM('unpaid', 'partial', 'paid', 'refunded') DEFAULT 'unpaid',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
        FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
        INDEX idx_tenant (tenant_id),
        INDEX idx_status (status),
        INDEX idx_dates (pickup_date, return_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
catch (PDOException $e) {
// Table already exists
}

// Ensure stripe_payment_id column exists
try {
    $check_col = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'stripe_payment_id'");
    $check_col->execute();
    if ($check_col->fetchColumn() == 0) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN stripe_payment_id VARCHAR(255) NULL AFTER payment_status");
    }

    // Ensure is_deleted column exists
    $check_deleted = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'is_deleted'");
    $check_deleted->execute();
    if ($check_deleted->fetchColumn() == 0) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN is_deleted TINYINT(1) DEFAULT 0 AFTER stripe_payment_id");
    }
}
catch (PDOException $e) {
// Column might already exist
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$vehicle_filter = $_GET['vehicle'] ?? '';
$date_filter = $_GET['date_filter'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$view = $_GET['view'] ?? 'active';

if ($date_filter === 'today') {
    $date_from = date('Y-m-d');
    $date_to = date('Y-m-d');
}
elseif ($date_filter === 'week') {
    $date_from = date('Y-m-d', strtotime('monday this week'));
    $date_to = date('Y-m-d', strtotime('sunday this week'));
}
elseif ($date_filter === 'month') {
    $date_from = date('Y-m-01');
    $date_to = date('Y-m-t');
}

// Build query
$is_deleted = ($view === 'deleted') ? 1 : 0;
$query = "SELECT b.*, v.brand, v.model, v.category, v.year 
          FROM bookings b 
          LEFT JOIN vehicles v ON b.vehicle_id = v.id 
          WHERE b.tenant_id = ? AND b.is_deleted = ?";
$params = [$_SESSION['tenant_id'], $is_deleted];

// Search by customer name, email, or license
if ($search) {
    $query .= " AND (b.customer_name LIKE ? OR b.customer_email LIKE ? OR b.customer_license LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

// Filter by status
if ($status_filter) {
    $query .= " AND b.status = ?";
    $params[] = $status_filter;
}

// Filter by vehicle
if ($vehicle_filter) {
    $query .= " AND b.vehicle_id = ?";
    $params[] = $vehicle_filter;
}

// Filter by date range
if ($date_from) {
    $query .= " AND b.pickup_date >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $query .= " AND b.return_date <= ?";
    $params[] = $date_to;
}

$query .= " ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Get all vehicles for filter dropdown
$stmt = $pdo->prepare("SELECT id, brand, model FROM vehicles WHERE tenant_id = ? ORDER BY brand, model");
$stmt->execute([$_SESSION['tenant_id']]);
$vehicles = $stmt->fetchAll();

// Get tenant information
$stmt = $pdo->prepare("SELECT * FROM tenants WHERE id = ?");
$stmt->execute([$_SESSION['tenant_id']]);
$tenant = $stmt->fetch();

// Calculate trial days remaining
$trial_days_remaining = 0;
$trial_percentage = 0;
if ($tenant && $tenant['plan'] === 'trial' && isset($tenant['trial_end_date']) && $tenant['trial_end_date']) {
    try {
        $trial_end = new DateTime($tenant['trial_end_date']);
        $now = new DateTime();
        $interval = $now->diff($trial_end);
        $trial_days_remaining = $interval->days;
        if ($now > $trial_end) {
            $trial_days_remaining = 0;
        }
        $trial_percentage = ($trial_days_remaining / 30) * 100;
    }
    catch (Exception $e) {
        $trial_days_remaining = 0;
        $trial_percentage = 0;
    }
}

// Calculate statistics
$total_bookings = count($bookings);
$pending_bookings = count(array_filter($bookings, fn($b) => $b['status'] === 'pending'));
$active_bookings = count(array_filter($bookings, fn($b) => in_array($b['status'], ['active', 'confirmed'])));
$total_revenue = array_sum(array_map(fn($b) => (float)$b['total_price'], array_filter($bookings, fn($b) => $b['status'] !== 'cancelled')));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings -
        <?= SITE_NAME?>
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="/app/custom.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-confirmed {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-active {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-completed {
            background-color: #e5e7eb;
            color: #374151;
        }

        .status-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }

        /* Remove spin buttons from number inputs */
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
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

    <div class="flex-1 flex flex-col overflow-hidden w-full lg:w-auto pt-14 lg:pt-0">
        <!-- Desktop Top Bar -->
        <header class="hidden lg:block bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <nav class="text-sm text-gray-500 mb-1">
                        <a href="/dashboard/" class="hover:text-gray-700">Dashboard</a>
                        <span class="mx-2">/</span>
                        <span class="text-gray-900">Bookings</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-gray-900">Bookings</h1>
                    <p class="text-sm text-gray-600 mt-1">Manage and track all your vehicle bookings</p>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-auto bg-gray-50">
            <div class="px-4 sm:px-6 lg:px-8 py-8">

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div
                        class="bg-white rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 p-6 relative overflow-hidden group hover:border-blue-200 transition-all duration-300">
                        <div
                            class="absolute right-0 top-0 w-24 h-24 bg-blue-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110">
                        </div>
                        <div class="relative flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Bookings</p>
                                <p class="text-3xl font-bold text-gray-900 mt-2">
                                    <?= $total_bookings?>
                                </p>
                            </div>
                            <div
                                class="w-12 h-12 bg-white border border-gray-100 shadow-sm rounded-xl flex items-center justify-center z-10 transition-transform group-hover:-translate-y-1">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white rounded-xl shadow-[0_2px_10px_-3px_rgba(0,0,0,0.05)] border border-gray-100 p-6 relative overflow-hidden group hover:border-gray-300 transition-all duration-300">
                        <div
                            class="absolute right-0 top-0 w-24 h-24 bg-gray-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110">
                        </div>
                        <div class="relative flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Pending</p>
                                <p class="text-3xl font-bold text-gray-700 mt-2">
                                    <?= $pending_bookings?>
                                </p>
                            </div>
                            <div
                                class="w-12 h-12 bg-white border border-gray-100 shadow-sm rounded-xl flex items-center justify-center z-10 transition-transform group-hover:-translate-y-1">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white rounded-xl shadow-[0_2px_10px_-3px_rgba(0,0,0,0.05)] border border-gray-100 p-6 relative overflow-hidden group hover:border-gray-900 transition-all duration-300">
                        <div
                            class="absolute right-0 top-0 w-24 h-24 bg-gray-100 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110">
                        </div>
                        <div class="relative flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Active</p>
                                <p class="text-3xl font-bold text-gray-900 mt-2">
                                    <?= $active_bookings?>
                                </p>
                            </div>
                            <div
                                class="w-12 h-12 bg-white border border-gray-200 shadow-sm rounded-xl flex items-center justify-center z-10 transition-transform group-hover:-translate-y-1">
                                <svg class="w-6 h-6 text-gray-800" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-[#1a1f2b] rounded-xl shadow-lg p-6 relative overflow-hidden group hover:bg-black transition-all duration-300">
                        <div
                            class="absolute right-0 top-0 w-32 h-32 bg-white opacity-5 rounded-bl-full -mr-8 -mt-8 transition-transform group-hover:scale-110">
                        </div>
                        <div
                            class="absolute left-0 bottom-0 w-24 h-24 bg-[#3b82f5] opacity-20 rounded-tr-full -ml-8 -mb-8">
                        </div>
                        <div class="relative flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Total Revenue</p>
                                <p class="text-3xl font-bold text-white mt-2">£
                                    <?= number_format($total_revenue, 2)?>
                                </p>
                            </div>
                            <div
                                class="w-12 h-12 bg-gray-800 border border-gray-700 rounded-xl flex items-center justify-center z-10 transition-transform group-hover:-translate-y-1">
                                <svg class="w-6 h-6 text-[#3b82f5]" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="flex items-center gap-4 mb-6 border-b border-gray-200 px-2 lg:px-0">
                    <a href="?view=active"
                        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors <?= $view !== 'deleted' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' ?>">
                        All Bookings
                    </a>
                    <a href="?view=deleted"
                        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors flex items-center gap-2 <?= $view === 'deleted' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                        Deleted
                    </a>
                </div>

                <!-- Search and Filters -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6 relative z-10">
                    <form method="GET" id="filterForm" class="contents">
                        <!-- Top Bar: Icons + Search -->
                        <div class="flex items-center justify-between p-4">
                            <div class="flex items-center gap-2">
                                <!-- Filter Toggle Button -->
                                <button type="button" id="filterToggleBtn" onclick="toggleFilters()"
                                    class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M3 7H21"></path>
                                        <path d="M6 12H18"></path>
                                        <path d="M10 17H14"></path>
                                    </svg>
                                </button>

                                <!-- Search Toggle Icon (shown when search is collapsed) -->
                                <button type="button" id="searchToggleBtn" onclick="toggleSearch()"
                                    class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </button>

                                <!-- Expanded Search Input (hidden by default) -->
                                <div id="searchInputWrapper" class="hidden flex-1 relative">
                                    <div class="flex items-center w-full">
                                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                        <input type="text" name="search" value="<?= htmlspecialchars($search)?>"
                                            placeholder="Search"
                                            class="w-full pl-9 pr-10 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 max-w-[200px]">
                                        <button type="button" onclick="toggleSearch()"
                                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">

                                <!-- Action button (visible when items selected) -->
                                <div id="bulkActions"
                                    class="flex items-center gap-2 opacity-0 pointer-events-none transition-all duration-200">
                                    <?php if ($view === 'deleted'): ?>
                                    <button type="button" onclick="restoreSelectedBookings()"
                                        class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-blue-600 bg-blue-50 border border-blue-100 rounded-lg hover:bg-blue-100 transition-colors uppercase tracking-wider">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                            </path>
                                        </svg>
                                        Restore Selected
                                    </button>
                                    <?php
else: ?>
                                    <button type="button" onclick="deleteSelectedBookings()"
                                        class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-red-600 bg-red-50 border border-red-100 rounded-lg hover:bg-red-100 transition-colors uppercase tracking-wider">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg>
                                        Delete Selected
                                    </button>
                                    <?php
endif; ?>
                                </div>

                            </div>

                            </div>
                        </div>

                        <!-- Filter Row (hidden by default) -->
                        <div id="filterRow" class="hidden border-t border-gray-100 px-4 py-3">
                            <div class="flex flex-wrap items-center gap-3">
                                <!-- Status Filter -->
                                <select name="status" onchange="this.form.submit()"
                                    class="px-4 py-1.5 text-sm text-gray-700 border border-gray-200 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                                    <option value="">Status</option>
                                    <option value="pending" <?=$status_filter==='pending' ? 'selected' : '' ?>>Pending
                                    </option>
                                    <option value="confirmed" <?=$status_filter==='confirmed' ? 'selected' : '' ?>
                                        >Confirmed</option>
                                    <option value="active" <?=$status_filter==='active' ? 'selected' : '' ?>>Active
                                    </option>
                                    <option value="completed" <?=$status_filter==='completed' ? 'selected' : '' ?>
                                        >Completed</option>
                                    <option value="cancelled" <?=$status_filter==='cancelled' ? 'selected' : '' ?>
                                        >Cancelled</option>
                                </select>

                                <!-- Date Filter -->
                                <select name="date_filter"
                                    onchange="if(this.value === 'custom'){ openRangePicker(); } else { this.form.submit(); }"
                                    class="px-4 py-1.5 text-sm text-gray-700 border border-gray-200 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                                    <option value="">Date</option>
                                    <option value="today" <?=$date_filter==='today' ? 'selected' : '' ?>>Today</option>
                                    <option value="week" <?=$date_filter==='week' ? 'selected' : '' ?>>This Week
                                    </option>
                                    <option value="month" <?=$date_filter==='month' ? 'selected' : '' ?>>This Month
                                    </option>
                                    <option value="custom" <?=$date_filter==='custom' ? 'selected' : '' ?>>Custom Range
                                    </option>
                                </select>
                                <input type="hidden" name="date_from" id="date_from"
                                    value="<?= htmlspecialchars($date_from)?>">
                                <input type="hidden" name="date_to" id="date_to"
                                    value="<?= htmlspecialchars($date_to)?>">

                                <!-- Vehicle Filter -->
                                <select name="vehicle" onchange="this.form.submit()"
                                    class="px-4 py-1.5 text-sm text-gray-700 border border-gray-200 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                                    <option value="">Vehicle</option>
                                    <?php foreach ($vehicles as $vehicle): ?>
                                    <option value="<?= $vehicle['id']?>" <?=$vehicle_filter==$vehicle['id'] ? 'selected'
                                        : '' ?>>
                                        <?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'])?>
                                    </option>
                                    <?php
endforeach; ?>
                                </select>

                                <!-- Clear All -->
                                <a href="/dashboard/bookings.php"
                                    class="text-sm text-gray-500 hover:text-gray-800 px-3 py-1.5 hover:bg-gray-50 rounded-lg transition-colors">
                                    Clear all
                                </a>

                                <!-- Range Picker Anchors & Hidden Inputs -->
                                <div id="range_picker_anchor"
                                    class="absolute left-0 top-full mt-2 w-0 h-0 overflow-visible"></div>
                                <input type="text" id="range_picker" class="hidden">
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Active Filter Pills -->
                <?php
$active_filters = [];
if ($search)
    $active_filters['search'] = ['label' => 'Search: ' . $search, 'param' => 'search'];
if ($status_filter)
    $active_filters['status'] = ['label' => 'Status: ' . ucfirst($status_filter), 'param' => 'status'];
if ($date_filter) {
    $date_label = 'Date: ';
    if ($date_filter === 'today')
        $date_label .= 'Today';
    elseif ($date_filter === 'week')
        $date_label .= 'This Week';
    elseif ($date_filter === 'month')
        $date_label .= 'This Month';
    $active_filters['date_filter'] = ['label' => $date_label, 'param' => 'date_filter'];
}
if ($vehicle_filter) {
    $v_name = 'Vehicle';
    foreach ($vehicles as $v) {
        if ($v['id'] == $vehicle_filter) {
            $v_name = $v['brand'] . ' ' . $v['model'];
            break;
        }
    }
    $active_filters['vehicle'] = ['label' => 'Vehicle: ' . $v_name, 'param' => 'vehicle'];
}
?>

                <?php if (!empty($active_filters)): ?>
                <div class="flex flex-wrap items-center gap-2 mb-6">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mr-1">Active
                        Filters:</span>
                    <?php foreach ($active_filters as $key => $filter): ?>
                    <div
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-200 rounded-full text-[11px] font-semibold text-gray-700 shadow-sm transition-all hover:border-gray-300">
                        <span>
                            <?= htmlspecialchars($filter['label'])?>
                        </span>
                        <button type="button" onclick="clearFilter('<?= $filter['param']?>')"
                            class="p-0.5 hover:bg-gray-100 rounded-full transition-colors group">
                            <svg class="w-3 h-3 text-gray-400 group-hover:text-red-500" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <?php
    endforeach; ?>
                    <a href="/dashboard/bookings.php"
                        class="text-[11px] font-bold text-blue-600 hover:text-blue-800 ml-2 uppercase tracking-tight">Clear
                        all</a>
                </div>
                <?php
endif; ?>

                <!-- Custom Select & Filter JS -->
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const form = document.getElementById('filterForm');
                        // Toggle filter row
                        window.toggleFilters = function () {
                            const row = document.getElementById('filterRow');
                            const btn = document.getElementById('filterToggleBtn');
                            if (row) row.classList.toggle('hidden');
                            if (btn) {
                                btn.classList.toggle('bg-gray-100');
                                btn.classList.toggle('text-gray-700');
                            }
                        };

                        // Toggle search input
                        window.toggleSearch = function () {
                            const wrapper = document.getElementById('searchInputWrapper');
                            const btn = document.getElementById('searchToggleBtn');
                            if (!wrapper || !btn) return;

                            const isHidden = wrapper.classList.contains('hidden');
                            wrapper.classList.toggle('hidden');
                            btn.classList.toggle('hidden');

                            if (isHidden) {
                                const input = wrapper.querySelector('input[name="search"]');
                                if (input) input.focus();
                            }
                        };
                        // Close search on outside click
                        document.addEventListener('click', (e) => {
                            const searchWrapper = document.getElementById('searchInputWrapper');
                            const searchBtn = document.getElementById('searchToggleBtn');
                            if (searchWrapper && !searchWrapper.contains(e.target) && !searchBtn.contains(e.target)) {
                                if (!searchWrapper.classList.contains('hidden')) {
                                    toggleSearch();
                                }
                            }
                        });


                        // Clear Individual Filter
                        window.clearFilter = function (param) {
                            const url = new URL(window.location.href);
                            url.searchParams.delete(param);
                            // Also clear related date params if clearing date_filter
                            if (param === 'date_filter') {
                                url.searchParams.delete('date_from');
                                url.searchParams.delete('date_to');
                            }
                            window.location.href = url.toString();
                        };

                        // Flatpickr for Custom Date Range
                        const fp = flatpickr("#range_picker", {
                            mode: "range",
                            appendTo: document.getElementById('range_picker_anchor'),
                            static: true,
                            onChange: function (selectedDates, dateStr, instance) {
                                if (selectedDates.length === 2) {
                                    const dateFrom = selectedDates[0].toISOString().split('T')[0];
                                    const dateTo = selectedDates[1].toISOString().split('T')[0];

                                    document.getElementById('date_from').value = dateFrom;
                                    document.getElementById('date_to').value = dateTo;
                                    document.querySelector('select[name="date_filter"]').value = 'custom';

                                    // Submit form after small delay
                                    setTimeout(() => {
                                        form.submit();
                                    }, 100);
                                }
                            }
                        });

                        window.openRangePicker = function () {
                            fp.open();
                        };

                        // Bulk selection and deletion logic
                        const selectAll = document.getElementById('selectAllCheckbox');
                        const checkboxes = document.querySelectorAll('.booking-checkbox');
                        const bulkActions = document.getElementById('bulkActions');

                        const updateBulkActionsVisibility = () => {
                            const selectedCount = document.querySelectorAll('.booking-checkbox:enabled:checked').length;
                            if (selectedCount > 0) {
                                bulkActions.classList.remove('opacity-0', 'pointer-events-none');
                            } else {
                                bulkActions.classList.add('opacity-0', 'pointer-events-none');
                            }
                        };

                        if (selectAll) {
                            selectAll.addEventListener('change', () => {
                                document.querySelectorAll('.booking-checkbox:enabled').forEach(cb => cb.checked = selectAll.checked);
                                updateBulkActionsVisibility();
                            });
                        }

                        checkboxes.forEach(cb => {
                            cb.addEventListener('change', () => {
                                const enabledCheckboxes = Array.from(document.querySelectorAll('.booking-checkbox:enabled'));
                                const allChecked = enabledCheckboxes.length > 0 && enabledCheckboxes.every(c => c.checked);
                                const someChecked = enabledCheckboxes.some(c => c.checked);
                                if (selectAll) {
                                    selectAll.checked = allChecked;
                                    selectAll.indeterminate = someChecked && !allChecked;
                                }
                                updateBulkActionsVisibility();
                            });
                        });

                        window.deleteSelectedBookings = function () {
                            const selectedIds = Array.from(document.querySelectorAll('.booking-checkbox:enabled:checked'))
                                .map(cb => cb.value);

                            if (selectedIds.length === 0) return;

                            showConfirmation(
                                'Delete Bookings',
                                `Are you sure you want to move ${selectedIds.length} selected booking(s) to deleted?`,
                                () => {
                                    fetch('/dashboard/delete-booking.php', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                        },
                                        body: JSON.stringify({ booking_ids: selectedIds })
                                    })
                                        .then(response => response.json())
                                        .then(data => {
                                            if (data.success) {
                                                showNotification(`${selectedIds.length} booking(s) moved to deleted`);
                                                setTimeout(() => window.location.reload(), 1000);
                                            } else {
                                                showNotification(data.message || 'Error deleting bookings', 'error');
                                            }
                                        })
                                        .catch(error => {
                                            console.error('Error:', error);
                                            showNotification('An error occurred while deleting bookings', 'error');
                                        });
                                },
                                'Delete Selected',
                                'bg-red-600 hover:bg-red-700'
                            );
                        };

                        window.restoreSelectedBookings = function () {
                            const selectedIds = Array.from(document.querySelectorAll('.booking-checkbox:enabled:checked'))
                                .map(cb => cb.value);

                            if (selectedIds.length === 0) return;

                            showConfirmation(
                                'Restore Bookings',
                                `Are you sure you want to restore ${selectedIds.length} selected booking(s)?`,
                                () => {
                                    fetch('/dashboard/restore-booking.php', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                        },
                                        body: JSON.stringify({ booking_ids: selectedIds })
                                    })
                                        .then(response => response.json())
                                        .then(data => {
                                            if (data.success) {
                                                showNotification(`${selectedIds.length} booking(s) restored successfully`);
                                                setTimeout(() => window.location.reload(), 1000);
                                            } else {
                                                showNotification(data.message || 'Error restoring bookings', 'error');
                                            }
                                        })
                                        .catch(error => {
                                            console.error('Error:', error);
                                            showNotification('An error occurred while restoring bookings', 'error');
                                        });
                                },
                                'Restore Selected',
                                'bg-blue-600 hover:bg-blue-700'
                            );
                        };
                    });
                </script>

                <!-- Bookings Table -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-6 py-3 text-left">
                                        <input type="checkbox" id="selectAllCheckbox"
                                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer">
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Booking ID</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Customer</th>
                                    <th
                                        class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Vehicle</th>
                                    <th
                                        class="hidden sm:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Total</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                            </path>
                                        </svg>
                                        <h3 class="text-lg font-semibold text-gray-900 mb-1">No bookings found</h3>
                                        <p class="text-gray-600">Try adjusting your search or filters</p>
                                    </td>
                                </tr>
                                <?php
else: ?>
                                <?php foreach ($bookings as $booking): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <?php
        $can_delete = ($view === 'deleted' || $booking['status'] === 'cancelled');
?>
                                        <input type="checkbox" name="booking_ids[]" value="<?= $booking['id']?>"
                                            class="booking-checkbox w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer <?=!$can_delete ? 'opacity-20 cursor-not-allowed' : ''?>"
                                            <?=!$can_delete ? 'disabled' : '' ?>>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">#
                                            <?= str_pad($booking['id'], 5, '0', STR_PAD_LEFT)?>
                                        </div>
                                        <div class="hidden sm:block text-xs text-gray-500">
                                            <?= date('M d, Y', strtotime($booking['created_at']))?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($booking['customer_name'])?>
                                        </div>
                                        <div class="hidden md:block text-xs text-gray-500">
                                            <?= htmlspecialchars($booking['customer_email'])?>
                                        </div>
                                        <?php if ($booking['customer_license']): ?>
                                        <div class="hidden lg:block text-xs text-gray-500">Licence:
                                            <?= htmlspecialchars($booking['customer_license'])?>
                                        </div>
                                        <?php
        endif; ?>
                                    </td>
                                    <td class="hidden md:table-cell px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($booking['brand'] . ' ' . $booking['model'])?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?= htmlspecialchars($booking['year'])?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?= htmlspecialchars(ucfirst($booking['category']))?>
                                        </div>
                                    </td>
                                    <td class="hidden sm:table-cell px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-gray-900">£
                                            <?= number_format($booking['total_price'], 2)?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?= $booking['total_days']?> day
                                            <?= $booking['total_days'] > 1 ? 's' : ''?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap space-y-1">
                                        <div class="flex flex-col gap-1.5">
                                            <span
                                                class="status-badge status-<?= $booking['status']?> inline-block w-fit">
                                                <?= ucfirst($booking['status'])?>
                                            </span>
                                            <?php if ($booking['payment_status'] === 'paid'): ?>
                                            <span
                                                class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-800 w-fit uppercase tracking-tighter">
                                                Paid
                                            </span>
                                            <?php
        elseif ($booking['payment_status'] === 'refunded'): ?>
                                            <span
                                                class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 w-fit uppercase tracking-tighter shadow-sm border border-amber-200">
                                                Refunded
                                            </span>
                                            <?php
        endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <button onclick="viewBooking(<?= $booking['id']?>)"
                                            class="text-blue-600 hover:text-blue-800 font-medium">
                                            View Details
                                        </button>
                                    </td>
                                </tr>
                                <?php
    endforeach; ?>
                                <?php
endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Booking Details Modal -->
        <div id="bookingModal"
            class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white">
                    <h3 class="text-xl font-bold text-gray-900">Booking Details</h3>
                    <button onclick="closeBookingModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div id="bookingModalContent" class="p-6">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>

        <script>
            const currentView = '<?php echo $view; ?>';
            const userRole = '<?php echo $_SESSION['role'] ?? 'staff'; ?>';

            function viewBooking(id) {
                openBookingModal(id);
            }

            function updateSecurityDeposit(bookingId) {
                const amount = document.getElementById('deposit-amount').value;
                const status = document.getElementById('deposit-status').value;
                const method = document.getElementById('deposit-method').value;

                fetch('/dashboard/update-booking-deposit.php', {
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
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(err => showNotification('Error updating deposit', 'error'));
            }

            function openBookingModal(bookingId, initialTab = 'details') {
                const modal = document.getElementById('bookingModal');
                const modalContentWrapper = document.getElementById('bookingModalContent');

                modal.classList.remove('hidden');

                modalContentWrapper.innerHTML = `
        <div class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>
    `;

                fetch('/dashboard/get-booking-details.php?id=' + bookingId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            displayBookingDetails(data.booking, data.condition_reports, data.contract, data.available_templates);
                            if (initialTab !== 'details') switchModalTab(initialTab);
                        } else {
                            modalContentWrapper.innerHTML = `
                    <div class="text-center py-12">
                        <p class="text-red-600">${data.message || 'Failed to load booking details'}</p>
                    </div>
                `;
                        }
                    })
                    .catch(error => {
                        modalContentWrapper.innerHTML = `
                <div class="text-center py-12">
                    <p class="text-red-600">Error loading booking details</p>
                </div>
            `;
                    });
            }

            function displayBookingDetails(booking, reports, contract, available_templates = []) {
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

                const modalTarget = document.getElementById('bookingModalContent');
                modalTarget.innerHTML = `
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

        <div id="tab-content-details" class="tab-pane active space-y-8 animate-fade-in-up">
            <div class="grid md:grid-cols-12 gap-6">
                <!-- Left Column: Details -->
                <div class="md:col-span-8 space-y-6">
                    <div class="bg-white rounded-[2rem] p-8 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(0,0,0,0.03)]">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Ref</span>
                                <p class="text-xl font-black text-gray-900 leading-none">#${String(booking.id).padStart(5, '0')}</p>
                            </div>
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

                        <div class="mt-6 grid grid-cols-3 gap-4 pt-6 border-t border-gray-50">
                            <div>
                                <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest mb-1">Price / Day</p>
                                <p class="text-sm font-black text-gray-900">£${parseFloat(booking.price_per_day).toLocaleString()}</p>
                            </div>
                            <div>
                                <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest mb-1">Tax Amount</p>
                                <p class="text-sm font-black text-gray-900">£${parseFloat(booking.tax_amount || 0).toLocaleString()}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[8px] font-black text-blue-500 uppercase tracking-widest mb-1">Grand Total</p>
                                <p class="text-sm font-black text-blue-600">£${parseFloat(booking.total_price).toLocaleString()}</p>
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

                    ${booking.notes ? `
                        <div class="bg-amber-50/50 p-6 rounded-3xl border border-amber-100 group shadow-sm">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2.5" /></svg>
                                <p class="text-[9px] font-black text-amber-500 uppercase tracking-widest">Internal Notes</p>
                            </div>
                            <p class="text-xs text-amber-900/80 font-bold italic leading-relaxed">"${booking.notes}"</p>
                        </div>
                    ` : ''}
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
            <div class="flex items-center justify-between">
                <h4 class="text-xs font-black text-gray-900 uppercase tracking-tighter">Condition Report</h4>
                <div class="flex p-1 bg-gray-100 rounded-xl">
                    <button type="button" onclick="switchConditionTab('pickup')" id="tab-pickup-btn" class="px-4 py-1.5 text-[10px] font-black rounded-lg transition-all bg-white shadow-sm text-blue-600 uppercase">Pickup</button>
                    <button type="button" onclick="switchConditionTab('return')" id="tab-return-btn" class="px-4 py-1.5 text-[10px] font-black rounded-lg transition-all text-gray-500 hover:text-gray-700 uppercase">Return</button>
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

                        <button type="submit" class="w-full py-4 bg-blue-600 text-white rounded-3xl font-black text-xs uppercase tracking-[0.2em] shadow-2xl shadow-blue-200 hover:scale-[1.02] transition-all">Finalize Return Condition</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="tab-content-contract" class="tab-pane hidden space-y-6">
            <div class="bg-gray-50 rounded-3xl p-8 border border-gray-100">
                ${contract ? `
                    <div class="flex items-center justify-between mb-8 pb-8 border-b border-gray-200">
                        <div class="flex items-center gap-5">
                            <div class="w-16 h-16 bg-white rounded-2xl shadow-xl border border-gray-100 flex items-center justify-center">
                                <svg class="w-8 h-8 ${contract.contract_status === 'signed' ? 'text-green-500' : 'text-amber-500'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="2.5" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-base font-black text-gray-900 uppercase tracking-tighter">Agreement</h4>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="w-2.5 h-2.5 rounded-full ${contract.contract_status === 'signed' ? 'bg-green-500' : 'bg-amber-500 animate-pulse'}"></span>
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                        Status: <span class="${contract.contract_status === 'signed' ? 'text-green-600' : 'text-amber-600'}">${contract.contract_status.toUpperCase()}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button onclick="viewContractPDF(${booking.id})" class="flex items-center gap-2 px-6 py-3 bg-white border border-gray-200 text-gray-900 rounded-2xl hover:bg-gray-50 transition-all text-xs font-black uppercase tracking-widest shadow-sm group">
                                <svg class="w-4 h-4 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="2.5"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-width="2.5"/></svg>
                                View Agreement
                            </button>
                            ${contract.contract_status === 'signed' ? `
                                <a href="/api/download-contract.php?booking_id=${booking.id}" target="_blank" class="flex items-center gap-2 px-6 py-3 bg-gray-900 text-white rounded-2xl hover:bg-black transition-all text-xs font-black uppercase tracking-widest shadow-2xl shadow-gray-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 4v12m0 0l-4-4m4 4l4-4" stroke-width="2.5" /></svg>
                                    Export PDF
                                </a>
                            ` : `
                                <div class="px-5 py-2.5 bg-amber-50 text-amber-600 rounded-xl border border-amber-200 text-[10px] font-black uppercase tracking-widest">Awaiting Signature</div>
                            `}
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
                            <div>
                                <p class="text-[9px] text-gray-400 font-black uppercase tracking-[0.2em] mb-1">Execution Detail</p>
                                <p class="text-xs font-bold text-gray-900">${contract.contract_status === 'signed' ? 'Signed successfully' : 'Invitation sent to client'}</p>
                            </div>
                        </div>

                        ${contract.contract_status !== 'signed' ? `
                        <div class="p-6 bg-blue-50/50 rounded-2xl border border-blue-100">
                            <p class="text-xs text-blue-900 font-black uppercase tracking-[0.1em] mb-3">Quick Link</p>
                            <div class="flex items-center gap-3">
                                <input type="text" value="${window.location.origin}/templates/contract-sign.php?booking_id=${booking.id}&token=${contract.signing_token}" readonly 
                                       class="flex-1 bg-white border border-blue-100 rounded-xl px-4 py-3 text-[10px] text-gray-500 font-mono shadow-sm">
                                <button onclick="copyContractLink(this)" class="p-3.5 bg-blue-600 text-white hover:bg-blue-700 rounded-xl transition-all shadow-xl shadow-blue-200">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" stroke-width="2.5" /></svg>
                                </button>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                ` : `
                    <div class="text-center py-12">
                        <div class="w-24 h-24 bg-gray-100 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-inner">
                            <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="2" /></svg>
                        </div>
                        <h4 class="text-lg font-black text-gray-900 uppercase tracking-tighter mb-2">No Active Contract</h4>
                        <p class="text-xs text-gray-400 mb-10 max-w-[280px] mx-auto font-bold leading-relaxed">This booking does not have an electronic agreement. you can send one manually below.</p>
                        
                        ${available_templates && available_templates.length > 0 ? `
                            <div class="max-w-xs mx-auto space-y-5">
                                <div class="text-left bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Template Selection</label>
                                    <select id="selected-template-id" class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-xs font-bold focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none">
                                        ${available_templates.map(tmpl => `<option value="${tmpl.id}">${tmpl.name}</option>`).join('')}
                                    </select>
                                </div>
                                <button onclick="generateManualContract(${booking.id})" id="send-contract-btn" class="w-full py-4 bg-gray-900 text-white rounded-2xl hover:bg-black transition-all text-xs font-black uppercase tracking-[0.2em] shadow-2xl shadow-gray-200 flex items-center justify-center gap-3">
                                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                     Send Invitation
                                </button>
                            </div>
                        ` : `
                            <div class="bg-amber-50 p-6 rounded-2xl border border-amber-100 font-bold text-amber-700 text-xs italic">No published templates found.</div>
                        `}
                    </div>
                `}
            </div>
        </div>
    `;

                // Initialize custom selects for dynamically injected content
                if (window.initCustomSelects) {
                    setTimeout(() => window.initCustomSelects(), 0);
                }
            }

            function switchModalTab(tabId) {
                // Hide all tab content
                document.querySelectorAll('.tab-pane').forEach(el => {
                    el.classList.add('hidden');
                    el.classList.remove('animate-fade-in-up');
                });

                // Show selected tab content
                const target = document.getElementById('tab-content-' + tabId);
                target.classList.remove('hidden');
                target.classList.add('animate-fade-in-up');

                // Reset all tab buttons
                document.querySelectorAll('[id^="tab-btn-"]').forEach(btn => {
                    btn.classList.add('text-[#4b5058]', 'hover:text-black');
                    btn.classList.remove('bg-white', 'shadow-sm', 'text-blue-600');
                });

                // Highlight active button
                const activeBtn = document.getElementById('tab-btn-' + tabId);
                activeBtn.classList.remove('text-[#4b5058]', 'hover:text-black');
                activeBtn.classList.add('bg-white', 'shadow-sm', 'text-blue-600');
            }

            function copyContractLink(btn) {
                const input = btn.previousElementSibling;
                input.select();
                document.execCommand('copy');

                const originalSvg = btn.innerHTML;
                btn.innerHTML = '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="3" /></svg>';
                btn.classList.remove('bg-blue-600');
                btn.classList.add('bg-green-500');

                setTimeout(() => {
                    btn.innerHTML = originalSvg;
                    btn.classList.remove('bg-green-500');
                    btn.classList.add('bg-blue-600');
                }, 2000);
            }


            function closeBookingModal() {
                document.getElementById('bookingModal').classList.add('hidden');
            }

            async function generateManualContract(bookingId) {
                const templateId = document.getElementById('selected-template-id').value;
                const btn = document.getElementById('send-contract-btn');

                if (!templateId) {
                    showNotification('Please select a template', 'error');
                    return;
                }

                const originalContent = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div> Sending...';

                try {
                    const response = await fetch('/dashboard/generate-manual-contract.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ booking_id: bookingId, template_id: templateId })
                    });

                    const data = await response.json();

                    if (data.success) {
                        showNotification(data.message, 'success');
                        openBookingModal(bookingId, 'contract');
                    } else {
                        showNotification(data.message, 'error');
                        btn.disabled = false;
                        btn.innerHTML = originalContent;
                    }
                } catch (error) {
                    showNotification('Failed to generate contract', 'error');
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                }
            }

            function updateBookingStatus(bookingId, status) {
                showConfirmation(
                    'Update Booking Status',
                    'Are you sure you want to update this booking status?',
                    () => {
                        fetch('/dashboard/update-booking-status.php', {
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

            function deleteSingleBooking(bookingId) {
                showConfirmation(
                    'Delete Booking',
                    'Are you sure you want to move this booking to deleted? You can restore it later from the Deleted view.',
                    () => {
                        fetch('/dashboard/delete-booking.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ booking_ids: [bookingId] })
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    showNotification('Booking moved to deleted');
                                    closeBookingModal();
                                    setTimeout(() => window.location.reload(), 1000);
                                } else {
                                    showNotification(data.message || 'Error deleting booking', 'error');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                showNotification('An error occurred while deleting the booking', 'error');
                            });
                    },
                    'Delete Booking',
                    'bg-red-600 hover:bg-red-700'
                );
            }

            function restoreSingleBooking(bookingId) {
                showConfirmation(
                    'Restore Booking',
                    'Are you sure you want to restore this booking to the active list?',
                    () => {
                        fetch('/dashboard/restore-booking.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ booking_ids: [bookingId] })
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    showNotification('Booking restored successfully');
                                    closeBookingModal();
                                    setTimeout(() => window.location.reload(), 1000);
                                } else {
                                    showNotification(data.message || 'Error restoring booking', 'error');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                showNotification('An error occurred while restoring the booking', 'error');
                            });
                    },
                    'Restore Booking',
                    'bg-blue-600 hover:bg-blue-700'
                );
            }

            document.getElementById('bookingModal').addEventListener('click', function (e) {
                if (e.target === this) {
                    closeBookingModal();
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closeBookingModal();
                }
            });

            // Condition Report Helpers
            function switchConditionTab(type) {
                const pickupBtn = document.getElementById('tab-pickup-btn');
                const returnBtn = document.getElementById('tab-return-btn');
                const pickupSec = document.getElementById('section-pickup');
                const returnSec = document.getElementById('section-return');

                const activeClass = 'bg-white shadow-sm text-blue-600';
                const inactiveClass = 'text-[#4b5058] hover:text-black';

                // Remove animation from both first
                pickupSec.classList.remove('animate-fade-in-up');
                returnSec.classList.remove('animate-fade-in-up');

                if (type === 'pickup') {
                    pickupBtn.className = `flex-1 py-1.5 text-[10px] font-black rounded-lg transition-all ${activeClass} uppercase`;
                    returnBtn.className = `flex-1 py-1.5 text-[10px] font-black rounded-lg transition-all ${inactiveClass} uppercase`;
                    pickupSec.classList.remove('hidden');
                    returnSec.classList.add('hidden');
                    void pickupSec.offsetWidth; // Trigger reflow
                    pickupSec.classList.add('animate-fade-in-up');
                } else {
                    returnBtn.className = `flex-1 py-1.5 text-[10px] font-black rounded-lg transition-all ${activeClass} uppercase`;
                    pickupBtn.className = `flex-1 py-1.5 text-[10px] font-black rounded-lg transition-all ${inactiveClass} uppercase`;
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
                        // Update wrapper state for validation
                        const wrapper = container.closest('.condition-photo-wrapper');
                        if (wrapper) wrapper.dataset.hasPhoto = 'true';
                    };
                    reader.readAsDataURL(input.files[0]);
                }
            }

            function previewMiscPhotos(input, type) {
                const container = document.getElementById(`misc-${type}-preview`);
                if (!container) return;

                // Clear and create placeholder array
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

            function showValidationError(message) {
                const overlay = document.createElement('div');
                overlay.className = 'fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4';
                overlay.innerHTML = `
        <div class="bg-white rounded-2xl max-w-sm w-full shadow-2xl overflow-hidden transform transition-all">
            <div class="p-6 text-center">
                <div class="w-16 h-16 bg-red-50 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Upload Required</h3>
                <p class="text-gray-600 text-sm mb-6">${message}</p>
                <button onclick="this.closest('.fixed').remove()" class="w-full py-3 bg-gray-900 text-white rounded-xl font-bold hover:bg-black transition-all">Got it</button>
            </div>
        </div>
    `;
                document.body.appendChild(overlay);
            }

            function submitConditionReport(event, type, bookingId) {
                event.preventDefault();
                const form = event.target;

                // Check mandatory photos
                if (type === 'pickup') {
                    const photoFields = ['photo_front', 'photo_back', 'photo_left', 'photo_right', 'photo_rim1', 'photo_rim2', 'photo_rim3', 'photo_rim4'];
                    let missing = [];
                    photoFields.forEach(field => {
                        const wrapper = form.querySelector(`.condition-photo-wrapper[data-id="${field}"]`);
                        const input = form.querySelector(`input[name="${field}"]`);
                        const hasExisting = wrapper && wrapper.dataset.hasPhoto === 'true';
                        const hasNew = input && input.files && input.files.length > 0;

                        if (!hasExisting && !hasNew) {
                            missing.push(field.replace('photo_', '').replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase()));
                        }
                    });

                    if (missing.length > 0) {
                        showValidationError('Please upload all mandatory photos: ' + missing.join(', ') + '. <br><br>Front, Back, Sides and 4 Rims are required.');
                        return;
                    }
                }

                const formData = new FormData(form);
                formData.append('booking_id', bookingId);
                formData.append('report_type', type);

                const btn = form.querySelector('button[type="submit"]');
                const originalText = btn.innerText;
                btn.disabled = true;
                btn.innerText = 'Saving...';

                fetch('/dashboard/upload-condition-report.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(async response => {
                        const text = await response.text();
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error("Server Error: " + text);
                        }
                    })
                    .then(data => {
                        if (data.success) {
                            showStatusToast(data.message || 'Report saved successfully');
                            openBookingModal(bookingId);
                        } else {
                            showNotification(data.message || 'Failed to save report', 'error');
                            btn.disabled = false;
                            btn.innerText = originalText;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Upload failed: ' + error.message, 'error');
                        btn.disabled = false;
                        btn.innerText = originalText;
                    });
            }

            function showStatusToast(message) {
                const toast = document.createElement('div');
                toast.className = 'fixed top-6 left-1/2 -translate-x-1/2 bg-gray-900 text-white px-6 py-3 rounded-xl shadow-2xl z-[100] font-bold text-sm tracking-wide transition-all duration-500 opacity-0 -translate-y-4';
                toast.innerText = message;
                document.body.appendChild(toast);

                setTimeout(() => {
                    toast.classList.remove('opacity-0', '-translate-y-4');
                }, 10);

                setTimeout(() => {
                    toast.classList.add('opacity-0', '-translate-y-4');
                    setTimeout(() => toast.remove(), 500);
                }, 3000);
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