<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    redirect('/auth/login.php');
}

$pdo = getDB();

// Add is_deleted column if it doesn't exist
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_deleted'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_deleted TINYINT(1) DEFAULT 0");
    }
}
catch (Exception $e) {
}

// Handle customer actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $email = sanitize($_POST['email'] ?? '');
    $action = $_POST['action'];

    if ($email) {
        if ($action === 'soft_delete') {
            // Ensure customer exists in users table first
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND tenant_id = ?");
            $stmt->execute([$email, $_SESSION['tenant_id']]);
            if (!$stmt->fetch()) {
                // Get name from bookings if possible
                $stmt_name = $pdo->prepare("SELECT customer_name, customer_phone FROM bookings WHERE customer_email = ? AND tenant_id = ? LIMIT 1");
                $stmt_name->execute([$email, $_SESSION['tenant_id']]);
                $b_info = $stmt_name->fetch();
                $name = $b_info['customer_name'] ?? $email;
                $phone = $b_info['customer_phone'] ?? '';

                $stmt_ins = $pdo->prepare("INSERT INTO users (tenant_id, role, email, full_name, phone, password, is_deleted) VALUES (?, 'customer', ?, ?, ?, 'legacy_account', 1)");
                $stmt_ins->execute([$_SESSION['tenant_id'], $email, $name, $phone]);
            }
            else {
                $stmt = $pdo->prepare("UPDATE users SET is_deleted = 1 WHERE email = ? AND tenant_id = ?");
                $stmt->execute([$email, $_SESSION['tenant_id']]);
            }
            header('Location: /dashboard/customers.php?success=deleted');
            exit;
        }
        elseif ($action === 'restore') {
            $stmt = $pdo->prepare("UPDATE users SET is_deleted = 0 WHERE email = ? AND tenant_id = ?");
            $stmt->execute([$email, $_SESSION['tenant_id']]);
            header('Location: /dashboard/customers.php?tab=deleted&success=restored');
            exit;
        }
        elseif ($action === 'permanent_delete') {
            // First find the user ID
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND tenant_id = ?");
            $stmt->execute([$email, $_SESSION['tenant_id']]);
            $user = $stmt->fetch();

            if ($user) {
                // Check if they have bookings - if so, we shouldn't really delete them from DB due to referential integrity
                // But for now, we just delete the user record.
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$user['id'], $_SESSION['tenant_id']]);
                header('Location: /dashboard/customers.php?tab=deleted&success=permanent');
                exit;
            }
        }
    }
}

// Handle add customer form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $license = sanitize($_POST['license'] ?? '');

    if ($full_name && $email) {
        try {
            // Check if user already exists on this tenant
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND tenant_id = ?");
            $stmt->execute([$email, $_SESSION['tenant_id']]);
            $existing_user = $stmt->fetch();

            if (!$existing_user) {
                // Check if user exists on ANOTHER tenant (due to global unique constraint)
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = "This email is already associated with another rental company. Customers can only be assigned to one tenant.";
                }
                else {
                    // Insert into users table
                    $stmt = $pdo->prepare("INSERT INTO users (tenant_id, role, email, password, full_name, phone) VALUES (?, 'customer', ?, ?, ?, ?)");
                    // Generate a random password for now
                    $password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
                    $stmt->execute([$_SESSION['tenant_id'], $email, $password, $full_name, $phone]);
                    $user_id = $pdo->lastInsertId();
                }
            }
            else {
                $user_id = $existing_user['id'];
                // Update user details if they already exist
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
                $stmt->execute([$full_name, $phone, $user_id]);
            }

            // Redirect to the same page or the view page
            header('Location: /dashboard/customers.php?view=' . urlencode($email) . '&success=1');
            exit;
        }
        catch (PDOException $e) {
            $error = "Error adding customer: " . $e->getMessage();
        }
    }
    else {
        $error = "Name and Email are required.";
    }
}

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

// Get customer statistics - excluding deleted (separate queries to avoid collation conflicts)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE tenant_id = ? AND role = 'customer' AND is_deleted = 0");
$stmt->execute([$_SESSION['tenant_id']]);
$total_customers = $stmt->fetchColumn();

// Get active rentals count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE tenant_id = ? AND status IN ('confirmed', 'active')");
$stmt->execute([$_SESSION['tenant_id']]);
$active_rentals = $stmt->fetchColumn();

// Get new customers this month - exclude deleted
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM users 
    WHERE tenant_id = ? AND role = 'customer' AND is_deleted = 0
    AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
