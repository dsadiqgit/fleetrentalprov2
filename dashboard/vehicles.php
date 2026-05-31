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

// Add featured column if it doesn't exist
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM vehicles LIKE 'featured'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE vehicles ADD COLUMN featured TINYINT(1) DEFAULT 0");
    }
}
catch (Exception $e) {
// Ignore error if column already exists
}

// Add contract_template_id column if it doesn't exist
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM vehicles LIKE 'contract_template_id'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE vehicles ADD COLUMN contract_template_id INT NULL");
    }
}
catch (Exception $e) {
// Ignore error if column already exists
}

// Add daily_pricing column if it doesn't exist
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM vehicles LIKE 'daily_pricing'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE vehicles ADD COLUMN daily_pricing TEXT NULL");
    }
}
catch (Exception $e) {
// Ignore error if column already exists
}

// Add pricing_packages column if it doesn't exist
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM vehicles LIKE 'pricing_packages'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE vehicles ADD COLUMN pricing_packages TEXT NULL");
    }
}
catch (Exception $e) {
// Ignore error if column already exists
}

// Add unavailable_dates column if it doesn't exist
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM vehicles LIKE 'unavailable_dates'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE vehicles ADD COLUMN unavailable_dates TEXT NULL");
    }
}
catch (Exception $e) {
// Ignore error if column already exists
}

// More car details columns
$new_details = [
    'doors' => 'INT DEFAULT 5',
    'bags' => 'INT DEFAULT 1',
    'exterior_color' => "VARCHAR(50) DEFAULT 'Blue'",
    'interior_color' => "VARCHAR(50) DEFAULT 'Brown'",
    'engine_capacity' => "VARCHAR(50) DEFAULT '1.6'",
    'air_conditioning' => 'TINYINT(1) DEFAULT 1',
    'gps' => 'TINYINT(1) DEFAULT 1',
    'description' => 'TEXT',
    'license_plate' => "VARCHAR(50) DEFAULT ''",
    'vehicle_features' => 'TEXT',
    'min_days' => 'INT DEFAULT 1',
    'min_license_years' => 'INT DEFAULT 1',
    'mileage_limit' => 'INT DEFAULT 300',
    'unlimited_mileage' => 'TINYINT(1) DEFAULT 0',
    'deposit_type' => "VARCHAR(50) DEFAULT 'collection'",
    'require_deposit' => 'TINYINT(1) DEFAULT 0'
];

foreach ($new_details as $col => $def) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM vehicles LIKE '$col'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE vehicles ADD COLUMN $col $def");
        }
    }
    catch (Exception $e) {
    }
}

// Get tenant information
$stmt = $pdo->prepare("SELECT * FROM tenants WHERE id = ?");
$stmt->execute([$_SESSION['tenant_id']]);
$tenant = $stmt->fetch();

if (!$tenant) {
    die('Error: Tenant not found.');
}

// Get tenant settings
$stmt = $pdo->prepare("SELECT * FROM tenant_settings WHERE tenant_id = ?");
$stmt->execute([$_SESSION['tenant_id']]);
$tenant_settings = $stmt->fetch();
$distance_unit = $tenant_settings['distance_unit'] ?? 'Miles';
$distance_unit_lower = strtolower($distance_unit);

// Calculate trial days remaining
$trial_days_remaining = 0;
$trial_percentage = 0;

// Use trial_ends_at if set, otherwise calculate 30 days from created_at
$trial_start = new DateTime($tenant['created_at']);
$trial_end = $tenant['trial_ends_at'] ? new DateTime($tenant['trial_ends_at']) : (clone $trial_start)->modify('+30 days');
$now = new DateTime();

if ($now < $trial_end) {
    $interval = $now->diff($trial_end);
    $trial_days_remaining = $interval->days;
    // Cap at 30 days
    if ($trial_days_remaining > 30)
        $trial_days_remaining = 30;
    $trial_percentage = ($trial_days_remaining / 30) * 100;
}

// Handle form submission
$error = '';
$success = '';
$formPost = $_POST ?? [];
$current_tab = sanitize($_POST['current_tab'] ?? $_GET['tab'] ?? 'basic');
$current_pricing_tab = sanitize($_POST['current_pricing_tab'] ?? $_GET['pricing_tab'] ?? 'daily');
$price_validation_error = false;
$make_error = false;
$model_error = false;

function field_raw($name, $default = '') {
    global $formPost, $show_edit_form, $edit_vehicle;
    if (isset($formPost[$name]) && $formPost[$name] !== '') {
        return $formPost[$name];
    }
    if ($show_edit_form && isset($edit_vehicle[$name])) {
        return $edit_vehicle[$name];
    }
    return $default;
}

function field_value($name, $default = '') {
    return htmlspecialchars(field_raw($name, $default));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $brand = sanitize($_POST['make'] ?? '');
    $model = sanitize($_POST['model'] ?? '');
    $year = intval($_POST['year'] ?? date('Y'));
    $category = sanitize($_POST['type'] ?? 'sedan');
    $transmission = sanitize($_POST['transmission'] ?? 'automatic');
    $fuel_type = sanitize($_POST['fuel_type'] ?? 'petrol');
    $seats = intval($_POST['seats'] ?? 5);
    $price_per_day = floatval($_POST['price_per_day'] ?? 0);
    $deposit = floatval($_POST['deposit'] ?? 0);
    $require_deposit = isset($_POST['require_deposit']) ? 1 : 0;
    $deposit_type = sanitize($_POST['deposit_type'] ?? 'collection');

    $featured = isset($_POST['featured']) ? 1 : 0;
    $contract_template_id = !empty($_POST['contract_template_id']) ? intval($_POST['contract_template_id']) : null;

    // Additional Details capture
    $doors = intval($_POST['doors'] ?? 5);
    $bags = intval($_POST['bags'] ?? 1);
    $exterior_color = sanitize($_POST['exterior_color'] ?? '');
    $interior_color = sanitize($_POST['interior_color'] ?? '');
    $engine_capacity = sanitize($_POST['engine_capacity'] ?? '');
    $air_conditioning = isset($_POST['air_conditioning']) ? 1 : 0;
    $gps = isset($_POST['gps']) ? 1 : 0;
    $description = $_POST['description'] ?? ''; // Allow some HTML or just text if preferred, but keep it safe for DB
    $license_plate = sanitize($_POST['license_plate'] ?? '');
    $vehicle_features = $_POST['vehicle_features'] ?? '[]';
    $min_days = intval($_POST['min_days'] ?? 1);
    $min_age = intval($_POST['min_age'] ?? 25);
    $min_license_years = intval($_POST['min_license_years'] ?? 1);
    $mileage_limit = intval($_POST['mileage_limit'] ?? 300);
    $unlimited_mileage = isset($_POST['unlimited_mileage']) ? 1 : 0;

    $current_tab = sanitize($_POST['current_tab'] ?? 'basic');
    $current_pricing_tab = sanitize($_POST['current_pricing_tab'] ?? 'daily');

    // Handle multiple image uploads
    $image_paths = [];

    // Get existing images from the form (user may have deleted some)
    if (isset($_POST['existing_images']) && is_array($_POST['existing_images'])) {
        $image_paths = array_filter($_POST['existing_images'], function ($img) {
            return !empty($img);
        });
    }

    if (isset($_FILES['vehicle_image']) && !empty($_FILES['vehicle_image']['name'][0])) {
        $upload_dir = __DIR__ . '/../uploads/vehicles/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

        // Loop through all uploaded files
        $file_count = count($_FILES['vehicle_image']['name']);
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['vehicle_image']['error'][$i] === UPLOAD_ERR_OK) {
                $file_type = $_FILES['vehicle_image']['type'][$i];
                $file_size = $_FILES['vehicle_image']['size'][$i];
                $file_name = $_FILES['vehicle_image']['name'][$i];
                $tmp_name = $_FILES['vehicle_image']['tmp_name'][$i];

                if (in_array($file_type, $allowed_types) && $file_size <= 5 * 1024 * 1024) {
                    $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $filename = uniqid() . '_' . time() . '_' . $i . '.' . $extension;
                    $filepath = $upload_dir . $filename;

                    if (move_uploaded_file($tmp_name, $filepath)) {
                        $image_paths[] = '/uploads/vehicles/' . $filename;
                    }
                }
            }
        }
    }

    // Store images as JSON array
    $image = !empty($image_paths) ? json_encode($image_paths) : '';

    // Handle daily pricing
    $daily_pricing = [];
    $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
    foreach ($days as $day) {
        if (!empty($_POST['price_' . $day])) {
            $daily_pricing[$day] = floatval($_POST['price_' . $day]);
        }
    }
    $daily_pricing_json = !empty($daily_pricing) ? json_encode($daily_pricing) : null;

    // Check if it was globally sanitized by config.php/security-helper.php and decode if so
    if ($daily_pricing_json && strpos($daily_pricing_json, '&quot;') !== false) {
        $daily_pricing_json = htmlspecialchars_decode($daily_pricing_json, ENT_QUOTES | ENT_HTML5);
    }

    $unavailable_dates = isset($_POST['unavailable_dates']) ? $_POST['unavailable_dates'] : null;

    // Pricing Packages
    $pricing_packages_json = $_POST['pricing_packages_json'] ?? '[]';
    // Decode because of global sanitization in config.php
    $pricing_packages_json = htmlspecialchars_decode($pricing_packages_json, ENT_QUOTES | ENT_HTML5);
    if (empty($pricing_packages_json))
        $pricing_packages_json = '[]';

    if (empty($brand) || empty($model) || $price_per_day <= 0) {
        $price_validation_error = $price_per_day <= 0;
        $make_error = empty($brand);
        $model_error = empty($model);
        $error = 'Please fill in brand, model, and price per day';
        
        // Dynamically override current_tab so Alpine loads on the correct tab!
        if ($make_error || $model_error) {
            $current_tab = 'basic';
        } else if ($price_validation_error) {
            $current_tab = 'pricing';
        }
    }
    else {
        try {
            $vehicle_name = $brand . ' ' . $model;

            if ($_POST['action'] === 'edit_vehicle' && isset($_POST['vehicle_id'])) {
                // Update existing vehicle
                $vehicle_id = intval($_POST['vehicle_id']);

                $stmt = $pdo->prepare("UPDATE vehicles SET name = ?, brand = ?, model = ?, year = ?, category = ?, transmission = ?, fuel_type = ?, seats = ?, price_per_day = ?, deposit = ?, images = ?, featured = ?, contract_template_id = ?, daily_pricing = ?, pricing_packages = ?, unavailable_dates = ?, doors = ?, bags = ?, exterior_color = ?, interior_color = ?, engine_capacity = ?, air_conditioning = ?, gps = ?, description = ?, license_plate = ?, vehicle_features = ?, min_days = ?, min_age = ?, min_license_years = ?, mileage_limit = ?, unlimited_mileage = ?, require_deposit = ?, deposit_type = ? WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$vehicle_name, $brand, $model, $year, $category, $transmission, $fuel_type, $seats, $price_per_day, $deposit, $image, $featured, $contract_template_id, $daily_pricing_json, $pricing_packages_json, $unavailable_dates, $doors, $bags, $exterior_color, $interior_color, $engine_capacity, $air_conditioning, $gps, $description, $license_plate, $vehicle_features, $min_days, $min_age, $min_license_years, $mileage_limit, $unlimited_mileage, $require_deposit, $deposit_type, $vehicle_id, $_SESSION['tenant_id']]);
                $success = 'Vehicle updated successfully!';
            }
            else {
                // Insert new vehicle
                $stmt = $pdo->prepare("INSERT INTO vehicles (tenant_id, name, brand, model, year, category, transmission, fuel_type, seats, price_per_day, deposit, images, featured, contract_template_id, daily_pricing, pricing_packages, unavailable_dates, doors, bags, exterior_color, interior_color, engine_capacity, air_conditioning, gps, description, license_plate, vehicle_features, min_days, min_age, min_license_years, mileage_limit, unlimited_mileage, require_deposit, deposit_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['tenant_id'], $vehicle_name, $brand, $model, $year, $category, $transmission, $fuel_type, $seats, $price_per_day, $deposit, $image, $featured, $contract_template_id, $daily_pricing_json, $pricing_packages_json, $unavailable_dates, $doors, $bags, $exterior_color, $interior_color, $engine_capacity, $air_conditioning, $gps, $description, $license_plate, $vehicle_features, $min_days, $min_age, $min_license_years, $mileage_limit, $unlimited_mileage, $require_deposit, $deposit_type]);
                $success = 'Vehicle added successfully!';
            }

            if ($_POST['action'] === 'edit_vehicle' && isset($_POST['vehicle_id'])) {
                $target_vid = $vehicle_id;
            } else {
                $target_vid = $pdo->lastInsertId();
            }

            // Sync blocked dates with user tracking
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS vehicle_blocked_dates (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    tenant_id INT NOT NULL,
                    vehicle_id INT NOT NULL,
                    blocked_date DATE NOT NULL,
                    blocked_by VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY (vehicle_id, blocked_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } catch (Exception $e) {}

            $unavailable_dates_str = $unavailable_dates ?? '';
            $user_name = trim($_SESSION['full_name'] ?? 'Admin');
            
            $new_dates = [];
            if (!empty($unavailable_dates_str)) {
                $parts = explode(',', $unavailable_dates_str);
                foreach ($parts as $p) {
                    $d = trim($p);
                    if ($d) $new_dates[] = $d;
                }
            }
            
            if (empty($new_dates)) {
                $stmt = $pdo->prepare("DELETE FROM vehicle_blocked_dates WHERE vehicle_id = ?");
                $stmt->execute([$target_vid]);
            } else {
                $placeholders = implode(',', array_fill(0, count($new_dates), '?'));
                $params = array_merge([$target_vid], $new_dates);
                $stmt = $pdo->prepare("DELETE FROM vehicle_blocked_dates WHERE vehicle_id = ? AND blocked_date NOT IN ($placeholders)");
                $stmt->execute($params);
                
                $stmt = $pdo->prepare("INSERT IGNORE INTO vehicle_blocked_dates (tenant_id, vehicle_id, blocked_date, blocked_by) VALUES (?, ?, ?, ?)");
                foreach ($new_dates as $d) {
                    $stmt->execute([$_SESSION['tenant_id'], $target_vid, $d, $user_name]);
                }
            }

            if ($_POST['action'] === 'add_vehicle') {
                header('Location: /dashboard/vehicles.php?created_success=1');
            } else {
                header('Location: /dashboard/vehicles.php?action=edit&id=' . $target_vid . '&success=1&tab=' . $current_tab . '&pricing_tab=' . $current_pricing_tab);
            }
            exit;
        }
        catch (Exception $e) {
            $error = 'Failed to save vehicle: ' . $e->getMessage();
        }
    }
}

// Handle toggle availability
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $vehicle_id = intval($_GET['toggle']);
    $stmt = $pdo->prepare("UPDATE vehicles SET availability = NOT availability WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$vehicle_id, $_SESSION['tenant_id']]);
    header('Location: /dashboard/vehicles.php');
    exit;
}

// Handle toggle featured
if (isset($_GET['toggle_featured']) && is_numeric($_GET['toggle_featured'])) {
    $vehicle_id = intval($_GET['toggle_featured']);
    $featured = isset($_GET['featured']) && $_GET['featured'] === 'true' ? 1 : 0;
    $stmt = $pdo->prepare("UPDATE vehicles SET featured = ? WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$featured, $vehicle_id, $_SESSION['tenant_id']]);
    header('Location: /dashboard/vehicles.php');
    exit;
}

// Handle delete vehicle
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $vehicle_id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$vehicle_id, $_SESSION['tenant_id']]);
    header('Location: /dashboard/vehicles.php');
    exit;
}

$vehicle_search = trim($_GET['vehicle_search'] ?? '');

// Get all vehicles for this tenant
$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE tenant_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['tenant_id']]);
$vehicles = $stmt->fetchAll();

// Get all contract templates for this tenant
$stmt = $pdo->prepare("SELECT id, name FROM contract_templates WHERE tenant_id = ? ORDER BY name ASC");
$stmt->execute([$_SESSION['tenant_id']]);
$contract_templates = $stmt->fetchAll();

// Helpers for schedule view
if (!function_exists('minutes_from_time')) {
    function minutes_from_time(?string $time): ?int
    {
        if (!$time) {
            return null;
        }
        [$hour, $minute] = array_pad(explode(':', $time), 2, '00');
        return (int)$hour * 60 + (int)$minute;
    }
}

$selected_schedule_date = $_GET['schedule_date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_schedule_date)) {
    $selected_schedule_date = date('Y-m-d');
}
$prevScheduleDate = date('Y-m-d', strtotime($selected_schedule_date . ' -1 day'));
$nextScheduleDate = date('Y-m-d', strtotime($selected_schedule_date . ' +1 day'));

$timelineStartHour = 9;
$timelineEndHour = 17;
$timelineStartMinutes = $timelineStartHour * 60;
$timelineEndMinutes = $timelineEndHour * 60;
$totalTimelineMinutes = max(60, $timelineEndMinutes - $timelineStartMinutes);
$hourColumns = max(1, $timelineEndHour - $timelineStartHour);

