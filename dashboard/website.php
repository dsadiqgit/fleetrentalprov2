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

// Get tenant information
$stmt = $pdo->prepare("SELECT * FROM tenants WHERE id = ?");
$stmt->execute([$_SESSION['tenant_id']]);
$tenant = $stmt->fetch();

if (!$tenant) {
    die('Error: Tenant not found.');
}

// Calculate trial days remaining
$trial_days_remaining = 0;
if ($tenant['plan'] === 'trial') {
    if ($tenant['trial_ends_at']) {
        $trial_end = new DateTime($tenant['trial_ends_at']);
        $now = new DateTime();
        $interval = $now->diff($trial_end);
        $trial_days_remaining = $interval->days;
        if ($trial_end < $now) {
            $trial_days_remaining = -$trial_days_remaining;
        }
    }
    else {
        $created = new DateTime($tenant['created_at']);
        $now = new DateTime();
        $interval = $created->diff($now);
        $trial_days_remaining = 14 - $interval->days;
    }
}

// Get current template setting
$current_template = $tenant['website_template'] ?? 'template1';

// Available templates
$templates = [
    [
        'id' => 'template1',
        'name' => 'Template 1',
        'description' => 'Modern and editable template with hero banner, about section, and contact',
        'image' => 'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        'active' => true
    ]
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Management -
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
        <div class="flex items-center gap-2">
            <?php
$tenant_url = (ROOT_DOMAIN === 'localhost')
    ? "http://{$tenant['subdomain']}." . ROOT_DOMAIN . ":" . PORT
    : "http://{$tenant['subdomain']}." . ROOT_DOMAIN;
?>
            <a href="<?= $tenant_url?>" target="_blank"
                class="px-2 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-xs font-medium hover:bg-gray-200 flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                    </path>
                </svg>
                Preview
            </a>
            <a href="/dashboard/website-builder.php"
                class="px-2 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-medium hover:bg-blue-700 flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                    </path>
                </svg>
                Edit
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

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden w-full lg:w-auto pt-14 lg:pt-0">
        <!-- Desktop Top Bar -->
        <header class="hidden lg:block bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <nav class="text-sm text-gray-500 mb-1">
                        <a href="/dashboard/" class="hover:text-gray-700">Dashboard</a>
                        <span class="mx-2">/</span>
                        <span class="text-gray-900">Website</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-gray-900">Website Management</h1>
                    <p class="text-sm text-gray-600 mt-1">Select and manage your website template</p>
                </div>
                <div class="flex items-center space-x-3">
                    <?php
$tenant_url = (ROOT_DOMAIN === 'localhost')
    ? "http://{$tenant['subdomain']}." . ROOT_DOMAIN . ":" . PORT
    : "http://{$tenant['subdomain']}." . ROOT_DOMAIN;
?>
                    <a href="<?= $tenant_url?>" target="_blank"
                        class="px-4 py-2 bg-gray-100 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-200 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                            </path>
                        </svg>
                        Preview Website
                    </a>
                    <a href="/dashboard/website-builder.php"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                        Edit Website
                    </a>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <main class="flex-1 overflow-y-auto p-6">
            <!-- Current Template Info -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-blue-900">
                    <span class="font-semibold">Current Template:</span>
                    <?= ucfirst($current_template)?>
                </p>
            </div>

            <!-- Templates Grid -->
            <div class="grid md:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-8 gap-6">
                <?php foreach ($templates as $template): ?>
                <div
                    class="bg-white rounded-lg border-2 <?= $template['id'] === $current_template ? 'border-blue-600' : 'border-gray-200'?> overflow-hidden hover:shadow-lg transition relative">
                    <?php if ($template['active']): ?>
                    <div class="absolute top-3 left-3 z-10">
                        <span class="px-3 py-1 bg-green-500 text-white text-xs font-semibold rounded-full">Active</span>
                    </div>
                    <?php
    endif; ?>

                    <?php if ($template['id'] === $current_template): ?>
                    <div class="absolute top-3 right-3 z-10">
                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </div>
                    <?php
    endif; ?>

                    <div class="aspect-video bg-gray-100">
                        <img src="<?= htmlspecialchars($template['image'])?>"
                            alt="<?= htmlspecialchars($template['name'])?>" class="w-full h-full object-cover">
                    </div>

                    <div class="p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            <h3 class="text-lg font-bold text-gray-900">
                                <?= htmlspecialchars($template['name'])?>
                            </h3>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">
                            <?= htmlspecialchars($template['description'])?>
                        </p>
                    </div>
                </div>
                <?php
endforeach; ?>
            </div>

            <!-- Apply Template Button -->
            <div class="mt-6 flex justify-end">
                <button class="px-6 py-2 bg-gray-100 text-gray-400 rounded-lg cursor-not-allowed" disabled>
                    Apply Template
                </button>
            </div>
        </main>
    </div>


    <?php include __DIR__ . '/../includes/onboarding-widget.php'; ?>
</body>

</html>