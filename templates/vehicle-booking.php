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
    
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm text-gray-600 mb-6">
            <a href="/" class="hover:text-gray-900">🏠</a>
            <span>/</span>
            <a href="/templates/template-1-preview.php" class="hover:text-gray-900">Popular Cars</a>
            <span>/</span>
            <span class="text-gray-900 font-medium"><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'])?></span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left Sidebar - Booking Form -->
            <div class="lg:col-span-3 space-y-6">
                <!-- Date & Time Inputs -->
                <div class="bg-white rounded-2xl p-6 shadow-sm">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-2">Pick Up Date & Time*</label>
                            <div class="relative">
                                <input type="text" id="pickup_datetime" placeholder="--" readonly
                                    class="w-full px-4 py-3 pr-10 bg-white text-gray-900 placeholder-gray-400 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none cursor-pointer">
                                <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-2">Return Date & Time*</label>
                            <div class="relative">
                                <input type="text" id="return_datetime" placeholder="--" readonly
                                    class="w-full px-4 py-3 pr-10 bg-white text-gray-900 placeholder-gray-400 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none cursor-pointer">
                                <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-2">Pick Up Location*</label>
                            <input type="text" placeholder="Enter Location"
                                class="w-full px-4 py-3 bg-white text-gray-900 placeholder-gray-400 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        </div>
                    </div>
                </div>

                <!-- Rental Info -->
                <div class="bg-white rounded-2xl p-6 shadow-sm">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Rental Info</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Rent Per Day</span>
                            <span class="text-xl font-bold text-gray-900"><?= $currency_symbol?><?= number_format($vehicle['price_per_day'])?>  </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Rent Per Hour</span>
                            <span class="text-xl font-bold text-gray-900"><?= $currency_symbol?><?= number_format($vehicle['price_per_day'] / 8)?>  </span>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-4">* After selecting days and times, your calculated rent amount and summary will be displayed below.</p>
                    <button class="w-full mt-6 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-semibold transition-colors">
                        Reserve Now
                    </button>
                </div>
            </div>

            <!-- Center - Vehicle Display -->
            <div class="lg:col-span-6 space-y-6">
                <!-- Vehicle Image & Title -->
                <div class="bg-[#FCECF3] rounded-[24px] p-8 relative overflow-hidden min-h-[340px] flex flex-col justify-between">
                    <!-- Brand Watermark Background Text -->
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none select-none overflow-hidden">
                        <span class="text-[120px] font-black text-pink-200/25 tracking-wider uppercase leading-none">
                            <?= htmlspecialchars($vehicle['brand'])?>
                        </span>
                    </div>

                    <!-- Top Row: Logo, Title & Bookmark -->
                    <div class="relative z-10 flex justify-between items-start w-full">
                        <div class="flex items-center gap-3">
                            <!-- Logo Brand Icon (using Unsplash logo wrapper or customizable icon) -->
                            <div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center p-2">
                                <img src="/assets/images/fleet-logo-black-small.png" onerror="this.src='https://cdn-icons-png.flaticon.com/512/744/744465.png'" class="w-full h-full object-contain" alt="Brand Logo">
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900 leading-tight"><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'])?></h1>
                                <p class="text-sm text-gray-500 font-medium"><?= htmlspecialchars($vehicle['category'] ?? 'Corvette Stingray')?></p>
                            </div>
                        </div>
                        <button class="w-10 h-10 bg-white/80 backdrop-blur hover:bg-white text-gray-700 rounded-xl shadow-sm flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Vehicle Main Image Container -->
                    <div class="relative z-10 my-4 flex justify-center items-center w-full">
                        <?php if ($image_url): ?>
                        <img src="<?= htmlspecialchars($image_url)?>" class="max-h-[180px] object-contain drop-shadow-xl hover:scale-105 transition-transform duration-300" alt="<?= htmlspecialchars($vehicle['brand'])?>">
                        <?php endif; ?>
                    </div>

                    <!-- Bottom Row: Color Dots Pagination -->
                    <div class="relative z-10 flex justify-end gap-1.5 w-full">
                        <button class="w-3.5 h-3.5 rounded-full bg-red-600 border border-white ring-1 ring-offset-1 ring-red-500"></button>
                        <button class="w-3.5 h-3.5 rounded-full bg-slate-500 border border-white"></button>
                        <button class="w-3.5 h-3.5 rounded-full bg-slate-100 border border-gray-300"></button>
                    </div>
                </div>

                <!-- Vehicle Information -->
                <div class="bg-white rounded-2xl p-6 shadow-sm">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Vehicle Information</h2>
                    <p class="text-gray-600 leading-relaxed mb-6">
                        <?= nl2br(htmlspecialchars($vehicle['description'] ?? 'The ' . $vehicle['brand'] . ' ' . $vehicle['model'] . ' is a premium vehicle offering exceptional performance and comfort.'))?>
                    </p>
                    
                    <!-- Specs Grid -->
                    <div class="grid grid-cols-4 gap-4">
                        <div class="text-center p-4 bg-purple-50 rounded-xl">
                            <div class="text-purple-600 mb-2">⚡</div>
                            <div class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($vehicle['engine'] ?? '3.0L')?></div>
                            <div class="text-xs text-gray-600 mt-1">ENGINE</div>
                        </div>
                        <div class="text-center p-4 bg-pink-50 rounded-xl">
                            <div class="text-pink-600 mb-2">🔥</div>
                            <div class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($vehicle['horsepower'] ?? '450')?></div>
                            <div class="text-xs text-gray-600 mt-1">HORSEPOWER</div>
                        </div>
                        <div class="text-center p-4 bg-yellow-50 rounded-xl">
                            <div class="text-yellow-600 mb-2">⚡</div>
                            <div class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($vehicle['top_speed'] ?? '180')?> Mph</div>
                            <div class="text-xs text-gray-600 mt-1">TOP SPEED</div>
                        </div>
                        <div class="text-center p-4 bg-blue-50 rounded-xl">
                            <div class="text-blue-600 mb-2">⚙️</div>
                            <div class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($vehicle['transmission'] ?? 'Auto')?></div>
                            <div class="text-xs text-gray-600 mt-1">TRANSMISSION</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar - Company Info -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-2xl p-6 shadow-sm sticky top-6">
                    <div class="text-center mb-6">
                        <?php if (!empty($tenant['logo'])): ?>
                        <img src="<?= htmlspecialchars($tenant['logo'])?>" alt="<?= htmlspecialchars($tenant['name'])?>" class="h-16 mx-auto mb-4">
                        <?php endif; ?>
                        <h3 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($tenant['name'])?></h3>
                        <p class="text-gray-600 text-sm">Car Rental Service</p>
                        <div class="flex items-center justify-center gap-1 mt-2">
                            <?php for($i = 0; $i < 5; $i++): ?>
                            <svg class="w-5 h-5 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            <?php endfor; ?>
                            <span class="text-sm text-gray-600 ml-2">(234+ ratings)</span>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <button class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-900 rounded-lg font-semibold transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                            </svg>
                            Send Message
                        </button>
                        <button class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg font-semibold transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Visit Profile
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/includes/tenant_footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        let currentPickerType = null;
        let selectedDate = null;

        document.addEventListener('DOMContentLoaded', function () {
            if (window.flatpickr) {
                flatpickr.l10ns.default.firstDayOfWeek = 1;
            }

            // Set default date values for tomorrow and 4 days later
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            
            const fourDaysLater = new Date();
            fourDaysLater.setDate(fourDaysLater.getDate() + 4);

            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            
            // Format defaults
            const defaultPickupText = `${tomorrow.getDate()} ${months[tomorrow.getMonth()]}, 10:00am`;
            const defaultReturnText = `${fourDaysLater.getDate()} ${months[fourDaysLater.getMonth()]}, 12:00pm`;

            // Set input values on load
            const pickupDatetime = document.getElementById('pickup_datetime');
            const returnDatetime = document.getElementById('return_datetime');
            
            if (pickupDatetime && !pickupDatetime.value) {
                pickupDatetime.value = defaultPickupText;
            }
            if (returnDatetime && !returnDatetime.value) {
                returnDatetime.value = defaultReturnText;
            }

            // Pickup datetime picker
            if (pickupDatetime) {
                flatpickr("#pickup_datetime", {
                    showMonths: 3,
                    dateFormat: "d M, Y",
                    minDate: "today",
                    defaultDate: tomorrow,
                    locale: {
                        firstDayOfWeek: 1
                    },
                    onChange: function(selectedDates, dateStr, instance) {
                        if (selectedDates.length > 0) {
                            selectedDate = selectedDates[0];
                            pickupDatetime.value = `${selectedDate.getDate()} ${months[selectedDate.getMonth()]}, 10:00am`;
                        }
                    }
                });
            }

            // Return datetime picker
            if (returnDatetime) {
                flatpickr("#return_datetime", {
                    showMonths: 3,
                    dateFormat: "d M, Y",
                    minDate: "today",
                    defaultDate: fourDaysLater,
                    locale: {
                        firstDayOfWeek: 1
                    },
                    onChange: function(selectedDates, dateStr, instance) {
                        if (selectedDates.length > 0) {
                            selectedDate = selectedDates[0];
                            returnDatetime.value = `${selectedDate.getDate()} ${months[selectedDate.getMonth()]} ${selectedDate.getFullYear()}, 12:00pm`;
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
