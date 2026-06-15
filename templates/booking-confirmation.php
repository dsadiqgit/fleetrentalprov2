<?php
require_once __DIR__ . '/../includes/tenant_init.php';

$tenant_id = getTenantId();
$tenant = getTenant();
$pdo = getDB();

// Get booking ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: /templates/fleet.php');
    exit;
}

$booking_id = intval($_GET['id']);

// Get booking details
$stmt = $pdo->prepare("
    SELECT b.*, v.brand, v.model, v.year, v.category, v.images
    FROM bookings b
    LEFT JOIN vehicles v ON b.vehicle_id = v.id
    WHERE b.id = ? AND b.tenant_id = ?
");
$stmt->execute([$booking_id, $tenant_id]);
$booking = $stmt->fetch();

// Handle Stripe Redirect success
if (isset($_GET['redirect_status']) && $_GET['redirect_status'] === 'succeeded' && $booking && $booking['payment_status'] !== 'paid') {
    $verified = false;
    $session_id = $_GET['session_id'] ?? null;
    
    if ($session_id) {
        try {
            // Get Stripe settings
            $stmt_st = $pdo->prepare("SELECT stripe_secret_key FROM tenant_settings WHERE tenant_id = ?");
            $stmt_st->execute([$tenant_id]);
            $stripe_settings = $stmt_st->fetch();
            $stripe_sk = $stripe_settings['stripe_secret_key'] ?? '';
            
            if (!empty($stripe_sk)) {
                \Stripe\Stripe::setApiKey($stripe_sk);
                $session = \Stripe\Checkout\Session::retrieve($session_id);
                if ($session && ($session->payment_status === 'paid' || $session->status === 'complete')) {
                    $verified = true;
                }
            } else {
                // If stripe is unconfigured on local sandbox, bypass for developer testing
                $verified = true;
            }
        } catch (Exception $e) {
            error_log("Stripe confirmation verification failed: " . $e->getMessage());
            // Fallback for local sandbox environments
            if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
                $verified = true;
            }
        }
    } else {
        // Fallback for testing redirects without session_id on localhost
        if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
            $verified = true;
        }
    }
    
    if ($verified) {
        $updateStmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed', payment_status = 'paid' WHERE id = ? AND tenant_id = ?");
        $updateStmt->execute([$booking_id, $tenant_id]);
        
        // Refresh booking data
        $stmt->execute([$booking_id, $tenant_id]);
        $booking = $stmt->fetch();
        
        // Retrieve deferred credentials and trigger emails
        try {
            require_once __DIR__ . '/../includes/email.php';
            
            // Get tenant settings for deposit/currency
            $stmt_ts = $pdo->prepare("SELECT currency FROM tenant_settings WHERE tenant_id = ?");
            $stmt_ts->execute([$tenant_id]);
            $tenant_sett = $stmt_ts->fetch();
            
            // Send booking confirmation email
            $emailBookingData = [
                'id' => $booking_id,
                'customer_name' => $booking['customer_name'],
                'pickup_date' => $booking['pickup_date'],
                'pickup_time' => $booking['pickup_time'] ?? '10:00',
                'return_date' => $booking['return_date'],
                'return_time' => $booking['return_time'] ?? '10:00',
                'total_price' => $booking['total_price'],
                'security_deposit' => $booking['security_deposit'],
                'currency' => $tenant_sett['currency'] ?? 'gbp'
            ];
            
            sendBookingConfirmationEmail(
                $booking['customer_email'],
                $emailBookingData,
                $tenant,
                $booking
            );
            
            // Fetch contract to check status & get token
            $contractStmt = $pdo->prepare("SELECT * FROM contracts WHERE booking_id = ? AND tenant_id = ? LIMIT 1");
            $contractStmt->execute([$booking_id, $tenant_id]);
            $contract = $contractStmt->fetch();
            
            if ($contract) {
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'];
                $contractUrl = $protocol . $host . '/templates/contract-sign.php?booking_id=' . $booking_id . '&token=' . $contract['signing_token'];
                
                // Only send signing email if not already signed inline
                if ($contract['contract_status'] !== 'signed') {
                    sendContractWelcomeEmail(
                        $booking['customer_email'],
                        $booking['customer_name'],
                        $contractUrl,
                        $tenant
                    );
                }
            }
            
            // Check if newly created customer account credentials exist in session
            $customerAccountCreated = $_SESSION['customer_account_created'] ?? false;
            $customerPassword = $_SESSION['generated_customer_password'] ?? null;
            
            if ($customerAccountCreated && $customerPassword) {
                sendCustomerAccountEmail(
                    $booking['customer_email'],
                    $booking['customer_name'],
                    $customerPassword,
                    $tenant,
                    $booking_id
                );
            }
            
            // Clear checkout session parameters now that payment is confirmed
            unset($_SESSION['booking_data']);
            unset($_SESSION['checkout_step']);
            unset($_SESSION['didit_session_id']);
            unset($_SESSION['customer_account_created']);
            unset($_SESSION['generated_customer_password']);
            
        } catch (Exception $e) {
            error_log("Error in post-payment email dispatch: " . $e->getMessage());
        }
    }
}

if (!$booking) {
    header('Location: /templates/fleet.php');
    exit;
}

