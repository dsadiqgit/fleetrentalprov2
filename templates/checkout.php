<?php
require_once __DIR__ . '/../includes/tenant_init.php';
$tenant_id = getTenantId();
$tenant = getTenant();
$pdo = getDB();

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_verification_help') {
    header('Content-Type: application/json');
    $name = sanitize($_POST['contact_name'] ?? '');
    $phone = sanitize($_POST['contact_phone'] ?? '');
    $message = sanitize($_POST['contact_message'] ?? '');
    $vehicle_id = $_POST['vehicle_id'] ?? null;
    if (empty($name) || empty($phone)) { echo json_encode(['success' => false, 'message' => 'Name and phone are required']); exit; }
    $stmt = $pdo->prepare("SELECT company_email FROM tenant_settings WHERE tenant_id = ?");
    $stmt->execute([$tenant_id]);
    $ts = $stmt->fetch();
    $tenant_email = $ts['company_email'] ?? '';
    if (empty($tenant_email)) { echo json_encode(['success' => false, 'message' => 'Unable to send message']); exit; }
    $vehicle_info = '';
    if ($vehicle_id) {
        $stmt = $pdo->prepare("SELECT brand, model FROM vehicles WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$vehicle_id, $tenant_id]);
        $v = $stmt->fetch();
        if ($v) $vehicle_info = htmlspecialchars($v['brand'] . ' ' . $v['model']);
    }
    try {
        require_once __DIR__ . '/../includes/email.php';
        $subject = "Customer ID Verification Help Request";
        $body = "<h2>Customer ID Verification Help Request</h2><p><strong>Name:</strong> " . htmlspecialchars($name) . "</p><p><strong>Phone:</strong> " . htmlspecialchars($phone) . "</p><p><strong>Message:</strong></p><p>" . nl2br(htmlspecialchars($message)) . "</p>" . ($vehicle_info ? "<p><strong>Vehicle:</strong> $vehicle_info</p>" : "") . "<hr><p><em>Customer needs help with ID verification.</em></p>";
        $headers = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\nFrom: $tenant_email\r\n";
        $sent = mail($tenant_email, $subject, $body, $headers);
        echo json_encode(['success' => $sent, 'message' => $sent ? 'Sent' : 'Failed to send']);
    } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
    exit;
}

$vehicle_id = $_GET['vehicle_id'] ?? $_GET['id'] ?? null;
if (!$vehicle_id) die('Vehicle not found');

$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ? AND tenant_id = ?");
$stmt->execute([$vehicle_id, $tenant_id]);
$vehicle = $stmt->fetch();
if (!$vehicle) die('Vehicle not found');

$vehicle_image = null;
if ($vehicle['images']) {
    $decoded = json_decode($vehicle['images'], true);
    $vehicle_image = is_array($decoded) && !empty($decoded) ? $decoded[0] : $vehicle['images'];
}
if (empty($vehicle_image)) $vehicle_image = '/assets/images/placeholder-img.webp';

$stmt = $pdo->prepare("SELECT require_license_verification, stripe_publishable_key FROM tenant_settings WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$settings = $stmt->fetch();
$require_verification = $settings ? (bool)$settings['require_license_verification'] : false;
$stripe_pk = $settings['stripe_publishable_key'] ?? '';

// Session already started in config.php
$booking_data = $_SESSION['booking_data'] ?? [];

// If a customer is logged in, retrieve their details to prefill the checkout form
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'customer') {
    $stmt_u = $pdo->prepare("SELECT * FROM users WHERE id = ? AND tenant_id = ?");
    $stmt_u->execute([$_SESSION['user_id'], $tenant_id]);
    $loggedInUser = $stmt_u->fetch();
    if ($loggedInUser) {
        if (empty($booking_data['customer_email'])) {
            $booking_data['customer_email'] = $loggedInUser['email'] ?? '';
        }
        if (empty($booking_data['customer_phone'])) {
            $booking_data['customer_phone'] = $loggedInUser['phone'] ?? '';
        }
        if (empty($booking_data['first_name']) && empty($booking_data['last_name'])) {
            $name_parts = explode(' ', $loggedInUser['full_name'] ?? '', 2);
            $booking_data['first_name'] = $name_parts[0] ?? '';
            $booking_data['last_name'] = $name_parts[1] ?? '';
        }
        if (empty($booking_data['customer_license'])) {
            $booking_data['customer_license'] = $loggedInUser['license_number'] ?? '';
        }
        if (empty($booking_data['customer_dob'])) {
            $booking_data['customer_dob'] = $loggedInUser['dob'] ?? '';
        }
        if (empty($booking_data['address_line1'])) {
            $booking_data['address_line1'] = $loggedInUser['address_line1'] ?? '';
        }
        if (empty($booking_data['address_line2'])) {
            $booking_data['address_line2'] = $loggedInUser['address_line2'] ?? '';
        }
        if (empty($booking_data['city'])) {
            $booking_data['city'] = $loggedInUser['city'] ?? '';
        }
        if (empty($booking_data['postcode'])) {
            $booking_data['postcode'] = $loggedInUser['postcode'] ?? '';
        }
        if (empty($booking_data['country'])) {
            $booking_data['country'] = $loggedInUser['country'] ?? '';
        }
    }
}

