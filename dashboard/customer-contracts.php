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
    AND NOT (b.status = 'pending' AND b.payment_status = 'unpaid')
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
            <!-- Header -->
            <div class="max-w-6xl mb-10">
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Digital Contracts</h1>
                <p class="text-slate-500 mt-2 font-medium text-sm sm:text-base">Review, sign, or download your legally binding rental agreements.</p>
            </div>

            <div class="max-w-6xl space-y-6">
                <?php if (empty($contracts)): ?>
                    <div class="bg-white rounded-[2.5rem] border border-slate-100 p-16 text-center shadow-sm">
                        <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900">No contracts found</h3>
                        <p class="text-slate-400 mt-2 max-w-sm mx-auto text-sm font-medium">All your signed agreements will be archived here for your records.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 gap-6 pb-20">
                        <?php foreach ($contracts as $contract): ?>
                        <div class="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm hover:shadow-md transition-all duration-300 group overflow-hidden relative">
                            <div class="flex flex-col lg:flex-row lg:items-center gap-6">
                                <div class="flex-1 flex items-center gap-6">
                                    <div class="w-14 h-14 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-brand-light group-hover:text-brand transition-all duration-300 border border-slate-100/50">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-3 mb-1.5">
                                            <span class="text-[9px] font-bold text-slate-400 tracking-wider uppercase">REF-<?= str_pad($contract['booking_id'], 5, '0', STR_PAD_LEFT) ?></span>
                                            <?php if ($contract['contract_status'] === 'signed'): ?>
                                                <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[9px] font-bold uppercase tracking-wider border border-emerald-100">Signed</span>
                                            <?php else: ?>
                                                <span class="px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 text-[9px] font-bold uppercase tracking-wider border border-amber-100 animate-pulse">Pending</span>
                                            <?php endif; ?>
                                        </div>
                                        <h3 class="text-xl font-bold text-slate-900 leading-tight mb-2"><?= htmlspecialchars($contract['brand'] . ' ' . $contract['model']) ?></h3>
                                        <div class="flex items-center gap-1.5 text-slate-400">
                                            <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            <span class="text-xs font-semibold tracking-tight text-slate-500"><?= date('j M Y', strtotime($contract['pickup_date'])) ?> — <?= date('j M Y', strtotime($contract['return_date'])) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-row lg:flex-col items-stretch gap-2.5 w-full lg:w-48 pt-4 lg:pt-0 border-t lg:border-t-0 lg:border-l border-slate-100 lg:pl-6">
                                    <?php if ($contract['contract_status'] === 'signed'): ?>
                                        <a href="/api/download-contract.php?booking_id=<?= $contract['booking_id'] ?>" target="_blank" class="flex-1 flex items-center justify-center gap-1.5 px-4 py-2.5 bg-slate-950 text-white hover:bg-slate-900 rounded-xl text-[10px] font-bold uppercase tracking-wider shadow-sm transition-all active:scale-95">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            Download PDF
                                        </a>
                                    <?php else: ?>
                                        <a href="/templates/contract-sign.php?booking_id=<?= $contract['booking_id'] ?>&token=<?= htmlspecialchars($contract['signing_token']) ?>" class="flex-1 flex items-center justify-center gap-1.5 px-4 py-3 text-white rounded-xl text-[10px] font-bold uppercase tracking-wider shadow-md shadow-amber-200/50 transition-all active:scale-95 bg-gradient-to-r from-amber-500 to-amber-600">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                            Sign Agreement
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