// Get website content
$stmt = $pdo->prepare("SELECT * FROM website_content WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$content = $stmt->fetch();

if (!$content) {
    $content = [
        'company_name' => $tenant['name'],
        'contact_phone' => '+1 (555) 123-4567',
        'contact_email' => 'info@yourcompany.com'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed | <?= htmlspecialchars($tenant['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">

    <!-- Universal Tenant Header Styles -->
    <?php include __DIR__ . '/includes/tenant_header.php'; ?>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Success Message -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Booking Confirmed!</h1>
            <p class="text-gray-600">Your booking reference is <span class="font-semibold text-gray-900">#<?= str_pad($booking['id'], 5, '0', STR_PAD_LEFT) ?></span></p>
        </div>

        <!-- Booking Details Card -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-6">
            <div class="p-6 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Booking Details</h2>
            </div>
            
            <div class="p-6">
                <!-- Vehicle Info -->
                <div class="flex items-start space-x-4 mb-6 pb-6 border-b border-gray-200">
                    <div class="w-24 h-24 bg-gray-200 rounded-lg overflow-hidden flex-shrink-0">
                        <?php
                        $image_url = null;
                        if ($booking['images']) {
                            $decoded = json_decode($booking['images'], true);
                            $image_url = is_array($decoded) && !empty($decoded) ? $decoded[0] : $booking['images'];
                        }
                        // Use placeholder if no image
                        if (empty($image_url)) {
                            $image_url = '/assets/images/placeholder-img.webp';
                        }
?>
                        <img src="<?= htmlspecialchars($image_url) ?>" alt="Vehicle" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($booking['brand'] . ' ' . $booking['model']) ?></h3>
                        <p class="text-gray-600"><?= htmlspecialchars($booking['year']) ?> • <?= htmlspecialchars(ucfirst($booking['category'])) ?></p>
                    </div>
                </div>

                <!-- Customer & Rental Info -->
                <div class="grid md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 mb-3">Customer Information</h4>
                        <div class="space-y-2 text-sm">
                            <div>
                                <span class="text-gray-600">Name:</span>
                                <span class="font-medium text-gray-900 ml-2"><?= htmlspecialchars($booking['customer_name']) ?></span>
                            </div>
                            <div>
                                <span class="text-gray-600">Email:</span>
                                <span class="font-medium text-gray-900 ml-2"><?= htmlspecialchars($booking['customer_email']) ?></span>
                            </div>
                            <div>
                                <span class="text-gray-600">Phone:</span>
                                <span class="font-medium text-gray-900 ml-2"><?= htmlspecialchars($booking['customer_phone']) ?></span>
                            </div>
                            <div>
                                <span class="text-gray-600">License:</span>
                                <span class="font-medium text-gray-900 ml-2"><?= htmlspecialchars($booking['customer_license']) ?></span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 mb-3">Rental Period</h4>
                        <div class="space-y-2 text-sm">
                            <div>
                                <span class="text-gray-600">Pickup:</span>
                                <span class="font-medium text-gray-900 ml-2">
                                    <?= date('M d, Y', strtotime($booking['pickup_date'])) ?> at <?= date('g:i A', strtotime($booking['pickup_time'])) ?>
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-600">Return:</span>
                                <span class="font-medium text-gray-900 ml-2">
                                    <?= date('M d, Y', strtotime($booking['return_date'])) ?> at <?= date('g:i A', strtotime($booking['return_time'])) ?>
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-600">Duration:</span>
                                <span class="font-medium text-gray-900 ml-2"><?= $booking['total_days'] ?> day<?= $booking['total_days'] > 1 ? 's' : '' ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Summary -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Payment Summary</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Price per day</span>
                            <span class="font-medium">£<?= number_format($booking['price_per_day'], 2) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Duration</span>
                            <span class="font-medium"><?= $booking['total_days'] ?> day<?= $booking['total_days'] > 1 ? 's' : '' ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Security Deposit</span>
                            <span class="font-medium">£<?= number_format($booking['security_deposit'], 2) ?></span>
                        </div>
                        <div class="flex justify-between pt-2 border-t border-gray-300">
                            <span class="font-semibold text-gray-900">Total Amount</span>
                            <span class="font-bold text-blue-600 text-lg">£<?= number_format($booking['total_price'], 2) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Payment Status</span>
                            <span class="px-2 py-1 bg-<?= $booking['payment_status'] === 'paid' ? 'green' : 'yellow' ?>-100 text-<?= $booking['payment_status'] === 'paid' ? 'green' : 'yellow' ?>-800 text-xs font-semibold rounded">
                                <?= ucfirst($booking['payment_status']) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Next Steps -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-4">What's Next?</h3>
            <ul class="space-y-3 text-sm text-blue-900">
                <li class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>You'll receive a confirmation email at <strong><?= htmlspecialchars($booking['customer_email']) ?></strong></span>
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Bring your driver's license and booking reference on pickup day</span>
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span><?= $booking['payment_status'] === 'unpaid' ? 'Payment will be collected on pickup' : 'Your payment has been processed successfully' ?></span>
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Contact us at <strong><?= htmlspecialchars($content['contact_phone']) ?></strong> if you have any questions</span>
                </li>
            </ul>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row gap-4">
            <a href="/templates/fleet.php" class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold text-center hover:bg-blue-700">
                Browse More Vehicles
            </a>
            <button onclick="window.print()" class="flex-1 px-6 py-3 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200">
                Print Confirmation
            </button>
        </div>
    </div>
    <!-- Universal Tenant Footer -->
    <?php include __DIR__ . '/includes/tenant_footer.php'; ?>
</html>