$get_pickup_date     = $_GET['pickup_date']     ?? ($booking_data['pickup_date'] ?? '');
$get_pickup_time     = $_GET['pickup_time']     ?? ($booking_data['pickup_time'] ?? '10:00');
$get_return_date     = $_GET['return_date']     ?? ($booking_data['return_date'] ?? '');
$get_return_time     = $_GET['return_time']     ?? ($booking_data['return_time'] ?? '10:00');
$get_pickup_location = $_GET['pickup_location'] ?? ($booking_data['pickup_location'] ?? '');
$get_return_location = $_GET['return_location'] ?? ($booking_data['return_location'] ?? '');

$pickup_ts = strtotime($get_pickup_date);
$return_ts = strtotime($get_return_date);
$pickup_date_display  = $pickup_ts ? date('D, j M Y', $pickup_ts) : $get_pickup_date;
$return_date_display  = $return_ts ? date('D, j M Y', $return_ts) : $get_return_date;
$pickup_time_display  = date('h:i a', strtotime($get_pickup_time));
$return_time_display  = date('h:i a', strtotime($get_return_time));

$back_pickup_param = $pickup_ts ? date('j M', $pickup_ts) . ', ' . date('h:ia', strtotime($get_pickup_time)) : '';
$back_return_param = $return_ts ? date('j M', $return_ts) . ', ' . date('h:ia', strtotime($get_return_time)) : '';
$back_url = "/templates/vehicle-booking.php?id=" . urlencode($vehicle_id)
          . "&pickup=" . urlencode($back_pickup_param)
          . "&return=" . urlencode($back_return_param)
          . "&pickup_location=" . urlencode($get_pickup_location)
          . "&return_location=" . urlencode($get_return_location);

