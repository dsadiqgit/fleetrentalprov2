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
$dropoff = $_GET['dropoff'] ?? '';

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
    // Try to parse "Wed 25 Mar" or similar
    $timestamp = strtotime($dateStr);
    if ($timestamp)
        return date('Y-m-d', $timestamp);
    return null;
}

$pickup_date = resolveDate($pickup);
$dropoff_date = resolveDate($dropoff);

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

// Get currency setting
$stmt = $pdo->prepare("SELECT currency FROM tenant_settings WHERE tenant_id = ?");
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
    <!-- Universal Tenant Header (Includes Branding, Navigation & Styles) -->
    <?php include __DIR__ . '/includes/tenant_header.php'; ?>

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

    <!-- Search and Filter Section -->
    <section class="bg-white border-b border-gray-200 py-3 md:py-6 sticky top-0 z-40 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
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

                <!-- Date Range -->
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
                        placeholder="Drop-off Date"
                        class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none cursor-pointer">
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

                    <!-- Date Range -->
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
                                class="block text-sm font-bold text-gray-700 leading-none uppercase tracking-wider">Drop-off
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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
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
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($vehicles as $vehicle): ?>
                <div
                    class="bg-white rounded-[20px] p-4 border border-gray-100 hover:shadow-xl transition-all duration-300 relative group">
                    <?php if ($vehicle['availability']): ?>
                    <div class="absolute top-6 right-6 z-10">
                        <span
                            class="px-2.5 py-1 bg-white/90 backdrop-blur-sm shadow-sm text-green-600 text-[10px] uppercase font-bold rounded-md tracking-wider">Available</span>
                    </div>
                    <?php
        endif; ?>

                    <a href="/templates/vehicle-booking.php?id=<?= $vehicle['id']?>"
                        class="block relative h-48 bg-[#f4f5f7] rounded-xl overflow-hidden mb-4 flex items-center justify-center group-hover:bg-[#edf0f5] transition-colors duration-300">
                        <?php
        $image_url = null;
        if ($vehicle['images']) {
            $decoded = json_decode($vehicle['images'], true);
            if (is_array($decoded) && !empty($decoded)) {
                $image_url = $decoded[0];
            }
            else if (!is_array($decoded)) {
                $image_url = $vehicle['images'];
            }
        }
        if ($image_url):
?>
                        <img src="<?= htmlspecialchars($image_url)?>" alt="<?= htmlspecialchars($vehicle['name'])?>"
                            class="w-full h-full object-cover">
                        <?php
        else: ?>
                        <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                        <?php
        endif; ?>
                    </a>

                    <a href="/templates/vehicle-booking.php?id=<?= $vehicle['id']?>" class="block">
                        <h3
                            class="text-base font-semibold text-gray-900 mb-4 line-clamp-1 hover:text-blue-600 transition-colors uppercase">
                            <?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'])?>
                        </h3>
                    </a>

                    <!-- Features Grid -->
                    <div class="grid grid-cols-2 gap-y-3 gap-x-2 mb-6">
                        <div class="flex items-center text-xs font-medium text-gray-500">
                            <!-- User Icon -->
                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span class="truncate">
                                <?= htmlspecialchars($vehicle['seats'])?> Passengers
                            </span>
                        </div>
                        <div class="flex items-center text-xs font-medium text-gray-500">
                            <!-- Manual Shift Icon -->
                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2">
                                </path>
                            </svg>
                            <span class="truncate">
                                <?= htmlspecialchars(ucfirst($vehicle['transmission']))?>
                            </span>
                        </div>
                        <div class="flex items-center text-xs font-medium text-gray-500">
                            <!-- Snowflake Icon -->
                            <svg class="w-4 h-4 mr-2 text-gray-400" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 2v20M17 5l-5 5-5-5M22 12H2M19 17l-5-5-5 5M12 12l2.5-2.5"></path>
                            </svg>
                            <span class="truncate">Air Conditioning</span>
                        </div>
                        <div class="flex items-center text-xs font-medium text-gray-500">
                            <!-- Car Icon -->
                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                                </path>
                            </svg>
                            <span class="truncate">4 Doors</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                        <div>
                            <span class="text-base font-bold text-gray-900"><?= $currency_symbol ?>
                                <?= number_format($vehicle['price_per_day'])?>
                            </span>
                            <span class="text-gray-400 text-[13px] font-medium ml-0.5">/day</span>
                        </div>
                        <a href="/templates/vehicle-booking.php?id=<?= $vehicle['id']?>"
                            class="text-[#3b82f5] hover:text-blue-700 text-[13px] font-semibold flex items-center transition-colors">
                            Rent Now
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </a>
                    </div>
                </div>
                <?php
    endforeach; ?>
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
                locale: {
                    firstDayOfWeek: 1
                }
            };

            // Desktop Datepickers
            flatpickr("#pickup_date", fpConfig);
            flatpickr("#dropoff_date", fpConfig);

            // Mobile Datepickers
            flatpickr("#mob_pickup_date", fpConfig);
            flatpickr("#mob_dropoff_date", fpConfig);
        });
    </script>
</body>

</html>