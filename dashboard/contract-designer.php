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

// Check if editing existing template
$edit_mode = isset($_GET['id']) && is_numeric($_GET['id']);
$template = null;

if ($edit_mode) {
    $stmt = $pdo->prepare("SELECT * FROM contract_templates WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$_GET['id'], $_SESSION['tenant_id']]);
    $template = $stmt->fetch();

    if (!$template) {
        redirect('/dashboard/e-signing.php');
    }
}

// Get tenant information
$stmt = $pdo->prepare("SELECT * FROM tenants WHERE id = ?");
$stmt->execute([$_SESSION['tenant_id']]);
$tenant = $stmt->fetch();

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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract Designer -
        <?= SITE_NAME?>
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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

        .contract-section {
            transition: all 0.2s;
        }

        .contract-section:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        [contenteditable="true"]:focus {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
            border-radius: 4px;
        }

        .contract-preview {
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        @media print {
            .no-print {
                display: none !important;
            }
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
        <div class="flex items-center gap-2">
            <button onclick="toggleSettingsModal()"
                class="p-1.5 hover:bg-gray-100 rounded-lg transition-colors lg:hidden">
                <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                    </path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </button>
            <button onclick="saveTemplate()"
                class="px-2 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-medium hover:bg-blue-700 flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4">
                    </path>
                </svg>
                Save
            </button>
            <a href="/dashboard/e-signing.php" class="p-1.5 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
            </a>
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

    <div class="flex-1 flex overflow-hidden pt-14 lg:pt-0" x-data="{ controlsHidden: false }">
        <!-- Left Panel - Settings -->
        <div class="bg-white border-r border-gray-200 overflow-y-auto lg:block hidden transition-all duration-300 shadow-sm"
            :class="controlsHidden ? 'lg:w-16' : 'lg:w-80'"
            id="settingsPanel">
            <div class="p-6 transition-all duration-300" :class="controlsHidden ? 'px-2 py-4' : 'p-6'">
                <div class="flex items-center justify-between mb-6" :class="controlsHidden && 'flex-col gap-4 mb-2'">
                    <h2 class="text-xl font-bold text-gray-900 truncate" x-show="!controlsHidden">Contract Designer</h2>
                    <div class="flex items-center gap-1" :class="controlsHidden && 'flex-col'">
                        <button @click="controlsHidden = !controlsHidden" 
                                class="p-2 text-gray-400 hover:text-black hover:bg-gray-100 rounded-lg transition-all"
                                :title="controlsHidden ? 'Expand Sidebar' : 'Collapse Sidebar'">
                            <svg x-show="!controlsHidden" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            <svg x-show="controlsHidden" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                        <a href="/dashboard/e-signing.php" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors"
                           x-show="!controlsHidden">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </a>
                    </div>
                </div>

                <div x-show="!controlsHidden" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                    <!-- Template Name -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-900 mb-2">Template Name</label>
                    <input type="text" id="templateName"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        placeholder="e.g., Standard Rental Agreement"
                        value="<?= htmlspecialchars($template['name'] ?? 'Vehicle Rental Contract')?>">
                </div>

                <!-- Brand Color -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-900 mb-2">Brand Color</label>
                    <div class="flex items-center space-x-3">
                        <input type="color" id="brandColor" value="#93C5FD"
                            class="w-12 h-12 rounded border border-gray-300 cursor-pointer">
                        <input type="text" id="brandColorText" value="#93C5FD"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono"
                            onchange="document.getElementById('brandColor').value = this.value; updateBrandColor()">
                    </div>
                </div>

                <!-- Logo Upload -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-900 mb-2">Company Logo</label>
                    <div id="logoPreview" class="mb-3 hidden">
                        <img id="logoImage" src="" alt="Logo" class="h-16 object-contain">
                    </div>
                    <button onclick="openMediaSelector(handleLogoSelect)"
                        class="block w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-lg text-center cursor-pointer hover:border-gray-400 transition bg-white group">
                        <svg class="w-8 h-8 mx-auto mb-2 text-gray-400 group-hover:text-blue-500 transition-colors"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                        <span
                            class="text-sm text-gray-600 group-hover:text-blue-600 font-medium transition-colors">Select
                            or Upload logo</span>
                    </button>
                    <input type="file" id="logoUpload" accept="image/*" class="hidden"
                        onchange="handleLogoUpload(event)">
                </div>

                <!-- Contact Information -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-900 mb-2">Contact Information</label>
                    <div class="flex items-center space-x-2 mb-2">
                        <input type="text" id="contactWebsite" placeholder="www.yourcompany.com"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <button onclick="deleteContactField('website')"
                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete website">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                </path>
                            </svg>
                        </button>
                    </div>
                    <div class="flex items-center space-x-2 mb-2">
                        <input type="text" id="contactSocial" placeholder="@yourcompany"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <button onclick="deleteContactField('social')"
                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete social">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                </path>
                            </svg>
                        </button>
                    </div>
                    <div class="flex items-center space-x-2">
                        <input type="text" id="contactPhone" placeholder="555-123-4567"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <button onclick="deleteContactField('phone')"
                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete phone">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                </path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Add Section -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-900 mb-3">Add Section</label>
                    <div class="space-y-2">
                        <button onclick="addSection('agreement')"
                            class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium text-left transition">
                            + Agreement
                        </button>
                        <button onclick="addSection('vehicle')"
                            class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium text-left transition">
                            + Leased Vehicle
                        </button>
                        <button onclick="addSection('payment')"
                            class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium text-left transition">
                            + Payment Terms
                        </button>
                        <button onclick="addSection('mileage')"
                            class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium text-left transition">
                            + Mileage Limit
                        </button>
                        <button onclick="addSection('accident')"
                            class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium text-left transition">
                            + Accident Policy
                        </button>
                        <button onclick="addSection('return')"
                            class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium text-left transition">
                            + Late Return
                        </button>
                        <button onclick="addSection('termination')"
                            class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium text-left transition">
                            + Termination
                        </button>

                        <button onclick="addSection('custom')"
                            class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium text-left transition">
                            + Custom Section
                        </button>
                    </div>
                </div>

                <!-- Save Button -->
                <button onclick="saveTemplate()"
                    class="w-full px-4 py-3 bg-black text-white rounded-lg font-medium hover:bg-gray-800 transition">
                    Save Template
                </button>
                </div>
                
                <!-- Collapsed State Icons -->
                <div x-show="controlsHidden" class="flex flex-col items-center gap-6 py-4 transition-all duration-300">
                    <div class="p-2 text-gray-400 hover:text-gray-600 cursor-pointer" @click="controlsHidden = false" title="Template Name">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <div class="p-2 text-gray-400 hover:text-gray-600 cursor-pointer" @click="controlsHidden = false" title="Brand Color">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path></svg>
                    </div>
                    <div class="p-2 text-gray-400 hover:text-gray-600 cursor-pointer" @click="controlsHidden = false" title="Logo">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <div class="p-2 text-gray-400 hover:text-gray-600 cursor-pointer" @click="controlsHidden = false" title="Contact Info">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                    </div>
                    <div class="p-2 text-gray-400 hover:text-gray-600 cursor-pointer" @click="controlsHidden = false" title="Add Section">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div class="mt-auto p-2 text-gray-400 hover:text-blue-600 cursor-pointer" @click="saveTemplate()" title="Save Template">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel - Contract Preview -->
        <div class="flex-1 overflow-y-auto bg-gray-100 p-4 lg:p-8">
            <div class="max-w-4xl mx-auto">
                <div id="contractPreview" class="contract-preview bg-white shadow-lg">
                    <!-- Header -->
                    <div id="contractHeader"
                        class="flex flex-col sm:flex-row items-center justify-between p-4 sm:p-8 gap-4"
                        style="background-color: #93C5FD;">
                        <h1 id="previewContractTitle" class="text-xl sm:text-3xl font-bold text-white text-center sm:text-left"
                            contenteditable="true"><?= htmlspecialchars($template['name'] ?? 'Vehicle Rental Contract')?></h1>
                        <div id="logoContainer" class="bg-white px-4 py-2 rounded">
                            <span class="text-gray-400 text-sm">Your Logo</span>
                        </div>
                    </div>

                    <!-- Contact Bar -->
                    <div id="contactBar"
                        class="flex flex-col sm:flex-row items-center justify-between px-4 sm:px-8 py-4 border-b border-gray-200 text-sm gap-2 sm:gap-0">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9">
                                </path>
                            </svg>
                            <span id="displayWebsite" class="text-gray-600">www.yourcompany.com</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207">
                                </path>
                            </svg>
                            <span id="displaySocial" class="text-gray-600">@yourcompany</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                </path>
                            </svg>
                            <span id="displayPhone" class="text-gray-600">555-123-4567</span>
                        </div>
                    </div>

                    <!-- Sections Container -->
                    <div id="sectionsContainer" class="p-8 space-y-6">
                        <!-- Initial Agreement Section -->
                        <div class="contract-section group relative" data-section-id="1">
                            <!-- Edit Tools -->
                            <div class="absolute -left-12 top-0 flex flex-col items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity no-print">
                                <button onclick="moveSection(1, 'up')" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-blue-600 bg-white rounded-full shadow-sm border border-gray-100 transition-all" title="Move Up">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                </button>
                                <button onclick="moveSection(1, 'down')" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-blue-600 bg-white rounded-full shadow-sm border border-gray-100 transition-all" title="Move Down">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                                <button onclick="removeSection(1)" class="w-8 h-8 flex items-center justify-center text-red-400 hover:text-red-600 bg-white rounded-full shadow-sm border border-gray-100 transition-all" title="Remove">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                            <div class="flex items-start mb-3">
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-6 rounded" style="background-color: #93C5FD;"></div>
                                    <h2 class="text-xl font-bold text-gray-900" contenteditable="true">Agreement</h2>
                                </div>
                            </div>
                            <div class="text-gray-700 leading-relaxed" contenteditable="true">
                                This Vehicle Rental Contract ("Contract") is entered into as of
                                <strong>{{current_datetime}}</strong>, by and between <strong>{{tenant_name}}</strong>
                                ("Owner"), with a principal place of business at <strong>Your Business Address</strong>,
                                and <strong>{{renter_full_name}}</strong> ("Renter"), residing at <strong>Renter
                                    Address</strong>. This Contract outlines the terms and conditions under which the
                                Owner agrees to lease the vehicle described below to the Renter.
                            </div>
                        </div>
                    </div>

                    <!-- Signature Section -->
                    <div class="px-8 pb-8">
                        <div class="border-t border-gray-200 pt-6">
                            <p class="text-sm text-gray-600 mb-6" contenteditable="true">IN WITNESS WHEREOF, the parties
                                hereto have executed this Vehicle Rental Contract as of the day and year first above
                                written.</p>
                            <div class="grid grid-cols-2 gap-8">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 mb-4" contenteditable="true">Witness</p>
                                    <div class="mb-2 h-16">{{witness_signature}}</div>
                                    <p class="text-sm text-gray-600" contenteditable="true">{{user_name}}</p>
                                    <p class="text-sm text-gray-600">Date: {{current_datetime}}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 mb-4" contenteditable="true">Renter</p>
                                    <div class="mb-2 h-16">{{signature}}</div>
                                    <p class="text-sm text-gray-600">{{renter_full_name}}</p>
                                    <p class="text-sm text-gray-600">Date: {{current_datetime}}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let sectionCounter = 1;
        let logoDataUrl = null;

        function initSortable() {
            // Drag-and-drop removed in favor of smoother up/down buttons
            console.log('Using button-based reordering for stability');
        }

        function moveSection(id, direction) {
            const section = document.querySelector(`[data-section-id="${id}"]`);
            if (!section) return;

            if (direction === 'up') {
                const prev = section.previousElementSibling;
                if (prev && prev.classList.contains('contract-section')) {
                    section.parentNode.insertBefore(section, prev);
                }
            } else {
                const next = section.nextElementSibling;
                if (next && next.classList.contains('contract-section')) {
                    section.parentNode.insertBefore(next, section);
                }
            }
        }

        function ensureSectionControls() {
            const brandColor = document.getElementById('brandColor').value;
            document.querySelectorAll('.contract-section').forEach(section => {
                // Remove any existing toolbars that might have been saved in the HTML
                const existingToolbar = section.querySelector('.no-print');
                if (existingToolbar) existingToolbar.remove();

                const id = section.dataset.sectionId || Math.floor(Math.random() * 1000000);
                section.dataset.sectionId = id;

                const controls = `
                    <div class="absolute -left-12 top-0 flex flex-col items-center gap-2 opacity-40 group-hover:opacity-100 transition-opacity no-print">
                        <button onclick="moveSection(${id}, 'up')" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-blue-600 bg-white rounded-full shadow-sm border border-gray-100 transition-all" title="Move Up">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                        </button>
                        <button onclick="moveSection(${id}, 'down')" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-blue-600 bg-white rounded-full shadow-sm border border-gray-100 transition-all" title="Move Down">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <button onclick="removeSection(${id})" class="w-8 h-8 flex items-center justify-center text-red-400 hover:text-red-600 bg-white rounded-full shadow-sm border border-gray-100 transition-all" title="Remove">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                `;
                section.insertAdjacentHTML('afterbegin', controls);
            });
        }

        window.addEventListener('DOMContentLoaded', initSortable);

        // Update brand color
        document.getElementById('brandColor').addEventListener('input', updateBrandColor);
        document.getElementById('brandColorText').addEventListener('input', function () {
            document.getElementById('brandColor').value = this.value;
            updateBrandColor();
        });

        function updateBrandColor() {
            const color = document.getElementById('brandColor').value;
            document.getElementById('brandColorText').value = color;
            document.getElementById('contractHeader').style.backgroundColor = color;

            // Update all section markers
            document.querySelectorAll('.contract-section .w-3').forEach(el => {
                el.style.backgroundColor = color;
            });
        }

        // Update contact information
        document.getElementById('contactWebsite').addEventListener('input', function () {
            document.getElementById('displayWebsite').textContent = this.value || 'www.yourcompany.com';
        });

        document.getElementById('contactSocial').addEventListener('input', function () {
            document.getElementById('displaySocial').textContent = this.value || '@yourcompany';
        });

        document.getElementById('contactPhone').addEventListener('input', function () {
            document.getElementById('displayPhone').textContent = this.value || '555-123-4567';
        });

        // Synchronize template/contract name bidirectionally between sidebar inputs and contract header
        const templateNameInput = document.getElementById('templateName');
        const templateNameMobileInput = document.getElementById('templateNameMobile');
        const previewContractTitle = document.getElementById('previewContractTitle');

        if (templateNameInput && previewContractTitle) {
            templateNameInput.addEventListener('input', function () {
                previewContractTitle.textContent = this.value || 'Vehicle Rental Contract';
                if (templateNameMobileInput) {
                    templateNameMobileInput.value = this.value;
                }
            });
        }

        if (templateNameMobileInput && previewContractTitle) {
            templateNameMobileInput.addEventListener('input', function () {
                previewContractTitle.textContent = this.value || 'Vehicle Rental Contract';
                if (templateNameInput) {
                    templateNameInput.value = this.value;
                }
            });
        }

        if (previewContractTitle) {
            previewContractTitle.addEventListener('input', function () {
                const text = this.textContent.trim();
                if (templateNameInput) {
                    templateNameInput.value = text;
                }
                if (templateNameMobileInput) {
                    templateNameMobileInput.value = text;
                }
            });
        }

        function handleLogoSelect(url) {
            logoDataUrl = url;
            document.getElementById('logoImage').src = logoDataUrl;
            document.getElementById('logoPreview').classList.remove('hidden');

            // Update contract preview
            document.getElementById('logoContainer').innerHTML = `<img src="${logoDataUrl}" alt="Logo" class="h-12 object-contain">`;
        }

        // Handle logo upload
        function handleLogoUpload(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    handleLogoSelect(e.target.result);
                };
                reader.readAsDataURL(file);
            }
        }

        // Section templates
        const sectionTemplates = {
            agreement: {
                title: 'Agreement',
                content: 'This Vehicle Rental Contract ("Contract") is entered into as of <strong>{{current_datetime}}</strong>, by and between <strong>{{tenant_name}}</strong> ("Owner"), with a principal place of business at <strong>Your Business Address</strong>, and <strong>{{renter_full_name}}</strong> ("Renter"), residing at <strong>Renter Address</strong>. This Contract outlines the terms and conditions under which the Owner agrees to lease the vehicle described below to the Renter.'
            },
            vehicle: {
                title: 'Leased Vehicle',
                content: `<table class="w-full border border-gray-300">
            <tr class="border-b border-gray-300"><td class="p-2 border-r border-gray-300 font-medium w-32">Make</td><td class="p-2">{{vehicle_name}}</td></tr>
            <tr class="border-b border-gray-300"><td class="p-2 border-r border-gray-300 font-medium">Model</td><td class="p-2">{{vehicle_name}}</td></tr>
            <tr class="border-b border-gray-300"><td class="p-2 border-r border-gray-300 font-medium">Year</td><td class="p-2">2024</td></tr>
            <tr class="border-b border-gray-300"><td class="p-2 border-r border-gray-300 font-medium">VIN</td><td class="p-2">{{vehicle_registration}}</td></tr>
            <tr><td class="p-2 border-r border-gray-300 font-medium">Color</td><td class="p-2">Black</td></tr>
        </table>`
            },
            payment: {
                title: 'Payment Term',
                content: 'The Renter agrees to pay the Owner a rental fee of <strong>{{booking_total_price}}</strong>. Payment is due in advance on the <strong>first day</strong> of each rental period. Payments can be made via <strong>credit card or PayPal</strong>. Failure to make timely payments will result in a late fee of <strong>$10 per day</strong>.'
            },
            mileage: {
                title: 'Mileage Limit',
                content: 'The Vehicle is subject to a mileage limit of <strong>{{included_distance}}</strong>. The Renter agrees to pay an additional fee of <strong>{{excess_distance_fee}}</strong> for any mileage exceeding the limit.'
            },
            accident: {
                title: 'Accident',
                content: 'If there is an accident involving the Vehicle, the Renter must promptly inform the Owner and authorities, obtain a police report, cooperate with investigations, and cover damages not protected by insurance.'
            },
            return: {
                title: 'Late Return',
                content: 'If the Renter doesn\'t return the Vehicle on time, a late fee of <strong>$20 per hour</strong> will be charged. The Owner may report the Vehicle as stolen if it\'s not returned within <strong>24 hours after the scheduled return time</strong>.'
            },
            termination: {
                title: 'Termination of Agreement',
                content: 'Either party can end this Contract by giving <strong>3 days\'</strong> written notice. The Renter must return the Vehicle to the Owner promptly upon termination. The Owner can terminate this Contract without notice for any breaches by the Renter.'
            },

            custom: {
                title: 'Custom Section',
                content: 'Add your custom content here. Click to edit this text and make it your own.'
            }
        };

        function addSection(type) {
            sectionCounter++;
            const template = sectionTemplates[type];

            if (!template) {
                console.error('Template not found for type:', type);
                showNotification('Error: Section template not found. Please refresh the page and try again.', 'error');
                return;
            }

            const brandColor = document.getElementById('brandColor').value;

            const sectionHtml = `
        <div class="contract-section group relative" data-section-id="${sectionCounter}">
            <!-- Edit Tools -->
            <div class="absolute -left-12 top-0 flex flex-col items-center gap-2 opacity-40 group-hover:opacity-100 transition-opacity no-print">
                <button onclick="moveSection(${sectionCounter}, 'up')" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-blue-600 bg-white rounded-full shadow-sm border border-gray-100 transition-all" title="Move Up">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                </button>
                <button onclick="moveSection(${sectionCounter}, 'down')" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-blue-600 bg-white rounded-full shadow-sm border border-gray-100 transition-all" title="Move Down">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <button onclick="removeSection(${sectionCounter})" class="w-8 h-8 flex items-center justify-center text-red-400 hover:text-red-600 bg-white rounded-full shadow-sm border border-gray-100 transition-all" title="Remove">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </div>
            <div class="flex items-start mb-3">
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-6 rounded" style="background-color: ${brandColor};"></div>
                    <h2 class="text-xl font-bold text-gray-900" contenteditable="true">${template.title}</h2>
                </div>
            </div>
            <div class="text-gray-700 leading-relaxed" contenteditable="true">
                ${template.content}
            </div>
        </div>
    `;

            document.getElementById('sectionsContainer').insertAdjacentHTML('beforeend', sectionHtml);
            console.log('Section added:', type);
        }

        function removeSection(id) {
            showConfirmation('Remove Section', 'Are you sure you want to remove this section?', function () {
                const section = document.querySelector(`[data-section-id="${id}"]`);
                if (section) {
                    section.remove();
                }
            }, 'Remove', 'bg-red-600 hover:bg-red-700');
        }

        function deleteContactField(type) {
            showConfirmation('Delete Contact Field', `Are you sure you want to delete this contact field?`, function () {
                if (type === 'website') {
                    document.getElementById('contactWebsite').value = '';
                    document.getElementById('displayWebsite').closest('.flex').style.display = 'none';
                } else if (type === 'social') {
                    document.getElementById('contactSocial').value = '';
                    document.getElementById('displaySocial').closest('.flex').style.display = 'none';
                } else if (type === 'phone') {
                    document.getElementById('contactPhone').value = '';
                    document.getElementById('displayPhone').closest('.flex').style.display = 'none';
                }
            }, 'Delete', 'bg-red-600 hover:bg-red-700');
        }

        // Initialize template data when editing or creating
        window.addEventListener('DOMContentLoaded', function () {
    <?php if ($edit_mode && $template): ?>
    // Load existing template data
    <?php
    $content = json_decode($template['content'], true);
    if ($content):
?>
    // Set brand color
    <?php if (isset($content['brand_color'])): ?>
                document.getElementById('brandColor').value = '<?= $content['brand_color']?>';
            document.getElementById('brandColorText').value = '<?= $content['brand_color']?>';
            updateBrandColor();
    <?php
        endif; ?>

    // Set contact info
    <?php if (isset($content['contact'])): ?>
    <?php if (isset($content['contact']['website'])): ?>
                document.getElementById('contactWebsite').value = '<?= htmlspecialchars($content['contact']['website'])?>';
            document.getElementById('displayWebsite').textContent = '<?= htmlspecialchars($content['contact']['website'])?>';
    <?php
            endif; ?>
    <?php if (isset($content['contact']['social'])): ?>
                document.getElementById('contactSocial').value = '<?= htmlspecialchars($content['contact']['social'])?>';
            document.getElementById('displaySocial').textContent = '<?= htmlspecialchars($content['contact']['social'])?>';
    <?php
            endif; ?>
    <?php if (isset($content['contact']['phone'])): ?>
                document.getElementById('contactPhone').value = '<?= htmlspecialchars($content['contact']['phone'])?>';
            document.getElementById('displayPhone').textContent = '<?= htmlspecialchars($content['contact']['phone'])?>';
    <?php
            endif; ?>
    <?php
        endif; ?>

    // Set logo
    <?php if (isset($content['logo']) && $content['logo']): ?>
                logoDataUrl = '<?= $content['logo']?>';
            document.getElementById('logoImage').src = logoDataUrl;
            document.getElementById('logoPreview').classList.remove('hidden');
            document.getElementById('logoContainer').innerHTML = '<img src="' + logoDataUrl + '" alt="Logo" class="h-12 object-contain">';
    <?php
        endif; ?>

    // Load HTML content (includes all sections)
    <?php if (isset($content['html'])): ?>
                document.getElementById('contractPreview').innerHTML = <?= json_encode($content['html']) ?>;
            // Ensure controls are present even if they weren't saved properly
            ensureSectionControls();
            
            // Update section counter based on loaded sections
            const sections = document.querySelectorAll('.contract-section');
            if (sections.length > 0) {
                const ids = Array.from(sections).map(s => parseInt(s.dataset.sectionId) || 0);
                sectionCounter = Math.max(...ids);
            }
    <?php
        endif; ?>
    <?php
    endif; ?>
    <?php
else: ?>
    // Add all sections by default for new templates
    const defaultSections = ['vehicle', 'payment', 'mileage', 'accident', 'return', 'termination'];
            defaultSections.forEach(sectionType => {
                addSection(sectionType);
            });
    <?php
endif; ?>
});

        function saveTemplate() {
            const templateName = document.getElementById('templateName').value;
            const brandColor = document.getElementById('brandColor').value;
            const contactWebsite = document.getElementById('contactWebsite').value;
            const contactSocial = document.getElementById('contactSocial').value;
            const contactPhone = document.getElementById('contactPhone').value;

            // Get contract HTML
            const contractHtml = document.getElementById('contractPreview').innerHTML;

            // Create template data
            const templateData = {
        <?php if ($edit_mode && $template): ?>
                template_id : <?= $template['id'] ?>,
        <?php
endif; ?>
                name: templateName,
                    brand_color: brandColor,
                        logo: logoDataUrl,
                            contact: {
                website: contactWebsite,
                    social: contactSocial,
                        phone: contactPhone
            },
            html: contractHtml
        };

        // Save to server
        fetch('/dashboard/save-contract-template.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(templateData)
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Template saved successfully!', 'success');
                    setTimeout(() => {
                        window.location.href = '/dashboard/e-signing.php';
                    }, 1500);
                } else {
                    showNotification('Failed to save template: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Error saving template', 'error');
            });
}
    </script>

    <?php include __DIR__ . '/../includes/confirmation-modal.php'; ?>
    <?php include __DIR__ . '/../includes/media-selector.php'; ?>

    <script>


        // Settings modal toggle for mobile
        function toggleSettingsModal() {
            const modal = document.getElementById('settingsModal');
            modal.classList.toggle('hidden');
            if (!modal.classList.contains('hidden')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }

        // Close settings modal when clicking outside
        document.addEventListener('click', function (e) {
            const modal = document.getElementById('settingsModal');
            const settingsBtn = document.querySelector('[onclick="toggleSettingsModal()"]');

            if (!modal.classList.contains('hidden') &&
                !modal.querySelector('.bg-white').contains(e.target) &&
                !settingsBtn.contains(e.target)) {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
        });
    </script>

    <!-- Settings Modal for Mobile -->
    <div id="settingsModal" class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-start justify-center min-h-screen pt-16 px-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[80vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900">Contract Designer</h2>
                        <button onclick="toggleSettingsModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Template Name -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-900 mb-2">Template Name</label>
                        <input type="text" id="templateNameMobile"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            placeholder="e.g., Standard Rental Agreement" value="Vehicle Rental Contract">
                    </div>

                    <!-- Brand Color -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-900 mb-2">Brand Color</label>
                        <div class="flex items-center space-x-3">
                            <input type="color" id="brandColorMobile" value="#93C5FD"
                                class="w-12 h-12 rounded border border-gray-300 cursor-pointer">
                            <input type="text" id="brandColorTextMobile" value="#93C5FD"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        </div>
                    </div>

                    <!-- Add Section -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-900 mb-3">Add Section</label>
                        <div class="space-y-2">
                            <button onclick="addSection('agreement'); toggleSettingsModal();"
                                class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium text-left transition">
                                + Agreement
                            </button>
                            <button onclick="addSection('vehicle'); toggleSettingsModal();"
                                class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium text-left transition">
                                + Leased Vehicle
                            </button>
                            <button onclick="addSection('payment'); toggleSettingsModal();"
                                class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium text-left transition">
                                + Payment Terms
                            </button>
                            <button onclick="addSection('termination'); toggleSettingsModal();"
                                class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium text-left transition">
                                + Termination
                            </button>

                            <button onclick="addSection('custom'); toggleSettingsModal();"
                                class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium text-left transition">
                                + Custom Section
                            </button>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <button onclick="saveTemplate(); toggleSettingsModal();"
                        class="w-full px-4 py-3 bg-black text-white rounded-lg font-medium hover:bg-gray-800 transition">
                        Save Template
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/confirmation-modal.php'; ?>
    <?php include __DIR__ . '/../includes/onboarding-widget.php'; ?>
</body>

</html>