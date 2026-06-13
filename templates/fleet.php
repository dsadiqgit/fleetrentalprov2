<?php
require_once __DIR__ . '/../includes/tenant_init.php';

// Tenant is already loaded by tenant_init.php
$tenant_id = getTenantId();
$tenant = getTenant();

$pdo = getDB();

// Get website content
$stmt = $pdo->prepare("SELECT * FROM website_content WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$content = $stmt->fetch();

if (!$content) {
    $content = [
        'company_name' => $tenant['name']
    ];
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? '';
$pickup = $_GET['pickup'] ?? '';
$dropoff = $_GET['dropoff'] ?? $_GET['return'] ?? '';
$location = $_GET['location'] ?? '';

$query_append = '';
if (!empty($pickup)) $query_append .= '&pickup=' . urlencode($pickup);
if (!empty($dropoff)) $query_append .= '&return=' . urlencode($dropoff);
if (!empty($location)) $query_append .= '&location=' . urlencode($location);

// Convert display dates to Y-m-d if they are provided from flatpickr format (e.g. "Wed 25 Mar")
// However, flatpickr usually sends them as they are in the input. 
// Standardizing to Y-m-d for backend logic.
function resolveDate($dateStr)
{
    if (!$dateStr)
        return null;
    // Try to parse standard Y-m-d
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr))
        return $dateStr;
    // Try to parse "7 Jun, 10:00" or "Wed 25 Mar" or similar
    $timestamp = strtotime($dateStr);
    if ($timestamp)
        return date('Y-m-d', $timestamp);
    return null;
}

// Parse datetime string to extract date for Flatpickr
function parseDateTimeForFlatpickr($dateStr)
{
    if (!$dateStr)
        return '';
    // Try to parse standard Y-m-d
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr))
        return $dateStr;
    // Try to parse "7 Jun, 10:00" or similar datetime formats
    $timestamp = strtotime($dateStr);
    if ($timestamp)
        return date('Y-m-d', $timestamp);
    return '';
}

$pickup_date = resolveDate($pickup);
$dropoff_date = resolveDate($dropoff);

// For Flatpickr defaultDate, use the parsed date in Y-m-d format
$pickup_flatpickr = parseDateTimeForFlatpickr($pickup);
$dropoff_flatpickr = parseDateTimeForFlatpickr($dropoff);

// Build query
$query = "SELECT v.* FROM vehicles v WHERE v.tenant_id = ? AND v.availability = 1";
$params = [$tenant_id];

if ($search) {
    $query .= " AND (v.brand LIKE ? OR v.model LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($type_filter) {
    $query .= " AND v.category = ?";
    $params[] = $type_filter;
}

// Availability filtering
if ($pickup_date && $dropoff_date) {
    // 1. Exclude vehicles that have bookings overlapping with the selected dates
    $query .= " AND v.id NOT IN (
        SELECT vehicle_id FROM bookings 
        WHERE status != 'cancelled' 
        AND (
            (pickup_date <= ? AND return_date >= ?) OR
            (pickup_date <= ? AND return_date >= ?) OR
            (pickup_date >= ? AND return_date <= ?)
        )
    )";
    $params[] = $dropoff_date;
    $params[] = $pickup_date;
    $params[] = $pickup_date;
    $params[] = $pickup_date;
    $params[] = $pickup_date;
    $params[] = $dropoff_date;

// 2. Exclude vehicles that have manual unavailable dates in the selected range
// Since unavailable_dates is a comma-separated string, we'll filter them in PHP for simplicity 
// or use a more complex MySQL check if needed. For now, let's fetch all and filter.
// (Optimization: we could use FIND_IN_SET or similar if it was one date, but it's a list)
}

$query .= " ORDER BY v.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$raw_vehicles = $stmt->fetchAll();

