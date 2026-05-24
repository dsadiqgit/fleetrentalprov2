<?php
require_once __DIR__ . '/../includes/tenant_init.php';
$tenant_id = getTenantId();
$tenant = getTenant();
$pdo = getDB();

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

$stmt = $pdo->prepare("SELECT require_license_verification FROM tenant_settings WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$settings = $stmt->fetch();
$require_verification = $settings ? (bool)$settings['require_license_verification'] : false;

// Get blocked dates
$stmt = $pdo->prepare("SELECT pickup_date, return_date FROM bookings WHERE vehicle_id = ? AND status NOT IN ('cancelled', 'completed')");
$stmt->execute([$vehicle_id]);
$booked_dates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Session already started in config.php
$current_step = $_SESSION['checkout_step'] ?? 1;
$booking_data = $_SESSION['booking_data'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?= htmlspecialchars($tenant['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>
<body class="bg-gray-50">
    <header class="bg-white border-b">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <span class="text-lg font-semibold"><?= htmlspecialchars($tenant['name']) ?></span>
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
                                <input type="tel" name="customer_phone" required class="w-full px-4 py-3 border rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Licence Number *</label>
                                <input type="text" name="customer_license" required class="w-full px-4 py-3 border rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Pickup Date *</label>
                                <input type="date" id="pickupDate" name="pickup_date" required class="w-full px-4 py-3 border rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Pickup Time *</label>
                                <input type="time" name="pickup_time" value="10:00" required class="w-full px-4 py-3 border rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Return Date *</label>
                                <input type="date" id="returnDate" name="return_date" required class="w-full px-4 py-3 border rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Return Time *</label>
                                <input type="time" name="return_time" value="10:00" required class="w-full px-4 py-3 border rounded-lg">
                            </div>
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
                        <button onclick="forceApprove()" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 text-sm">
                            I've Completed Verification - Enable Button
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
        
        // Initialize Availability Calendar
        const bookedDatesLists = <?= json_encode($booked_dates) ?>;
        const unavailableDatesStr = "<?= htmlspecialchars($vehicle['unavailable_dates'] ?? '') ?>";
        const unavailableDatesMap = unavailableDatesStr ? unavailableDatesStr.split(", ") : [];
        
        const disableDates = bookedDatesLists.map(b => ({
            from: b.pickup_date,
            to: b.return_date
        })).concat(unavailableDatesMap);

        const returnFlatpickr = flatpickr("#returnDate", {
            minDate: "today",
            disable: disableDates,
            dateFormat: "Y-m-d",
            onChange: function() {
                calculatePrice();
            }
        });

        const pickupFlatpickr = flatpickr("#pickupDate", {
            minDate: "today",
            disable: disableDates,
            dateFormat: "Y-m-d",
            onChange: function(selectedDates, dateStr) {
                returnFlatpickr.set('minDate', dateStr);
                calculatePrice();
            }
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
                alert('Error: ' + (result.message || 'Failed to create verification session'));
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
                        alert('Verification was declined. Please try again or contact support.');
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

        document.getElementById('paymentForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            const res = await fetch('/templates/process-booking.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await res.json();
            if (result.success) {
                document.getElementById('bookingId').textContent = '#' + result.booking_id;
                
                // Show account created notice if a new account was made
                if (result.account_created) {
                    const notice = document.getElementById('accountCreatedNotice');
                    const loginLink = document.getElementById('loginLink');
                    if (notice) notice.classList.remove('hidden');
                    if (loginLink) loginLink.classList.remove('hidden');
                }
                
                goToStep(4);
            }
        });
    </script>
</body>
</html>
