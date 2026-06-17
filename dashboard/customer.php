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

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND tenant_id = ?");
$stmt->execute([$user_id, $tenant_id]);
$user = $stmt->fetch();

$success_msg = '';
$error_msg = '';

// Helper: ensure a column exists on a table
try {
    $tablesToFix = [
        'bookings' => [
            'customer_id' => 'INT NULL AFTER vehicle_id',
            'is_deleted' => 'TINYINT(1) DEFAULT 0 AFTER payment_status',
        ],
        'contracts' => [
            'contract_status' => "VARCHAR(50) DEFAULT 'pending' AFTER signed_at",
            'signing_token' => 'VARCHAR(255) NULL AFTER signature_url',
            'signed_pdf_path' => 'VARCHAR(500) NULL AFTER signing_token',
        ],
    ];
    foreach ($tablesToFix as $table => $columns) {
        foreach ($columns as $col => $definition) {
            $check = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
            $check->execute([$table, $col]);
            if ($check->fetchColumn() == 0) {
                $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$col} {$definition}");
            }
        }
    }
} catch (PDOException $e) {
    error_log("Column migration error in customer.php: " . $e->getMessage());
}

// Handle Booking Cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_booking') {
    $cancel_booking_id = intval($_POST['booking_id'] ?? 0);
    if ($cancel_booking_id <= 0) {
        $error_msg = "Invalid booking ID.";
    } else {
        try {
            // Fetch the booking with all relevant fields
            $stmt_check = $pdo->prepare("SELECT status, customer_email, customer_id FROM bookings WHERE id = ? AND tenant_id = ? LIMIT 1");
            $stmt_check->execute([$cancel_booking_id, $tenant_id]);
            $booking = $stmt_check->fetch();
            
            if (!$booking) {
                $error_msg = "Booking not found.";
            } else {
                $is_owner = false;
                // Primary: check by customer_id (most reliable)
                if (!empty($booking['customer_id']) && $booking['customer_id'] == $user_id) {
                    $is_owner = true;
                }
                // Fallback: check by customer_email (for legacy bookings without customer_id)
                if (!$is_owner && !empty($booking['customer_email']) && strtolower(trim($booking['customer_email'])) === strtolower(trim($user_email))) {
                    $is_owner = true;
                }
                
                if (!$is_owner) {
                    $error_msg = "Access denied. This booking is not associated with your account.";
                } elseif (!in_array($booking['status'], ['pending', 'confirmed'])) {
                    $error_msg = "This booking cannot be cancelled because its status is already '{$booking['status']}'.";
                } else {
                    $pdo->beginTransaction();
                    
                    // Update booking status to cancelled (never delete — only change status)
                    $stmt_cancel = $pdo->prepare("UPDATE bookings SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
                    $stmt_cancel->execute([$cancel_booking_id]);
                    
                    // Update associated contract status to cancelled if a contract exists
                    try {
                        $hasContractStatus = $pdo->query("SHOW COLUMNS FROM contracts LIKE 'contract_status'")->rowCount() > 0;
                        if ($hasContractStatus) {
                            $stmt_contract = $pdo->prepare("UPDATE contracts SET contract_status = 'cancelled', updated_at = NOW() WHERE booking_id = ?");
                            $stmt_contract->execute([$cancel_booking_id]);
                        }
                    } catch (Exception $contractErr) {
                        // Contract update is secondary — don't fail the whole cancellation
                        error_log("Contract status update skipped during cancellation: " . $contractErr->getMessage());
                    }
                    
                    $pdo->commit();
                    $success_msg = "Booking #REF-" . str_pad($cancel_booking_id, 5, '0', STR_PAD_LEFT) . " has been successfully cancelled.";
                }
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_msg = "An error occurred while cancelling the booking: " . $e->getMessage();
            error_log("Customer booking cancellation error: " . $e->getMessage());
        }
    }
}

// Get customer bookings with vehicle and contract info
// is_deleted = 0 ensures truly deleted bookings are hidden, but cancelled bookings remain visible
$stmt = $pdo->prepare("
    SELECT b.*, 
           v.brand, v.model, v.year, v.category, v.images as vehicle_images,
           c.id as contract_id, c.contract_status, c.signed_at, c.signing_token, c.signed_pdf_path
    FROM bookings b
    LEFT JOIN vehicles v ON b.vehicle_id = v.id
    LEFT JOIN contracts c ON c.booking_id = b.id AND c.tenant_id = b.tenant_id
    WHERE b.tenant_id = ? 
      AND b.is_deleted = 0
      AND (b.customer_email = ? OR b.customer_id = ?)
    AND NOT (b.status = 'pending' AND b.payment_status = 'unpaid')
    ORDER BY b.created_at DESC
");
$stmt->execute([$tenant_id, $user_email, $user_id]);
$bookings = $stmt->fetchAll();

$primaryColor = $tenant['primary_color'] ?? '#3B82F6';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - <?= htmlspecialchars($tenant['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/app/custom.css">
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
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
        .hover\:bg-brand-dark:hover { background-color: var(--primary-hover); }
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
    <aside id="sidebar" class="fixed lg:static inset-y-0 left-0 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out z-40 shadow-xl lg:shadow-none">
        <?php include __DIR__ . '/../includes/customer-sidebar.php'; ?>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden pt-16 lg:pt-0">
        <main class="flex-1 overflow-y-auto bg-slate-50/30 p-4 sm:p-6 lg:p-10">
            
            <!-- Welcome Header -->
            <div class="max-w-6xl mb-10">
                <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Hello, <?= htmlspecialchars(explode(' ', $user['full_name'] ?? 'Customer')[0]) ?>!</h1>
                <p class="text-slate-500 mt-2 font-medium max-w-xl text-sm sm:text-base leading-relaxed">
                    Welcome to your personalized dashboard with <?= htmlspecialchars($tenant['name']) ?>. Track your active rentals, view reservations, and complete pending contracts.
                </p>
            </div>

            <!-- Stats Overview -->
            <?php
            $totalBookings = count($bookings);
            $activeBookings = count(array_filter($bookings, function($b) { return in_array($b['status'], ['confirmed', 'active']); }));
            $pendingContracts = count(array_filter($bookings, function($b) { return $b['contract_id'] && $b['contract_status'] !== 'signed'; }));
            ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-10 max-w-6xl">
                <!-- Stat Card 1 -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/70 shadow-[0_1px_3px_rgba(0,0,0,0.04)]">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-3">Total Reservations</p>
                    <h3 class="text-4xl font-light text-emerald-500 mb-3"><?= $totalBookings ?></h3>
                    <p class="text-xs text-slate-400 font-medium">0 vs yesterday &middot; 0 vs last week</p>
                </div>

                <!-- Stat Card 2 -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/70 shadow-[0_1px_3px_rgba(0,0,0,0.04)]">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-3">Active Rentals</p>
                    <h3 class="text-4xl font-light text-brand mb-3"><?= $activeBookings ?></h3>
                    <p class="text-xs text-slate-400 font-medium">0 vs yesterday &middot; 0 vs last week</p>
                </div>

                <!-- Stat Card 3 -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/70 shadow-[0_1px_3px_rgba(0,0,0,0.04)]">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-3">Pending Contracts</p>
                    <h3 class="text-4xl font-light text-amber-500 mb-3"><?= $pendingContracts ?></h3>
                    <p class="text-xs text-slate-400 font-medium">0 vs yesterday &middot; 0 vs last week</p>
                </div>
            </div>

            <!-- Content Area -->
            <div class="max-w-6xl space-y-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Recent Reservations</h2>
                </div>

                <?php if (empty($bookings)): ?>
                    <div class="bg-white rounded-[2.5rem] border border-slate-100 p-16 text-center shadow-sm max-w-6xl">
                        <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900">No reservations found</h3>
                        <p class="text-slate-400 mt-2 max-w-sm mx-auto text-sm font-medium">Your upcoming or past reservations will appear here once booked.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 gap-6 pb-20">
                        <?php foreach ($bookings as $booking): 
                            $vehicleName = trim(($booking['brand'] ?? '') . ' ' . ($booking['model'] ?? ''));
                            $bookingRef = 'REF-' . str_pad($booking['id'], 5, '0', STR_PAD_LEFT);
                            
                            $vehicleImage = null;
                            if (!empty($booking['vehicle_images'])) {
                                $decoded = json_decode($booking['vehicle_images'], true);
                                $vehicleImage = is_array($decoded) && !empty($decoded) ? $decoded[0] : "";
                            }
                            
                            $statusColors = [
                                'confirmed' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                'active' => 'bg-blue-50 text-blue-700 border-blue-100',
                                'completed' => 'bg-slate-100 text-slate-600 border-slate-200',
                                'cancelled' => 'bg-rose-50 text-rose-600 border-rose-100',
                                'pending' => 'bg-amber-50 text-amber-700 border-amber-100',
                            ];
                            $statusClass = $statusColors[$booking['status']] ?? 'bg-slate-100 text-slate-600 border-slate-200';
                        ?>
                        <div class="bg-white rounded-[2rem] border border-slate-100 p-5 sm:p-6 shadow-sm hover:shadow-md transition-all duration-300 group overflow-hidden relative">
                            <div class="flex flex-col lg:flex-row lg:items-center gap-6">
                                
                                <!-- Vehicle Image Container -->
                                <div class="w-full lg:w-48 h-36 rounded-2xl overflow-hidden bg-slate-50 flex-shrink-0 relative border border-slate-100">
                                    <?php if ($vehicleImage): ?>
                                        <img src="<?= htmlspecialchars($vehicleImage) ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-slate-200 bg-slate-50">
                                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                    <div class="absolute top-3 left-3">
                                        <span class="px-2.5 py-1 rounded-lg text-[9px] font-bold uppercase tracking-wider bg-slate-900/80 backdrop-blur-md shadow-sm text-white border border-white/10"><?= $bookingRef ?></span>
                                    </div>
                                </div>
        
                                <!-- Vehicle and Booking Details -->
                                <div class="flex-1 flex flex-col justify-between">
                                    <div class="flex flex-wrap items-center gap-3 mb-2.5">
                                        <span class="px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider border <?= $statusClass ?>"><?= $booking['status'] ?></span>
                                        <div class="flex items-center gap-1 text-slate-400">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <span class="text-[10px] font-semibold uppercase tracking-tight"><?= date('M j, Y', strtotime($booking['created_at'])) ?></span>
                                        </div>
                                    </div>
                                    
                                    <h3 class="text-xl font-bold text-slate-900 leading-tight mb-4 group-hover:text-brand transition-colors"><?= htmlspecialchars($vehicleName ?: 'Vehicle Rental') ?></h3>
                                    
                                    <!-- Journey Flow Section -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                                        <div class="flex items-center gap-4 sm:gap-6">
                                            <div>
                                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Pick-up</p>
                                                <p class="text-xs font-bold text-slate-800"><?= date('D, j M Y', strtotime($booking['pickup_date'])) ?></p>
                                                <p class="text-[10px] text-slate-400 mt-0.5"><?= date('h:ia', strtotime($booking['pickup_time'] ?? '10:00')) ?></p>
                                            </div>
                                            <!-- Visual Connector Line with Indicator -->
                                            <div class="flex items-center flex-1 max-w-[40px] justify-center text-slate-300">
                                                <svg class="w-5 h-5 animate-pulse text-brand/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Return</p>
                                                <p class="text-xs font-bold text-slate-800"><?= date('D, j M Y', strtotime($booking['return_date'])) ?></p>
                                                <p class="text-[10px] text-slate-400 mt-0.5"><?= date('h:ia', strtotime($booking['return_time'] ?? '10:00')) ?></p>
                                            </div>
                                        </div>
                                        <div class="md:ml-auto flex items-center justify-between md:justify-end gap-3 bg-slate-50 px-4 py-2 rounded-xl border border-slate-100 w-full md:w-auto">
                                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Total amount</span>
                                            <span class="text-lg font-extrabold text-slate-900">£<?= number_format($booking['total_price'], 2) ?></span>
                                        </div>
                                    </div>
                                </div>
        
                                <!-- Actions Block -->
                                <div class="flex flex-row lg:flex-col items-stretch gap-2.5 w-full lg:w-48 pt-4 lg:pt-0 border-t lg:border-t-0 lg:border-l border-slate-100 lg:pl-6">
                                    <?php if ($booking['contract_id']): ?>
                                        <?php if ($booking['contract_status'] === 'signed'): ?>
                                            <button onclick="viewContract(<?= $booking['id'] ?>, '<?= htmlspecialchars($booking['signing_token']) ?>')" 
                                                    class="flex-1 flex items-center justify-center gap-1.5 px-4 py-2.5 text-[10px] font-bold uppercase tracking-wider text-slate-600 bg-slate-50 hover:bg-slate-100 rounded-xl border border-slate-200 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                Agreement
                                            </button>
                                            <a href="/api/download-contract.php?booking_id=<?= $booking['id'] ?>" target="_blank"
                                               class="flex-1 flex items-center justify-center gap-1.5 px-4 py-2.5 text-[10px] font-bold uppercase tracking-wider text-white rounded-xl shadow-md shadow-brand/10 hover:opacity-95 transition-all active:scale-95 bg-brand">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                PDF Copy
                                            </a>
                                        <?php elseif ($booking['status'] !== 'cancelled'): ?>
                                            <a href="/templates/contract-sign.php?booking_id=<?= $booking['id'] ?>&token=<?= htmlspecialchars($booking['signing_token']) ?>" 
                                               class="w-full flex items-center justify-center gap-1.5 px-4 py-3 text-[10px] font-bold uppercase tracking-wider text-white rounded-xl shadow-md shadow-amber-200/50 hover:opacity-95 transition-all active:scale-95 bg-gradient-to-r from-amber-500 to-amber-600">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                Sign Contract
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <!-- Cancellation Button -->
                                    <?php if (in_array($booking['status'], ['pending', 'confirmed'])): ?>
                                        <button onclick="triggerCancelBooking(<?= $booking['id'] ?>)" 
                                                class="flex-1 flex items-center justify-center gap-1.5 px-4 py-2.5 text-[10px] font-bold uppercase tracking-wider text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-xl transition-all">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            Cancel
                                        </button>
                                    <?php endif; ?>
                                </div>

                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Notification Trigger for Backend Messages -->
    <?php if ($success_msg || $error_msg): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                showNotificationModal(
                    "<?= $success_msg ? 'Success' : 'Error' ?>",
                    "<?= htmlspecialchars($success_msg ?: $error_msg) ?>",
                    "<?= $success_msg ? 'success' : 'error' ?>"
                );
            });
        </script>
    <?php endif; ?>

    <!-- Mobile Menu Script -->
    <script>
        const btn = document.getElementById('mobile-menu-btn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');

        function toggleMenu() {
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        btn?.addEventListener('click', toggleMenu);
        overlay?.addEventListener('click', toggleMenu);
    </script>

    <!-- Custom Modal for Notifications / Errors -->
    <div id="notificationModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 transition-all duration-300">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-6 text-center transform transition-all duration-300 scale-95 opacity-0" id="notificationModalBox">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4" id="notificationIconBox">
                <!-- Dynamic SVG will be injected here -->
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-2" id="notificationTitle">Notification</h3>
            <p class="text-slate-500 text-sm mb-6 leading-relaxed" id="notificationText">Message goes here.</p>
            <button onclick="closeNotificationModal()" class="w-full py-3 bg-slate-950 hover:bg-slate-900 text-white text-sm font-semibold rounded-xl transition-all shadow-sm">
                Okay
            </button>
        </div>
    </div>

    <!-- Custom Confirmation Modal for Cancellation -->
    <div id="confirmCancelModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 transition-all duration-300">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-6 text-center transform transition-all duration-300 scale-95 opacity-0" id="confirmCancelBox">
            <div class="w-16 h-16 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-rose-100">
                <svg class="w-8 h-8 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-2">Cancel Reservation?</h3>
            <p class="text-slate-500 text-sm mb-6 leading-relaxed">Are you sure you want to cancel this booking? This action will set your status to cancelled but your record remains secure in history.</p>
            
            <form method="POST" id="cancelForm" class="grid grid-cols-2 gap-3">
                <input type="hidden" name="action" value="cancel_booking">
                <input type="hidden" name="booking_id" id="cancelBookingId" value="">
                <button type="button" onclick="closeCancelModal()" class="w-full py-3 bg-slate-50 hover:bg-slate-100 text-slate-700 text-sm font-bold rounded-xl transition-all border border-slate-200/60">
                    Keep It
                </button>
                <button type="submit" class="w-full py-3 bg-rose-600 hover:bg-rose-700 text-white text-sm font-bold rounded-xl transition-all shadow-md shadow-rose-100">
                    Cancel Booking
                </button>
            </form>
        </div>
    </div>

    <!-- Contract Viewer Modal -->
    <div id="contractModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Rental Agreement</h3>
                    <p class="text-xs text-slate-400 mt-0.5 font-medium" id="contractModalSubtitle"></p>
                </div>
                <button onclick="closeContractModal()" class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center hover:bg-slate-200 transition-colors">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <!-- Modal Content -->
            <div class="flex-1 overflow-y-auto p-6 text-sm leading-relaxed text-slate-700 bg-white" id="contractModalContent">
                <div class="text-center py-12 text-slate-400">
                    <svg class="animate-spin h-8 w-8 mx-auto mb-3 text-brand" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Loading agreement...
                </div>
            </div>
            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div id="contractSignedInfo" class="text-xs text-slate-500 font-semibold uppercase tracking-wider"></div>
                <button onclick="closeContractModal()" class="px-4 py-2 text-xs font-bold text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200 transition-colors border border-slate-200/50">Close</button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
    // Custom Notification Modal
    function showNotificationModal(title, text, type) {
        const modal = document.getElementById('notificationModal');
        const box = document.getElementById('notificationModalBox');
        const titleEl = document.getElementById('notificationTitle');
        const textEl = document.getElementById('notificationText');
        const iconEl = document.getElementById('notificationIconBox');

        titleEl.textContent = title;
        textEl.textContent = text;

        if (type === 'success') {
            iconEl.className = "w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 bg-green-50 text-green-600";
            iconEl.innerHTML = `<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
        } else {
            iconEl.className = "w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 bg-red-50 text-red-600";
            iconEl.innerHTML = `<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>`;
        }

        modal.classList.remove('hidden');
        setTimeout(() => {
            box.classList.remove('scale-95', 'opacity-0');
            box.classList.add('scale-100', 'opacity-100');
        }, 50);
    }

    function closeNotificationModal() {
        const modal = document.getElementById('notificationModal');
        const box = document.getElementById('notificationModalBox');
        box.classList.remove('scale-100', 'opacity-100');
        box.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 200);
    }

    // Cancellation Flow
    function triggerCancelBooking(bookingId) {
        document.getElementById('cancelBookingId').value = bookingId;
        const modal = document.getElementById('confirmCancelModal');
        const box = document.getElementById('confirmCancelBox');
        
        modal.classList.remove('hidden');
        setTimeout(() => {
            box.classList.remove('scale-95', 'opacity-0');
            box.classList.add('scale-100', 'opacity-100');
        }, 50);
    }

    function closeCancelModal() {
        const modal = document.getElementById('confirmCancelModal');
        const box = document.getElementById('confirmCancelBox');
        box.classList.remove('scale-100', 'opacity-100');
        box.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 200);
    }

    // Contract Viewer
    function viewContract(bookingId, token) {
        const modal = document.getElementById('contractModal');
        const content = document.getElementById('contractModalContent');
        const subtitle = document.getElementById('contractModalSubtitle');
        const signedInfo = document.getElementById('contractSignedInfo');
        
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        subtitle.textContent = 'Booking #' + String(bookingId).padStart(5, '0');
        content.innerHTML = '<div class="text-center py-12 text-gray-400"><svg class="animate-spin h-8 w-8 mx-auto mb-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Loading contract...</div>';
        
        fetch('/api/get-contract.php?booking_id=' + bookingId + '&token=' + token)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    let html = '';
                    if (data.contract.is_html) {
                        html = '<div class="prose prose-sm max-w-none">' + data.contract.content + '</div>';
                    } else {
                        let text = data.contract.content;
                        text = text.replace(/\n/g, '<br>');
                        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                        html = '<div class="prose prose-sm max-w-none">' + text + '</div>';
                    }
                    
                    content.innerHTML = html;
                    
                    if (data.contract.signed_at) {
                        const d = new Date(data.contract.signed_at);
                        signedInfo.innerHTML = '<span class="inline-flex items-center gap-1 text-green-600 font-medium"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>Signed on ' + d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) + '</span>';
                    } else {
                        signedInfo.textContent = '';
                    }
                } else {
                    content.innerHTML = '<p class="text-red-500 text-center py-8">Failed to load contract: ' + (data.message || 'Unknown error') + '</p>';
                }
            })
            .catch(() => {
                content.innerHTML = '<p class="text-red-500 text-center py-8">Error loading contract. Please try again.</p>';
            });
    }
    
    function closeContractModal() {
        document.getElementById('contractModal').classList.add('hidden');
        document.body.style.overflow = '';
    }
    
    // Close modal on backdrop click
    document.getElementById('contractModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeContractModal();
    });
    
    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeContractModal();
    });
    </script>
</body>
</html>
