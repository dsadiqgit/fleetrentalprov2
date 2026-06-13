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
    
    if (empty($name) || empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'Name and phone are required']);
        exit;
    }
    
    // Get tenant email
    $stmt = $pdo->prepare("SELECT company_email FROM tenant_settings WHERE tenant_id = ?");
    $stmt->execute([$tenant_id]);
    $tenant_settings = $stmt->fetch();
    $tenant_email = $tenant_settings['company_email'] ?? '';
    
    if (empty($tenant_email)) {
        echo json_encode(['success' => false, 'message' => 'Unable to send message']);
        exit;
    }
    
    // Get vehicle info
    $vehicle_info = '';
    if ($vehicle_id) {
        $stmt = $pdo->prepare("SELECT brand, model FROM vehicles WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$vehicle_id, $tenant_id]);
        $vehicle = $stmt->fetch();
        if ($vehicle) {
            $vehicle_info = htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']);
        }
    }
    
    // Send email
    try {
        require_once __DIR__ . '/../includes/email.php';
        
        $subject = "Customer ID Verification Help Request";
        $body = "
            <h2>Customer ID Verification Help Request</h2>
            <p><strong>Customer Name:</strong> " . htmlspecialchars($name) . "</p>
            <p><strong>Phone Number:</strong> " . htmlspecialchars($phone) . "</p>
            <p><strong>Message:</strong></p>
            <p>" . nl2br(htmlspecialchars($message)) . "</p>
            " . ($vehicle_info ? "<p><strong>Vehicle:</strong> " . $vehicle_info . "</p>" : "") . "
            <hr>
            <p><em>This customer is having trouble with the ID verification process and needs assistance.</em></p>
        ";
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . $tenant_email . "\r\n";
        
        $sent = mail($tenant_email, $subject, $body, $headers);
        
        if ($sent) {
            echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send email']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

$vehicle_id = $_GET['vehicle_id'] ?? $_GET['id'] ?? null;
if (!$vehicle_id) die('Vehicle not found');

$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ? AND tenant_id = ?");
$stmt->execute([$vehicle_id, $tenant_id]);
$vehicle = $stmt->fetch();
if (!$vehicle) die('Vehicle not found');

// Decode vehicle images
$vehicle_image = null;
if ($vehicle['images']) {
    $decoded = json_decode($vehicle['images'], true);
    $vehicle_image = is_array($decoded) && !empty($decoded) ? $decoded[0] : $vehicle['images'];
}
// Use placeholder if no image
if (empty($vehicle_image)) {
    $vehicle_image = '/assets/images/placeholder-img.webp';
}

$stmt = $pdo->prepare("SELECT require_license_verification, stripe_publishable_key FROM tenant_settings WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$settings = $stmt->fetch();
$require_verification = $settings ? (bool)$settings['require_license_verification'] : false;
$stripe_pk = $settings['stripe_publishable_key'] ?? '';

// Get blocked dates
$stmt = $pdo->prepare("SELECT pickup_date, return_date FROM bookings WHERE vehicle_id = ? AND status NOT IN ('cancelled', 'completed')");
$stmt->execute([$vehicle_id]);
$booked_dates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Session already started in config.php
$current_step = $_SESSION['checkout_step'] ?? 1;
$booking_data = $_SESSION['booking_data'] ?? [];

$get_pickup_date = $_GET['pickup_date'] ?? ($booking_data['pickup_date'] ?? '');
$get_pickup_time = $_GET['pickup_time'] ?? ($booking_data['pickup_time'] ?? '10:00');
$get_return_date = $_GET['return_date'] ?? ($booking_data['return_date'] ?? '');
$get_return_time = $_GET['return_time'] ?? ($booking_data['return_time'] ?? '10:00');
$get_pickup_location = $_GET['pickup_location'] ?? ($booking_data['pickup_location'] ?? '');
$get_return_location = $_GET['return_location'] ?? ($booking_data['return_location'] ?? '');

$pickup_ts = strtotime($get_pickup_date);
$pickup_date_display = $pickup_ts ? date('D, j M Y', $pickup_ts) : $get_pickup_date;

$return_ts = strtotime($get_return_date);
$return_date_display = $return_ts ? date('D, j M Y', $return_ts) : $get_return_date;

$pickup_time_display = date('h:i a', strtotime($get_pickup_time));
$return_time_display = date('h:i a', strtotime($get_return_time));

$back_pickup_param = '';
if ($pickup_ts) {
    $back_pickup_param = date('j M', $pickup_ts) . ', ' . date('h:ia', strtotime($get_pickup_time));
}
$back_return_param = '';
if ($return_ts) {
    $back_return_param = date('j M', $return_ts) . ', ' . date('h:ia', strtotime($get_return_time));
}

$back_url = "/templates/vehicle-booking.php?id=" . urlencode($vehicle_id) .
            "&pickup=" . urlencode($back_pickup_param) .
            "&return=" . urlencode($back_return_param) .
            "&pickup_location=" . urlencode($get_pickup_location) .
            "&return_location=" . urlencode($get_return_location);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?= htmlspecialchars($tenant['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://js.stripe.com/v3/"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>
<body class="bg-gray-50">
    <header class="bg-white border-b">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center">
            <?php if (!empty($tenant['logo_url']) || !empty($tenant['logo'])): ?>
                <img src="<?= htmlspecialchars($tenant['logo_url'] ?: $tenant['logo']) ?>" alt="<?= htmlspecialchars($tenant['name']) ?>" class="h-10 w-auto">
            <?php else: ?>
                <span class="text-lg font-semibold"><?= htmlspecialchars($tenant['name']) ?></span>
            <?php endif; ?>
        </div>
    </header>

    <!-- Progress Steps -->
    <div class="bg-white border-b">
        <div class="max-w-7xl mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center flex-1">
                    <div id="step1-indicator" class="w-10 h-10 rounded-full flex items-center justify-center font-semibold <?= $current_step >= 1 ? 'bg-blue-600 text-white' : 'bg-gray-200' ?>">
                        <?= $current_step > 1 ? '✓' : '1' ?>
                    </div>
                    <span class="ml-2 text-sm">Details</span>
                    <div class="flex-1 h-0.5 mx-4 <?= $current_step > 1 ? 'bg-blue-600' : 'bg-gray-200' ?>"></div>
                </div>
                
                <div id="step2-container" class="flex items-center flex-1 <?= $require_verification ? '' : 'hidden' ?>">
                    <div id="step2-indicator" class="w-10 h-10 rounded-full flex items-center justify-center font-semibold <?= $current_step >= 2 ? 'bg-blue-600 text-white' : 'bg-gray-200' ?>">
                        <?= $current_step > 2 ? '✓' : '2' ?>
                    </div>
                    <span class="ml-2 text-sm">Verification</span>
                    <div class="flex-1 h-0.5 mx-4 <?= $current_step > 2 ? 'bg-blue-600' : 'bg-gray-200' ?>"></div>
                </div>
                
                <div id="step3-container" class="flex items-center flex-1">
                    <div id="step3-indicator" class="w-10 h-10 rounded-full flex items-center justify-center font-semibold <?= $current_step >= 3 ? 'bg-blue-600 text-white' : 'bg-gray-200' ?>">
                        <?= $current_step > 3 ? '✓' : ($require_verification ? '3' : '2') ?>
                    </div>
                    <span class="ml-2 text-sm">Payment</span>
                    <div class="flex-1 h-0.5 mx-4 <?= $current_step > 3 ? 'bg-blue-600' : 'bg-gray-200' ?>"></div>
                </div>
                
                <div id="step4-container" class="flex items-center">
                    <div id="step4-indicator" class="w-10 h-10 rounded-full flex items-center justify-center font-semibold <?= $current_step >= 4 ? 'bg-blue-600 text-white' : 'bg-gray-200' ?>">
                        <?= $require_verification ? '4' : '3' ?>
                    </div>
                    <span class="ml-2 text-sm">Confirm</span>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="grid lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <!-- Step 1: Details -->
                <div id="step1" class="<?= $current_step !== 1 ? 'hidden' : '' ?> bg-white rounded-lg p-8">
                    <h2 class="text-2xl font-bold mb-6">Your Details</h2>
                    <form id="detailsForm">
                        <input type="hidden" name="vehicle_id" value="<?= $vehicle_id ?>">
                        <div class="grid md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium mb-2">Full Name *</label>
                                <input type="text" name="customer_name" required class="w-full px-4 py-3 border rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Email *</label>
                                <input type="email" name="customer_email" required class="w-full px-4 py-3 border rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Phone *</label>
                                <input type="tel" name="customer_phone" required maxlength="12" pattern="[0-9]{1,12}" class="w-full px-4 py-3 border rounded-lg" placeholder="Enter up to 12 digits" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Licence Number *</label>
                                <input type="text" name="customer_license" required class="w-full px-4 py-3 border rounded-lg">
                            </div>
                            <!-- Date & Time Display Container (Read-only) -->
                            <div class="col-span-1 md:col-span-2 bg-gray-50 border border-gray-200 rounded-xl p-5 mt-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                <div class="space-y-3 w-full">
                                    <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Rental Period Details</h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full">
                                        <div class="border-l-4 border-blue-500 pl-3">
                                            <span class="block text-xs font-semibold text-gray-400 uppercase">Pick-up</span>
                                            <span class="block text-sm font-bold text-gray-800"><?= htmlspecialchars($pickup_date_display) ?></span>
                                            <span class="block text-xs text-gray-500 font-medium"><?= htmlspecialchars($pickup_time_display) ?></span>
                                        </div>
                                        <div class="border-l-4 border-blue-500 pl-3">
                                            <span class="block text-xs font-semibold text-gray-400 uppercase">Return</span>
                                            <span class="block text-sm font-bold text-gray-800"><?= htmlspecialchars($return_date_display) ?></span>
                                            <span class="block text-xs text-gray-500 font-medium"><?= htmlspecialchars($return_time_display) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <a href="<?= htmlspecialchars($back_url) ?>" class="shrink-0 w-full sm:w-auto px-4 py-2.5 text-center text-xs font-bold text-blue-600 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition-colors flex items-center justify-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Change Dates / Times
                                </a>
                            </div>

                            <!-- Keep fields hidden for step submission -->
                            <input type="hidden" id="pickupDate" name="pickup_date" value="<?= htmlspecialchars($get_pickup_date) ?>">
                            <input type="hidden" name="pickup_time" value="<?= htmlspecialchars($get_pickup_time) ?>">
                            <input type="hidden" id="returnDate" name="return_date" value="<?= htmlspecialchars($get_return_date) ?>">
                            <input type="hidden" name="return_time" value="<?= htmlspecialchars($get_return_time) ?>">
                        </div>
                        <button id="step1SubmitBtn" type="submit" class="w-full px-6 py-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
                            <?= $require_verification ? 'Continue to Verification →' : 'Continue to Payment →' ?>
                        </button>
                    </form>
                </div>

                <!-- Step 2: Verification -->
                <div id="step2" class="<?= $current_step !== 2 ? 'hidden' : '' ?> bg-white rounded-lg p-8">
                    <h2 class="text-2xl font-bold mb-4">Identity Verification</h2>
                    <p class="text-gray-600 mb-6">Complete identity verification to continue.</p>
                    <div id="diditVerification" class="border-2 rounded-lg overflow-hidden mb-6">
                        <iframe id="diditIframe" src="" allow="camera; microphone; geolocation" class="w-full h-[600px] border-0" frameborder="0"></iframe>
                    </div>
                    <div id="verificationStatus" class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg hidden">
                        <p class="text-sm text-blue-800">Checking verification status...</p>
                    </div>
                    <div id="manualApproveSection" class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg hidden">
                        <p class="text-sm text-yellow-800 mb-2">If you've completed verification but the button hasn't activated:</p>
                        <button onclick="openContactModal()" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 text-sm">
                            Contact Rental Company
                        </button>
                    </div>
                    <div class="flex gap-4">
                        <button onclick="goToStep(1)" class="flex-1 px-6 py-3 border rounded-lg">← Back</button>
                        <button id="verificationCompleteBtn" onclick="manualContinue()" disabled class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg disabled:bg-gray-300 disabled:cursor-not-allowed">
                            Continue to Payment →
                        </button>
                    </div>
                </div>

                <!-- Step 3: Payment -->
                <div id="step3" class="<?= $current_step !== 3 ? 'hidden' : '' ?> bg-white rounded-lg p-8">
                    <h2 class="text-2xl font-bold mb-6">Payment</h2>
                    <form id="paymentForm">
                        <div class="mb-6">
                            <label class="block text-sm font-medium mb-4">Payment Method</label>
                            <div class="space-y-3">
                                <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer">
                                    <input type="radio" name="payment_method" value="card" checked class="w-4 h-4">
                                    <span class="ml-3 font-medium">Credit/Debit Card</span>
                                </label>
                                <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer">
                                    <input type="radio" name="payment_method" value="cash" class="w-4 h-4">
                                    <span class="ml-3 font-medium">Pay at Pickup</span>
                                </label>
                            </div>
                        </div>

                        <!-- Stripe Card Element Container -->
                        <div id="card-element-container" class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg flex items-center gap-3 transition-all">
                            <svg class="w-5 h-5 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <p class="text-xs text-blue-800 font-medium">
                                You will be securely redirected to Stripe to enter your payment details.
                            </p>
                        </div>
                        <div class="flex gap-4">
                            <button type="button" onclick="goToStep(<?= $require_verification ? 2 : 1 ?>)" class="flex-1 px-6 py-3 border rounded-lg">← Back</button>
                            <button type="submit" class="flex-1 px-6 py-4 bg-blue-600 text-white rounded-lg font-semibold">Complete Booking ✓</button>
                        </div>
                    </form>
                </div>

                <!-- Step 4: Confirmation -->
                <div id="step4" class="<?= $current_step !== 4 ? 'hidden' : '' ?> bg-white rounded-lg p-8 text-center">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold mb-4">Booking Confirmed!</h2>
                    <p class="text-gray-600 mb-4">Booking ID: <span id="bookingId" class="font-mono font-semibold">#</span></p>
                    
                    <!-- Account created notice -->
                    <div id="accountCreatedNotice" class="hidden mb-6 mx-auto max-w-md text-left bg-blue-50 border border-blue-200 rounded-lg p-5">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-blue-900 mb-1">Account Created!</p>
                                <p class="text-sm text-blue-700">An account has been created for you. Check your email for your login details so you can manage your bookings online.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                        <a href="/templates/fleet.php" class="inline-block px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Back to Fleet</a>
                        <a id="loginLink" href="/auth/login.php" class="hidden inline-block px-8 py-3 border border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 transition-colors">Login to Your Account</a>
                    </div>
                </div>
            </div>

            <!-- Summary Sidebar -->
            <div>
                <div class="bg-white rounded-lg p-6 sticky top-4">
                    <h3 class="text-lg font-bold mb-4">Summary</h3>
                    <?php if ($vehicle_image): ?>
                        <img src="<?= htmlspecialchars($vehicle_image) ?>" alt="Vehicle" class="w-full h-40 object-cover rounded-lg mb-4">
                    <?php endif; ?>
                    <h4 class="font-bold mb-1"><?= htmlspecialchars($vehicle['name']) ?></h4>
                    <p class="text-gray-600 text-sm mb-4"><?= htmlspecialchars($vehicle['year']) ?></p>
                    <div class="border-t pt-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span>Daily Rate:</span>
                            <span class="font-semibold">£<?= number_format($vehicle['price_per_day'], 2) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Days:</span>
                            <span id="numDays" class="font-semibold">0</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold border-t pt-3">
                            <span>Total:</span>
                            <span id="totalPrice" class="text-blue-600">£0.00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        const pricePerDay = <?= $vehicle['price_per_day'] ?>;
        const diditAppId = '3b64939d-1ba7-42df-b602-344cbc78e387';
        
        // Run initial price calculation
        calculatePrice();

        const stripePk = '<?= htmlspecialchars($stripe_pk) ?>';

        if (!stripePk) {
            // Hide card option if Stripe is not configured
            const cardRadio = document.querySelector('input[name="payment_method"][value="card"]');
            if (cardRadio) {
                const label = cardRadio.closest('label');
                if (label) label.classList.add('hidden');
            }
            const cashRadio = document.querySelector('input[name="payment_method"][value="cash"]');
            if (cashRadio) {
                cashRadio.checked = true;
            }
            const container = document.getElementById('card-element-container');
            if (container) container.classList.add('hidden');
        }

        const paymentMethodRadios = document.querySelectorAll('input[name="payment_method"]');
        const cardContainer = document.getElementById('card-element-container');
        
        paymentMethodRadios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                if (e.target.value === 'card') {
                    cardContainer.classList.remove('hidden');
                } else {
                    cardContainer.classList.add('hidden');
                }
            });
        });
        
        function goToStep(step) {
            document.querySelectorAll('[id^="step"]').forEach(el => {
                if(el.id === 'step1' || el.id === 'step2' || el.id === 'step3' || el.id === 'step4') {
                    el.classList.add('hidden');
                }
            });
            document.getElementById('step' + step).classList.remove('hidden');
            fetch('/templates/update-checkout-step.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({step: step})
            });
            
            // update indicators dynamically
            const reqVerify = <?= $require_verification ? 'true' : 'false' ?>;
            for(let i = 1; i <= 4; i++) {
                const ind = document.getElementById('step' + i + '-indicator');
                if(!ind) continue;
                
                let num = i;
                if(!reqVerify) {
                    if(i === 3) num = 2;
                    if(i === 4) num = 3;
                }
                
                if (i < step) {
                    ind.className = 'w-10 h-10 rounded-full flex items-center justify-center font-semibold bg-blue-600 text-white';
                    ind.innerHTML = '✓';
                } else if (i === step) {
                    ind.className = 'w-10 h-10 rounded-full flex items-center justify-center font-semibold bg-blue-600 text-white';
                    ind.innerHTML = num;
                } else {
                    ind.className = 'w-10 h-10 rounded-full flex items-center justify-center font-semibold bg-gray-200 text-gray-700';
                    ind.innerHTML = num;
                }
            }
        }

        function calculatePrice() {
            const pickup = document.getElementById('pickupDate')?.value;
            const returnDate = document.getElementById('returnDate')?.value;
            if (pickup && returnDate) {
                const days = Math.ceil((new Date(returnDate) - new Date(pickup)) / (1000 * 60 * 60 * 24));
                if (days > 0) {
                    const total = days * pricePerDay;
                    document.getElementById('numDays').textContent = days + ' day' + (days > 1 ? 's' : '');
                    document.getElementById('totalPrice').textContent = '£' + total.toFixed(2);
                }
            }
        }

        document.getElementById('detailsForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('step1SubmitBtn');
            if(submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Processing...';
            }
            
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            
            const requireVerification = <?= $require_verification ? 'true' : 'false' ?>;
            if (!requireVerification) {
                data.skip_verification = true;
            }
            
            const res = await fetch('/templates/save-booking-data.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
            
            const result = await res.json();
            
            if (result.success && result.skipped) {
                goToStep(3);
            } else if (result.success && result.verification_url) {
                // Ensure URL uses verify.didit.me
                let url = result.verification_url;
                if (url.includes('business.didit.me')) {
                    url = url.replace('business.didit.me', 'verify.didit.me');
                }
                document.getElementById('diditIframe').src = url;
                goToStep(2);
            } else {
                showErrorModal('Error: ' + (result.message || 'Failed to create verification session'));
                console.error('Didit API Error:', result);
                if(submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = requireVerification ? 'Continue to Verification →' : 'Continue to Payment →';
                }
            }
        });

        // Poll for verification status as fallback
        let verificationCheckInterval;
        
        function actualGoToStep(step) {
            document.querySelectorAll('[id^="step"]').forEach(el => el.classList.add('hidden'));
            document.getElementById('step' + step).classList.remove('hidden');
            fetch('/templates/update-checkout-step.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({step: step})
            });
        }
        
        let verificationDetected = false;
        
        function handleVerificationApproved(isAgeIneligible = false, ageLimit = 18, actualAge = 0) {
            if (verificationCheckInterval) {
                clearInterval(verificationCheckInterval);
            }
            
            if (isAgeIneligible) {
                const status = document.getElementById('verificationStatus');
                const btn = document.getElementById('verificationCompleteBtn');
                
                if (status) {
                    status.classList.remove('hidden', 'bg-blue-50', 'border-blue-200');
                    status.classList.add('bg-red-50', 'border-red-200');
                    status.querySelector('p').className = 'text-sm text-red-800 font-medium';
                    status.querySelector('p').innerHTML = `✕ <strong>Booking Disqualified:</strong> Minimum age for this vehicle is ${ageLimit}. Your verified age is ${actualAge}.`;
                }
                
                if (btn) {
                    btn.disabled = true;
                    btn.textContent = '✕ Minimum Age Not Met';
                    btn.classList.remove('bg-blue-600');
                    btn.classList.add('bg-red-600');
                }
                return;
            }
            
            verificationDetected = true;
            console.log('Verification approved! Enabling button...');
            
            const btn = document.getElementById('verificationCompleteBtn');
            const status = document.getElementById('verificationStatus');
            
            if (btn) {
                btn.disabled = false;
                btn.textContent = '✓ Verified - Continue to Payment →';
                btn.classList.remove('disabled:bg-gray-300', 'disabled:cursor-not-allowed');
                btn.classList.add('bg-green-600', 'hover:bg-green-700');
            }
            
            if (status) {
                status.classList.remove('hidden', 'bg-blue-50', 'border-blue-200');
                status.classList.add('bg-green-50', 'border-green-200');
                status.querySelector('p').className = 'text-sm text-green-800 font-medium';
                status.querySelector('p').textContent = '✓ Verification complete! You can now continue to payment.';
            }
            
            // Save verification status to backend
            fetch('/templates/force-verification-approved.php', { 
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'status=approved'
            });
        }
        
        function manualContinue() {
            if (verificationDetected) {
                console.log('Manual continue - advancing to step 3...');
                actualGoToStep(3);
            }
        }
        
        function forceApprove() {
            console.log('Force approve triggered by user');
            handleVerificationApproved();
        }

        function startVerificationCheck() {
            console.log("Starting verification status polling...");
            
            // Show status indicator
            const status = document.getElementById('verificationStatus');
            if (status) {
                status.classList.remove('hidden');
            }
            
            let pollCount = 0;
            verificationCheckInterval = setInterval(async () => {
                pollCount++;
                try {
                    const res = await fetch('/templates/check-verification-status.php');
                    const result = await res.json();
                    console.log(`Poll #${pollCount}:`, result);
                    
                    if (result.status === 'approved' || result.status === 'in_review') {
                        console.log("Status is approved/in_review - checking age eligibility...");
                        if (result.age_eligible === false) {
                            handleVerificationApproved(true, result.age_limit, result.age);
                        } else {
                            handleVerificationApproved();
                        }
                    } else if (result.status === 'declined') {
                        clearInterval(verificationCheckInterval);
                        showErrorModal('Verification was declined. Please try again or contact support.');
                        goToStep(1);
                    }
                } catch (e) {
                    console.error("Polling error:", e);
                }
            }, 2000); // Check every 2 seconds
            
            // Show manual approve button after 15 seconds if not auto-detected
            setTimeout(() => {
                if (!verificationDetected) {
                    const manualSection = document.getElementById('manualApproveSection');
                    if (manualSection) {
                        manualSection.classList.remove('hidden');
                    }
                }
            }, 15000);
            
            // Also listen for iframe completion message
            window.addEventListener('message', function(event) {
                console.log("Received iframe message:", event.data);
                
                // If we detect completion message from Didit iframe, force a status check
                if (event.data && typeof event.data === 'string' && 
                    (event.data.includes('verified') || event.data.includes('complete') || event.data.includes('approved'))) {
                    console.log("Iframe indicates completion - forcing status check in 2 seconds...");
                    setTimeout(async () => {
                        try {
                            const res = await fetch('/templates/check-verification-status.php');
                            const result = await res.json();
                            console.log("Forced check result:", result);
                            if (result.status === 'approved' || result.status === 'in_review') {
                                if (result.age_eligible === false) {
                                    handleVerificationApproved(true, result.age_limit, result.age);
                                } else {
                                    handleVerificationApproved();
                                }
                            }
                        } catch (e) {
                            console.error("Forced check error:", e);
                        }
                    }, 2000);
                }
            });
        }
        
        // Start checking when moving to step 2
        const originalGoToStep = goToStep;
        goToStep = function(step) {
            originalGoToStep(step);
            if (step === 2) {
                startVerificationCheck();
            } else if (verificationCheckInterval) {
                clearInterval(verificationCheckInterval);
            }
        };

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

        // Custom success modal function
        function showSuccessModal(message) {
            const existingModal = document.getElementById('customSuccessModal');
            if (existingModal) {
                existingModal.remove();
            }

            const modal = document.createElement('div');
            modal.id = 'customSuccessModal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-lg p-6 max-w-md mx-4 shadow-xl">
                    <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-green-100 rounded-full">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 text-center mb-2">Success</h3>
                    <p class="text-gray-600 text-center mb-6">${message}</p>
                    <button onclick="document.getElementById('customSuccessModal').remove()" class="w-full px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold transition-colors">
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

        // Form validation function
        function validateCheckoutForm(form) {
            const errors = [];
            
            // Get form values
            const customerName = form.querySelector('[name="customer_name"]')?.value?.trim();
            const customerEmail = form.querySelector('[name="customer_email"]')?.value?.trim();
            const customerPhone = form.querySelector('[name="customer_phone"]')?.value?.trim();
            const customerLicense = form.querySelector('[name="customer_license"]')?.value?.trim();
            
            // Validate name
            if (!customerName || customerName.length < 2) {
                errors.push('Please enter a valid full name (at least 2 characters).');
            }
            
            // Validate email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!customerEmail || !emailRegex.test(customerEmail)) {
                errors.push('Please enter a valid email address.');
            }
            
            // Validate phone (numeric only, max 12 digits)
            const phoneRegex = /^[0-9]{1,12}$/;
            if (!customerPhone || !phoneRegex.test(customerPhone)) {
                errors.push('Please enter a valid phone number (up to 12 digits, numbers only).');
            }
            
            // Validate license
            if (!customerLicense || customerLicense.length < 3) {
                errors.push('Please enter a valid driver\'s license number (at least 3 characters).');
            }
            
            return errors;
        }

        document.getElementById('paymentForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Validate form before submission
            const form = e.target;
            const validationErrors = validateCheckoutForm(form);
            
            if (validationErrors.length > 0) {
                showErrorModal(validationErrors.join('<br>'));
                return;
            }
            
            const submitBtn = e.target.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Redirecting to Stripe...';
            }

            const formData = new FormData(e.target);
            const paymentMethod = formData.get('payment_method');

            try {
                const res = await fetch('/templates/process-booking.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await res.json();
                if (!result.success) {
                    throw new Error(result.message || 'Failed to process booking');
                }

                if (paymentMethod === 'card') {
                    if (result.redirect_url) {
                        window.location.href = result.redirect_url;
                        return; // Wait for redirect
                    } else {
                        throw new Error('Failed to initialize card payment session. Please try again.');
                    }
                }

                document.getElementById('bookingId').textContent = '#' + result.booking_id;
                
                // Show account created notice if a new account was made
                if (result.account_created) {
                    const notice = document.getElementById('accountCreatedNotice');
                    const loginLink = document.getElementById('loginLink');
                    if (notice) notice.classList.remove('hidden');
                    if (loginLink) loginLink.classList.remove('hidden');
                }
                
                goToStep(4);
            } catch (error) {
                showErrorModal('Error: ' + error.message);
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Complete Booking ✓';
                }
            }
        });
    </script>

    <!-- Contact Modal -->
    <div id="contactModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 max-w-md mx-4 w-full">
            <h3 class="text-xl font-bold mb-4">Contact Rental Company</h3>
            <p class="text-sm text-gray-600 mb-4">Having trouble with ID verification? Let us know and we'll help you complete your booking.</p>
            <form id="contactForm" onsubmit="submitContactForm(event)">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Your Name *</label>
                    <input type="text" name="contact_name" required class="w-full px-4 py-3 border rounded-lg">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Phone Number *</label>
                    <input type="tel" name="contact_phone" required class="w-full px-4 py-3 border rounded-lg" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Message</label>
                    <textarea name="contact_message" rows="3" class="w-full px-4 py-3 border rounded-lg" placeholder="Describe your issue..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeContactModal()" class="flex-1 px-4 py-3 border rounded-lg">Cancel</button>
                    <button type="submit" class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-lg">Send Message</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openContactModal() {
            document.getElementById('contactModal').classList.remove('hidden');
        }

        function closeContactModal() {
            document.getElementById('contactModal').classList.add('hidden');
        }

        async function submitContactForm(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            formData.append('action', 'send_verification_help');
            formData.append('vehicle_id', '<?= $vehicle_id ?>');

            try {
                const res = await fetch('/templates/checkout.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();
                if (result.success) {
                    closeContactModal();
                    showSuccessModal('Message sent! The rental company will contact you shortly.');
                    form.reset();
                } else {
                    showErrorModal('Failed to send message: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                showErrorModal('Error sending message: ' + error.message);
            }
        }
    </script>
</body>
</html>
