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

// Get customer signed contracts
$stmt = $pdo->prepare("
    SELECT c.*, b.id as booking_id, b.pickup_date, b.return_date, v.brand, v.model, v.year
    FROM contracts c
    JOIN bookings b ON c.booking_id = b.id
    LEFT JOIN vehicles v ON b.vehicle_id = v.id
    WHERE c.tenant_id = ? AND b.customer_email = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$tenant_id, $user_email]);
$contracts = $stmt->fetchAll();

$primaryColor = $tenant['primary_color'] ?? '#3B82F6';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Contracts - <?= htmlspecialchars($tenant['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Mobile Header -->
    <header class="lg:hidden fixed top-0 left-0 right-0 bg-white border-b border-gray-200 px-4 py-3 z-40 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <button id="mobile-menu-btn" class="p-2 hover:bg-gray-100 rounded-xl transition-colors text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <span class="text-lg font-bold text-gray-900"><?= htmlspecialchars($tenant['name']) ?></span>
        </div>
        <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center text-white font-bold text-sm">
            <?= strtoupper(substr($user['full_name'] ?? 'C', 0, 1)) ?>
        </div>
    </header>

    <!-- Sidebar Overlay -->
    <div id="sidebar-overlay" class="lg:hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-30 hidden transition-all duration-300"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed lg:static inset-y-0 left-0 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out z-40">
        <?php include __DIR__ . '/../includes/customer-sidebar.php'; ?>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden pt-14 lg:pt-0">
        <main class="flex-1 overflow-y-auto bg-gray-50/50 p-4 sm:p-6 lg:p-10">
            <!-- Header -->
            <div class="max-w-6xl mb-12">
                <h1 class="text-4xl font-black text-gray-900 tracking-tight">Digital Contracts</h1>
                <p class="text-gray-500 mt-2 text-lg font-medium">Review and download your signed rental agreements.</p>
            </div>

            <div class="max-w-6xl space-y-6">
                <?php if (empty($contracts)): ?>
                    <div class="bg-white rounded-[3rem] border border-gray-100 p-20 text-center shadow-sm">
                        <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-8">
                            <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <h3 class="text-2xl font-black text-gray-900">No contracts found</h3>
                        <p class="text-gray-400 mt-4 max-w-sm mx-auto font-medium">All your signed agreements will be archived here for your records.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 gap-6 pb-20">
                        <?php foreach ($contracts as $contract): ?>
                        <div class="bg-white rounded-[2.5rem] border border-gray-100 p-8 shadow-sm hover:shadow-2xl transition-all group relative overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-br from-blue-50/0 to-indigo-50/0 group-hover:from-blue-50/30 group-hover:to-indigo-50/30 transition-all duration-500 -z-10"></div>
                            
                            <div class="flex flex-col md:flex-row md:items-center gap-10">
                                <div class="flex-1 flex items-center gap-8">
                                    <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center text-gray-400 group-hover:bg-white group-hover:shadow-xl transition-all">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-4 mb-2">
                                            <span class="text-[10px] font-black font-mono text-gray-400 tracking-widest uppercase">REF-<?= str_pad($contract['booking_id'], 5, '0', STR_PAD_LEFT) ?></span>
                                            <?php if ($contract['contract_status'] === 'signed'): ?>
                                                <span class="px-3 py-1 rounded-full bg-green-50 text-green-700 text-[10px] font-black uppercase tracking-widest shadow-sm shadow-green-200/50">Signed</span>
                                            <?php else: ?>
                                                <span class="px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-[10px] font-black uppercase tracking-widest shadow-sm shadow-amber-200/50 animate-pulse">Pending</span>
                                            <?php endif; ?>
                                        </div>
                                        <h3 class="text-2xl font-black text-gray-900 leading-tight mb-2"><?= htmlspecialchars($contract['brand'] . ' ' . $contract['model']) ?></h3>
                                        <div class="flex items-center gap-2 text-gray-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            <span class="text-xs font-black uppercase tracking-tight"><?= date('M j, Y', strtotime($contract['pickup_date'])) ?> — <?= date('M j, Y', strtotime($contract['return_date'])) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-row md:flex-col items-stretch gap-4 w-full md:w-56 pt-6 md:pt-0 border-t md:border-t-0 md:border-l border-gray-100 md:pl-10">
                                    <?php if ($contract['contract_status'] === 'signed'): ?>
                                        <a href="/api/download-contract.php?booking_id=<?= $contract['booking_id'] ?>" target="_blank" class="flex-1 flex items-center justify-center gap-2 px-8 py-5 bg-gray-900 text-white rounded-[1.5rem] text-xs font-black uppercase tracking-widest hover:bg-black shadow-2xl shadow-gray-200 transition-all active:scale-95">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            PDF Copy
                                        </a>
                                    <?php else: ?>
                                        <a href="/templates/contract-sign.php?booking_id=<?= $contract['booking_id'] ?>&token=<?= htmlspecialchars($contract['signing_token']) ?>" class="flex-1 flex items-center justify-center gap-2 px-8 py-6 text-white rounded-[1.5rem] text-xs font-black uppercase tracking-[0.2em] shadow-2xl shadow-amber-200 transition-all active:scale-95 hover:opacity-90 animate-pulse" style="background: linear-gradient(135deg, #f59e0b, #d97706)">
                                            Sign Now
                                        </a>
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
    <?php include __DIR__ . '/../includes/onboarding-widget.php'; ?>
</body>
</html>
