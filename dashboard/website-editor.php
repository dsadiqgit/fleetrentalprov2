<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/auth/login.php');
}

$pdo = getDB();

// Get tenant info
$stmt = $pdo->prepare("SELECT * FROM tenants WHERE id = ?");
$stmt->execute([$_SESSION['tenant_id']]);
$tenant = $stmt->fetch();

// Create website_content table if it doesn't exist
$pdo->exec("CREATE TABLE IF NOT EXISTS website_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    hero_title VARCHAR(255) DEFAULT 'Premium Car Rentals Made Easy',
    hero_subtitle TEXT,
    hero_image VARCHAR(500) DEFAULT 'https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
    company_name VARCHAR(255),
    about_title VARCHAR(255) DEFAULT 'About Your Company Name',
    about_text TEXT,
    about_image VARCHAR(500) DEFAULT 'https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    contact_phone VARCHAR(50) DEFAULT '+1 (555) 123-4567',
    contact_email VARCHAR(100) DEFAULT 'info@yourcompany.com',
    contact_address TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_tenant (tenant_id),
    INDEX idx_tenant_id (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Get or create website content
$stmt = $pdo->prepare("SELECT * FROM website_content WHERE tenant_id = ?");
$stmt->execute([$_SESSION['tenant_id']]);
$content = $stmt->fetch();

if (!$content) {
    // Create default content
    $stmt = $pdo->prepare("INSERT INTO website_content (tenant_id, company_name, about_text) VALUES (?, ?, ?)");
    $stmt->execute([
        $_SESSION['tenant_id'],
        $tenant['name'],
        'We are a leading car rental company committed to providing exceptional service and quality vehicles to our customers. With years of experience in the industry, we take pride in offering a wide range of vehicles to suit all your needs.'
    ]);
    $content_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT * FROM website_content WHERE id = ?");
    $stmt->execute([$content_id]);
    $content = $stmt->fetch();
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $stmt = $pdo->prepare("UPDATE website_content SET 
        hero_title = ?,
        hero_subtitle = ?,
        hero_image = ?,
        company_name = ?,
        about_title = ?,
        about_text = ?,
        about_image = ?,
        contact_phone = ?,
        contact_email = ?,
        contact_address = ?
        WHERE tenant_id = ?");

    $stmt->execute([
        $_POST['hero_title'] ?? $content['hero_title'],
        $_POST['hero_subtitle'] ?? $content['hero_subtitle'],
        $_POST['hero_image'] ?? $content['hero_image'],
        $_POST['company_name'] ?? $content['company_name'],
        $_POST['about_title'] ?? $content['about_title'],
        $_POST['about_text'] ?? $content['about_text'],
        $_POST['about_image'] ?? $content['about_image'],
        $_POST['contact_phone'] ?? $content['contact_phone'],
        $_POST['contact_email'] ?? $content['contact_email'],
        $_POST['contact_address'] ?? $content['contact_address'],
        $_SESSION['tenant_id']
    ]);

    // Refresh content
    $stmt = $pdo->prepare("SELECT * FROM website_content WHERE tenant_id = ?");
    $stmt->execute([$_SESSION['tenant_id']]);
    $content = $stmt->fetch();

    $success_message = "Website updated successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Website -
        <?= htmlspecialchars($tenant['name'])?>
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/app/custom.css">
    <script src="/app/custom-select.js" defer></script>
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>

<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Bar -->
            <header class="bg-white border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Edit Website</h1>
                        <p class="text-sm text-gray-600 mt-1">customise your website content and design</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <a href="/templates/template-1-preview.php?tenant_id=<?= $_SESSION['tenant_id']?>"
                            target="_blank"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium">
                            Preview
                        </a>
                        <button onclick="document.getElementById('saveForm').submit()"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                            Save Changes
                        </button>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-y-auto p-6">
                <?php if (isset($success_message)): ?>
                <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                    <?= $success_message?>
                </div>
                <?php
endif; ?>

                <form id="saveForm" method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="save">

                    <!-- Company Info Section -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Company Information</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Company Name</label>
                                <input type="text" name="company_name"
                                    value="<?= htmlspecialchars($content['company_name'])?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                        </div>
                    </div>

                    <!-- Hero Section -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Hero Section</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Hero Title</label>
                                <input type="text" name="hero_title"
                                    value="<?= htmlspecialchars($content['hero_title'])?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Hero Subtitle</label>
                                <textarea name="hero_subtitle" rows="2"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?= htmlspecialchars($content['hero_subtitle'])?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Hero Background Image
                                    URL</label>
                                <input type="url" name="hero_image"
                                    value="<?= htmlspecialchars($content['hero_image'])?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <p class="text-xs text-gray-500 mt-1">Enter the URL of your hero background image</p>
                            </div>
                            <div class="mt-4">
                                <p class="text-sm font-medium text-gray-700 mb-2">Preview:</p>
                                <div class="relative h-48 rounded-lg overflow-hidden bg-cover bg-center"
                                    style="background-image: url('<?= htmlspecialchars($content['hero_image'])?>');">
                                    <div
                                        class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center">
                                        <div class="text-white text-center px-4">
                                            <h3 class="text-2xl font-bold mb-2">
                                                <?= htmlspecialchars($content['hero_title'])?>
                                            </h3>
                                            <p class="text-sm">
                                                <?= htmlspecialchars($content['hero_subtitle'])?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- About Section -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">About Section</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">About Title</label>
                                <input type="text" name="about_title"
                                    value="<?= htmlspecialchars($content['about_title'])?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">About Text</label>
                                <textarea name="about_text" rows="4"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?= htmlspecialchars($content['about_text'])?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">About Image URL</label>
                                <input type="url" name="about_image"
                                    value="<?= htmlspecialchars($content['about_image'])?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                        </div>
                    </div>

                    <!-- Contact Section -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h2>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                <input type="tel" name="contact_phone"
                                    value="<?= htmlspecialchars($content['contact_phone'])?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                <input type="email" name="contact_email"
                                    value="<?= htmlspecialchars($content['contact_email'])?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                                <textarea name="contact_address" rows="2"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?= htmlspecialchars($content['contact_address'])?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button (Bottom) -->
                    <div class="flex justify-end space-x-3">
                        <a href="/dashboard/website.php"
                            class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium">
                            Cancel
                        </a>
                        <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                            Save Changes
                        </button>
                    </div>
                </form>
            </main>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/onboarding-widget.php'; ?>
</body>

</html>