// Calculate rental days for sidebar
$rental_days = ($pickup_ts && $return_ts) ? max(1, (int)round(($return_ts - $pickup_ts) / 86400)) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — <?= htmlspecialchars($tenant['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://js.stripe.com/v3/"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
    <style>
        .step-panel { transition: opacity .25s ease, transform .25s ease; }
        .step-panel.hidden { opacity: 0; pointer-events: none; position: absolute; }
        input:focus, select:focus, textarea:focus { outline: none; box-shadow: 0 0 0 3px rgba(59,130,246,.25); border-color: #3b82f6; }
        .field-label { font-size: .8125rem; font-weight: 600; color: #374151; margin-bottom: .375rem; display: block; }
        .field-input { width: 100%; padding: .625rem .875rem; border: 1.5px solid #d1d5db; border-radius: .5rem; font-size: .9375rem; background: #fff; transition: border-color .15s; }
        .contract-body { max-height: 340px; overflow-y: auto; font-size: .875rem; line-height: 1.65; color: #374151; border: 1.5px solid #d1d5db; border-radius: .75rem; padding: 1.25rem 1.5rem; background: #fafafa; }
        .contract-body::-webkit-scrollbar { width: 6px; }
        .contract-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        #signatureCanvas { display: block; width: 100%; height: 160px; cursor: crosshair; touch-action: none; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen">

<!-- Header -->
<header class="bg-white border-b border-gray-200 sticky top-0 z-30">
    <div class="max-w-6xl mx-auto px-4 py-3.5 flex items-center justify-between">
        <?php if (!empty($tenant['logo_url']) || !empty($tenant['logo'])): ?>
            <img src="<?= htmlspecialchars($tenant['logo_url'] ?: $tenant['logo']) ?>" alt="<?= htmlspecialchars($tenant['name']) ?>" class="h-9 w-auto">
        <?php else: ?>
            <span class="text-base font-bold text-gray-900"><?= htmlspecialchars($tenant['name']) ?></span>
        <?php endif; ?>
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <span>Secure Checkout</span>
        </div>
    </div>
</header>

<!-- Progress Bar -->
<div class="bg-white border-b border-gray-100">
    <div class="max-w-6xl mx-auto px-4 py-5">
        <div id="progressBar" class="flex items-center gap-0">
            <!-- Steps injected by JS -->
        </div>
    </div>
</div>

<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="grid lg:grid-cols-3 gap-8 items-start">

        <!-- Main Steps Area -->
        <div class="lg:col-span-2 relative">

            <!-- ══════════════ STEP 1: PERSONAL DETAILS ══════════════ -->
            <div id="step1" class="step-panel bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-9 h-9 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold text-sm shrink-0">1</div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Your Details</h2>
                        <p class="text-sm text-gray-500">Personal and address information</p>
                    </div>
                </div>

                <form id="detailsForm" novalidate>
                    <input type="hidden" name="vehicle_id" value="<?= htmlspecialchars($vehicle_id) ?>">
                    <input type="hidden" name="pickup_date" value="<?= htmlspecialchars($get_pickup_date) ?>">
                    <input type="hidden" name="pickup_time" value="<?= htmlspecialchars($get_pickup_time) ?>">
                    <input type="hidden" name="return_date" value="<?= htmlspecialchars($get_return_date) ?>">
                    <input type="hidden" name="return_time" value="<?= htmlspecialchars($get_return_time) ?>">
                    <input type="hidden" name="pickup_location" value="<?= htmlspecialchars($get_pickup_location) ?>">
                    <input type="hidden" name="return_location" value="<?= htmlspecialchars($get_return_location) ?>">

                    <!-- Personal Info -->
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Personal Information</p>
                    <div class="grid sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="field-label">First Name <span class="text-red-500">*</span></label>
                            <input type="text" name="first_name" class="field-input" placeholder="John" required autocomplete="given-name" value="<?= htmlspecialchars($booking_data['first_name'] ?? '') ?>">
                        </div>
                        <div>
                            <label class="field-label">Last Name <span class="text-red-500">*</span></label>
                            <input type="text" name="last_name" class="field-input" placeholder="Smith" required autocomplete="family-name" value="<?= htmlspecialchars($booking_data['last_name'] ?? '') ?>">
                        </div>
                        <div>
                            <label class="field-label">Email Address <span class="text-red-500">*</span></label>
                            <input type="email" name="customer_email" class="field-input" placeholder="john@example.com" required autocomplete="email" value="<?= htmlspecialchars($booking_data['customer_email'] ?? '') ?>">
                        </div>
                        <div>
                            <label class="field-label">Mobile Number <span class="text-red-500">*</span></label>
                            <input type="tel" name="customer_phone" class="field-input" placeholder="07700 900000" required maxlength="12" oninput="this.value=this.value.replace(/[^0-9]/g,'')" autocomplete="tel" value="<?= htmlspecialchars($booking_data['customer_phone'] ?? '') ?>">
                        </div>
                        <div>
                            <label class="field-label">Date of Birth <span class="text-red-500">*</span></label>
                            <input type="date" name="customer_dob" class="field-input" required autocomplete="bday" value="<?= htmlspecialchars($booking_data['customer_dob'] ?? '') ?>">
                        </div>
                        <div>
                            <label class="field-label">Driver's Licence Number <span class="text-red-500">*</span></label>
                            <input type="text" name="customer_license" class="field-input" placeholder="SMITH701234AB9CD" required autocomplete="off" value="<?= htmlspecialchars($booking_data['customer_license'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Address -->
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3 mt-6">Address</p>
                    <div class="grid sm:grid-cols-2 gap-4 mb-6">
                        <div class="sm:col-span-2">
                            <label class="field-label">Address Line 1 <span class="text-red-500">*</span></label>
                            <input type="text" name="address_line1" class="field-input" placeholder="123 High Street" required autocomplete="address-line1" value="<?= htmlspecialchars($booking_data['address_line1'] ?? '') ?>">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="field-label">Address Line 2</label>
                            <input type="text" name="address_line2" class="field-input" placeholder="Apartment, suite, etc. (optional)" autocomplete="address-line2" value="<?= htmlspecialchars($booking_data['address_line2'] ?? '') ?>">
                        </div>
                        <div>
                            <label class="field-label">City <span class="text-red-500">*</span></label>
                            <input type="text" name="city" class="field-input" placeholder="London" required autocomplete="address-level2" value="<?= htmlspecialchars($booking_data['city'] ?? '') ?>">
                        </div>
                        <div>
                            <label class="field-label">Postcode <span class="text-red-500">*</span></label>
                            <input type="text" name="postcode" class="field-input" placeholder="SW1A 1AA" required autocomplete="postal-code" value="<?= htmlspecialchars($booking_data['postcode'] ?? '') ?>">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="field-label">Country <span class="text-red-500">*</span></label>
                            <select name="country" class="field-input" required autocomplete="country">
<?php
                                $saved_country = $booking_data['country'] ?? 'GB';
                                $country_options = [''=>'Select country…','GB'=>'United Kingdom','IE'=>'Ireland','US'=>'United States','AU'=>'Australia','CA'=>'Canada','FR'=>'France','DE'=>'Germany','ES'=>'Spain','IT'=>'Italy','NL'=>'Netherlands','Other'=>'Other'];
                                foreach ($country_options as $code => $label): ?>
                                <option value="<?= htmlspecialchars($code) ?>"<?= $saved_country === $code ? ' selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Rental Period Card -->
                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div class="grid sm:grid-cols-2 gap-4 w-full">
                            <div class="border-l-4 border-blue-500 pl-3">
                                <span class="block text-xs font-bold text-gray-400 uppercase">Pick-up</span>
                                <span class="block text-sm font-bold text-gray-800"><?= htmlspecialchars($pickup_date_display) ?></span>
                                <span class="block text-xs text-gray-500"><?= htmlspecialchars($pickup_time_display) ?></span>
                            </div>
                            <div class="border-l-4 border-blue-500 pl-3">
                                <span class="block text-xs font-bold text-gray-400 uppercase">Return</span>
                                <span class="block text-sm font-bold text-gray-800"><?= htmlspecialchars($return_date_display) ?></span>
                                <span class="block text-xs text-gray-500"><?= htmlspecialchars($return_time_display) ?></span>
                            </div>
                        </div>
                        <a href="<?= htmlspecialchars($back_url) ?>" class="shrink-0 text-xs font-bold text-blue-600 hover:text-blue-700 bg-white border border-blue-200 rounded-lg px-3 py-2 flex items-center gap-1.5 transition hover:bg-blue-50">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Edit Dates
                        </a>
                    </div>

                    <button id="step1Btn" type="submit" class="w-full py-3.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition text-base">
                        <?= $require_verification ? 'Continue to Verification →' : 'Continue to Agreement →' ?>
                    </button>
                </form>
            </div>

            <!-- ══════════════ STEP 2: VERIFICATION ══════════════ -->
            <div id="step2" class="step-panel hidden bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-9 h-9 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold text-sm shrink-0" id="step2Icon">2</div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Identity Verification</h2>
                        <p class="text-sm text-gray-500">Complete a quick identity check to proceed</p>
                    </div>
                </div>
                <div class="border-2 border-gray-100 rounded-xl overflow-hidden mb-5">
                    <iframe id="diditIframe" src="" allow="camera; microphone; geolocation" class="w-full h-[580px] border-0" frameborder="0"></iframe>
                </div>
                <div id="verificationStatus" class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg hidden">
                    <p class="text-sm text-blue-800">Checking verification status…</p>
                </div>
                <div id="manualApproveSection" class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg hidden">
                    <p class="text-sm text-amber-800 mb-2">Completed verification but button is not active?</p>
                    <button onclick="openContactModal()" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 text-sm font-medium">Contact Rental Company</button>
                </div>
                <div class="flex gap-3">
                    <button onclick="goToStep(1)" class="flex-1 py-3 border border-gray-200 rounded-xl font-medium hover:bg-gray-50 transition">← Back</button>
                    <button id="verificationCompleteBtn" onclick="manualContinue()" disabled class="flex-1 py-3 bg-blue-600 text-white rounded-xl font-semibold disabled:bg-gray-300 disabled:cursor-not-allowed transition">Continue to Agreement →</button>
                </div>
            </div>

            <!-- ══════════════ STEP 3: RENTAL AGREEMENT ══════════════ -->
            <div id="step3" class="step-panel hidden bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-9 h-9 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold text-sm shrink-0">3</div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Rental Agreement</h2>
                        <p class="text-sm text-gray-500">Please read and sign the rental agreement below</p>
                    </div>
                </div>

                <div id="contractLoading" class="flex flex-col items-center justify-center py-12 text-gray-400">
                    <svg class="animate-spin w-8 h-8 mb-3 text-blue-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <p class="text-sm">Loading agreement…</p>
                </div>

                <div id="contractContent" class="hidden">
                    <div class="flex items-center justify-between mb-3">
                        <h3 id="contractTitle" class="font-bold text-gray-800 text-sm"></h3>
                        <span class="text-xs text-gray-400">Scroll to read all terms</span>
                    </div>
                    <div id="contractBody" class="contract-body mb-5"></div>

                    <div class="space-y-4">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" id="agreeCheck" class="mt-1 w-4 h-4 accent-blue-600 shrink-0" onchange="toggleSignSection()">
                            <span class="text-sm text-gray-700">I confirm I have read and agree to all terms in this rental agreement</span>
                        </label>

                        <div id="signSection" class="hidden space-y-3 pt-3 border-t border-gray-100">
                            <label class="field-label">Draw Your Signature <span class="text-red-500">*</span></label>
                            <div class="relative border-2 border-gray-200 rounded-xl bg-white overflow-hidden">
                                <canvas id="signatureCanvas"></canvas>
                                <div id="sigPlaceholder" class="absolute inset-0 flex items-center justify-center pointer-events-none select-none">
                                    <span class="text-gray-300 text-sm">Sign here with mouse or finger</span>
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <button type="button" onclick="clearSignature()" class="text-xs text-red-500 hover:text-red-700 font-semibold">Clear Signature</button>
                            </div>
                            <p class="text-xs text-gray-400">By drawing your signature and clicking Agree &amp; Sign, you are electronically signing this rental agreement.</p>
                        </div>
                    </div>

                    <div class="flex gap-3 mt-6">
                        <button onclick="goToStep(<?= $require_verification ? 2 : 1 ?>)" class="flex-1 py-3 border border-gray-200 rounded-xl font-medium hover:bg-gray-50 transition">← Back</button>
                        <button id="signBtn" onclick="signContract()" disabled class="flex-1 py-3.5 bg-blue-600 text-white font-semibold rounded-xl disabled:bg-gray-300 disabled:cursor-not-allowed transition">
                            Agree &amp; Sign →
                        </button>
                    </div>
                </div>
            </div>

            <!-- ══════════════ STEP 4: PAYMENT ══════════════ -->
            <div id="step4" class="step-panel hidden bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-9 h-9 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold text-sm shrink-0">4</div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Payment</h2>
                        <p class="text-sm text-gray-500">Choose how you'd like to pay</p>
                    </div>
                </div>

                <form id="paymentForm">
                    <div class="space-y-3 mb-6">
                        <label class="flex items-center gap-3 p-4 border-2 rounded-xl cursor-pointer hover:border-blue-300 transition has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                            <input type="radio" name="payment_method" value="card" checked class="w-4 h-4 accent-blue-600">
                            <div>
                                <span class="font-semibold text-gray-900">Credit / Debit Card</span>
                                <p class="text-xs text-gray-500 mt-0.5">Secure payment via Stripe</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-4 border-2 rounded-xl cursor-pointer hover:border-blue-300 transition has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                            <input type="radio" name="payment_method" value="cash" class="w-4 h-4 accent-blue-600">
                            <div>
                                <span class="font-semibold text-gray-900">Pay at Pick-up</span>
                                <p class="text-xs text-gray-500 mt-0.5">Cash on collection of the vehicle</p>
                            </div>
                        </label>
                    </div>

                    <div id="cardNotice" class="mb-5 p-4 bg-blue-50 border border-blue-100 rounded-xl flex items-center gap-3">
                        <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <p class="text-xs text-blue-800 font-medium">You'll be securely redirected to Stripe to complete your payment.</p>
                    </div>

                    <div class="flex gap-3">
                        <button type="button" onclick="goToStep(3)" class="flex-1 py-3 border border-gray-200 rounded-xl font-medium hover:bg-gray-50 transition">← Back</button>
                        <button type="submit" id="payBtn" class="flex-1 py-3.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition">Complete Booking →</button>
                    </div>
                </form>
            </div>

            <!-- ══════════════ STEP 5: CONFIRMATION ══════════════ -->
            <div id="step5" class="step-panel hidden bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-5">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Booking Confirmed!</h2>
                <p class="text-gray-500 mb-6">Booking ID: <span id="bookingId" class="font-mono font-bold text-gray-800">#</span></p>
                <div id="accountCreatedNotice" class="hidden mb-6 max-w-sm mx-auto text-left bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-start gap-3">
                    <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <div>
                        <p class="text-sm font-bold text-blue-900">Account Created</p>
                        <p class="text-sm text-blue-700">Check your email for login credentials to manage your bookings.</p>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="/templates/fleet.php" class="px-8 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-semibold transition">Browse More Vehicles</a>
                    <a id="loginLink" href="/auth/login.php" class="hidden px-8 py-3 border border-blue-500 text-blue-600 rounded-xl hover:bg-blue-50 font-semibold transition">Login to Account</a>
                </div>
            </div>

        </div><!-- /main -->

        <!-- ══════════════ SIDEBAR ══════════════ -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sticky top-24">
                <img src="<?= htmlspecialchars($vehicle_image) ?>" alt="<?= htmlspecialchars($vehicle['name']) ?>" class="w-full h-40 object-cover rounded-xl mb-4">
                <h3 class="font-bold text-gray-900 text-base"><?= htmlspecialchars($vehicle['name']) ?></h3>
                <p class="text-sm text-gray-500 mb-4"><?= htmlspecialchars($vehicle['year'] . ' · ' . ucfirst($vehicle['category'] ?? '')) ?></p>

                <div class="border-t border-gray-100 pt-4 space-y-2 text-sm">
                    <?php if ($get_pickup_date && $get_return_date): ?>
                    <div class="flex justify-between text-gray-600">
                        <span>Pick-up</span>
                        <span class="font-medium text-gray-800"><?= htmlspecialchars($pickup_date_display) ?></span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Return</span>
                        <span class="font-medium text-gray-800"><?= htmlspecialchars($return_date_display) ?></span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Duration</span>
                        <span class="font-medium text-gray-800"><?= $rental_days ?> day<?= $rental_days != 1 ? 's' : '' ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between text-gray-600">
                        <span>Daily Rate</span>
                        <span class="font-medium text-gray-800">£<?= number_format($vehicle['price_per_day'], 2) ?></span>
                    </div>
                    <div class="flex justify-between font-bold text-base border-t border-gray-100 pt-3 mt-2">
                        <span class="text-gray-900">Est. Total</span>
                        <span class="text-blue-600">£<?= number_format($vehicle['price_per_day'] * $rental_days, 2) ?></span>
                    </div>
                    <p class="text-xs text-gray-400 pt-1">* Final price may vary based on any active pricing packages.</p>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Contact Modal -->
<div id="contactModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden p-4">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-xl">
        <h3 class="text-lg font-bold mb-1">Contact Rental Company</h3>
        <p class="text-sm text-gray-500 mb-4">Having trouble with ID verification? We'll help you complete your booking.</p>
        <form id="contactForm" onsubmit="submitContactForm(event)">
            <div class="mb-3">
                <label class="field-label">Your Name *</label>
                <input type="text" name="contact_name" required class="field-input">
            </div>
            <div class="mb-3">
                <label class="field-label">Phone Number *</label>
                <input type="tel" name="contact_phone" required class="field-input" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
            </div>
            <div class="mb-4">
                <label class="field-label">Message</label>
                <textarea name="contact_message" rows="3" class="field-input" placeholder="Describe your issue…"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeContactModal()" class="flex-1 py-2.5 border rounded-xl font-medium hover:bg-gray-50">Cancel</button>
                <button type="submit" class="flex-1 py-2.5 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700">Send Message</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
// ─────────────────────────────────────────────────────────────
// Config
// ─────────────────────────────────────────────────────────────
const requireVerification = <?= $require_verification ? 'true' : 'false' ?>;
const stripePk = '<?= htmlspecialchars($stripe_pk) ?>';
const vehicleId = '<?= htmlspecialchars($vehicle_id) ?>';
const pricePerDay = <?= floatval($vehicle['price_per_day']) ?>;

// Hide card option if no Stripe key
if (!stripePk) {
    document.querySelector('input[value="card"]')?.closest('label')?.classList.add('hidden');
    const cashRadio = document.querySelector('input[value="cash"]');
    if (cashRadio) cashRadio.checked = true;
    document.getElementById('cardNotice')?.classList.add('hidden');
}

// Toggle card notice visibility
document.querySelectorAll('input[name="payment_method"]').forEach(r => {
    r.addEventListener('change', e => {
        const notice = document.getElementById('cardNotice');
        if (notice) notice.classList.toggle('hidden', e.target.value !== 'card');
    });
});

// ─────────────────────────────────────────────────────────────
// Progress bar
// ─────────────────────────────────────────────────────────────
const STEPS = requireVerification
    ? [{id:1,label:'Details'},{id:2,label:'Verification'},{id:3,label:'Agreement'},{id:4,label:'Payment'}]
    : [{id:1,label:'Details'},{id:3,label:'Agreement'},{id:4,label:'Payment'}];

let currentStep = 1;

function renderProgress(active) {
    const bar = document.getElementById('progressBar');
    if (!bar) return;
    bar.innerHTML = STEPS.map((s, idx) => {
        const done = active > s.id;
        const curr = active === s.id;
        const circle = done
            ? `<div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-bold shrink-0"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div>`
            : `<div class="w-8 h-8 rounded-full ${curr ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-400'} flex items-center justify-center text-xs font-bold shrink-0">${idx+1}</div>`;
        const line = idx < STEPS.length - 1
            ? `<div class="flex-1 h-0.5 mx-3 ${done ? 'bg-blue-600' : 'bg-gray-200'} transition-colors duration-300"></div>`
            : '';
        return `<div class="flex items-center ${idx < STEPS.length - 1 ? 'flex-1' : ''}">
            <div class="flex items-center gap-2">
                ${circle}
                <span class="text-xs font-semibold ${curr ? 'text-blue-600' : done ? 'text-blue-600' : 'text-gray-400'} hidden sm:block">${s.label}</span>
            </div>
            ${line}
        </div>`;
    }).join('');
}

function showPanel(stepId) {
    document.querySelectorAll('.step-panel').forEach(el => el.classList.add('hidden'));
    const panel = document.getElementById('step' + stepId);
    if (panel) panel.classList.remove('hidden');
}

function goToStep(stepId) {
    currentStep = stepId;
    showPanel(stepId);
    renderProgress(stepId);
    window.scrollTo({top: 0, behavior: 'smooth'});
    // Update backend step
    fetch('/templates/update-checkout-step.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({step: stepId})
    });
    // Load contract when entering step 3
    if (stepId === 3) loadContract();
    // Start verification polling when entering step 2
    if (stepId === 2) startVerificationCheck();
    else if (verificationCheckInterval) clearInterval(verificationCheckInterval);
}

// Init
renderProgress(1);

// ─────────────────────────────────────────────────────────────
// Step 1: Details form
// ─────────────────────────────────────────────────────────────
document.getElementById('detailsForm')?.addEventListener('submit', async e => {
    e.preventDefault();
    const btn = document.getElementById('step1Btn');
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);

    // Validation
    const errors = [];
    if (!data.first_name?.trim()) errors.push('First name is required.');
    if (!data.last_name?.trim()) errors.push('Last name is required.');
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.customer_email?.trim())) errors.push('Please enter a valid email address.');
    if (!/^[0-9]{1,12}$/.test(data.customer_phone?.trim())) errors.push('Please enter a valid mobile number (digits only, up to 12).');
    if (!data.customer_dob) errors.push('Date of birth is required.');
    if (!data.customer_license?.trim() || data.customer_license.trim().length < 3) errors.push('Please enter a valid licence number.');
    if (!data.address_line1?.trim()) errors.push('Address line 1 is required.');
    if (!data.city?.trim()) errors.push('City is required.');
    if (!data.postcode?.trim()) errors.push('Postcode is required.');
    if (!data.country) errors.push('Please select a country.');

    if (errors.length) { showErrorModal(errors.join('<br>')); return; }

    // Combine first + last name for backward-compat
    data.customer_name = (data.first_name.trim() + ' ' + data.last_name.trim()).trim();

    if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }

    if (!requireVerification) data.skip_verification = true;

    try {
        const res = await fetch('/templates/save-booking-data.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        const result = await res.json();

        if (result.success && result.skipped) {
            goToStep(3); // skip verification → go straight to agreement
        } else if (result.success && result.verification_url) {
            let url = result.verification_url;
            if (url.includes('business.didit.me')) url = url.replace('business.didit.me', 'verify.didit.me');
            document.getElementById('diditIframe').src = url;
            goToStep(2);
        } else {
            showErrorModal('Error: ' + (result.message || 'Failed to proceed'));
        }
    } catch (err) {
        showErrorModal('Network error: ' + err.message);
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.textContent = requireVerification ? 'Continue to Verification →' : 'Continue to Agreement →';
        }
    }
});

// ─────────────────────────────────────────────────────────────
// Step 2: Verification
// ─────────────────────────────────────────────────────────────
let verificationCheckInterval;
let verificationDetected = false;

function handleVerificationApproved(isAgeIneligible = false, ageLimit = 18, actualAge = 0) {
    if (verificationCheckInterval) clearInterval(verificationCheckInterval);
    const btn = document.getElementById('verificationCompleteBtn');
    const status = document.getElementById('verificationStatus');

    if (isAgeIneligible) {
        if (status) {
            status.classList.remove('hidden','bg-blue-50','border-blue-200');
            status.classList.add('bg-red-50','border-red-200');
            status.querySelector('p').className = 'text-sm text-red-800 font-medium';
            status.querySelector('p').innerHTML = `✕ <strong>Booking Disqualified:</strong> Minimum age is ${ageLimit}. Your age is ${actualAge}.`;
        }
        if (btn) { btn.disabled = true; btn.textContent = '✕ Age Requirement Not Met'; btn.classList.replace('bg-blue-600','bg-red-600'); }
        return;
    }

    verificationDetected = true;
    if (btn) {
        btn.disabled = false;
        btn.textContent = '✓ Verified — Continue to Agreement →';
        btn.classList.remove('disabled:bg-gray-300','disabled:cursor-not-allowed');
        btn.classList.add('bg-green-600','hover:bg-green-700');
    }
    if (status) {
        status.classList.remove('hidden','bg-blue-50','border-blue-200');
        status.classList.add('bg-green-50','border-green-200');
        status.querySelector('p').className = 'text-sm text-green-800 font-medium';
        status.querySelector('p').textContent = '✓ Verification complete! You can now continue.';
    }
    fetch('/templates/force-verification-approved.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'status=approved'
    });
}