// PHP Filter for manual unavailable dates
$vehicles = [];
if ($pickup_date && $dropoff_date) {
    foreach ($raw_vehicles as $v) {
        $is_manual_unavailable = false;
        if (!empty($v['unavailable_dates'])) {
            $manual_dates = explode(', ', $v['unavailable_dates']);
            foreach ($manual_dates as $m_date) {
                if ($m_date >= $pickup_date && $m_date <= $dropoff_date) {
                    $is_manual_unavailable = true;
                    break;
                }
            }
        }
        if (!$is_manual_unavailable) {
            $vehicles[] = $v;
        }
    }
}
else {
    $vehicles = $raw_vehicles;
}

// Get unique vehicle types for filter
$stmt = $pdo->prepare("SELECT DISTINCT category FROM vehicles WHERE tenant_id = ? AND availability = 1 ORDER BY category");
$stmt->execute([$tenant_id]);
$vehicle_types = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get tenant settings
$stmt = $pdo->prepare("SELECT * FROM tenant_settings WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$settings = $stmt->fetch();
$currency_code = $settings['currency'] ?? 'GBP';
$currency_symbols = ['GBP' => '£', 'USD' => '$', 'EUR' => '€'];
$currency_symbol = $currency_symbols[$currency_code] ?? $currency_code;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Fleet -
        <?= htmlspecialchars($content['company_name'])?>
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="/app/custom-select.js" defer></script>

    <style>
        .search-pill-mobile {
            background: #f3f4f6;
            border-radius: 16px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .filter-btn-mobile {
            background: #f3f4f6;
            border-radius: 16px;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        @media (max-width: 767px) {
            .desktop-search-form {
                display: none !important;
            }

            .mobile-search-capsule {
                display: flex !important;
                gap: 8px;
                width: 100%;
            }

            .modal-search-form {
                display: grid !important;
            }
        }

        @media (min-width: 768px) {
            .mobile-search-capsule {
                display: none !important;
            }

            .desktop-search-form {
                display: grid !important;
            }

            .modal-search-form {
                display: none !important;
            }
        }

        #mobileSearchModal {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.2s ease-in-out;
            transform: translateY(100%);
            opacity: 0;
        }

        #mobileSearchModal.active {
            transform: translateY(0);
            opacity: 1;
            display: flex !important;
        }

        .flatpickr-day.prevMonthDay, .flatpickr-day.nextMonthDay, .flatpickr-day.hidden {
            display: inline-block !important;
            visibility: visible !important;
            opacity: 0.3 !important;
        }

        .flatpickr-day.startRange {
            background: #2563eb !important;
            color: white !important;
            border-radius: 50% !important;
            box-shadow: 12px 0 0 #eff6ff !important;
            z-index: 2;
        }

        .flatpickr-day.endRange {
            background: #2563eb !important;
            color: white !important;
            border-radius: 50% !important;
            box-shadow: -12px 0 0 #eff6ff !important;
            z-index: 2;
        }

        .flatpickr-day.startRange.endRange {
            box-shadow: none !important;
        }

        .flatpickr-day.inRange {
            background: #eff6ff !important;
            border-color: transparent !important;
            box-shadow: -10px 0 0 #eff6ff, 10px 0 0 #eff6ff !important;
            border-radius: 0 !important;
            color: #2563eb !important;
        }

        .flatpickr-day {
            border-radius: 10px !important;
        }


    </style>
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>

<body class="bg-gray-50">

    <!-- Universal Tenant Header (Includes Branding, Navigation & Styles) -->
    <?php include __DIR__ . '/includes/tenant_header.php'; ?>

    <!-- Search and Filter Section -->
    <section class="bg-white border-b border-gray-200 py-3 md:py-6 sticky top-0 z-40 shadow-sm">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Mobile Search Capsule -->
            <div class="mobile-search-capsule">
                <button onclick="toggleMobileSearch()" class="search-pill-mobile">
                    <div class="flex flex-col items-start overflow-hidden text-left">
                        <span class="text-xs font-bold text-gray-900 truncate w-full leading-tight">
                            <?= $search ? htmlspecialchars($search) : 'All Brands'?>
                        </span>
                        <span class="text-[10px] text-gray-500 font-medium leading-tight">
                            <?php if ($pickup_date && $dropoff_date): ?>
                            <?= date('d M', strtotime($pickup_date))?> -
                            <?= date('d M', strtotime($dropoff_date))?>
                            <?php
else: ?>
                            Select dates
                            <?php
endif; ?>
                            <?php if ($type_filter): ?>
                            •
                            <?= htmlspecialchars(ucfirst($type_filter))?>
                            <?php
endif; ?>
                        </span>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 ml-2 shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                        </path>
                    </svg>
                </button>
                <button onclick="toggleMobileSearch()" class="filter-btn-mobile">
                    <svg class="w-5 h-5 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4h18M6 10h12M9 16h6"></path>
                    </svg>
                </button>
            </div>

            <!-- Desktop Search Form (Standard) -->
            <form method="GET" id="mainSearchForm" class="desktop-search-form grid grid-cols-1 md:grid-cols-5 gap-4">
                <input type="hidden" name="tenant_id" value="<?= $tenant_id?>">
                <input type="hidden" name="tenant" value="<?= $tenant['subdomain']?>">

                <!-- Date Range - Moved to Top -->
                <div class="md:col-span-1 relative">
                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                        </path>
                    </svg>
                    <input type="text" id="pickup_date" name="pickup" value="<?= htmlspecialchars($pickup)?>"
                        placeholder="Pick-up Date"
                        class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none cursor-pointer">
                </div>

                <div class="md:col-span-1 relative">
                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                        </path>
                    </svg>
                    <input type="text" id="dropoff_date" name="dropoff" value="<?= htmlspecialchars($dropoff)?>"
                        placeholder="Return Date"
                        class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none cursor-pointer">
                </div>

                <!-- Brand Search -->
                <div class="md:col-span-1 relative">
                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="text" name="search" value="<?= htmlspecialchars($search)?>"
                        placeholder="Search brand..."
                        class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                </div>

                <!-- Category -->
                <div class="md:col-span-1">
                    <select name="type" onchange="this.form.submit()" class="cs-select hidden">
                        <option value="">All Categories</option>
                        <?php foreach ($vehicle_types as $type): ?>
                        <option value="<?= htmlspecialchars($type)?>" <?= $type_filter === $type ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst($type))?>
                        </option>
                        <?php
