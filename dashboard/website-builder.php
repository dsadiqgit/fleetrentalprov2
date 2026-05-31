<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/auth/login.php');
}

$pdo = getDB();

// Handle image upload FIRST before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/json');

    try {
        $upload_dir = __DIR__ . '/../uploads/website/';

        // Create directory with proper permissions
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                echo json_encode(['success' => false, 'error' => 'Failed to create upload directory. Please check server permissions.']);
                exit;
            }
            chmod($upload_dir, 0777);
        }

        // Verify directory is writable
        if (!is_writable($upload_dir)) {
            echo json_encode(['success' => false, 'error' => 'Upload directory is not writable. Please check permissions.']);
            exit;
        }

        $file = $_FILES['image'];

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
            ];
            $error = $error_messages[$file['error']] ?? 'Unknown upload error (code: ' . $file['error'] . ')';
            echo json_encode(['success' => false, 'error' => $error]);
            exit;
        }

        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($file['type'], $allowed_types)) {
            echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP images allowed.']);
            exit;
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => 'File too large. Maximum size is 5MB.']);
            exit;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filepath = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            chmod($filepath, 0644);
            $url = '/uploads/website/' . $filename;

            // Save to media library
            $stmt = $pdo->prepare("INSERT INTO media_library (tenant_id, file_path, file_name, file_size, file_type) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['tenant_id'], $url, $file['name'], $file['size'], $file['type']]);

            echo json_encode(['success' => true, 'url' => $url]);
        }
        else {
            echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file. Directory: ' . $upload_dir]);
        }
    }
    catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Upload error: ' . $e->getMessage()]);
    }
    exit;
}

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
    stat_vehicles VARCHAR(50) DEFAULT '1+',
    stat_support VARCHAR(50) DEFAULT '24/7',
    testimonial_title VARCHAR(255) DEFAULT 'Our Customers Saying',
    testimonial_subtitle VARCHAR(255) DEFAULT 'See what our satisfied customers have to say about their experience',
    review1_name VARCHAR(100) DEFAULT 'Alexander',
    review1_role VARCHAR(100) DEFAULT 'Entrepreneur',
    review1_text TEXT,
    review1_image VARCHAR(500) DEFAULT 'https://i.pravatar.cc/60?img=1',
    review1_stars INT DEFAULT 5,
    review2_name VARCHAR(100) DEFAULT 'Sarah',
    review2_role VARCHAR(100) DEFAULT 'Designer',
    review2_text TEXT,
    review2_image VARCHAR(500) DEFAULT 'https://i.pravatar.cc/60?img=2',
    review2_stars INT DEFAULT 5,
    review3_name VARCHAR(100) DEFAULT 'Michael',
    review3_role VARCHAR(100) DEFAULT 'Business Owner',
    review3_text TEXT,
    review3_image VARCHAR(500) DEFAULT 'https://i.pravatar.cc/60?img=3',
    review3_stars INT DEFAULT 5,
    contact_title VARCHAR(255) DEFAULT 'Contact Us',
    contact_subtitle VARCHAR(255) DEFAULT 'Get in touch for bookings and inquiries',
    contact_phone VARCHAR(50) DEFAULT '+1 (555) 123-4567',
    contact_email VARCHAR(100) DEFAULT 'info@yourcompany.com',
    contact_address TEXT,
    font_family VARCHAR(100) DEFAULT 'Inter',
    header_color VARCHAR(7) DEFAULT '#ffffff',
    primary_color VARCHAR(7) DEFAULT '#3b82f6',
    secondary_color VARCHAR(7) DEFAULT '#1e40af',
    text_color VARCHAR(7) DEFAULT '#111827',
    background_color VARCHAR(7) DEFAULT '#ffffff',
    hero_hidden TINYINT(1) DEFAULT 0,
    vehicles_hidden TINYINT(1) DEFAULT 0,
    about_hidden TINYINT(1) DEFAULT 0,
    testimonials_hidden TINYINT(1) DEFAULT 0,
    contact_hidden TINYINT(1) DEFAULT 0,
    stat_vehicles_label VARCHAR(100) DEFAULT 'Vehicles Available',
    stat_support_label VARCHAR(100) DEFAULT 'Customer Support',
    hero_button_text VARCHAR(100) DEFAULT 'Rent a Car',
    services_title VARCHAR(255) DEFAULT 'Our Services',
    services_subtitle VARCHAR(255) DEFAULT 'Our Premier services for your car rental needs',
    services_description TEXT,
    service1_title VARCHAR(255) DEFAULT 'Well-Maintained Car',
    service1_text TEXT,
    service2_title VARCHAR(255) DEFAULT 'Secure Payments',
    service2_text TEXT,
    service3_title VARCHAR(255) DEFAULT '24/7 Support',
    service3_text TEXT,
    services_hidden TINYINT(1) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_tenant (tenant_id),
    INDEX idx_tenant_id (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Run schema updates for existing tables
try {
    $pdo->exec("ALTER TABLE website_content ADD COLUMN IF NOT EXISTS hero_hidden TINYINT(1) DEFAULT 0");
    $pdo->exec("ALTER TABLE website_content ADD COLUMN IF NOT EXISTS vehicles_hidden TINYINT(1) DEFAULT 0");
    $pdo->exec("ALTER TABLE website_content ADD COLUMN IF NOT EXISTS about_hidden TINYINT(1) DEFAULT 0");
    $pdo->exec("ALTER TABLE website_content ADD COLUMN IF NOT EXISTS testimonials_hidden TINYINT(1) DEFAULT 0");
    $pdo->exec("ALTER TABLE website_content ADD COLUMN IF NOT EXISTS contact_hidden TINYINT(1) DEFAULT 0");
    $pdo->exec("ALTER TABLE website_content ADD COLUMN IF NOT EXISTS stat_vehicles_label VARCHAR(100) DEFAULT 'Vehicles Available'");
    $pdo->exec("ALTER TABLE website_content ADD COLUMN IF NOT EXISTS stat_support_label VARCHAR(100) DEFAULT 'Customer Support'");
    $pdo->exec("ALTER TABLE website_content ADD COLUMN IF NOT EXISTS hero_button_text VARCHAR(100) DEFAULT 'Rent a Car'");
    $pdo->exec("ALTER TABLE website_content ADD COLUMN IF NOT EXISTS services_title VARCHAR(255) DEFAULT 'Our Services'");
    $pdo->exec("ALTER TABLE website_content ADD COLUMN IF NOT EXISTS services_subtitle VARCHAR(255) DEFAULT 'Our Premier services for your car rental needs'");
    $pdo->exec("ALTER TABLE website_content ADD COLUMN IF NOT EXISTS services_description TEXT");
    $pdo->exec("ALTER TABLE website_content ADD COLUMN IF NOT EXISTS service1_title VARCHAR(255) DEFAULT 'Well-Maintained Car'");
    $pdo->exec("ALTER TABLE website_content ADD COLUMN IF NOT EXISTS service1_text TEXT");
    $pdo->exec("ALTER TABLE website_content ADD COLUMN IF NOT EXISTS service2_title VARCHAR(255) DEFAULT 'Secure Payments'");
    $pdo->exec("ALTER TABLE website_content ADD COLUMN IF NOT EXISTS service2_text TEXT");
    $pdo->exec("ALTER TABLE website_content ADD COLUMN IF NOT EXISTS service3_title VARCHAR(255) DEFAULT '24/7 Support'");
    $pdo->exec("ALTER TABLE website_content ADD COLUMN IF NOT EXISTS service3_text TEXT");
    $pdo->exec("ALTER TABLE website_content ADD COLUMN IF NOT EXISTS services_hidden TINYINT(1) DEFAULT 0");
    $pdo->exec("ALTER TABLE website_content ADD COLUMN IF NOT EXISTS sections_order TEXT NULL");
}
catch (PDOException $e) {
// Columns might already exist
}

// Add display_order column to vehicles table if it doesn't exist
try {
    $pdo->exec("ALTER TABLE vehicles ADD COLUMN IF NOT EXISTS display_order INT DEFAULT 0");
}
catch (PDOException $e) {
// Column already exists, ignore
}

// Create media_library table
$pdo->exec("CREATE TABLE IF NOT EXISTS media_library (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255),
    file_size INT,
    file_type VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Get or create website content
$stmt = $pdo->prepare("SELECT * FROM website_content WHERE tenant_id = ?");
$stmt->execute([$_SESSION['tenant_id']]);
$content = $stmt->fetch();

if (!$content) {
    // Create default content
    $stmt = $pdo->prepare("INSERT INTO website_content (tenant_id, company_name, hero_subtitle, about_text, contact_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['tenant_id'],
        $tenant['name'],
        'Discover the freedom, explore the world with our wide range of premium vehicles',
        'We are a leading car rental company committed to providing exceptional service and quality vehicles to our customers. With years of experience in the industry, we take pride in offering a wide range of vehicles to suit all your needs.',
        '123 Main Street, New York, NY 10001'
    ]);

    $stmt = $pdo->prepare("SELECT * FROM website_content WHERE tenant_id = ?");
    $stmt->execute([$_SESSION['tenant_id']]);
    $content = $stmt->fetch();
}

// Handle AJAX save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_once __DIR__ . '/../includes/security-helper.php';
    check_rate_limit('builder_save', 30, 60); // 30 saves per minute
    header('Content-Type: application/json');

    if ($_POST['action'] === 'save') {
        $field = $_POST['field'] ?? '';
        $value = $_POST['value'] ?? '';

        $allowed_fields = [
            'hero_title', 'hero_subtitle', 'hero_image', 'company_name',
            'about_title', 'about_text', 'about_image',
            'stat_vehicles', 'stat_support',
            'testimonial_title', 'testimonial_subtitle',
            'review1_name', 'review1_role', 'review1_text', 'review1_image', 'review1_stars',
            'review2_name', 'review2_role', 'review2_text', 'review2_image', 'review2_stars',
            'review3_name', 'review3_role', 'review3_text', 'review3_image', 'review3_stars',
            'contact_title', 'contact_subtitle',
            'contact_phone', 'contact_email', 'contact_address',
            'font_family', 'header_color', 'primary_color', 'secondary_color', 'text_color', 'background_color',
            'hero_hidden', 'vehicles_hidden', 'about_hidden', 'testimonials_hidden', 'contact_hidden',
            'stat_vehicles_label', 'stat_support_label', 'hero_button_text',
            'services_title', 'services_subtitle', 'services_description',
            'service1_title', 'service1_text',
            'service2_title', 'service2_text',
            'service3_title', 'service3_text',
            'services_hidden'
        ];

        if (in_array($field, $allowed_fields)) {
            $stmt = $pdo->prepare("UPDATE website_content SET $field = ? WHERE tenant_id = ?");
            $stmt->execute([$value, $_SESSION['tenant_id']]);
            echo json_encode(['success' => true]);
        }
        else {
            echo json_encode(['success' => false, 'error' => 'Invalid field']);
        }
    }
    elseif ($_POST['action'] === 'reorder_vehicles') {
        $order = json_decode($_POST['order'], true);
        if (is_array($order)) {
            foreach ($order as $index => $vehicleId) {
                $stmt = $pdo->prepare("UPDATE vehicles SET display_order = ? WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$index, $vehicleId, $_SESSION['tenant_id']]);
            }
            echo json_encode(['success' => true]);
        }
    }
    elseif ($_POST['action'] === 'reorder_sections') {
        $order = $_POST['order'] ?? '[]';
        $stmt = $pdo->prepare("UPDATE website_content SET sections_order = ? WHERE tenant_id = ?");
        $stmt->execute([$order, $_SESSION['tenant_id']]);
        echo json_encode(['success' => true]);
    }
    elseif ($_POST['action'] === 'get_media') {
        $stmt = $pdo->prepare("SELECT * FROM media_library WHERE tenant_id = ? ORDER BY created_at DESC");
        $stmt->execute([$_SESSION['tenant_id']]);
        $media = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'media' => $media]);
    }
    elseif ($_POST['action'] === 'save_logo') {
        $logo_url = $_POST['logo_url'] ?? '';
        $stmt = $pdo->prepare("UPDATE tenants SET logo_url = ? WHERE id = ?");
        $stmt->execute([$logo_url, $_SESSION['tenant_id']]);
        echo json_encode(['success' => true]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Builder -
        <?= htmlspecialchars($tenant['name'])?>
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="/app/custom-select.js" defer></script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&family=Open+Sans:wght@300;400;600;700&family=Lato:wght@300;400;700&family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&family=Raleway:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        .editable {
            position: relative;
            cursor: pointer;
            transition: all 0.2s;
        }

        .editable:hover {
            outline: 2px dashed #3b82f6;
            outline-offset: 4px;
        }

        .editable.editing {
            outline: 2px solid #3b82f6;
            outline-offset: 4px;
        }

        .edit-tooltip {
            position: absolute;
            top: -40px;
            left: 0;
            background: #3b82f6;
            color: white;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            opacity: 0;
            pointer-events: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
            z-index: 1000;
            letter-spacing: normal;
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);
        }

        .editable:hover .edit-tooltip {
            opacity: 1;
        }

        .builder-toolbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            z-index: 9999;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .builder-content {
            margin-top: 90px;
        }

        .save-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s;
            z-index: 9999;
        }

        .save-indicator.show {
            opacity: 1;
            transform: translateY(0);
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
        }

        .modal-overlay.show {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            transform: scale(0.9);
            transition: transform 0.3s;
        }

        .modal-overlay.show .modal-content {
            transform: scale(1);
        }

        .loader-overlay {
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10001;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
        }

        .loader-overlay.show {
            opacity: 1;
            pointer-events: auto;
        }

        .loader {
            width: 60px;
            height: 60px;
            border: 4px solid #e5e7eb;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .settings-panel {
            position: fixed;
            right: -400px;
            top: 0;
            width: 400px;
            height: 100vh;
            background: white;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
            transition: right 0.3s;
            z-index: 10000;
            overflow-y: auto;
        }

        .settings-panel.show {
            right: 0;
        }

        .builder-toolbar.hide-for-settings {
            transform: translateY(-100%);
            transition: transform 0.3s ease-in-out;
        }

        .color-picker-wrapper {
            position: relative;
        }

        .color-preview {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 2px solid #e5e7eb;
            cursor: pointer;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>

<body class="bg-gray-50">
    <!-- Builder Toolbar -->
    <div class="builder-toolbar">
        <div class="flex items-center justify-between px-6 py-4">
            <div>
                <nav class="text-sm text-gray-500 mb-1">
                    <a href="/dashboard/" class="hover:text-gray-700">Dashboard</a>
                    <span class="mx-2">/</span>
                    <span class="text-gray-900">Website Builder</span>
                </nav>
                <h1 class="text-xl font-bold text-gray-900">Website Builder</h1>
            </div>
            <div class="flex items-center space-x-3">
                <button onclick="toggleTab('sections')"
                    class="p-2 text-gray-400 hover:text-blue-600 transition-colors relative group" title="Sections">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    <span
                        class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 text-white text-[10px] rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">Sections</span>
                </button>
                <button onclick="toggleTab('settings')"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                        </path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Settings
                </button>
                <?php
$tenant_url = (ROOT_DOMAIN === 'localhost')
    ? "http://{$tenant['subdomain']}." . ROOT_DOMAIN . ":" . PORT
    : "http://{$tenant['subdomain']}." . ROOT_DOMAIN;
?>
                <a href="<?= $tenant_url?>" target="_blank"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                        </path>
                    </svg>
                    Preview Website
                </a>
            </div>
        </div>
    </div>

    <!-- Save Indicator -->
    <div class="save-indicator" id="saveIndicator">
        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        Saved
    </div>

    <!-- Website Content -->
    <div class="builder-content" id="builderBody">
        <?php
$sections_order_json = $content['sections_order'] ?? '["hero", "vehicles", "services", "about", "testimonials", "contact"]';
$sections_order = json_decode($sections_order_json, true);
if (!$sections_order || !is_array($sections_order)) {
    $sections_order = ["hero", "vehicles", "services", "about", "testimonials", "contact"];
}

// Ensure 'services' is in the order for existing tenants
if (!in_array('services', $sections_order)) {
    $v_index = array_search('vehicles', $sections_order);
    if ($v_index !== false) {
        array_splice($sections_order, $v_index + 1, 0, 'services');
    } else {
        $sections_order[] = 'services';
    }
}

// Get currency setting
$stmt = $pdo->prepare("SELECT currency FROM tenant_settings WHERE tenant_id = ?");
$stmt->execute([$_SESSION['tenant_id']]);
$settings = $stmt->fetch();
$currency_code = $settings['currency'] ?? 'GBP';
$currency_symbols = ['GBP' => '£', 'USD' => '$', 'EUR' => '€'];
$currency_symbol = $currency_symbols[$currency_code] ?? $currency_code;
?>
    <header class="border-b border-gray-200 sticky top-0 z-[100] transition-colors duration-300" style="background-color: var(--header-color);">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <div class="flex items-center space-x-3">
                    <?php if (!empty($tenant['logo']) || !empty($tenant['logo_url'])): ?>
                    <img src="<?= htmlspecialchars($tenant['logo_url'] ?: $tenant['logo'])?>" alt="Logo" class="h-8 w-auto">
                    <?php
else: ?>
                    <div class="w-8 h-8 bg-black rounded flex items-center justify-center text-white font-bold">⚡</div>
                    <?php
endif; ?>
                    <span class="text-xl font-bold truncate">
                        <?= htmlspecialchars($content['company_name'] ?? $tenant['name'])?>
                    </span>
                </div>

                <!-- Navigation -->
                <nav class="hidden md:flex items-center space-x-8">
                    <a href="#" class="text-gray-700 hover:text-gray-900 text-sm font-semibold transition-colors">Home</a>
                    <a href="#fleet" class="text-gray-700 hover:text-gray-900 text-sm font-semibold transition-colors">Our Fleet</a>
                    <a href="#about" class="text-gray-700 hover:text-gray-900 text-sm font-semibold transition-colors">About</a>
                    <a href="#contact" class="text-gray-700 hover:text-gray-900 text-sm font-semibold transition-colors">Contact</a>
                    <button class="px-6 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-100">
                        Book Now
                    </button>
                </nav>
            </div>
        </div>
    </header>

        <div id="sortableSections">
            <?php foreach ($sections_order as $section): ?>
            <?php if ($section === 'hero'): ?>
            <!-- Hero Section -->
            <section class="relative h-[85vh] min-h-[700px] bg-cover bg-center flex items-end rounded-[20px] m-[20px] group/section <?=($content['hero_hidden'] ?? 0) ? 'hidden' : ''?>"
                id="section_hero" data-section-id="hero"
                style="background-image: url('<?= htmlspecialchars($content['hero_image'] ?? '')?>');">
                <!-- Section Controls -->
                <div class="absolute top-4 right-4 z-[60] opacity-0 group-hover/section:opacity-100 transition-opacity">
                    <button onclick="toggleSection('hero', 1)"
                        class="bg-red-500 hover:bg-red-600 text-white p-2 rounded-lg shadow-lg flex items-center gap-2 text-xs font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                        REMOVE
                    </button>
                </div>
                
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent rounded-[20px]"></div>

                <div class="relative max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 w-full pb-60">
                    <div class="text-white max-w-3xl">
                        <h1 class="text-5xl md:text-7xl font-bold mb-4 tracking-tight leading-tight">
                            <span class="editable block w-full h-full" data-field="hero_title" contenteditable="false">
                                <span class="edit-tooltip">Click to edit title</span>
                                <?= htmlspecialchars($content['hero_title'] ?? 'Rent a Car for Every Journey')?>
                            </span>
                        </h1>
                        <p class="text-xl opacity-0 h-0 overflow-hidden">
                            <span class="editable block w-full h-full" data-field="hero_subtitle" contenteditable="false">
                                <span class="edit-tooltip">Click to edit subtitle</span>
                                <?= htmlspecialchars($content['hero_subtitle'] ?? '')?>
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Search Bar Floating -->
                <div class="absolute bottom-12 left-1/2 transform -translate-x-1/2 w-full max-w-7xl px-4 z-[90]">
                    <div class="bg-white rounded-2xl shadow-2xl p-6 sm:p-5 border border-gray-100">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 flex items-center gap-3 cursor-pointer relative" id="date_range_picker_preview">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <label class="block text-[10px] uppercase font-bold text-gray-400">Pick-up</label>
                                            <span id="pickup_display" class="font-bold text-gray-900 text-sm">Select dates</span>
                                        </div>
                                        <div class="w-px h-8 bg-gray-200 mx-4"></div>
                                        <div class="flex-1">
                                            <label class="block text-[10px] uppercase font-bold text-gray-400">Drop-off</label>
                                            <span id="dropoff_display" class="font-bold text-gray-900 text-sm">Select dates</span>
                                        </div>
                                    </div>
                                    <input type="text" id="date_range_prev" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                                </div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 flex items-center gap-3 cursor-pointer relative time-dropdown-container">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="w-full">
                                    <label class="block text-[10px] uppercase font-bold text-gray-400">Time</label>
                                    <div class="flex items-center justify-between">
                                        <span id="pickup_time_display" class="font-bold text-gray-900">10:00 AM</span>
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="absolute top-full left-0 w-full bg-white mt-2 rounded-xl shadow-2xl border border-gray-100 hidden z-[100] max-h-[300px] overflow-y-auto time-options-list custom-scrollbar">
                                </div>
                            </div>

                            <button class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-xl font-bold shadow-lg shadow-blue-200 transition-all flex items-center justify-center gap-2">
                                Search Vehicles
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <?php
    elseif ($section === 'vehicles'): ?>
            <!-- All Vehicles Section -->
            <section class="py-24 bg-white group/section <?=($content['vehicles_hidden'] ?? 0) ? 'hidden' : ''?>"
                id="section_vehicles" data-section-id="vehicles">
                <!-- Section Controls -->
                <div class="absolute top-4 right-4 z-[60] opacity-0 group-hover/section:opacity-100 transition-opacity">
                    <button onclick="toggleSection('vehicles', 1)"
                        class="bg-red-500 hover:bg-red-600 text-white p-2 rounded-lg shadow-lg flex items-center gap-2 text-xs font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                        REMOVE
                    </button>
                </div>
                <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-col md:flex-row justify-between items-end mb-12 gap-6">
                        <div>
                            <h2 class="text-[32px] font-bold text-gray-900 mb-3 tracking-tight editable" data-field="vehicles_title" contenteditable="false">
                                <span class="edit-tooltip">Click to edit title</span>
                                <?= htmlspecialchars($content['vehicles_title'] ?? 'Top picks vehicle this month')?>
                            </h2>
                            <p class="text-gray-500 text-base max-w-xl editable" data-field="vehicles_subtitle" contenteditable="false">
                                <span class="edit-tooltip">Click to edit subtitle</span>
                                <?= htmlspecialchars($content['vehicles_subtitle'] ?? 'Experience the epitome of amazing journey with our top picks.')?>
                            </p>
                        </div>
                    </div>

                    <?php
        // Fetch all vehicles for this tenant
        $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE tenant_id = ? ORDER BY display_order ASC, id ASC");
        $stmt->execute([$_SESSION['tenant_id']]);
        $vehicles = $stmt->fetchAll();

        if (count($vehicles) > 0):
?>
                    <div id="vehiclesList" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <?php foreach ($vehicles as $vehicle): ?>
                        <div class="vehicle-card block group"
                            data-vehicle-id="<?= $vehicle['id']?>" id="vehicle_<?= $vehicle['id']?>">
                            
                            <!-- Image Box -->
                            <div
                                class="bg-[#F8F9FA] rounded-[24px] h-60 mb-5 relative flex items-center justify-center transition-all duration-300 group-hover:bg-gray-200/60 overflow-hidden">
                                <div
                                    class="absolute top-4 left-4 bg-white/60 text-gray-700 px-3.5 py-1.5 rounded-full text-[11px] font-semibold backdrop-blur-sm z-10 transition-colors group-hover:bg-black/[0.04]">
                                    <?= htmlspecialchars(ucfirst($vehicle['category']))?>
                                </div>
                                <?php
                        $images = json_decode($vehicle['images'], true);
                        $img = is_array($images) ? $images[0] : ($vehicle['images'] ?: '');
                        if ($img): ?>
                                <img src="<?= htmlspecialchars($img)?>"
                                    class="w-full h-full object-cover mix-blend-multiply group-hover:scale-105 transition-transform duration-500">
                                <?php
                        endif; ?>

                                <!-- Vehicle Ordering Controls (Builder Only) -->
                                <div class="absolute bottom-4 right-4 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button onclick="moveVehicle(<?= $vehicle['id']?>, 'up')" class="bg-white/80 backdrop-blur p-2 rounded-xl shadow-lg hover:bg-white text-gray-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg></button>
                                    <button onclick="moveVehicle(<?= $vehicle['id']?>, 'down')" class="bg-white/80 backdrop-blur p-2 rounded-xl shadow-lg hover:bg-white text-gray-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg></button>
                                </div>
                            </div>

                            <!-- Content -->
                            <h3
                                class="text-lg font-bold text-gray-900 mb-1.5 tracking-tight group-hover:text-blue-600 transition-colors">
                                <?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'])?>
                            </h3>

                            <div class="flex items-center text-[13px] text-gray-500 mb-4 gap-1.5 font-medium">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4">
                                    </path>
                                </svg>
                                <?= htmlspecialchars(ucfirst($vehicle['transmission']))?>
                            </div>

                            <div class="flex items-center text-[13px] text-gray-600 gap-4 mb-5 font-semibold">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <?= $vehicle['seats']?>
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                    2
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z">
                                        </path>
                                    </svg>
                                    4.8
                                </span>
                            </div>

                            <div class="flex flex-col">
                                <span class="text-[11px] text-gray-500 mb-0.5">Start from</span>
                                <div class="flex items-baseline gap-1">
                                    <span class="text-xl font-bold text-gray-900"><?= $currency_symbol ?>
                                        <?= number_format($vehicle['price_per_day'])?>
                                    </span>
                                    <span class="text-[13px] font-medium text-gray-500">/ day</span>
                                </div>
                            </div>
                        </div>
                        <?php
                endforeach; ?>
                    </div>
                    <?php
        else: ?>
                    <div class="text-center py-20 bg-gray-50 rounded-3xl border border-dashed border-gray-200">
                        <svg class="w-20 h-20 mx-auto text-gray-300 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">No Vehicles Ready</h3>
                        <p class="text-gray-500">Your fleet will appear here once vehicles are added.</p>
                    </div>
                    <?php
        endif; ?>
                </div>
            </section>
            
            <?php
    elseif ($section === 'services'): ?>
            <!-- Services Section -->
            <section id="services" class="py-24 bg-[#0F1219] group/section <?=($content['services_hidden'] ?? 0) ? 'hidden' : ''?>" data-section-id="services">
                <!-- Section Controls -->
                <div class="absolute top-4 right-4 z-[60] opacity-0 group-hover/section:opacity-100 transition-opacity">
                    <button onclick="toggleSection('services', 1)" class="bg-red-500 hover:bg-red-600 text-white p-2 rounded-lg shadow-lg flex items-center gap-2 text-xs font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        REMOVE
                    </button>
                </div>

                <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid lg:grid-cols-2 gap-8 items-start mb-16">
                        <div>
                            <span class="text-blue-500 font-bold tracking-widest text-sm uppercase mb-4 block editable" data-field="services_title" contenteditable="false">
                                <span class="edit-tooltip">Click to edit</span>
                                <?= htmlspecialchars($content['services_title'] ?? 'Our Services')?>
                            </span>
                            <h2 class="text-[40px] leading-[1.2] font-bold text-white max-w-xl editable" data-field="services_subtitle" contenteditable="false">
                                <span class="edit-tooltip">Click to edit subtitle</span>
                                <?= htmlspecialchars($content['services_subtitle'] ?? 'Our Premier services for your car rental needs')?>
                            </h2>
                        </div>
                        <div class="lg:pt-8">
                            <p class="text-gray-400 text-lg leading-relaxed max-w-xl editable" data-field="services_description" contenteditable="false">
                                <span class="edit-tooltip">Click to edit description</span>
                                <?= htmlspecialchars($content['services_description'] ?? 'We take pride in providing top-notch solutions! Our premier services ensure a seamless & simple car rental experience. offering cars that suit your preferences')?>
                            </p>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-3 gap-8">
                        <!-- Service 1 -->
                        <div class="bg-[#1A1F29] p-10 rounded-[32px] border border-white/5 hover:border-blue-500/30 transition-all duration-300 group/card">
                            <div class="w-16 h-16 bg-white/5 rounded-full flex items-center justify-center mb-8 ring-8 ring-white/[0.02]">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-4 editable" data-field="service1_title" contenteditable="false">
                                <span class="edit-tooltip">Click to edit</span>
                                <?= htmlspecialchars($content['service1_title'] ?? 'Well-Maintained Car')?>
                            </h3>
                            <p class="text-gray-400 leading-relaxed editable" data-field="service1_text" contenteditable="false">
                                <span class="edit-tooltip">Click to edit text</span>
                                <?= htmlspecialchars($content['service1_text'] ?? 'Enjoy your trip in peace and comfort with our car rental which offers a well-maintained fleet, prioritize the health and safety of our vehicles')?>
                            </p>
                        </div>

                        <!-- Service 2 -->
                        <div class="bg-[#1A1F29] p-10 rounded-[32px] border border-white/5 hover:border-blue-500/30 transition-all duration-300 group/card">
                            <div class="w-16 h-16 bg-white/5 rounded-full flex items-center justify-center mb-8 ring-8 ring-white/[0.02]">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-4 editable" data-field="service2_title" contenteditable="false">
                                <span class="edit-tooltip">Click to edit</span>
                                <?= htmlspecialchars($content['service2_title'] ?? 'Secure Payments')?>
                            </h3>
                            <p class="text-gray-400 leading-relaxed editable" data-field="service2_text" contenteditable="false">
                                <span class="edit-tooltip">Click to edit text</span>
                                <?= htmlspecialchars($content['service2_text'] ?? 'With a safe and reliable payment system, you can continue your journey with peace of mind, without worrying about transaction security.')?>
                            </p>
                        </div>

                        <!-- Service 3 -->
                        <div class="bg-[#1A1F29] p-10 rounded-[32px] border border-white/5 hover:border-blue-500/30 transition-all duration-300 group/card">
                            <div class="w-16 h-16 bg-white/5 rounded-full flex items-center justify-center mb-8 ring-8 ring-white/[0.02]">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-4 editable" data-field="service3_title" contenteditable="false">
                                <span class="edit-tooltip">Click to edit</span>
                                <?= htmlspecialchars($content['service3_title'] ?? '24/7 Support')?>
                            </h3>
                            <p class="text-gray-400 leading-relaxed editable" data-field="service3_text" contenteditable="false">
                                <span class="edit-tooltip">Click to edit text</span>
                                <?= htmlspecialchars($content['service3_text'] ?? 'We understand that the journey does not always run smoothly. Therefore, our customer support team is ready to help you 24/7.')?>
                            </p>
                        </div>
                    </div>
                </div>
            </section>
            <!-- About Section -->
            <section id="about" class="py-24 bg-gray-50 group/section <?=($content['about_hidden'] ?? 0) ? 'hidden' : ''?>" data-section-id="about">
                <!-- Section Controls -->
                <div class="absolute top-4 right-4 z-[60] opacity-0 group-hover/section:opacity-100 transition-opacity">
                    <button onclick="toggleSection('about', 1)" class="bg-red-500 hover:bg-red-600 text-white p-2 rounded-lg shadow-lg flex items-center gap-2 text-xs font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        REMOVE
                    </button>
                </div>
                <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid lg:grid-cols-2 gap-16 items-center">
                        <div class="editable-image relative" data-field="about_image">
                            <span class="edit-tooltip">Click to change image</span>
                            <img src="<?= htmlspecialchars($content['about_image'] ?? '')?>"
                                class="rounded-[40px] shadow-2xl relative z-10">
                            <div class="absolute -bottom-8 -right-8 w-64 h-64 bg-blue-600/10 rounded-full blur-3xl"></div>
                        </div>
                        <div>
                            <span class="text-blue-600 font-bold tracking-widest text-sm uppercase mb-4 block">About Our
                                Company</span>
                            <h2 class="text-4xl font-extrabold text-gray-900 mb-8 leading-tight">
                                <span class="editable block w-full h-full" data-field="about_title" contenteditable="false">
                                    <span class="edit-tooltip">Click to edit title</span>
                                    <?= htmlspecialchars($content['about_title'] ?? '')?>
                                </span>
                            </h2>
                            <p class="text-gray-600 text-lg leading-relaxed mb-10">
                                <span class="editable block w-full h-full" data-field="about_text" contenteditable="false">
                                    <span class="edit-tooltip">Click to edit text</span>
                                    <?= nl2br(htmlspecialchars($content['about_text'] ?? ''))?>
                                </span>
                            </p>
                            <div class="grid grid-cols-2 gap-8">
                                <div class="p-6 bg-white rounded-3xl border border-gray-100">
                                    <div class="text-3xl font-black text-blue-600 mb-2 editable" data-field="stat_vehicles" contenteditable="false">
                                        <span class="edit-tooltip">Click to edit</span>
                                        <?= htmlspecialchars($content['stat_vehicles'] ?? '1+')?>
                                    </div>
                                    <div class="text-sm font-bold text-gray-400 uppercase tracking-widest editable" data-field="stat_vehicles_label" contenteditable="false">
                                        <span class="edit-tooltip">Click to edit label</span>
                                        <?= htmlspecialchars($content['stat_vehicles_label'] ?? 'Vehicles')?>
                                    </div>
                                </div>
                                <div class="p-6 bg-white rounded-3xl border border-gray-100">
                                    <div class="text-3xl font-black text-blue-600 mb-2 editable" data-field="stat_support" contenteditable="false">
                                        <span class="edit-tooltip">Click to edit</span>
                                        <?= htmlspecialchars($content['stat_support'] ?? '24/7')?>
                                    </div>
                                    <div class="text-sm font-bold text-gray-400 uppercase tracking-widest editable" data-field="stat_support_label" contenteditable="false">
                                        <span class="edit-tooltip">Click to edit label</span>
                                        <?= htmlspecialchars($content['stat_support_label'] ?? 'Support')?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Testimonials Section -->
            <section class="py-24 bg-white group/section <?=($content['testimonials_hidden'] ?? 0) ? 'hidden' : ''?>" id="section_testimonials" data-section-id="testimonials">
                <!-- Section Controls -->
                <div class="absolute top-4 right-4 z-[60] opacity-0 group-hover/section:opacity-100 transition-opacity">
                    <button onclick="toggleSection('testimonials', 1)" class="bg-red-500 hover:bg-red-600 text-white p-2 rounded-lg shadow-lg flex items-center gap-2 text-xs font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        REMOVE
                    </button>
                </div>
                <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-16">
                        <h2 class="text-4xl font-extrabold text-gray-900 mb-4">
                            <span class="editable block w-full h-full" data-field="testimonial_title" contenteditable="false">
                                <span class="edit-tooltip">Click to edit title</span>
                                <?= htmlspecialchars($content['testimonial_title'] ?? 'Our Customers')?>
                            </span>
                        </h2>
                        <p class="text-gray-500 text-lg max-w-2xl mx-auto">
                            <span class="editable block w-full h-full" data-field="testimonial_subtitle" contenteditable="false">
                                <span class="edit-tooltip">Click to edit subtitle</span>
                                <?= htmlspecialchars($content['testimonial_subtitle'] ?? 'Experience shared by our happy clients')?>
                            </span>
                        </p>
                    </div>

                    <div class="grid md:grid-cols-3 gap-8">
                        <?php for ($i = 1; $i <= 3; $i++): ?>
                        <div class="bg-gray-50 p-8 rounded-[32px] border border-gray-100 hover:bg-white hover:shadow-2xl transition duration-300">
                            <div class="flex items-center gap-4 mb-6">
                                <div class="editable-image" data-field="review<?= $i?>_image">
                                    <span class="edit-tooltip">Click to change avatar</span>
                                    <img src="<?= htmlspecialchars($content["review{$i}_image"] ?? "https://i.pravatar.cc/100?img=$i")?>" class="w-14 h-14 rounded-full object-cover ring-4 ring-white shadow-lg">
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-900">
                                        <span class="editable block w-full h-full" data-field="review<?= $i?>_name" contenteditable="false">
                                            <span class="edit-tooltip">Click to edit name</span>
                                            <?= htmlspecialchars($content["review{$i}_name"] ?? 'Happy Customer')?>
                                        </span>
                                    </h4>
                                    <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">
                                        <span class="editable block w-full h-full" data-field="review<?= $i?>_role" contenteditable="false">
                                            <span class="edit-tooltip">Click to edit role</span>
                                            <?= htmlspecialchars($content["review{$i}_role"] ?? 'Customer')?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <p class="text-gray-600 italic leading-relaxed mb-6">
                                " <span class="editable inline-block" data-field="review<?= $i?>_text" contenteditable="false">
                                    <span class="edit-tooltip">Click to edit review</span>
                                    <?= htmlspecialchars($content["review{$i}_text"] ?? 'Great experience renting with FleetRentalPro! Highly recommend.')?>
                                </span> "
                            </p>
                            <div class="flex text-yellow-400 gap-1">
                                <?php for ($s = 0; $s < ($content["review{$i}_stars"] ?? 5); $s++): ?>
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                    <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"></path>
                                </svg>
                                <?php
            endfor; ?>
                            </div>
                        </div>
                        <?php
        endfor; ?>
                    </div>
                </div>
            </section>

            <?php
    elseif ($section === 'contact'): ?>
            <!-- Contact Section -->
            <section id="contact" class="py-24 bg-gray-50 group/section <?=($content['contact_hidden'] ?? 0) ? 'hidden' : ''?>" data-section-id="contact">
                <!-- Section Controls -->
                <div class="absolute top-4 right-4 z-[60] opacity-0 group-hover/section:opacity-100 transition-opacity">
                    <button onclick="toggleSection('contact', 1)" class="bg-red-500 hover:bg-red-600 text-white p-2 rounded-lg shadow-lg flex items-center gap-2 text-xs font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        REMOVE
                    </button>
                </div>
                
                <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="bg-white rounded-[40px] shadow-2xl overflow-hidden border border-gray-100">
                        <div class="grid lg:grid-cols-5">
                            <div class="lg:col-span-2 bg-gray-900 p-12 text-white flex flex-col justify-between">
                                <div>
                                    <h2 class="text-3xl font-bold mb-8">
                                        <span class="editable block w-full h-full" data-field="contact_title" contenteditable="false">
                                            <span class="edit-tooltip">Click to edit title</span>
                                            <?= htmlspecialchars($content['contact_title'] ?? 'Get in Touch')?>
                                        </span>
                                    </h2>
                                    <div class="space-y-8">
                                        <div class="flex items-start gap-4">
                                            <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center shrink-0">
                                                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" stroke-width="2"></path></svg>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Phone</p>
                                                <p class="text-lg editable" data-field="contact_phone" contenteditable="false">
                                                    <span class="edit-tooltip">Click to edit phone</span>
                                                    <?= htmlspecialchars($content['contact_phone'] ?? '+1 (555) 123-4567')?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-4">
                                            <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center shrink-0">
                                                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke-width="2"></path></svg>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Email</p>
                                                <p class="text-lg editable" data-field="contact_email" contenteditable="false">
                                                    <span class="edit-tooltip">Click to edit email</span>
                                                    <?= htmlspecialchars($content['contact_email'] ?? 'info@yourcompany.com')?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="lg:col-span-3 p-12">
                                <form class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Your Name</label>
                                        <input type="text" class="w-full bg-gray-50 border-0 rounded-xl px-4 py-4 focus:ring-2 focus:ring-blue-600 transition" placeholder="John Doe">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Email Address</label>
                                        <input type="email" class="w-full bg-gray-50 border-0 rounded-xl px-4 py-4 focus:ring-2 focus:ring-blue-600 transition" placeholder="john@example.com">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Phone Number</label>
                                        <input type="tel" class="w-full bg-gray-50 border-0 rounded-xl px-4 py-4 focus:ring-2 focus:ring-blue-600 transition" placeholder="+1 (555) 000-0000">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Message</label>
                                        <textarea rows="4" class="w-full bg-gray-50 border-0 rounded-xl px-4 py-4 focus:ring-2 focus:ring-blue-600 transition" placeholder="How can we help?"></textarea>
                                    </div>
                                    <div class="md:col-span-2">
                                        <button class="w-full py-5 bg-blue-600 text-white rounded-2xl font-black text-lg hover:bg-blue-700 transition shadow-xl shadow-blue-100">Send Message</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <?php
    endif; ?>
            <?php
endforeach; ?>

            <!-- Footer -->
            <footer class="bg-gray-900 text-white py-20">
                <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid md:grid-cols-4 gap-12 mb-16 border-b border-gray-800 pb-16">
                        <div class="col-span-2">
                             <div class="flex items-center space-x-3 mb-8">
                                <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white font-bold shadow-lg shadow-blue-900/20">⚡</div>
                                <span class="text-2xl font-black tracking-tight">
                                    <span class="editable" data-field="company_name" contenteditable="false">
                                        <span class="edit-tooltip">Click to edit company name</span>
                                        <?= htmlspecialchars($content['company_name'] ?? 'FleetRentalPro')?>
                                    </span>
                                </span>
                            </div>
                            <p class="text-gray-400 text-lg leading-relaxed max-w-sm">
                                Providing premium vehicle rental services across the globe. Experience luxury, comfort, and reliability with every mile.
                            </p>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold uppercase tracking-widest text-blue-400 mb-8">Navigation</h4>
                            <ul class="space-y-4 text-gray-500 font-medium">
                                <li><a href="#" class="hover:text-white transition">Home</a></li>
                                <li><a href="#vehicles" class="hover:text-white transition">Our Fleet</a></li>
                                <li><a href="#about" class="hover:text-white transition">About Us</a></li>
                                <li><a href="#contact" class="hover:text-white transition">Contact</a></li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold uppercase tracking-widest text-blue-400 mb-8">Support</h4>
                            <ul class="space-y-4 text-gray-500 font-medium">
                                <li><a href="#" class="hover:text-white transition">Help Center</a></li>
                                <li><a href="#" class="hover:text-white transition">Privacy Policy</a></li>
                                <li><a href="#" class="hover:text-white transition">Terms of Service</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="flex flex-col md:flex-row justify-between items-center gap-8">
                        <p class="text-gray-500 font-medium text-sm">
                            &copy; <?= date('Y')?> <?= htmlspecialchars($content['company_name'] ?? 'FleetRentalPro')?>. All rights reserved.
                        </p>
                        <div class="flex gap-6">
                            <?php foreach (['facebook', 'twitter', 'instagram', 'linkedin'] as $social): ?>
                            <a href="#" class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center text-gray-400 hover:bg-blue-600 hover:text-white transition">
                                <span class="sr-only"><?= ucfirst($social)?></span>
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.477 2 2 6.477 2 12c0 5.523 4.477 10 10 10s10-4.477 10-10c0-5.523-4.477-10-10-10z"/></svg>
                            </a>
                            <?php
endforeach; ?>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Media Selector replaced old Upload Modal -->
    <?php include __DIR__ . '/../includes/media-selector.php'; ?>

    <!-- Loader Overlay -->
    <div class="loader-overlay" id="loaderOverlay">
        <div class="loader"></div>
    </div>

    <!-- Error Modal -->
    <div class="modal-overlay" id="errorModal">
        <div class="modal-content">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 text-center mb-2">Upload Failed</h3>
                <p class="text-gray-600 text-center mb-6" id="errorMessage"></p>
                <button onclick="closeErrorModal()"
                    class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                    OK
                </button>
            </div>
        </div>
    </div>

    <!-- Settings Panel -->
    <div class="settings-panel" id="settingsPanel">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900" id="panelTitle">Website Settings</h3>
                <button onclick="toggleTab(null)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
            <div class="flex border-b border-gray-200">
                <button onclick="switchTab('settings')" id="tabBtnSettings"
                    class="px-4 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600">Settings</button>
                <button onclick="switchTab('sections')" id="tabBtnSections"
                    class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">Sections</button>
            </div>
        </div>

        <div id="settingsTabContent" class="p-6 space-y-6">
            <!-- Font Settings -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-4">Typography</h4>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Font Family</label>
                        <select id="fontFamily" onchange="updateFont(this.value)"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="Inter">Inter (Default)</option>
                            <option value="Poppins">Poppins</option>
                            <option value="Roboto">Roboto</option>
                            <option value="Open Sans">Open Sans</option>
                            <option value="Lato">Lato</option>
                            <option value="Montserrat">Montserrat</option>
                            <option value="Playfair Display">Playfair Display</option>
                            <option value="Raleway">Raleway</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Company Logo</label>
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 bg-gray-50 rounded-xl border border-dashed border-gray-300 flex items-center justify-center overflow-hidden" id="logo_preview_container">
                                <?php if (!empty($tenant['logo']) || !empty($tenant['logo_url'])): ?>
                                    <img src="<?= htmlspecialchars($tenant['logo_url'] ?: $tenant['logo'])?>" class="w-full h-full object-contain">
                                <?php
else: ?>
                                    <span class="text-2xl">⚡</span>
                                <?php
endif; ?>
                            </div>
                            <button onclick="openMediaSelector(updateLogo)" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">Change Logo</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Color Settings -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-4">Colors</h4>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Header Color</label>
                        <div class="flex items-center gap-3">
                            <input type="color" id="headerColor" value="#ffffff"
                                onchange="updateColor('header', this.value)"
                                class="w-12 h-12 rounded-lg border-2 border-gray-300 cursor-pointer">
                            <input type="text" id="headerColorText" value="#ffffff"
                                onchange="updateColorFromText('header', this.value)"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Primary Color</label>
                        <div class="flex items-center gap-3">
                            <input type="color" id="primaryColor" value="#3b82f6"
                                onchange="updateColor('primary', this.value)"
                                class="w-12 h-12 rounded-lg border-2 border-gray-300 cursor-pointer">
                            <input type="text" id="primaryColorText" value="#3b82f6"
                                onchange="updateColorFromText('primary', this.value)"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Secondary Color</label>
                        <div class="flex items-center gap-3">
                            <input type="color" id="secondaryColor" value="#1e40af"
                                onchange="updateColor('secondary', this.value)"
                                class="w-12 h-12 rounded-lg border-2 border-gray-300 cursor-pointer">
                            <input type="text" id="secondaryColorText" value="#1e40af"
                                onchange="updateColorFromText('secondary', this.value)"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Text Color</label>
                        <div class="flex items-center gap-3">
                            <input type="color" id="textColor" value="#111827"
                                onchange="updateColor('text', this.value)"
                                class="w-12 h-12 rounded-lg border-2 border-gray-300 cursor-pointer">
                            <input type="text" id="textColorText" value="#111827"
                                onchange="updateColorFromText('text', this.value)"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Background Color</label>
                        <div class="flex items-center gap-3">
                            <input type="color" id="backgroundColor" value="#ffffff"
                                onchange="updateColor('background', this.value)"
                                class="w-12 h-12 rounded-lg border-2 border-gray-300 cursor-pointer">
                            <input type="text" id="backgroundColorText" value="#ffffff"
                                onchange="updateColorFromText('background', this.value)"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visibility Settings -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-4">Visible Sections</h4>
                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100">
                        <input type="checkbox" <?=($content['hero_hidden'] ?? 0) ? '' : 'checked'?>
                        onchange="toggleSection('hero', !this.checked)" class="w-4 h-4 text-blue-600 rounded">
                        <span class="text-sm font-medium text-gray-700">Hero Section</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100">
                        <input type="checkbox" <?=($content['vehicles_hidden'] ?? 0) ? '' : 'checked'?>
                        onchange="toggleSection('vehicles', !this.checked)" class="w-4 h-4 text-blue-600 rounded">
                        <span class="text-sm font-medium text-gray-700">Vehicles Section</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100">
                        <input type="checkbox" <?=($content['about_hidden'] ?? 0) ? '' : 'checked'?>
                        onchange="toggleSection('about', !this.checked)" class="w-4 h-4 text-blue-600 rounded">
                        <span class="text-sm font-medium text-gray-700">About Section</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100">
                        <input type="checkbox" <?=($content['testimonials_hidden'] ?? 0) ? '' : 'checked'?>
                        onchange="toggleSection('testimonials', !this.checked)" class="w-4 h-4 text-blue-600 rounded">
                        <span class="text-sm font-medium text-gray-700">Testimonials Section</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100">
                        <input type="checkbox" <?=($content['contact_hidden'] ?? 0) ? '' : 'checked'?>
                        onchange="toggleSection('contact', !this.checked)" class="w-4 h-4 text-blue-600 rounded">
                        <span class="text-sm font-medium text-gray-700">Contact Section</span>
                    </label>
                </div>
            </div>

            <!-- Hero Content Settings -->
            <div class="mt-8 pt-8 border-t border-gray-100">
                <h4 class="text-sm font-semibold text-gray-900 mb-4">Hero Content</h4>
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-gray-400 mb-1">Title</label>
                        <input type="text" data-sync="hero_title" value="<?= htmlspecialchars($content['hero_title'])?>"
                            oninput="syncPreview('hero_title', this.value)"
                            onchange="saveField('hero_title', this.value)"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-gray-400 mb-1">Subtitle</label>
                        <textarea data-sync="hero_subtitle" rows="3" oninput="syncPreview('hero_subtitle', this.value)"
                            onchange="saveField('hero_subtitle', this.value)"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?= htmlspecialchars($content['hero_subtitle'])?></textarea>
                    </div>
                </div>
            </div>

            <!-- Reset Button -->
            <div class="pt-4 border-t border-gray-200">
                <button onclick="resetSettings()"
                    class="w-full px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium">
                    Reset to Default
                </button>
            </div>
        </div>

        <div id="sectionsTabContent" class="p-6 space-y-6 hidden">
        <p class="text-sm text-gray-500 mb-4">Drag sections to reorder or toggle visibility.</p>
        <div id="sectionsList" class="space-y-3">
            <!-- Sections will be populated here -->
            <?php foreach ($sections_order as $section_id): ?>
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200 section-order-item <?=($content[$section_id . '_hidden'] ?? 0) ? 'opacity-50 grayscale bg-gray-100' : ''?>"
                data-id="<?= $section_id?>" id="mgmt_row_<?= $section_id?>">
                <div class="flex items-center gap-3">
                    <div class="flex flex-col gap-1">
                        <button onclick="moveSectionTab('up', '<?= $section_id?>')"
                            class="text-gray-400 hover:text-blue-600 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7">
                                </path>
                            </svg>
                        </button>
                        <button onclick="moveSectionTab('down', '<?= $section_id?>')"
                            class="text-gray-400 hover:text-blue-600 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                    </div>
                    <span class="text-sm font-semibold text-gray-900 capitalize">
                        <?= str_replace('_', ' ', $section_id)?>
                    </span>
                </div>
                <input type="checkbox" <?=($content[$section_id . '_hidden'] ?? 0) ? '' : 'checked'?>
                onchange="toggleSection('<?= $section_id?>', !this.checked)" class="w-4 h-4 text-blue-600 rounded">
            </div>
            <?php
endforeach; ?>
        </div>
    </div>
</div>
    <script>

        function updateLogo(url) {
            const container = document.getElementById('logo_preview_container');
            container.innerHTML = `<img src="${url}" class="w-full h-full object-contain">`;
            
            // In a real multi-tenant app, we would update the tenants table
            // For now, we'll use a hack to save it to a field we can use
            saveField('company_logo', url); // We'll add this to allowed fields or handle differently
            
            // Ideally we'd have a specific action for tenant logo
            const formData = new FormData();
            formData.append('action', 'save_logo');
            formData.append('logo_url', url);
            fetch(window.location.href, { method: 'POST', body: formData });

            setTimeout(() => window.location.reload(), 500);
        }

        function toggleTab(tab) {
            const panel = document.getElementById('settingsPanel');
            const toolbar = document.querySelector('.builder-toolbar');

            if (tab === null) {
                panel.classList.remove('show');
                toolbar.classList.remove('hide-for-settings');
                return;
            }

            panel.classList.add('show');
            toolbar.classList.add('hide-for-settings');
            switchTab(tab);
        }

        function switchTab(tab) {
            const settingsTab = document.getElementById('settingsTabContent');
            const sectionsTab = document.getElementById('sectionsTabContent');
            const settingsBtn = document.getElementById('tabBtnSettings');
            const sectionsBtn = document.getElementById('tabBtnSections');
            const title = document.getElementById('panelTitle');

            if (tab === 'settings') {
                settingsTab.classList.remove('hidden');
                sectionsTab.classList.add('hidden');
                settingsBtn.className = 'px-4 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600';
                sectionsBtn.className = 'px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700';
                title.textContent = 'Website Settings';
            } else {
                settingsTab.classList.add('hidden');
                sectionsTab.classList.remove('hidden');
                settingsBtn.className = 'px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700';
                sectionsBtn.className = 'px-4 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600';
                title.textContent = 'Section Management';
            }
        }



        function moveSectionTab(direction, section_id) {
            const row = document.getElementById(`mgmt_row_${section_id}`);
            if (!row) return;

            if (direction === 'up') {
                const prev = row.previousElementSibling;
                if (prev) {
                    row.parentNode.insertBefore(row, prev);
                }
            } else {
                const next = row.nextElementSibling;
                if (next) {
                    row.parentNode.insertBefore(next, row);
                }
            }
            saveSectionOrder();
        }

        function moveVehicle(id, direction) {
            const vehicle = document.getElementById(`vehicle_${id}`);
            const list = document.getElementById('vehiclesList');
            if (!vehicle || !list) return;

            if (direction === 'up') {
                const prev = vehicle.previousElementSibling;
                if (prev) {
                    list.insertBefore(vehicle, prev);
                }
            } else {
                const next = vehicle.nextElementSibling;
                if (next) {
                    list.insertBefore(next, vehicle);
                }
            }
            saveVehiclesOrder();
        }

        function saveVehiclesOrder() {
            const list = document.getElementById('vehiclesList');
            const items = [...list.querySelectorAll('.vehicle-card')];
            const order = items.map(item => item.dataset.vehicleId);

            const formData = new FormData();
            formData.append('action', 'reorder_vehicles');
            formData.append('order', JSON.stringify(order));

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showSaveIndicator();
                    }
                });
        }

        function saveSectionOrder() {
            const items = [...sectionsList.querySelectorAll('.section-order-item')];
            const order = items.map(item => item.dataset.id);

            const formData = new FormData();
            formData.append('action', 'reorder_sections');
            formData.append('order', JSON.stringify(order));

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showSaveIndicator();
                        // Optional: show reload prompt or just reload
                        showLoader();
                        setTimeout(() => window.location.reload(), 1000);
                    }
                });
        }

        function toggleSettings() {
            toggleTab('settings');
        }

        function closePanel() {
            toggleTab(null);
        }
        let currentImageField = null;
        let currentImageElement = null;

        // Auto-save functionality
        function saveField(field, value) {
            console.log(`Saving field ${field} with value:`, value);
            const formData = new FormData();
            formData.append('action', 'save');
            formData.append('field', field);
            formData.append('value', value);

            return fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    console.log('Save response:', data);
                    if (data.success) {
                        showSaveIndicator();
                        // Update UI if needed
                        const el = document.querySelector(`[data-field="${field}"]`);
                        if (el && el.tagName !== 'INPUT' && el.tagName !== 'TEXTAREA') {
                            // Update content while preserving the tooltip if it exists
                            const tooltip = el.querySelector('.edit-tooltip');
                            el.textContent = value;
                            if (tooltip) el.appendChild(tooltip);
                        }
                    }
                    return data;
                })
                .catch(err => {
                    console.error('Save error:', err);
                    showErrorModal('Failed to save changes: ' + err.message);
                });
        }

        function showSaveIndicator() {
            const indicator = document.getElementById('saveIndicator');
            indicator.classList.add('show');
            setTimeout(() => {
                indicator.classList.remove('show');
            }, 2000);
        }

        function showLoader() {
            document.getElementById('loaderOverlay').classList.add('show');
        }

        function hideLoader() {
            document.getElementById('loaderOverlay').classList.remove('show');
        }

        function openModal(field, element) {
            currentImageField = field;
            currentImageElement = element;
            openMediaSelector(updateImage);
        }

        function closeModal() {
            closeMediaSelector();
        }

        function updateImage(url) {
            if (!currentImageElement) {
                console.error('No image element selected');
                return;
            }

            if (currentImageField === 'hero_image' || currentImageElement.classList.contains('bg-cover')) {
                currentImageElement.style.backgroundImage = `url('${url}')`;
            } else {
                const img = currentImageElement.querySelector('img');
                if (img) {
                    img.src = url;
                }
            }
            saveField(currentImageField, url);

            // Reload after a short delay to see changes persist
            setTimeout(() => {
                window.location.reload();
            }, 500);
        }

        // Text editing
        document.querySelectorAll('.editable').forEach(element => {
            // Store original value
            let originalValue = '';

            element.addEventListener('click', function (e) {
                e.stopPropagation();
                if (this.getAttribute('contenteditable') === 'false') {
                    // Hide tooltip
                    const tooltip = this.querySelector('.edit-tooltip');
                    if (tooltip) {
                        tooltip.style.display = 'none';
                    }

                    // Store original text (excluding tooltip)
                    // Capture original value by cloning and removing the tooltip
                    const cloneForOrig = this.cloneNode(true);
                    const tooltipInOrig = cloneForOrig.querySelector('.edit-tooltip');
                    if (tooltipInOrig) tooltipInOrig.remove();
                    originalValue = cloneForOrig.innerText;

                    this.setAttribute('contenteditable', 'true');
                    this.classList.add('editing');
                    this.focus();

                    // Select all text (excluding tooltip)
                    const range = document.createRange();
                    const textNodes = Array.from(this.childNodes).filter(node =>
                        node.nodeType === Node.TEXT_NODE ||
                        (node.nodeType === Node.ELEMENT_NODE && !node.classList.contains('edit-tooltip'))
                    );

                    if (textNodes.length > 0) {
                        range.setStartBefore(textNodes[0]);
                        range.setEndAfter(textNodes[textNodes.length - 1]);
                        const sel = window.getSelection();
                        sel.removeAllRanges();
                        sel.addRange(range);
                    }
                }
            });

            element.addEventListener('blur', function () {
                if (this.getAttribute('contenteditable') === 'true') {
                    this.setAttribute('contenteditable', 'false');
                    this.classList.remove('editing');

                    // Show tooltip again
                    const tooltip = this.querySelector('.edit-tooltip');
                    if (tooltip) {
                        tooltip.style.display = '';
                    }

                    const field = this.getAttribute('data-field');
                    // Get only text content, excluding tooltip
                    // Get only text content by cloning and removing the tooltip
                    const clone = this.cloneNode(true);
                    const tooltipInClone = clone.querySelector('.edit-tooltip');
                    if (tooltipInClone) tooltipInClone.remove();
                    const value = clone.innerText; // Use innerText to get what is actually visible/typed

                    // Only save if value changed
                    if (value !== originalValue) {
                        saveField(field, value);
                        // Show loader and reload
                        showLoader();
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                }
            });

            element.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.blur();
                }
                if (e.key === 'Escape') {
                    // Restore original value
                    const tooltip = this.querySelector('.edit-tooltip');
                    this.textContent = originalValue;
                    if (tooltip) {
                        this.appendChild(tooltip);
                        tooltip.style.display = '';
                    }
                    this.blur();
                }
            });
        });

        // Image editing
        document.querySelectorAll('.editable-image').forEach(element => {
            element.addEventListener('click', function (e) {
                e.stopPropagation();
                const field = this.getAttribute('data-field');
                openModal(field, this);
            });
        });

        // File upload handling
        const dropZone = document.getElementById('dropZone');
        const imageInput = document.getElementById('imageInput');

        if (dropZone && imageInput) {
            dropZone.addEventListener('click', () => imageInput.click());

            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('border-blue-500');
            });

            dropZone.addEventListener('dragleave', () => {
                dropZone.classList.remove('border-blue-500');
            });

            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('border-blue-500');
                const file = e.dataTransfer.files[0];
                if (file && file.type.startsWith('image/')) {
                    uploadImage(file);
                }
            });

            imageInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    uploadImage(file);
                }
            });
        }

        function showErrorModal(message) {
            showNotification(message, 'error');
        }

        function closeErrorModal() {
            document.getElementById('errorModal').classList.remove('show');
        }

        function uploadImage(file) {
            closeModal();
            showLoader();

            console.log('Starting upload for file:', file.name, 'Size:', file.size, 'Type:', file.type);

            const formData = new FormData();
            formData.append('image', file);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);

                    // Try to get response text first to see what we're receiving
                    return response.text().then(text => {
                        console.log('Raw response:', text);

                        if (!response.ok) {
                            throw new Error('HTTP error! status: ' + response.status);
                        }

                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('JSON parse error:', e);
                            console.error('Response text:', text);
                            throw new Error('Invalid JSON response from server');
                        }
                    });
                })
                .then(data => {
                    console.log('Parsed data:', data);
                    if (data.success) {
                        updateImage(data.url);
                    } else {
                        hideLoader();
                        showErrorModal(data.error || 'Upload failed. Please try again.');
                    }
                })
                .catch(error => {
                    hideLoader();
                    console.error('Upload error:', error);
                    showErrorModal('Upload failed: ' + error.message);
                });
        }

        function performSearch() {
            const pickupDate = document.getElementById('pickup_date').value;
            const pickupTime = document.getElementById('pickup_time').value;
            const dropoffDate = document.getElementById('dropoff_date').value;
            const dropoffTime = document.getElementById('dropoff_time').value;

            showLoader();
            setTimeout(() => {
                window.location.href = `/templates/fleet.php?pickup=${encodeURIComponent(pickupDate)}&dropoff=${encodeURIComponent(dropoffDate)}&pickup_time=${encodeURIComponent(pickupTime)}&dropoff_time=${encodeURIComponent(dropoffTime)}&tenant=<?= $tenant['subdomain']?>`;
            }, 500);
        }

        function toggleSection(section, hide) {
            const field = section + '_hidden';
            const value = hide ? 1 : 0;
            saveField(field, value);

            // Immediately hide/show on preview
            const el = document.querySelector(`[data-section-id="${section}"]`);
            if (el) {
                if (hide) el.classList.add('hidden');
                else el.classList.remove('hidden');
            }

            // Sync with ALL checkboxes for this section
            document.querySelectorAll(`input[onchange*="${section}"]`).forEach(cb => {
                cb.checked = !hide;
            });

            // Visually dim the management row
            const mgmtRow = document.getElementById(`mgmt_row_${section}`);
            if (mgmtRow) {
                if (hide) {
                    mgmtRow.classList.add('opacity-50', 'grayscale', 'bg-gray-100');
                } else {
                    mgmtRow.classList.remove('opacity-50', 'grayscale', 'bg-gray-100');
                }
            }

            showSaveIndicator();
        }

        // Prevent default link behavior
        document.querySelectorAll('a[href="#"]').forEach(link => {
            link.addEventListener('click', e => e.preventDefault());
        });

        // Close modal on overlay click
        const uploadModal = document.getElementById('uploadModal');
        if (uploadModal) {
            uploadModal.addEventListener('click', function (e) {
                if (e.target === this) {
                    closeModal();
                }
            });
        }

        const errorModal = document.getElementById('errorModal');
        if (errorModal) {
            errorModal.addEventListener('click', function (e) {
                if (e.target === this) {
                    closeErrorModal();
                }
            });
        }

        // Settings Panel Functions
        // Close settings panel when clicking outside
        document.addEventListener('click', function (e) {
            const panel = document.getElementById('settingsPanel');
            const toolbar = document.querySelector('.builder-toolbar');
            const sectionsBtn = document.querySelector('[onclick="toggleTab(\'sections\')"]');
            const settingsBtn = document.querySelector('[onclick="toggleTab(\'settings\')"]');

            if (panel.classList.contains('show') &&
                !panel.contains(e.target) &&
                (sectionsBtn && !sectionsBtn.contains(e.target)) &&
                (settingsBtn && !settingsBtn.contains(e.target))) {
                panel.classList.remove('show');
                toolbar.classList.remove('hide-for-settings');
            }
        });

        function updateFont(fontFamily) {
            document.body.style.fontFamily = `'${fontFamily}', sans-serif`;
            saveField('font_family', fontFamily);
        }

        function updateColor(type, color) {
            const textInput = document.getElementById(`${type}ColorText`);
            textInput.value = color;
            applyColor(type, color);
        }

        function updateColorFromText(type, color) {
            const colorInput = document.getElementById(`${type}Color`);
            colorInput.value = color;
            applyColor(type, color);
        }

        function applyColor(type, color) {
            const root = document.documentElement;

            switch (type) {
                case 'header':
                    document.querySelector('header').style.backgroundColor = color;
                    saveField('header_color', color);
                    break;
                case 'primary':
                    // Update all primary color elements
                    document.querySelectorAll('.bg-blue-600, .text-blue-600').forEach(el => {
                        if (el.classList.contains('bg-blue-600')) {
                            el.style.backgroundColor = color;
                        }
                        if (el.classList.contains('text-blue-600')) {
                            el.style.color = color;
                        }
                    });
                    saveField('primary_color', color);
                    break;
                case 'secondary':
                    document.querySelectorAll('.bg-blue-700').forEach(el => {
                        el.style.backgroundColor = color;
                    });
                    saveField('secondary_color', color);
                    break;
                case 'text':
                    document.querySelectorAll('.text-gray-900, .text-gray-800').forEach(el => {
                        el.style.color = color;
                    });
                    saveField('text_color', color);
                    break;
                case 'background':
                    document.body.style.backgroundColor = color;
                    saveField('background_color', color);
                    break;
            }
        }

        function updateStars(reviewId, stars) {
            const container = document.getElementById(`${reviewId}-stars`);
            container.innerHTML = '';
            for (let i = 1; i <= 5; i++) {
                const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                svg.setAttribute('class', `w-5 h-5 fill-current${i <= stars ? '' : ' opacity-30'}`);
                svg.setAttribute('viewBox', '0 0 20 20');
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', 'M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z');
                svg.appendChild(path);
                container.appendChild(svg);
            }
        }

        function resetSettings() {
            showConfirmation('Reset Settings', 'Are you sure you want to reset all settings to default? This action cannot be undone.', function () {
                // Reset font
                document.getElementById('fontFamily').value = 'Inter';
                updateFont('Inter');

                // Reset colors
                document.getElementById('headerColor').value = '#ffffff';
                document.getElementById('headerColorText').value = '#ffffff';
                document.getElementById('primaryColor').value = '#3b82f6';
                document.getElementById('primaryColorText').value = '#3b82f6';
                document.getElementById('secondaryColor').value = '#1e40af';
                document.getElementById('secondaryColorText').value = '#1e40af';
                document.getElementById('textColor').value = '#111827';
                document.getElementById('textColorText').value = '#111827';
                document.getElementById('backgroundColor').value = '#ffffff';
                document.getElementById('backgroundColorText').value = '#ffffff';

                applyColor('header', '#ffffff');
                applyColor('primary', '#3b82f6');
                applyColor('secondary', '#1e40af');
                applyColor('text', '#111827');
                applyColor('background', '#ffffff');

                showSaveIndicator();
            }, 'Reset', 'bg-red-600 hover:bg-red-700');
        }

        // Load saved settings on page load from database
        window.addEventListener('load', function () {
            // Apply font from database
            const savedFont = '<?= htmlspecialchars($content['font_family'] ?? 'Inter')?>';
            if (savedFont) {
                document.getElementById('fontFamily').value = savedFont;
                document.body.style.fontFamily = `'${savedFont}', sans-serif`;
            }

            // Apply colors from database
            const headerColor = '<?= htmlspecialchars($content['header_color'] ?? '#ffffff')?>';
            document.getElementById('headerColor').value = headerColor;
            document.getElementById('headerColorText').value = headerColor;
            if (document.querySelector('header')) {
                document.querySelector('header').style.backgroundColor = headerColor;
            }

            const primaryColor = '<?= htmlspecialchars($content['primary_color'] ?? '#3b82f6')?>';
            document.getElementById('primaryColor').value = primaryColor;
            document.getElementById('primaryColorText').value = primaryColor;
            document.querySelectorAll('.bg-blue-600, .text-blue-600').forEach(el => {
                if (el.classList.contains('bg-blue-600')) {
                    el.style.backgroundColor = primaryColor;
                }
                if (el.classList.contains('text-blue-600')) {
                    el.style.color = primaryColor;
                }
            });

            const secondaryColor = '<?= htmlspecialchars($content['secondary_color'] ?? '#1e40af')?>';
            document.getElementById('secondaryColor').value = secondaryColor;
            document.getElementById('secondaryColorText').value = secondaryColor;
            document.querySelectorAll('.bg-blue-700').forEach(el => {
                el.style.backgroundColor = secondaryColor;
            });

            const textColor = '<?= htmlspecialchars($content['text_color'] ?? '#111827')?>';
            document.getElementById('textColor').value = textColor;
            document.getElementById('textColorText').value = textColor;
            document.querySelectorAll('.text-gray-900, .text-gray-800').forEach(el => {
                el.style.color = textColor;
            });

            const backgroundColor = '<?= htmlspecialchars($content['background_color'] ?? '#ffffff')?>';
            document.getElementById('backgroundColor').value = backgroundColor;
            document.getElementById('backgroundColorText').value = backgroundColor;
            document.body.style.backgroundColor = backgroundColor;

            // Initialize Flatpickr for search bar
            // Initialize Flatpickr for unified date range
            flatpickr("#date_range", {
                mode: "range",
                dateFormat: "D d M",
                minDate: "today",
                defaultDate: ["today", new Date(Date.now() + 86400000)],
                onChange: function (selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        document.getElementById('pickup_display').textContent = instance.formatDate(selectedDates[0], "D d M");
                        document.getElementById('dropoff_display').textContent = instance.formatDate(selectedDates[1], "D d M");
                    }
                },
                onReady: function (selectedDates, dateStr, instance) {
                    if (selectedDates.length === 1) {
                        document.getElementById('pickup_display').textContent = instance.formatDate(selectedDates[0], "D d M");
                    }
                    if (selectedDates.length === 2) {
                        document.getElementById('pickup_display').textContent = instance.formatDate(selectedDates[0], "D d M");
                        document.getElementById('dropoff_display').textContent = instance.formatDate(selectedDates[1], "D d M");
                    }
                }
            });

            // Populate custom time dropdowns
            const times = [];
            for (let h = 0; h < 24; h++) {
                for (let m = 0; m < 60; m += 30) {
                    const hour = h % 12 || 12;
                    const ampm = h < 12 ? 'AM' : 'PM';
                    const minute = m === 0 ? '00' : m;
                    times.push(`${hour}:${minute} ${ampm}`);
                }
            }

            document.querySelectorAll('.time-dropdown-container').forEach(container => {
                const list = container.querySelector('.time-options-list');
                const display = container.querySelector('span[id$="_display"]');
                const hiddenInput = container.querySelector('input[type="hidden"]');

                times.forEach(t => {
                    const div = document.createElement('div');
                    div.className = 'px-4 py-3 hover:bg-blue-50 cursor-pointer text-sm font-medium text-gray-700 transition-colors border-b border-gray-50 last:border-0';
                    div.textContent = t;
                    div.onclick = (e) => {
                        e.stopPropagation();
                        display.textContent = t;
                        hiddenInput.value = t;
                        list.classList.add('hidden');
                    };
                    list.appendChild(div);
                });

                container.onclick = (e) => {
                    e.stopPropagation();
                    // Close other open dropdowns
                    document.querySelectorAll('.time-options-list').forEach(l => {
                        if (l !== list) l.classList.add('hidden');
                    });
                    list.classList.toggle('hidden');
                };
            });

            // Close dropdowns when clicking outside
            document.addEventListener('click', () => {
                document.querySelectorAll('.time-options-list').forEach(l => l.classList.add('hidden'));
            });
        });

        // Helper to sync side panel settings with the visual preview
        function syncPreview(field, value) {
            const previewEl = document.querySelector(`[data-field="${field}"]`);
            if (previewEl) {
                const tooltip = previewEl.querySelector('.edit-tooltip');
                previewEl.textContent = value;
                if (tooltip) previewEl.appendChild(tooltip);
            }
        }

        // Connect visual editor clicks to side panel
        document.querySelectorAll('.editable').forEach(el => {
            el.addEventListener('click', (e) => {
                const field = el.getAttribute('data-field');
                const sidebarInput = document.querySelector(`[data-sync="${field}"]`);
                if (sidebarInput) {
                    toggleTab('settings');
                    setTimeout(() => {
                        sidebarInput.focus();
                        sidebarInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 100);
                }
            });
        });

        // Drag and Drop functionality for vehicles
        let draggedElement = null;

        document.addEventListener('DOMContentLoaded', function () {
            const vehiclesList = document.getElementById('vehiclesList');
            if (!vehiclesList) return;

            const vehicleCards = vehiclesList.querySelectorAll('.vehicle-card');

            vehicleCards.forEach(card => {
                card.addEventListener('dragstart', handleDragStart);
                card.addEventListener('dragover', handleDragOver);
                card.addEventListener('drop', handleDrop);
                card.addEventListener('dragend', handleDragEnd);
            });

            function handleDragStart(e) {
                draggedElement = this;
                this.style.opacity = '0.4';
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/html', this.innerHTML);
            }

            function handleDragOver(e) {
                if (e.preventDefault) {
                    e.preventDefault();
                }
                e.dataTransfer.dropEffect = 'move';

                if (draggedElement !== this) {
                    this.style.borderColor = '#3b82f6';
                    this.style.borderWidth = '3px';
                }
                return false;
            }

            function handleDrop(e) {
                if (e.stopPropagation) {
                    e.stopPropagation();
                }

                if (draggedElement !== this) {
                    const allCards = Array.from(vehiclesList.querySelectorAll('.vehicle-card'));
                    const draggedIndex = allCards.indexOf(draggedElement);
                    const targetIndex = allCards.indexOf(this);

                    if (draggedIndex < targetIndex) {
                        this.parentNode.insertBefore(draggedElement, this.nextSibling);
                    } else {
                        this.parentNode.insertBefore(draggedElement, this);
                    }

                    saveVehicleOrder();
                }

                this.style.borderColor = '';
                this.style.borderWidth = '';
                return false;
            }

            function handleDragEnd(e) {
                this.style.opacity = '1';

                vehicleCards.forEach(card => {
                    card.style.borderColor = '';
                    card.style.borderWidth = '';
                });
            }

            function saveVehicleOrder() {
                const cards = vehiclesList.querySelectorAll('.vehicle-card');
                const order = Array.from(cards).map(card => card.dataset.vehicleId);

                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=reorder_vehicles&order=${JSON.stringify(order)}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showSaveIndicator();
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        });
    </script>

    <?php include __DIR__ . '/../includes/confirmation-modal.php'; ?>

    <?php include __DIR__ . '/../includes/onboarding-widget.php'; ?>
</body>

</html>