function manualContinue() {
    if (verificationDetected) goToStep(3);
}

function startVerificationCheck() {
    const status = document.getElementById('verificationStatus');
    if (status) status.classList.remove('hidden');
    let pollCount = 0;
    verificationCheckInterval = setInterval(async () => {
        pollCount++;
        try {
            const res = await fetch('/templates/check-verification-status.php');
            const result = await res.json();
            if (result.status === 'approved' || result.status === 'in_review') {
                result.age_eligible === false
                    ? handleVerificationApproved(true, result.age_limit, result.age)
                    : handleVerificationApproved();
            } else if (result.status === 'declined') {
                clearInterval(verificationCheckInterval);
                showErrorModal('Verification was declined. Please try again or contact support.');
                goToStep(1);
            }
        } catch(e) { console.error('Poll error:', e); }
    }, 2000);
    setTimeout(() => {
        if (!verificationDetected) document.getElementById('manualApproveSection')?.classList.remove('hidden');
    }, 15000);
    window.addEventListener('message', function(event) {
        if (event.data && typeof event.data === 'string' &&
            (event.data.includes('verified') || event.data.includes('complete') || event.data.includes('approved'))) {
            setTimeout(async () => {
                try {
                    const res = await fetch('/templates/check-verification-status.php');
                    const result = await res.json();
                    if (result.status === 'approved' || result.status === 'in_review') {
                        result.age_eligible === false
                            ? handleVerificationApproved(true, result.age_limit, result.age)
                            : handleVerificationApproved();
                    }
                } catch(e) {}
            }, 2000);
        }
    });
}

