<?php
require_once __DIR__ . '/../includes/tenant_init.php';

$tenant_id = getTenantId();
$tenant = getTenant();
$pdo = getDB();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: /templates/fleet.php');
    exit;
}

$vehicle_id = intval($_GET['id']);

$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ? AND tenant_id = ? AND availability = 1");
$stmt->execute([$vehicle_id, $tenant_id]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    header('Location: /templates/fleet.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM website_content WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$content = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM tenant_settings WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$settings = $stmt->fetch();

$raw_locations = $settings['pickup_location'] ?? '';
$locations = [];
if (!empty($raw_locations)) {
    $locations = array_map('trim', preg_split('/[;\n\r]+/', $raw_locations));
}
if (empty($locations)) {
    $locations = ['London Heathrow Airport', 'London City Centre', 'Gatwick Airport', 'Manchester Airport'];
}

$currency_code = $settings['currency'] ?? 'GBP';
$currency_symbols = ['GBP' => '£', 'USD' => '$', 'EUR' => '€'];
$currency_symbol = $currency_symbols[$currency_code] ?? $currency_code;

$require_verification = isset($settings['require_license_verification']) ? (bool)$settings['require_license_verification'] : true;
$min_age = intval($vehicle['min_age'] ?? 0);
$min_days_required = max(1, intval($vehicle['min_days'] ?? 1));

$stmt = $pdo->prepare("SELECT pickup_date, return_date FROM bookings WHERE vehicle_id = ? AND status NOT IN ('cancelled', 'completed')");
$stmt->execute([$vehicle_id]);
$booked_dates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convert booked dates to flatpickr disable format
$disabled_dates = [];
foreach ($booked_dates as $booking) {
    $start = new DateTime($booking['pickup_date']);
    $end = new DateTime($booking['return_date']);
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end->modify('+1 day'));
    foreach ($period as $day) {
        $disabled_dates[] = $day->format('Y-m-d');
    }
}

if (!$content) {
    $content = ['company_name' => $tenant['name'], 'contact_phone' => '', 'contact_email' => ''];
}

$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE tenant_id = ? AND category = ? AND id != ? AND availability = 1 LIMIT 3");
$stmt->execute([$tenant_id, $vehicle['category'], $vehicle_id]);
$related_vehicles = $stmt->fetchAll();
if (empty($related_vehicles)) {
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE tenant_id = ? AND id != ? AND availability = 1 LIMIT 3");
    $stmt->execute([$tenant_id, $vehicle_id]);
    $related_vehicles = $stmt->fetchAll();
}

$image_url = null;
if (!empty($vehicle['images'])) {
    $decoded = json_decode($vehicle['images'], true);
    $image_url = is_array($decoded) && !empty($decoded) ? $decoded[0] : $vehicle['images'];
}
// Use placeholder if no image
if (empty($image_url)) {
    $image_url = '/assets/images/placeholder-img.webp';
}

// Store vehicle_id in session for verification status checks
$_SESSION['booking_data']['vehicle_id'] = $vehicle_id;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book <?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'])?> - <?= htmlspecialchars($content['company_name'])?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://js.stripe.com/v3/"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        /* Calendar Modal Styles */
        .calendar-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .calendar-modal.active {
            display: flex;
        }
        
        .calendar-modal-content {
            background: white;
            border-radius: 24px;
            max-width: 900px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        .time-slot {
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 600;
            font-size: 14px;
        }
        
        .time-slot:hover {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        
        .time-slot.selected {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        
        .time-slot.am {
            color: #1f2937;
        }
        
        .time-slot.pm {
            color: #1f2937;
        }
        [x-cloak] { display: none !important; }
        
        /* Flatpickr Calendar Styling - Matches template preview calendar */
        .flatpickr-calendar {
            background: white;
            border-radius: 16px !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15) !important;
            border: none !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            width: auto !important;
            padding: 10px !important;
        }

        /* Force months headers to show side-by-side on multi-month view */
        .flatpickr-calendar.showMonths .flatpickr-months {
            display: flex !important;
            justify-content: space-around !important;
            background: white !important;
            padding: 15px 20px 5px !important;
            border-bottom: 1px solid #f3f4f6 !important;
        }

        .flatpickr-calendar.showMonths .flatpickr-months .flatpickr-month {
            flex: 1 !important;
            text-align: center !important;
            display: block !important;
            height: auto !important;
            opacity: 1 !important;
            visibility: visible !important;
        }

        .flatpickr-current-month {
            font-size: 16px !important;
            font-weight: 700 !important;
            color: #1f2937 !important;
            position: relative !important;
            display: inline-block !important;
        }

        .flatpickr-prev-month,
        .flatpickr-next-month {
            padding: 4px !important;
            fill: #1f2937 !important;
        }

        .flatpickr-weekday {
            color: #6b7280 !important;
            font-weight: 600 !important;
            font-size: 11px !important;
            text-transform: uppercase !important;
        }

        .flatpickr-day {
            border-radius: 8px !important;
            border: none !important;
            color: #1f2937 !important;
            font-weight: 500 !important;
        }

        /* CRITICAL: Override Flatpickr's default hiding of adjacent month days in multi-month view */
        .flatpickr-day.prevMonthDay,
        .flatpickr-day.nextMonthDay {
            display: inline-block !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }

        .flatpickr-day:hover:not(.flatpickr-disabled) {
            background: #f3f4f6 !important;
            border: none !important;
        }

        /* Force previous/next month spacer days to keep layout space but be invisible to preserve alignment */
        .flatpickr-calendar.showMonths .flatpickr-days .flatpickr-day.prevMonthDay,
        .flatpickr-calendar.showMonths .flatpickr-days .flatpickr-day.nextMonthDay {
            display: inline-block !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }

        /* Past dates with reduced opacity */
        .flatpickr-day.flatpickr-disabled {
            opacity: 0.3 !important;
            color: #9ca3af !important;
        }

        .flatpickr-day.today {
            background: transparent !important;
            border: 2px solid #1f2937 !important;
            color: #1f2937 !important;
        }

        .flatpickr-day.selected,
        .flatpickr-day.startRange,
        .flatpickr-day.endRange {
            background: #3b82f6 !important;
            color: white !important;
            border: none !important;
            font-weight: 700 !important;
            border-radius: 50% !important;
        }

        .flatpickr-day.inRange {
            background: #dbeafe !important;
            border-color: transparent !important;
            border-radius: 0 !important;
            color: #1f2937 !important;
        }
        
        /* Use box-shadow to bridge horizontal gaps between inRange days without breaking grid layout */
        .flatpickr-day.inRange:not(.startRange):not(.endRange) {
            box-shadow: -5px 0 0 #dbeafe, 5px 0 0 #dbeafe !important;
        }
        
        .flatpickr-day.inRange.startRange {
            box-shadow: 5px 0 0 #dbeafe !important;
        }
        
        .flatpickr-day.inRange.endRange {
            box-shadow: -5px 0 0 #dbeafe !important;
        }
        
        /* Round the start and end of range */
        .flatpickr-day.startRange {
            border-radius: 50% 0 0 50% !important;
        }
        
        .flatpickr-day.endRange {
            border-radius: 0 50% 50% 0 !important;
        }
        
        /* If start and end are the same day */
        .flatpickr-day.startRange.endRange {
            border-radius: 50% !important;
            box-shadow: none !important;
        }
    </style>
</head>

<body class="bg-gray-50">

    <?php include __DIR__ . '/includes/tenant_header.php'; ?>
    
    <main class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-8">
            <a href="/" class="hover:text-gray-900 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
            </a>
            <span class="text-gray-300">/</span>
            <a href="/fleet" class="hover:text-gray-900">Our Fleet</a>
            <span class="text-gray-300">/</span>
            <span class="text-blue-600 font-medium"><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'])?></span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content - Left 2 columns -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Image Gallery -->
                <div class="grid grid-cols-4 gap-3">
                    <?php 
                    $all_images = [];
                    if (!empty($vehicle['images'])) {
                        $decoded = json_decode($vehicle['images'], true);
                        $all_images = is_array($decoded) ? $decoded : [$vehicle['images']];
                    }
                    if (empty($all_images) && $image_url) {
                        $all_images = [$image_url];
                    }
                    
                    // Main large image
                    $main_img = $all_images[0] ?? 'https://via.placeholder.com/800x600?text=Vehicle+Image';
                    ?>
                    <div class="col-span-4 md:col-span-2 aspect-[4/3] bg-gray-100 rounded-2xl overflow-hidden relative group cursor-pointer" onclick="openLightbox(0)">
                        <img id="mainImage" src="<?= htmlspecialchars($main_img)?>" class="w-full h-full object-cover" alt="Main Vehicle Image">
                        <div class="absolute top-4 right-4 bg-white px-3 py-1.5 rounded-lg text-sm font-semibold text-gray-700 shadow-sm">
                            1/<?= count($all_images)?>
                        </div>
                    </div>
                    
                    <!-- Thumbnail images -->
                    <?php for ($i = 1; $i < 4; $i++): 
                        if (!isset($all_images[$i])) break;
                        $thumb = $all_images[$i];
                    ?>
                    <div class="col-span-2 md:col-span-1 aspect-[4/3] bg-gray-100 rounded-xl overflow-hidden cursor-pointer hover:opacity-75 transition-opacity" onclick="openLightbox(<?= $i?>)">
                        <img src="<?= htmlspecialchars($thumb)?>" class="w-full h-full object-cover" alt="Thumbnail <?= $i + 1?>">
                    </div>
                    <?php endfor; ?>
                    
                    <!-- See all photos button -->
                    <?php if (count($all_images) > 4): ?>
                    <div class="col-span-2 md:col-span-1 aspect-[4/3] bg-gray-900/80 rounded-xl overflow-hidden cursor-pointer hover:bg-gray-900 transition-colors flex items-center justify-center" onclick="openLightbox(0)">
                        <div class="text-center text-white">
                            <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p class="text-sm font-semibold">See all photos (<?= count($all_images)?>)</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Vehicle Title & Actions -->
                <div>
                    <p class="text-sm text-gray-500 mb-2"><?= htmlspecialchars($vehicle['brand'])?></p>
                    <div class="flex items-start justify-between mb-3">
                        <h1 class="text-3xl font-bold text-gray-900"><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'] . ' (' . $vehicle['year'] . ')')?></h1>
                        <div class="flex items-center gap-2">
                            <?php
                            $raw_phone = $settings['company_phone'] ?? '';
                            $clean_phone = preg_replace('/[^0-9]/', '', $raw_phone);
                            ?>
                            <?php if (!empty($clean_phone)): ?>
                            <a href="https://wa.me/<?= htmlspecialchars($clean_phone) ?>" target="_blank" class="p-2.5 bg-gray-100 hover:bg-green-50 hover:text-green-600 rounded-lg transition-colors flex items-center justify-center text-gray-500" title="Chat on WhatsApp">
                                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12.004 2C6.51 2 2.014 6.509 2.014 12c0 2.18.7 4.21 1.89 5.87L2.5 22l4.25-1.38c1.61 1.07 3.53 1.69 5.59 1.69 5.494 0 9.99-4.509 9.99-10s-4.496-10-9.99-10zm5.82 14.16c-.25.7-1.46 1.36-2.02 1.42-.49.05-1.12.06-1.81-.16-.43-.14-.98-.35-1.57-.61-2.52-1.1-4.14-3.66-4.27-3.83-.13-.17-.99-1.32-.99-2.52 0-1.2.62-1.79.84-2.03.22-.24.49-.3.66-.3.17 0 .34.01.49.01.16 0 .37-.06.58.46.22.53.75 1.83.81 1.95.07.12.11.27.03.43-.08.17-.16.27-.27.4-.11.13-.24.3-.34.4-.11.12-.23.25-.1.46.13.21.58.96 1.25 1.57.86.77 1.58 1.01 1.8 1.12.22.1.35.09.48-.06.13-.15.56-.65.71-.88.15-.22.3-.19.51-.11.21.08 1.34.63 1.57.74.23.11.38.16.44.25.06.09.06.54-.19 1.24z"/>
                                </svg>
                            </a>
                            <a href="tel:<?= htmlspecialchars($raw_phone) ?>" class="p-2.5 bg-gray-100 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors flex items-center justify-center text-gray-500" title="Call Us">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="border-b border-gray-200">
                    <nav class="flex gap-8">
                        <button onclick="switchTab('details')" id="tab-details" class="tab-button pb-3 border-b-2 border-blue-600 text-blue-600 font-semibold text-sm">
                            Car details
                        </button>
                        <button onclick="switchTab('policies')" id="tab-policies" class="tab-button pb-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-semibold text-sm">
                            Policies
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div id="content-details" class="tab-content">
                    <div class="space-y-6">
                        <!-- Description -->
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 mb-4">Description</h2>
                            <p class="text-gray-600 leading-relaxed text-[15px]">
                                <?= nl2br(htmlspecialchars($vehicle['description'] ?? 'No description provided.'))?>
                            </p>
                            
                            <?php
                            $features = [];
                            if (!empty($vehicle['vehicle_features'])) {
                                $decoded_features = json_decode($vehicle['vehicle_features'], true);
                                if (is_array($decoded_features)) {
                                    $features = $decoded_features;
                                }
                            }
                            ?>
                            <?php if (!empty($features)): ?>
                            <div class="mt-6">
                                <h3 class="font-bold text-gray-900 text-sm mb-3">Key Features</h3>
                                <div class="grid grid-cols-2 gap-2">
                                    <?php foreach ($features as $feat): ?>
                                    <div class="flex items-center gap-2 text-sm text-gray-600">
                                        <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span><?= htmlspecialchars($feat) ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Car Specifications -->
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 mb-4">Car Specifications</h2>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-gray-500 shadow-sm border border-gray-100">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-[10px] uppercase tracking-wider text-gray-400 font-bold">Transmission</p>
                                        <p class="font-semibold text-sm text-gray-900"><?= ucfirst(htmlspecialchars($vehicle['transmission'] ?? 'Automatic'))?></p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-gray-500 shadow-sm border border-gray-100">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 9.172V5L8 4z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-[10px] uppercase tracking-wider text-gray-400 font-bold">Fuel Type</p>
                                        <p class="font-semibold text-sm text-gray-900"><?= ucfirst(htmlspecialchars($vehicle['fuel_type'] ?? 'Petrol'))?></p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-gray-500 shadow-sm border border-gray-100">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-[10px] uppercase tracking-wider text-gray-400 font-bold">Seats</p>
                                        <p class="font-semibold text-sm text-gray-900"><?= htmlspecialchars($vehicle['seats'] ?? '5')?> Seats</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-gray-500 shadow-sm border border-gray-100">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-[10px] uppercase tracking-wider text-gray-400 font-bold">Doors & Bags</p>
                                        <p class="font-semibold text-sm text-gray-900"><?= htmlspecialchars($vehicle['doors'] ?? '5')?>D / <?= htmlspecialchars($vehicle['bags'] ?? '2')?> Bags</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-gray-500 shadow-sm border border-gray-100">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-[10px] uppercase tracking-wider text-gray-400 font-bold">Engine Capacity</p>
                                        <p class="font-semibold text-sm text-gray-900"><?= htmlspecialchars($vehicle['engine_capacity'] ?? 'N/A')?></p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-gray-500 shadow-sm border border-gray-100">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-[10px] uppercase tracking-wider text-gray-400 font-bold">Mileage</p>
                                        <p class="font-semibold text-sm text-gray-900 truncate"><?= !empty($vehicle['unlimited_mileage']) ? 'Unlimited' : htmlspecialchars($vehicle['mileage_limit'] ?? '300') . ' mi/day' ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="content-policies" class="tab-content hidden">
                    <div class="space-y-4">
                        <h2 class="text-xl font-bold text-gray-900">Rental Policies</h2>
                        <p class="text-gray-600">Policy information will be displayed here.</p>
                    </div>
                </div>

                <div id="content-reviews" class="tab-content hidden">
                    <div class="space-y-4">
                        <h2 class="text-xl font-bold text-gray-900">Customer Reviews</h2>
                        <p class="text-gray-600">Reviews will be displayed here.</p>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar - Booking Form -->
            <div class="lg:col-span-1 hidden lg:block">
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-200 sticky top-6">
                    <!-- Price -->
                    <div class="mb-6">
                        <div class="flex items-baseline gap-2 mb-1">
                            <span class="text-3xl font-bold text-gray-900"><?= $currency_symbol?><?= number_format($vehicle['price_per_day'])?></span>
                            <span class="text-gray-500">/day</span>
                        </div>
                        <p class="text-sm text-gray-500">Total before taxes</p>
                    </div>

                    <!-- Booking Form -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Pick-up Date</label>
                            <div class="relative">
                                <input type="text" id="pickup_datetime" placeholder="Oct 12th, 2023, 10:30am" readonly onclick="openCalendarModal('pickup')" class="w-full px-4 py-3 pr-10 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none cursor-pointer">
                                <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Return Date</label>
                            <div class="relative">
                                <input type="text" id="return_datetime" placeholder="Oct 21st, 2023, 11:00pm" readonly onclick="openCalendarModal('return')" class="w-full px-4 py-3 pr-10 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none cursor-pointer">
                                <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Pick-up Location</label>
                            <select name="pickup_location" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none cursor-pointer appearance-none" style="background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 4 5&quot;><path fill=&quot;%23666&quot; d=&quot;M2 0L0 2h4zm0 5L0 3h4z&quot;/></svg>'); background-repeat: no-repeat; background-position: right 16px center; background-size: 10px;">
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= htmlspecialchars($loc) ?>"><?= htmlspecialchars($loc) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Return Location</label>
                            <select name="return_location" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none cursor-pointer appearance-none" style="background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 4 5&quot;><path fill=&quot;%23666&quot; d=&quot;M2 0L0 2h4zm0 5L0 3h4z&quot;/></svg>'); background-repeat: no-repeat; background-position: right 16px center; background-size: 10px;">
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= htmlspecialchars($loc) ?>"><?= htmlspecialchars($loc) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Pricing Breakdown -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h3 class="font-bold text-gray-900 mb-4">Pricing Breakdown</h3>
                        
                        <!-- Minimum Days warning -->
                        <div id="min_days_warning" class="hidden p-3 mb-4 text-xs font-semibold text-amber-700 bg-amber-50 rounded-xl border border-amber-100 flex items-center gap-2">
                        </div>
                        
                        <div class="space-y-3 text-sm">
                            <!-- Base rate total -->
                            <div class="flex justify-between">
                                <span class="text-gray-600">Rental Price (<span id="breakdown_duration">0 days</span>)</span>
                                <span id="breakdown_base_total" class="font-semibold text-gray-900"><?= $currency_symbol?>0.00</span>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500">
                                <span id="breakdown_base_rate"><?= $currency_symbol?><?= number_format($vehicle['price_per_day'])?>/day</span>
                            </div>
                            
                            <!-- Discount package row -->
                            <div id="breakdown_discount_row" class="hidden flex justify-between">
                                <span id="breakdown_discount_label" class="text-gray-600">Discount</span>
                                <span id="breakdown_discount_total" class="font-semibold text-green-600">-<?= $currency_symbol?>0.00</span>
                            </div>
                            
                            <!-- Security deposit row -->
                            <div id="breakdown_deposit_row" class="hidden space-y-1">
                                <div class="flex justify-between">
                                    <span id="breakdown_deposit_label" class="text-gray-600">Security Deposit</span>
                                    <span id="breakdown_deposit_total" class="font-semibold text-gray-900"><?= $currency_symbol?>0.00</span>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500">
                                    <span id="breakdown_deposit_note">Payable</span>
                                </div>
                            </div>
                            
                            <!-- Total Price row -->
                            <div class="flex justify-between pt-3 border-t border-gray-200">
                                <span class="font-bold text-gray-900">Total Price Due</span>
                                <span id="breakdown_total_due" class="font-bold text-gray-900"><?= $currency_symbol?>0.00</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-4">* Your total rent amount is calculated dynamically depending on your selected pick-up and return dates.</p>
                    </div>
                    
                    <button onclick="continueToCheckout('desktop')" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-xl font-bold text-lg transition-all shadow-sm mt-6">
                        Continue Booking
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Book Now Button -->
        <div class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 shadow-lg z-50">
            <button onclick="openBookingModal()" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-xl font-bold text-lg transition-all shadow-sm">
                Book Now - <?= $currency_symbol?><?= number_format($vehicle['price_per_day'])?>/day
            </button>
        </div>
    </main>

    <!-- Mobile Booking Modal -->
    <div id="bookingModal" class="calendar-modal">
        <div class="calendar-modal-content max-w-lg">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Book This Car</h2>
                    <button onclick="closeBookingModal()" class="w-10 h-10 bg-gray-100 hover:bg-gray-200 rounded-xl flex items-center justify-center transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <!-- Price -->
                <div class="mb-6 pb-6 border-b border-gray-200">
                    <div class="flex items-baseline gap-2 mb-1">
                        <span class="text-3xl font-bold text-gray-900"><?= $currency_symbol?><?= number_format($vehicle['price_per_day'])?></span>
                        <span class="text-gray-500">/day</span>
                    </div>
                    <p class="text-sm text-gray-500">Total before taxes</p>
                </div>

                <!-- Booking Form -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Pick-up Date</label>
                        <div class="relative">
                            <input type="text" id="mobile_pickup_datetime" placeholder="Oct 12th, 2023, 10:30am" readonly onclick="openCalendarModal('pickup')" class="w-full px-4 py-3 pr-10 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none cursor-pointer">
                            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Return Date</label>
                        <div class="relative">
                            <input type="text" id="mobile_return_datetime" placeholder="Oct 21st, 2023, 11:00pm" readonly onclick="openCalendarModal('return')" class="w-full px-4 py-3 pr-10 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none cursor-pointer">
                            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Pick-up Location</label>
                        <select name="mobile_pickup_location" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none cursor-pointer appearance-none" style="background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 4 5&quot;><path fill=&quot;%23666&quot; d=&quot;M2 0L0 2h4zm0 5L0 3h4z&quot;/></svg>'); background-repeat: no-repeat; background-position: right 16px center; background-size: 10px;">
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= htmlspecialchars($loc) ?>"><?= htmlspecialchars($loc) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Return Location</label>
                        <select name="mobile_return_location" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none cursor-pointer appearance-none" style="background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 4 5&quot;><path fill=&quot;%23666&quot; d=&quot;M2 0L0 2h4zm0 5L0 3h4z&quot;/></svg>'); background-repeat: no-repeat; background-position: right 16px center; background-size: 10px;">
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= htmlspecialchars($loc) ?>"><?= htmlspecialchars($loc) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Mobile Pricing Breakdown -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h3 class="font-bold text-gray-900 mb-4 text-base">Pricing Breakdown</h3>
                        
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Rental Price (<span id="mobile_breakdown_duration">0 days</span>)</span>
                                <span id="mobile_breakdown_base_total" class="font-semibold text-gray-900"><?= $currency_symbol?>0.00</span>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500">
                                <span id="mobile_breakdown_base_rate"><?= $currency_symbol?><?= number_format($vehicle['price_per_day'])?>/day</span>
                            </div>
                            
                            <div id="mobile_breakdown_discount_row" class="hidden flex justify-between">
                                <span id="mobile_breakdown_discount_label" class="text-gray-600">Discount</span>
                                <span id="mobile_breakdown_discount_total" class="font-semibold text-green-600">-<?= $currency_symbol?>0.00</span>
                            </div>
                            
                            <div id="mobile_breakdown_deposit_row" class="hidden space-y-1">
                                <div class="flex justify-between">
                                    <span id="mobile_breakdown_deposit_label" class="text-gray-600">Security Deposit</span>
                                    <span id="mobile_breakdown_deposit_total" class="font-semibold text-gray-900"><?= $currency_symbol?>0.00</span>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500">
                                    <span id="mobile_breakdown_deposit_note">Payable</span>
                                </div>
                            </div>
                            
                            <div class="flex justify-between pt-3 border-t border-gray-200">
                                <span class="font-bold text-gray-900">Total Price Due</span>
                                <span id="mobile_breakdown_total_due" class="font-bold text-gray-900"><?= $currency_symbol?>0.00</span>
                            </div>
                        </div>
                    </div>

                    <button onclick="continueToCheckout('mobile')" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-xl font-bold text-lg transition-all shadow-sm mt-6">
                        Continue Booking
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lightbox Modal -->
    <div id="lightboxModal" class="calendar-modal" style="z-index: 9999;">
        <div class="fixed inset-0 bg-black/95 flex items-center justify-center">
            <button onclick="closeLightbox()" class="absolute top-6 right-6 w-12 h-12 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center transition-colors z-50">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
            
            <!-- Previous Button -->
            <button onclick="previousImage()" class="absolute left-6 w-12 h-12 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center transition-colors z-50">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>
            
            <!-- Next Button -->
            <button onclick="nextImage()" class="absolute right-6 w-12 h-12 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center transition-colors z-50">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
            
            <!-- Image Container -->
            <div class="max-w-6xl max-h-[90vh] w-full px-20">
                <img id="lightboxImage" src="" class="w-full h-full object-contain" alt="Vehicle Image">
                <div class="text-center mt-4">
                    <span id="lightboxCounter" class="text-white text-lg font-semibold"></span>
                </div>
            </div>
            
            <!-- Thumbnail Strip -->
            <div class="absolute bottom-6 left-1/2 -translate-x-1/2 max-w-4xl w-full px-6">
                <div id="lightboxThumbnails" class="flex gap-2 overflow-x-auto pb-2 justify-center">
                    <!-- Thumbnails will be inserted here by JavaScript -->
                </div>
            </div>
        </div>
    </div>

    </main>

    <!-- Maximum Booking Window Modal -->
    <div id="maxBookingModal" class="calendar-modal" style="z-index: 10000;">
        <div class="calendar-modal-content max-w-md w-full relative overflow-hidden p-8 shadow-2xl">
            <!-- Close Button -->
            <button onclick="closeMaxBookingModal()" class="absolute top-4 right-4 w-10 h-10 bg-gray-100 hover:bg-gray-200 rounded-xl flex items-center justify-center transition-colors">
                <svg class="w-5 h-5 stroke-[2.5] text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <div class="text-center space-y-6">
                <!-- Warning / Calendar Icon in blue (brand color) -->
                <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto shadow-md">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>

                <div class="space-y-2">
                    <h3 class="text-xl font-bold text-gray-900">Custom Booking Required</h3>
                    <p class="text-sm text-gray-600">The selected date exceeds our maximum online booking window of <span class="font-bold text-gray-900"><?= isset($settings['max_booking_advance_days']) && $settings['max_booking_advance_days'] > 0 ? (int)$settings['max_booking_advance_days'] : 30 ?></span> days in advance.</p>
                    <p class="text-sm text-gray-500">Please contact our team directly to book this vehicle.</p>
                </div>

                <!-- Call Box / Details -->
                <?php if (!empty($settings['company_phone'])): ?>
                <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4 flex flex-col items-center justify-center gap-1">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Company Contact Number</span>
                    <a href="tel:<?= htmlspecialchars($settings['company_phone']) ?>" class="text-lg font-bold text-blue-600 hover:text-blue-800 transition-colors"><?= htmlspecialchars($settings['company_phone']) ?></a>
                </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="flex flex-col gap-3">
                    <?php if (!empty($settings['company_phone'])): ?>
                    <a href="tel:<?= htmlspecialchars($settings['company_phone']) ?>" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold shadow-lg shadow-blue-100 transition-all flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        Call Us Now
                    </a>
                    <?php endif; ?>
                    <button type="button" onclick="closeMaxBookingModal()" class="w-full py-3 text-sm font-bold text-gray-500 hover:bg-gray-50 rounded-xl transition-colors">
                        Cancel & Modify Dates
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar Modal -->
    <div id="calendarModal" class="calendar-modal">
        <div class="calendar-modal-content">
            <div class="p-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900" id="modalTitle">Pick Up Date & Time</h2>
                    <button onclick="closeCalendarModal()" class="w-10 h-10 bg-gray-100 hover:bg-gray-200 rounded-xl flex items-center justify-center transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <!-- Calendar Container Wrapper -->
                <div id="calendarWrapper" class="mb-8">
                    <div id="modalCalendar"></div>
                </div>
                
                <!-- Time Selection -->
                <div id="timeSelection" class="hidden">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900" id="timeSelectionTitle">Choose Pick-up Time</h3>
                        <button type="button" onclick="showCalendarFromTime()" class="md:hidden text-sm font-semibold text-blue-600 hover:text-blue-800 flex items-center gap-1.5 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            Back to Date
                        </button>
                    </div>
                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-2 h-2 rounded-full bg-blue-600"></div>
                                <span class="text-sm font-semibold text-gray-700 uppercase tracking-wide">AM</span>
                            </div>
                            <div class="grid grid-cols-3 gap-2" id="amTimes"></div>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-2 h-2 rounded-full bg-blue-600"></div>
                                <span class="text-sm font-semibold text-gray-700 uppercase tracking-wide">PM</span>
                            </div>
                            <div class="grid grid-cols-3 gap-2" id="pmTimes"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/tenant_footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="/app/custom-select.js" defer></script>
    <script>
        // Vehicle images array from PHP
        const vehicleImages = <?= json_encode($all_images) ?>;
        let currentLightboxIndex = 0;
        
        let currentPickerType = null;
        let selectedDate = null;
        let selectedTime = null;
        let calendarInstance = null;

        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        // Lightbox functions
        function openLightbox(index) {
            currentLightboxIndex = index;
            const modal = document.getElementById('lightboxModal');
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            updateLightboxImage();
            generateLightboxThumbnails();
        }

        function closeLightbox() {
            const modal = document.getElementById('lightboxModal');
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        function updateLightboxImage() {
            const img = document.getElementById('lightboxImage');
            const counter = document.getElementById('lightboxCounter');
            img.src = vehicleImages[currentLightboxIndex];
            counter.textContent = `${currentLightboxIndex + 1} / ${vehicleImages.length}`;
        }

        function previousImage() {
            currentLightboxIndex = (currentLightboxIndex - 1 + vehicleImages.length) % vehicleImages.length;
            updateLightboxImage();
            updateThumbnailSelection();
        }

        function nextImage() {
            currentLightboxIndex = (currentLightboxIndex + 1) % vehicleImages.length;
            updateLightboxImage();
            updateThumbnailSelection();
        }

        function generateLightboxThumbnails() {
            const container = document.getElementById('lightboxThumbnails');
            container.innerHTML = '';
            
            vehicleImages.forEach((img, index) => {
                const thumb = document.createElement('div');
                thumb.className = `w-20 h-16 rounded-lg overflow-hidden cursor-pointer border-2 transition-all ${index === currentLightboxIndex ? 'border-white' : 'border-transparent opacity-60 hover:opacity-100'}`;
                thumb.onclick = () => {
                    currentLightboxIndex = index;
                    updateLightboxImage();
                    updateThumbnailSelection();
                };
                
                const thumbImg = document.createElement('img');
                thumbImg.src = img;
                thumbImg.className = 'w-full h-full object-cover';
                thumbImg.alt = `Thumbnail ${index + 1}`;
                
                thumb.appendChild(thumbImg);
                container.appendChild(thumb);
            });
        }

        function updateThumbnailSelection() {
            const thumbs = document.getElementById('lightboxThumbnails').children;
            Array.from(thumbs).forEach((thumb, index) => {
                if (index === currentLightboxIndex) {
                    thumb.className = 'w-20 h-16 rounded-lg overflow-hidden cursor-pointer border-2 border-white transition-all';
                } else {
                    thumb.className = 'w-20 h-16 rounded-lg overflow-hidden cursor-pointer border-2 border-transparent opacity-60 hover:opacity-100 transition-all';
                }
            });
        }

        // Keyboard navigation for lightbox
        document.addEventListener('keydown', function(e) {
            const lightbox = document.getElementById('lightboxModal');
            if (lightbox.classList.contains('active')) {
                if (e.key === 'ArrowLeft') previousImage();
                if (e.key === 'ArrowRight') nextImage();
                if (e.key === 'Escape') closeLightbox();
            }
        });

        // Tab switching
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Remove active state from all tabs
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('border-blue-600', 'text-blue-600');
                button.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Show selected tab content
            document.getElementById('content-' + tabName).classList.remove('hidden');
            
            // Add active state to selected tab
            const activeTab = document.getElementById('tab-' + tabName);
            activeTab.classList.remove('border-transparent', 'text-gray-500');
            activeTab.classList.add('border-blue-600', 'text-blue-600');
        }

        // Change main image
        function changeMainImage(imageSrc) {
            document.getElementById('mainImage').src = imageSrc;
        }

        // Custom error modal function
        function showErrorModal(message) {
            // Remove existing error modal if present
            const existingModal = document.getElementById('customErrorModal');
            if (existingModal) {
                existingModal.remove();
            }

            const modal = document.createElement('div');
            modal.id = 'customErrorModal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-lg p-6 max-w-md mx-4 shadow-xl animate-fade-in">
                    <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-red-100 rounded-full">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-600 text-center mb-6">${message}</p>
                    <button onclick="document.getElementById('customErrorModal').remove()" class="w-full px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold transition-colors">
                        OK
                    </button>
                </div>
            `;
            document.body.appendChild(modal);

            // Close modal on backdrop click
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        // Open/close booking modal
        function openBookingModal() {
            document.getElementById('bookingModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeBookingModal() {
            document.getElementById('bookingModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        function openCalendarModal(type) {
            currentPickerType = type;
            const modal = document.getElementById('calendarModal');
            const modalTitle = document.getElementById('modalTitle');
            
            modalTitle.textContent = 'Select Pick-up & Return Dates';
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Ensure calendar is visible when opening modal
            const wrapper = document.getElementById('calendarWrapper');
            if (wrapper) wrapper.classList.remove('hidden');
            
            // Initialize calendar if not already done
            if (!calendarInstance) {
                initializeCalendar();
            }
            
            // Reset time selection
            document.getElementById('timeSelection').classList.add('hidden');
            generateTimeSlots();
            
            // Reset time picker type to pickup
            modal.dataset.currentTimeType = 'pickup';
        }

        function closeCalendarModal() {
            const modal = document.getElementById('calendarModal');
            modal.classList.remove('active');
            
            // Only restore body scroll if mobile bookingModal is not open
            const bookingModal = document.getElementById('bookingModal');
            if (!bookingModal || !bookingModal.classList.contains('active')) {
                document.body.style.overflow = '';
            }
            
            selectedDate = null;
            selectedTime = null;
        }

        function openMaxBookingModal() {
            document.getElementById('maxBookingModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeMaxBookingModal() {
            document.getElementById('maxBookingModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        function initializeCalendar() {
            calendarInstance = flatpickr("#modalCalendar", {
                mode: "range",
                inline: true,
                showMonths: window.innerWidth < 768 ? 1 : 2,
                dateFormat: "Y-m-d",
                minDate: "today",
                maxDate: new Date(Date.now() + (<?= isset($settings['max_booking_advance_days']) && $settings['max_booking_advance_days'] > 0 ? (int)$settings['max_booking_advance_days'] : 30 ?> * 24 * 60 * 60 * 1000)),
                disable: <?= json_encode($disabled_dates) ?>,
                locale: {
                    firstDayOfWeek: 1
                },
                onChange: function(selectedDates) {
                    const today = new Date();
                    today.setHours(0,0,0,0);
                    const maxAdvanceDays = <?= isset($settings['max_booking_advance_days']) && $settings['max_booking_advance_days'] > 0 ? (int)$settings['max_booking_advance_days'] : 30 ?>;
                    const maxDate = new Date(today.getTime() + maxAdvanceDays * 24 * 60 * 60 * 1000);
                    
                    if (selectedDates.length > 0) {
                        const targetDate = selectedDates[selectedDates.length - 1];
                        if (targetDate > maxDate) {
                            calendarInstance.clear();
                            closeCalendarModal();
                            openMaxBookingModal();
                            return;
                        }
                    }

                    // When both pickup and return dates are selected
                    if (selectedDates.length === 2) {
                        selectedDate = selectedDates[0];
                        
                        // Format and display pickup date
                        const pickupDate = selectedDates[0];
                        const pickupFormatted = `${pickupDate.getDate()} ${months[pickupDate.getMonth()]}, ${pickupDate.getFullYear()}`;
                        const pickupInput = document.getElementById('pickup_datetime');
                        const mobilePickupInput = document.getElementById('mobile_pickup_datetime');
                        if (pickupInput) pickupInput.value = pickupFormatted;
                        if (mobilePickupInput) mobilePickupInput.value = pickupFormatted;
                        
                        // Format and display return date
                        const returnDate = selectedDates[1];
                        const returnFormatted = `${returnDate.getDate()} ${months[returnDate.getMonth()]}, ${returnDate.getFullYear()}`;
                        const returnInput = document.getElementById('return_datetime');
                        const mobileReturnInput = document.getElementById('mobile_return_datetime');
                        if (returnInput) returnInput.value = returnFormatted;
                        if (mobileReturnInput) mobileReturnInput.value = returnFormatted;
                        
                        // Show time selection for pickup first
                        document.getElementById('timeSelection').classList.remove('hidden');
                        document.getElementById('timeSelectionTitle').textContent = 'Choose Pick-up Time';
                        document.getElementById('calendarModal').dataset.currentTimeType = 'pickup';
                        
                        // Hide calendar on mobile when both dates are selected
                        if (window.innerWidth < 768) {
                            const wrapper = document.getElementById('calendarWrapper');
                            if (wrapper) wrapper.classList.add('hidden');
                        }
                    }
                }
            });
        }

        function continueToCheckout(type) {
            let pickupLoc = '';
            let returnLoc = '';
            let pickupDt = '';
            let returnDt = '';
            
            if (type === 'mobile') {
                pickupLoc = document.querySelector('select[name="mobile_pickup_location"]')?.value || '';
                returnLoc = document.querySelector('select[name="mobile_return_location"]')?.value || '';
                pickupDt = document.getElementById('mobile_pickup_datetime')?.value || '';
                returnDt = document.getElementById('mobile_return_datetime')?.value || '';
            } else {
                pickupLoc = document.querySelector('select[name="pickup_location"]')?.value || '';
                returnLoc = document.querySelector('select[name="return_location"]')?.value || '';
                pickupDt = document.getElementById('pickup_datetime')?.value || '';
                returnDt = document.getElementById('return_datetime')?.value || '';
            }
            
            if (!pickupDt || !returnDt) {
                showErrorModal('Please select both pick-up and return dates.');
                return;
            }
            
            // Validate that return date is not before pickup date
            if (pickupDateObj && returnDateObj) {
                if (returnDateObj < pickupDateObj) {
                    showErrorModal('Return date cannot be before pickup date.');
                    return;
                }
                
                // Validate minimum rental period
                const minDays = priceConfig.min_days || 1;
                const diffTime = returnDateObj - pickupDateObj;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                if (diffDays < minDays) {
                    showErrorModal(`Minimum rental period is ${minDays} days. Please select a longer rental period.`);
                    return;
                }
            }
            
            // Validate that pickup date is not in the past
            if (pickupDateObj) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const pickupDay = new Date(pickupDateObj);
                pickupDay.setHours(0, 0, 0, 0);
                
                if (pickupDay < today) {
                    showErrorModal('Pickup date cannot be in the past.');
                    return;
                }
            }

            // Validate that dates do not exceed maximum advance days
            if (pickupDateObj || returnDateObj) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const maxAdvanceDays = <?= isset($settings['max_booking_advance_days']) && $settings['max_booking_advance_days'] > 0 ? (int)$settings['max_booking_advance_days'] : 30 ?>;
                const maxDate = new Date(today.getTime() + maxAdvanceDays * 24 * 60 * 60 * 1000);
                
                if ((pickupDateObj && pickupDateObj > maxDate) || (returnDateObj && returnDateObj > maxDate)) {
                    openMaxBookingModal();
                    return;
                }
            }
            
            let pDateStr = '';
            let pTimeStr = '';
            let rDateStr = '';
            let rTimeStr = '';
            
            if (pickupDateObj) {
                const yyyy = pickupDateObj.getFullYear();
                const mm = String(pickupDateObj.getMonth() + 1).padStart(2, '0');
                const dd = String(pickupDateObj.getDate()).padStart(2, '0');
                pDateStr = `${yyyy}-${mm}-${dd}`;
                
                const hh = String(pickupDateObj.getHours()).padStart(2, '0');
                const min = String(pickupDateObj.getMinutes()).padStart(2, '0');
                pTimeStr = `${hh}:${min}`;
            }
            if (returnDateObj) {
                const yyyy = returnDateObj.getFullYear();
                const mm = String(returnDateObj.getMonth() + 1).padStart(2, '0');
                const dd = String(returnDateObj.getDate()).padStart(2, '0');
                rDateStr = `${yyyy}-${mm}-${dd}`;
                
                const hh = String(returnDateObj.getHours()).padStart(2, '0');
                const min = String(returnDateObj.getMinutes()).padStart(2, '0');
                rTimeStr = `${hh}:${min}`;
            }
            
            const url = `/templates/checkout.php?vehicle_id=<?= $vehicle['id'] ?>` +
                        `&pickup_location=` + encodeURIComponent(pickupLoc) +
                        `&return_location=` + encodeURIComponent(returnLoc) +
                        `&pickup_date=` + pDateStr +
                        `&pickup_time=` + pTimeStr +
                        `&return_date=` + rDateStr +
                        `&return_time=` + rTimeStr;
            
            window.location.href = url;
        }

        function showCalendarFromTime() {
            const wrapper = document.getElementById('calendarWrapper');
            if (wrapper) wrapper.classList.remove('hidden');
            document.getElementById('timeSelection').classList.add('hidden');
            if (calendarInstance) {
                calendarInstance.redraw();
            }
        }

        const businessHours = {
            opening: <?= json_encode($settings['opening_time'] ?? '08:00') ?>,
            closing: <?= json_encode($settings['closing_time'] ?? '18:00') ?>
        };

        const bookingNotice = {
            minNotice: <?= isset($settings['min_booking_notice']) ? (int)$settings['min_booking_notice'] : 48 ?>,
            noticeUnit: <?= json_encode($settings['booking_notice_unit'] ?? 'hours') ?>
        };

        function generateTimeSlots() {
            const amContainer = document.getElementById('amTimes');
            const pmContainer = document.getElementById('pmTimes');
            
            amContainer.innerHTML = '';
            pmContainer.innerHTML = '';
            
            let [openHours, openMinutes] = businessHours.opening.split(':').map(Number);
            let [closeHours, closeMinutes] = businessHours.closing.split(':').map(Number);
            
            // Calculate minimum booking time (current time + notice period)
            const now = new Date();
            let minBookingTime = new Date(now);
            
            if (bookingNotice.noticeUnit === 'hours') {
                minBookingTime.setHours(minBookingTime.getHours() + bookingNotice.minNotice);
            } else {
                minBookingTime.setDate(minBookingTime.getDate() + bookingNotice.minNotice);
            }
            
            let currentHour = openHours;
            let currentMinute = openMinutes;
            
            while (currentHour < closeHours || (currentHour === closeHours && currentMinute <= closeMinutes)) {
                let hour12 = currentHour % 12;
                if (hour12 === 0) hour12 = 12;
                let minuteStr = currentMinute.toString().padStart(2, '0');
                let period = currentHour >= 12 ? 'PM' : 'AM';
                let timeStr = `${hour12.toString().padStart(2, '0')}:${minuteStr}`;
                
                // Check if this time slot is available
                let isAvailable = true;
                
                if (selectedDate) {
                    // Create the full datetime for this slot
                    const slotTime = new Date(selectedDate);
                    slotTime.setHours(currentHour, currentMinute, 0, 0);
                    
                    // Check if slot time is before minimum booking time
                    if (slotTime < minBookingTime) {
                        isAvailable = false;
                    }
                }
                
                const slot = createTimeSlot(timeStr, period, isAvailable);
                if (period === 'AM') {
                    amContainer.appendChild(slot);
                } else {
                    pmContainer.appendChild(slot);
                }
                
                currentMinute += 30;
                if (currentMinute >= 60) {
                    currentMinute -= 60;
                    currentHour += 1;
                }
            }
        }

        function createTimeSlot(time, period, isAvailable = true) {
            const div = document.createElement('div');
            div.className = 'time-slot' + (isAvailable ? '' : ' disabled');
            div.textContent = time;
            
            if (isAvailable) {
                div.onclick = function() {
                    selectTime(time, period, div);
                };
            } else {
                div.style.opacity = '0.4';
                div.style.cursor = 'not-allowed';
                div.title = 'This time is not available due to minimum booking notice';
            }
            
            return div;
        }

        // Live Pricing Configuration from backend
        const priceConfig = {
            price_per_day: parseFloat(<?= json_encode($vehicle['price_per_day']) ?> || 0),
            daily_pricing: <?= json_encode(json_decode($vehicle['daily_pricing'] ?? '[]', true) ?? []) ?>,
            pricing_packages: <?= json_encode(json_decode($vehicle['pricing_packages'] ?? '[]', true) ?? []) ?>,
            require_deposit: parseInt(<?= json_encode($vehicle['require_deposit'] ?? 0) ?> || 0),
            deposit: parseFloat(<?= json_encode($vehicle['deposit'] ?? 0) ?> || 0),
            deposit_type: <?= json_encode($vehicle['deposit_type'] ?? 'collection') ?>,
            min_days: parseInt(<?= json_encode($vehicle['min_days'] ?? 1) ?> || 1),
            currency_symbol: <?= json_encode($currency_symbol) ?>
        };

        let pickupDateObj = null;
        let returnDateObj = null;

        function parseTimeString(timeStr) {
            const match = timeStr.toLowerCase().match(/^(\d+):(\d+)(am|pm)$/);
            if (!match) return { hours: 10, minutes: 0 };
            let hours = parseInt(match[1]);
            const minutes = parseInt(match[2]);
            const ampm = match[3];
            if (ampm === 'pm' && hours < 12) hours += 12;
            if (ampm === 'am' && hours === 12) hours = 0;
            return { hours, minutes };
        }

        function calculatePriceBreakdown() {
            if (!pickupDateObj || !returnDateObj) return;
            
            const diffMs = returnDateObj.getTime() - pickupDateObj.getTime();
            if (diffMs <= 0) {
                document.getElementById('breakdown_duration').innerText = '0 days';
                document.getElementById('breakdown_base_total').innerText = priceConfig.currency_symbol + '0.00';
                document.getElementById('breakdown_total_due').innerText = priceConfig.currency_symbol + '0.00';
                return;
            }
            
            const msPerDay = 24 * 60 * 60 * 1000;
            let diffDays = Math.floor(diffMs / msPerDay);
            const remainderMs = diffMs % msPerDay;
            if (remainderMs > 60 * 60 * 1000) {
                diffDays += 1;
            }
            if (diffDays < 1) diffDays = 1;
            
            // Check minimum days
            const minDays = priceConfig.min_days || 1;
            const warningEl = document.getElementById('min_days_warning');
            if (diffDays < minDays) {
                if (warningEl) {
                    warningEl.innerHTML = `
                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <span>Minimum rental period is ${minDays} days.</span>
                    `;
                    warningEl.classList.remove('hidden');
                }
            } else {
                if (warningEl) warningEl.classList.add('hidden');
            }
            
            // Calculate Base Price
            let totalBasePrice = 0;
            const tempDate = new Date(pickupDateObj);
            const daysOfWeek = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
            
            for (let i = 0; i < diffDays; i++) {
                const dayName = daysOfWeek[tempDate.getDay()];
                if (priceConfig.daily_pricing && priceConfig.daily_pricing[dayName]) {
                    totalBasePrice += parseFloat(priceConfig.daily_pricing[dayName]);
                } else {
                    totalBasePrice += priceConfig.price_per_day;
                }
                tempDate.setDate(tempDate.getDate() + 1);
            }
            
            // Calculate Discount
            let discountAmount = 0;
            let appliedPackage = null;
            if (priceConfig.pricing_packages && priceConfig.pricing_packages.length > 0) {
                const sortedPackages = [...priceConfig.pricing_packages].sort((a, b) => b.days - a.days);
                for (const pkg of sortedPackages) {
                    if (diffDays >= pkg.days) {
                        appliedPackage = pkg;
                        break;
                    }
                }
            }
            if (appliedPackage) {
                const discountPercent = parseFloat(appliedPackage.discount || 0);
                discountAmount = totalBasePrice * (discountPercent / 100);
            }
            
            // Calculate Deposit
            let depositAmount = 0;
            let depositNote = '';
            if (priceConfig.require_deposit === 1) {
                depositAmount = parseFloat(priceConfig.deposit || 0);
                if (priceConfig.deposit_type === 'booking') {
                    depositNote = 'Payable now at booking (Fully Refunded)';
                } else {
                    depositNote = 'Payable upon collection at desk (Fully Refunded)';
                }
            }
            
            const subtotal = totalBasePrice - discountAmount;
            const totalDue = subtotal + (priceConfig.deposit_type === 'booking' ? depositAmount : 0);
            
            const currency = priceConfig.currency_symbol;
            
            const setVal = (id, text) => {
                const el = document.getElementById(id);
                if (el) el.innerText = text;
            };
            const toggleClass = (id, cls, state) => {
                const el = document.getElementById(id);
                if (el) el.classList.toggle(cls, state);
            };

            // Update UI elements - Desktop & Mobile
            setVal('breakdown_duration', `${diffDays} day${diffDays > 1 ? 's' : ''}`);
            setVal('mobile_breakdown_duration', `${diffDays} day${diffDays > 1 ? 's' : ''}`);
            
            setVal('breakdown_base_rate', `${currency}${priceConfig.price_per_day.toFixed(2)}/day`);
            setVal('mobile_breakdown_base_rate', `${currency}${priceConfig.price_per_day.toFixed(2)}/day`);
            
            setVal('breakdown_base_total', `${currency}${totalBasePrice.toFixed(2)}`);
            setVal('mobile_breakdown_base_total', `${currency}${totalBasePrice.toFixed(2)}`);
            
            if (discountAmount > 0 && appliedPackage) {
                const labelText = `${appliedPackage.days}+ Day Discount (${appliedPackage.discount}%)`;
                const valText = `-${currency}${discountAmount.toFixed(2)}`;
                
                setVal('breakdown_discount_label', labelText);
                setVal('mobile_breakdown_discount_label', labelText);
                
                setVal('breakdown_discount_total', valText);
                setVal('mobile_breakdown_discount_total', valText);
                
                toggleClass('breakdown_discount_row', 'hidden', false);
                toggleClass('mobile_breakdown_discount_row', 'hidden', false);
            } else {
                toggleClass('breakdown_discount_row', 'hidden', true);
                toggleClass('mobile_breakdown_discount_row', 'hidden', true);
            }
            
            if (depositAmount > 0) {
                const labelText = `Security Deposit (${priceConfig.deposit_type === 'booking' ? 'Pay now' : 'Pay at collection'})`;
                const valText = `${currency}${depositAmount.toFixed(2)}`;
                
                setVal('breakdown_deposit_label', labelText);
                setVal('mobile_breakdown_deposit_label', labelText);
                
                setVal('breakdown_deposit_total', valText);
                setVal('mobile_breakdown_deposit_total', valText);
                
                setVal('breakdown_deposit_note', depositNote);
                setVal('mobile_breakdown_deposit_note', depositNote);
                
                toggleClass('breakdown_deposit_row', 'hidden', false);
                toggleClass('mobile_breakdown_deposit_row', 'hidden', false);
            } else {
                toggleClass('breakdown_deposit_row', 'hidden', true);
                toggleClass('mobile_breakdown_deposit_row', 'hidden', true);
            }
            
            setVal('breakdown_total_due', `${currency}${totalDue.toFixed(2)}`);
            setVal('mobile_breakdown_total_due', `${currency}${totalDue.toFixed(2)}`);
            
            // Update main book buttons
            const bookBtns = document.querySelectorAll('button[onclick="openBookingModal()"]');
            bookBtns.forEach(btn => {
                btn.innerHTML = `Book Now - ${currency}${totalDue.toFixed(2)}`;
            });
        }

        function selectTime(time, period, element) {
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.classList.remove('selected');
            });
            
            element.classList.add('selected');
            selectedTime = `${time}${period.toLowerCase()}`;
            
            setTimeout(() => {
                if (calendarInstance && selectedTime) {
                    const selectedDates = calendarInstance.selectedDates;
                    
                    if (selectedDates.length === 2) {
                        const modal = document.getElementById('calendarModal');
                        const currentTimeType = modal.dataset.currentTimeType || 'pickup';
                        const pickupDate = selectedDates[0];
                        const returnDate = selectedDates[1];
                        
                        const parsedTime = parseTimeString(selectedTime);
                        
                        if (currentTimeType === 'pickup') {
                            // Set pickup date with time
                            const pickupWithTime = new Date(pickupDate);
                            pickupWithTime.setHours(parsedTime.hours, parsedTime.minutes, 0, 0);
                            pickupDateObj = pickupWithTime;
                            
                            // Format and display pickup
                            const pickupFormatted = `${pickupDate.getDate()} ${months[pickupDate.getMonth()]}, ${selectedTime}`;
                            const pickupInput = document.getElementById('pickup_datetime');
                            const mobilePickupInput = document.getElementById('mobile_pickup_datetime');
                            if (pickupInput) pickupInput.value = pickupFormatted;
                            if (mobilePickupInput) mobilePickupInput.value = pickupFormatted;
                            
                            // Switch to return time selection
                            document.getElementById('timeSelectionTitle').textContent = 'Choose Return Time';
                            modal.dataset.currentTimeType = 'return';
                            
                            // Update selectedDate to return date for time slot generation
                            selectedDate = returnDate;
                            
                            // Regenerate time slots for return date
                            generateTimeSlots();
                            
                            // Reset time selection UI
                            document.querySelectorAll('.time-slot').forEach(slot => {
                                slot.classList.remove('selected');
                            });
                        } else {
                            // Set return date with time
                            const returnWithTime = new Date(returnDate);
                            returnWithTime.setHours(parsedTime.hours, parsedTime.minutes, 0, 0);
                            returnDateObj = returnWithTime;
                            
                            // Format and display return
                            const returnFormatted = `${returnDate.getDate()} ${months[returnDate.getMonth()]}, ${selectedTime}`;
                            const returnInput = document.getElementById('return_datetime');
                            const mobileReturnInput = document.getElementById('mobile_return_datetime');
                            if (returnInput) returnInput.value = returnFormatted;
                            if (mobileReturnInput) mobileReturnInput.value = returnFormatted;
                            
                            calculatePriceBreakdown();
                            closeCalendarModal();
                        }
                    }
                }
            }, 300);
        }

        function parseUrlDateTime(str) {
            if (!str) return null;
            str = decodeURIComponent(str).trim();
            
            // Format 1: YYYY-MM-DD HH:MM
            if (/^\d{4}-\d{2}-\d{2}/.test(str)) {
                return new Date(str.replace(/-/g, '/'));
            }
            
            // Format 2: "D MMM, h:mmam" or similar e.g. "12 Oct, 10:30am"
            const parts = str.match(/^(\d+)\s+([A-Za-z]+),?\s+(\d+):(\d+)(am|pm)$/i);
            if (parts) {
                const day = parseInt(parts[1]);
                const monthName = parts[2].substring(0, 3).toLowerCase();
                const monthIdx = months.map(m => m.toLowerCase()).indexOf(monthName);
                let hours = parseInt(parts[3]);
                const minutes = parseInt(parts[4]);
                const ampm = parts[5].toLowerCase();
                
                if (ampm === 'pm' && hours < 12) hours += 12;
                if (ampm === 'am' && hours === 12) hours = 0;
                
                if (monthIdx !== -1) {
                    const d = new Date();
                    d.setDate(day);
                    d.setMonth(monthIdx);
                    d.setHours(hours, minutes, 0, 0);
                    
                    const now = new Date();
                    if (d.getMonth() < now.getMonth() || (d.getMonth() === now.getMonth() && d.getDate() < now.getDate())) {
                        d.setFullYear(now.getFullYear() + 1);
                    } else {
                        d.setFullYear(now.getFullYear());
                    }
                    return d;
                }
            }
            
            const parsed = new Date(str);
            if (!isNaN(parsed.getTime())) return parsed;
            return null;
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (window.flatpickr) {
                flatpickr.l10ns.default.firstDayOfWeek = 1;
            }

            // Set default date values
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            tomorrow.setHours(10, 0, 0, 0);
            
            const fourDaysLater = new Date();
            fourDaysLater.setDate(fourDaysLater.getDate() + 4);
            fourDaysLater.setHours(12, 0, 0, 0);
            
            const urlParams = new URLSearchParams(window.location.search);
            const urlPickup = urlParams.get('pickup');
            const urlReturn = urlParams.get('return');
            const urlPickupLoc = urlParams.get('pickup_location') || urlParams.get('location');
            const urlReturnLoc = urlParams.get('return_location') || urlParams.get('location');
            
            const parsedPickup = parseUrlDateTime(urlPickup);
            const parsedReturn = parseUrlDateTime(urlReturn);
            
            pickupDateObj = parsedPickup || tomorrow;
            returnDateObj = parsedReturn || fourDaysLater;

            // Set locations in selects if passed
            if (urlPickupLoc) {
                const select = document.querySelector('select[name="pickup_location"]');
                if (select) select.value = decodeURIComponent(urlPickupLoc);
                const mobileSelect = document.querySelector('select[name="mobile_pickup_location"]');
                if (mobileSelect) mobileSelect.value = decodeURIComponent(urlPickupLoc);
            }
            if (urlReturnLoc) {
                const select = document.querySelector('select[name="return_location"]');
                if (select) select.value = decodeURIComponent(urlReturnLoc);
                const mobileSelect = document.querySelector('select[name="mobile_return_location"]');
                if (mobileSelect) mobileSelect.value = decodeURIComponent(urlReturnLoc);
            }

            const pickupDatetime = document.getElementById('pickup_datetime');
            const returnDatetime = document.getElementById('return_datetime');
            const mobilePickupDatetime = document.getElementById('mobile_pickup_datetime');
            const mobileReturnDatetime = document.getElementById('mobile_return_datetime');
            
            const formatForInput = (dateObj) => {
                const ampm = dateObj.getHours() >= 12 ? 'pm' : 'am';
                let hours = dateObj.getHours() % 12;
                if (hours === 0) hours = 12;
                const minutes = String(dateObj.getMinutes()).padStart(2, '0');
                return `${dateObj.getDate()} ${months[dateObj.getMonth()]}, ${String(hours).padStart(2, '0')}:${minutes}${ampm}`;
            };
            
            const pickupFormatted = formatForInput(pickupDateObj);
            const returnFormatted = formatForInput(returnDateObj);

            if (pickupDatetime) {
                pickupDatetime.value = pickupFormatted;
            }
            if (returnDatetime) {
                returnDatetime.value = returnFormatted;
            }
            if (mobilePickupDatetime) {
                mobilePickupDatetime.value = pickupFormatted;
            }
            if (mobileReturnDatetime) {
                mobileReturnDatetime.value = returnFormatted;
            }

            // Perform initial price breakdown load
            calculatePriceBreakdown();

            // Close modal when clicking outside
            document.getElementById('calendarModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeCalendarModal();
                }
            });
        });
    </script>
</body>
</html>