$assignmentStatusColors = [
    'pending' => 'bg-amber-100 text-amber-900 border-amber-200',
    'confirmed' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
    'active' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
    'completed' => 'bg-gray-100 text-gray-700 border-gray-200',
];

$assignmentStmt = $pdo->prepare("SELECT b.*, v.name AS vehicle_name, v.brand, v.model, v.category, v.images, v.license_plate
    FROM bookings b
    LEFT JOIN vehicles v ON b.vehicle_id = v.id
    WHERE b.tenant_id = ? AND b.status != 'cancelled' AND b.pickup_date <= ? AND b.return_date >= ?");
$assignmentStmt->execute([$_SESSION['tenant_id'], $selected_schedule_date, $selected_schedule_date]);
$vehicleAssignments = $assignmentStmt->fetchAll(PDO::FETCH_ASSOC);

$assignmentsByVehicle = [];
foreach ($vehicleAssignments as $assignment) {
    if (!isset($assignmentsByVehicle[$assignment['vehicle_id']])) {
        $assignmentsByVehicle[$assignment['vehicle_id']] = [];
    }
    $assignmentsByVehicle[$assignment['vehicle_id']][] = $assignment;
}

$vehicleAvatarPalette = [
    'bg-rose-100 text-rose-700',
    'bg-sky-100 text-sky-600',
    'bg-amber-100 text-amber-700',
    'bg-emerald-100 text-emerald-700',
    'bg-indigo-100 text-indigo-700',
    'bg-purple-100 text-purple-700',
    'bg-cyan-100 text-cyan-700',
    'bg-lime-100 text-lime-700',
];

if (!empty($vehicle_search)) {
    $filteredVehicles = array_values(array_filter($vehicles, function ($vehicle) use ($vehicle_search) {
        $haystack = strtolower(
            ($vehicle['brand'] ?? '') . ' ' .
            ($vehicle['model'] ?? '') . ' ' .
            ($vehicle['license_plate'] ?? '') . ' ' .
            ($vehicle['category'] ?? '')
        );
        return strpos($haystack, strtolower($vehicle_search)) !== false;
    }));
} else {
    $filteredVehicles = $vehicles;
}

$filteredVehicleCount = count($filteredVehicles);

// Check if we're in add mode or edit mode
$show_add_form = isset($_GET['action']) && $_GET['action'] === 'add';
$show_edit_form = isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']);
$edit_vehicle = null;

if ($show_edit_form) {
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$_GET['id'], $_SESSION['tenant_id']]);
    $edit_vehicle = $stmt->fetch();
    if (!$edit_vehicle) {
        $show_edit_form = false;
    }
    else {
        // Fetch booked dates for the availability calendar
        $stmt = $pdo->prepare("SELECT pickup_date, return_date FROM bookings WHERE vehicle_id = ? AND status NOT IN ('cancelled', 'completed')");
        $stmt->execute([$_GET['id']]);
        $booked_dates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicles - <?= htmlspecialchars($tenant['name'])?></title>
    <!-- Add Flatpickr CSS/JS natively if missing here ideally, or at bottom -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="/app/custom.css">
    <script src="/app/custom-select.js" defer></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
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
        .toggle-checkbox:checked {
            background-color: #10b981;
            border-color: #10b981;
        }
        .toggle-checkbox:checked + .toggle-label {
            transform: translateX(1.25rem);
        }
    </style>
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <!-- Mobile Header -->
    <header class="lg:hidden fixed top-0 left-0 right-0 bg-white border-b border-gray-200 px-4 py-3 z-40 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <button id="mobile-menu-btn" class="p-1 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <h1 class="text-lg font-semibold text-gray-900">Dashboard</h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="/dashboard/vehicles.php?action=add" class="px-3 py-1.5 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-gray-800 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Vehicle
            </a>
            <button class="p-1 hover:bg-gray-100 rounded-lg transition-colors relative">
                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
            </button>
            <button class="w-8 h-8 rounded-full bg-black text-white flex items-center justify-center text-xs font-semibold">
                FL
            </button>
        </div>
    </header>

    <!-- Sidebar Overlay -->
    <div id="sidebar-overlay" class="lg:hidden fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-30 hidden transition-all duration-300"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed lg:static top-14 lg:top-0 bottom-0 left-0 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out z-40 lg:flex">
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
                        <span class="text-gray-900">Vehicles</span>
                        <?php if ($show_add_form): ?>
                        <span class="mx-2">/</span>
                        <span class="text-gray-900">Add Vehicle</span>
                        <?php
elseif ($show_edit_form): ?>
                        <span class="mx-2">/</span>
                        <span class="text-gray-900">Edit Vehicle</span>
                        <?php
endif; ?>
                    </nav>
                    <h1 class="text-2xl font-bold text-gray-900">
                        <?php if ($show_add_form): ?>
                        Add New Vehicle
                        <?php
elseif ($show_edit_form): ?>
                        Edit Vehicle
                        <?php
else: ?>
                        Vehicles
                        <?php
endif; ?>
                    </h1>
                    <p class="text-sm text-gray-600 mt-1">
                        <?php if ($show_add_form || $show_edit_form): ?>
                        Fill in the details below to <?= $show_add_form ? 'add a new' : 'update this'?> vehicle
                        <?php
else: ?>
                        Manage your fleet of vehicles
                        <?php
endif; ?>
                    </p>
                </div>
                <?php if (!$show_add_form && !$show_edit_form): ?>
                <a href="/dashboard/vehicles.php?action=add" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Vehicle
                </a>
                <?php
endif; ?>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto bg-gray-50 p-4 sm:p-6 lg:p-8">
            <?php if (!$show_add_form && !$show_edit_form): ?>
            <!-- Vehicle Schedule View -->
            <div class="space-y-6">
                <!-- Schedule Header -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="relative">
                            <input type="text" name="vehicle_search" value="<?= htmlspecialchars($vehicle_search)?>" placeholder="Search vehicles" class="pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" onkeydown="if(event.key==='Enter'){ window.location='/dashboard/vehicles.php?vehicle_search='+encodeURIComponent(this.value); }">
                            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"></path>
                            </svg>
                        </div>
                        <button class="flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 hover:border-gray-300">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L15 12.414V19a1 1 0 01-1.447.894l-4-2A1 1 0 019 17v-4.586L3.293 6.707A1 1 0 013 6V4z"></path>
                            </svg>
                            Filters
                        </button>
                        <div class="flex items-center gap-3 bg-white border border-gray-200 rounded-lg px-4 py-2">
                            <button onclick="window.location='/dashboard/vehicles.php?schedule_date=<?= $prevScheduleDate?>'" class="p-1 text-gray-500 hover:text-gray-900">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                            <button class="text-sm font-semibold text-gray-700 flex items-center gap-2" onclick="document.getElementById('scheduleDatePicker').showPicker()">
                                <?= date('F j, Y', strtotime($selected_schedule_date))?>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                            <input type="date" id="scheduleDatePicker" class="hidden" value="<?= htmlspecialchars($selected_schedule_date)?>" onchange="window.location='/dashboard/vehicles.php?schedule_date='+this.value">
                            <button onclick="window.location='/dashboard/vehicles.php?schedule_date=<?= $nextScheduleDate?>'" class="p-1 text-gray-500 hover:text-gray-900">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="flex items-center gap-2 border border-gray-200 rounded-lg p-1 bg-white text-sm">
                            <button class="px-3 py-1 rounded-md bg-gray-100 text-gray-700 font-medium">Day</button>
                            <button class="px-3 py-1 text-gray-500 hover:text-gray-900">Week</button>
                            <button class="px-3 py-1 text-gray-500 hover:text-gray-900">Month</button>
                        </div>
                        <button class="px-4 py-2.5 bg-violet-600 text-white rounded-lg text-sm font-semibold hover:bg-violet-500">Add Assignment</button>
                    </div>
                </div>

                <!-- Schedule Board -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="flex border-b border-gray-100 bg-gray-50 px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <div class="w-60">Vehicles (<?= $filteredVehicleCount?>)</div>
                        <div class="flex-1 grid grid-cols-<?= $hourColumns?> gap-0 text-center">
                            <?php for ($hour = $timelineStartHour; $hour < $timelineEndHour; $hour++): ?>
                            <div><?= sprintf('%02d:00', $hour) ?></div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <?php if (empty($filteredVehicles)): ?>
                        <div class="p-12 text-center text-gray-500 text-sm">No vehicles match your filters.</div>
                        <?php else: ?>
                        <?php foreach ($filteredVehicles as $index => $vehicle): 
                            $palette = $vehicleAvatarPalette[$index % count($vehicleAvatarPalette)];
                            $vehicleImage = null;
                            if (!empty($vehicle['images'])) {
                                $decoded = json_decode($vehicle['images'], true);
                                if (is_array($decoded) && !empty($decoded)) {
                                    $vehicleImage = $decoded[0];
                                } elseif (!is_array($decoded)) {
                                    $vehicleImage = $vehicle['images'];
                                }
                            }
                            $vehicleBookings = $assignmentsByVehicle[$vehicle['id']] ?? [];
                        ?>
                        <div class="flex">
                            <div class="w-60 px-4 py-4 flex items-center justify-between gap-2 border-r border-gray-100">
                                <a href="/dashboard/vehicles.php?action=edit&id=<?= (int)$vehicle['id'] ?>" class="flex items-center gap-3 group flex-1 min-w-0">
                                    <?php if ($vehicleImage): ?>
                                    <img src="<?= htmlspecialchars($vehicleImage)?>" alt="<?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'])?>" class="w-10 h-10 rounded-xl object-cover border border-gray-200 group-hover:scale-105 transition-transform duration-200">
                                    <?php else: ?>
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xs font-semibold <?= $palette ?> group-hover:scale-105 transition-transform duration-200">
                                        <?= strtoupper(substr($vehicle['brand'] ?? 'V', 0, 1))?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-semibold text-gray-900 leading-tight group-hover:text-blue-600 group-hover:underline transition-colors truncate"><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'])?></p>
                                        <p class="text-[10px] text-gray-500 truncate">
                                            <?= htmlspecialchars($vehicle['category'] ?? 'Car')?> · <?= htmlspecialchars($vehicle['license_plate'] ?? 'No plate')?>
                                        </p>
                                        <?php if (($vehicle['availability'] ?? 1) == 1): ?>
                                        <p class="text-[10px] text-emerald-600 font-semibold flex items-center gap-1 mt-1">
                                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                            Active
                                        </p>
                                        <?php else: ?>
                                        <p class="text-[10px] text-gray-400 font-semibold flex items-center gap-1 mt-1">
                                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
                                            Disabled
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </a>
                                
                                <!-- Availability Toggle Switch -->
                                <button type="button" 
                                        onclick="window.location.href='/dashboard/vehicles.php?toggle=<?= $vehicle['id'] ?>'" 
                                        class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none <?= (($vehicle['availability'] ?? 1) == 1) ? 'bg-blue-600' : 'bg-gray-200' ?>"
                                        title="<?= (($vehicle['availability'] ?? 1) == 1) ? 'Deactivate Vehicle' : 'Activate Vehicle' ?>">
                                    <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out <?= (($vehicle['availability'] ?? 1) == 1) ? 'translate-x-4' : 'translate-x-0' ?>"></span>
                                </button>
                            </div>
                            <div class="flex-1 relative border-l border-gray-100">
                                <div class="grid grid-cols-<?= $hourColumns?> text-xs text-gray-300">
                                    <?php for ($hour = $timelineStartHour; $hour < $timelineEndHour; $hour++): ?>
                                    <div class="border-l border-gray-100 min-h-[80px]"></div>
                                    <?php endfor; ?>
                                </div>
                                <?php foreach ($vehicleBookings as $booking): 
                                    $startMinutes = minutes_from_time($booking['pickup_time']) ?? $timelineStartMinutes;
                                    $endMinutes = minutes_from_time($booking['return_time']) ?? $timelineEndMinutes;
                                    $clampedStart = max($timelineStartMinutes, $startMinutes);
                                    $clampedEnd = min($timelineEndMinutes, $endMinutes);
                                    $offsetPercent = (($clampedStart - $timelineStartMinutes) / $totalTimelineMinutes) * 100;
                                    $widthPercent = (($clampedEnd - $clampedStart) / $totalTimelineMinutes) * 100;
                                    $statusClass = $assignmentStatusColors[$booking['status']] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                                ?>
                                <button type="button" onclick="openBookingModal(<?= (int)$booking['id']?>)" class="absolute top-3 h-14 rounded-xl border px-4 py-2 flex flex-col justify-center text-left text-xs font-medium shadow-sm <?= $statusClass ?> hover:shadow-md hover:-translate-y-0.5 transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-white/60" style="left: <?= $offsetPercent ?>%; width: <?= max($widthPercent, 10) ?>%; min-width: 120px;">
                                    <div class="flex items-center gap-2">
                                        <span><?= htmlspecialchars($booking['customer_name'] ?? 'Guest')?> </span>
                                        <span class="text-[10px] uppercase text-gray-400"><?= htmlspecialchars($booking['status'])?></span>
                                    </div>
                                    <p class="text-[11px] text-gray-500">
                                        <?= date('M d h:ia', strtotime($booking['pickup_date'] . ' ' . ($booking['pickup_time'] ?? '09:00')))?> -
                                        <?= date('M d h:ia', strtotime($booking['return_date'] . ' ' . ($booking['return_time'] ?? '17:00')))?>
                                    </p>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Booking Details Modal -->
            <div id="bookingModal"
                class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[70] hidden flex items-center justify-center p-4">
                <div class="bg-white rounded-[2rem] w-full max-w-4xl max-h-[95vh] overflow-hidden shadow-2xl flex flex-col relative"
                    onclick="event.stopPropagation()">
                    <div
                        class="p-8 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white/80 backdrop-blur-md z-10">
                        <div>
                            <h3 class="text-xl font-black text-gray-900 uppercase tracking-tighter">Management</h3>
                            <p class="text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] mt-1">Booking Operations
                            </p>
                        </div>
                        <button onclick="closeBookingModal()"
                            class="w-10 h-10 flex items-center justify-center text-gray-400 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-all">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                                </path>
                            </svg>
                        </button>
                    </div>

                    <div id="bookingModalContent" class="p-8 overflow-y-auto flex-1">
                        <!-- Content will be loaded here -->
                    </div>
                </div>
            </div>

            <!-- Contract Preview Modal -->
            <div id="contractPreviewModal"
                class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-[80] flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden">
                    <div
                        class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-white text-gray-900">
                        <div>
                            <h3 class="text-xl font-black uppercase tracking-tighter">Contract Preview</h3>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Agreement Details
                            </p>
                        </div>
                        <button onclick="closeContractPreviewModal()"
                            class="w-10 h-10 flex items-center justify-center text-gray-400 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-all">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div id="contractPreviewContent" class="p-8 overflow-y-auto bg-gray-50 flex-1">
                        <!-- Content will be injected here -->
                    </div>
                    <div class="p-6 border-t border-gray-100 bg-white flex justify-end">
                        <button onclick="closeContractPreviewModal()"
                            class="px-8 py-3 bg-gray-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-black transition-all shadow-xl shadow-gray-200">Close
                            Preview</button>
                    </div>
                </div>
            </div>

            <?php if (isset($_GET['created_success']) && $_GET['created_success'] == 1): ?>
            <div id="successCreatedModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
                <div class="bg-white rounded-[2rem] max-w-md w-full shadow-2xl overflow-hidden p-8 text-center animate-fade-in-up">
                    <div class="w-20 h-20 bg-green-50 text-green-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-black text-gray-900 mb-2 uppercase tracking-tighter">Created Successfully!</h3>
                    <p class="text-gray-500 font-bold text-sm mb-8 leading-relaxed">Your vehicle has been created successfully and is now active in your fleet.</p>
                    <button onclick="closeSuccessCreatedModal()" class="w-full py-4 bg-gray-900 hover:bg-black text-white rounded-2xl font-black text-xs uppercase tracking-[0.2em] shadow-xl shadow-gray-200 transition-all hover:-translate-y-0.5">Got it</button>
                </div>
            </div>
            <script>
                function closeSuccessCreatedModal() {
                    const modal = document.getElementById('successCreatedModal');
                    if (modal) modal.remove();
                    // Quietly remove created_success parameter from URL without page reload
                    const url = new URL(window.location);
                    url.searchParams.delete('created_success');
                    window.history.replaceState({}, document.title, url);
                }
            </script>
            <?php endif; ?>

            <?php else: ?>
            <!-- Add/Edit Vehicle Form -->
            <div class="max-w-5xl mx-auto">
                <?php if ($error): ?>
                <div id="vehicle-error-message" class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
                    <?= htmlspecialchars($error)?>
                </div>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const err = document.getElementById('vehicle-error-message');
                        if (err) {
                            err.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    });
                </script>
                <?php
    endif; ?>

                <div x-data="{ 
                    vehicleTab: '<?= htmlspecialchars($current_tab)?>', 
                    pricingTab: '<?= htmlspecialchars($current_pricing_tab)?>' 
                }" class="space-y-8">

                    <!-- Hero header -->
                    <?php
                        $stepNavigation = [
                            ['label' => 'Vehicle Information', 'tab' => 'basic'],
                            ['label' => 'Service & Repairing', 'tab' => 'images'],
                            ['label' => 'Owner Information', 'tab' => 'settings'],
                            ['label' => 'Rental Setting', 'tab' => 'pricing'],
                        ];
                        $activeCircleClasses = 'bg-white text-blue-600 shadow-lg border-2 border-blue-500';
                        $inactiveCircleClasses = 'bg-white/70 text-blue-200 border border-blue-100';
                    ?>
                    <div class="bg-gradient-to-br from-blue-100/90 via-blue-100 to-blue-50 border border-blue-200 rounded-[32px] shadow-lg p-6 md:p-8 text-gray-900 overflow-hidden">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="space-y-1">
                                <h1 class="text-3xl font-bold leading-tight tracking-tight">Add Your Car for Rental</h1>
                                <p class="text-sm text-blue-700/80">Please fill in all the details to get approval for rental permission.</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-3 justify-end">
                                <a href="/dashboard/vehicles.php" class="px-4 py-2 rounded-full border border-white bg-white/80 text-sm font-semibold text-blue-800 shadow-sm hover:shadow-md transition">Cancel</a>
                                <button type="button" @click="navigateToTab(vehicleTab === 'basic' ? 'images' : (vehicleTab === 'images' ? 'settings' : 'pricing'), $data)" x-show="vehicleTab !== 'pricing'" class="px-5 py-2.5 rounded-full bg-blue-600 text-sm font-semibold text-white shadow-lg hover:shadow-xl transition">Next</button>
                                <button form="vehicleForm" type="submit" x-show="vehicleTab === 'pricing'" class="px-5 py-2.5 rounded-full bg-green-600 text-sm font-semibold text-white shadow-lg hover:shadow-xl transition">Submit</button>
                            </div>
                        </div>
                        <div class="mt-8">
                            <div class="relative">
                                <div class="hidden md:block absolute inset-x-6 top-1/2 -translate-y-1/2">
                                    <div class="h-[2px] bg-blue-200"></div>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 md:gap-0 relative z-10">
                                    <?php foreach ($stepNavigation as $stepIdx => $step): ?>
                                    <button type="button" id="step-btn-<?= $step['tab']?>" @click="navigateToTab('<?= $step['tab']?>', $data)" class="flex flex-col items-center text-center gap-2 md:gap-1 focus:outline-none">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm transition-all" :class="vehicleTab === '<?= $step['tab']?>' ? '<?= $activeCircleClasses?>' : '<?= $inactiveCircleClasses?>'">
                                            <?= str_pad($stepIdx + 1, 2, '0', STR_PAD_LEFT)?>
                                        </div>
                                        <span class="text-[10px] md:text-[11px] font-semibold uppercase tracking-[0.4em] text-blue-500/70">
                                            <?= $step['label']?>
                                        </span>
                                    </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form id="vehicleForm" method="POST" enctype="multipart/form-data" class="space-y-8" @submit="showHtml5Error = false" @invalid.capture="handleFormInvalid($event, $data)">
                    <input type="hidden" name="action" value="<?= $show_edit_form ? 'edit_vehicle' : 'add_vehicle'?>">
                    <?php if ($show_edit_form): ?>
                    <input type="hidden" name="vehicle_id" value="<?= $edit_vehicle['id']?>">
                    <?php
    endif; ?>

                    <input type="hidden" name="current_tab" :value="vehicleTab">
                    <input type="hidden" name="current_pricing_tab" :value="pricingTab">

                    <!-- Basic Information Tab -->
                    <div x-show="vehicleTab === 'basic'" class="space-y-8" x-cloak>

                        <div class="bg-white rounded-3xl border border-gray-100 shadow-xl p-6 sm:p-8">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-2">
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">Vehicle Information</h2>
                                    <p class="text-sm text-gray-500">Provide basic details about the vehicle.</p>
                                </div>
                                <span class="px-3 py-1 text-xs font-semibold text-indigo-600 bg-indigo-50 rounded-full">Step 1</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                                <div>
                                    <?php
    $popular_makes = [
        "Acura", "Alfa Romeo", "Aston Martin", "Audi", "Bentley",
        "BMW", "Buick", "Cadillac", "Chevrolet", "Chrysler",
        "Citroën", "Dacia", "Dodge", "Ferrari", "Fiat",
        "Ford", "Genesis", "GMC", "Honda", "Hyundai",
        "Infiniti", "Jaguar", "Jeep", "Kia", "Lamborghini",
        "Land Rover", "Lexus", "Lincoln", "Maserati", "Mazda",
        "McLaren", "Mercedes-Benz", "Mini", "Mitsubishi", "Nissan",
        "Peugeot", "Polestar", "Porsche", "Ram", "Renault",
        "Rolls-Royce", "Seat", "Skoda", "Subaru", "Suzuki",
        "Tesla", "Toyota", "Vauxhall", "Volkswagen", "Volvo"
    ];