// ─────────────────────────────────────────────────────────────
// Step 3: Contract / Agreement
// ─────────────────────────────────────────────────────────────
let contractLoaded = false;

async function loadContract() {
    if (contractLoaded) return;
    const loading = document.getElementById('contractLoading');
    const content = document.getElementById('contractContent');
    try {
        const res = await fetch('/templates/get-contract-template.php');
        const data = await res.json();
        if (data.success) {
            document.getElementById('contractTitle').textContent = data.name || 'Rental Agreement';
            let html = data.content || 'Standard rental agreement applies.';
            if (data.is_html) {
                // Visual template — set directly as HTML
                document.getElementById('contractBody').innerHTML = html;
            } else {
                // Plain text — convert newlines and basic markdown
                html = html.replace(/\n/g, '<br>').replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                document.getElementById('contractBody').innerHTML = html;
            }
            contractLoaded = true;
            if (loading) loading.classList.add('hidden');
            if (content) content.classList.remove('hidden');
        } else {
            if (loading) loading.innerHTML = '<p class="text-sm text-red-600">Failed to load agreement. Please refresh the page.</p>';
        }
    } catch(e) {
        if (loading) loading.innerHTML = '<p class="text-sm text-red-600">Failed to load agreement: ' + e.message + '</p>';
    }
}