");
$stmt->execute([$_SESSION['tenant_id']]);
$new_this_month = $stmt->fetchColumn();

// Check if viewing a specific customer
$view_customer = isset($_GET['view']) ? $_GET['view'] : null;
$customer_detail = null;
$customer_bookings = [];

if ($view_customer) {
    // Get customer details
    $stmt = $pdo->prepare("
        SELECT 
            ANY_VALUE(COALESCE(b.customer_name, u.full_name)) as full_name,
            u.email as email,
            ANY_VALUE(COALESCE(b.customer_phone, u.phone)) as phone,
            ANY_VALUE(b.customer_license) as license_number,
            COALESCE(MIN(b.created_at), u.created_at) as created_at,
            COUNT(b.id) as total_rentals,
            MAX(b.created_at) as last_rental_date
        FROM users u
        LEFT JOIN bookings b ON u.email COLLATE utf8mb4_unicode_ci = b.customer_email COLLATE utf8mb4_unicode_ci AND u.tenant_id = b.tenant_id
        WHERE u.tenant_id = ? AND u.email = ? AND u.role = 'customer'
        GROUP BY u.email, u.created_at
    ");
    $stmt->execute([$_SESSION['tenant_id'], $view_customer]);
    $customer_detail = $stmt->fetch();

    // Fallback if not in users table (legacy bookings)
    if (!$customer_detail) {
        $stmt = $pdo->prepare("
            SELECT 
                customer_name as full_name,
                customer_email as email,
                customer_phone as phone,
                customer_license as license_number,
                MIN(created_at) as created_at,
                COUNT(*) as total_rentals,
                MAX(created_at) as last_rental_date
            FROM bookings 
            WHERE tenant_id = ? AND customer_email = ?
            GROUP BY customer_email, customer_name, customer_phone, customer_license
        ");
        $stmt->execute([$_SESSION['tenant_id'], $view_customer]);
        $customer_detail = $stmt->fetch();
    }

    // Get verification data
    $verification_data = null;
    $stmt = $pdo->prepare("
        SELECT 
            verification_status,
            session_id,
            first_name,
            last_name,
            license_number,
            dob,
            address,
            date_of_issue,
            expiration_date,
            verified_at
        FROM customer_verifications
        WHERE tenant_id = ? AND customer_email = ?
        ORDER BY verified_at DESC
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['tenant_id'], $view_customer]);
    $verification_data = $stmt->fetch();

    // Get customer's booking history with contract info
    $stmt = $pdo->prepare("
        SELECT b.*, v.name as vehicle_name, v.brand, v.model,
               c.id as contract_id, c.contract_status, c.signed_at, c.signing_token, c.signed_pdf_path
        FROM bookings b
        LEFT JOIN vehicles v ON b.vehicle_id = v.id
        LEFT JOIN contracts c ON c.booking_id = b.id AND c.tenant_id = b.tenant_id
        WHERE b.tenant_id = ? AND b.customer_email = ?
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$_SESSION['tenant_id'], $view_customer]);
    $customer_bookings = $stmt->fetchAll();
}

// Get current tab
$current_tab = $_GET['tab'] ?? 'all';

// Get all unique customers from bookings and users table
$where_clause = $current_tab === 'deleted' ? "u.is_deleted = 1" : "u.is_deleted = 0";

