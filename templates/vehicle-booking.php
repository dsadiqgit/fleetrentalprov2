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
        .flatpickr-calendar { border: none !important; box-shadow: 0 20px 25px -5px rgba(0,0,0,.1) !important; border-radius: 20px !important; padding: 12px !important; background: #fff !important; z-index: 99 !important; }
        .flatpickr-day.booked-date { background: #fee2e2 !important; border-color: transparent !important; color: #ef4444 !important; text-decoration: line-through; opacity: .7; }
        .flatpickr-day.startRange { background: #2563eb !important; color: #fff !important; border-radius: 12px !important; box-shadow: 10px 0 0 #eff6ff !important; z-index: 2; }
        .flatpickr-day.endRange { background: #2563eb !important; color: #fff !important; border-radius: 12px !important; box-shadow: -10px 0 0 #eff6ff !important; z-index: 2; }
        .flatpickr-day.startRange.endRange { box-shadow: none !important; }
        .flatpickr-day.inRange { background: #eff6ff !important; border-color: transparent !important; box-shadow: -10px 0 0 #eff6ff, 10px 0 0 #eff6ff !important; border-radius: 0 !important; color: #2563eb !important; }
        .flatpickr-day.today { border-color: #2563eb !important; color: #2563eb !important; font-weight: 800 !important; }
        .flatpickr-months { display: none !important; }
        .custom-month-year-container { display: flex; align-items: center; justify-content: space-between; padding: 4px 4px 16px 4px; }
        .custom-fp-arrow { display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; border: none; background: #f1f5f9; color: #475569; cursor: pointer; transition: all .2s; flex-shrink: 0; }
        .custom-fp-arrow:hover { background: #e2e8f0; color: #0f172a; }
        .custom-fp-arrow svg { width: 18px; height: 18px; }
        .custom-month-label { font-size: 16px; font-weight: 700; color: #0f172a; letter-spacing: -.02em; text-align: center; flex: 1; }
        @media (min-width: 1024px) { .sticky-widget { position: sticky; top: 40px; } }
        .flatpickr-calendar.inline { width: 100% !important; box-shadow: none !important; border: none !important; background: transparent !important; }
        .dayContainer { display: block !important; width: 100% !important; min-width: 100% !important; max-width: 100% !important; }
        .flatpickr-day { display: inline-block !important; width: 14.2857% !important; max-width: 14.2857% !important; height: 48px !important; line-height: 48px !important; margin: 0 !important; border-radius: 12px !important; box-sizing: border-box !important; }
    </style>
</head>

<body class="bg-[#F8FAFC]">

    <?php include __DIR__ . '/includes/tenant_header.php'; ?>
    <main class="px-4 sm:px-6 lg:px-8 py-8" x-data="bookingFlow()">
        <!-- Progress Stepper -->
        <div class="flex items-center justify-center mb-10 max-w-2xl mx-auto">
            <div class="flex items-center w-full">
                <template x-for="(label, idx) in stepLabels" :key="idx">
                    <template x-if="true">
                        <div class="contents">
                            <div class="relative flex flex-col items-center flex-1">
                                <div class="w-10 h-10 flex items-center justify-center rounded-full transition-all duration-300 text-sm font-bold" :class="step > idx+1 ? 'bg-green-500 text-white shadow-lg' : step === idx+1 ? 'bg-blue-600 text-white shadow-lg' : 'bg-slate-200 text-slate-500'">
                                    <span x-text="step > idx+1 ? '✓' : idx+1"></span>
                                </div>
                                <span class="absolute top-12 text-[11px] font-semibold whitespace-nowrap" :class="step === idx+1 ? 'text-blue-600' : 'text-slate-400'" x-text="label"></span>
                            </div>
                            <div x-show="idx < stepLabels.length - 1" class="flex-1 h-1 rounded-full transition-all duration-500" :class="step > idx+1 ? 'bg-green-500' : 'bg-slate-200'"></div>
                        </div>
                    </template>
                </template>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-10 gap-8 mt-8">
            <!-- Left Column -->
            <div class="lg:col-span-7 order-1 space-y-6">
                <!-- STEP 1: Vehicle + Calendar -->
                <div x-show="step === 1" class="space-y-6">
                    <div class="bg-white rounded-3xl overflow-hidden shadow-sm border border-slate-100">
                        <div class="w-full bg-slate-100 relative overflow-hidden h-[400px]">
                            <?php if ($image_url): ?>
                            <img src="<?= htmlspecialchars($image_url)?>" class="w-full h-full object-cover" alt="<?= htmlspecialchars($vehicle['brand'])?>">
                            <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-slate-300"><svg class="w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
                            <?php endif; ?>
                            <div class="absolute top-6 left-6 bg-white/90 backdrop-blur px-5 py-2 rounded-full shadow-sm text-[11px] font-black text-slate-900 uppercase tracking-widest"><?= htmlspecialchars(strtoupper($vehicle['category']))?></div>
                        </div>
                        <div class="p-6">
                            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight"><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'])?></h1>
                            <p class="text-slate-500 font-medium text-lg mt-1"><?= htmlspecialchars($vehicle['brand'])?> • <?= htmlspecialchars($vehicle['year'])?></p>
                        </div>
                    </div>

                    <!-- Time Selection -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
                        <h2 class="text-lg font-bold text-slate-900 mb-4">Select Pick-up & Return Time</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 block">Pick-up Time</label>
                                <select x-model="formData.pickup_time" class="w-full bg-slate-50 border-0 rounded-xl px-4 py-3 text-sm font-medium focus:ring-2 focus:ring-blue-500">
                                    <template x-for="t in timeSlots" :key="'p'+t"><option :value="t" x-text="t"></option></template>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 block">Return Time</label>
                                <select x-model="formData.return_time" class="w-full bg-slate-50 border-0 rounded-xl px-4 py-3 text-sm font-medium focus:ring-2 focus:ring-blue-500">
                                    <template x-for="t in timeSlots" :key="'r'+t"><option :value="t" x-text="t"></option></template>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Specs -->
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden" x-data="{ activeTab: 'overview' }">
                        <div class="flex border-b border-slate-100 bg-slate-50/50">
                            <button @click="activeTab = 'overview'" :class="activeTab === 'overview' ? 'text-blue-600 border-b-2 border-blue-600 bg-white' : 'text-slate-500'" class="px-8 py-5 text-sm font-bold uppercase tracking-widest">Overview</button>
                            <button @click="activeTab = 'description'" :class="activeTab === 'description' ? 'text-blue-600 border-b-2 border-blue-600 bg-white' : 'text-slate-500'" class="px-8 py-5 text-sm font-bold uppercase tracking-widest">Description</button>
                        </div>
                        <div class="p-8">
                            <div x-show="activeTab === 'overview'" x-transition>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-12">
                                    <div class="flex justify-between items-center border-b border-slate-50 pb-3"><span class="text-slate-400 font-medium">Transmission</span><span class="text-slate-900 font-bold"><?= htmlspecialchars(ucfirst($vehicle['transmission']))?></span></div>
                                    <div class="flex justify-between items-center border-b border-slate-50 pb-3"><span class="text-slate-400 font-medium">Fuel Type</span><span class="text-slate-900 font-bold"><?= htmlspecialchars(ucfirst($vehicle['fuel_type']))?></span></div>
                                    <div class="flex justify-between items-center border-b border-slate-50 pb-3"><span class="text-slate-400 font-medium">Seats</span><span class="text-slate-900 font-bold"><?= htmlspecialchars($vehicle['seats'])?></span></div>
                                    <?php if ($min_age): ?>
                                    <div class="flex justify-between items-center border-b border-slate-50 pb-3"><span class="text-slate-400 font-medium">Min. Driver Age</span><span class="text-slate-900 font-bold"><?= $min_age ?> years</span></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div x-show="activeTab === 'description'" x-cloak x-transition>
                                <p class="text-slate-600 leading-relaxed text-lg"><?= nl2br(htmlspecialchars($vehicle['description'] ?? ''))?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Related Vehicles -->
                    <?php if (!empty($related_vehicles)): ?>
                    <div class="mt-12"><h2 class="text-2xl font-bold text-slate-900 mb-6">Related Vehicles</h2><div class="grid grid-cols-1 sm:grid-cols-2 gap-6"><?php foreach ($related_vehicles as $rv): $rv_images = json_decode($rv['images'], true); $rv_image = is_array($rv_images) && !empty($rv_images) ? $rv_images[0] : $rv['images']; ?><a href="?id=<?= $rv['id']?>" class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden hover:shadow-xl transition-all"><img src="<?= htmlspecialchars($rv_image)?>" class="w-full h-48 object-cover"><div class="p-6"><h3 class="font-bold text-slate-900"><?= htmlspecialchars($rv['brand'] . ' ' . $rv['model'])?></h3></div></a><?php endforeach; ?></div></div>
                    <?php endif; ?>
                </div>

                <!-- STEP 2: ID VERIFICATION -->
                <div x-show="step === 2" x-cloak class="space-y-6">
                    <?php if ($require_verification): ?>
                    <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-4"><svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg></div>
                            <h2 class="text-2xl font-bold text-slate-900">Driver Verification</h2>
                            <p class="text-slate-500 text-sm mt-1">Scan your driver's licence to verify your identity</p>
                        </div>
                        <div id="diditVerification" class="rounded-2xl overflow-hidden border border-slate-200 bg-slate-50">
                            <div x-show="verificationLoading" class="flex flex-col items-center justify-center py-16">
                                <svg class="animate-spin h-10 w-10 text-blue-600 mb-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span class="text-slate-500 font-medium">Starting verification...</span>
                            </div>
                            <div x-show="verificationError" class="p-8 text-center">
                                <div class="w-14 h-14 bg-red-50 rounded-2xl flex items-center justify-center mx-auto mb-4"><svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                                <p class="text-red-600 font-semibold mb-2">Verification Error</p>
                                <p class="text-slate-500 text-sm mb-4" x-text="verificationError"></p>
                                <button @click="initVerification()" class="px-6 py-2.5 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition">Try Again</button>
                            </div>
                            <iframe id="diditIframe" src="" allow="camera; microphone; geolocation" class="w-full h-[600px]" frameborder="0" x-show="!verificationLoading && !verificationError"></iframe>
                        </div>
                        <!-- Status banner -->
                        <div x-show="verificationStatusMsg" x-cloak class="mt-4 p-4 rounded-xl text-sm font-medium flex items-center gap-3" :class="isVerified ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-blue-50 border border-blue-200 text-blue-800'">
                            <svg x-show="isVerified" class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <svg x-show="!isVerified" class="w-5 h-5 text-blue-600 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            <span x-text="verificationStatusMsg"></span>
                        </div>
                        <!-- Manual fallback -->
                        <div x-show="showManualApprove && !isVerified" x-cloak class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-xl">
                            <p class="text-sm text-amber-800 mb-3">Completed verification but status hasn't updated?</p>
                            <button @click="forceApproveVerification()" class="px-5 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 text-sm font-semibold transition">I've Completed Verification</button>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- No verification required — collect details manually -->
                    <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
                        <h2 class="text-xl font-bold text-slate-900 mb-6">Personal Details</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <input type="text" x-model="formData.name" placeholder="Full Name *" class="bg-slate-50 border-0 rounded-2xl px-6 py-4 focus:ring-2 focus:ring-blue-500">
                            <input type="email" x-model="formData.email" placeholder="Email Address *" class="bg-slate-50 border-0 rounded-2xl px-6 py-4 focus:ring-2 focus:ring-blue-500">
                            <input type="tel" x-model="formData.phone" placeholder="Phone Number" class="bg-slate-50 border-0 rounded-2xl px-6 py-4 focus:ring-2 focus:ring-blue-500">
                            <input type="text" x-model="formData.license" placeholder="Licence Number" class="bg-slate-50 border-0 rounded-2xl px-6 py-4 focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Personal details (always shown after verification succeeds or if not required) -->
                    <div x-show="isVerified || !requireVerification" x-cloak class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
                        <h2 class="text-xl font-bold text-slate-900 mb-6">Contact Details</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <input type="text" x-model="formData.name" placeholder="Full Name *" class="bg-slate-50 border-0 rounded-2xl px-6 py-4 focus:ring-2 focus:ring-blue-500">
                            <input type="email" x-model="formData.email" placeholder="Email Address *" class="bg-slate-50 border-0 rounded-2xl px-6 py-4 focus:ring-2 focus:ring-blue-500">
                            <input type="tel" x-model="formData.phone" placeholder="Phone Number" class="bg-slate-50 border-0 rounded-2xl px-6 py-4 focus:ring-2 focus:ring-blue-500">
                            <input type="text" x-model="formData.license" placeholder="Licence Number" class="bg-slate-50 border-0 rounded-2xl px-6 py-4 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <textarea x-model="formData.notes" placeholder="Additional Notes (optional)" class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 mt-4 h-28 focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="flex justify-between items-center" x-show="isVerified || !requireVerification" x-cloak>
                        <button @click="step = 1" class="text-slate-500 font-bold hover:text-slate-900 transition">← Back</button>
                        <button @click="goToReview()" :disabled="!formData.name || !formData.email" class="bg-blue-600 text-white font-bold py-4 px-10 rounded-2xl shadow-lg hover:bg-blue-700 disabled:opacity-40 transition-all">Review Booking →</button>
                    </div>
                </div>

                <!-- STEP 3: REVIEW -->
                <div x-show="step === 3" x-cloak class="space-y-6">
                    <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
                        <h2 class="text-xl font-bold text-slate-900 mb-6 tracking-tight">Booking Review</h2>
                        <!-- Vehicle -->
                        <div class="flex items-center space-x-6 bg-slate-50/50 p-5 rounded-2xl border border-slate-100 mb-6">
                            <?php if ($image_url): ?><img src="<?= htmlspecialchars($image_url)?>" class="w-24 h-16 rounded-xl object-cover shadow-sm"><?php endif; ?>
                            <div><p class="font-bold text-slate-900 text-lg"><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'])?></p><p class="text-slate-500 font-medium"><?= htmlspecialchars($vehicle['category'])?></p></div>
                        </div>
                        <!-- Dates -->
                        <div class="grid grid-cols-2 gap-6 mb-6">
                            <div class="bg-slate-50 rounded-2xl p-4"><p class="text-[10px] uppercase font-bold text-slate-400 tracking-widest mb-1">Pick-up</p><p class="font-bold text-slate-900" x-text="formatDisplayDate(dateRange.start)"></p><p class="text-sm text-slate-500" x-text="formData.pickup_time"></p></div>
                            <div class="bg-slate-50 rounded-2xl p-4"><p class="text-[10px] uppercase font-bold text-slate-400 tracking-widest mb-1">Return</p><p class="font-bold text-slate-900" x-text="formatDisplayDate(dateRange.end)"></p><p class="text-sm text-slate-500" x-text="formData.return_time"></p></div>
                        </div>
                        <!-- Customer -->
                        <div class="border-t border-slate-100 pt-6">
                            <p class="text-xs uppercase font-bold text-slate-400 tracking-widest mb-3">Customer Details</p>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div><span class="text-slate-400">Name</span><p class="font-bold text-slate-900" x-text="formData.name"></p></div>
                                <div><span class="text-slate-400">Email</span><p class="font-bold text-slate-900" x-text="formData.email"></p></div>
                                <div x-show="formData.phone"><span class="text-slate-400">Phone</span><p class="font-bold text-slate-900" x-text="formData.phone"></p></div>
                                <div x-show="formData.license"><span class="text-slate-400">Licence</span><p class="font-bold text-slate-900" x-text="formData.license"></p></div>
                            </div>
                        </div>
                        <!-- Verified data -->
                        <div x-show="Object.keys(verifiedData).length > 0" x-cloak class="border-t border-slate-100 pt-6 mt-6">
                            <p class="text-xs uppercase font-bold text-green-600 tracking-widest mb-3 flex items-center gap-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>Verified Information</p>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <template x-for="(val, key) in verifiedData" :key="key">
                                    <div x-show="val"><span class="text-slate-400" x-text="key"></span><p class="font-bold text-slate-900" x-text="val"></p></div>
                                </template>
                            </div>
                        </div>
                        <!-- Price -->
                        <div class="border-t border-slate-100 pt-6 mt-6 flex justify-between items-center">
                            <span class="text-lg font-bold text-slate-900">Total <span class="font-normal text-slate-400 ml-1" x-text="'(' + days + ' days)'"></span></span>
                            <span class="text-2xl font-extrabold text-slate-900" x-text="'<?= $currency_symbol ?>' + calculateTotal()"></span>
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <button @click="step = 2" class="text-slate-500 font-bold hover:text-slate-900 transition">← Back</button>
                        <button @click="step = 4" class="bg-blue-600 text-white font-bold py-4 px-10 rounded-2xl shadow-lg hover:bg-blue-700 transition-all">Proceed to Payment →</button>
                    </div>
                </div>

                <!-- STEP 4: PAYMENT -->
                <div x-show="step === 4" x-cloak class="space-y-6">
                    <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
                        <h2 class="text-xl font-bold text-slate-900 mb-6">Complete Your Payment</h2>
                        <div class="grid grid-cols-2 gap-3 mb-8">
                            <button @click="formData.payment_method = 'card'" :class="formData.payment_method === 'card' ? 'border-blue-600 bg-blue-50/50 text-blue-600' : 'border-slate-200 text-slate-500'" class="flex flex-col items-center justify-center p-5 rounded-2xl border-2 transition-all">
                                <svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                <span class="font-bold text-sm">Credit Card</span>
                            </button>
                            <button @click="formData.payment_method = 'cash'" :class="formData.payment_method === 'cash' ? 'border-blue-600 bg-blue-50/50 text-blue-600' : 'border-slate-200 text-slate-500'" class="flex flex-col items-center justify-center p-5 rounded-2xl border-2 transition-all">
                                <svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                <span class="font-bold text-sm">Pay on Pickup</span>
                            </button>
                        </div>
                        <div x-show="formData.payment_method === 'card'" class="space-y-4">
                            <div id="stripe-payment-element" class="min-h-[200px] bg-slate-50 rounded-2xl p-4"></div>
                            <div id="card-errors" class="text-xs text-red-500 font-medium"></div>
                        </div>
                    </div>
                    <div class="bg-blue-600 rounded-3xl p-8 text-white shadow-xl shadow-blue-200">
                        <div class="flex justify-between items-center mb-6">
                            <span class="text-lg opacity-80 font-medium">Total due now</span>
                            <span class="text-4xl font-extrabold" x-text="'<?= $currency_symbol ?>' + calculateTotal()"></span>
                        </div>
                        <button @click="submitBooking()" :disabled="isSubmitting" class="w-full bg-white text-blue-600 font-bold py-5 rounded-2xl text-lg hover:bg-blue-50 transition-all flex items-center justify-center disabled:opacity-60">
                            <span x-show="!isSubmitting" x-text="formData.payment_method === 'card' ? 'Pay & Confirm Booking' : 'Confirm Booking'"></span>
                            <svg x-show="isSubmitting" class="animate-spin h-6 w-6" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </button>
                    </div>
                    <button @click="step = 3" class="w-full text-slate-400 font-bold hover:text-slate-600 py-2">← Back to Review</button>
                </div>

                <!-- STEP 5: CONFIRMED -->
                <div x-show="step === 5" x-cloak class="space-y-6">
                    <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100 text-center">
                        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6"><svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg></div>
                        <h2 class="text-3xl font-extrabold text-slate-900 mb-2">Booking Confirmed!</h2>
                        <p class="text-slate-500 mb-6">Booking ID: <span class="font-mono font-bold text-slate-900" x-text="'#' + confirmedBookingId"></span></p>
                        <a href="/templates/fleet.php" class="inline-block px-8 py-3 bg-blue-600 text-white rounded-2xl font-bold hover:bg-blue-700 transition-colors">Back to Fleet</a>
                    </div>
                </div>
            </div>

            <!-- Right Column: Calendar / Pricing Widget -->
            <div class="lg:col-span-3 order-2">
                <div class="sticky-widget" x-show="step === 1" x-transition:enter="duration-300">
                    <div class="bg-white shadow-xl border border-slate-100 overflow-hidden" style="border-radius: 4px;">
                        <div class="bg-[#0b1b3d] text-white p-6 flex justify-between items-baseline">
                            <div><span class="text-3xl font-bold tracking-tight"><?= $currency_symbol ?><?= number_format($vehicle['price_per_day'])?></span><span class="text-blue-200 text-sm ml-1">/day</span></div>
                        </div>
                        <div class="p-8 space-y-6">
                            <div class="mb-6 rounded-2xl overflow-hidden border border-slate-100 bg-slate-50/30"><div id="inlineCalendar"></div></div>
                            <div class="flex justify-between items-center border-t border-slate-50 pt-6">
                                <span class="text-base font-bold text-slate-900">Total <span class="font-normal text-slate-400 ml-1" x-text="'(' + days + ' days)'"></span></span>
                                <span class="text-xl font-extrabold text-slate-900" x-text="'<?= $currency_symbol ?>' + calculateTotal()"></span>
                            </div>
                            <button @click="proceedToStep2()" :disabled="days < 1" class="w-full mt-2 py-4 bg-[#0b1b3d] text-white rounded font-bold text-base hover:bg-[#152a5c] disabled:opacity-50 transition-all">Rent Now</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Error / Age Modal -->
    <template x-teleport="body">
        <div x-show="modal.show" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div @click="modal.show = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            <div class="relative bg-white rounded-3xl p-8 max-w-md w-full shadow-2xl border border-slate-100">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center mb-6" :class="modal.type === 'error' ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600'">
                        <svg x-show="modal.type === 'error'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        <svg x-show="modal.type !== 'error'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-2" x-text="modal.title"></h3>
                    <p class="text-slate-500 text-sm mb-8" x-text="modal.message"></p>
                    <button @click="modal.show = false" class="w-full bg-[#0b1b3d] text-white font-bold py-4 rounded-2xl transition hover:bg-slate-800">Got it</button>
                </div>
            </div>
        </div>
    </template>

    <script>
    function bookingFlow() {
        const requireVerification = <?= $require_verification ? 'true' : 'false' ?>;
        const vehicleMinAge = <?= $min_age ?>;
        const vehicleId = <?= $vehicle_id ?>;

        return {
            step: 1,
            days: 0,
            dateRange: { start: '', end: '' },
            isVerified: !requireVerification,
            requireVerification,
            isSubmitting: false,
            confirmedBookingId: '',
            formData: { name: '', email: '', phone: '', license: '', notes: '', payment_method: 'cash', pickup_time: '10:00', return_time: '10:00' },
            verificationLoading: false,
            verificationError: '',
            verificationStatusMsg: '',
            verificationPollInterval: null,
            showManualApprove: false,
            verifiedData: {},
            modal: { show: false, title: '', message: '', type: 'info' },
            depositAmount: <?= (float)($settings['deposit_amount'] ?? 0) ?>,
            depositPaymentMode: '<?= $settings['deposit_payment_mode'] ?? 'collection' ?>',
            stripePublishableKey: '<?= $settings['stripe_publishable_key'] ?? "" ?>',
            pricingPackages: <?= $vehicle['pricing_packages'] ?: '[]' ?>,
            dailyPricing: <?= $vehicle['daily_pricing'] ?: '{}' ?>,
            basePrice: <?= (float)$vehicle['price_per_day'] ?>,
            stripe: null, elements: null, paymentElement: null, clientSecret: null,
            timeSlots: (() => { const s = []; for (let h = 6; h <= 22; h++) { s.push(h.toString().padStart(2,'0') + ':00'); s.push(h.toString().padStart(2,'0') + ':30'); } return s; })(),

            get stepLabels() {
                if (requireVerification) return ['Dates', 'Verify', 'Review', 'Payment'];
                return ['Dates', 'Details', 'Review', 'Payment'];
            },

            formatDisplayDate(d) { if (!d) return ''; return new Date(d + 'T12:00:00').toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' }); },
            toIsoDate(date) { if (!date) return ''; return `${date.getFullYear()}-${(date.getMonth()+1).toString().padStart(2,'0')}-${date.getDate().toString().padStart(2,'0')}`; },

            init() {
                const booked = <?= json_encode($booked_dates) ?>;
                const manual = "<?= htmlspecialchars($vehicle['unavailable_dates'] ?? '')?>".split(", ").filter(i => i);
                const disableList = booked.map(b => ({ from: b.pickup_date, to: b.return_date })).concat(manual);

                flatpickr.l10ns.default.firstDayOfWeek = 1;
                flatpickr("#inlineCalendar", {
                    mode: "range", inline: true, minDate: "today", disable: disableList, dateFormat: "Y-m-d",
                    locale: { firstDayOfWeek: 1 },
                    onReady: (sd, ds, inst) => this.renderMonthNav(inst),
                    onMonthChange: (sd, ds, inst) => this.renderMonthNav(inst),
                    onYearChange: (sd, ds, inst) => this.renderMonthNav(inst),
                    onDayCreate: (dObj, dStr, fp, dayElem) => {
                        const ds2 = fp.formatDate(dayElem.dateObj, "Y-m-d");
                        if (booked.some(b => ds2 >= b.pickup_date && ds2 <= b.return_date) || manual.includes(ds2)) dayElem.classList.add('booked-date');
                    },
                    onChange: (sd) => {
                        if (sd.length === 2) { this.days = Math.ceil((sd[1] - sd[0])/(864e5)) + 1; this.dateRange.start = this.toIsoDate(sd[0]); this.dateRange.end = this.toIsoDate(sd[1]); }
                        else { this.days = 0; }
                    }
                });

                window.addEventListener('message', (e) => {
                    if (e.data && e.data.type === 'didit_verification_complete') { setTimeout(() => this.checkVerificationStatus(), 1500); }
                    if (e.data && typeof e.data === 'string' && (e.data.includes('verified') || e.data.includes('complete'))) { setTimeout(() => this.checkVerificationStatus(), 2000); }
                });
            },

            renderMonthNav(inst) {
                const months = ["January","February","March","April","May","June","July","August","September","October","November","December"];
                const c = inst.calendarContainer, ic = c.querySelector('.flatpickr-innerContainer');
                if (!ic) return;
                const ex = c.querySelector('.custom-month-year-container');
                if (ex) ex.remove();
                const d = document.createElement('div');
                d.className = 'custom-month-year-container';
                d.innerHTML = `<div class="custom-fp-arrow" onclick="window._fp.changeMonth(-1)"><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></div><span class="custom-month-label">${months[inst.currentMonth]} ${inst.currentYear}</span><div class="custom-fp-arrow" onclick="window._fp.changeMonth(1)"><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></div>`;
                c.insertBefore(d, ic);
                window._fp = inst;
            },

            calculateTotal() {
                if (this.days <= 0) return "0.00";
                let total = 0;
                const dayMap = {1:'mon',2:'tue',3:'wed',4:'thu',5:'fri',6:'sat',7:'sun'};
                let cur = new Date(this.dateRange.start + 'T12:00:00');
                for (let i = 0; i < this.days; i++) {
                    const k = dayMap[cur.getDay() === 0 ? 7 : cur.getDay()];
                    total += this.dailyPricing[k] ? parseFloat(this.dailyPricing[k]) : this.basePrice;
                    cur.setDate(cur.getDate() + 1);
                }
                if (this.pricingPackages?.length) {
                    for (const p of this.pricingPackages) {
                        if (p.type === 'discount_target_day' && this.days >= parseInt(p.target_day)) {
                            const a = parseFloat(p.discount_amount) || 0;
                            total -= p.discount_type === 'percentage' ? total * (a/100) : a;
                        }
                    }
                }
                if (this.depositPaymentMode === 'online') total += parseFloat(this.depositAmount);
                return Math.max(0, total).toFixed(2);
            },

            proceedToStep2() {
                this.step = 2;
                if (requireVerification && !this.isVerified) this.initVerification();
            },

            async initVerification() {
                if (this.isVerified) return;
                this.verificationLoading = true;
                this.verificationError = '';
                try {
                    const res = await fetch('/templates/didit-session.php');
                    const data = await res.json();
                    const url = data.url || data.verification_url;
                    if (url) {
                        let u = url.includes('business.didit.me') ? url.replace('business.didit.me', 'verify.didit.me') : url;
                        document.getElementById('diditIframe').src = u;
                        this.verificationLoading = false;
                        this.startPolling();
                    } else {
                        this.verificationLoading = false;
                        this.verificationError = 'Could not start verification. Please try again.';
                    }
                } catch (e) {
                    this.verificationLoading = false;
                    this.verificationError = 'Network error. Please try again.';
                }
            },

            startPolling() {
                if (this.verificationPollInterval) clearInterval(this.verificationPollInterval);
                this.verificationStatusMsg = 'Waiting for verification...';
                this.verificationPollInterval = setInterval(async () => {
                    try {
                        const r = await (await fetch(`/templates/check-verification-status.php?vehicle_id=${vehicleId}`)).json();
                        if (r.status === 'approved' || r.status === 'in_review') {
                            clearInterval(this.verificationPollInterval);
                            this.handleVerificationSuccess(r);
                        } else if (r.status === 'declined') {
                            clearInterval(this.verificationPollInterval);
                            this.verificationStatusMsg = '';
                            this.modal = { show: true, title: 'Verification Failed', message: 'Your ID could not be verified. Please try again or contact support.', type: 'error' };
                        }
                    } catch (e) { console.error(e); }
                }, 3000);
                setTimeout(() => { if (!this.isVerified) this.showManualApprove = true; }, 20000);
            },

            handleVerificationSuccess(data) {
                this.isVerified = true;
                this.verificationStatusMsg = '✓ Identity verified successfully!';
                this.showManualApprove = false;
                // Store verified data for review display
                if (data.verified_at) this.verifiedData['Verified At'] = data.verified_at;
                // Fetch session data from backend
                this.fetchVerifiedDetails();
                // Age check
                if (data.age_eligible === false) {
                    this.modal = { show: true, title: 'Age Requirement Not Met', message: `The minimum driver age for this vehicle is ${data.age_limit} years. Your verified age is ${data.age}. You cannot proceed with this booking.`, type: 'error' };
                    this.isVerified = false;
                    this.verificationStatusMsg = '✕ Age requirement not met.';
                }
            },

            async fetchVerifiedDetails() {
                // The check-verification-status.php stores data in session. We fetch it via a lightweight endpoint.
                try {
                    const r = await (await fetch('/templates/get-verification-data.php')).json();
                    if (r.success) {
                        if (r.full_name) { this.verifiedData['Full Name'] = r.full_name; if (!this.formData.name) this.formData.name = r.full_name; }
                        if (r.dob) this.verifiedData['Date of Birth'] = r.dob;
                        if (r.address) this.verifiedData['Address'] = r.address;
                        if (r.license_number) { this.verifiedData['Licence Number'] = r.license_number; if (!this.formData.license) this.formData.license = r.license_number; }
                        if (r.date_of_issue) this.verifiedData['Licence Issue Date'] = r.date_of_issue;
                        if (r.expiration_date) this.verifiedData['Licence Expiry'] = r.expiration_date;
                        if (r.session_id) this.verifiedData['Session ID'] = r.session_id;
                        // Check expired license
                        if (r.expiration_date) {
                            const exp = new Date(r.expiration_date);
                            if (exp < new Date()) {
                                this.modal = { show: true, title: 'Licence Expired', message: 'Your driving licence has expired. Please renew your licence before booking.', type: 'error' };
                                this.isVerified = false;
                                this.verificationStatusMsg = '✕ Licence expired.';
                            }
                        }
                    }
                } catch (e) { console.error('Could not fetch verified details', e); }
            },

            async checkVerificationStatus() {
                try {
                    const r = await (await fetch(`/templates/check-verification-status.php?vehicle_id=${vehicleId}`)).json();
                    if (r.status === 'approved' || r.status === 'in_review') {
                        if (this.verificationPollInterval) clearInterval(this.verificationPollInterval);
                        this.handleVerificationSuccess(r);
                    }
                } catch (e) {}
            },

            forceApproveVerification() {
                if (this.verificationPollInterval) clearInterval(this.verificationPollInterval);
                this.isVerified = true;
                this.verificationStatusMsg = '✓ Verification confirmed.';
                this.showManualApprove = false;
                fetch('/templates/force-verification-approved.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'status=approved' });
            },

            goToReview() {
                if (!this.formData.name || !this.formData.email) {
                    this.modal = { show: true, title: 'Missing Information', message: 'Please fill in your full name and email address.', type: 'error' };
                    return;
                }
                // Store email in session for verification status checks
                fetch('/templates/save-booking-data.php', {
                    method: 'POST', headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ vehicle_id: vehicleId, customer_name: this.formData.name, customer_email: this.formData.email, customer_phone: this.formData.phone, customer_license: this.formData.license, pickup_date: this.dateRange.start, return_date: this.dateRange.end, pickup_time: this.formData.pickup_time, return_time: this.formData.return_time, total_days: this.days, price_per_day: this.basePrice, notes: this.formData.notes, skip_verification: true })
                });
                this.step = 3;
            },

            async submitBooking() {
                this.isSubmitting = true;
                try {
                    const fd = new FormData();
                    fd.append('vehicle_id', vehicleId);
                    fd.append('customer_name', this.formData.name);
                    fd.append('customer_email', this.formData.email);
                    fd.append('customer_phone', this.formData.phone);
                    fd.append('customer_license', this.formData.license);
                    fd.append('pickup_date', this.dateRange.start);
                    fd.append('return_date', this.dateRange.end);
                    fd.append('pickup_time', this.formData.pickup_time);
                    fd.append('return_time', this.formData.return_time);
                    fd.append('total_days', this.days);
                    fd.append('price_per_day', this.basePrice);
                    fd.append('notes', this.formData.notes);
                    fd.append('payment_method', this.formData.payment_method);

                    if (this.formData.payment_method === 'card') {
                        fd.append('intent_only', '1');
                    }

                    const res = await fetch('/templates/process-booking.php', { method: 'POST', body: fd });
                    const result = await res.json();

                    if (!result.success) {
                        this.modal = { show: true, title: 'Booking Error', message: result.message || 'Something went wrong.', type: 'error' };
                        this.isSubmitting = false;
                        return;
                    }

                    if (this.formData.payment_method === 'card' && result.client_secret) {
                        // Stripe payment
                        if (!this.stripe) this.stripe = Stripe(this.stripePublishableKey);
                        const { error } = await this.stripe.confirmCardPayment(result.client_secret, {
                            payment_method: { card: this.paymentElement || {}, billing_details: { name: this.formData.name, email: this.formData.email } }
                        });
                        if (error) {
                            this.modal = { show: true, title: 'Payment Failed', message: error.message, type: 'error' };
                            this.isSubmitting = false;
                            return;
                        }
                    }

                    this.confirmedBookingId = result.booking_id;
                    this.step = 5;
                    this.isSubmitting = false;
                } catch (e) {
                    this.modal = { show: true, title: 'Error', message: 'An unexpected error occurred. Please try again.', type: 'error' };
                    this.isSubmitting = false;
                }
            }
        }
    }
    </script>
</body>
</html>