// ─── Canvas Signature Pad ────────────────────────────────────────────────────
let sigCanvas, sigCtx, sigDrawing = false, sigHasSigned = false;

function initSignatureCanvas() {
    sigCanvas = document.getElementById('signatureCanvas');
    if (!sigCanvas || sigCtx) return; // already initialised
    sigCtx = sigCanvas.getContext('2d');
    const dpr = window.devicePixelRatio || 1;
    const w = sigCanvas.parentElement.clientWidth;
    const h = 160;
    sigCanvas.width = w * dpr;
    sigCanvas.height = h * dpr;
    sigCanvas.style.width = w + 'px';
    sigCanvas.style.height = h + 'px';
    sigCtx.scale(dpr, dpr);
    sigCtx.strokeStyle = '#000000';
    sigCtx.lineWidth = 2.5;
    sigCtx.lineCap = 'round';
    sigCtx.lineJoin = 'round';

    function pos(e) {
        const r = sigCanvas.getBoundingClientRect();
        return { x: e.clientX - r.left, y: e.clientY - r.top };
    }
    sigCanvas.addEventListener('mousedown', e => { sigDrawing = true; const p = pos(e); sigCtx.beginPath(); sigCtx.moveTo(p.x, p.y); });
    sigCanvas.addEventListener('mousemove', e => { if (!sigDrawing) return; const p = pos(e); sigCtx.lineTo(p.x, p.y); sigCtx.stroke(); onSigned(); });
    sigCanvas.addEventListener('mouseup', () => sigDrawing = false);
    sigCanvas.addEventListener('mouseleave', () => sigDrawing = false);
    sigCanvas.addEventListener('touchstart', e => { e.preventDefault(); sigDrawing = true; const p = pos(e.touches[0]); sigCtx.beginPath(); sigCtx.moveTo(p.x, p.y); }, {passive:false});
    sigCanvas.addEventListener('touchmove', e => { e.preventDefault(); if (!sigDrawing) return; const p = pos(e.touches[0]); sigCtx.lineTo(p.x, p.y); sigCtx.stroke(); onSigned(); }, {passive:false});
    sigCanvas.addEventListener('touchend', () => sigDrawing = false);
}