endforeach; ?>
                    </select>
                </div>

                <!-- Submit Button -->
                <div class="md:col-span-1">
                    <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg text-sm font-semibold transition-colors shadow-sm">
                        Find Vehicles
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- Mobile Search Modal -->
    <div id="mobileSearchModal" class="fixed inset-0 z-[100] hidden bg-white">
        <div class="flex flex-col h-full font-inter">
            <div class="flex items-center justify-between p-4 border-b">
                <h2 class="font-bold text-lg text-gray-900">Edit Search</h2>
                <button onclick="toggleMobileSearch()" class="p-2 border-0 bg-transparent text-gray-900"><svg
                        class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg></button>
            </div>
            <div class="flex-1 overflow-y-auto p-4 md:p-6">
                <form method="GET" class="modal-search-form space-y-6">
                    <input type="hidden" name="tenant_id" value="<?= $tenant_id?>">
                    <input type="hidden" name="tenant" value="<?= $tenant['subdomain']?>">

                    <!-- Date Range - Moved to Top -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label
                                class="block text-sm font-bold text-gray-700 leading-none uppercase tracking-wider">Pick-up
                                Date</label>
                            <div class="relative">
                                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                                <input type="text" id="mob_pickup_date" name="pickup"
                                    value="<?= htmlspecialchars($pickup)?>" placeholder="Select date"
                                    class="w-full pl-12 pr-4 py-4 bg-gray-50 border-0 rounded-2xl outline-none text-base cursor-pointer"
                                    readonly>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label
                                class="block text-sm font-bold text-gray-700 leading-none uppercase tracking-wider">Return
                                Date</label>
                            <div class="relative">
                                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                                <input type="text" id="mob_dropoff_date" name="dropoff"
                                    value="<?= htmlspecialchars($dropoff)?>" placeholder="Select date"
                                    class="w-full pl-12 pr-4 py-4 bg-gray-50 border-0 rounded-2xl outline-none text-base cursor-pointer"
                                    readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Brand Search -->
                    <div class="space-y-2">
                        <label class="block text-sm font-bold text-gray-700 leading-none uppercase tracking-wider">Brand
                            / Search</label>
                        <div class="relative">
                            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <input type="text" name="search" value="<?= htmlspecialchars($search)?>"
                                placeholder="Search brand..."
                                class="w-full pl-12 pr-4 py-4 bg-gray-50 border-0 rounded-2xl outline-none text-base focus:ring-2 focus:ring-blue-500/20 transition-all">
                        </div>
                    </div>

                    <!-- Category -->
                    <div class="space-y-2">
                        <label
                            class="block text-sm font-bold text-gray-700 leading-none uppercase tracking-wider">Category</label>
                        <select name="type" class="cs-select hidden">
                            <option value="">All Categories</option>
                            <?php foreach ($vehicle_types as $type): ?>
                            <option value="<?= htmlspecialchars($type)?>" <?=$type_filter===$type ? 'selected' : ''?>>
                                <?= htmlspecialchars(ucfirst($type))?>
                            </option>
                            <?php
