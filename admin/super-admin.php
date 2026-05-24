<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in and is super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    redirect('/auth/login.php');
}

$pdo = getDB();

// Get statistics
$stats = [];

// Total tenants
$stmt = $pdo->query("SELECT COUNT(*) FROM tenants");
$stats['total_tenants'] = $stmt->fetchColumn();

// Active tenants
$stmt = $pdo->query("SELECT COUNT(*) FROM tenants WHERE status = 'active'");
$stats['active_tenants'] = $stmt->fetchColumn();

// Trial tenants
$stmt = $pdo->query("SELECT COUNT(*) FROM tenants WHERE status = 'trial'");
$stats['trial_tenants'] = $stmt->fetchColumn();

// Total users
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'super_admin'");
$stats['total_users'] = $stmt->fetchColumn();

// Get recent tenants
$stmt = $pdo->query("SELECT * FROM tenants ORDER BY created_at DESC LIMIT 10");
$recent_tenants = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Companies - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/app/custom.css">
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
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Bar -->
            <header class="bg-white border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Companies</h1>
                        <p class="text-sm text-gray-600 mt-1">Manage all tenant companies and accounts</p>
                    </div>
                    <button class="px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800 text-sm font-medium">
                        Add New Company
                    </button>
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-y-auto p-6">
                <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="text-sm font-medium text-gray-600">Total Tenants</div>
                <div class="text-3xl font-bold text-gray-900 mt-2"><?= $stats['total_tenants'] ?></div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="text-sm font-medium text-gray-600">Active Tenants</div>
                <div class="text-3xl font-bold text-blue-600 mt-2"><?= $stats['active_tenants'] ?></div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="text-sm font-medium text-gray-600">Trial Tenants</div>
                <div class="text-3xl font-bold text-gray-600 mt-2"><?= $stats['trial_tenants'] ?></div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="text-sm font-medium text-gray-600">Total Users</div>
                <div class="text-3xl font-bold text-blue-600 mt-2"><?= $stats['total_users'] ?></div>
            </div>
        </div>

                <!-- Recent Tenants -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Recent Tenants</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subdomain</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($recent_tenants)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                No tenants yet. Waiting for signups!
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($recent_tenants as $tenant): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900"><?= htmlspecialchars($tenant['name']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="http://<?= $tenant['subdomain'] ?>.<?= ROOT_DOMAIN ?>" 
                                   target="_blank"
                                   class="text-blue-600 hover:text-blue-800">
                                    <?= $tenant['subdomain'] ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800">
                                    <?= ucfirst($tenant['plan']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $status_colors = [
                                    'active' => 'bg-blue-100 text-blue-800',
                                    'trial' => 'bg-gray-100 text-gray-800',
                                    'suspended' => 'bg-gray-200 text-gray-900',
                                    'cancelled' => 'bg-gray-100 text-gray-600'
                                ];
                                ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded <?= $status_colors[$tenant['status']] ?>">
                                    <?= ucfirst($tenant['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= formatDate($tenant['created_at']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="#" class="text-blue-600 hover:text-blue-800 mr-3">View</a>
                                <a href="#" class="text-gray-600 hover:text-gray-800">Edit</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