function onSigned() {
    sigHasSigned = true;
    document.getElementById('sigPlaceholder')?.classList.add('hidden');
    document.getElementById('signBtn').disabled = false;
}

function clearSignature() {
    if (!sigCanvas) return;
    const dpr = window.devicePixelRatio || 1;
    sigCtx.clearRect(0, 0, sigCanvas.width / dpr, sigCanvas.height / dpr);
    sigHasSigned = false;
    document.getElementById('sigPlaceholder')?.classList.remove('hidden');
    document.getElementById('signBtn').disabled = true;
}

function toggleSignSection() {
    const checked = document.getElementById('agreeCheck')?.checked;
    document.getElementById('signSection')?.classList.toggle('hidden', !checked);
    if (checked) { setTimeout(initSignatureCanvas, 50); }
    if (!checked) document.getElementById('signBtn').disabled = true;
}

async function signContract() {
    if (!sigHasSigned) { showErrorModal('Please draw your signature before continuing.'); return; }
    const btn = document.getElementById('signBtn');
    if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }
    const signatureData = sigCanvas.toDataURL('image/png');
    try {
        const res = await fetch('/templates/save-contract-signature.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ signature: signatureData })
        });
        const data = await res.json();
        if (data.success) {
            goToStep(4);
        } else {
            showErrorModal(data.message || 'Failed to save signature. Please try again.');
            if (btn) { btn.disabled = false; btn.textContent = 'Agree & Sign →'; }
        }
    } catch(e) {
        showErrorModal('Network error: ' + e.message);
        if (btn) { btn.disabled = false; btn.textContent = 'Agree & Sign →'; }
    }
}

