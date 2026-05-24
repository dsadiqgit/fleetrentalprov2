<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in and is super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    redirect('/auth/login.php');
}

$pdo = getDB();

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="revenue_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Revenue Report - Generated ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    // Plan pricing
    $plan_prices = [
        'trial' => 0,
        'starter' => 29,
        'growth' => 79,
        'pro' => 149,
        'enterprise' => 299
    ];
    
    // Get active tenants
    $stmt = $pdo->query("SELECT plan, COUNT(*) as count FROM tenants WHERE status = 'active' GROUP BY plan");
    $plan_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $mrr = 0;
    foreach ($plan_counts as $plan => $count) {
        $mrr += ($plan_prices[$plan] ?? 0) * $count;
    }
    
    fputcsv($output, ['Metric', 'Value']);
    fputcsv($output, ['Monthly Recurring Revenue (MRR)', '$' . number_format($mrr, 2)]);
    fputcsv($output, ['Annual Recurring Revenue (ARR)', '$' . number_format($mrr * 12, 2)]);
    fputcsv($output, ['Active Customers', array_sum($plan_counts)]);
    fputcsv($output, ['Average Revenue Per User (ARPU)', '$' . number_format(array_sum($plan_counts) > 0 ? $mrr / array_sum($plan_counts) : 0, 2)]);
    fputcsv($output, []);
    
    fputcsv($output, ['Revenue by Plan']);
    fputcsv($output, ['Plan', 'Customers', 'Price/Month', 'Total Revenue']);
    foreach ($plan_counts as $plan => $count) {
        if ($plan !== 'trial') {
            $revenue = ($plan_prices[$plan] ?? 0) * $count;
            fputcsv($output, [ucfirst($plan), $count, '$' . $plan_prices[$plan], '$' . number_format($revenue, 2)]);
        }
    }
    
    fclose($output);
    exit;
}

// Calculate revenue statistics
$stats = [];

// Plan pricing
$plan_prices = [
    'trial' => 0,
    'starter' => 29,
    'growth' => 79,
    'pro' => 149,
    'enterprise' => 299
];

// Get plan counts
$stmt = $pdo->query("SELECT plan, COUNT(*) as count FROM tenants WHERE status = 'active' GROUP BY plan");
$plan_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Calculate MRR (Monthly Recurring Revenue)
$mrr = 0;
foreach ($plan_counts as $plan => $count) {
    $mrr += ($plan_prices[$plan] ?? 0) * $count;
}

$stats['mrr'] = $mrr;
$stats['arr'] = $mrr * 12; // Annual Recurring Revenue

// Total active customers
$stmt = $pdo->query("SELECT COUNT(*) FROM tenants WHERE status = 'active'");
$stats['active_customers'] = $stmt->fetchColumn();

// Average revenue per user
$stats['arpu'] = $stats['active_customers'] > 0 ? $mrr / $stats['active_customers'] : 0;

// Calculate previous month's MRR for growth percentage
$stmt = $pdo->query("SELECT COUNT(*) FROM tenants WHERE status = 'active' AND created_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)");
$prev_active = $stmt->fetchColumn();
$prev_mrr = $prev_active * ($stats['active_customers'] > 0 ? $stats['arpu'] : 0);
$mrr_growth = $prev_mrr > 0 ? (($mrr - $prev_mrr) / $prev_mrr) * 100 : 0;
$stats['mrr_growth'] = $mrr_growth;

// Monthly data for chart (last 6 months) - calculate based on tenant creation dates
$monthly_revenue = [];
for ($i = 5; $i >= 0; $i--) {
    $month_start = date('Y-m-01', strtotime("-$i months"));
    $month_end = date('Y-m-t', strtotime("-$i months"));
    $month_label = date('M', strtotime("-$i months"));
    
    // Count active tenants in that month
    $stmt = $pdo->prepare("SELECT plan, COUNT(*) as count FROM tenants WHERE status = 'active' AND created_at <= ? GROUP BY plan");
    $stmt->execute([$month_end]);
    $month_plans = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $month_revenue = 0;
    foreach ($month_plans as $plan => $count) {
        $month_revenue += ($plan_prices[$plan] ?? 0) * $count;
    }
    
    $monthly_revenue[] = [
        'month' => $month_label,
        'revenue' => $month_revenue
    ];
}