endforeach; ?>
                        </select>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-6">
                        <button type="submit"
                            class="w-full bg-blue-600 text-white py-4 rounded-2xl font-bold text-lg shadow-lg active:scale-[0.98] transition-all">Show
                            Available Fleet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Search Status Bar -->
    <?php if ($search || $type_filter || ($pickup_date && $dropoff_date)): ?>
    <section class="bg-blue-50 py-3 border-b border-blue-100">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-2 text-sm text-blue-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>
                        Showing results for
                        <?php if ($pickup_date && $dropoff_date): ?>
                        <span class="font-bold">
                            <?= date('M d', strtotime($pickup_date))?> -
                            <?= date('M d', strtotime($dropoff_date))?>
                        </span>
                        <?php
    endif; ?>
                        <?php if ($search): ?>
                        in <span class="font-bold">"
                            <?= htmlspecialchars($search)?>"
                        </span>
                        <?php
    endif; ?>
                        <?php if ($type_filter): ?>
                        in <span class="font-bold">
                            <?= htmlspecialchars(ucfirst($type_filter))?>
                        </span>
                        <?php
    endif; ?>
                    </span>
                </div>
                <a href="/templates/fleet.php?tenant=<?= $tenant['subdomain']?>"
                    class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1 uppercase tracking-wider">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                    Clear Filters
                </a>
            </div>
        </div>
    </section>
    <?php
endif; ?>

    <!-- Vehicles Grid -->
    <section class="py-12">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-gray-600 mb-8">Showing <?= count($vehicles)?> available vehicle<?= count($vehicles) !== 1 ? 's' : ''?></p>

            <?php if (empty($vehicles)): ?>
            <div class="text-center py-16">
                <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                </svg>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No vehicles found</h3>
                <p class="text-gray-600">Try adjusting your search or filters</p>
            </div>
            <?php
