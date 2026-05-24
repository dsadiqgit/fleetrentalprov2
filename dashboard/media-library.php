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

// Create media_library table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS media_library (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_type VARCHAR(50),
        file_size INT,
        uploaded_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
        INDEX idx_tenant_id (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
catch (PDOException $e) {
// Table might already exist or other error
}

// Handle delete media
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $media_id = intval($_GET['delete']);

    // Get file path before deleting from database
    $stmt = $pdo->prepare("SELECT file_path FROM media_library WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$media_id, $_SESSION['tenant_id']]);
    $media = $stmt->fetch();

    if ($media) {
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM media_library WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$media_id, $_SESSION['tenant_id']]);

        // Delete physical file
        $file_path = __DIR__ . '/..' . $media['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    redirect('/dashboard/media-library.php');
}

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    $upload_dir = __DIR__ . '/../uploads/media/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $uploaded_files = $_FILES['images'];
    $file_count = count($uploaded_files['name']);

    for ($i = 0; $i < $file_count; $i++) {
        if ($uploaded_files['error'][$i] === UPLOAD_ERR_OK) {
            $file_name = basename($uploaded_files['name'][$i]);
            $file_tmp = $uploaded_files['tmp_name'][$i];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $file_size = $uploaded_files['size'][$i];

            // Validate file type
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($file_ext, $allowed_types)) {
                $new_file_name = uniqid() . '_' . $file_name;
                $destination = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp, $destination)) {
                    // Save to database
                    try {
                        $stmt = $pdo->prepare("INSERT INTO media_library (tenant_id, file_name, file_path, file_type, file_size, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                        $stmt->execute([
                            $_SESSION['tenant_id'],
                            $file_name,
                            '/uploads/media/' . $new_file_name,
                            $file_ext,
                            $file_size,
                            $_SESSION['user_id']
                        ]);
                    }
                    catch (PDOException $e) {
                    // Database insert failed, but file is uploaded
                    }
                }
            }
        }
    }
    redirect('/dashboard/media-library.php');
}

// Get all media for this tenant
try {
    $stmt = $pdo->prepare("SELECT * FROM media_library WHERE tenant_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['tenant_id']]);
    $media_items = $stmt->fetchAll();
}
catch (PDOException $e) {
    // Table might not exist yet
    $media_items = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Library -
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

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden w-full lg:w-auto pt-14 lg:pt-0">
        <!-- Desktop Top Bar -->
        <header class="hidden lg:block bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <nav class="text-sm text-gray-500 mb-1">
                        <a href="/dashboard/" class="hover:text-gray-700">Dashboard</a>
                        <span class="mx-2">/</span>
                        <span class="text-gray-900">Media Library</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-gray-900">Media Library</h1>
                    <p class="text-sm text-gray-600 mt-1">Upload and manage images for your vehicles</p>
                </div>
                <button onclick="document.getElementById('uploadInput').click()"
                    class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                        </path>
                    </svg>
                    Upload images
                </button>
            </div>
        </header>

        <!-- Content Area -->
        <main class="flex-1 overflow-y-auto p-6">
            <?php if (empty($media_items)): ?>
            <!-- Empty State -->
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-12 text-center">
                <div class="flex justify-center mb-4">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                            </path>
                        </svg>
                    </div>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No images yet</h3>
                <p class="text-gray-600 mb-6">Upload images to use in your vehicle listings</p>
                <button onclick="document.getElementById('uploadInput').click()"
                    class="px-6 py-3 bg-gray-900 text-white rounded-lg hover:bg-gray-800 inline-flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                        </path>
                    </svg>
                    Upload your first images
                </button>
            </div>
            <?php
else: ?>
            <!-- Media Grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 xxl:grid-cols-8 gap-4">
                <?php foreach ($media_items as $item): ?>
                <div
                    class="group relative bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-lg transition">
                    <div class="aspect-square bg-gray-100">
                        <img src="<?= htmlspecialchars($item['file_path'])?>"
                            alt="<?= htmlspecialchars($item['file_name'])?>" class="w-full h-full object-cover">
                    </div>
                    <div class="p-3">
                        <p class="text-sm font-medium text-gray-900 truncate">
                            <?= htmlspecialchars($item['file_name'])?>
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            <?= date('M d, Y', strtotime($item['created_at']))?>
                        </p>
                    </div>
                    <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition">
                        <button
                            onclick="showConfirmation('Delete Image', 'Are you sure you want to delete this image? This action cannot be undone.', function() { window.location.href='/dashboard/media-library.php?delete=<?= $item['id']?>'; }, 'Delete', 'bg-red-600 hover:bg-red-700')"
                            class="p-2 bg-white rounded-lg shadow-lg hover:bg-red-50">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                </path>
                            </svg>
                        </button>
                    </div>
                </div>
                <?php
    endforeach; ?>
            </div>
            <?php
endif; ?>
        </main>
    </div>
    </div>

    <!-- Hidden Upload Form -->
    <form id="uploadForm" method="POST" enctype="multipart/form-data" style="display: none;">
        <input type="file" id="uploadInput" name="images[]" multiple accept="image/*" onchange="handleUpload()">
    </form>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-sm mx-4">
            <div class="flex flex-col items-center">
                <svg class="animate-spin h-12 w-12 text-blue-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Uploading Images...</h3>
                <p class="text-sm text-gray-600 text-center">Please wait while we upload your images</p>
            </div>
        </div>
    </div>

    <script>
        function handleUpload() {
            const form = document.getElementById('uploadForm');
            const input = document.getElementById('uploadInput');
            const overlay = document.getElementById('loadingOverlay');

            if (input.files.length > 0) {
                // Show loading overlay
                overlay.classList.remove('hidden');
                overlay.classList.add('flex');

                // Submit form
                form.submit();
            }
        }
    </script>

    <?php include __DIR__ . '/../includes/confirmation-modal.php'; ?>


    <?php include __DIR__ . '/../includes/onboarding-widget.php'; ?>
</body>

</html>