?>
                                <label class="block text-sm font-medium <?= $make_error ? 'text-red-600' : 'text-gray-700' ?> mb-2">Make *</label>
                                <select id="make_select" class="w-full px-4 py-2 border <?= $make_error ? 'border-red-500 focus:ring-red-200 ring-1 ring-red-100' : 'border-gray-300' ?> rounded-lg bg-white select-with-custom focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">Select Make</option>
                                    <?php foreach ($popular_makes as $make): ?>
                                        <option value="<?= htmlspecialchars($make)?>"><?= htmlspecialchars($make)?></option>
                                    <?php
    endforeach; ?>
                                    <option value="Other">Other...</option>
                                </select>
                                <?php if ($make_error): ?>
                                    <p class="text-xs text-red-600 mt-2">Please select a Make.</p>
                                <?php endif; ?>
                                <input type="text" id="make_custom" placeholder="Enter custom make" class="w-full hidden mt-2 px-4 py-2 border border-blue-300 bg-blue-50 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <input type="hidden" name="make" id="make_input" value="<?= field_value('make')?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium <?= $model_error ? 'text-red-600' : 'text-gray-700' ?> mb-2">Model *</label>
                                <select id="model_select" class="w-full px-4 py-2 border <?= $model_error ? 'border-red-500 focus:ring-red-200 ring-1 ring-red-100' : 'border-gray-300' ?> rounded-lg bg-white select-with-custom focus:ring-2 focus:ring-blue-500 focus:border-transparent" <?= $show_edit_form ? '' : 'disabled'?>>
                                    <option value="">Select Model</option>
                                    <?php if ($show_edit_form && !empty($edit_vehicle['model'])): ?>
                                        <option value="<?= htmlspecialchars($edit_vehicle['model'])?>" selected><?= htmlspecialchars($edit_vehicle['model'])?></option>
                                    <?php
    endif; ?>
                                    <option value="Other">Other...</option>
                                </select>
                                <?php if ($model_error): ?>
                                    <p class="text-xs text-red-600 mt-2">Please select a Model.</p>
                                <?php endif; ?>
                                <input type="text" id="model_custom" placeholder="Enter custom model" class="w-full hidden mt-2 px-4 py-2 border border-blue-300 bg-blue-50 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <input type="hidden" name="model" id="model_input" value="<?= field_value('model')?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Registration <span class="text-red-500">*</span></label>
                                <input type="text" name="license_plate" required value="<?= field_value('license_plate')?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="e.g. AB12 CDE">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                                <select name="year" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <?php
    $current_year = date('Y');
    $selected_year = field_raw('year', $current_year);
    for ($y = $current_year; $y >= 2005; $y--):
?>
                                        <option value="<?= $y?>" <?= $selected_year == $y ? 'selected' : ''?>><?= $y?></option>
                                    <?php
    endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Vehicle Type</label>
                                <select name="type" class="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900 appearance-none cursor-pointer hover:border-gray-400 transition-colors"
                                        style="background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 4 5&quot;><path fill=&quot;%23666&quot; d=&quot;M2 0L0 2h4zm0 5L0 3h4z&quot;/></svg>'); background-repeat: no-repeat; background-position: right 12px center; background-size: 12px;"">
                                    <option value="sedan" <?= field_raw('type', 'sedan') === 'sedan' ? 'selected' : ''?>>Sedan</option>
                                    <option value="suv" <?= field_raw('type', 'sedan') === 'suv' ? 'selected' : ''?>>SUV</option>
                                    <option value="coupe" <?= field_raw('type', 'sedan') === 'coupe' ? 'selected' : ''?>>Coupe</option>
                                    <option value="truck" <?= field_raw('type', 'sedan') === 'truck' ? 'selected' : ''?>>Truck</option>
                                    <option value="van" <?= field_raw('type', 'sedan') === 'van' ? 'selected' : ''?>>Van</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Transmission</label>
                                <select name="transmission" class="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900 appearance-none cursor-pointer hover:border-gray-400 transition-colors"
                                        style="background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 4 5&quot;><path fill=&quot;%23666&quot; d=&quot;M2 0L0 2h4zm0 5L0 3h4z&quot;/></svg>'); background-repeat: no-repeat; background-position: right 12px center; background-size: 12px;"">
                                    <option value="automatic" <?= field_raw('transmission', 'automatic') === 'automatic' ? 'selected' : ''?>>Automatic</option>
                                    <option value="manual" <?= field_raw('transmission', 'automatic') === 'manual' ? 'selected' : ''?>>Manual</option>
                                    <option value="semi_auto" <?= field_raw('transmission', 'automatic') === 'semi_auto' ? 'selected' : ''?>>Semi Auto</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fuel Type</label>
                                <select name="fuel_type" class="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900 appearance-none cursor-pointer hover:border-gray-400 transition-colors"
                                        style="background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 4 5&quot;><path fill=&quot;%23666&quot; d=&quot;M2 0L0 2h4zm0 5L0 3h4z&quot;/></svg>'); background-repeat: no-repeat; background-position: right 12px center; background-size: 12px;"">
                                    <option value="petrol" <?= field_raw('fuel_type', 'petrol') === 'petrol' ? 'selected' : ''?>>Petrol</option>
                                    <option value="diesel" <?= field_raw('fuel_type', 'petrol') === 'diesel' ? 'selected' : ''?>>Diesel</option>
                                    <option value="electric" <?= field_raw('fuel_type', 'petrol') === 'electric' ? 'selected' : ''?>>Electric</option>
                                    <option value="hybrid" <?= field_raw('fuel_type', 'petrol') === 'hybrid' ? 'selected' : ''?>>Hybrid</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Seats</label>
                                <select name="seats" class="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg">
                                    <?php $selected_seats = (int) field_raw('seats', 5); ?>
                                    <option value="2" <?= $selected_seats === 2 ? 'selected' : ''?>>2 seats</option>
                                    <option value="4" <?= $selected_seats === 4 ? 'selected' : ''?>>4 seats</option>
                                    <option value="5" <?= $selected_seats === 5 ? 'selected' : ''?>>5 seats</option>
                                    <option value="7" <?= $selected_seats === 7 ? 'selected' : ''?>>7 seats</option>
                                    <option value="8" <?= $selected_seats >= 8 ? 'selected' : ''?>>8+ seats</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Doors</label>
                                <input type="number" name="doors" value="<?= field_value('doors', '5')?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Exterior Colour</label>
                                <input type="text" name="exterior_color" value="<?= field_value('exterior_color', 'Blue')?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Interior Colour</label>
                                <input type="text" name="interior_color" value="<?= field_value('interior_color', 'Brown')?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Engine Capacity (L)</label>
                                <input type="text" name="engine_capacity" value="<?= field_value('engine_capacity', '1.6')?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div class="col-span-2">
                                <?php
    $saved_features = [];
    $raw_features = field_raw('vehicle_features', '');
    if (!empty($raw_features)) {
        $saved_features = json_decode($raw_features, true) ?? [];
    } elseif ($show_edit_form && !empty($edit_vehicle['vehicle_features'])) {
        $saved_features = json_decode($edit_vehicle['vehicle_features'], true) ?? [];
    }
