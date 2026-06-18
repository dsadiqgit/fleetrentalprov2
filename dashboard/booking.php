<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
if (!isset($_SESSION['user_id'])) redirect('/auth/login.php');
if ($_SESSION['role'] === 'super_admin') redirect('/admin/super-admin.php');
if (!$_SESSION['tenant_id']) die('Error: No tenant associated with this account.');
$pdo = getDB();
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) redirect('/dashboard/bookings.php');
$booking_id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT b.*, v.brand, v.model, v.year, v.category, v.images FROM bookings b LEFT JOIN vehicles v ON b.vehicle_id = v.id WHERE b.id = ? AND b.tenant_id = ?");
$stmt->execute([$booking_id, $_SESSION['tenant_id']]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$booking) redirect('/dashboard/bookings.php');
$stmt = $pdo->prepare("SELECT * FROM booking_condition_reports WHERE booking_id = ? AND tenant_id = ?");
$stmt->execute([$booking_id, $_SESSION['tenant_id']]);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
$condition_reports = ['pickup' => null, 'return' => null];
foreach ($reports as $r) { if ($r['report_type'] === 'pickup') $condition_reports['pickup'] = $r; if ($r['report_type'] === 'return') $condition_reports['return'] = $r; }
$stmt = $pdo->prepare("SELECT id, contract_status, signed_at, signing_token FROM contracts WHERE booking_id = ? AND tenant_id = ? LIMIT 1");
$stmt->execute([$booking_id, $_SESSION['tenant_id']]);
$contract = $stmt->fetch(PDO::FETCH_ASSOC);
$available_templates = [];
if (!$contract) { $stmt = $pdo->prepare("SELECT id, name FROM contract_templates WHERE tenant_id = ? AND status = 'published' ORDER BY is_default DESC, created_at DESC"); $stmt->execute([$_SESSION['tenant_id']]); $available_templates = $stmt->fetchAll(PDO::FETCH_ASSOC); }
$statusMap = ['pending'=>'bg-amber-50 text-amber-600 border border-amber-100','confirmed'=>'bg-blue-50 text-blue-600 border border-blue-100','active'=>'bg-green-50 text-green-600 border border-green-100','completed'=>'bg-gray-50 text-gray-600 border border-gray-100','cancelled'=>'bg-red-50 text-red-600 border border-red-100'];
$paymentMap = ['unpaid'=>'bg-amber-100 text-amber-800','partial'=>'bg-blue-100 text-blue-800','paid'=>'bg-green-100 text-green-800','refunded'=>'bg-red-100 text-red-800'];
$active_tab = in_array($_GET['tab'] ?? '', ['details','condition','contract']) ? ($_GET['tab'] ?? 'details') : 'details';
function fmtMoney($a){ return '£'.number_format(floatval($a),2); }
function crPhoto($r,$f){ return ($r && !empty($r[$f])) ? $r[$f] : null; }
function crVal($r,$f){ return $r[$f] ?? ''; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking #<?= str_pad($booking['id'],5,'0',STR_PAD_LEFT) ?> - <?= SITE_NAME ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="/app/custom-select.js" defer></script>
<link rel="stylesheet" href="/app/custom.css">
<link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
<style>input::-webkit-outer-spin-button,input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}input[type=number]{-moz-appearance:textfield}</style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
<header class="lg:hidden fixed top-0 left-0 right-0 bg-white border-b border-gray-200 px-4 py-3 z-40 flex items-center justify-between">
<div class="flex items-center gap-3">
<button id="mobile-menu-btn" class="p-1 hover:bg-gray-100 rounded-lg transition-colors">
<svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
</button>
<h1 class="text-lg font-semibold text-gray-900">Booking Details</h1>
</div>
</header>
<div id="sidebar-overlay" class="lg:hidden fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-30 hidden transition-all duration-300"></div>
<aside id="sidebar" class="fixed lg:static top-14 lg:top-0 bottom-0 left-0 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out z-40 lg:flex">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
</aside>
<div class="flex-1 flex flex-col overflow-hidden w-full lg:w-auto pt-14 lg:pt-0">
<header class="hidden lg:block bg-white border-b border-gray-200 px-6 py-4">
<div class="flex items-center justify-between">
<div>
<nav class="text-sm text-gray-500 mb-1">
<a href="/dashboard/" class="hover:text-gray-700">Dashboard</a><span class="mx-2">/</span>
<a href="/dashboard/bookings.php" class="hover:text-gray-700">Bookings</a><span class="mx-2">/</span>
<span class="text-gray-900">#<?= str_pad($booking['id'],5,'0',STR_PAD_LEFT) ?></span>
</nav>
<h1 class="text-2xl font-bold text-gray-900">Booking Details</h1>
</div>
<a href="/dashboard/bookings.php" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-all shadow-sm">
<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
Back to Bookings
</a>
</div>
</header>
<div class="flex-1 overflow-auto bg-gray-50">
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
<!-- Header Card -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-6">
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
<div>
<div class="flex items-center gap-3 mb-2">
<h2 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($booking['customer_name'] ?: 'Guest Booking') ?></h2>
<span class="px-2.5 py-0.5 rounded-full text-xs font-medium capitalize <?= $statusMap[$booking['status']] ?? 'bg-gray-100 text-gray-700' ?>"><?= htmlspecialchars($booking['status']) ?></span>
</div>
<div class="flex items-center gap-2 text-sm text-gray-500 flex-wrap">
<span>Ref: #<?= str_pad($booking['id'],5,'0',STR_PAD_LEFT) ?></span>
<span class="text-gray-300">|</span>
<span class="flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg><?= date('M d, Y', strtotime($booking['pickup_date'])) ?> – <?= date('M d, Y', strtotime($booking['return_date'])) ?></span>
<span class="text-gray-300">|</span><span><?= $booking['total_days'] ?> days</span>
</div>
</div>
<div class="flex flex-wrap items-center gap-2">
<?php if ($booking['status'] === 'pending'): ?><button onclick="updateBookingStatus(<?= $booking['id'] ?>,'confirmed')" class="px-5 py-2.5 bg-gray-900 text-white rounded-xl text-sm font-semibold hover:bg-black transition-all shadow-sm">Confirm Booking</button>
<?php elseif ($booking['status'] === 'confirmed'): ?><button onclick="openStartTripModal(<?= $booking['id'] ?>)" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-semibold hover:bg-blue-700 transition-all shadow-sm shadow-blue-100">Start Trip</button>
<?php elseif ($booking['status'] === 'active'): ?><button onclick="openCompleteTripModal(<?= $booking['id'] ?>)" class="px-5 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-semibold hover:bg-emerald-700 transition-all shadow-sm shadow-emerald-100">Complete Trip</button>
<?php endif; ?>
<?php if (!in_array($booking['status'], ['cancelled','completed'])): ?><button onclick="updateBookingStatus(<?= $booking['id'] ?>,'cancelled')" class="px-5 py-2.5 bg-white text-red-600 border border-red-100 rounded-xl text-sm font-semibold hover:bg-red-50 transition-all">Cancel</button><?php endif; ?>
</div>
</div>
</div>
<!-- Tabs -->
<div class="border-b border-gray-200 mb-6">
    <nav class="flex space-x-1 overflow-x-auto">
        <a href="?id=<?= $booking['id'] ?>&tab=details" class="tab-button flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 <?= $active_tab === 'details' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700' ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Overview
        </a>
        <a href="?id=<?= $booking['id'] ?>&tab=condition" class="tab-button flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 <?= $active_tab === 'condition' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700' ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Condition
        </a>
        <a href="?id=<?= $booking['id'] ?>&tab=contract" class="tab-button flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 <?= $active_tab === 'contract' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700' ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Contract
        </a>
    </nav>
