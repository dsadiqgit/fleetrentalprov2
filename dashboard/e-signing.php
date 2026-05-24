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

// Check if user has a tenant
if (!$_SESSION['tenant_id']) {
    die('Error: No tenant associated with this account.');
}

$pdo = getDB();

// Create contract_templates table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS contract_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        status ENUM('draft', 'published') DEFAULT 'draft',
        is_default BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
    )");

    // Create e_signing_settings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS e_signing_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL UNIQUE,
        enabled BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
    )");
}
catch (Exception $e) {
// Tables might already exist
}

// Handle toggle e-signing feature
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_esigning'])) {
    $enabled = isset($_POST['enabled']) ? 1 : 0;

    $stmt = $pdo->prepare("INSERT INTO e_signing_settings (tenant_id, enabled) VALUES (?, ?) 
                          ON DUPLICATE KEY UPDATE enabled = ?");
    $stmt->execute([$_SESSION['tenant_id'], $enabled, $enabled]);

    header('Location: /dashboard/e-signing.php');
    exit;
}

// Handle delete template
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM contract_templates WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$_GET['delete'], $_SESSION['tenant_id']]);
    header('Location: /dashboard/e-signing.php');
    exit;
}

// Get e-signing settings
$stmt = $pdo->prepare("SELECT * FROM e_signing_settings WHERE tenant_id = ?");
$stmt->execute([$_SESSION['tenant_id']]);
$esigning_settings = $stmt->fetch();

if (!$esigning_settings) {
    // Create default settings
    $stmt = $pdo->prepare("INSERT INTO e_signing_settings (tenant_id, enabled) VALUES (?, FALSE)");
    $stmt->execute([$_SESSION['tenant_id']]);
    $esigning_settings = ['enabled' => false];
}