?>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Vehicle Features</label>
                                <p class="text-xs text-gray-500 mb-2">Type a feature and press <kbd class="px-1 py-0.5 bg-gray-100 rounded text-xs">Enter</kbd> or <kbd class="px-1 py-0.5 bg-gray-100 rounded text-xs">,</kbd> to add it.</p>
                                <div id="featuresContainer" class="flex flex-wrap gap-2 p-3 border border-gray-300 rounded-lg min-h-[48px] bg-white focus-within:ring-2 focus-within:ring-blue-500 cursor-text" onclick="document.getElementById('featureInput').focus()">
                                    <?php foreach ($saved_features as $feat): ?>
                                    <span class="feature-tag inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                                        <?= htmlspecialchars($feat)?>
                                        <button type="button" onclick="removeFeatureTag(this)" class="ml-1 text-blue-500 hover:text-blue-800 font-bold leading-none">&times;</button>
                                    </span>
                                    <?php
    endforeach; ?>
                                    <input type="text" id="featureInput" placeholder="Add feature (e.g. Air Con, GPS, Bluetooth)..." class="flex-1 min-w-[180px] outline-none border-none bg-transparent text-sm text-gray-700 py-0.5">
                                </div>
                                <input type="hidden" name="vehicle_features" id="vehicle_features_input" value="<?= htmlspecialchars(json_encode($saved_features))?>">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Vehicle Description</label>
                                <textarea name="description" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Enter car description..."><?= field_value('description')?></textarea>
                            </div>
                            </div>
                        </div>

                        <!-- Featured Vehicle -->
                        <?php $featured_checked = isset($formPost['featured']) || ($show_edit_form && !empty($edit_vehicle['featured'])); ?>
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="featured" <?= $featured_checked ? 'checked' : ''?> class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                <span class="ml-3 text-sm font-medium text-gray-700">Mark as Featured Vehicle</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Images Tab -->
                    <div x-show="vehicleTab === 'images'" class="space-y-8" x-cloak>
                        <div>
                            <h2 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">Vehicle Images</h2>
                            <div class="space-y-3 bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                                <div class="flex flex-col sm:flex-row gap-4 items-center justify-center">
                                    <div class="flex-1 w-full flex flex-col items-center justify-center py-8 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition-colors" onclick="document.getElementById('vehicleImageInput').click()">
                                        <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                        </svg>
                                        <p class="text-gray-600 font-medium mb-1">Click to upload or drag & drop</p>
                                        <p class="text-xs text-gray-500">PNG, JPG up to 5MB</p>
                                        <input type="file" id="vehicleImageInput" name="vehicle_image[]" accept="image/*" class="hidden" multiple onchange="handleVehicleFiles(this)">
                                    </div>
                                    <div class="hidden sm:block w-px h-24 bg-gray-200"></div>
                                    <div class="flex-1 w-full flex flex-col items-center justify-center py-8 border-2 border-dashed border-blue-200 bg-blue-50 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-100 transition-colors" onclick="openMediaSelector(handleLibrarySelection, true)">
                                        <svg class="w-12 h-12 text-blue-500 mb-3 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <p class="text-blue-600 font-bold mb-1">Browse Gallery</p>
                                        <p class="text-xs text-gray-500">Select existing media</p>
                                    </div>
                                </div>
                                <div id="imagePreview" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 mt-6">
                                    <!-- Previews will appear here -->
                                </div>
                                
                                <!-- Existing Images -->
                                <?php
                                $existing_images = [];
                                if ($show_edit_form && !empty($edit_vehicle['images'])) {
                                    $decoded = json_decode($edit_vehicle['images'], true);
                                    $existing_images = is_array($decoded) ? $decoded : [$edit_vehicle['images']];
                                }
                                ?>
                                
                                <?php if (!empty($existing_images)): ?>
                                <h3 class="font-medium text-gray-900 mt-6 mb-3">Saved Images</h3>
                                <?php endif; ?>
                                <div id="imagePreviewContainer" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 <?= empty($existing_images) ? 'hidden' : ''?>">
                                    <?php foreach ($existing_images as $index => $img): ?>
                                        <div class="relative group rounded-lg overflow-hidden h-32 existing-image-wrapper border border-gray-200">
                                            <img src="<?= htmlspecialchars($img)?>" class="w-full h-full object-cover">
                                            <input type="hidden" name="existing_images[]" value="<?= htmlspecialchars($img)?>">
                                            <button type="button" class="absolute top-2 right-2 p-1.5 bg-red-600 text-white rounded-lg opacity-0 group-hover:opacity-100 transition shadow-sm hover:bg-red-700 remove-existing-btn">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rental Settings Tab -->
                    <div x-show="vehicleTab === 'settings'" class="space-y-8" x-cloak>

                        <div class="bg-white rounded-3xl border border-gray-100 shadow-xl p-6 sm:p-8">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-2">
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">Rental Policy & Documents</h2>
                                    <p class="text-sm text-gray-500">Specify deposit requirements and renter qualifications.</p>
                                </div>
                                <span class="px-3 py-1 text-xs font-semibold text-amber-600 bg-amber-50 rounded-full">Step 2</span>
                            </div>
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Contract Template <span class="text-red-500">*</span>
                                    </label>
                                    <select name="contract_template_id" required class="w-full px-4 py-2 bg-white border border-gray-300 text-gray-700 pr-8 pl-4 py-2 rounded-lg hover:bg-gray-50 cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none"
                                            style="background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 4 5&quot;><path fill=&quot;%23666&quot; d=&quot;M2 0L0 2h4zm0 5L0 3h4z&quot;/></svg>'); background-repeat: no-repeat; background-position: right 12px center; background-size: 12px;">
                                        <option value="">No contract assigned</option>
                                        <?php foreach ($contract_templates as $template): ?>
                                            <option value="<?= $template['id']?>" <?=($show_edit_form && isset($edit_vehicle['contract_template_id']) && $edit_vehicle['contract_template_id'] == $template['id']) ? 'selected' : ''?>>
                                                <?= htmlspecialchars($template['name'])?>
                                            </option>
                                        <?php
    endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="space-y-4 bg-gray-50 p-6 rounded-xl border border-gray-100" x-data="{ requireDeposit: <?= $show_edit_form && !empty($edit_vehicle['require_deposit']) ? 'true' : 'false'?> }">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900">Require Security Deposit</h4>
                                            <p class="text-xs text-gray-500 mt-1">Tenant must pay a deposit for this vehicle</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" class="sr-only peer" name="require_deposit" x-model="requireDeposit">
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                        </label>
                                    </div>
                                    
                                    <div x-show="requireDeposit" x-cloak x-transition class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6 pt-4 border-t border-gray-200">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Deposit Amount (GBP) <span class="text-red-500">*</span></label>
                                            <input type="number" name="deposit" placeholder="1000" x-bind:required="requireDeposit" value="<?= $show_edit_form ? ($edit_vehicle['deposit'] ?? '') : ''?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                                            <select name="deposit_type" x-bind:required="requireDeposit" class="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 appearance-none cursor-pointer"
                                                    style="background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 4 5&quot;><path fill=&quot;%23666&quot; d=&quot;M2 0L0 2h4zm0 5L0 3h4z&quot;/></svg>'); background-repeat: no-repeat; background-position: right 12px center; background-size: 12px;">
                                                <option value="booking" <?= $show_edit_form && isset($edit_vehicle['deposit_type']) && $edit_vehicle['deposit_type'] === 'booking' ? 'selected' : ''?>>Pay at Booking</option>
                                                <option value="collection" <?= $show_edit_form && isset($edit_vehicle['deposit_type']) && $edit_vehicle['deposit_type'] === 'collection' ? 'selected' : ''?>>Pay on Collection</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Minimum licence years</label>
                                        <select name="min_license_years" class="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 appearance-none cursor-pointer"
                                                style="background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 4 5&quot;><path fill=&quot;%23666&quot; d=&quot;M2 0L0 2h4zm0 5L0 3h4z&quot;/></svg>'); background-repeat: no-repeat; background-position: right 12px center; background-size: 12px;">
                                            <?php for ($i = 1; $i <= 7; $i++): ?>
                                            <option value="<?= $i?>" <?= $show_edit_form && isset($edit_vehicle['min_license_years']) && $edit_vehicle['min_license_years'] == $i ? 'selected' : ''?>><?= $i?> Year<?= $i > 1 ? 's' : ''?></option>
                                            <?php
    endfor; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Min age required</label>
                                        <input type="number" name="min_age" placeholder="25" value="<?= $show_edit_form && isset($edit_vehicle['min_age']) ? $edit_vehicle['min_age'] : ''?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Minimum Rental Period</label>
                                        <select name="min_days" class="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 appearance-none cursor-pointer"
                                                style="background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 4 5&quot;><path fill=&quot;%23666&quot; d=&quot;M2 0L0 2h4zm0 5L0 3h4z&quot;/></svg>'); background-repeat: no-repeat; background-position: right 12px center; background-size: 12px;">
                                            <?php for ($i = 1; $i <= 30; $i++): ?>
                                                <option value="<?= $i?>" <?=($show_edit_form && isset($edit_vehicle['min_days']) && $edit_vehicle['min_days'] == $i) ? 'selected' : ($i == 1 ? 'selected' : '')?>><?= $i?> <?= $i == 1 ? 'day' : 'days'?></option>
                                            <?php
    endfor; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing Tab -->
                    <div x-show="vehicleTab === 'pricing'" class="space-y-8" x-cloak>
                        <div id="pricing-settings-container" class="bg-white rounded-3xl border border-gray-100 shadow-xl p-6 sm:p-8">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-2">
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">Pricing Settings</h2>
                                    <p class="text-sm text-gray-500">Adjust daily rates and custom packages.</p>
                                </div>
                                <span class="px-3 py-1 text-xs font-semibold text-violet-600 bg-violet-50 rounded-full">Step 4</span>
                            </div>
                            <div class="flex flex-wrap gap-3 mb-6">
                                <button type="button" @click="pricingTab = 'daily'" :class="pricingTab === 'daily' ? 'bg-slate-900 text-white shadow-lg' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-4 py-2 rounded-xl font-medium text-sm transition">Daily rate</button>
                                <button type="button" @click="pricingTab = 'packages'" :class="pricingTab === 'packages' ? 'bg-slate-900 text-white shadow-lg' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-4 py-2 rounded-xl font-medium text-sm transition">Pricing packages</button>
                            </div>
                            
                            <!-- Daily Rate Content -->
                            <div x-show="pricingTab === 'daily'">
                                <?php $priceErrorClass = $price_validation_error ? 'border-red-500 focus:ring-red-200 ring-1 ring-red-100' : ''; ?>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Default price per day (GBP)</label>
                                        <input type="number" step="0.01" name="price_per_day" placeholder="120" value="<?= field_value('price_per_day')?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 <?= $priceErrorClass?>">
                                        <?php if ($price_validation_error): ?>
                                        <p class="text-xs text-red-600 mt-2">Please provide the default price per day before continuing.</p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-span-1 sm:col-span-2 mt-2 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                                        <label class="block text-sm font-medium text-gray-900 mb-3">Dynamic Pricing per Day of Week</label>
                                        <p class="text-xs text-gray-500 mb-4">Set specific prices for certain days. Leave blank to use default.</p>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
                                            <?php
    $day_labels = ['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun'];
    $current_pricing = $show_edit_form && !empty($edit_vehicle['daily_pricing']) ? json_decode($edit_vehicle['daily_pricing'], true) : [];
    foreach ($day_labels as $d_key => $d_label):
        $posted_day_price = $formPost['price_' . $d_key] ?? '';
        $d_price = $posted_day_price !== '' ? $posted_day_price : ($current_pricing[$d_key] ?? '');
?>
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-700 mb-1"><?= $d_label?></label>
                                                <input type="number" step="0.01" name="price_<?= $d_key?>" placeholder="Default" value="<?= htmlspecialchars($d_price)?>" class="w-full px-2 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 text-sm">
                                            </div>
                                            <?php
    endforeach; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Included <?= htmlspecialchars($distance_unit)?> per day</label>
                                        <div class="flex items-center space-x-2">
                                            <input type="number" name="mileage_limit" placeholder="300" value="<?= $show_edit_form ? $edit_vehicle['mileage_limit'] : ''?>" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg">
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" name="unlimited_mileage" class="sr-only peer">
                                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                                <span class="ml-3 text-sm text-gray-700 font-medium">Unlimited</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="hidden">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Minimum days</label>
                                        <input type="number" name="old_min_days" value="1">
                                    </div>
                                </div>
                            </div>

                            <!-- Pricing Packages Content -->
                            <div x-show="pricingTab === 'packages'">
                                <input type="hidden" name="pricing_packages_json" id="pricing_packages_json" value="<?= htmlspecialchars($show_edit_form && !empty($edit_vehicle['pricing_packages']) ? $edit_vehicle['pricing_packages'] : '[]', ENT_QUOTES, 'UTF-8')?>">
                                <p class="text-sm text-gray-600 mb-4">Create special offers and package discounts to encourage longer bookings. These rules override normal pricing when the minimum conditions are met.</p>
                                <div id="packages-list" class="space-y-4"></div>
                                <button type="button" id="add-package-btn" class="mt-4 px-4 py-3 bg-white border-2 border-dashed border-gray-300 text-gray-600 rounded-lg hover:border-gray-400 hover:text-gray-900 hover:bg-gray-50 text-sm font-medium w-full flex items-center justify-center transition-colors">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Add New Package Rule
                                </button>
                            </div>
                        </div>
                    </div>


                    <!-- Calendar Tab -->
                    <?php if ($show_edit_form): ?>
                    <div x-show="vehicleTab === 'calendar'" class="space-y-8" x-cloak>

                        <div class="bg-white rounded-3xl border border-gray-100 shadow-xl p-6 sm:p-8">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-2">
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">Availability Management</h2>
                                    <p class="text-sm text-gray-500">Block maintenance days or inspect bookings at a glance.</p>
                                </div>
                                <span class="px-3 py-1 text-xs font-semibold text-emerald-600 bg-emerald-50 rounded-full">Live</span>
                            </div>
                            
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-3 underline decoration-blue-500 underline-offset-4">Set Manual Unavailable Dates</label>
                                    <div class="relative">
                                        <input type="text" id="unavailable_dates" name="unavailable_dates" 
                                           value="<?= htmlspecialchars($edit_vehicle['unavailable_dates'] ?? '')?>"
                                           class="w-full px-4 py-3 bg-white border border-gray-300 text-gray-700 rounded-xl shadow-sm hover:ring-2 hover:ring-blue-100 transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           placeholder="Select days to block...">
                                    </div>
                                    <!-- Container for selected date tags -->
                                    <div id="selected-dates-tags" class="mt-2 flex flex-wrap gap-2"></div>
                                    <p class="text-xs text-gray-500 mt-2 italic flex-grow">Selected dates will be blocked off and impossible for customers to book on the front-end.</p>
                                    <button type="button" id="save-dates-btn" onclick="saveUnavailableDates()" class="mt-4 px-6 py-2 bg-blue-600 text-white font-bold rounded-lg shadow hover:bg-blue-700 transition-colors hidden w-full sm:w-auto text-center">Save Blocked Dates</button>
                                </div>
                                
                                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                                    <label class="block text-sm font-bold text-gray-900 mb-4 flex items-center">
                                        <span class="w-3 h-3 bg-blue-600 rounded-full mr-2"></span>
                                        Live Availability View
                                    </label>
                                    <div id="inline-availability-calendar" class="mx-auto"></div>
                                    <div class="mt-6 flex flex-wrap gap-4 text-xs font-semibold uppercase tracking-wider text-gray-500 justify-center">
                                        <div class="flex items-center"><span class="w-3 h-3 bg-red-100 border border-red-300 rounded mr-2"></span> Booked</div>
                                        <div class="flex items-center"><span class="w-3 h-3 bg-blue-600 rounded mr-2"></span> Manual Block</div>
                                        <div class="flex items-center"><span class="w-3 h-3 border border-gray-300 rounded mr-2"></span> Available</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
    endif; ?>

                    <!-- Form Actions -->
                    <div class="flex flex-col sm:flex-row justify-end gap-3 sm:space-x-4 pt-6 border-t border-gray-200">
                        <a href="/dashboard/vehicles.php" class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-center">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2.5 bg-black text-white rounded-lg hover:bg-gray-800">
                            <?= $show_edit_form ? 'Update Vehicle' : 'Create Vehicle'?>
                        </button>
                    </div>
                </form>
                </div>
            <?php
