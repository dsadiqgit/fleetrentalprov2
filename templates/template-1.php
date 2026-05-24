<?php
require_once __DIR__ . '/includes/tenant_init.php';

$tenant_id = getTenantId();
$tenant = getTenant();
$pdo = getDB();

// Get website content
$stmt = $pdo->prepare("SELECT * FROM website_content WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$content = $stmt->fetch();

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
    <!-- Universal Tenant Header (Includes Branding, Navigation & Styles) -->
    <?php include __DIR__ . '/includes/tenant_header.php'; ?>
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

    <?php foreach ($sections_order as $section): ?>
    <?php if ($section === 'hero' && !($content['hero_hidden'] ?? 0)): ?>
    <!-- Hero Section -->
    <section class="relative h-[85vh] min-h-[700px] bg-cover bg-center flex items-end rounded-[20px] m-[20px]"
        style="background-image: url('<?= htmlspecialchars($content['hero_image'])?>');">
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent rounded-[20px]"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full pb-60">
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
                                    <label class="block text-[10px] uppercase font-bold text-gray-400">Drop-off</label>
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
    <section id="fleet" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-end mb-12 gap-6">
                <div>
                    <h2 class="text-[32px] font-bold text-gray-900 mb-3 tracking-tight">Top picks vehicle this month
                    </h2>
                    <p class="text-gray-500 text-base max-w-xl">Experience the epitome of amazing journey with our top
                        picks.</p>
                </div>
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
            <?php
        else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($featured_vehicles as $vehicle): ?>
                <a href="/templates/vehicle-booking.php?id=<?= $vehicle['id']?>" class="block group">
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
                </a>
                <?php
            endforeach; ?>
            </div>
            <?php
        endif; ?>
        </div>
    </section>

    <?php elseif ($section === 'services' && !($content['services_hidden'] ?? 0)): ?>
    <!-- Services Section -->
    <section id="services" class="py-24 bg-[#0F1219]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-8 items-start mb-16">
                <div>
                    <span class="text-blue-500 font-bold tracking-widest text-sm uppercase mb-4 block editable" data-field="services_title">
                        <?= htmlspecialchars($content['services_title'] ?? 'Our Services')?>
                    </span>
                    <h2 class="text-[40px] leading-[1.2] font-bold text-white max-w-xl editable" data-field="services_subtitle">
                        <?= htmlspecialchars($content['services_subtitle'] ?? 'Our Premier services for your car rental needs')?>
                    </h2>
                </div>
                <div class="lg:pt-8">
                    <p class="text-gray-400 text-lg leading-relaxed max-w-xl editable" data-field="services_description">
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
                    <h3 class="text-xl font-bold text-white mb-4 editable" data-field="service1_title">
                        <?= htmlspecialchars($content['service1_title'] ?? 'Well-Maintained Car')?>
                    </h3>
                    <p class="text-gray-400 leading-relaxed editable" data-field="service1_text">
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
                    <h3 class="text-xl font-bold text-white mb-4 editable" data-field="service2_title">
                        <?= htmlspecialchars($content['service2_title'] ?? 'Secure Payments')?>
                    </h3>
                    <p class="text-gray-400 leading-relaxed editable" data-field="service2_text">
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
                    <h3 class="text-xl font-bold text-white mb-4 editable" data-field="service3_title">
                        <?= htmlspecialchars($content['service3_title'] ?? '24/7 Support')?>
                    </h3>
                    <p class="text-gray-400 leading-relaxed editable" data-field="service3_text">
                        <?= htmlspecialchars($content['service3_text'] ?? 'We understand that the journey does not always run smoothly. Therefore, our customer support team is ready to help you 24/7.')?>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <?php
    elseif ($section === 'about' && !($content['about_hidden'] ?? 0)): ?>
    <!-- About Section -->
    <section id="about" class="py-24 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
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
                                    <div>
                                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Phone
                                        </p>
                                        <p class="text-lg">
                                            <?= htmlspecialchars($content['contact_phone'] ?? '')?>
                                        </p>
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
        document.addEventListener('DOMContentLoaded', function () {
            if (window.flatpickr) {
                flatpickr.l10ns.default.firstDayOfWeek = 1;
            }

            flatpickr("#date_range", {
                mode: "range",
                dateFormat: "Y-m-d",
                minDate: "today",
                showMonths: 2,
                locale: {
                    firstDayOfWeek: 1
                },
                onChange: function (selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        document.getElementById('pickup_display').textContent = instance.formatDate(selectedDates[0], "j M Y");
                        document.getElementById('dropoff_display').textContent = instance.formatDate(selectedDates[1], "j M Y");
                    }
                }
            });

            // Times populator
            const times = ["08:00 AM", "09:00 AM", "10:00 AM", "11:00 AM", "12:00 PM", "01:00 PM", "02:00 PM", "03:00 PM", "04:00 PM", "05:00 PM", "06:00 PM"];
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