</div>
<?php if ($active_tab === 'details'): ?>
<div class="grid lg:grid-cols-12 gap-6">
<div class="lg:col-span-8 space-y-6">
<div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
<div class="flex items-center justify-between mb-5 pb-4 border-b border-gray-100">
<div class="flex items-center gap-2"><span class="text-xs text-gray-400 font-medium">Ref</span><p class="text-lg font-bold text-gray-900">#<?= str_pad($booking['id'],5,'0',STR_PAD_LEFT) ?></p></div>
<span class="px-2.5 py-1 rounded-full text-xs font-medium capitalize <?= $statusMap[$booking['status']] ?? 'bg-gray-100 text-gray-700' ?>"><?= htmlspecialchars($booking['status']) ?></span>
</div>
<div class="flex items-center gap-4 p-5 bg-gray-50/50 rounded-2xl border border-gray-100/50 mb-6">
<div class="flex-1">
<div class="flex items-center gap-2 mb-1"><svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg><p class="text-xs font-semibold text-gray-500">Pickup</p></div>
<p class="text-lg font-bold text-gray-900"><?= date('j M Y', strtotime($booking['pickup_date'])) ?></p>
<p class="text-sm text-gray-400"><?= htmlspecialchars($booking['pickup_time'] ?: '10:00') ?></p>
</div>
<div class="flex flex-col items-center px-2"><div class="w-px h-4 bg-gray-200"></div><span class="my-1 px-2.5 py-0.5 bg-white rounded-full text-xs font-medium text-gray-500 border border-gray-100 shadow-sm"><?= $booking['total_days'] ?> days</span><div class="w-px h-4 bg-gray-200"></div></div>
<div class="flex-1 text-right">
<div class="flex items-center justify-end gap-2 mb-1"><p class="text-xs font-semibold text-gray-500">Return</p><svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
<p class="text-lg font-bold text-gray-900"><?= date('j M Y', strtotime($booking['return_date'])) ?></p>
<p class="text-sm text-gray-400"><?= htmlspecialchars($booking['return_time'] ?: '10:00') ?></p>
</div>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
<div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm">
<div class="flex items-center gap-2 mb-2"><svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg><p class="text-xs font-semibold text-gray-500">Vehicle</p></div>
<p class="text-sm font-bold text-gray-900 leading-tight"><?= htmlspecialchars($booking['brand'].' '.$booking['model']) ?></p>
<p class="text-xs text-gray-400 mt-1"><?= $booking['year'] ?> &middot; <?= str_replace('_',' ',$booking['category'] ?: 'Standard') ?></p>
</div>
<div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm">
<div class="flex items-center gap-2 mb-2"><svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg><p class="text-xs font-semibold text-gray-500">Customer</p></div>
<p class="text-sm font-bold text-gray-900 leading-tight truncate"><?= htmlspecialchars($booking['customer_name']) ?></p>
<p class="text-xs text-gray-400 mt-1 truncate"><?= htmlspecialchars($booking['customer_email']) ?></p>
<?php if (!empty($booking['customer_phone'])): ?><p class="text-xs text-gray-400 mt-1 truncate"><?= htmlspecialchars($booking['customer_phone']) ?></p><?php endif; ?>
</div>
</div>
<div class="mt-5 pt-5 border-t border-gray-100 space-y-2">
<div class="flex justify-between text-sm"><span class="text-gray-500">Daily rate</span><span class="font-semibold text-gray-900"><?= fmtMoney($booking['price_per_day']) ?></span></div>
<div class="flex justify-between text-sm"><span class="text-gray-500"><?= $booking['total_days'] ?> days</span><span class="font-semibold text-gray-900"><?= fmtMoney(floatval($booking['price_per_day']) * intval($booking['total_days'])) ?></span></div>
<div class="flex justify-between text-sm"><span class="text-gray-500">Tax</span><span class="font-semibold text-gray-900"><?= fmtMoney($booking['tax_amount'] ?? 0) ?></span></div>
<div class="pt-2 border-t border-gray-100 flex justify-between items-center"><span class="text-sm font-medium text-gray-700">Total</span><span class="text-base font-bold text-gray-900"><?= fmtMoney($booking['total_price']) ?></span></div>
</div>
</div>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
<div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
<div class="flex items-center gap-2"><svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg><h4 class="text-sm font-semibold text-gray-900">Security Deposit</h4></div>
<span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?= $paymentMap[$booking['security_deposit_status'] ?? 'unpaid'] ?? 'bg-gray-100 text-gray-600' ?>"><?= str_replace('_',' ',$booking['security_deposit_status'] ?? 'unpaid') ?></span>
</div>
<div class="p-6 space-y-4">
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
<div><label class="block text-xs font-medium text-gray-500 mb-1.5">Deposit Amount</label><div class="relative"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500 font-semibold text-sm">£</div><input type="number" id="deposit-amount" value="<?= $booking['security_deposit'] ?? 0 ?>" class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-7 pr-3 py-2.5 text-sm font-semibold focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all outline-none"></div></div>
<div><label class="block text-xs font-medium text-gray-500 mb-1.5">Payment Status</label><select id="deposit-status" class="custom-select w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-sm font-medium focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all outline-none cursor-pointer"><option value="unpaid" <?= ($booking['security_deposit_status']??'')==='unpaid'?'selected':'' ?>>Outstanding</option><option value="paid" <?= ($booking['security_deposit_status']??'')==='paid'?'selected':'' ?>>Paid</option><option value="refunded" <?= ($booking['security_deposit_status']??'')==='refunded'?'selected':'' ?>>Refunded</option></select></div>
<div><label class="block text-xs font-medium text-gray-500 mb-1.5">Payment Method</label><select id="deposit-method" class="custom-select w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-sm font-medium focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all outline-none cursor-pointer"><option value="" <?= empty($booking['security_deposit_method'])?'selected':'' ?>>Not set</option><option value="cash" <?= ($booking['security_deposit_method']??'')==='cash'?'selected':'' ?>>Cash</option><option value="card" <?= ($booking['security_deposit_method']??'')==='card'?'selected':'' ?>>Card Terminal</option><option value="stripe" <?= ($booking['security_deposit_method']??'')==='stripe'?'selected':'' ?>>Online</option></select></div>
</div>
<button onclick="updateSecurityDeposit(<?= $booking['id'] ?>)" class="w-full py-3 bg-gray-900 text-white rounded-xl text-sm font-semibold hover:bg-black transition-all shadow-sm">Update Deposit</button>
</div>
</div>
</div>
<div class="lg:col-span-4 flex flex-col gap-4">
<?php if (!empty($booking['notes'])): ?>
<div class="bg-amber-50 rounded-2xl p-5 border border-amber-100">
<div class="flex items-start gap-3">
<svg class="w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
<div class="min-w-0"><p class="text-xs font-semibold text-amber-700 mb-1">Notes</p><p class="text-sm text-amber-900/80 break-words"><?= nl2br(htmlspecialchars($booking['notes'])) ?></p></div>
</div>
</div>
<?php endif; ?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
<h4 class="text-sm font-semibold text-gray-900 mb-4">Customer Information</h4>
<div class="space-y-3">
<div><p class="text-xs text-gray-400 mb-0.5">Name</p><p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($booking['customer_name']) ?></p></div>
<div><p class="text-xs text-gray-400 mb-0.5">Email</p><p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($booking['customer_email']) ?></p></div>
<?php if (!empty($booking['customer_phone'])): ?><div><p class="text-xs text-gray-400 mb-0.5">Phone</p><p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($booking['customer_phone']) ?></p></div><?php endif; ?>
<?php if (!empty($booking['customer_license'])): ?><div><p class="text-xs text-gray-400 mb-0.5">License</p><p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($booking['customer_license']) ?></p></div><?php endif; ?>
</div>
</div>
<?php if (!empty($booking['images'])): $vehicle_images = json_decode($booking['images'], true) ?? []; $first_image = $vehicle_images[0] ?? null; ?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
<?php if ($first_image): ?><div class="aspect-video bg-gray-100"><img src="<?= htmlspecialchars($first_image) ?>" class="w-full h-full object-cover" alt="Vehicle"></div><?php endif; ?>
<div class="p-5"><h4 class="text-sm font-semibold text-gray-900 mb-1"><?= htmlspecialchars($booking['brand'].' '.$booking['model']) ?></h4><p class="text-xs text-gray-500"><?= $booking['year'] ?> &middot; <?= str_replace('_',' ',$booking['category'] ?: 'Standard') ?></p></div>
</div>
<?php endif; ?>
</div>
</div>
<?php endif; ?>
<?php if ($active_tab === 'condition'): ?>
<div x-data="{ conditionTab: 'pickup' }" class="space-y-5">
<div class="flex items-center gap-3 bg-white rounded-xl p-1 border border-gray-200 w-fit">
<button type="button" @click="conditionTab = 'pickup'" :class="conditionTab === 'pickup' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-500 hover:text-gray-900'" class="px-4 py-2 text-sm font-semibold rounded-lg transition-all">Pickup</button>
<button type="button" @click="conditionTab = 'return'" :class="conditionTab === 'return' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-500 hover:text-gray-900'" class="px-4 py-2 text-sm font-semibold rounded-lg transition-all">Return</button>
</div>
<!-- Pickup -->
<div x-show="conditionTab === 'pickup'" x-cloak>
<form method="POST" action="/dashboard/upload-condition-report.php" enctype="multipart/form-data" onsubmit="return handleConditionSubmit(event,this,'pickup')">
<input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
<input type="hidden" name="report_type" value="pickup">
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
<div class="px-6 py-4 border-b border-gray-100"><h4 class="text-sm font-semibold text-gray-900">Pickup Condition Report</h4></div>
<div class="p-6 space-y-6">
<div><label class="block text-xs font-medium text-gray-500 mb-2">Mileage at Pickup</label><input type="number" name="mileage" value="<?= crVal($condition_reports['pickup'],'mileage') ?>" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all outline-none" placeholder="00,000"></div>
<div><p class="text-xs font-medium text-gray-500 mb-3">Vehicle Photos</p><div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
<?php foreach ([['photo_front','Front View'],['photo_back','Rear View'],['photo_left','Left Side'],['photo_right','Right Side']] as [$f,$l]): ?>
<div class="relative group">
<label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1"><?= $l ?></label>
<div onclick="this.querySelector('input').click()" class="relative aspect-video rounded-2xl bg-gray-50 border-2 border-dashed <?= crPhoto($condition_reports['pickup'],$f)?'border-gray-200':'border-red-100/50' ?> hover:border-blue-300 transition-all cursor-pointer overflow-hidden flex items-center justify-center">
<?php if (crPhoto($condition_reports['pickup'],$f)): ?><img src="<?= crPhoto($condition_reports['pickup'],$f) ?>" class="w-full h-full object-cover"><div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"><span class="text-[10px] text-white font-bold uppercase tracking-widest">Replace</span></div>
<?php else: ?><div class="text-center"><svg class="w-8 h-8 text-gray-300 mb-1 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg><span class="text-[10px] text-gray-400 font-medium">Click to upload</span></div><?php endif; ?>
<input type="file" name="<?= $f ?>" class="hidden" onchange="previewConditionPhoto(this)">
</div>
</div>
<?php endforeach; ?>
</div></div>
<div><p class="text-xs font-medium text-gray-500 mb-3">Wheels & Rims</p><div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
<?php foreach ([['photo_rim1','Front Left'],['photo_rim2','Front Right'],['photo_rim3','Rear Left'],['photo_rim4','Rear Right']] as [$f,$l]): ?>
<div class="relative group">
<label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1"><?= $l ?></label>
<div onclick="this.querySelector('input').click()" class="relative aspect-video rounded-2xl bg-gray-50 border-2 border-dashed <?= crPhoto($condition_reports['pickup'],$f)?'border-gray-200':'border-red-100/50' ?> hover:border-blue-300 transition-all cursor-pointer overflow-hidden flex items-center justify-center">
<?php if (crPhoto($condition_reports['pickup'],$f)): ?><img src="<?= crPhoto($condition_reports['pickup'],$f) ?>" class="w-full h-full object-cover"><div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"><span class="text-[10px] text-white font-bold uppercase tracking-widest">Replace</span></div>
<?php else: ?><div class="text-center"><svg class="w-8 h-8 text-gray-300 mb-1 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg><span class="text-[10px] text-gray-400 font-medium">Click to upload</span></div><?php endif; ?>
<input type="file" name="<?= $f ?>" class="hidden" onchange="previewConditionPhoto(this)">
</div>
</div>
<?php endforeach; ?>
</div></div>
<div class="pt-4 border-t border-gray-100">
<p class="text-xs font-medium text-gray-500 mb-3">Additional Photos</p>
<div class="grid grid-cols-4 gap-3 mb-3" id="misc-pickup-preview">
<?php $misc = []; if ($condition_reports['pickup'] && !empty($condition_reports['pickup']['misc_photos'])) { $misc = json_decode($condition_reports['pickup']['misc_photos'], true) ?? []; } for ($i=0;$i<4;$i++): ?>
<?php if (!empty($misc[$i])): ?><div class="aspect-square rounded-xl bg-gray-100 overflow-hidden shadow-sm border border-gray-100"><img src="<?= htmlspecialchars($misc[$i]) ?>" class="w-full h-full object-cover"></div>
<?php else: ?><div class="aspect-square rounded-xl bg-gray-50 border-2 border-dashed border-gray-200 flex flex-col items-center justify-center p-2 text-center text-gray-300"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="2"/></svg></div><?php endif; ?>
<?php endfor; ?>
</div>
<input type="file" name="misc_photos[]" multiple accept="image/*" onchange="previewMiscPhotos(this,'pickup')" class="block w-full text-xs text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 transition-all cursor-pointer">
</div>
</div>
<div class="px-6 py-4 bg-gray-50 border-t border-gray-100"><button type="submit" class="w-full py-3 bg-gray-900 text-white rounded-xl text-sm font-semibold hover:bg-black transition-all shadow-sm">Save Pickup Report</button></div>
</div>
</form>
</div>
<!-- Return -->
<div x-show="conditionTab === 'return'" x-cloak>
<form method="POST" action="/dashboard/upload-condition-report.php" enctype="multipart/form-data" onsubmit="return handleConditionSubmit(event,this,'return')">
<input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
<input type="hidden" name="report_type" value="return">
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
<div class="px-6 py-4 border-b border-gray-100"><h4 class="text-sm font-semibold text-gray-900">Return Condition Report</h4></div>
<div class="p-6 space-y-6">
<div><label class="block text-xs font-medium text-gray-500 mb-2">Mileage at Return</label><input type="number" name="mileage" value="<?= crVal($condition_reports['return'],'mileage') ?>" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all outline-none" placeholder="00,000"></div>
<div><p class="text-xs font-medium text-gray-500 mb-3">Vehicle Photos</p><div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
<?php foreach ([['photo_front','Front View'],['photo_back','Rear View'],['photo_left','Left Side'],['photo_right','Right Side']] as [$f,$l]): ?>
<div class="relative group">
<label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1"><?= $l ?></label>
<div onclick="this.querySelector('input').click()" class="relative aspect-video rounded-2xl bg-gray-50 border-2 border-dashed <?= crPhoto($condition_reports['return'],$f)?'border-gray-200':'border-red-100/50' ?> hover:border-blue-300 transition-all cursor-pointer overflow-hidden flex items-center justify-center">
<?php if (crPhoto($condition_reports['return'],$f)): ?><img src="<?= crPhoto($condition_reports['return'],$f) ?>" class="w-full h-full object-cover"><div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"><span class="text-[10px] text-white font-bold uppercase tracking-widest">Replace</span></div>
<?php else: ?><div class="text-center"><svg class="w-8 h-8 text-gray-300 mb-1 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg><span class="text-[10px] text-gray-400 font-medium">Click to upload</span></div><?php endif; ?>
<input type="file" name="<?= $f ?>" class="hidden" onchange="previewConditionPhoto(this)">
</div>
</div>
<?php endforeach; ?>
</div></div>
<div><p class="text-xs font-medium text-gray-500 mb-3">Wheels & Rims</p><div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
<?php foreach ([['photo_rim1','Front Left'],['photo_rim2','Front Right'],['photo_rim3','Rear Left'],['photo_rim4','Rear Right']] as [$f,$l]): ?>
<div class="relative group">
<label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1"><?= $l ?></label>
<div onclick="this.querySelector('input').click()" class="relative aspect-video rounded-2xl bg-gray-50 border-2 border-dashed <?= crPhoto($condition_reports['return'],$f)?'border-gray-200':'border-red-100/50' ?> hover:border-blue-300 transition-all cursor-pointer overflow-hidden flex items-center justify-center">
<?php if (crPhoto($condition_reports['return'],$f)): ?><img src="<?= crPhoto($condition_reports['return'],$f) ?>" class="w-full h-full object-cover"><div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"><span class="text-[10px] text-white font-bold uppercase tracking-widest">Replace</span></div>
<?php else: ?><div class="text-center"><svg class="w-8 h-8 text-gray-300 mb-1 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg><span class="text-[10px] text-gray-400 font-medium">Click to upload</span></div><?php endif; ?>
<input type="file" name="<?= $f ?>" class="hidden" onchange="previewConditionPhoto(this)">
</div>
</div>
<?php endforeach; ?>
</div></div>
<div class="pt-4 border-t border-gray-100">
<p class="text-xs font-medium text-gray-500 mb-3">Additional Photos</p>
<div class="grid grid-cols-4 gap-3 mb-3" id="misc-return-preview">
<?php $misc = []; if ($condition_reports['return'] && !empty($condition_reports['return']['misc_photos'])) { $misc = json_decode($condition_reports['return']['misc_photos'], true) ?? []; } for ($i=0;$i<4;$i++): ?>
<?php if (!empty($misc[$i])): ?><div class="aspect-square rounded-xl bg-gray-100 overflow-hidden shadow-sm border border-gray-100"><img src="<?= htmlspecialchars($misc[$i]) ?>" class="w-full h-full object-cover"></div>
<?php else: ?><div class="aspect-square rounded-xl bg-gray-50 border-2 border-dashed border-gray-200 flex flex-col items-center justify-center p-2 text-center text-gray-300"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="2"/></svg></div><?php endif; ?>
<?php endfor; ?>
</div>
<input type="file" name="misc_photos[]" multiple accept="image/*" onchange="previewMiscPhotos(this,'return')" class="block w-full text-xs text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 transition-all cursor-pointer">
</div>
</div>
<div class="px-6 py-4 bg-gray-50 border-t border-gray-100"><button type="submit" class="w-full py-3 bg-blue-600 text-white rounded-xl text-sm font-semibold hover:bg-blue-700 transition-all shadow-sm">Save Return Report</button></div>
</div>
</form>
</div>
</div>
<?php endif; ?>
<?php if ($active_tab === 'contract'): ?>
<div class="max-w-3xl mx-auto space-y-5">
<?php if ($contract): ?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
<div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
<div class="flex items-center gap-4">
<div class="w-10 h-10 rounded-lg <?= $contract['contract_status']==='signed'?'bg-green-50':'bg-amber-50' ?> flex items-center justify-center">
<svg class="w-5 h-5 <?= $contract['contract_status']==='signed'?'text-green-600':'text-amber-600' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
</div>
<div>
<h4 class="text-sm font-semibold text-gray-900">Agreement</h4>
<div class="flex items-center gap-2 mt-0.5">
<span class="w-2 h-2 rounded-full <?= $contract['contract_status']==='signed'?'bg-green-500':'bg-amber-500' ?>"></span>
<span class="text-xs text-gray-500 capitalize"><?= htmlspecialchars($contract['contract_status']) ?></span>
</div>
</div>
</div>
<div class="flex items-center gap-2">
<button onclick="viewContractPDF(<?= $booking['id'] ?>)" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50 transition-all text-sm font-medium shadow-sm">
<svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
View
</button>
<?php if ($contract['contract_status'] === 'signed'): ?>
<a href="/api/download-contract.php?booking_id=<?= $booking['id'] ?>" target="_blank" class="flex items-center gap-2 px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-black transition-all text-sm font-medium shadow-sm">
<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 4v12m0 0l-4-4m4 4l4-4"></path></svg>
Export
</a>
<?php else: ?>
<span class="px-3 py-1.5 bg-amber-50 text-amber-700 rounded-lg border border-amber-100 text-xs font-medium">Awaiting Signature</span>
<?php endif; ?>
</div>
</div>
<div class="p-6 space-y-4">
<div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
<div><p class="text-xs text-gray-400">Contract ID</p><p class="text-sm font-semibold text-gray-900">#<?= $contract['id'] ?></p></div>
<div class="text-right"><p class="text-xs text-gray-400">Status</p><p class="text-sm font-semibold text-gray-900 capitalize"><?= htmlspecialchars($contract['contract_status']) ?></p></div>
</div>
<?php if ($contract['signed_at']): ?>
<div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
<div><p class="text-xs text-gray-400">Signed At</p><p class="text-sm font-semibold text-gray-900"><?= date('M d, Y H:i', strtotime($contract['signed_at'])) ?></p></div>
</div>
<?php endif; ?>
</div>
</div>
<?php elseif (count($available_templates) > 0): ?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
<h4 class="text-sm font-semibold text-gray-900 mb-4">Contract Templates Available</h4>
<p class="text-sm text-gray-500 mb-4">Templates exist but contract generation is managed from the Bookings page.</p>
<div class="space-y-2">
<?php foreach ($available_templates as $tmpl): ?>
<div class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-left">
<span class="text-sm font-medium text-gray-900"><?= htmlspecialchars($tmpl['name']) ?></span>
</div>
<?php endforeach; ?>
</div>
</div>
<?php else: ?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center">
<svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
<h4 class="text-sm font-semibold text-gray-900 mb-1">No Contract</h4>
<p class="text-sm text-gray-500">No contract has been generated for this booking yet. Create a contract template in settings to generate one.</p>
</div>
<?php endif; ?>
</div>
<?php endif; ?>
</div>
</div>
</div>
<!-- TOAST -->
<div id="notification" class="fixed bottom-6 right-6 z-[200] transform translate-y-20 opacity-0 transition-all duration-300 pointer-events-none">
<div class="bg-gray-900 text-white px-5 py-3 rounded-xl shadow-xl flex items-center gap-3">
<svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
<span id="notification-text" class="text-sm font-medium"></span>
</div>
</div>
<script>
function showNotification(message,type='success'){
const n=document.getElementById('notification'),t=document.getElementById('notification-text');
t.textContent=message;n.classList.remove('translate-y-20','opacity-0','pointer-events-none');
setTimeout(()=>n.classList.add('translate-y-20','opacity-0','pointer-events-none'),3000);
}
function updateBookingStatus(bookingId,status){
showConfirmation('Update Booking Status','Are you sure you want to update this booking status?',()=>{
fetch('/dashboard/update-booking-status.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({booking_id:bookingId,status:status})})
.then(r=>r.json()).then(d=>{if(d.success){showNotification('Status updated','success');setTimeout(()=>location.reload(),800);}else{showNotification(d.message||'Error','error');}})
.catch(()=>showNotification('Error updating status','error'));
},'Update Status','bg-blue-600 hover:bg-blue-700');
}
function updateSecurityDeposit(bookingId){
const amount=document.getElementById('deposit-amount').value;
const status=document.getElementById('deposit-status').value;
const method=document.getElementById('deposit-method').value;
fetch('/dashboard/update-booking-deposit.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({booking_id:bookingId,amount:amount,status:status,method:method})})
.then(r=>r.json()).then(d=>{if(d.success){showNotification('Deposit updated','success');setTimeout(()=>location.reload(),800);}else{showNotification(d.message||'Error','error');}})
.catch(()=>showNotification('Error updating deposit','error'));
}

function handleConditionSubmit(event, form, type) {
    event.preventDefault();
    if (type === 'pickup') {
        const fields = ['photo_front','photo_back','photo_left','photo_right','photo_rim1','photo_rim2','photo_rim3','photo_rim4'];
        let missing = [];
        fields.forEach(f => {
            const input = form.querySelector('input[name="'+f+'"]');
            const hasNew = input && input.files && input.files.length > 0;
            const container = input ? input.closest('.relative') : null;
            const hasExisting = container && container.querySelector('img') !== null;
            if (!hasExisting && !hasNew) missing.push(f.replace('photo_','').replace('_',' ').replace(/\b\w/g,c=>c.toUpperCase()));
        });
        if (missing.length > 0) {
            showValidationError('Please upload all mandatory photos: ' + missing.join(', ') + '. Front, Back, Sides and 4 Rims are required.');
            return false;
        }
    }
    const btn = form.querySelector('button[type="submit"]');
    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = 'Saving...';
    const formData = new FormData(form);
    fetch('/dashboard/upload-condition-report.php', { method: 'POST', body: formData })
    .then(async r => { const text = await r.text(); try { return JSON.parse(text); } catch(e) { throw new Error('Server Error: ' + text); } })
    .then(d => { if (d.success) { showNotification(d.message || 'Report saved','success'); setTimeout(()=>location.reload(),800); } else { showNotification(d.message || 'Failed to save','error'); btn.disabled = false; btn.innerText = originalText; } })
    .catch(err => { showNotification('Upload failed: ' + err.message,'error'); btn.disabled = false; btn.innerText = originalText; });
    return false;
}

function previewConditionPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        const container = input.parentElement;
        reader.onload = function(e) {
            let img = container.querySelector('img');
            if (!img) {
                const ph = container.querySelector('.text-center');
                if (ph) ph.classList.add('hidden');
                img = document.createElement('img');
                img.className = 'w-full h-full object-cover';
                container.appendChild(img);
            }
            img.src = e.target.result;
            const ov = container.querySelector('.absolute.inset-0');
            if (ov) ov.classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function previewMiscPhotos(input, type) {
    const container = document.getElementById('misc-' + type + '-preview');
    if (!container) return;
    const files = Array.from(input.files).slice(0, 4);
    container.innerHTML = '';
    for (let i = 0; i < 4; i++) {
        const div = document.createElement('div');
        if (files[i]) {
            const r = new FileReader();
            r.onload = function(e) {
                div.className = 'aspect-square rounded-xl bg-gray-100 overflow-hidden shadow-sm border border-gray-100';
                div.innerHTML = '<img src="' + e.target.result + '" class="w-full h-full object-cover">';
            };
            r.readAsDataURL(files[i]);
        } else {
            div.className = 'aspect-square rounded-xl bg-gray-50 border-2 border-dashed border-gray-200 flex flex-col items-center justify-center p-2 text-center text-gray-300';
            div.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="2"/></svg>';
        }
        container.appendChild(div);
    }
}

function showValidationError(message) {
    const overlay = document.createElement('div');
    overlay.className = 'fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4';
    overlay.innerHTML = '<div class="bg-white rounded-2xl max-w-sm w-full shadow-2xl overflow-hidden"><div class="p-6 text-center"><div class="w-16 h-16 bg-red-50 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg></div><h3 class="text-xl font-bold text-gray-900 mb-2">Upload Required</h3><p class="text-gray-600 text-sm mb-6">' + message + '</p><button onclick="this.closest(\'.fixed\').remove()" class="w-full py-3 bg-gray-900 text-white rounded-xl font-bold hover:bg-black transition-all">Got it</button></div></div>';
    document.body.appendChild(overlay);
}

function viewContractPDF(bookingId) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] flex items-center justify-center p-4';
    modal.innerHTML = '<div class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden"><div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-white"><div><h3 class="text-xl font-black uppercase tracking-tighter">Contract Preview</h3><p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Agreement Details</p></div><button class="close-contract w-10 h-10 flex items-center justify-center text-gray-400 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-all"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg></button></div><div class="p-8 overflow-y-auto bg-gray-50 flex-1"><iframe src="/dashboard/preview-contract.php?booking_id=' + bookingId + '" class="w-full border-0 rounded-xl" style="height:60vh;"></iframe></div><div class="p-6 border-t border-gray-100 bg-white flex justify-end"><button class="close-contract px-6 py-3 bg-gray-900 text-white rounded-xl font-bold hover:bg-black transition-all">Close</button></div></div>';
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
    modal.querySelectorAll('.close-contract').forEach(b => b.addEventListener('click', () => { modal.remove(); document.body.style.overflow = ''; }));
}

function openCompleteTripModal(bookingId) {
    window._completeTripBookingId = bookingId;
    document.getElementById('completeTripModal').classList.remove('hidden');
    document.getElementById('completeTripMileage').value = '';
    document.getElementById('completeTripMileage').focus();
}
function closeCompleteTripModal() {
    document.getElementById('completeTripModal').classList.add('hidden');
    window._completeTripBookingId = null;
}
function submitCompleteTrip() {
    const mileage = document.getElementById('completeTripMileage').value.trim();
    if (!mileage || parseInt(mileage) <= 0) {
        showNotification('Please enter a valid return mileage', 'error');
        return;
    }
    fetch('/dashboard/update-booking-status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ booking_id: window._completeTripBookingId, status: 'completed', mileage: parseInt(mileage) })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            showNotification('Trip completed. Add return photos now.', 'success');
            setTimeout(() => {
                window.location.href = '?id=' + window._completeTripBookingId + '&tab=condition';
            }, 800);
        } else {
            showNotification(d.message || 'Error completing trip', 'error');
        }
    })
    .catch(() => showNotification('Error completing trip', 'error'));
}
function openStartTripModal(bookingId) {
    window._startTripBookingId = bookingId;
    document.getElementById('startTripModal').classList.remove('hidden');
    document.getElementById('startTripMileage').value = '';
    document.getElementById('startTripMileage').focus();
}
function closeStartTripModal() {
    document.getElementById('startTripModal').classList.add('hidden');
    window._startTripBookingId = null;
}
function submitStartTrip() {
    const mileage = document.getElementById('startTripMileage').value.trim();
    if (!mileage || parseInt(mileage) <= 0) {
        showNotification('Please enter a valid mileage', 'error');
        return;
    }
    fetch('/dashboard/update-booking-status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ booking_id: window._startTripBookingId, status: 'active', mileage: parseInt(mileage) })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            showNotification('Trip started. Add pickup photos now.', 'success');
            setTimeout(() => {
                window.location.href = '?id=' + window._startTripBookingId + '&tab=condition';
            }, 800);
        } else {
            showNotification(d.message || 'Error starting trip', 'error');
        }
    })
    .catch(() => showNotification('Error starting trip', 'error'));
}
function generateContract(bookingId, templateId) {
    showConfirmation('Generate Contract','Generate contract for this booking?',()=>{
    fetch('/dashboard/generate-contract.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ booking_id: bookingId, template_id: templateId }) })
    .then(r => r.json()).then(d => { if (d.success) { showNotification('Contract generated','success'); setTimeout(()=>location.reload(),800); } else { showNotification(d.message || 'Failed to generate','error'); } })
    .catch(() => showNotification('Error generating contract','error'));
    },'Generate','bg-blue-600 hover:bg-blue-700');
}
</script>

<!-- Complete Trip Mileage Modal -->
<div id="completeTripModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-xl font-black uppercase tracking-tighter text-gray-900">Complete Trip</h3>
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Record return mileage</p>
            </div>
            <button onclick="closeCompleteTripModal()" class="w-10 h-10 flex items-center justify-center text-gray-400 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="space-y-4">
            <div>
                <label class="block text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] mb-2">Return Mileage <span class="text-red-500">*</span></label>
                <input type="number" id="completeTripMileage" placeholder="e.g. 24850" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" min="0" required>
                <p class="text-xs text-gray-400 mt-2">Enter the current vehicle mileage at return. You can add photos on the next screen.</p>
            </div>
            <div class="flex gap-3 pt-2">
                <button onclick="closeCompleteTripModal()" class="flex-1 px-5 py-3 bg-gray-100 text-gray-700 rounded-xl text-sm font-semibold hover:bg-gray-200 transition-all">Cancel</button>
                <button onclick="submitCompleteTrip()" class="flex-1 px-5 py-3 bg-emerald-600 text-white rounded-xl text-sm font-semibold hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-200">Complete</button>
            </div>
        </div>
    </div>
</div>

<!-- Start Trip Mileage Modal -->
<div id="startTripModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-xl font-black uppercase tracking-tighter text-gray-900">Start Trip</h3>
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Record pickup mileage</p>
            </div>
            <button onclick="closeStartTripModal()" class="w-10 h-10 flex items-center justify-center text-gray-400 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="space-y-4">
            <div>
                <label class="block text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] mb-2">Pickup Mileage <span class="text-red-500">*</span></label>
                <input type="number" id="startTripMileage" placeholder="e.g. 24500" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 font-bold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" min="0" required>
                <p class="text-xs text-gray-400 mt-2">Enter the current vehicle mileage at pickup. You can add photos on the next screen.</p>
            </div>
            <div class="flex gap-3 pt-2">
                <button onclick="closeStartTripModal()" class="flex-1 px-5 py-3 bg-gray-100 text-gray-700 rounded-xl text-sm font-semibold hover:bg-gray-200 transition-all">Cancel</button>
                <button onclick="submitStartTrip()" class="flex-1 px-5 py-3 bg-blue-600 text-white rounded-xl text-sm font-semibold hover:bg-blue-700 transition-all shadow-lg shadow-blue-200">Continue</button>
            </div>
        </div>
    </div>
</div>

    <?php include __DIR__ . '/../includes/confirmation-modal.php'; ?>
</body>
</html>