endif; ?>
        </main>
    </div>
    
    <!-- Support Chat Button -->
    <button class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 w-12 h-12 sm:w-14 sm:h-14 bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-lg flex items-center justify-center transition z-30">
        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
        </svg>
    </button>

    <!-- Mobile Menu Overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden hidden"></div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        function navigateToTab(targetTab, alpineData) {
            const currentTab = alpineData.vehicleTab;
            const steps = ['basic', 'images', 'settings', 'pricing', 'calendar'];
            const currentIdx = steps.indexOf(currentTab);
            const targetIdx = steps.indexOf(targetTab);
            
            // Going backwards is always allowed
            if (targetIdx <= currentIdx) {
                alpineData.vehicleTab = targetTab;
                return;
            }
            
            // Going forward: validate each intermediate tab
            for (let i = currentIdx; i < targetIdx; i++) {
                const stepTab = steps[i];
                const tabEl = document.querySelector(`div[x-show="vehicleTab === '${stepTab}'"]`);
                if (tabEl) {
                    // Custom validation for Make / Model since they use hidden inputs
                    if (stepTab === 'basic') {
                        const makeInput = document.getElementById('make_input');
                        const modelInput = document.getElementById('model_input');
                        const makeVal = makeInput ? makeInput.value.trim() : '';
                        const modelVal = modelInput ? modelInput.value.trim() : '';
                        
                        if (!makeVal) {
                            showNotification('Missing details: Please select a Make.', 'error');
                            const makeSelect = document.getElementById('make_select');
                            if (makeSelect) {
                                makeSelect.style.borderColor = '#ef4444';
                                makeSelect.style.boxShadow = '0 0 0 1px #ef4444';
                                makeSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                makeSelect.focus();
                            }
                            alpineData.vehicleTab = 'basic';
                            return;
                        }
                        if (!modelVal) {
                            showNotification('Missing details: Please select a Model.', 'error');
                            const modelSelect = document.getElementById('model_select');
                            if (modelSelect) {
                                modelSelect.style.borderColor = '#ef4444';
                                modelSelect.style.boxShadow = '0 0 0 1px #ef4444';
                                modelSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                modelSelect.focus();
                            }
                            alpineData.vehicleTab = 'basic';
                            return;
                        }
                    }
                    
                    // Validate standard required inputs on this tab
                    const requiredInputs = tabEl.querySelectorAll('[required]');
                    for (const input of requiredInputs) {
                        if (!input.checkValidity()) {
                            alpineData.vehicleTab = stepTab;
                            setTimeout(() => {
                                handleFormInvalid({ target: input, preventDefault: () => {} }, alpineData);
                            }, 50);
                            return;
                        }
                    }
                }
            }
            
            // If all validation passed, switch to target tab
            alpineData.vehicleTab = targetTab;
        }
        window.navigateToTab = navigateToTab;

        function handleFormInvalid(event, alpineData) {
            event.preventDefault();
            const target = event.target;
            
            // 1. Find which tab has the invalid field
            const tabDiv = target.closest('[x-show*="vehicleTab"]');
            let tabName = '';
            if (tabDiv) {
                const xShow = tabDiv.getAttribute('x-show');
                const match = xShow.match(/vehicleTab\s*===\s*'([^']+)'/);
                if (match && match[1]) {
                    tabName = match[1];
                    if (alpineData) {
                        alpineData.vehicleTab = tabName;
                    }
                }
            }
            
            // 2. Add visual validation highlights (1px solid red border around the input/select, label, or wrapper)
            target.classList.add('border-red-500', 'focus:ring-red-500', 'focus:border-red-500');
            target.style.borderColor = '#ef4444';
            target.style.boxShadow = '0 0 0 1px #ef4444';
            
            // Find closest label
            let label = null;
            if (target.id) {
                label = document.querySelector(`label[for="${target.id}"]`);
            }
            if (!label) {
                const parent = target.closest('div');
                if (parent) {
                    label = parent.querySelector('label');
                }
            }
            
            if (label) {
                label.style.border = '1px solid #ef4444';
                label.style.borderRadius = '8px';
                label.style.padding = '2px 6px';
                label.style.backgroundColor = '#fef2f2';
                label.style.color = '#b91c1c';
                label.style.display = 'inline-block';
            }
            
            // Highlight the stepper button for the tab containing the error
            if (tabName) {
                const stepBtn = document.getElementById(`step-btn-${tabName}`);
                if (stepBtn) {
                    const circle = stepBtn.querySelector('.rounded-full');
                    if (circle) {
                        circle.style.border = '2px solid #ef4444';
                        circle.style.backgroundColor = '#fef2f2';
                        circle.style.color = '#b91c1c';
                    }
                }
            }
            
            // Get field display name for notifications
            let fn = 'required';
            if (label) {
                fn = label.innerText.replace('*','').trim();
            } else if (target.name) {
                fn = target.name.replace(/_/g, ' ');
                fn = fn.charAt(0).toUpperCase() + fn.slice(1);
            }
            
            if (typeof showNotification === 'function') {
                showNotification('Missing details: Please complete the "' + fn + '" field.', 'error');
            }
            
            // Clear the highlight when the user starts typing/editing
            const clearHighlight = () => {
                target.style.borderColor = '';
                target.style.boxShadow = '';
                target.classList.remove('border-red-500', 'focus:ring-red-500', 'focus:border-red-500');
                if (label) {
                    label.style.border = '';
                    label.style.borderRadius = '';
                    label.style.padding = '';
                    label.style.backgroundColor = '';
                    label.style.color = '';
                    label.style.display = '';
                }
                if (tabName) {
                    const stepBtn = document.getElementById(`step-btn-${tabName}`);
                    if (stepBtn) {
                        const circle = stepBtn.querySelector('.rounded-full');
                        if (circle) {
                            circle.style.border = '';
                            circle.style.backgroundColor = '';
                            circle.style.color = '';
                        }
                    }
                }
                target.removeEventListener('input', clearHighlight);
                target.removeEventListener('change', clearHighlight);
            };
            target.addEventListener('input', clearHighlight);
            target.addEventListener('change', clearHighlight);
            
            // Scroll the target element into view
            setTimeout(() => {
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                target.focus();
            }, 100);
        }
        window.handleFormInvalid = handleFormInvalid;

        // Init Flatpickr Multi-Select for blocked dates
        const unavailableDatesInput = document.getElementById('unavailable_dates');
        const selectedDatesTagsContainer = document.getElementById('selected-dates-tags');
        // Helper to render selected dates as removable tags
        function renderSelectedDates(dates) {
            if (!selectedDatesTagsContainer) return;
            selectedDatesTagsContainer.innerHTML = '';
            dates.forEach(function(date) {
                const tag = document.createElement('span');
                tag.className = 'inline-flex items-center px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-sm';
                tag.textContent = date.toISOString().split('T')[0];
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'ml-1 text-blue-600 hover:text-blue-900';
                removeBtn.innerHTML = '&times;';
                removeBtn.onclick = function() {
                    // Remove date from flatpickr selection
                    const fp = unavailableDatesInput._flatpickr;
                    if (fp) {
                        const newDates = fp.selectedDates.filter(d => d.getTime() !== date.getTime());
                        fp.setDate(newDates, true);
                    }
                };
                tag.appendChild(removeBtn);
                selectedDatesTagsContainer.appendChild(tag);
            });
        }
        if (unavailableDatesInput) {
            flatpickr(unavailableDatesInput, {
                mode: "multiple",
                dateFormat: "Y-m-d",
                minDate: "today",
                onChange: function(selectedDates, dateStr) {
                    // Update hidden input value (flatpickr does this automatically)
                    renderSelectedDates(selectedDates);
                    // Update inline calendar when selection changes
                    if (window.inlineCal) {
                        window.inlineCal.set('disable', getFullDisableList(dateStr));
                        window.inlineCal.redraw();
                    }
                    
                    // Show save button
                    const saveBtn = document.getElementById('save-dates-btn');
                    if(saveBtn) saveBtn.classList.remove('hidden');
                }
            });
            // Initial render if there are pre‑saved dates
            if (unavailableDatesInput.value) {
                const initialDates = unavailableDatesInput.value.split(', ');
                const dateObjs = initialDates.map(d => new Date(d));
                renderSelectedDates(dateObjs);
            }
        }
        
        // Inline Read-Only Availability Calendar for Admin
        const inlineCalContainer = document.getElementById('inline-availability-calendar');
        if (inlineCalContainer) {
            const bookedRanges = <?= json_encode($booked_dates ?? [])?>;
            const getManualDates = (manualStr) => {
                return (manualStr || "").split(", ").filter(d => d.trim() !== "");
            };
            const getFullDisableList = (manualStr) => {
                return bookedRanges.map(b => ({ from: b.pickup_date, to: b.return_date })).concat(getManualDates(manualStr));
            };

            window.inlineCal = flatpickr(inlineCalContainer, {
                inline: true,
                clickOpens: false,
                mode: "multiple",
                minDate: "today",
                showMonths: 1,
                disable: getFullDisableList(unavailableDatesInput ? unavailableDatesInput.value : ""),
                locale: {
                    firstDayOfWeek: 1
                },
                 onDayCreate: function(dObj, dStr, fp, dayElem) {
                    const dateStr = fp.formatDate(dayElem.dateObj, "Y-m-d");
                    const manualList = getManualDates(unavailableDatesInput ? unavailableDatesInput.value : "");
                    
                    let isBooked = false;
                    for(let i=0; i<bookedRanges.length; i++) {
                        let bkFrom = bookedRanges[i].pickup_date;
                        let bkTo = bookedRanges[i].return_date;
                        
                        if (dateStr >= bkFrom && dateStr <= bkTo) {
                            isBooked = true;
                            break;
                        }
                    }
                    
                    let isManual = manualList.includes(dateStr);
                    
                    if(isBooked) {
                        dayElem.style.backgroundColor = '#fee2e2'; // red-100
                        dayElem.style.borderColor = '#fca5a5'; // red-300
                        dayElem.style.color = '#7f1d1d'; // red-900
                    } else if(isManual) {
                        dayElem.style.backgroundColor = '#2563eb'; // blue-600
                        dayElem.style.borderColor = '#1d4ed8'; // blue-700
                        dayElem.style.color = '#ffffff'; // white
                    }
                }
            });
        }
        
        function saveUnavailableDates() {
            const val = document.getElementById('unavailable_dates').value;
            const btn = document.getElementById('save-dates-btn');
            btn.textContent = 'Saving...';
            btn.disabled = true;
            
            const params = new URLSearchParams();
            params.append('vehicle_id', '<?= htmlspecialchars($_GET['id'] ?? '')?>');
            params.append('unavailable_dates', val);
            
            fetch('/dashboard/update-blocked-dates.php', {
                method: 'POST',
                body: params
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    btn.textContent = 'Saved!';
                    btn.classList.replace('bg-blue-600', 'bg-green-600');
                    btn.classList.replace('hover:bg-blue-700', 'hover:bg-green-700');
                    setTimeout(() => { 
                        btn.classList.add('hidden');
                        btn.textContent = 'Save Blocked Dates';
                        btn.classList.replace('bg-green-600', 'bg-blue-600');
                        btn.classList.replace('hover:bg-green-700', 'hover:bg-blue-700');
                        btn.disabled = false;
                    }, 2000);
                } else {
                    showNotification(data.message, 'error');
                    btn.textContent = 'Save Blocked Dates';
                    btn.disabled = false;
                }
            })
            .catch(err => {
                showNotification('Something went wrong. Please try again.', 'error');
                btn.textContent = 'Save Blocked Dates';
                btn.disabled = false;
            });
        }

        // Image upload functionality - only run if form elements exist
        const dropZone = document.getElementById('imageDropZone');
        const imageInput = document.getElementById('vehicleImageInput');
        const imagePreviewContainer = document.getElementById('imagePreviewContainer');
        
        if (dropZone && imageInput && imagePreviewContainer) {
            const removeBtns = document.querySelectorAll('.remove-existing-btn');
            let selectedFiles = [];

            // Click to upload
            dropZone.addEventListener('click', () => imageInput.click());

            // Drag and drop
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('border-blue-500', 'bg-blue-50');
            });

            dropZone.addEventListener('dragleave', () => {
                dropZone.classList.remove('border-blue-500', 'bg-blue-50');
            });

            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('border-blue-500', 'bg-blue-50');
                
                if (e.dataTransfer.files.length > 0) {
                    handleImageFiles(Array.from(e.dataTransfer.files));
                }
            });

            // File input change
            imageInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    handleImageFiles(Array.from(e.target.files));
                }
            });

            // Handle existing image removal
            removeBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    this.closest('.existing-image-wrapper').remove();
                    if (imagePreviewContainer.children.length === 0) {
                        imagePreviewContainer.classList.add('hidden');
                    }
                });
            });

            function handleImageFiles(files) {
            let validFiles = [];
            
            files.forEach(file => {
                if (!file.type.startsWith('image/')) return;
                
                // Check file size (5MB max)
                if (file.size > 5 * 1024 * 1024) {
                    showFormError('File Too Large', `File ${file.name} exceeds 5MB limit.`);
                    return;
                }
                
                validFiles.push(file);
            });

            if (validFiles.length > 0) {
                imagePreviewContainer.classList.remove('hidden');
                
                validFiles.forEach(file => {
                    selectedFiles.push(file);
                    
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const div = document.createElement('div');
                        div.className = 'relative group rounded-lg overflow-hidden h-32 new-image-wrapper';
                        div.innerHTML = `
                            <img src="${e.target.result}" class="w-full h-full object-cover">
                            <button type="button" class="absolute top-2 right-2 p-1.5 bg-red-600 text-white rounded-lg opacity-0 group-hover:opacity-100 transition shadow-sm hover:bg-red-700" onclick="removeNewImage(this, '${file.name}')">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        `;
                        imagePreviewContainer.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                });
                
                // Update file input with all selected files
                updateFileInput();
            }
            }

            function removeNewImage(btn, fileName) {
                // Remove from DOM
                btn.closest('.new-image-wrapper').remove();
                
                // Remove from selected files array
                selectedFiles = selectedFiles.filter(f => f.name !== fileName);
                updateFileInput();
                
                if (imagePreviewContainer.children.length === 0) {
                    imagePreviewContainer.classList.add('hidden');
                }
            }

            function updateFileInput() {
                const dt = new DataTransfer();
                selectedFiles.forEach(file => dt.items.add(file));
                imageInput.files = dt.files;
            }
            
            function showFormError(title, message) {
                showNotification(title + ': ' + message, 'error');
            }

            // Make removeNewImage globally accessible for onclick handlers
            window.removeNewImage = removeNewImage;
        }

        // Toggle featured status
        function toggleFeatured(vehicleId, featured) {
            window.location.href = '/dashboard/vehicles.php?toggle_featured=' + vehicleId + '&featured=' + featured;
        }

        function viewBooking(id) {
            openBookingModal(id);
        }

        function updateSecurityDeposit(bookingId) {
            const amount = document.getElementById('deposit-amount').value;
            const status = document.getElementById('deposit-status').value;
            const method = document.getElementById('deposit-method').value;

            fetch('/dashboard/update-booking-deposit.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    booking_id: bookingId,
                    amount: amount,
                    status: status,
                    method: method
                })
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Security deposit updated successfully');
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(err => showNotification('Error updating deposit', 'error'));
        }

        function openBookingModal(bookingId, initialTab = 'details') {
            const modal = document.getElementById('bookingModal');
            const content = document.getElementById('bookingModalContent');
            if (!modal || !content) return;

            modal.classList.remove('hidden');
            content.innerHTML = `
                <div class="flex items-center justify-center py-20">
                    <div class="flex flex-col items-center gap-4">
                        <div class="animate-spin rounded-full h-12 w-12 border-[3px] border-blue-600/20 border-t-blue-600"></div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Retrieving Data</p>
                    </div>
                </div>
            `;

            fetch('/dashboard/get-booking-details.php?id=' + bookingId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayBookingDetails(data.booking, data.condition_reports, data.contract, data.available_templates);
                        if (initialTab !== 'details') switchModalTab(initialTab);
                    } else {
                        content.innerHTML = `
                            <div class="text-center py-12">
                                <p class="text-red-600 font-bold">${data.message || 'Failed to load booking details'}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    content.innerHTML = `
                        <div class="text-center py-12">
                            <p class="text-red-600 font-bold">Error loading booking details</p>
                            <p class="text-[10px] text-gray-400 mt-2">${error.message}</p>
                        </div>
                    `;
                });
        }

        function displayBookingDetails(booking, reports, contract, available_templates = []) {
            const pickupReport = reports && reports.pickup ? reports.pickup : null;
            const returnReport = reports && reports.return ? reports.return : null;

            const statusMap = {
                'pending': 'bg-amber-50 text-amber-600 border border-amber-100',
                'confirmed': 'bg-blue-50 text-blue-600 border border-blue-100',
                'active': 'bg-green-50 text-green-600 border border-green-100',
                'completed': 'bg-gray-50 text-gray-600 border border-gray-100',
                'cancelled': 'bg-red-50 text-red-600 border border-red-100'
            };

            const paymentMap = {
                'unpaid': 'bg-amber-100 text-amber-800',
                'partial': 'bg-blue-100 text-blue-800',
                'paid': 'bg-green-100 text-green-800',
                'refunded': 'bg-red-100 text-red-800'
            };

            const prepareMiscPhotos = (report) => {
                let html = '';
                try {
                    const misc = (report && report.misc_photos) ? JSON.parse(report.misc_photos) : [];
                    for (let i = 0; i < 4; i++) {
                        if (misc[i]) {
                            html += `<div class="aspect-square rounded-xl bg-gray-100 overflow-hidden shadow-sm border border-gray-100 group relative">
                                <img src="${misc[i]}" class="w-full h-full object-cover">
                            </div>`;
                        } else {
                            html += `<div class="aspect-square rounded-xl bg-gray-50 border-2 border-dashed border-gray-200 flex flex-col items-center justify-center p-2 text-center text-gray-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="2" /></svg>
                            </div>`;
                        }
                    }
                } catch (e) {
                    for (let i = 0; i < 4; i++) html += `<div class="aspect-square rounded-xl bg-gray-50 border-2 border-dashed border-gray-200 flex items-center justify-center"><svg class="w-5 h-5 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="2.5"/></svg></div>`;
                }
                return html;
            };

            const renderPhotoField = (id, label, icon, currentPath, type) => {
                const hasPhoto = currentPath && currentPath !== '';
                const isRequired = !hasPhoto;
                return `
                    <div class="relative group condition-photo-wrapper" data-id="${id}" data-has-photo="${hasPhoto}">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">${label}${isRequired ? ' <span class="text-red-500">*</span>' : ''}</label>
                        <div onclick="this.querySelector('input').click()" class="relative aspect-video rounded-2xl bg-gray-50 border-2 border-dashed ${isRequired ? 'border-red-100/50' : 'border-gray-200'} hover:border-blue-300 transition-all cursor-pointer overflow-hidden flex items-center justify-center group/photo">
                            ${hasPhoto ?
                        `<img src="${currentPath}" class="w-full h-full object-cover transition-transform group-hover/photo:scale-110">
                         <div class="absolute inset-0 bg-black/40 opacity-0 group-hover/photo:opacity-100 transition-opacity flex items-center justify-center">
                            <span class="text-[10px] text-white font-bold uppercase tracking-widest">Replace Photo</span>
                         </div>` :
                        `<div class="text-center">
                                    <svg class="w-8 h-8 text-gray-300 mb-1 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-[10px] text-gray-400 font-medium">Click to upload</span>
                                 </div>`
                    }
                            <input type="file" name="${id}" class="hidden" ${isRequired ? 'required' : ''} onchange="previewConditionPhoto(this)">
                        </div>
                    </div>
                `;
            };

            const content = document.getElementById('bookingModalContent');
            if (!content) return;

            content.innerHTML = `
                <div class="flex items-center justify-center mb-8">
                    <div class="flex h-11 w-fit max-w-full items-center rounded-full border border-gray-100 bg-gray-50/50 p-1 select-none backdrop-blur-sm">
                        <button onclick="switchModalTab('details')" id="tab-btn-details" class="flex cursor-pointer items-center gap-2 rounded-full px-5 py-1.5 font-bold whitespace-nowrap transition-all text-[11px] uppercase tracking-widest bg-white shadow-sm text-blue-600">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Information
                        </button>
                        <button onclick="switchModalTab('condition')" id="tab-btn-condition" class="flex cursor-pointer items-center gap-2 rounded-full px-5 py-1.5 font-bold whitespace-nowrap transition-all text-[11px] uppercase tracking-widest text-[#4b5058] hover:text-black">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Condition
                        </button>
                        <button onclick="switchModalTab('contract')" id="tab-btn-contract" class="flex cursor-pointer items-center gap-2 rounded-full px-5 py-1.5 font-bold whitespace-nowrap transition-all text-[11px] uppercase tracking-widest text-[#4b5058] hover:text-black">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Contract
                        </button>
                    </div>
                </div>

                <div id="tab-content-details" class="tab-pane space-y-8 animate-fade-in-up">
                    <div class="grid md:grid-cols-12 gap-6">
                        <div class="md:col-span-8 space-y-6">
                            <div class="bg-white rounded-[2rem] p-8 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(0,0,0,0.03)]">
                                <div class="flex items-center justify-between mb-6">
                                    <div class="flex items-center gap-3">
                                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Ref</span>
                                        <p class="text-xl font-black text-gray-900 leading-none">#${String(booking.id).padStart(5, '0')}</p>
                                    </div>
                                    <div class="flex gap-2">
                                        <span class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-tighter ${statusMap[booking.status] || 'bg-gray-100 text-gray-800'}">
                                            ${booking.status}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-6 p-4 bg-gray-50/50 rounded-2xl border border-gray-100/50 mb-8">
                                    <div class="flex-1 text-center">
                                        <p class="text-[9px] font-black text-blue-500 uppercase tracking-widest mb-1">Pickup</p>
                                        <p class="text-base font-black text-gray-900">${new Date(booking.pickup_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}</p>
                                    </div>
                                    <div class="flex flex-col items-center gap-1">
                                        <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 8l4 4m0 0l-4 4m4-4H3" stroke-width="2.5" /></svg>
                                        <span class="text-[9px] font-black text-gray-300 uppercase tracking-tighter">${booking.total_days} Days</span>
                                    </div>
                                    <div class="flex-1 text-center">
                                        <p class="text-[9px] font-black text-orange-500 uppercase tracking-widest mb-1">Return</p>
                                        <p class="text-base font-black text-gray-900">${new Date(booking.return_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="p-5 bg-gray-50/30 rounded-2xl border border-gray-100/50 group hover:bg-white hover:border-blue-100 transition-all">
                                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-[0.2em] mb-2">Vehicle</p>
                                        <p class="text-sm font-black text-gray-900 leading-tight small-vechicle-fix">${booking.brand} ${booking.model}</p>
                                        <p class="text-[10px] text-gray-400 mt-1">${booking.year} • ${(booking.category || '').toUpperCase()}</p>
                                    </div>
                                    <div class="p-5 bg-gray-50/30 rounded-2xl border border-gray-100/50 group hover:bg-white hover:border-blue-100 transition-all">
                                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-[0.2em] mb-2">Customer</p>
                                        <p class="text-sm font-black text-gray-900 leading-tight truncate">${booking.customer_name}</p>
                                        <p class="text-[10px] text-gray-400 mt-1 truncate">${booking.customer_email}</p>
                                    </div>
                                </div>

                                <div class="mt-6 grid grid-cols-3 gap-4 pt-6 border-t border-gray-50">
                                    <div>
                                        <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest mb-1">Price / Day</p>
                                        <p class="text-sm font-black text-gray-900">£${parseFloat(booking.price_per_day).toLocaleString()}</p>
                                    </div>
                                    <div>
                                        <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest mb-1">Tax Amount</p>
                                        <p class="text-sm font-black text-gray-900">£${parseFloat(booking.tax_amount || 0).toLocaleString()}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[8px] font-black text-blue-500 uppercase tracking-widest mb-1">Grand Total</p>
                                        <p class="text-sm font-black text-blue-600">£${parseFloat(booking.total_price).toLocaleString()}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="md:col-span-4 flex flex-col gap-4">
                            <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Quick Actions</h4>
                            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(0,0,0,0.03)] space-y-3">
                                <button onclick="updateBookingStatus(${booking.id}, 'confirmed')" class="w-full py-4 bg-gray-900 text-white rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-xl shadow-gray-200 hover:bg-black hover:-translate-y-0.5 transition-all">Confirm</button>
                                <button onclick="updateBookingStatus(${booking.id}, 'active')" class="w-full py-4 bg-blue-600 text-white rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-xl shadow-blue-200 hover:bg-blue-700 hover:-translate-y-0.5 transition-all">Start Trip</button>
                                <div class="pt-4 mt-2 border-t border-gray-50">
                                    <button onclick="updateBookingStatus(${booking.id}, 'cancelled')" class="w-full py-3 bg-white text-red-500 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] border border-red-50 hover:bg-red-50 transition-all">Void Agreement</button>
                                </div>
                            </div>

                            ${booking.notes ? `
                                <div class="bg-amber-50/50 p-6 rounded-3xl border border-amber-100 group shadow-sm">
                                    <div class="flex items-center gap-2 mb-2">
                                        <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2.5" /></svg>
                                        <p class="text-[9px] font-black text-amber-500 uppercase tracking-widest">Internal Notes</p>
                                    </div>
                                    <p class="text-xs text-amber-900/80 font-bold italic leading-relaxed">"${booking.notes}"</p>
                                </div>
                            ` : ''}
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="flex items-center justify-between ml-2">
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                <h4 class="text-[11px] font-black text-gray-900 uppercase tracking-widest">Financial Guarantee</h4>
                            </div>
                            <div class="px-3 py-1 bg-blue-50 rounded-full border border-blue-100 text-[9px] font-black text-blue-600 uppercase tracking-tighter shadow-sm">Securing Assets</div>
                        </div>
                        <div class="bg-white rounded-[2.5rem] border border-gray-100 shadow-[0_15px_40px_-20px_rgba(0,0,0,0.08)] p-8 space-y-10 border-t-2 border-t-blue-500/10">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                                <div class="space-y-3">
                                    <label class="block text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] ml-2">Amount Secured</label>
                                    <div class="relative group">
                                        <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none text-gray-900 font-black text-sm">£</div>
                                        <input type="number" id="deposit-amount" value="${booking.security_deposit || 0}" class="w-full bg-gray-50/50 border border-transparent rounded-3xl pl-10 pr-6 py-5 text-sm font-black focus:bg-white focus:border-blue-500/20 focus:ring-8 focus:ring-blue-500/5 transition-all outline-none shadow-inner">
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    <label class="block text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] ml-2">Status Tracking</label>
                                    <div class="relative">
                                        <select id="deposit-status" class="w-full bg-gray-50/50 border border-transparent rounded-3xl pl-6 pr-10 py-5 text-[10px] font-black uppercase tracking-[0.2em] focus:bg-white focus:border-blue-500/20 transition-all outline-none appearance-none shadow-inner cursor-pointer">
                                            <option value="unpaid" ${booking.security_deposit_status === 'unpaid' ? 'selected' : ''}>⚠️ Outstanding</option>
                                            <option value="paid" ${booking.security_deposit_status === 'paid' ? 'selected' : ''}>💎 Fully Paid</option>
                                            <option value="refunded" ${booking.security_deposit_status === 'refunded' ? 'selected' : ''}>🔄 Refunded</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-5 flex items-center pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="3" /></svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    <label class="block text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] ml-2">Payment Channel</label>
                                    <div class="relative">
                                        <select id="deposit-method" class="w-full bg-gray-50/50 border border-transparent rounded-3xl pl-6 pr-10 py-5 text-[10px] font-black uppercase tracking-[0.2em] focus:bg-white focus:border-blue-500/20 transition-all outline-none appearance-none shadow-inner cursor-pointer">
                                            <option value="" ${!booking.security_deposit_method ? 'selected' : ''}>Not Set</option>
                                            <option value="cash" ${booking.security_deposit_method === 'cash' ? 'selected' : ''}>💵 Cash Deposit</option>
                                            <option value="card" ${booking.security_deposit_method === 'card' ? 'selected' : ''}>💳 Terminal</option>
                                            <option value="stripe" ${booking.security_deposit_method === 'stripe' ? 'selected' : ''}>🌍 Online Payment</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-5 flex items-center pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="3" /></svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button onclick="updateSecurityDeposit(${booking.id})" class="w-full py-6 bg-blue-600 text-white rounded-[2rem] font-black text-[12px] uppercase tracking-[0.4em] shadow-2xl shadow-blue-500/20 hover:bg-blue-700 hover:scale-[1.01] active:scale-95 transition-all flex items-center justify-center gap-5 group/btn">
                                <span class="bg-white/20 p-2 rounded-xl group-hover/btn:rotate-12 transition-transform">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-width="3" /></svg>
                                </span>
                                Synchronize Deposit Data
                            </button>
                        </div>
                    </div>
                </div>

                <div id="tab-content-condition" class="tab-pane hidden space-y-6">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-black text-gray-900 uppercase tracking-tighter">Condition Report</h4>
                        <div class="flex p-1 bg-gray-100 rounded-xl">
                            <button type="button" onclick="switchConditionTab('pickup')" id="tab-pickup-btn" class="px-4 py-1.5 text-[10px] font-black rounded-lg transition-all bg-white shadow-sm text-blue-600 uppercase">Pickup</button>
                            <button type="button" onclick="switchConditionTab('return')" id="tab-return-btn" class="px-4 py-1.5 text-[10px] font-black rounded-lg transition-all text-gray-500 hover:text-gray-700 uppercase">Return</button>
                        </div>
                    </div>

                    <div id="section-pickup" class="space-y-6">
                        <form onsubmit="submitConditionReport(event, 'pickup', ${booking.id})">
                            <div class="bg-gray-50 rounded-3xl p-6 space-y-8 border border-gray-100">
                                <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
                                     <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Mileage at Pickup</label>
                                     <input type="number" name="mileage" value="${pickupReport ? pickupReport.mileage : ''}" required class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm font-bold focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none" placeholder="00,000">
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-gray-900 uppercase tracking-widest mb-4 ml-1">Vehicle Photos</p>
                                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                                        ${renderPhotoField('photo_front', 'Front View', '', pickupReport ? pickupReport.photo_front : '', 'pickup')}
                                        ${renderPhotoField('photo_back', 'Rear View', '', pickupReport ? pickupReport.photo_back : '', 'pickup')}
                                        ${renderPhotoField('photo_left', 'Left Side', '', pickupReport ? pickupReport.photo_left : '', 'pickup')}
                                        ${renderPhotoField('photo_right', 'Right Side', '', pickupReport ? pickupReport.photo_right : '', 'pickup')}
                                    </div>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-gray-900 uppercase tracking-widest mb-4 ml-1">Alloys & Rims</p>
                                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                                        ${renderPhotoField('photo_rim1', 'Front Left', '', pickupReport ? pickupReport.photo_rim1 : '', 'pickup')}
                                        ${renderPhotoField('photo_rim2', 'Front Right', '', pickupReport ? pickupReport.photo_rim2 : '', 'pickup')}
                                        ${renderPhotoField('photo_rim3', 'Rear Left', '', pickupReport ? pickupReport.photo_rim3 : '', 'pickup')}
                                        ${renderPhotoField('photo_rim4', 'Rear Right', '', pickupReport ? pickupReport.photo_rim4 : '', 'pickup')}
                                    </div>
                                </div>
                                <div class="pt-6 border-t border-gray-200">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4 ml-1 italic">Additional Photos (up to 4)</p>
                                    <div class="grid grid-cols-4 gap-4 mb-4" id="misc-pickup-preview">
                                        ${prepareMiscPhotos(pickupReport)}
                                    </div>
                                    <label class="block">
                                        <span class="sr-only">Upload images</span>
                                        <input type="file" name="misc_photos[]" multiple accept="image/*" onchange="previewMiscPhotos(this, 'pickup')" class="block w-full text-[10px] text-gray-400 file:mr-4 file:py-2 file:px-6 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:bg-gray-900 file:text-white hover:file:bg-black transition-all cursor-pointer">
                                    </label>
                                </div>
                                <button type="submit" class="w-full py-4 bg-gray-900 text-white rounded-3xl font-black text-xs uppercase tracking-[0.2em] shadow-2xl shadow-gray-200 hover:scale-[1.02] transition-all">Update Pickup Status</button>
                            </div>
                        </form>
                    </div>

                    <div id="section-return" class="hidden space-y-6">
                        <form onsubmit="submitConditionReport(event, 'return', ${booking.id})">
                            <div class="bg-gray-50 rounded-3xl p-6 space-y-8 border border-gray-100">
                                <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
                                     <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Mileage at Return</label>
                                     <input type="number" name="mileage" value="${returnReport ? returnReport.mileage : ''}" required class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm font-bold focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none" placeholder="00,000">
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-gray-900 uppercase tracking-widest mb-4 ml-1">Vehicle Photos</p>
                                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                                        ${renderPhotoField('photo_front', 'Front View', '', returnReport ? returnReport.photo_front : '', 'return')}
                                        ${renderPhotoField('photo_back', 'Rear View', '', returnReport ? returnReport.photo_back : '', 'return')}
                                        ${renderPhotoField('photo_left', 'Left Side', '', returnReport ? returnReport.photo_left : '', 'return')}
                                        ${renderPhotoField('photo_right', 'Right Side', '', returnReport ? returnReport.photo_right : '', 'return')}
                                    </div>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-gray-900 uppercase tracking-widest mb-4 ml-1">Alloys & Rims</p>
                                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                                        ${renderPhotoField('photo_rim1', 'Front Left', '', returnReport ? returnReport.photo_rim1 : '', 'return')}
                                        ${renderPhotoField('photo_rim2', 'Front Right', '', returnReport ? returnReport.photo_rim2 : '', 'return')}
                                        ${renderPhotoField('photo_rim3', 'Rear Left', '', returnReport ? returnReport.photo_rim3 : '', 'return')}
                                        ${renderPhotoField('photo_rim4', 'Rear Right', '', returnReport ? returnReport.photo_rim4 : '', 'return')}
                                    </div>
                                </div>
                                <div class="pt-6 border-t border-gray-200">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4 ml-1 italic">Additional Photos (up to 4)</p>
                                    <div class="grid grid-cols-4 gap-4 mb-4" id="misc-return-preview">
                                        ${prepareMiscPhotos(returnReport)}
                                    </div>
                                    <label class="block">
                                        <span class="sr-only">Upload images</span>
                                        <input type="file" name="misc_photos[]" multiple accept="image/*" onchange="previewMiscPhotos(this, 'return')" class="block w-full text-[10px] text-gray-400 file:mr-4 file:py-2 file:px-6 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:bg-gray-900 file:text-white hover:file:bg-black transition-all cursor-pointer">
                                    </label>
                                </div>
                                <button type="submit" class="w-full py-4 bg-blue-600 text-white rounded-3xl font-black text-xs uppercase tracking-[0.2em] shadow-2xl shadow-blue-200 hover:scale-[1.02] transition-all">Finalize Return Condition</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="tab-content-contract" class="tab-pane hidden space-y-6">
                    <div class="bg-gray-50 rounded-3xl p-8 border border-gray-100">
                        ${contract ? `
                            <div class="flex items-center justify-between mb-8 pb-8 border-b border-gray-200">
                                <div class="flex items-center gap-5">
                                    <div class="w-16 h-16 bg-white rounded-2xl shadow-xl border border-gray-100 flex items-center justify-center">
                                        <svg class="w-8 h-8 ${contract.contract_status === 'signed' ? 'text-green-500' : 'text-amber-500'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="2.5" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-base font-black text-gray-900 uppercase tracking-tighter">Agreement</h4>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="w-2.5 h-2.5 rounded-full ${contract.contract_status === 'signed' ? 'bg-green-500' : 'bg-amber-500 animate-pulse'}"></span>
                                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                                Status: <span class="${contract.contract_status === 'signed' ? 'text-green-600' : 'text-amber-600'}">${contract.contract_status.toUpperCase()}</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <button onclick="viewContractPDF(${booking.id})" class="flex items-center gap-2 px-6 py-3 bg-white border border-gray-200 text-gray-900 rounded-2xl hover:bg-gray-50 transition-all text-xs font-black uppercase tracking-widest shadow-sm group">
                                        <svg class="w-4 h-4 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-width="2.5"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-width="2.5"/></svg>
                                        View Agreement
                                    </button>
                                    ${contract.contract_status === 'signed' ? `
                                        <a href="/api/download-contract.php?booking_id=${booking.id}" target="_blank" class="flex items-center gap-2 px-6 py-3 bg-gray-900 text-white rounded-2xl hover:bg-black transition-all text-xs font-black uppercase tracking-widest shadow-2xl shadow-gray-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 4v12m0 0l-4-4m4 4l4-4" stroke-width="2.5" /></svg>
                                            Export PDF
                                        </a>
                                    ` : `
                                        <div class="px-5 py-2.5 bg-amber-50 text-amber-600 rounded-xl border border-amber-200 text-[10px] font-black uppercase tracking-widest">Awaiting Signature</div>
                                    `}
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
                                    <div>
                                        <p class="text-[9px] text-gray-400 font-black uppercase tracking-[0.2em] mb-1">Execution Detail</p>
                                        <p class="text-xs font-bold text-gray-900">${contract.contract_status === 'signed' ? 'Signed successfully' : 'Invitation sent to client'}</p>
                                    </div>
                                </div>

                                ${contract.contract_status !== 'signed' ? `
                                <div class="p-6 bg-blue-50/50 rounded-2xl border border-blue-100">
                                    <p class="text-xs text-blue-900 font-black uppercase tracking-[0.1em] mb-3">Quick Link</p>
                                    <div class="flex items-center gap-3">
                                        <input type="text" value="${window.location.origin}/templates/contract-sign.php?booking_id=${booking.id}&token=${contract.signing_token}" readonly 
                                               class="flex-1 bg-white border border-blue-100 rounded-xl px-4 py-3 text-[10px] text-gray-500 font-mono shadow-sm">
                                        <button onclick="copyContractLink(this)" class="p-3.5 bg-blue-600 text-white hover:bg-blue-700 rounded-xl transition-all shadow-xl shadow-blue-200">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" stroke-width="2.5" /></svg>
                                        </button>
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                        ` : `
                            <div class="text-center py-12">
                                <div class="w-24 h-24 bg-gray-100 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-inner">
                                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="2" /></svg>
                                </div>
                                <h4 class="text-lg font-black text-gray-900 uppercase tracking-tighter mb-2">No Active Contract</h4>
                                <p class="text-xs text-gray-400 mb-10 max-w-[280px] mx-auto font-bold leading-relaxed">This booking does not have an electronic agreement. you can send one manually below.</p>
                                
                                ${available_templates && available_templates.length > 0 ? `
                                    <div class="max-w-xs mx-auto space-y-5">
                                        <div class="text-left bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Template Selection</label>
                                            <select id="selected-template-id" class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-xs font-bold focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none">
                                                ${available_templates.map(tmpl => `<option value="${tmpl.id}">${tmpl.name}</option>`).join('')}
                                            </select>
                                        </div>
                                        <button onclick="generateManualContract(${booking.id})" id="send-contract-btn" class="w-full py-4 bg-gray-900 text-white rounded-2xl hover:bg-black transition-all text-xs font-black uppercase tracking-[0.2em] shadow-2xl shadow-gray-200 flex items-center justify-center gap-3">
                                             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                             Send Invitation
                                        </button>
                                    </div>
                                ` : `
                                    <div class="bg-amber-50 p-6 rounded-2xl border border-amber-100 font-bold text-amber-700 text-xs italic">No published templates found.</div>
                                `}
                            </div>
                        `}
                    </div>
                </div>
            `;

            // Initialize custom selects for dynamically injected content
            if (window.initCustomSelects) {
                setTimeout(() => window.initCustomSelects(), 0);
            }
        }

        function switchModalTab(tabId) {
            document.querySelectorAll('.tab-pane').forEach(el => {
                el.classList.add('hidden');
                el.classList.remove('animate-fade-in-up');
            });

            const target = document.getElementById('tab-content-' + tabId);
            if (!target) return;
            target.classList.remove('hidden');
            target.classList.add('animate-fade-in-up');

            document.querySelectorAll('[id^="tab-btn-"]').forEach(btn => {
                btn.classList.add('text-[#4b5058]', 'hover:text-black');
                btn.classList.remove('bg-white', 'shadow-sm', 'text-blue-600');
            });

            const activeBtn = document.getElementById('tab-btn-' + tabId);
            if (activeBtn) {
                activeBtn.classList.remove('text-[#4b5058]', 'hover:text-black');
                activeBtn.classList.add('bg-white', 'shadow-sm', 'text-blue-600');
            }
        }

        function copyContractLink(btn) {
            const input = btn.previousElementSibling;
            if (!input) return;
            input.select();
            document.execCommand('copy');

            const originalSvg = btn.innerHTML;
            btn.innerHTML = '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="3" /></svg>';
            btn.classList.remove('bg-blue-600');
            btn.classList.add('bg-green-500');

            setTimeout(() => {
                btn.innerHTML = originalSvg;
                btn.classList.remove('bg-green-500');
                btn.classList.add('bg-blue-600');
            }, 2000);
        }

        function closeBookingModal() {
            const modal = document.getElementById('bookingModal');
            if (modal) modal.classList.add('hidden');
        }

        async function generateManualContract(bookingId) {
            const templateIdEl = document.getElementById('selected-template-id');
            const btn = document.getElementById('send-contract-btn');
            if (!templateIdEl || !btn) return;

            const templateId = templateIdEl.value;
            if (!templateId) {
                showNotification('Please select a template', 'error');
                return;
            }

            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div> Sending...';

            try {
                const response = await fetch('/dashboard/generate-manual-contract.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ booking_id: bookingId, template_id: templateId })
                });

                const data = await response.json();

                if (data.success) {
                    showNotification(data.message, 'success');
                    openBookingModal(bookingId, 'contract');
                } else {
                    showNotification(data.message, 'error');
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                }
            } catch (error) {
                showNotification('Failed to generate contract', 'error');
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        }

        function updateBookingStatus(bookingId, status) {
            showConfirmation(
                'Update Booking Status',
                'Are you sure you want to update this booking status?',
                () => {
                    fetch('/dashboard/update-booking-status.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ booking_id: bookingId, status: status })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification('Booking status updated successfully', 'success');
                                closeBookingModal();
                                setTimeout(() => location.reload(), 1000);
                            } else {
                                showNotification('Error: ' + data.message, 'error');
                            }
                        })
                        .catch(() => {
                            showNotification('Error updating booking status', 'error');
                        });
                },
                'Update Status',
                'bg-blue-600 hover:bg-blue-700'
            );
        }

        function deleteSingleBooking(bookingId) {
            showConfirmation(
                'Delete Booking',
                'Are you sure you want to move this booking to deleted? You can restore it later from the Deleted view.',
                () => {
                    fetch('/dashboard/delete-booking.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ booking_ids: [bookingId] })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification('Booking moved to deleted');
                                closeBookingModal();
                                setTimeout(() => window.location.reload(), 1000);
                            } else {
                                showNotification(data.message || 'Error deleting booking', 'error');
                            }
                        })
                        .catch(() => {
                            showNotification('An error occurred while deleting the booking', 'error');
                        });
                },
                'Delete Booking',
                'bg-red-600 hover:bg-red-700'
            );
        }

        function restoreSingleBooking(bookingId) {
            showConfirmation(
                'Restore Booking',
                'Are you sure you want to restore this booking to the active list?',
                () => {
                    fetch('/dashboard/restore-booking.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ booking_ids: [bookingId] })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification('Booking restored successfully');
                                closeBookingModal();
                                setTimeout(() => window.location.reload(), 1000);
                            } else {
                                showNotification(data.message || 'Error restoring booking', 'error');
                            }
                        })
                        .catch(() => {
                            showNotification('An error occurred while restoring the booking', 'error');
                        });
                },
                'Restore Booking',
                'bg-blue-600 hover:bg-blue-700'
            );
        }

        const bookingModalEl = document.getElementById('bookingModal');
        if (bookingModalEl) {
            bookingModalEl.addEventListener('click', function (e) {
                if (e.target === this) {
                    closeBookingModal();
                }
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeBookingModal();
            }
        });

        function switchConditionTab(type) {
            const pickupBtn = document.getElementById('tab-pickup-btn');
            const returnBtn = document.getElementById('tab-return-btn');
            const pickupSec = document.getElementById('section-pickup');
            const returnSec = document.getElementById('section-return');

            const activeClass = 'bg-white shadow-sm text-blue-600';
            const inactiveClass = 'text-[#4b5058] hover:text-black';

            pickupSec?.classList.remove('animate-fade-in-up');
            returnSec?.classList.remove('animate-fade-in-up');

            if (type === 'pickup') {
                pickupBtn && (pickupBtn.className = `flex-1 py-1.5 text-[10px] font-black rounded-lg transition-all ${activeClass} uppercase`);
                returnBtn && (returnBtn.className = `flex-1 py-1.5 text-[10px] font-black rounded-lg transition-all ${inactiveClass} uppercase`);
                pickupSec?.classList.remove('hidden');
                returnSec?.classList.add('hidden');
                void pickupSec?.offsetWidth;
                pickupSec?.classList.add('animate-fade-in-up');
            } else {
                returnBtn && (returnBtn.className = `flex-1 py-1.5 text-[10px] font-black rounded-lg transition-all ${activeClass} uppercase`);
                pickupBtn && (pickupBtn.className = `flex-1 py-1.5 text-[10px] font-black rounded-lg transition-all ${inactiveClass} uppercase`);
                returnSec?.classList.remove('hidden');
                pickupSec?.classList.add('hidden');
                void returnSec?.offsetWidth;
                returnSec?.classList.add('animate-fade-in-up');
            }
        }

        function previewConditionPhoto(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                const container = input.parentElement;

                reader.onload = function (e) {
                    let img = container.querySelector('img');
                    if (!img) {
                        const placeholder = container.querySelector('.text-center');
                        placeholder && placeholder.classList.add('hidden');

                        img = document.createElement('img');
                        img.className = 'w-full h-full object-cover';
                        container.appendChild(img);
                    }
                    img.src = e.target.result;
                    const wrapper = container.closest('.condition-photo-wrapper');
                    if (wrapper) wrapper.dataset.hasPhoto = 'true';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function previewMiscPhotos(input, type) {
            const container = document.getElementById(`misc-${type}-preview`);
            if (!container) return;

            const files = Array.from(input.files || []).slice(0, 4);
            container.innerHTML = '';

            for (let i = 0; i < 4; i++) {
                const div = document.createElement('div');
                div.className = 'aspect-square rounded-xl bg-gray-50 border-2 border-dashed border-gray-200 flex items-center justify-center overflow-hidden';

                if (files[i]) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        div.className = 'aspect-square rounded-xl bg-gray-100 overflow-hidden shadow-sm border border-gray-100';
                        div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                    };
                    reader.readAsDataURL(files[i]);
                    container.appendChild(div);
                } else {
                    div.innerHTML = `<svg class="w-5 h-5 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="2.5" stroke-linecap="round"></path></svg>`;
                    container.appendChild(div);
                }
            }
        }

        function showValidationError(message) {
            const overlay = document.createElement('div');
            overlay.className = 'fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4';
            overlay.innerHTML = `
        <div class="bg-white rounded-2xl max-w-sm w-full shadow-2xl overflow-hidden transform transition-all">
            <div class="p-6 text-center">
                <div class="w-16 h-16 bg-red-50 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Upload Required</h3>
                <p class="text-gray-600 text-sm mb-6">${message}</p>
                <button onclick="this.closest('.fixed').remove()" class="w-full py-3 bg-gray-900 text-white rounded-xl font-bold hover:bg-black transition-all">Got it</button>
            </div>
        </div>
    `;
            document.body.appendChild(overlay);
        }

        function submitConditionReport(event, type, bookingId) {
            event.preventDefault();
            const form = event.target;

            if (type === 'pickup') {
                const photoFields = ['photo_front', 'photo_back', 'photo_left', 'photo_right', 'photo_rim1', 'photo_rim2', 'photo_rim3', 'photo_rim4'];
                const missing = [];
                photoFields.forEach(field => {
                    const wrapper = form.querySelector(`.condition-photo-wrapper[data-id="${field}"]`);
                    const input = form.querySelector(`input[name="${field}"]`);
                    const hasExisting = wrapper && wrapper.dataset.hasPhoto === 'true';
                    const hasNew = input && input.files && input.files.length > 0;

                    if (!hasExisting && !hasNew) {
                        missing.push(field.replace('photo_', '').replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase()));
                    }
                });

                if (missing.length > 0) {
                    showValidationError('Please upload all mandatory photos: ' + missing.join(', ') + '. <br><br>Front, Back, Sides and 4 Rims are required.');
                    return;
                }
            }

            const formData = new FormData(form);
            formData.append('booking_id', bookingId);
            formData.append('report_type', type);

            const btn = form.querySelector('button[type="submit"]');
            const originalText = btn ? btn.innerText : '';
            if (btn) {
                btn.disabled = true;
                btn.innerText = 'Saving...';
            }

            fetch('/dashboard/upload-condition-report.php', {
                method: 'POST',
                body: formData
            })
                .then(async response => {
                    const text = await response.text();
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Server Error: ' + text);
                    }
                })
                .then(data => {
                    if (data.success) {
                        showStatusToast(data.message || 'Report saved successfully');
                        openBookingModal(bookingId);
                    } else {
                        showNotification(data.message || 'Failed to save report', 'error');
                        if (btn) {
                            btn.disabled = false;
                            btn.innerText = originalText;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Upload failed: ' + error.message, 'error');
                    if (btn) {
                        btn.disabled = false;
                        btn.innerText = originalText;
                    }
                });
        }

        function showStatusToast(message) {
            const toast = document.createElement('div');
            toast.className = 'fixed top-6 left-1/2 -translate-x-1/2 bg-gray-900 text-white px-6 py-3 rounded-xl shadow-2xl z-[100] font-bold text-sm tracking-wide transition-all duration-500 opacity-0 -translate-y-4';
            toast.innerText = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.classList.remove('opacity-0', '-translate-y-4');
            }, 10);

            setTimeout(() => {
                toast.classList.add('opacity-0', '-translate-y-4');
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }

        function viewContractPDF(bookingId) {
            const modal = document.getElementById('contractPreviewModal');
            const content = document.getElementById('contractPreviewContent');
            if (!modal || !content) return;

            modal.classList.remove('hidden');
            content.innerHTML = `
        <div class="w-full h-full min-h-[600px] flex flex-col">
            <iframe src="/dashboard/preview-contract.php?booking_id=${bookingId}" class="w-full flex-1 border-0 rounded-xl" style="height: 60vh;"></iframe>
        </div>
    `;
        }

        function closeContractPreviewModal() {
            const modal = document.getElementById('contractPreviewModal');
            if (modal) modal.classList.add('hidden');
        }

        if (typeof window !== 'undefined') {
            const bookingModalGlobals = {
                openBookingModal,
                switchModalTab,
                closeBookingModal,
                copyContractLink,
                generateManualContract,
                updateSecurityDeposit,
                updateBookingStatus,
                deleteSingleBooking,
                restoreSingleBooking,
                switchConditionTab,
                previewConditionPhoto,
                previewMiscPhotos,
                submitConditionReport,
                viewContractPDF,
                closeContractPreviewModal
            };

            Object.entries(bookingModalGlobals).forEach(([key, fn]) => {
                if (typeof fn === 'function') {
                    window[key] = fn;
                }
            });
        }

    </script>

    <script>
            // Pricing Packages Vanilla Array Logistics
            const hiddenInput = document.getElementById('pricing_packages_json');
            const packagesList = document.getElementById('packages-list');
            const addBtn = document.getElementById('add-package-btn');
            
            if (hiddenInput && packagesList && addBtn) {
                let packagesData = [];
                try {
                    packagesData = JSON.parse(hiddenInput.value || '[]');
                } catch (e) {
                    packagesData = [];
                }
                
                function updateHiddenState() {
                    const json = JSON.stringify(packagesData);
                    console.log('SYNCING PACKAGES TO HIDDEN INPUT:', json);
                    hiddenInput.value = json;
                }
                
                window.removeVehiclePackage = function(index) {
                    packagesData.splice(index, 1);
                    renderVehiclePackages();
                    updateHiddenState();
                };
                
                window.updateVehiclePackage = function(index, field, value) {
                    if (field === 'type' && packagesData[index][field] !== value) {
                        packagesData[index].fixed_price = '';
                        packagesData[index].target_day = '';
                        packagesData[index].discount_amount = '';
                    }
                    if (['min_days', 'fixed_price', 'target_day', 'discount_amount', 'start_day', 'end_day'].includes(field)) {
                        packagesData[index][field] = (value !== '' && value !== null) ? Number(value) : '';
                    } else {
                        packagesData[index][field] = value;
                    }
                    if (field === 'type') {
                        renderVehiclePackages();
                    }
                    updateHiddenState();
                };
                
                function renderVehiclePackages() {
                    packagesList.innerHTML = '';
                    packagesData.forEach((pkg, index) => {
                        const isFixed = pkg.type === 'fixed_price';
                        const isDiscount = pkg.type === 'discount_target_day';
                        const isFree = pkg.type === 'free_target_day';
                        
                        let extraHtml = '';
                        if (isFixed) {
                            const dayNames = [{v:1,l:'Monday'},{v:2,l:'Tuesday'},{v:3,l:'Wednesday'},{v:4,l:'Thursday'},{v:5,l:'Friday'},{v:6,l:'Saturday'},{v:7,l:'Sunday'}];
                            const startOpts = dayNames.map(d => `<option value="${d.v}" ${Number(pkg.start_day)===d.v?'selected':''}>${d.l}</option>`).join('');
                            const endOpts = dayNames.map(d => `<option value="${d.v}" ${Number(pkg.end_day)===d.v?'selected':''}>${d.l}</option>`).join('');
                            extraHtml = `<div class="col-span-1 sm:col-span-2 lg:col-span-3 bg-gray-50 border border-gray-100 p-4 rounded-lg mt-2">
                                <div class="grid gap-4 grid-cols-1 sm:grid-cols-3">
                                    <div><label class="block text-sm font-medium text-gray-700 mb-2">Pickup Day</label><select onchange="updateVehiclePackage(${index}, 'start_day', this.value)" class="w-full px-3 py-2 border border-gray-300 bg-white rounded-lg focus:ring-2 focus:ring-gray-300 focus:border-gray-300 text-sm"><option value="">Any day</option>${startOpts}</select></div>
                                    <div><label class="block text-sm font-medium text-gray-700 mb-2">Return Day</label><select onchange="updateVehiclePackage(${index}, 'end_day', this.value)" class="w-full px-3 py-2 border border-gray-300 bg-white rounded-lg focus:ring-2 focus:ring-gray-300 focus:border-gray-300 text-sm"><option value="">Any day</option>${endOpts}</select></div>
                                    <div><label class="block text-sm font-medium text-gray-700 mb-2">Total Fixed Price (GBP)</label><input type="number" step="0.01" value="${pkg.fixed_price || ''}" onkeyup="updateVehiclePackage(${index}, 'fixed_price', this.value)" onchange="updateVehiclePackage(${index}, 'fixed_price', this.value)" placeholder="E.g. 150" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-300 focus:border-gray-300 text-sm"></div>
                                </div>
                            </div>`;
                        } else if (isDiscount) {
                            extraHtml = `<div class="col-span-1 sm:col-span-2 lg:col-span-3 bg-gray-50 border border-gray-100 p-4 rounded-lg mt-2 grid gap-5 grid-cols-1 sm:grid-cols-2"><div><label class="block text-sm font-medium text-gray-700 mb-2">Target Day (e.g. 4 for 4th day)</label><input type="number" min="1" value="${pkg.target_day || ''}" onkeyup="updateVehiclePackage(${index}, 'target_day', this.value)" onchange="updateVehiclePackage(${index}, 'target_day', this.value)" placeholder="E.g. 4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-300 focus:border-gray-300 text-sm"></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Discount Amount</label><div class="flex"><input type="number" step="0.01" value="${pkg.discount_amount || ''}" onkeyup="updateVehiclePackage(${index}, 'discount_amount', this.value)" onchange="updateVehiclePackage(${index}, 'discount_amount', this.value)" placeholder="E.g. 40" class="w-full px-3 py-2 border border-gray-300 border-r-0 rounded-l-lg focus:ring-2 focus:ring-gray-300 focus:border-gray-300 text-sm"><select onchange="updateVehiclePackage(${index}, 'discount_type', this.value)" class="px-3 py-2 border border-gray-300 rounded-r-lg bg-gray-100 text-sm"><option value="fixed" ${pkg.discount_type === 'fixed' ? 'selected' : ''}>GBP Off</option><option value="percentage" ${pkg.discount_type === 'percentage' ? 'selected' : ''}>% Off</option></select></div></div></div>`;
                        } else if (isFree) {
                            extraHtml = `<div class="col-span-1 sm:col-span-2 lg:col-span-3 bg-gray-50 border border-gray-100 p-4 rounded-lg mt-2"><label class="block text-sm font-medium text-gray-700 mb-2">Target Free Day (e.g. 2 for 2nd day)</label><input type="number" min="1" value="${pkg.target_day || ''}" onkeyup="updateVehiclePackage(${index}, 'target_day', this.value)" onchange="updateVehiclePackage(${index}, 'target_day', this.value)" placeholder="E.g. 2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-300 focus:border-gray-300 text-sm"><p class="text-xs text-gray-500 mt-2">If specifically the Xth day is hit by the booking, it becomes 100% free of charge.</p></div>`;
                        }

                        const html = `<div class="border border-gray-200 rounded-lg p-5 bg-white relative shadow-sm"><button type="button" onclick="removeVehiclePackage(${index})" class="absolute top-3 right-3 p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button><div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5"><div class="col-span-1 sm:col-span-2 lg:col-span-1"><label class="block text-sm font-medium text-gray-700 mb-2">Package Name</label><input type="text" value="${pkg.name || ''}" onkeyup="updateVehiclePackage(${index}, 'name', this.value)" onchange="updateVehiclePackage(${index}, 'name', this.value)" placeholder="E.g. Weekend Special..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-300 focus:border-gray-300 text-sm"></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Rule Type</label><select onchange="updateVehiclePackage(${index}, 'type', this.value)" class="w-full px-3 py-2 border border-gray-300 bg-white rounded-lg focus:ring-2 focus:ring-gray-300 focus:border-gray-300 text-sm"><option value="fixed_price" ${pkg.type === 'fixed_price' ? 'selected' : ''}>Day of Week Package</option><option value="discount_target_day" ${pkg.type === 'discount_target_day' ? 'selected' : ''}>Discount on Specific Day</option><option value="free_target_day" ${pkg.type === 'free_target_day' ? 'selected' : ''}>Free Specific Day</option></select></div><div><label class="block text-sm font-medium text-gray-700 mb-2">Min. Days</label><input type="number" min="1" value="${pkg.min_days || ''}" onkeyup="updateVehiclePackage(${index}, 'min_days', this.value)" onchange="updateVehiclePackage(${index}, 'min_days', this.value)" placeholder="E.g. 1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-300 focus:border-gray-300 text-sm"></div>${extraHtml}</div></div>`;
                        packagesList.insertAdjacentHTML('beforeend', html);
                    });
                    if (typeof window.initCustomSelects === 'function') window.initCustomSelects();
                }
                
                addBtn.addEventListener('click', () => {
                    packagesData.push({ name: '', type: 'fixed_price', min_days: 1, fixed_price: '', target_day: '', discount_amount: '', discount_type: 'fixed', start_day: '', end_day: '' });
                    renderVehiclePackages();
                    updateHiddenState();
                });
                renderVehiclePackages();

                // Final sync before submit
                const form = document.querySelector('form');
                if (form) {
                    form.addEventListener('submit', () => {
                        updateHiddenState();
                    });
                }
            }


            // Make/Model Dropdown Logic
            const carModels = {
                "Acura": ["Integra", "MDX", "RDX", "TLX", "ZDX"],
                "Alfa Romeo": ["Giulia", "Stelvio", "Tonale", "Junior", "33 Stradale"],
                "Aston Martin": ["DB12", "DBX", "Vantage", "Valhalla"],
                "Audi": ["A1", "A3", "A4", "A5", "A6", "A7", "A8", "Q2", "Q3", "Q4 e-tron", "Q5", "Q6 e-tron", "Q7", "Q8", "Q8 e-tron", "e-tron GT"],
                "Bentley": ["Bentayga", "Continental GT", "Flying Spur"],
                "BMW": ["1 Series", "2 Series", "3 Series", "4 Series", "5 Series", "7 Series", "8 Series", "i4", "i5", "i7", "iX", "iX1", "iX2", "iX3", "X1", "X2", "X3", "X4", "X5", "X6", "X7", "XM", "Z4"],
                "Buick": ["Enclave", "Encore GX", "Envision", "Envista"],
                "Cadillac": ["CT4", "CT5", "Celestiq", "Escalade", "Lyriq", "Optiq", "XT4", "XT5", "XT6"],
                "Chevrolet": ["Blazer", "Blazer EV", "Colorado", "Corvette", "Equinox", "Equinox EV", "Malibu", "Silverado", "Silverado EV", "Suburban", "Tahoe", "Trailblazer", "Trax"],
                "Chrysler": ["Pacifica", "Voyager"],
                "Citroën": ["Ami", "Berlingo", "C3", "C3 Aircross", "C4", "C4 X", "C5 Aircross", "C5 X"],
                "Dacia": ["Duster", "Jogger", "Logan", "Sandero", "Spring"],
                "Dodge": ["Challenger", "Charger", "Durango", "Hornet"],
                "Ferrari": ["12Cilindri", "296 GTB", "296 GTS", "812 GTS", "Daytona SP3", "Purosangue", "Roma", "SF90 Stradale"],
                "Fiat": ["500", "500X", "500e", "600e", "Panda", "Tipo", "Topolino"],
                "Ford": ["Bronco", "Bronco Sport", "Edge", "Escape", "Expedition", "Explorer", "F-150", "Focus", "Kuga", "Maverick", "Mustang", "Mustang Mach-E", "Puma", "Ranger"],
                "Genesis": ["G70", "G80", "G90", "GV60", "GV70", "GV80"],
                "GMC": ["Acadia", "Canyon", "Hummer EV", "Sierra", "Terrain", "Yukon"],
                "Honda": ["Accord", "City", "Civic", "CR-V", "HR-V", "Jazz", "Odyssey", "Passport", "Pilot", "Prologue", "Ridgeline", "ZR-V"],
                "Hyundai": ["Elantra", "Ioniq 5", "Ioniq 6", "Kona", "Palisade", "Santa Cruz", "Santa Fe", "Sonata", "Tucson", "Venue", "i10", "i20", "i30"],
                "Infiniti": ["Q50", "QX50", "QX55", "QX60", "QX80"],
                "Jaguar": ["E-Pace", "F-Pace", "I-Pace", "XF"],
                "Jeep": ["Avenger", "Compass", "Gladiator", "Grand Cherokee", "Grand Wagoneer", "Recon", "Renegade", "Wagoneer", "Wrangler"],
                "Kia": ["Carnival", "EV6", "EV9", "Forte", "K5", "Niro", "Rio", "Seltos", "Sorento", "Soul", "Sportage", "Telluride"],
                "Lamborghini": ["Huracán", "Revuelto", "Urus"],
                "Land Rover": ["Defender", "Discovery", "Discovery Sport", "Range Rover", "Range Rover Evoque", "Range Rover Sport", "Range Rover Velar"],
                "Lexus": ["ES", "GX", "IS", "LC", "LS", "LX", "NX", "RC", "RX", "RZ", "TX", "UX"],
                "Lincoln": ["Aviator", "Corsair", "Nautilus", "Navigator"],
                "Maserati": ["Ghibli", "GranTurismo", "Grecale", "Levante", "MC20", "Quattroporte"],
                "Mazda": ["CX-30", "CX-5", "CX-50", "CX-60", "CX-70", "CX-90", "MX-30", "MX-5", "Mazda3"],
                "McLaren": ["750S", "Artura", "GTS"],
                "Mercedes-Benz": ["A-Class", "AMG GT", "B-Class", "C-Class", "CLA", "CLE", "E-Class", "EQA", "EQB", "EQE", "EQS", "G-Class", "GLA", "GLB", "GLC", "GLE", "GLS", "S-Class", "SL"],
                "Mini": ["Aceman", "Cooper", "Countryman"],
                "Mitsubishi": ["ASX", "Eclipse Cross", "Mirage", "Outlander", "Triton"],
                "Nissan": ["Altima", "Ariya", "Armada", "Frontier", "Juke", "Kicks", "Leaf", "Murano", "Navara", "Pathfinder", "Patrol", "Qashqai", "Rogue", "Sentra", "Versa", "X-Trail", "Z"],
                "Peugeot": ["2008", "208", "3008", "308", "408", "5008", "508"],
                "Polestar": ["Polestar 2", "Polestar 3", "Polestar 4"],
                "Porsche": ["718 Boxster", "718 Cayman", "911", "Cayenne", "Macan", "Panamera", "Taycan"],
                "Ram": ["1500", "2500", "3500", "ProMaster"],
                "Renault": ["Arkana", "Austral", "Captur", "Clio", "Espace", "Kangoo", "Megane E-Tech", "Rafale", "Scenic", "Twingo"],
                "Rolls-Royce": ["Cullinan", "Ghost", "Phantom", "Spectre"],
                "Seat": ["Arona", "Ateca", "Ibiza", "Leon", "Tarraco"],
                "Skoda": ["Enyaq", "Fabia", "Kamiq", "Karoq", "Kodiaq", "Octavia", "Scala", "Superb"],
                "Subaru": ["Ascent", "BRZ", "Crosstrek", "Forester", "Impreza", "Legacy", "Outback", "Solterra", "WRX"],
                "Suzuki": ["Across", "Ignis", "Jimny", "S-Cross", "Swace", "Swift", "Vitara"],
                "Tesla": ["Cybertruck", "Model 3", "Model S", "Model X", "Model Y"],
                "Toyota": ["4Runner", "C-HR", "Camry", "Corolla", "Corolla Cross", "Crown", "GR86", "Highlander", "Land Cruiser", "Prius", "RAV4", "Sequoia", "Sienna", "Supra", "Tacoma", "Tundra", "Venza", "Yaris", "bZ4X"],
                "Vauxhall": ["Astra", "Combo", "Corsa", "Crossland", "Grandland", "Mokka"],
                "Volkswagen": ["Amarok", "Arteon", "Atlas", "Golf", "ID.3", "ID.4", "ID.5", "ID.7", "ID.Buzz", "Jetta", "Passat", "Polo", "T-Cross", "T-Roc", "Taos", "Tiguan", "Touareg"],
                "Volvo": ["C40", "EX30", "EX90", "S60", "S90", "V60", "V90", "XC40", "XC60", "XC90"]
            };
            const makeSelect = document.getElementById('make_select');
            const makeCustom = document.getElementById('make_custom');
            const makeInput = document.getElementById('make_input');
            const modelSelect = document.getElementById('model_select');
            const modelCustom = document.getElementById('model_custom');
            const modelInput = document.getElementById('model_input');

            if (makeSelect && modelSelect) {
                let initialMake = makeInput.value.trim();
                let initialModel = modelInput.value.trim();

                if (initialMake) {
                    if (Array.from(makeSelect.options).some(opt => opt.value === initialMake) && initialMake !== 'Other') {
                        makeSelect.value = initialMake;
                        // Fetch models in background but keep the pre-filled model value
                        populateModels(initialMake, initialModel);
                    } else {
                        makeSelect.value = 'Other';
                        makeCustom.value = initialMake;
                        makeCustom.classList.remove('hidden');
                        modelSelect.disabled = false;
                        if (initialModel) {
                            modelSelect.value = 'Other';
                            modelCustom.value = initialModel;
                            modelCustom.classList.remove('hidden');
                        }
                    }
                }

                makeSelect.addEventListener('change', function() {
                    const selectedMake = this.value;
                    modelCustom.classList.add('hidden');
                    modelCustom.value = '';
                    modelInput.value = '';
                    if (selectedMake === 'Other') {
                        makeCustom.classList.remove('hidden');
                        makeInput.value = makeCustom.value;
                        modelSelect.innerHTML = '<option value="">Select Model</option><option value="Other">Other...</option>';
                        modelSelect.value = 'Other';
                        modelSelect.disabled = false;
                        modelCustom.classList.remove('hidden');
                        modelInput.value = modelCustom.value;
                        if (typeof window.initCustomSelects === 'function') window.initCustomSelects();
                    } else {
                        makeCustom.classList.add('hidden');
                        makeCustom.value = '';
                        makeInput.value = selectedMake;
                        populateModels(selectedMake, null);
                        modelSelect.disabled = selectedMake === '';
                    }
                });
                
                makeCustom.addEventListener('input', function() { makeInput.value = this.value; });
                modelSelect.addEventListener('change', function() {
                    const selectedModel = this.value;
                    if (selectedModel === 'Other') {
                        modelCustom.classList.remove('hidden');
                        modelInput.value = modelCustom.value;
                    } else {
                        modelCustom.classList.add('hidden');
                        modelCustom.value = '';
                        modelInput.value = selectedModel;
                    }
                });
                modelCustom.addEventListener('input', function() { modelInput.value = this.value; });



                function populateModels(make, defaultModel) {
                    if (!make || make === 'Other') return;
                    
                    let models = carModels[make] || [];
                    models.sort();
                    
                    let html = '<option value="">Select Model</option>';
                    models.forEach(m => { html += '<option value="' + m + '">' + m + '</option>'; });
                    html += '<option value="Other">Other...</option>';
                    
                    modelSelect.innerHTML = html;
                    modelSelect.disabled = false;
                    
                    if (defaultModel) {
                        const match = models.find(m => m.toLowerCase() === defaultModel.toLowerCase());
                        if (match) {
                            modelSelect.value = match;
                            modelInput.value = match;
                        } else {
                            const customOpt = new Option(defaultModel, defaultModel, true, true);
                            const otherOpt = modelSelect.querySelector('option[value="Other"]');
                            modelSelect.insertBefore(customOpt, otherOpt);
                            modelSelect.value = defaultModel;
                            modelInput.value = defaultModel;
                        }
                    }
                    
                    if (typeof window.initCustomSelects === 'function') {
                        const wrapperNode = modelSelect.nextElementSibling;
                        if (wrapperNode && wrapperNode.classList.contains('cs-wrapper')) {
                            wrapperNode.remove();
                        }
                        modelSelect.removeAttribute('data-custom-initialized');
                        modelSelect.style.display = '';
                        window.initCustomSelects();
                    }
                }
            }

            // Vehicle Features Tag Input
            const featureInput = document.getElementById('featureInput');
            const featuresHiddenInput = document.getElementById('vehicle_features_input');
            const featuresContainer = document.getElementById('featuresContainer');

            function syncFeatureTags() {
                const tags = Array.from(document.querySelectorAll('#featuresContainer .feature-tag'))
                    .map(tag => tag.childNodes[0].textContent.trim());
                if (featuresHiddenInput) featuresHiddenInput.value = JSON.stringify(tags);
            }

            window.removeFeatureTag = function(btn) {
                btn.parentElement.remove();
                syncFeatureTags();
            };

            function addFeatureTag(val) {
                val = val.replace(/,/g, '').trim();
                if (!val) return;
                const tag = document.createElement('span');
                tag.className = 'feature-tag inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium';
                tag.innerHTML = val + '<button type="button" onclick="removeFeatureTag(this)" class="ml-1 text-blue-500 hover:text-blue-800 font-bold leading-none">&times;</button>';
                if (featureInput) featuresContainer.insertBefore(tag, featureInput);
                syncFeatureTags();
            }

            if (featureInput) {
                featureInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ',') {
                        e.preventDefault();
                        addFeatureTag(this.value);
                        this.value = '';
                    }
                    if (e.key === 'Backspace' && this.value === '') {
                        const tags = document.querySelectorAll('#featuresContainer .feature-tag');
                        if (tags.length) { tags[tags.length - 1].remove(); syncFeatureTags(); }
                    }
                });
                featureInput.addEventListener('blur', function() {
                    if (this.value.trim()) { addFeatureTag(this.value); this.value = ''; }
                });
            }
    </script>
    <?php include __DIR__ . '/../includes/media-selector.php'; ?>
    
    <script>
    function handleLibrarySelection(urls) {
        if (!Array.isArray(urls)) urls = [urls];
        
        urls.forEach(url => {
            // Add to the hidden input list and preview
            addFileToPreview({ name: url.split('/').pop(), type: 'image/library', url: url });
            
            // For saving, we'll need to send these URLs to the server
            // We can add hidden inputs for these
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'library_images[]';
            hidden.value = url;
            document.querySelector('form').appendChild(hidden);
        });
    }

    function addFileToPreview(fileObj) {
        const preview = document.getElementById('imagePreview');
        const count = preview.children.length;
        
        const container = document.createElement('div');
        container.className = 'relative aspect-video rounded-lg overflow-hidden group border border-gray-200';
        
        const img = document.createElement('img');
        img.src = fileObj.url;
        img.className = 'w-full h-full object-cover';
        
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'absolute top-1 right-1 p-1 bg-red-600 text-white rounded-full opacity-0 group-hover:opacity-100 transition shadow-lg';
        removeBtn.innerHTML = '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
        removeBtn.onclick = () => container.remove();
        
        container.appendChild(img);
        container.appendChild(removeBtn);
        preview.appendChild(container);
    }
    
    // Existing handleVehicleFiles logic might need adjustment or call addFileToPreview
    function handleVehicleFiles(input) {
        if (input.files) {
            Array.from(input.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = (e) => addFileToPreview({ name: file.name, type: file.type, url: e.target.result });
                reader.readAsDataURL(file);
            });
        }
    }
    </script>
    <?php include __DIR__ . '/../includes/confirmation-modal.php'; ?>
    <?php include __DIR__ . '/../includes/onboarding-widget.php'; ?>
</body>
</html>
