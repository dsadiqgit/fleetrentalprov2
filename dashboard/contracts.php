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
$tenant_id = $_SESSION['tenant_id'];

// Get current tab
$current_tab = $_GET['tab'] ?? 'all';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $contract_id = intval($_POST['contract_id'] ?? 0);
    $action = $_POST['action'];

    if ($contract_id) {
        if ($action === 'soft_delete') {
            $stmt = $pdo->prepare("UPDATE contracts SET is_deleted = 1 WHERE id = ? AND tenant_id = ? AND contract_status != 'signed'");
            $stmt->execute([$contract_id, $tenant_id]);
            header("Location: contracts.php?success=deleted");
            exit;
        }
        elseif ($action === 'restore') {
            $stmt = $pdo->prepare("UPDATE contracts SET is_deleted = 0 WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$contract_id, $tenant_id]);
            header("Location: contracts.php?tab=deleted&success=restored");
            exit;
        }
        elseif ($action === 'permanent_delete') {
            $stmt = $pdo->prepare("DELETE FROM contracts WHERE id = ? AND tenant_id = ? AND contract_status != 'signed'");
            $stmt->execute([$contract_id, $tenant_id]);
            header("Location: contracts.php?tab=deleted&success=permanent");
            exit;
        }
    }
}

// Get search and filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query
$query = "SELECT c.*, b.customer_name, b.customer_email, v.brand, v.model, v.year 
          FROM contracts c
          JOIN bookings b ON c.booking_id = b.id
          LEFT JOIN vehicles v ON b.vehicle_id = v.id
          WHERE c.tenant_id = ?";
$params = [$tenant_id];

if ($current_tab === 'deleted') {
    $query .= " AND c.is_deleted = 1";
}
else {
    $query .= " AND c.is_deleted = 0";
}