// Calculate metrics
$total_tenants = $pdo->query("SELECT COUNT(*) FROM tenants")->fetchColumn();
$trial_tenants = $pdo->query("SELECT COUNT(*) FROM tenants WHERE status = 'trial'")->fetchColumn();
$stats['conversion_rate'] = $total_tenants > 0 ? (($stats['active_customers'] / $total_tenants) * 100) : 0;
$stats['churn_rate'] = 2.1; // Would calculate from cancellations
$stats['ltv'] = $stats['arpu'] > 0 ? ($stats['arpu'] * 12) / ($stats['churn_rate'] / 100) : 0;
$stats['cac'] = 456; // Would integrate with marketing spend data
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/app/custom.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                        <h1 class="text-2xl font-bold text-gray-900">Revenue</h1>
                        <p class="text-sm text-gray-600 mt-1">Financial overview and analytics</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <select class="custom-select">
                            <option>Last 30 days</option>
                            <option>Last 90 days</option>
                            <option>Last 12 months</option>
                            <option>All time</option>
                        </select>
                        <a href="?export=csv" class="px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800 text-sm font-medium inline-block">
                            Export Report
                        </a>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-y-auto p-6">
                <!-- Revenue Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm font-medium text-gray-600">MRR</div>
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="text-3xl font-bold text-gray-900">$<?= number_format($stats['mrr']) ?></div>
                        <p class="text-sm <?= $stats['mrr_growth'] >= 0 ? 'text-blue-600' : 'text-gray-600' ?> mt-2"><?= $stats['mrr_growth'] >= 0 ? '↑' : '↓' ?> <?= number_format(abs($stats['mrr_growth']), 1) ?>% from last month</p>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm font-medium text-gray-600">ARR</div>
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <div class="text-3xl font-bold text-gray-900">$<?= number_format($stats['arr']) ?></div>
                        <p class="text-sm text-blue-600 mt-2">Annual projection</p>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm font-medium text-gray-600">Active Customers</div>
                            <svg class="w-8 h-8 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div class="text-3xl font-bold text-gray-900"><?= $stats['active_customers'] ?></div>
                        <p class="text-sm text-gray-600 mt-2">Paying customers</p>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm font-medium text-gray-600">ARPU</div>
                            <svg class="w-8 h-8 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="text-3xl font-bold text-gray-900">$<?= number_format($stats['arpu'], 2) ?></div>
                        <p class="text-sm text-gray-600 mt-2">Avg per user/month</p>
                    </div>
                </div>

                <!-- Revenue Chart -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Revenue Trend</h3>
                    <canvas id="revenueChart" height="80"></canvas>
                </div>

                <!-- Revenue by Plan -->
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Revenue by Plan</h3>
                        <div class="space-y-4">
                            <?php
                            $plan_colors = [
                                'starter' => 'bg-blue-500',
                                'growth' => 'bg-blue-600',
                                'pro' => 'bg-gray-700',
                                'enterprise' => 'bg-gray-900'
                            ];
                            
                            $total_revenue = 0;
                            foreach ($plan_counts as $plan => $count) {
                                if ($plan !== 'trial') {
                                    $total_revenue += ($plan_prices[$plan] ?? 0) * $count;
                                }
                            }
                            
                            foreach ($plan_counts as $plan => $count):
                                if ($plan === 'trial') continue;
                                $revenue = ($plan_prices[$plan] ?? 0) * $count;
                                $percentage = $total_revenue > 0 ? ($revenue / $total_revenue) * 100 : 0;
                            ?>
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="font-medium text-gray-700"><?= ucfirst($plan) ?> ($<?= $plan_prices[$plan] ?>/mo)</span>
                                    <span class="text-gray-600">$<?= number_format($revenue) ?> (<?= number_format($percentage, 1) ?>%)</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="<?= $plan_colors[$plan] ?? 'bg-gray-500' ?> h-2 rounded-full" style="width: <?= $percentage ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Key Metrics</h3>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center py-3 border-b border-gray-100">
                                <span class="text-gray-600">Conversion Rate</span>
                                <span class="text-lg font-semibold text-gray-900"><?= number_format($stats['conversion_rate'], 1) ?>%</span>
                            </div>
                            <div class="flex justify-between items-center py-3 border-b border-gray-100">
                                <span class="text-gray-600">Churn Rate</span>
                                <span class="text-lg font-semibold text-gray-900"><?= number_format($stats['churn_rate'], 1) ?>%</span>
                            </div>
                            <div class="flex justify-between items-center py-3 border-b border-gray-100">
                                <span class="text-gray-600">Customer LTV</span>
                                <span class="text-lg font-semibold text-gray-900">$<?= number_format($stats['ltv'], 0) ?></span>
                            </div>
                            <div class="flex justify-between items-center py-3">
                                <span class="text-gray-600">CAC</span>
                                <span class="text-lg font-semibold text-gray-900">$<?= number_format($stats['cac'], 0) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Revenue Chart
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($monthly_revenue, 'month')) ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?= json_encode(array_column($monthly_revenue, 'revenue')) ?>,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
