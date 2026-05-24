<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in and is super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    redirect('/auth/login.php');
}

$pdo = getDB();

// Get templates (for now, hardcoded - can be moved to database later)
$templates = [
    [
        'id' => 1,
        'name' => 'Template 1',
        'description' => 'Modern and editable template with hero banner, about section, and contact',
        'image' => 'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        'active' => true,
        'companies_using' => 0
    ]
];

// Add website_template column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE tenants ADD COLUMN IF NOT EXISTS website_template VARCHAR(50) DEFAULT 'template1'");
}
catch (PDOException $e) {
// Column might already exist
}

// Count companies using each template
try {
    $stmt = $pdo->query("SELECT website_template, COUNT(*) as count FROM tenants WHERE website_template IS NOT NULL GROUP BY website_template");
    $usage = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}
catch (PDOException $e) {
    // If column still doesn't exist, set empty usage
    $usage = [];
}

foreach ($templates as &$template) {
    $template_key = 'template' . $template['id'];
    $template['companies_using'] = $usage[$template_key] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Templates -
        <?= SITE_NAME?>
    </title>
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
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Templates</h1>
                    <p class="text-sm text-gray-600 mt-1">Manage website and contract templates for rental companies</p>
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-y-auto p-6">
                <!-- Tabs -->
                <div class="border-b border-gray-200 mb-6">
                    <nav class="-mb-px flex space-x-8">
                        <a href="#" class="border-b-2 border-blue-600 py-4 px-1 text-sm font-medium text-blue-600">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z">
                                </path>
                            </svg>
                            Website Templates
                        </a>
                    </nav>
                </div>

                <!-- Templates Grid -->
                <div class="grid md:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-8 gap-6">
                    <?php foreach ($templates as $template): ?>
                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-lg transition">
                        <!-- Template Image -->
                        <div class="relative aspect-video bg-gray-100">
                            <img src="<?= htmlspecialchars($template['image'])?>"
                                alt="<?= htmlspecialchars($template['name'])?>" class="w-full h-full object-cover">
                            <?php if ($template['active']): ?>
                            <div class="absolute top-3 right-3">
                                <span
                                    class="px-3 py-1 bg-blue-600 text-white text-xs font-semibold rounded-full">Active</span>
                            </div>
                            <?php
    endif; ?>
                        </div>

                        <!-- Template Info -->
                        <div class="p-6">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z">
                                    </path>
                                </svg>
                                <h3 class="text-lg font-bold text-gray-900">
                                    <?= htmlspecialchars($template['name'])?>
                                </h3>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">
                                <?= htmlspecialchars($template['description'])?>
                            </p>

                            <p class="text-sm text-gray-500 mb-4">
                                <span class="font-semibold">
                                    <?= $template['companies_using']?>
                                </span> companies using
                            </p>

                            <!-- Actions -->
                            <div class="flex gap-2">
                                <a href="/admin/template-edit.php?id=<?= $template['id']?>"
                                    class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium text-center inline-flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                        </path>
                                    </svg>
                                    Edit
                                </a>
                                <a href="/templates/template-<?= $template['id']?>.php" target="_blank"
                                    class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium text-center inline-flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                        </path>
                                    </svg>
                                    Preview
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php
endforeach; ?>
                </div>
            </main>
        </div>
    </div>
</body>

</html>