$stmt = $pdo->prepare("
    SELECT 
        ANY_VALUE(COALESCE(u.full_name, b.customer_name)) as full_name,
        unique_customers.email as email,
        ANY_VALUE(COALESCE(u.phone, b.customer_phone)) as phone,
        ANY_VALUE(b.customer_license) as license_number,
        ANY_VALUE(COALESCE(u.created_at, b.created_at)) as created_at,
        COUNT(b.id) as total_rentals,
        MAX(b.created_at) as last_rental_date,
        ANY_VALUE(u.is_deleted) as is_deleted
    FROM (
        SELECT email COLLATE utf8mb4_unicode_ci as email FROM users WHERE tenant_id = ? AND role = 'customer' " . ($current_tab === 'deleted' ? "AND is_deleted = 1" : "AND is_deleted = 0") . "
        UNION
        SELECT customer_email COLLATE utf8mb4_unicode_ci as email FROM bookings WHERE tenant_id = ?
    ) as unique_customers
    LEFT JOIN users u ON unique_customers.email = u.email COLLATE utf8mb4_unicode_ci AND u.tenant_id = ? AND u.role = 'customer'
    LEFT JOIN bookings b ON unique_customers.email = b.customer_email COLLATE utf8mb4_unicode_ci AND b.tenant_id = ?
    WHERE " . ($current_tab === 'deleted' ? "u.is_deleted = 1" : "u.is_deleted = 0 OR u.is_deleted IS NULL") . "
    GROUP BY unique_customers.email
    ORDER BY created_at DESC
");
$stmt->execute([
    $_SESSION['tenant_id'],
    $_SESSION['tenant_id'],
    $_SESSION['tenant_id'],
    $_SESSION['tenant_id']
]);
$customers = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers -
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
            <button onclick="openAddCustomerModal()"
                class="px-3 py-1.5 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-gray-800 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Customer
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
                        <span class="text-gray-900">Customers</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-gray-900">Customers</h1>
                    <p class="text-sm text-gray-600 mt-1">Manage your customer database</p>
                </div>
                <button onclick="openAddCustomerModal()"
                    class="px-4 py-2 bg-gray-900 text-white rounded-lg font-medium hover:bg-gray-800 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>Add Customer</span>
                </button>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto bg-gray-50 p-6">
            <?php if ($customer_detail): ?>
            <!-- Customer Profile View -->
            <div class="mb-6">
                <a href="/dashboard/customers.php"
                    class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-4">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7">
                        </path>
                    </svg>
                    Back to Customers
                </a>
            </div>

            <!-- Customer Info Card -->
            <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
                <div class="flex items-start justify-between mb-6">
                    <div class="flex items-center">
                        <div
                            class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                            <?= strtoupper(substr($customer_detail['full_name'] ?? $customer_detail['email'], 0, 1))?>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-2xl font-bold text-gray-900">
                                <?= htmlspecialchars($customer_detail['full_name'])?>
                            </h2>
                            <p class="text-gray-600">
                                <?= htmlspecialchars($customer_detail['email'])?>
                            </p>
                        </div>
                    </div>
                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">Active</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Phone</p>
                        <p class="font-medium text-gray-900">
                            <?= htmlspecialchars($customer_detail['phone'] ?? 'N/A')?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">License Number</p>
                        <p class="font-medium text-gray-900">
                            <?= htmlspecialchars($customer_detail['license_number'] ?? 'N/A')?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Customer Since</p>
                        <p class="font-medium text-gray-900">
                            <?= date('M d, Y', strtotime($customer_detail['created_at']))?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Rentals</p>
                        <p class="font-medium text-gray-900">
                            <?= $customer_detail['total_rentals']?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Identity Verification Card -->
            <?php if ($verification_data): ?>
            <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Identity Verification</h3>
                    <?php
        $status_colors = [
            'approved' => 'bg-green-100 text-green-800',
            'declined' => 'bg-red-100 text-red-800',
            'in_review' => 'bg-yellow-100 text-yellow-800',
            'pending' => 'bg-gray-100 text-gray-800'
        ];
        $color = $status_colors[$verification_data['verification_status']] ?? 'bg-gray-100 text-gray-800';
?>
                    <span class="px-3 py-1 <?= $color?> rounded-full text-sm font-medium">
                        <?= ucfirst($verification_data['verification_status'])?>
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Session ID</p>
                        <p class="font-medium text-gray-900 text-xs break-all">
                            <?= htmlspecialchars($verification_data['session_id'])?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Verified Name</p>
                        <p class="font-medium text-gray-900">
                            <?= htmlspecialchars(($verification_data['first_name'] ?? '') . ' ' . ($verification_data['last_name'] ?? '')) ?: 'N/A'?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Date of Birth</p>
                        <p class="font-medium text-gray-900">
                            <?= $verification_data['dob'] ? date('M d, Y', strtotime($verification_data['dob'])) : 'N/A'?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Verified At</p>
                        <p class="font-medium text-gray-900">
                            <?= $verification_data['verified_at'] ? date('M d, Y H:i', strtotime($verification_data['verified_at'])) : 'N/A'?>
                        </p>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-200">
                    <p class="text-sm text-gray-600 mb-1">Verified Address</p>
                    <p class="font-medium text-gray-900">
                        <?= htmlspecialchars($verification_data['address'] ?? 'N/A')?>
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4 pt-4 border-t border-gray-200">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Verified License Number</p>
                        <p class="font-medium text-gray-900">
                            <?= htmlspecialchars($verification_data['license_number'] ?? 'N/A')?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">License Issue Date</p>
                        <p class="font-medium text-gray-900">
                            <?= $verification_data['date_of_issue'] ? date('M d, Y', strtotime($verification_data['date_of_issue'])) : 'N/A'?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">License Expiration Date</p>
                        <p class="font-medium text-gray-900">
                            <?= $verification_data['expiration_date'] ? date('M d, Y', strtotime($verification_data['expiration_date'])) : 'N/A'?>
                        </p>
                    </div>
                </div>
            </div>
            <?php
    endif; ?>

            <!-- Booking History -->
            <div class="bg-white rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Booking History</h3>
                </div>
                <?php if (count($customer_bookings) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Booking ID
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vehicle</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pickup Date
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Return Date
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contract
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($customer_bookings as $booking): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">#
                                    <?= $booking['id']?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?= htmlspecialchars($booking['vehicle_name'] ?? ($booking['brand'] . ' ' . $booking['model']))?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?= date('M d, Y', strtotime($booking['pickup_date']))?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?= date('M d, Y', strtotime($booking['return_date']))?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
            $status_colors = [
                'pending' => 'bg-yellow-100 text-yellow-800',
                'confirmed' => 'bg-blue-100 text-blue-800',
                'active' => 'bg-green-100 text-green-800',
                'completed' => 'bg-gray-100 text-gray-800',
                'cancelled' => 'bg-red-100 text-red-800'
            ];
            $color = $status_colors[$booking['status']] ?? 'bg-gray-100 text-gray-800';
?>
                                    <span class="px-2 py-1 <?= $color?> rounded-full text-xs font-medium">
                                        <?= ucfirst($booking['status'])?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    £
                                    <?= number_format($booking['total_price'], 2)?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($booking['contract_id']): ?>
                                    <?php if ($booking['contract_status'] === 'signed'): ?>
                                    <div class="flex items-center gap-2">
                                        <button
                                            onclick="viewContract(<?= $booking['id']?>, '<?= htmlspecialchars($booking['signing_token'])?>')"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                </path>
                                            </svg>
                                            View
                                        </button>
                                        <?php if (!empty($booking['signed_pdf_path']) && file_exists($booking['signed_pdf_path'])): ?>
                                        <a href="/api/download-contract.php?booking_id=<?= $booking['id']?>"
                                            target="_blank" rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                </path>
                                            </svg>
                                            PDF
                                        </a>
                                        <?php
                    endif; ?>
                                    </div>
                                    <?php
                else: ?>
                                    <span
                                        class="px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-medium">Pending</span>
                                    <?php
                endif; ?>
                                    <?php
            else: ?>
                                    <span class="text-xs text-gray-400">—</span>
                                    <?php
            endif; ?>
                                </td>
                            </tr>
                            <?php
        endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php
    else: ?>
                <div class="text-center py-12">
                    <p class="text-gray-500">No booking history found</p>
                </div>
                <?php
    endif; ?>
            </div>

            <?php
else: ?>
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Total Customers -->
                <div
                    class="bg-white rounded-xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 p-6 relative overflow-hidden group hover:border-blue-200 transition-all duration-300">
                    <div
                        class="absolute right-0 top-0 w-24 h-24 bg-blue-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110">
                    </div>
                    <div class="relative flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Customers</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">
                                <?= $total_customers?>
                            </p>
                        </div>
                        <div
                            class="w-12 h-12 bg-white border border-gray-100 shadow-sm rounded-xl flex items-center justify-center z-10 transition-transform group-hover:-translate-y-1">
                            <!-- Users icon -->
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                </path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Active Rentals -->
                <div
                    class="bg-white rounded-xl shadow-[0_2px_10px_-3px_rgba(0,0,0,0.05)] border border-gray-100 p-6 relative overflow-hidden group hover:border-gray-900 transition-all duration-300">
                    <div
                        class="absolute right-0 top-0 w-24 h-24 bg-gray-100 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110">
                    </div>
                    <div class="relative flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Active Rentals</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">
                                <?= $active_rentals?>
                            </p>
                        </div>
                        <div
                            class="w-12 h-12 bg-white border border-gray-200 shadow-sm rounded-xl flex items-center justify-center z-10 transition-transform group-hover:-translate-y-1">
                            <!-- Checkmark/Car icon -->
                            <svg class="w-6 h-6 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- New This Month -->
                <div
                    class="bg-[#1a1f2b] rounded-xl shadow-lg p-6 relative overflow-hidden group hover:bg-black transition-all duration-300">
                    <div
                        class="absolute right-0 top-0 w-32 h-32 bg-white opacity-5 rounded-bl-full -mr-8 -mt-8 transition-transform group-hover:scale-110">
                    </div>
                    <div class="absolute left-0 bottom-0 w-24 h-24 bg-[#3b82f5] opacity-20 rounded-tr-full -ml-8 -mb-8">
                    </div>
                    <div class="relative flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">New This Month</p>
                            <p class="text-3xl font-bold text-white mt-2">
                                <?= $new_this_month?>
                            </p>
                        </div>
                        <div
                            class="w-12 h-12 bg-gray-800 border border-gray-700 rounded-xl flex items-center justify-center z-10 transition-transform group-hover:-translate-y-1">
                            <!-- Lightning Star icon -->
                            <svg class="w-6 h-6 text-[#3b82f5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success Notifications -->
            <?php if (isset($_GET['success'])): ?>
            <div
                class="mb-6 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg flex items-center gap-3">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd"></path>
                </svg>
                <span class="text-sm font-medium">
                    <?php
        if ($_GET['success'] === 'deleted')
            echo "Customer moved to bin.";
        elseif ($_GET['success'] === 'restored')
            echo "Customer restored successfully.";
        elseif ($_GET['success'] === 'permanent')
            echo "Customer deleted permanently.";
        else
            echo "Action completed successfully.";
?>
                </span>
            </div>
            <?php
    endif; ?>

            <!-- Tabs -->
            <div class="flex items-center gap-4 mb-6 border-b border-gray-200">
                <a href="/dashboard/customers.php?tab=all"
                    class="px-4 py-2 text-sm font-medium border-b-2 transition-colors <?= $current_tab === 'all' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'?>">
                    All Customers
                </a>
                <a href="/dashboard/customers.php?tab=deleted"
                    class="px-4 py-2 text-sm font-medium border-b-2 transition-colors flex items-center gap-2 <?= $current_tab === 'deleted' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                        </path>
                    </svg>
                    Deleted
                </a>
            </div>

            <!-- Search Bar -->
            <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="text" placeholder="Search customers by name, email, phone, or license number..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>

            <!-- Customers Table -->
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <?php if (count($customers) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Customer</th>
                                <th
                                    class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Contact</th>
                                <th
                                    class="hidden lg:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    License Number</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Joined Date</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total Rentals</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($customers as $customer): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div
                                            class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                                            <?= strtoupper(substr($customer['full_name'] ?? $customer['email'], 0, 1))?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($customer['full_name'] ?? 'N/A')?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?= htmlspecialchars($customer['email'])?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($customer['phone'] ?? 'N/A')?>
                                </td>
                                <td class="hidden lg:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($customer['license_number'] ?? 'N/A')?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= date('M d, Y', strtotime($customer['created_at']))?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                                        <?= $customer['total_rentals']?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center gap-3">
                                        <?php if ($current_tab === 'all'): ?>
                                        <a href="/dashboard/customers.php?view=<?= urlencode($customer['email'])?>"
                                            class="text-blue-600 hover:text-blue-900">View</a>
                                        <form id="softDeleteForm_<?= md5($customer['email'])?>" method="POST">
                                            <input type="hidden" name="email"
                                                value="<?= htmlspecialchars($customer['email'])?>">
                                            <input type="hidden" name="action" value="soft_delete">
                                            <button type="button"
                                                onclick="showConfirmation('Move to Bin', 'Are you sure you want to move this customer to the bin? You can restore them later.', () => document.getElementById('softDeleteForm_<?= md5($customer['email'])?>').submit(), 'Move to Bin', 'bg-red-600 hover:bg-red-700')"
                                                class="text-red-600 hover:text-red-900"><svg class="mt-1 w-4 h-4"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                    </path>
                                                </svg></button>
                                        </form>
                                        <?php
            else: ?>
                                        <form method="POST">
                                            <input type="hidden" name="email"
                                                value="<?= htmlspecialchars($customer['email'])?>">
                                            <input type="hidden" name="action" value="restore">
                                            <button type="submit"
                                                class="text-green-600 hover:text-green-900">Restore</button>
                                        </form>
                                        <form id="permDeleteForm_<?= md5($customer['email'])?>" method="POST">
                                            <input type="hidden" name="email"
                                                value="<?= htmlspecialchars($customer['email'])?>">
                                            <input type="hidden" name="action" value="permanent_delete">
                                            <button type="button"
                                                onclick="showConfirmation('Permanent Delete', 'Are you sure you want to PERMANENTLY delete this customer? This action cannot be undone.', () => document.getElementById('permDeleteForm_<?= md5($customer['email'])?>').submit(), 'Delete Permanently', 'bg-red-600 hover:bg-red-700')"
                                                class="text-red-600 hover:text-red-900 font-bold">Delete
                                                Permanently</button>
                                        </form>
                                        <?php
            endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php
        endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php
    else: ?>
                <!-- Empty State -->
                <div class="text-center py-16">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No customers yet</h3>
                    <p class="text-gray-600 mb-6">Add your first customer to get started</p>
                    <button onclick="openAddCustomerModal()"
                        class="px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 inline-flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                            </path>
                        </svg>
                        <span>Add Customer</span>
                    </button>
                </div>
                <?php
    endif; ?>
            </div>
            <?php
endif; ?>
        </main>
    </div>

    <!-- Contract Viewer Modal -->
    <div id="contractModal"
        class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Rental Contract</h3>
                    <p class="text-sm text-gray-500" id="contractModalSubtitle"></p>
                </div>
                <button onclick="closeContractModal()"
                    class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition-colors">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-6" id="contractModalContent">
                <div class="text-center py-12 text-gray-400">
                    <svg class="animate-spin h-8 w-8 mx-auto mb-3" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    Loading contract...
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                <div id="contractSignedInfo" class="text-sm text-gray-500"></div>
                <button onclick="closeContractModal()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Close</button>
            </div>
        </div>
    </div>

    <!-- Add Customer Modal -->
    <div id="addCustomerModal"
        class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-900">Add New Customer</h3>
                <button onclick="closeAddCustomerModal()"
                    class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition-colors">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="add_customer" value="1">
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all"
                        placeholder="John Doe">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                    <input type="email" id="email" name="email" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all"
                        placeholder="john@example.com">
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <input type="text" id="phone" name="phone"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all"
                        placeholder="+1 234 567 8900">
                </div>
                <div>
                    <label for="license" class="block text-sm font-medium text-gray-700 mb-1">Driver's License
                        Number</label>
                    <input type="text" id="license" name="license"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all"
                        placeholder="ABC123456789">
                </div>
                <div class="pt-4 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeAddCustomerModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Cancel</button>
                    <button type="submit"
                        class="px-6 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-lg shadow-blue-200">Create
                        Customer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Contract Viewer
        function viewContract(bookingId, token) {
            const modal = document.getElementById('contractModal');
            const content = document.getElementById('contractModalContent');
            const subtitle = document.getElementById('contractModalSubtitle');
            const signedInfo = document.getElementById('contractSignedInfo');

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            subtitle.textContent = 'Booking #' + String(bookingId).padStart(5, '0');

            content.innerHTML = `
                <div class="w-full h-full min-h-[500px] flex flex-col">
                    <iframe src="/dashboard/preview-contract.php?booking_id=${bookingId}" class="w-full flex-1 border-0 rounded-xl" style="height: 60vh;"></iframe>
                </div>
            `;

            // Basic signed info from API if needed, or just let the PDF show it
            fetch('/api/get-contract.php?booking_id=' + bookingId + '&token=' + token)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.contract.signed_at) {
                        const d = new Date(data.contract.signed_at);
                        signedInfo.innerHTML = '<span class="inline-flex items-center gap-1 text-green-600 font-medium"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>Signed on ' + d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) + '</span>';
                    } else {
                        signedInfo.textContent = '';
                    }
                });
        }

        function closeContractModal() {
            document.getElementById('contractModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        function openAddCustomerModal() {
            const modal = document.getElementById('addCustomerModal');
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            document.getElementById('full_name').focus();
        }

        function closeAddCustomerModal() {
            document.getElementById('addCustomerModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        document.getElementById('contractModal')?.addEventListener('click', function (e) {
            if (e.target === this) closeContractModal();
        });

        document.getElementById('addCustomerModal')?.addEventListener('click', function (e) {
            if (e.target === this) closeAddCustomerModal();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeContractModal();
                closeAddCustomerModal();
            }
        });
    </script>
    <?php include __DIR__ . '/../includes/confirmation-modal.php'; ?>
    <?php include __DIR__ . '/../includes/onboarding-widget.php'; ?>
</body>

</html>