<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in and is super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    redirect('/auth/login.php');
}

$pdo = getDB();

// Get subscription statistics
$stats = [];

// Total subscriptions
$stmt = $pdo->query("SELECT COUNT(*) FROM tenants");
$stats['total'] = $stmt->fetchColumn();

// Active subscriptions
$stmt = $pdo->query("SELECT COUNT(*) FROM tenants WHERE status = 'active'");
$stats['active'] = $stmt->fetchColumn();

// Trial subscriptions
$stmt = $pdo->query("SELECT COUNT(*) FROM tenants WHERE status = 'trial'");
$stats['trial'] = $stmt->fetchColumn();

// Cancelled subscriptions
$stmt = $pdo->query("SELECT COUNT(*) FROM tenants WHERE status = 'cancelled'");
$stats['cancelled'] = $stmt->fetchColumn();

// Get all tenants with subscription info
$stmt = $pdo->query("SELECT * FROM tenants ORDER BY created_at DESC");
$subscriptions = $stmt->fetchAll();

// Plan statistics
$stmt = $pdo->query("SELECT plan, COUNT(*) as count FROM tenants GROUP BY plan");
$plan_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscriptions - <?= SITE_NAME ?></title>
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
                        <h1 class="text-2xl font-bold text-gray-900">Subscriptions</h1>
                        <p class="text-sm text-gray-600 mt-1">Manage and monitor all subscription plans</p>
                    </div>
                    <button class="px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800 text-sm font-medium">
                        Export Data
                    </button>
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-y-auto p-6">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm font-medium text-gray-600">Total Subscriptions</div>
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <div class="text-3xl font-bold text-gray-900"><?= $stats['total'] ?></div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm font-medium text-gray-600">Active</div>
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="text-3xl font-bold text-blue-600"><?= $stats['active'] ?></div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm font-medium text-gray-600">Trial</div>
                            <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="text-3xl font-bold text-gray-600"><?= $stats['trial'] ?></div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm font-medium text-gray-600">Cancelled</div>
                            <svg class="w-8 h-8 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="text-3xl font-bold text-gray-900"><?= $stats['cancelled'] ?></div>
                    </div>
                </div>

                <!-- Plan Distribution -->
                <div class="grid md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Plan Distribution</h3>
                        <div class="space-y-4">
                            <?php
                            $plan_colors = [
                                'trial' => 'bg-gray-500',
                                'starter' => 'bg-blue-500',
                                'growth' => 'bg-blue-600',
                                'pro' => 'bg-gray-700',
                                'enterprise' => 'bg-gray-900'
                            ];
                            
                            foreach ($plan_stats as $plan => $count):
                                $percentage = $stats['total'] > 0 ? ($count / $stats['total']) * 100 : 0;
                            ?>
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="font-medium text-gray-700"><?= ucfirst($plan) ?></span>
                                    <span class="text-gray-600"><?= $count ?> (<?= number_format($percentage, 1) ?>%)</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="<?= $plan_colors[$plan] ?? 'bg-gray-500' ?> h-2 rounded-full" style="width: <?= $percentage ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            <?php
                            $trial_count = $pdo->query("SELECT COUNT(*) FROM tenants WHERE status = 'trial'")->fetchColumn();
                            $expired_trials = $pdo->query("SELECT COUNT(*) FROM tenants WHERE status = 'trial' AND trial_ends_at < NOW()")->fetchColumn();
                            ?>
                            <a href="?filter=trial" class="w-full px-4 py-3 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 text-left flex items-center justify-between">
                                <div>
                                    <span class="font-medium block">Upgrade Trial Users</span>
                                    <span class="text-xs text-blue-600"><?= $trial_count ?> trial users</span>
                                </div>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                            <a href="?action=send_reminders" class="w-full px-4 py-3 bg-gray-50 text-gray-700 rounded-lg hover:bg-gray-100 text-left flex items-center justify-between">
                                <div>
                                    <span class="font-medium block">Send Renewal Reminders</span>
                                    <span class="text-xs text-gray-600">Email active customers</span>
                                </div>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                            <a href="?filter=expired" class="w-full px-4 py-3 bg-gray-50 text-gray-700 rounded-lg hover:bg-gray-100 text-left flex items-center justify-between">
                                <div>
                                    <span class="font-medium block">View Expired Trials</span>
                                    <span class="text-xs text-gray-600"><?= $expired_trials ?> expired trials</span>
                                </div>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Subscriptions Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-900">All Subscriptions</h2>
                        <div class="flex items-center space-x-2">
                            <input type="text" placeholder="Search..." class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <select class="custom-select">
                                <option value="">All Plans</option>
                                <option value="trial">Trial</option>
                                <option value="starter">Starter</option>
                                <option value="growth">Growth</option>
                                <option value="pro">Pro</option>
                                <option value="enterprise">Enterprise</option>
                            </select>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Started</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trial Ends</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($subscriptions)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                        No subscriptions yet
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($subscriptions as $sub): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-900"><?= htmlspecialchars($sub['name']) ?></div>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($sub['subdomain']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800">
                                            <?= ucfirst($sub['plan']) ?>
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
                                        <span class="px-2 py-1 text-xs font-semibold rounded <?= $status_colors[$sub['status']] ?>">
                                            <?= ucfirst($sub['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('M d, Y', strtotime($sub['created_at'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= $sub['trial_ends_at'] ? date('M d, Y', strtotime($sub['trial_ends_at'])) : '-' ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <button class="text-blue-600 hover:text-blue-800 mr-3">Manage</button>
                                        <button class="text-gray-600 hover:text-gray-800">View</button>
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