// ─────────────────────────────────────────────────────────────
// Step 4: Payment
// ─────────────────────────────────────────────────────────────
document.getElementById('paymentForm')?.addEventListener('submit', async e => {
    e.preventDefault();
    const payBtn = document.getElementById('payBtn');
    if (payBtn) { payBtn.disabled = true; payBtn.textContent = 'Processing…'; }

    const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value || 'cash';
    const formData = new FormData();
    formData.append('payment_method', paymentMethod);

    try {
        const res = await fetch('/templates/process-booking.php', { method: 'POST', body: formData });
        const result = await res.json();
        if (!result.success) throw new Error(result.message || 'Failed to process booking');

        if (paymentMethod === 'card') {
            if (result.redirect_url) { window.location.href = result.redirect_url; return; }
            throw new Error('Failed to initialize card payment. Please try again.');
        }

        document.getElementById('bookingId').textContent = '#' + result.booking_id;
        if (result.account_created) {
            document.getElementById('accountCreatedNotice')?.classList.remove('hidden');
            document.getElementById('loginLink')?.classList.remove('hidden');
        }
        goToStep(5);
    } catch(err) {
        showErrorModal('Error: ' + err.message);
        if (payBtn) { payBtn.disabled = false; payBtn.textContent = 'Complete Booking →'; }
    }
});

// ─────────────────────────────────────────────────────────────
// Modals
// ─────────────────────────────────────────────────────────────
function showErrorModal(message) {
    document.getElementById('customErrorModal')?.remove();
    const m = document.createElement('div');
    m.id = 'customErrorModal';
    m.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4';
    m.innerHTML = `<div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-2xl">
        <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900 text-center mb-2">Something went wrong</h3>
        <p class="text-gray-600 text-center text-sm mb-5">${message}</p>
        <button onclick="document.getElementById('customErrorModal').remove()" class="w-full py-3 bg-red-600 text-white rounded-xl font-semibold hover:bg-red-700 transition">OK</button>
    </div>`;
    m.addEventListener('click', ev => { if (ev.target === m) m.remove(); });
    document.body.appendChild(m);
}

function showSuccessModal(message) {
    document.getElementById('customSuccessModal')?.remove();
    const m = document.createElement('div');
    m.id = 'customSuccessModal';
    m.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4';
    m.innerHTML = `<div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-2xl">
        <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900 text-center mb-2">Success</h3>
        <p class="text-gray-600 text-center text-sm mb-5">${message}</p>
        <button onclick="document.getElementById('customSuccessModal').remove()" class="w-full py-3 bg-green-600 text-white rounded-xl font-semibold hover:bg-green-700 transition">OK</button>
    </div>`;
    m.addEventListener('click', ev => { if (ev.target === m) m.remove(); });
    document.body.appendChild(m);
}

function openContactModal() { document.getElementById('contactModal')?.classList.remove('hidden'); }
function closeContactModal() { document.getElementById('contactModal')?.classList.add('hidden'); }

async function submitContactForm(event) {
    event.preventDefault();
    const form = event.target;
    const fd = new FormData(form);
    fd.append('action', 'send_verification_help');
    fd.append('vehicle_id', vehicleId);
    try {
        const res = await fetch('/templates/checkout.php', { method: 'POST', body: fd });
        const result = await res.json();
        if (result.success) {
            closeContactModal();
            showSuccessModal('Message sent! The rental company will contact you shortly.');
            form.reset();
        } else {
            showErrorModal('Failed to send: ' + (result.message || 'Unknown error'));
        }
    } catch(err) {
        showErrorModal('Error: ' + err.message);
    }
}
</script>
</body>
</html>
