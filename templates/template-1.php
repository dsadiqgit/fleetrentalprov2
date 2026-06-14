<?php
require_once __DIR__ . '/includes/tenant_init.php';

$tenant_id = getTenantId();
$tenant = getTenant();
$pdo = getDB();

// Get website content
$stmt = $pdo->prepare("SELECT * FROM website_content WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$content = $stmt->fetch();

// Get tenant settings for phone / WhatsApp
$stmt = $pdo->prepare("SELECT company_phone, whatsapp_number, whatsapp_enabled FROM tenant_settings WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$ts = $stmt->fetch() ?: [];
$company_phone = $ts['company_phone'] ?? '';
$whatsapp_number = $ts['whatsapp_number'] ?? '';
$whatsapp_enabled = !empty($ts['whatsapp_enabled']);

// Use defaults if no content exists
if (!$content) {
    $content = [
        'company_name' => $tenant['name'],
        'hero_title' => 'Premium Car Rentals Made Easy',
        'hero_subtitle' => 'Discover the freedom, explore the world with our wide range of premium vehicles',
        'hero_image' => 'https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
        'about_title' => 'About ' . $tenant['name'],
        'about_text' => 'We are a leading car rental company committed to providing exceptional service and quality vehicles to our customers.',
        'about_image' => 'https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        'contact_phone' => '+1 (555) 123-4567',
        'contact_email' => 'info@yourcompany.com',
        'contact_address' => '123 Main Street, New York, NY 10001',
        'font_family' => 'Inter',
        'primary_color' => '#3b82f6',
        'secondary_color' => '#1e40af',
        'header_color' => '#ffffff',
        'text_color' => '#111827',
        'background_color' => '#ffffff',
        'hero_button_text' => 'Rent a Car'
    ];
}

// Get sections order
$sections_order_json = $content['sections_order'] ?? '[]';
$sections_order = json_decode($sections_order_json, true);
if (empty($sections_order)) {
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

// Get featured vehicles (limit tracking or all based on logic)
$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE tenant_id = ? AND availability = 1 ORDER BY display_order ASC, created_at DESC LIMIT 6");
$stmt->execute([$tenant_id]);
$featured_vehicles = $stmt->fetchAll();

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
    <title>
        <?= htmlspecialchars($content['hero_title'] ?? '')?> -
        <?= htmlspecialchars($content['company_name'] ?? $tenant['name'] ?? '')?>
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=<?= str_replace(' ', '+', $content['font_family'] ?? 'Inter')?>:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
    <style>
        .flatpickr-day.prevMonthDay,
        .flatpickr-day.nextMonthDay,
        .flatpickr-day.hidden {
            display: inline-block !important;
            visibility: visible !important;
            opacity: 0.3 !important;
            color: #ccc !important;
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
</head>


<body class="bg-gray-50">

    <!-- Universal Tenant Header (Includes Branding, Navigation & Styles) -->
    <?php include __DIR__ . '/includes/tenant_header.php'; ?>

    <?php foreach ($sections_order as $section): ?>
    <?php if ($section === 'hero' && !($content['hero_hidden'] ?? 0)): ?>
    <!-- Hero Section -->
    <section class="relative h-[85vh] min-h-[700px] bg-cover bg-center flex items-end rounded-[20px] m-[20px]"
        style="background-image: url('<?= htmlspecialchars($content['hero_image'])?>');">
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent rounded-[20px]"></div>

        <div class="relative max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 w-full pb-60">
            <div class="text-white max-w-3xl">
                <h1 class="text-5xl md:text-7xl font-bold mb-4 tracking-tight leading-tight">
                    <?= htmlspecialchars($content['hero_title'] ?? 'Rent a Car for Every Journey')?>
                </h1>
                <div class="opacity-0 h-0 overflow-hidden">
                    <?= htmlspecialchars($content['hero_subtitle'] ?? '')?>
                </div>
            </div>
        </div>

        <!-- Search Bar Floating -->
        <div class="absolute bottom-12 left-1/2 transform -translate-x-1/2 w-full max-w-7xl px-4 z-[90]">
            <div class="bg-white rounded-2xl shadow-2xl p-6 sm:p-5 border border-gray-100">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 flex items-center gap-3 cursor-pointer relative"
                        id="date_range_picker">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <label class="block text-[10px] uppercase font-bold text-gray-400">Pick-up</label>
                                    <span id="pickup_display" class="font-bold text-gray-900 text-sm">Select
                                        dates</span>
                                </div>
                                <div class="w-px h-8 bg-gray-200 mx-4"></div>
                                <div class="flex-1">
                                    <label class="block text-[10px] uppercase font-bold text-gray-400">Return</label>
                                    <span id="dropoff_display" class="font-bold text-gray-900 text-sm">Select
                                        dates</span>
                                </div>
                            </div>
                            <input type="text" id="date_range"
                                class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                        </div>
                    </div>

                    <div
                        class="bg-gray-50 p-4 rounded-xl border border-gray-100 flex items-center gap-3 cursor-pointer relative time-dropdown-container">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="w-full">
                            <label class="block text-[10px] uppercase font-bold text-gray-400">Time</label>
                            <div class="flex items-center justify-between">
                                <span id="pickup_time_display" class="font-bold text-gray-900">10:00 AM</span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                        <div
                            class="absolute top-full left-0 w-full bg-white mt-2 rounded-xl shadow-2xl border border-gray-100 hidden z-[100] max-h-[300px] overflow-y-auto time-options-list custom-scrollbar">
                        </div>
                    </div>

                    <button onclick="performSearch()"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-xl font-bold shadow-lg shadow-blue-200 transition-all flex items-center justify-center gap-2">
                        Search Vehicles
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <?php
    elseif ($section === 'vehicles' && !($content['vehicles_hidden'] ?? 0)): ?>
    <!-- Vehicles Section -->
    <style>#fleet-carousel::-webkit-scrollbar{display:none}</style>
    <section id="fleet" class="py-24 bg-white">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex items-end justify-between mb-10">
                <div>
                    <h2 class="text-[32px] font-bold text-gray-900 mb-3 tracking-tight">Top picks vehicle this month</h2>
                    <p class="text-gray-500 text-base max-w-xl">Experience the epitome of amazing journey with our top picks.</p>
                </div>
                <?php if (!empty($featured_vehicles)): ?>
                <div class="flex gap-3 pb-1 flex-shrink-0">
                    <button id="fleet-prev"
                        class="w-10 h-10 rounded-full border-2 border-gray-300 flex items-center justify-center text-gray-600 hover:border-gray-500 hover:text-gray-900 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <button id="fleet-next"
                        class="w-10 h-10 rounded-full flex items-center justify-center text-white transition-opacity hover:opacity-80"
                        style="background-color:<?= htmlspecialchars($content['primary_color'] ?? '#111827')?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <?php if (empty($featured_vehicles)): ?>
            <div class="text-center py-16">
                <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                </svg>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No vehicles are available at the moment</h3>
                <p class="text-gray-600">Please check back later or contact us for availability</p>
            </div>
            <?php else: ?>
            <!-- Carousel -->
            <div id="fleet-carousel" class="flex gap-5 overflow-x-auto pb-2" style="-ms-overflow-style:none;scrollbar-width:none;">
                <?php
                $wa_available = $whatsapp_enabled && !empty($whatsapp_number);
                $wa_number = $wa_available ? preg_replace('/\D/', '', $whatsapp_number) : '';
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $base_url = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? '');
                foreach ($featured_vehicles as $vehicle):
                    $v_images = json_decode($vehicle['images'], true);
                    $v_img = is_array($v_images) ? $v_images[0] : ($vehicle['images'] ?: '');
                    if (empty($v_img)) $v_img = '/assets/images/placeholder-img.webp';
                    $v_ml = intval($vehicle['mileage_limit'] ?? 0);
                    $v_mileage = $v_ml > 0 ? ($v_ml >= 1000 ? round($v_ml / 1000, 1) . 'k mi' : $v_ml . ' mi') : 'Unlimited';
                ?>
                <a href="/templates/vehicle-details.php?id=<?= $vehicle['id']?>"
                   class="group flex-none bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-xl transition-all duration-300"
                   style="min-width:280px;width:280px;">
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
                                <?php if (!empty($wa_number)): ?>
                                <button type="button"
                                    onclick="event.preventDefault(); event.stopPropagation(); window.open('https://wa.me/<?= $wa_number ?>?text=<?= rawurlencode('I would like to enquire about the ' . $vehicle['brand'] . ' ' . $vehicle['model'] . ' - ' . $base_url . '/templates/vehicle-details.php?id=' . $vehicle['id']) ?>', '_blank');"
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
            <!-- See All -->
            <div class="flex justify-end mt-6">
                <a href="/templates/fleet.php"
                   class="text-gray-600 hover:text-gray-900 font-medium flex items-center gap-2 transition-colors group">
                    See all
                    <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0-4 4m4-4H3"/>
                    </svg>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <script>
    (function(){
        var c=document.getElementById('fleet-carousel');
        var p=document.getElementById('fleet-prev');
        var n=document.getElementById('fleet-next');
        if(p)p.addEventListener('click',function(){c.scrollBy({left:-300,behavior:'smooth'});});
        if(n)n.addEventListener('click',function(){c.scrollBy({left:300,behavior:'smooth'});});
    })();
    </script>

    <?php elseif ($section === 'services' && !($content['services_hidden'] ?? 0)): ?>
    <!-- Services Section -->
    <?php
    $service_primary = $content['primary_color'] ?? '#2563eb';
    $service_cards = [
        [
            'title_field' => 'service1_title',
            'text_field' => 'service1_text',
            'icon_field' => 'service1_icon',
            'default_svg' => '<svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5-4.5v5.25c0 5.25-3.7 10.2-9 11.5-5.3-1.3-9-6.25-9-11.5V5.5L12 3l9 2.5z" /></svg>'
        ],
        [
            'title_field' => 'service2_title',
            'text_field' => 'service2_text',
            'icon_field' => 'service2_icon',
            'default_svg' => '<svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10m-12 7h14a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v9a2 2 0 002 2z" /></svg>'
        ],
        [
            'title_field' => 'service3_title',
            'text_field' => 'service3_text',
            'icon_field' => 'service3_icon',
            'default_svg' => '<svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6l3.5 3.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
        ]
    ];
    ?>
    <section id="services" class="py-24 relative overflow-hidden" style="--service-primary: <?= htmlspecialchars($service_primary, ENT_QUOTES) ?>; background: radial-gradient(circle at 20% 20%, rgba(255,255,255,0.07), transparent 45%), linear-gradient(130deg, #030a1c 0%, #061a3d 50%, #020712 100%);">
        <div class="absolute inset-0 pointer-events-none">
            <div class="w-72 h-72 bg-[rgba(255,255,255,0.08)] blur-[140px] rounded-full absolute -top-16 -left-10"></div>
            <div class="w-80 h-80 bg-[rgba(37,99,235,0.25)] blur-[160px] rounded-full absolute bottom-0 right-0"></div>
        </div>
        <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-xs sm:text-sm font-semibold tracking-[0.35em] uppercase mb-4" style="color: var(--service-primary, #2563eb);">
                <?= htmlspecialchars($content['services_title'] ?? 'Our Services')?>
            </p>
            <h2 class="text-3xl sm:text-4xl lg:text-[42px] font-bold text-white leading-tight">
                <?= htmlspecialchars($content['services_subtitle'] ?? 'Our Premier services for your car rental needs')?>
            </h2>
            <p class="text-blue-100/80 text-base sm:text-lg leading-relaxed mt-6">
                <?= htmlspecialchars($content['services_description'] ?? 'We take pride in providing top-notch solutions for a seamless rental experience you can trust')?>
            </p>
        </div>

        <div class="relative z-10 mt-14 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-3 gap-6">
                <?php foreach ($service_cards as $card):
                    $icon_value = trim($content[$card['icon_field']] ?? '');
                    $title_value = $content[$card['title_field']] ?? 'Premium Service';
                    $text_value = $content[$card['text_field']] ?? '';
                ?>
                <div class="rounded-3xl p-8 border border-white/10 bg-white/5 backdrop-blur-md shadow-[0_35px_60px_rgba(3,7,18,0.55)] hover:border-white/30 hover:-translate-y-1.5 transition-all duration-300">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-white bg-white/10 border border-white/10 shadow-[0_20px_30px_rgba(37,99,235,0.25)]">
                        <?php if (!empty($icon_value)): ?>
                            <img src="<?= htmlspecialchars($icon_value)?>" alt="<?= htmlspecialchars($title_value)?> icon" class="w-10 h-10 object-contain drop-shadow-[0_8px_20px_rgba(37,99,235,0.45)]">
                        <?php else: ?>
                            <?= $card['default_svg'] ?>
                        <?php endif; ?>
                    </div>
                    <h3 class="text-white text-xl font-semibold mt-6">
                        <?= htmlspecialchars($title_value)?>
                    </h3>
                    <p class="text-blue-100/80 leading-relaxed mt-3">
                        <?= htmlspecialchars($text_value)?>
                    </p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- FAQs Section -->
    <section class="bg-gray-50 py-20">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">
                    Frequently Asked <span class="text-[var(--primary-color,#2563eb)]">Questions</span>
                </h2>
                <p class="mt-4 text-gray-500 max-w-xl mx-auto text-sm sm:text-base leading-relaxed">
                    Everything you need to know about renting with us. Can't find what you\'re looking for? Reach out to our team.
                </p>
            </div>

            <div class="grid lg:grid-cols-5 gap-10 items-start">
                <!-- Accordion -->
                <div class="lg:col-span-3 space-y-4">
                    <?php
                    $faq_items = [
                        ['q' => 'What documents do I need to rent a car?', 'a' => 'You will need a valid driving licence, proof of address, and a credit or debit card in the driver\'s name. International drivers may need an IDP.'],
                        ['q' => 'Can I modify or cancel my booking?', 'a' => 'Yes. You can cancel or modify your booking free of charge up to 24 hours before the scheduled pick-up time.'],
                        ['q' => 'Is insurance included in the rental price?', 'a' => 'Third-party insurance is included as standard. Comprehensive and excess-waiver options are available at checkout.'],
                        ['q' => 'What is the fuel policy?', 'a' => 'All vehicles are supplied with a full tank and should be returned with a full tank to avoid refuelling charges.'],
                        ['q' => 'Do you offer delivery and collection?', 'a' => 'Yes. We can deliver to your home, hotel, or airport. Charges vary by distance and are shown during checkout.'],
                    ];
                    foreach ($faq_items as $i => $faq):
                    ?>
                    <div class="faq-item bg-white border border-gray-200 rounded-2xl overflow-hidden transition-all duration-300 hover:shadow-sm">
                        <button type="button" onclick="toggleFaq(<?= $i ?>)" class="faq-trigger w-full flex items-center justify-between px-6 py-5 text-left focus:outline-none">
                            <span class="font-semibold text-gray-900 text-sm sm:text-base pr-4"><?= htmlspecialchars($faq['q']) ?></span>
                            <svg id="faq-icon-<?= $i ?>" class="w-5 h-5 text-gray-400 flex-shrink-0 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div id="faq-body-<?= $i ?>" class="faq-body hidden px-6 pb-5">
                            <p class="text-gray-500 text-sm leading-relaxed"><?= htmlspecialchars($faq['a']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Contact Card -->
                <div class="lg:col-span-2">
                    <div class="bg-white border border-gray-200 rounded-2xl p-8 text-center sticky top-24">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"></path>
                                <circle cx="9" cy="10" r="1.5"></circle>
                                <circle cx="15" cy="10" r="1.5"></circle>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Can't find answer to your question?</h3>
                        <p class="text-gray-500 text-sm leading-relaxed mb-8">
                            Didn't find what you were looking for? Our team is ready to help and will respond as quickly as possible with clear, helpful answers tailored to your needs.
                        </p>
                        <a href="<?= $tenant_home ?>#contact" class="inline-flex items-center justify-center px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow-lg shadow-blue-600/20 hover:shadow-blue-600/30">
                            Get in Touch
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php
    elseif ($section === 'about' && !($content['about_hidden'] ?? 0)): ?>
    <!-- About Section -->
    <section id="about" class="py-24 bg-gray-50">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div class="relative">
                    <img src="<?= htmlspecialchars($content['about_image'])?>"
                        class="rounded-[40px] shadow-2xl relative z-10">
                    <div class="absolute -bottom-8 -right-8 w-64 h-64 bg-blue-600/10 rounded-full blur-3xl"></div>
                </div>
                <div>
                    <span class="text-blue-600 font-bold tracking-widest text-sm uppercase mb-4 block">About Our
                        Company</span>
                    <h2 class="text-4xl font-extrabold text-gray-900 mb-8 leading-tight">
                        <?= htmlspecialchars($content['about_title'] ?? '')?>
                    </h2>
                    <p class="text-gray-600 text-lg leading-relaxed mb-10">
                        <?= nl2br(htmlspecialchars($content['about_text'] ?? ''))?>
                    </p>
                    <div class="grid grid-cols-2 gap-8">
                        <div class="p-6 bg-white rounded-3xl border border-gray-100">
                            <div class="text-3xl font-black text-blue-600 mb-2">
                                <?= htmlspecialchars($content['stat_vehicles'] ?? '1+')?>
                            </div>
                            <div class="text-sm font-bold text-gray-400 uppercase">
                                <?= htmlspecialchars($content['stat_vehicles_label'] ?? 'Vehicles')?>
                            </div>
                        </div>
                        <div class="p-6 bg-white rounded-3xl border border-gray-100">
                            <div class="text-3xl font-black text-blue-600 mb-2">
                                <?= htmlspecialchars($content['stat_support'] ?? '24/7')?>
                            </div>
                            <div class="text-sm font-bold text-gray-400 uppercase">
                                <?= htmlspecialchars($content['stat_support_label'] ?? 'Support')?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php
    elseif ($section === 'testimonials' && !($content['testimonials_hidden'] ?? 0)): ?>
    <!-- Testimonials -->
    <section class="py-24 bg-white overflow-hidden">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-extrabold text-gray-900 mb-4">
                    <?= htmlspecialchars($content['testimonial_title'] ?? 'Our Customers')?>
                </h2>
                <p class="text-gray-500 text-lg max-w-2xl mx-auto">
                    <?= htmlspecialchars($content['testimonial_subtitle'] ?? 'Experience shared by our happy clients')?>
                </p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <?php for ($i = 1; $i <= 3; $i++): ?>
                <div
                    class="bg-gray-50 p-8 rounded-[32px] border border-gray-100 hover:bg-white hover:shadow-2xl transition duration-300">
                    <div class="flex items-center gap-4 mb-6">
                        <img src="<?= htmlspecialchars($content["review{$i}_image"]
                            ?? "https://i.pravatar.cc/100?img=$i" )?>" class="w-14 h-14 rounded-full object-cover ring-4
                        ring-white shadow-lg">
                        <div>
                            <h4 class="font-bold text-gray-900">
                                <?= htmlspecialchars($content["review{$i}_name"] ?? 'Happy Customer')?>
                            </h4>
                            <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">
                                <?= htmlspecialchars($content["review{$i}_role"] ?? 'Customer')?>
                            </p>
                        </div>
                    </div>
                    <p class="text-gray-600 italic leading-relaxed mb-6">
                        "
                        <?= htmlspecialchars($content["review{$i}_text"] ?? 'Great experience renting with FleetRentalPro! Highly recommend.')?>
                        "
                    </p>
                    <div class="flex text-yellow-400 gap-1">
                        <?php for ($s = 0; $s < ($content["review{$i}_stars"] ?? 5); $s++): ?>
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                            <path
                                d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z">
                            </path>
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
    elseif ($section === 'contact' && !($content['contact_hidden'] ?? 0)): ?>
    <!-- Contact -->
    <section id="contact" class="py-24 bg-gray-50">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-[40px] shadow-2xl overflow-hidden border border-gray-100">
                <div class="grid lg:grid-cols-5">
                    <div class="lg:col-span-2 bg-gray-900 p-12 text-white flex flex-col justify-between">
                        <div>
                            <h2 class="text-3xl font-bold mb-8">
                                <?= htmlspecialchars($content['contact_title'] ?? 'Get in Touch')?>
                            </h2>
                            <div class="space-y-8">
                                <div class="flex items-start gap-4">
                                    <div
                                        class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path
                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                                                stroke-width="2"></path>
                                        </svg>
                                    </div>
                                    <div class="grid grid-cols-2 gap-6">
                                        <div>
                                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Phone
                                            </p>
                                            <p class="text-lg">
                                                <?= htmlspecialchars($company_phone ?: ($content['contact_phone'] ?? ''))?>
                                            </p>
                                        </div>
                                        <?php if ($whatsapp_enabled && !empty($whatsapp_number)): ?>
                                        <div>
                                            <p class="text-xs font-bold text-green-400 uppercase tracking-widest mb-1">WhatsApp</p>
                                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $whatsapp_number)?>" target="_blank" rel="noopener noreferrer" class="text-lg hover:text-green-400 transition"><?= htmlspecialchars($whatsapp_number)?></a>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex items-start gap-4">
                                    <div
                                        class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path
                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                                stroke-width="2"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Email
                                        </p>
                                        <p class="text-lg">
                                            <?= htmlspecialchars($content['contact_email'] ?? '')?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="lg:col-span-3 p-12">
                        <form class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Your
                                    Name</label>
                                <input type="text"
                                    class="w-full bg-gray-50 border-0 rounded-xl px-4 py-4 focus:ring-2 focus:ring-blue-600 transition"
                                    placeholder="John Doe">
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Email
                                    Address</label>
                                <input type="email"
                                    class="w-full bg-gray-50 border-0 rounded-xl px-4 py-4 focus:ring-2 focus:ring-blue-600 transition"
                                    placeholder="john@example.com">
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Phone
                                    Number</label>
                                <input type="tel"
                                    class="w-full bg-gray-50 border-0 rounded-xl px-4 py-4 focus:ring-2 focus:ring-blue-600 transition"
                                    placeholder="+1 (555) 000-0000">
                            </div>
                            <div class="md:col-span-2">
                                <label
                                    class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Message</label>
                                <textarea rows="4"
                                    class="w-full bg-gray-50 border-0 rounded-xl px-4 py-4 focus:ring-2 focus:ring-blue-600 transition"
                                    placeholder="How can we help?"></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <button
                                    class="w-full py-5 bg-blue-600 text-white rounded-2xl font-black text-lg hover:bg-blue-700 transition shadow-xl shadow-blue-100">Send
                                    Message</button>
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

    <!-- Universal Tenant Footer -->
    <?php include __DIR__ . '/includes/tenant_footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
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
            if (window.flatpickr) {
                flatpickr.l10ns.default.firstDayOfWeek = 1;
            }

            flatpickr("#date_range", {
                mode: "range",
                dateFormat: "Y-m-d",
                minDate: "today",
                maxDate: new Date(Date.now() + (<?= isset($settings['max_booking_advance_days']) && $settings['max_booking_advance_days'] > 0 ? (int)$settings['max_booking_advance_days'] : 30 ?> * 24 * 60 * 60 * 1000)),
                showMonths: 2,
                locale: {
                    firstDayOfWeek: 1
                },
                onChange: function (selectedDates, dateStr, instance) {
                    const today = new Date();
                    today.setHours(0,0,0,0);
                    const maxAdvanceDays = <?= isset($settings['max_booking_advance_days']) && $settings['max_booking_advance_days'] > 0 ? (int)$settings['max_booking_advance_days'] : 30 ?>;
                    const maxDate = new Date(today.getTime() + maxAdvanceDays * 24 * 60 * 60 * 1000);
                    
                    if (selectedDates.length > 0) {
                        const targetDate = selectedDates[selectedDates.length - 1];
                        if (targetDate > maxDate) {
                            instance.clear();
                            showMaxBookingModal();
                            return;
                        }
                    }

                    if (selectedDates.length === 2) {
                        // Validate that end date is not before start date (Flatpickr handles this, but double-check)
                        if (selectedDates[1] < selectedDates[0]) {
                            showErrorModal('Return date cannot be before pickup date.');
                            instance.clear();
                            return;
                        }
                        
                        document.getElementById('pickup_display').textContent = instance.formatDate(selectedDates[0], "j M Y");
                        document.getElementById('dropoff_display').textContent = instance.formatDate(selectedDates[1], "j M Y");
                    }
                }
            });

            // Generate time slots dynamically based on business hours
            const openingTime = <?= json_encode($settings['opening_time'] ?? '08:00') ?>;
            const closingTime = <?= json_encode($settings['closing_time'] ?? '18:00') ?>;
            const minBookingNotice = <?= isset($settings['min_booking_notice']) ? (int)$settings['min_booking_notice'] : 48 ?>;
            const noticeUnit = <?= json_encode($settings['booking_notice_unit'] ?? 'hours') ?>;
            
            function generateTimeSlots() {
                const times = [];
                let [openHours, openMinutes] = openingTime.split(':').map(Number);
                let [closeHours, closeMinutes] = closingTime.split(':').map(Number);
                
                // Calculate minimum booking time (current time + notice period)
                const now = new Date();
                let minBookingTime = new Date(now);
                
                if (noticeUnit === 'hours') {
                    minBookingTime.setHours(minBookingTime.getHours() + minBookingNotice);
                } else {
                    minBookingTime.setDate(minBookingTime.getDate() + minBookingNotice);
                }
                
                let currentHour = openHours;
                let currentMinute = openMinutes;
                
                while (currentHour < closeHours || (currentHour === closeHours && currentMinute <= closeMinutes)) {
                    let hourStr = currentHour.toString().padStart(2, '0');
                    let minuteStr = currentMinute.toString().padStart(2, '0');
                    let displayHour = currentHour > 12 ? currentHour - 12 : (currentHour === 0 ? 12 : currentHour);
                    let ampm = currentHour >= 12 ? 'PM' : 'AM';
                    
                    // Check if this time slot is available
                    let isAvailable = true;
                    
                    const pickupDate = pickupInstance ? pickupInstance.selectedDates[0] : null;
                    if (pickupDate) {
                        // Create the full datetime for this slot
                        const slotTime = new Date(pickupDate);
                        slotTime.setHours(currentHour, currentMinute, 0, 0);
                        
                        // Check if slot time is before minimum booking time
                        if (slotTime < minBookingTime) {
                            isAvailable = false;
                        }
                    }
                    
                    if (isAvailable) {
                        times.push(`${displayHour}:${minuteStr} ${ampm}`);
                    }
                    
                    currentMinute += 30;
                    if (currentMinute >= 60) {
                        currentMinute -= 60;
                        currentHour += 1;
                    }
                }
                return times;
            }
            
            const times = generateTimeSlots();
            document.querySelectorAll('.time-dropdown-container').forEach(container => {
                const list = container.querySelector('.time-options-list');
                const display = container.querySelector('#pickup_time_display');
                if (!display) return;
                times.forEach(t => {
                    const d = document.createElement('div');
                    d.className = 'px-4 py-3 hover:bg-blue-50 cursor-pointer text-sm font-medium';
                    d.textContent = t;
                    d.onclick = (e) => {
                        e.stopPropagation();
                        display.textContent = t;
                        list.classList.add('hidden');
                    };
                    list.appendChild(d);
                });
                container.onclick = (e) => {
                    e.stopPropagation();
                    list.classList.toggle('hidden');
                };
            });
        });

        function performSearch() {
            window.location.href = "/templates/fleet.php?tenant=<?= $tenant['subdomain']?>";
        }
    </script>
</body>

</html>