else: ?>
            <?php
            $whatsapp_phone = preg_replace('/\D/', '', $tenant['company_phone'] ?? $settings['company_phone'] ?? '');
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $base_url = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? '');
            ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php foreach ($vehicles as $vehicle):
                    $v_images = json_decode($vehicle['images'], true);
                    $v_img = is_array($v_images) ? $v_images[0] : ($vehicle['images'] ?: '');
                    if (empty($v_img)) $v_img = '/assets/images/placeholder-img.webp';
                    $v_ml = intval($vehicle['mileage_limit'] ?? 0);
                    $v_mileage = $v_ml > 0 ? ($v_ml >= 1000 ? round($v_ml / 1000, 1) . 'k mi' : $v_ml . ' mi') : 'Unlimited';
                ?>
                <a href="/templates/vehicle-details.php?id=<?= $vehicle['id']?>"
                   class="group bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-xl transition-all duration-300">
                    <!-- Image -->
                    <div class="h-48 overflow-hidden bg-gray-50">
                        <img src="<?= htmlspecialchars($v_img)?>"
                             alt="<?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'])?>"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    </div>
                    <!-- Body -->
                    <div class="p-5">
                        <!-- Name -->
                        <h3 class="text-base font-semibold text-gray-900 mb-4">
                            <?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'])?>
                        </h3>
                        <!-- Specs -->
                        <div class="flex items-center justify-around py-3 mb-4 border-t border-b border-gray-100">
                            <!-- Transmission -->
                            <div class="flex flex-col items-center gap-1">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4m0 0v4m0 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m0 0h8m0-4a2 2 0 1 0 0-4 2 2 0 0 0 0 4"/>
                                </svg>
                                <span class="text-xs text-gray-500"><?= htmlspecialchars(ucfirst($vehicle['transmission'] ?? 'Manual'))?></span>
                            </div>
                            <div class="w-px h-8 bg-gray-200"></div>
                            <!-- Fuel -->
                            <div class="flex flex-col items-center gap-1">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 20V8a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v12m-8 0h8m-8 0H4m10 0h1M9 6V3.5h2.5"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8h.5a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H17"/>
                                </svg>
                                <span class="text-xs text-gray-500"><?= htmlspecialchars(ucfirst($vehicle['fuel_type'] ?? 'Petrol'))?></span>
                            </div>
                            <div class="w-px h-8 bg-gray-200"></div>
                            <!-- Mileage -->
                            <div class="flex flex-col items-center gap-1">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m12 14 4-4"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.34 19a10 10 0 1 1 17.32 0"/>
                                </svg>
                                <span class="text-xs text-gray-500"><?= $v_mileage ?></span>
                            </div>
                        </div>
                        <!-- Price & CTA -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-baseline gap-0.5">
                                <span class="text-xl font-bold text-gray-900"><?= $currency_symbol ?><?= number_format($vehicle['price_per_day'])?></span>
                                <span class="text-sm text-gray-400">/day</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <?php if (!empty($whatsapp_phone)): ?>
                                <button type="button"
                                    onclick="event.preventDefault(); event.stopPropagation(); window.open('https://wa.me/<?= $whatsapp_phone ?>?text=<?= rawurlencode('I would like to enquire about the ' . $vehicle['brand'] . ' ' . $vehicle['model'] . ' - ' . $base_url . '/templates/vehicle-details.php?id=' . $vehicle['id']) ?>', '_blank');"
                                    class="w-9 h-9 rounded-full bg-[#25D366] flex items-center justify-center hover:opacity-90 transition-opacity shrink-0"
                                    title="Enquire on WhatsApp">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                    </svg>
                                </button>
                                <?php endif; ?>
                                <span class="text-white text-sm font-semibold px-5 py-2.5 rounded-full transition-opacity hover:opacity-90"
                                      style="background-color:<?= htmlspecialchars($content['primary_color'] ?? '#dc2626')?>">
                                    Book
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php
endif; ?>
        </div>
    </section>

    <!-- Universal Tenant Footer -->
    <?php include __DIR__ . '/includes/tenant_footer.php'; ?>

    <script>
        function toggleMobileSearch() {
            const modal = document.getElementById('mobileSearchModal');
            const isActive = modal.classList.contains('active');

            if (!isActive) {
                modal.classList.remove('hidden');
                // Force layout for transition
                modal.offsetHeight;
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            } else {
                modal.classList.remove('active');
                document.body.style.overflow = '';
                // Hide after transition
                setTimeout(() => {
                    if (!modal.classList.contains('active')) {
                        modal.classList.add('hidden');
                    }
                }, 300);
            }
        }

        function toggleMobileFilters() {
            toggleMobileSearch();
        }

        // Custom error modal function
        function showErrorModal(message) {
            const existingModal = document.getElementById('customErrorModal');
            if (existingModal) {
                existingModal.remove();
            }

            const modal = document.createElement('div');
            modal.id = 'customErrorModal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-lg p-6 max-w-md mx-4 shadow-xl">
                    <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-red-100 rounded-full">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 text-center mb-2">Error</h3>
                    <p class="text-gray-600 text-center mb-6">${message}</p>
                    <button onclick="document.getElementById('customErrorModal').remove()" class="w-full px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold transition-colors">
                        OK
                    </button>
                </div>
            `;
            document.body.appendChild(modal);

            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        function showMaxBookingModal() {
            const existingModal = document.getElementById('maxBookingModal');
            if (existingModal) {
                existingModal.remove();
            }

            const maxDays = <?= isset($settings['max_booking_advance_days']) && $settings['max_booking_advance_days'] > 0 ? (int)$settings['max_booking_advance_days'] : 30 ?>;
            const phone = "<?= htmlspecialchars($settings['company_phone'] ?? '') ?>";
            
            const modal = document.createElement('div');
            modal.id = 'maxBookingModal';
            modal.className = 'fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4';
            
            let phoneHTML = '';
            let callBtnHTML = '';
            if (phone) {
                phoneHTML = `
                    <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4 flex flex-col items-center justify-center gap-1 mb-6">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Company Contact Number</span>
                        <a href="tel:${phone}" class="text-lg font-bold text-blue-600 hover:text-blue-800 transition-colors">${phone}</a>
                    </div>
                `;
                callBtnHTML = `
                    <a href="tel:${phone}" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold shadow-lg shadow-blue-100 transition-all flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        Call Us Now
                    </a>
                `;
            }

            modal.innerHTML = `
                <div class="bg-white rounded-3xl max-w-sm w-full p-8 shadow-2xl relative overflow-hidden text-center space-y-6">
                    <button onclick="document.getElementById('maxBookingModal').remove()" class="absolute top-4 right-4 w-10 h-10 bg-gray-100 hover:bg-gray-200 rounded-xl flex items-center justify-center transition-colors">
                        <svg class="w-5 h-5 stroke-[2.5] text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>

                    <!-- Warning / Calendar Icon in blue (brand color) -->
                    <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto shadow-md">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>

                    <div class="space-y-2">
                        <h3 class="text-xl font-bold text-gray-900">Custom Booking Required</h3>
                        <p class="text-sm text-gray-600">The selected date exceeds our maximum online booking window of <span class="font-bold text-gray-900">${maxDays}</span> days in advance.</p>
                        <p class="text-sm text-gray-500">Please contact our team directly to book this vehicle.</p>
                    </div>

                    \${phoneHTML}

                    <div class="flex flex-col gap-3">
                        \${callBtnHTML}
                        <button type="button" onclick="document.getElementById('maxBookingModal').remove()" class="w-full py-3 text-sm font-bold text-gray-500 hover:bg-gray-50 rounded-xl transition-colors">
                            Cancel & Modify Dates
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Set Global Flatpickr Locale to start on Monday
            if (window.flatpickr) {
                flatpickr.l10ns.default.firstDayOfWeek = 1;
            }

            const fpConfig = {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "j M Y",
                minDate: "today",
                maxDate: new Date(Date.now() + (<?= isset($settings['max_booking_advance_days']) && $settings['max_booking_advance_days'] > 0 ? (int)$settings['max_booking_advance_days'] : 30 ?> * 24 * 60 * 60 * 1000)),
                locale: {
                    firstDayOfWeek: 1
                }
            };

            function validateSelectedDate(selectedDates, instance) {
                const today = new Date();
                today.setHours(0,0,0,0);
                const maxAdvanceDays = <?= isset($settings['max_booking_advance_days']) && $settings['max_booking_advance_days'] > 0 ? (int)$settings['max_booking_advance_days'] : 30 ?>;
                const maxDate = new Date(today.getTime() + maxAdvanceDays * 24 * 60 * 60 * 1000);
                if (selectedDates.length > 0) {
                    const targetDate = selectedDates[selectedDates.length - 1];
                    if (targetDate > maxDate) {
                        instance.clear();
                        showMaxBookingModal();
                        return false;
                    }
                }
                return true;
            }

            // Desktop Datepickers
            const pickupPicker = flatpickr("#pickup_date", {
                ...fpConfig,
                defaultDate: "<?= htmlspecialchars($pickup_flatpickr) ?>",
                onChange: function(selectedDates, dateStr, instance) {
                    if (!validateSelectedDate(selectedDates, instance)) return;
                    if (selectedDates.length > 0) {
                        const pickupDate = selectedDates[0];
                        const pickupDateStr = pickupDate.toISOString().split('T')[0];
                        // Update return date picker minDate to pickup date
                        if (returnPicker) {
                            returnPicker.set('minDate', pickupDateStr);
                        }
                        if (mobReturnPicker) {
                            mobReturnPicker.set('minDate', pickupDateStr);
                        }
                    }
                }
            });

            const returnPicker = flatpickr("#dropoff_date", {
                ...fpConfig,
                defaultDate: "<?= htmlspecialchars($dropoff_flatpickr) ?>",
                onChange: function(selectedDates, dateStr, instance) {
                    validateSelectedDate(selectedDates, instance);
                }
            });

            // Mobile Datepickers
            const mobPickupPicker = flatpickr("#mob_pickup_date", {
                ...fpConfig,
                defaultDate: "<?= htmlspecialchars($pickup_flatpickr) ?>",
                onChange: function(selectedDates, dateStr, instance) {
                    if (!validateSelectedDate(selectedDates, instance)) return;
                    if (selectedDates.length > 0) {
                        const pickupDate = selectedDates[0];
                        const pickupDateStr = pickupDate.toISOString().split('T')[0];
                        // Update return date picker minDate to pickup date
                        if (returnPicker) {
                            returnPicker.set('minDate', pickupDateStr);
                        }
                        if (mobReturnPicker) {
                            mobReturnPicker.set('minDate', pickupDateStr);
                        }
                    }
                }
            });

            const mobReturnPicker = flatpickr("#mob_dropoff_date", {
                ...fpConfig,
                defaultDate: "<?= htmlspecialchars($dropoff_flatpickr) ?>",
                onChange: function(selectedDates, dateStr, instance) {
                    validateSelectedDate(selectedDates, instance);
                }
            });

            // Add form validation to prevent return date before pickup date
            const searchForm = document.querySelector('form[action="vehicle-booking.php"]');
            if (searchForm) {
                searchForm.addEventListener('submit', function(e) {
                    const pickupVal = document.getElementById('pickup_date').value;
                    const returnVal = document.getElementById('dropoff_date').value;
                    
                    const maxAdvanceDays = <?= isset($settings['max_booking_advance_days']) && $settings['max_booking_advance_days'] > 0 ? (int)$settings['max_booking_advance_days'] : 30 ?>;
                    const today = new Date();
                    today.setHours(0,0,0,0);
                    const maxDate = new Date(today.getTime() + maxAdvanceDays * 24 * 60 * 60 * 1000);

                    if (pickupVal) {
                        const pDate = new Date(pickupVal);
                        if (pDate > maxDate) {
                            e.preventDefault();
                            showMaxBookingModal();
                            return false;
                        }
                    }
                    if (returnVal) {
                        const rDate = new Date(returnVal);
                        if (rDate > maxDate) {
                            e.preventDefault();
                            showMaxBookingModal();
                            return false;
                        }
                    }

                    if (pickupVal && returnVal) {
                        const pickupDate = new Date(pickupVal);
                        const returnDate = new Date(returnVal);
                        
                        if (returnDate < pickupDate) {
                            e.preventDefault();
                            showErrorModal('Return date cannot be before pickup date.');
                            return false;
                        }
                    }
                });
            }
        });
    </script>
</body>

</html>