// Get all contract templates
$stmt = $pdo->prepare("SELECT * FROM contract_templates WHERE tenant_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['tenant_id']]);
$templates = $stmt->fetchAll();

// Check if default template exists
$has_default = false;
foreach ($templates as $template) {
    if ($template['is_default']) {
        $has_default = true;
        break;
    }
}

// Create default template if none exists
if (!$has_default && empty($templates)) {
    $default_content = "This Vehicle Rental Agreement (\"Agreement\") is entered into between the Rental Provider (\"Owner\") and the Renter named below. By signing this document, the Renter agrees to the terms and conditions outlined herein.

1. Rental Details

Field | Information
--- | ---
Vehicle Name | {{vehicle_name}}
Vehicle Registration | {{vehicle_registration}}
Renter Full Name | {{renter_full_name}}
Booking Reference | {{booking_reference}}

2. Duration and Costs

Pickup Date & Time: {{pickup_datetime}}
Return Date & Time: {{return_datetime}}
Total Booking Price: {{booking_total_price}}
Security Deposit: {{security_deposit}}

Note: The Security Deposit will be held for the duration of the rental and returned within 5–7 business days following a successful post-rental inspection, provided no damages or extra fees are incurred.

3. Mileage and Usage

Included Distance: {{included_distance}}
Excess Mileage Fee: {{excess_distance_fee}} per unit of distance over the included limit.
Deductible Amount: {{deductible_amount}} (This is the maximum amount the Renter is liable for in the event of damage to the vehicle, subject to insurance terms).

4. Terms and Conditions

Vehicle Condition: The Renter acknowledges receiving the vehicle in good overall condition and agrees to return it in the same condition, fair wear and tear excepted.

Usage: The vehicle shall not be used for any illegal purposes, off-roading, or racing.

Late Returns: Returns exceeding the {{return_datetime}} by more than 59 minutes may be subject to additional daily rental charges.

Fuel: The vehicle must be returned with the same level of fuel as provided at pickup unless otherwise specified.

5. Authorization

By signing below, the Renter confirms they have read, understood, and agreed to the terms of this Agreement.

Current Date & Time: {{current_datetime}}

Renter Signature:
(Sign below)
{{signature}}";

    $stmt = $pdo->prepare("INSERT INTO contract_templates (tenant_id, name, content, status, is_default) VALUES (?, ?, ?, 'published', TRUE)");
    $stmt->execute([$_SESSION['tenant_id'], 'Default contract template', $default_content]);

    // Refresh templates
    $stmt = $pdo->prepare("SELECT * FROM contract_templates WHERE tenant_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['tenant_id']]);
    $templates = $stmt->fetchAll();
}

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
        // If date parsing fails, default to 0
        $trial_days_remaining = 0;
        $trial_percentage = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-signing -
        <?= SITE_NAME?>
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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

        #previewContent .no-print,
        #previewContent .absolute.-left-12 {
            display: none !important;
        }
    </style>
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
    <script src="/app/custom-select.js" defer></script>
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
                        <span class="text-gray-900">Contracts</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-gray-900">Digital Contracts</h1>
                    <p class="text-sm text-gray-600 mt-1">Manage the e-signing feature and your contract templates</p>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-auto bg-gray-50">
            <div class="px-4 sm:px-6 lg:px-8 py-8">

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

                <!-- E-Sign Feature Toggle -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                    <form method="POST" class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="enabled" class="sr-only peer"
                                        <?= $esigning_settings['enabled'] ? 'checked' : '' ?>
                                    onchange="this.form.submit()">
                                    <input type="hidden" name="toggle_esigning" value="1">
                                    <div
                                        class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500">
                                    </div>
                                </label>
                                <span class="text-lg font-semibold text-gray-900">E-Sign feature</span>
                            </div>
                            <p class="text-sm text-gray-600">When turned on, your customers will sign rental contracts
                                electronically.</p>
                        </div>
                    </form>
                </div>

                <!-- Contract Templates Section -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div
                        class="p-4 sm:p-6 border-b border-gray-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <h2 class="text-lg sm:text-xl font-semibold text-gray-900">Default contract template</h2>
                        <div
                            class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3 w-full sm:w-auto">
                            <a href="/dashboard/contract-designer.php"
                                class="bg-blue-600 text-white px-3 sm:px-6 py-2 sm:py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01">
                                    </path>
                                </svg>
                                <span>Create a Contract Template</span>
                            </a>
                        </div>
                    </div>

                    <div class="p-6">
                        <?php if (empty($templates)): ?>
                        <div class="text-center py-12">
                            <div class="text-6xl mb-4">📄</div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">No contract templates yet</h3>
                            <p class="text-gray-600 mb-6">Create your first contract template to get started</p>
                            <a href="/dashboard/contract-designer.php"
                                class="inline-block px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                Create Contract Template
                            </a>
                        </div>
                        <?php
else: ?>
                        <?php foreach ($templates as $template): ?>
                        <div class="border border-gray-200 rounded-lg p-4 mb-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <?= ucfirst($template['status'])?>
                                    </span>
                                    <h3 class="text-lg font-medium text-gray-900">
                                        <?= htmlspecialchars($template['name'])?>
                                    </h3>
                                </div>
                                <div
                                    class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-2 sm:space-x-0">
                                    <a href="/dashboard/contract-designer.php?id=<?= $template['id']?>"
                                        class="flex items-center justify-center px-3 sm:px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 border border-blue-300 rounded-lg transition">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                            </path>
                                        </svg>
                                        Edit
                                    </a>
                                    <button onclick="openPreviewModal(<?= $template['id']?>)"
                                        class="flex items-center justify-center px-3 sm:px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 border border-gray-300 rounded-lg transition">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                            </path>
                                        </svg>
                                        Preview
                                    </button>

                                    <button
                                        onclick="showConfirmation('Delete Template', 'Are you sure you want to delete this template? This action cannot be undone.', function() { window.location.href='/dashboard/e-signing.php?delete=<?= $template['id']?>'; }, 'Delete', 'bg-red-600 hover:bg-red-700')"
                                        class="flex items-center justify-center px-3 sm:px-4 py-2 text-sm text-red-600 hover:bg-red-50 border border-red-300 rounded-lg transition">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg>
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php
    endforeach; ?>
                        <?php
endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <div id="previewModal"
            class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-hidden">
                <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-xl font-semibold text-gray-900">Contract Preview</h3>
                    <button onclick="closePreviewModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="previewContent" class="p-6 overflow-y-auto max-h-[calc(90vh-140px)] prose max-w-none">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>


        <script>
            let currentTemplateId = null;

            function openPreviewModal(templateId) {
                currentTemplateId = templateId;
                document.getElementById('previewModal').classList.remove('hidden');
                document.getElementById('previewContent').innerHTML = `
                    <div class="w-full h-full min-h-[600px] flex flex-col">
                        <iframe src="/dashboard/preview-contract.php?template_id=${templateId}" class="w-full flex-1 border-0 rounded-xl" style="height: 60vh;"></iframe>
                    </div>
                `;
            }

            function closePreviewModal() {
                document.getElementById('previewModal').classList.add('hidden');
            }


            // Close modals when clicking outside
            document.getElementById('previewModal').addEventListener('click', function (e) {
                if (e.target === this) {
                    closePreviewModal();
                }
            });

        </script>

        <?php include __DIR__ . '/../includes/confirmation-modal.php'; ?>


</body>

</html>