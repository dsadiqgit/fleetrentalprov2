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

$currency_code = $settings['currency'] ?? 'GBP';
$currency_symbols = ['GBP' => '£', 'USD' => '$', 'EUR' => '€'];
$currency_symbol = $currency_symbols[$currency_code] ?? $currency_code;

$require_verification = isset($settings['require_license_verification']) ? (bool)$settings['require_license_verification'] : true;
$min_age = intval($vehicle['min_age'] ?? 0);
$min_days_required = max(1, intval($vehicle['min_days'] ?? 1));

$stmt = $pdo->prepare("SELECT pickup_date, return_date FROM bookings WHERE vehicle_id = ? AND status NOT IN ('cancelled', 'completed')");
$stmt->execute([$vehicle_id]);
$booked_dates = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

        .flatpickr-months {
            padding: 10px 10px 5px !important;
            background: white !important;
            display: flex !important; /* Fixed broken hidden months */
        }

        .flatpickr-current-month {
            font-size: 16px !important;
            font-weight: 700 !important;
            color: #1f2937 !important;
        }

        .flatpickr-month {
            height: auto !important;
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
            background: #1f2937 !important;
            color: white !important;
            border: none !important;
            font-weight: 600 !important;
            border-radius: 8px !important;
        }

        .flatpickr-day.inRange {
            background: #f3f4f6 !important;
            border-color: transparent !important;
            box-shadow: none !important;
            border-radius: 8px !important;
            color: #1f2937 !important;
        }
    </style>
</head>