if ($search) {
    $query .= " AND (b.customer_name LIKE ? OR b.customer_email LIKE ? OR b.id LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($status_filter) {
    $query .= " AND c.contract_status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$contracts = $stmt->fetchAll();

// Statistics
$total_contracts = count($contracts);
$signed_contracts = count(array_filter($contracts, fn($c) => $c['contract_status'] === 'signed'));
$pending_contracts = $total_contracts - $signed_contracts;

// Get tenant info for sidebar
$stmt = $pdo->prepare("SELECT * FROM tenants WHERE id = ?");
$stmt->execute([$tenant_id]);
$tenant = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contracts -
        <?= SITE_NAME?>
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

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
    <!-- Updated Dropdown -->
    <script src="/app/custom-select.js" defer></script>
</head>

<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside id="sidebar"
        class="fixed lg:static top-14 lg:top-0 bottom-0 left-0 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out z-40 lg:flex">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Bar -->
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <nav class="text-sm text-gray-500 mb-1">
                        <a href="/dashboard/" class="hover:text-gray-700">Dashboard</a>
                        <span class="mx-2">/</span>
                        <span class="text-gray-900">Contracts</span>
                    </nav>
                    <h1 class="text-2xl text-gray-900">Digital Contracts</h1>
                    <p class="text-sm text-gray-600 mt-1">Monitor and manage all rental agreements</p>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto p-6">
            <div class="max-w-7xl mx-auto">
                <!-- Page Toggle -->
                <div class="flex p-1 bg-gray-100 rounded-xl w-fit mb-6 ml-auto">
                    <a href="/dashboard/contracts.php"
                        class="px-5 py-2.5 text-sm font-normal rounded-lg transition-all <?= $current_page === 'contracts.php' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-500 hover:text-gray-700'?>">
                        Agreements
                    </a>
                    <a href="/dashboard/e-signing.php"
                        class="px-5 py-2.5 text-sm font-normal rounded-lg transition-all <?= $current_page === 'e-signing.php' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-500 hover:text-gray-700'?>">
                        Templates
                    </a>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Issued</p>
                                <p class="text-2xl  text-gray-900 mt-1">
                                    <?= $total_contracts?>
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Signed</p>
                                <p class="text-2xl  text-green-600 mt-1">
                                    <?= $signed_contracts?>
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Pending Signature</p>
                                <p class="text-2xl  text-amber-600 mt-1">
                                    <?= $pending_contracts?>
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
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
        echo "Contract moved to bin.";
    elseif ($_GET['success'] === 'restored')
        echo "Contract restored successfully.";
    elseif ($_GET['success'] === 'permanent')
        echo "Contract deleted permanently.";
    else
        echo "Action completed successfully.";
?>
                    </span>
                </div>
                <?php
endif; ?>

                <!-- Tabs -->
                <div class="flex items-center gap-4 mb-6 border-b border-gray-200">
                    <a href="/dashboard/contracts.php?tab=all"
                        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors <?= $current_tab === 'all' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'?>">
                        All Contracts
                    </a>
                    <a href="/dashboard/contracts.php?tab=deleted"
                        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors flex items-center gap-2 <?= $current_tab === 'deleted' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                        Deleted
                    </a>
                </div>

                <!-- Filters -->
                <div
                    class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm mb-6 flex flex-col md:flex-row gap-4 items-center justify-between">
                    <form method="GET" class="flex flex-col md:flex-row gap-4 w-full md:w-auto">
                        <div class="relative">
                            <input type="text" name="search" value="<?= htmlspecialchars($search)?>"
                                placeholder="Search customer or booking ID..."
                                class="pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full md:w-64 text-sm">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <select name="status"
                            class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            <option value="">All Statuses</option>
                            <option value="signed" <?=$status_filter==='signed' ? 'selected' : ''?>>Signed</option>
                            <option value="pending" <?=$status_filter==='pending' ? 'selected' : ''?>>Pending</option>
                        </select>
                        <button type="submit"
                            class="bg-gray-900 text-white px-6 py-2 rounded-lg font-medium hover:bg-black transition text-sm">Filter</button>
                    </form>
                    <a href="/dashboard/e-signing.php"
                        class="text-sm font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                            </path>
                        </svg>
                        Manage Templates
                    </a>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="px-6 py-4 text-xs  text-gray-500 uppercase tracking-wider">Booking /
                                        Vehicle</th>
                                    <th class="px-6 py-4 text-xs  text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-4 text-xs  text-gray-500 uppercase tracking-wider">Issued Date
                                    </th>
                                    <th class="px-6 py-4 text-xs  text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-4 text-xs  text-gray-500 uppercase tracking-wider text-right">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if (empty($contracts)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">No contracts found.
                                    </td>
                                </tr>
                                <?php
endif; ?>
                                <?php foreach ($contracts as $contract): ?>
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm  text-gray-900">#
                                                <?= str_pad($contract['booking_id'], 5, '0', STR_PAD_LEFT)?>
                                            </span>
                                            <span class="text-xs text-gray-500">
                                                <?= htmlspecialchars($contract['brand'] . ' ' . $contract['model'])?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($contract['customer_name'])?>
                                            </span>
                                            <span class="text-xs text-gray-500">
                                                <?= htmlspecialchars($contract['customer_email'])?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?= date('M j, Y', strtotime($contract['created_at']))?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($contract['contract_status'] === 'signed'): ?>
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs  bg-green-50 text-green-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                            Signed
                                        </span>
                                        <?php
    else: ?>
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs  bg-amber-50 text-amber-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            Pending
                                        </span>
                                        <?php
    endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <?php if ($current_tab === 'all'): ?>
                                            <button onclick="viewContract(<?= $contract['booking_id']?>)"
                                                class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                                title="View Contract">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                    </path>
                                                </svg>
                                            </button>
                                            <?php if ($contract['contract_status'] === 'signed'): ?>
                                            <a href="/api/download-contract.php?booking_id=<?= $contract['booking_id']?>"
                                                target="_blank"
                                                class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                                title="Download Signed PDF">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                    </path>
                                                </svg>
                                            </a>
                                            <?php
        else: ?>
                                            <button
                                                onclick="copyToClipboard('<?= SITE_URL?>/templates/contract-sign.php?booking_id=<?= $contract['booking_id']?>&token=<?= $contract['signing_token']?>')"
                                                class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                                title="Copy Signing Link">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.826L10.242 9.172a4 4 0 015.656 0l4 4a4 4 0 01-5.656 5.656l-1.102 1.101">
                                                    </path>
                                                </svg>
                                            </button>
                                            <?php
        endif; ?>
                                            <?php if ($contract['contract_status'] !== 'signed'): ?>
                                            <form method="POST" class="inline" id="delete-form-<?= $contract['id']?>">
                                                <input type="hidden" name="contract_id" value="<?= $contract['id']?>">
                                                <input type="hidden" name="action" value="soft_delete">
                                                <button type="button"
                                                    onclick="showConfirmation('Move to Bin', 'Are you sure you want to move this contract to the bin?', function() { document.getElementById('delete-form-<?= $contract['id']?>').submit(); }, 'Move to Bin', 'bg-red-600 hover:bg-red-700')"
                                                    class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                    title="Move to Bin">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                        </path>
                                                    </svg>
                                                </button>
                                            </form>
                                            <?php
        endif; ?>
                                            <?php
    else: ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="contract_id" value="<?= $contract['id']?>">
                                                <input type="hidden" name="action" value="restore">
                                                <button type="submit"
                                                    class="px-3 py-1 text-xs  text-green-600 hover:bg-green-50 rounded-lg transition-colors border border-green-200">RESTORE</button>
                                            </form>
                                            <?php if ($contract['contract_status'] !== 'signed'): ?>
                                            <form method="POST" class="inline"
                                                id="perm-delete-form-<?= $contract['id']?>">
                                                <input type="hidden" name="contract_id" value="<?= $contract['id']?>">
                                                <input type="hidden" name="action" value="permanent_delete">
                                                <button type="button"
                                                    onclick="showConfirmation('Delete Permanently', 'PERMANENTLY delete this contract? This cannot be undone.', function() { document.getElementById('perm-delete-form-<?= $contract['id']?>').submit(); }, 'Delete', 'bg-red-600 hover:bg-red-700')"
                                                    class="px-3 py-1 text-xs  text-red-600 hover:bg-red-50 rounded-lg transition-colors border border-red-200 uppercase">Delete
                                                    Permanently</button>
                                            </form>
                                            <?php
        endif; ?>
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
                </div>
            </div>
        </main>
    </div>

    <div id="toast"
        class="fixed bottom-6 right-6 bg-gray-900 text-white px-6 py-3 rounded-xl shadow-2xl transform translate-y-24 transition-transform duration-300 z-[60]">
        Signing link copied to clipboard!
    </div>

    <!-- Contract Preview Modal -->
    <div id="contractModal"
        class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden">
            <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-white text-gray-900">
                <div>
                    <h3 class="text-xl font-black uppercase tracking-tighter">Contract Preview</h3>
                    <p id="contractModalSubtitle" class="text-[10px] text-gray-400  uppercase tracking-widest mt-1">
                        Booking Details</p>
                </div>
                <button onclick="closeContractModal()"
                    class="w-10 h-10 flex items-center justify-center text-gray-400 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="contractModalContent" class="p-8 overflow-y-auto bg-gray-50 flex-1 prose max-w-none">
                <!-- Content will be injected here -->
            </div>
            <div class="p-6 border-t border-gray-100 bg-white flex justify-end">
                <button onclick="closeContractModal()"
                    class="px-8 py-3 bg-gray-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-black transition-all shadow-xl shadow-gray-200">Close
                    Preview</button>
            </div>
        </div>
    </div>

    <script>
        function viewContract(bookingId) {
            const modal = document.getElementById('contractModal');
            const content = document.getElementById('contractModalContent');
            const subtitle = document.getElementById('contractModalSubtitle');

            modal.classList.remove('hidden');
            subtitle.textContent = `Booking #${bookingId.toString().padStart(5, '0')}`;

            // Set up iframe for PDF preview
            content.innerHTML = `
                <div class="w-full h-full min-h-[600px] flex flex-col">
                    <iframe src="/dashboard/preview-contract.php?booking_id=${bookingId}" class="w-full flex-1 border-0 rounded-xl" style="height: 60vh;"></iframe>
                </div>
            `;
        }

        function closeContractModal() {
            document.getElementById('contractModal').classList.add('hidden');
        }

        // Close on outside click
        document.getElementById('contractModal').addEventListener('click', function (e) {
            if (e.target === this) closeContractModal();
        });

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                const toast = document.getElementById('toast');
                toast.classList.remove('translate-y-24');
                setTimeout(() => {
                    toast.classList.add('translate-y-24');
                }, 3000);
            });
        }
    </script>

    <?php include __DIR__ . '/../includes/confirmation-modal.php'; ?>
</body>

</html>