<body class="bg-gray-50">

    <?php include __DIR__ . '/includes/tenant_header.php'; ?>
    
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
                            <button class="p-2.5 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                                </svg>
                            </button>
                            <button class="p-2.5 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                                </svg>
                            </button>
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
                                <?= nl2br(htmlspecialchars($vehicle['description'] ?? 'Dolor enim eu tortor urna sed duis nulla. Aliquam vestibulum, nulla odio nisl vitae. In aliquet pellentesque aenean hac vestibulum turpis mi bibendum diam. Tempor integer aliquam in vitae malesuada fringilla. Elit nisi in eleifend sed nisi. Pulvinar at orci, proin imperdiet commodo consectetur convallis risus.'))?>
                            </p>
                            <ul class="mt-4 space-y-2 text-gray-600 text-[15px]">
                                <li class="flex items-start">
                                    <span class="mr-2">•</span>
                                    <span>Ipsum sit mattis nulla quam nulla. Gravida id gravida ac enim mauris id.Diam elit, orci, tincidunt aenean tempus.</span>
                                </li>
                                <li class="flex items-start">
                                    <span class="mr-2">•</span>
                                    <span>Non pellentesque congue eget consectetur turpis.</span>
                                </li>
                                <li class="flex items-start">
                                    <span class="mr-2">•</span>
                                    <span>Sapien, dictum molestie sem tempor. Diam elit, orci, tincidunt aenean tempus.</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Car Specifications -->
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 mb-4">Car Specifications</h2>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Engine/Cylinders:</p>
                                        <p class="font-semibold text-gray-900"><?= htmlspecialchars($vehicle['engine'] ?? '6 Cylinders')?></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Max Torque:</p>
                                        <p class="font-semibold text-gray-900"><?= htmlspecialchars($vehicle['torque'] ?? '630Nm')?></p>
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
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Pick-up Location</label>
                            <input type="text" value="New York, NY" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        </div>
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
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Drop-off Location</label>
                            <input type="text" value="New York, NY" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Drop-off Date</label>
                            <div class="relative">
                                <input type="text" id="return_datetime" placeholder="Oct 21st, 2023, 11:00pm" readonly onclick="openCalendarModal('return')" class="w-full px-4 py-3 pr-10 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none cursor-pointer">
                                <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing Breakdown -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h3 class="font-bold text-gray-900 mb-4">Pricing Breakdown</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Rental price</span>
                                <span class="font-semibold text-gray-900"><?= $currency_symbol?>4340</span>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500">
                                <span><?= $currency_symbol?><?= number_format($vehicle['price_per_day'])?>/day (x7 days)</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">3+ day discount</span>
                                <span class="font-semibold text-green-600">-<?= $currency_symbol?>217</span>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500">
                                <span>Extended trip discount (5%)</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Refundable deposit</span>
                                <span class="font-semibold text-gray-900"><?= $currency_symbol?>500</span>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500">
                                <span>Refunded by Oct 14th</span>
                            </div>
                            <div class="flex justify-between pt-3 border-t border-gray-200">
                                <span class="font-bold text-gray-900">Total Price Due</span>
                                <span class="font-bold text-gray-900"><?= $currency_symbol?>4623</span>
                            </div>
                            <div class="text-xs text-gray-500">
                                <?= $currency_symbol?>500 Refunded by Oct 14th
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-4">* Your total rent amount will be calculated depending on the pick-up and drop-off dates.</p>
                    </div>
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
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Pick-up Location</label>
                        <input type="text" value="New York, NY" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>
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
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Drop-off Location</label>
                        <input type="text" value="New York, NY" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Drop-off Date</label>
                        <div class="relative">
                            <input type="text" id="mobile_return_datetime" placeholder="Oct 21st, 2023, 11:00pm" readonly onclick="openCalendarModal('return')" class="w-full px-4 py-3 pr-10 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none cursor-pointer">
                            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <button class="w-full bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-xl font-bold text-lg transition-all shadow-sm mt-6">
                        Continue to Book
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
                
                <!-- Calendar Container -->
                <div id="modalCalendar" class="mb-8"></div>
                
                <!-- Time Selection -->
                <div id="timeSelection" class="hidden">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Choose a time</h3>
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
            
            modalTitle.textContent = type === 'pickup' ? 'Pick Up Date & Time' : 'Return Date & Time';
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Initialize calendar if not already done
            if (!calendarInstance) {
                initializeCalendar();
            }
            
            // Reset time selection
            document.getElementById('timeSelection').classList.add('hidden');
            generateTimeSlots();
        }

        function closeCalendarModal() {
            const modal = document.getElementById('calendarModal');
            modal.classList.remove('active');
            document.body.style.overflow = '';
            selectedDate = null;
            selectedTime = null;
        }

        function initializeCalendar() {
            calendarInstance = flatpickr("#modalCalendar", {
                inline: true,
                showMonths: 1,
                dateFormat: "Y-m-d",
                minDate: "today",
                locale: {
                    firstDayOfWeek: 1
                },
                onChange: function(selectedDates) {
                    if (selectedDates.length > 0) {
                        selectedDate = selectedDates[0];
                        document.getElementById('timeSelection').classList.remove('hidden');
                    }
                }
            });
        }

        function generateTimeSlots() {
            const amContainer = document.getElementById('amTimes');
            const pmContainer = document.getElementById('pmTimes');
            
            amContainer.innerHTML = '';
            pmContainer.innerHTML = '';
            
            // AM times: 10:00, 12:00, 02:00
            const amTimes = ['10:00', '12:00', '02:00'];
            amTimes.forEach(time => {
                const slot = createTimeSlot(time, 'AM');
                amContainer.appendChild(slot);
            });
            
            // PM times: 04:00, 06:00, 08:00, 10:00
            const pmTimes = ['04:00', '06:00', '08:00', '10:00'];
            pmTimes.forEach(time => {
                const slot = createTimeSlot(time, 'PM');
                pmContainer.appendChild(slot);
            });
        }

        function createTimeSlot(time, period) {
            const div = document.createElement('div');
            div.className = 'time-slot';
            div.textContent = time;
            div.onclick = function() {
                selectTime(time, period, div);
            };
            return div;
        }

        function selectTime(time, period, element) {
            // Remove previous selection
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.classList.remove('selected');
            });
            
            element.classList.add('selected');
            selectedTime = `${time}${period.toLowerCase()}`;
            
            // Update the input field and close modal
            setTimeout(() => {
                if (selectedDate && selectedTime) {
                    const inputId = currentPickerType === 'pickup' ? 'pickup_datetime' : 'return_datetime';
                    const mobileInputId = currentPickerType === 'pickup' ? 'mobile_pickup_datetime' : 'mobile_return_datetime';
                    const input = document.getElementById(inputId);
                    const mobileInput = document.getElementById(mobileInputId);
                    const dayName = days[selectedDate.getDay()];
                    const formattedValue = `${selectedDate.getDate()} ${months[selectedDate.getMonth()]}, ${selectedTime}`;
                    
                    if (input) input.value = formattedValue;
                    if (mobileInput) mobileInput.value = formattedValue;
                    
                    closeCalendarModal();
                }
            }, 300);
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (window.flatpickr) {
                flatpickr.l10ns.default.firstDayOfWeek = 1;
            }

            // Set default date values
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            
            const fourDaysLater = new Date();
            fourDaysLater.setDate(fourDaysLater.getDate() + 4);
            
            const pickupDatetime = document.getElementById('pickup_datetime');
            const returnDatetime = document.getElementById('return_datetime');
            
            if (pickupDatetime && !pickupDatetime.value) {
                pickupDatetime.value = `${tomorrow.getDate()} ${months[tomorrow.getMonth()]}, 10:00am`;
            }
            if (returnDatetime && !returnDatetime.value) {
                returnDatetime.value = `${fourDaysLater.getDate()} ${months[fourDaysLater.getMonth()]}, 12:00pm`;
            }

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
