<?php
require_once __DIR__ . '/../includes/tenant_init.php';

$tenant_id = getTenantId();
$tenant = getTenant();
$pdo = getDB();

$booking = null;
$booking_id = null;

// =============================================
// CARD PAYMENT FLOW: Create booking AFTER Stripe confirms payment
// =============================================
if (isset($_GET['redirect_status']) && $_GET['redirect_status'] === 'succeeded') {
    $verified = false;
    $session_id = $_GET['session_id'] ?? null;
    $pending = $_SESSION['stripe_pending_booking'] ?? null;
    
    if ($session_id && $pending) {
        try {
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
                $verified = true; // local sandbox fallback
            }
        } catch (Exception $e) {
            error_log("Stripe confirmation verification failed: " . $e->getMessage());
            if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
                $verified = true;
            }
        }
    } elseif (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
        $verified = true; // localhost fallback
    }
    
    if ($verified && $pending) {
        $bd = $pending['booking_data'];
        
        try {
            // 1) CREATE BOOKING
            $insertStmt = $pdo->prepare("
                INSERT INTO bookings (
                    tenant_id, vehicle_id, customer_name, customer_email, customer_phone,
                    customer_license, pickup_date, return_date, pickup_time, return_time,
                    total_days, price_per_day, total_price, security_deposit, status,
                    payment_status, stripe_payment_id, notes, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', 'paid', ?, ?, NOW())
            ");
            $insertStmt->execute([
                $tenant_id,
                $bd['vehicle_id'],
                $bd['customer_name'],
                $bd['customer_email'],
                $bd['customer_phone'] ?? null,
                $bd['customer_license'] ?? null,
                $bd['pickup_date'],
                $bd['return_date'],
                $bd['pickup_time'] ?? '10:00',
                $bd['return_time'] ?? '10:00',
                $bd['total_days'],
                $bd['price_per_day'],
                $pending['total_price'],
                $pending['security_deposit'],
                $session_id,
                $bd['notes'] ?? ''
            ]);
            $booking_id = $pdo->lastInsertId();
            
            // Fetch the newly created booking with vehicle info for display
            $stmtBk = $pdo->prepare("
                SELECT b.*, v.brand, v.model, v.year, v.category, v.images
                FROM bookings b
                LEFT JOIN vehicles v ON b.vehicle_id = v.id
                WHERE b.id = ? AND b.tenant_id = ?
            ");
            $stmtBk->execute([$booking_id, $tenant_id]);
            $booking = $stmtBk->fetch();
            
            // 2) CREATE CUSTOMER ACCOUNT
            $customerAccountCreated = false;
            $customerPassword = null;
            try {
                require_once __DIR__ . '/../includes/functions.php';
                if (!empty($bd['customer_email'])) {
                    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ? AND tenant_id = ?");
                    $stmt->execute([$bd['customer_email'], $tenant_id]);
                    $existingUser = $stmt->fetch();
                    
                    if (!$existingUser) {
                        $customerPassword = substr(bin2hex(random_bytes(5)), 0, 8);
                        $hashedPassword = hashPassword($customerPassword);
                        $stmt = $pdo->prepare("
                            INSERT INTO users (tenant_id, role, email, password, full_name, phone, created_at)
                            VALUES (?, 'customer', ?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([
                            $tenant_id,
                            $bd['customer_email'],
                            $hashedPassword,
                            $bd['customer_name'],
                            $bd['customer_phone'] ?? null
                        ]);
                        $customerAccountCreated = true;
                    } elseif (empty($existingUser['password'])) {
                        $customerPassword = substr(bin2hex(random_bytes(5)), 0, 8);
                        $hashedPassword = hashPassword($customerPassword);
                        $pdo->prepare("UPDATE users SET password = ?, full_name = COALESCE(NULLIF(full_name, ''), ?), phone = COALESCE(NULLIF(phone, ''), ?) WHERE id = ?")
                            ->execute([$hashedPassword, $bd['customer_name'], $bd['customer_phone'] ?? null, $existingUser['id']]);
                        $customerAccountCreated = true;
                    }
                }
            } catch (Exception $accountError) {
                error_log("Customer account creation error (non-fatal): " . $accountError->getMessage());
            }
            
            // 3) CREATE CONTRACT
            $signingToken = bin2hex(random_bytes(32));
            try {
                $columnsToAdd = [
                    'contract_status' => "ENUM('pending', 'signed') DEFAULT 'pending'",
                    'signature_typed' => 'VARCHAR(255) NULL',
                    'signed_pdf_path' => 'VARCHAR(500) NULL',
                    'signing_token' => 'VARCHAR(64) NULL',
                ];
                foreach ($columnsToAdd as $col => $definition) {
                    $checkCol = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contracts' AND COLUMN_NAME = ?");
                    $checkCol->execute([$col]);
                    if ($checkCol->fetchColumn() == 0) {
                        $pdo->exec("ALTER TABLE contracts ADD COLUMN {$col} {$definition}");
                    }
                }
                
                $tmplStmt = $pdo->prepare("SELECT * FROM contract_templates WHERE tenant_id = ? AND is_default = 1 LIMIT 1");
                $tmplStmt->execute([$tenant_id]);
                $defaultTemplate = $tmplStmt->fetch();
                if (!$defaultTemplate) {
                    $tmplStmt = $pdo->prepare("SELECT * FROM contract_templates WHERE tenant_id = ? ORDER BY created_at ASC LIMIT 1");
                    $tmplStmt->execute([$tenant_id]);
                    $defaultTemplate = $tmplStmt->fetch();
                }
                
                $contractContent = $defaultTemplate ? $defaultTemplate['content'] : 'Default rental agreement for booking #' . $booking_id;
                $templateId = $defaultTemplate ? $defaultTemplate['id'] : null;
                
                $inlineSignature = $_SESSION['contract_signature'] ?? null;
                $inlineSignedAt  = $_SESSION['contract_signed_at'] ?? null;
                $contractStatus  = $inlineSignature ? 'signed' : 'pending';
                
                $contractStmt = $pdo->prepare("
                    INSERT INTO contracts (tenant_id, booking_id, template_id, content, contract_status, signing_token, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $contractStmt->execute([$tenant_id, $booking_id, $templateId, $contractContent, $contractStatus, $signingToken]);
                $newContractId = $pdo->lastInsertId();
                
                if ($inlineSignature && $newContractId) {
                    try {
                        $signatureImagePath = null;
                        if (strpos($inlineSignature, 'data:image/') === 0) {
                            $sigDir = __DIR__ . '/../uploads/signatures';
                            if (!is_dir($sigDir)) mkdir($sigDir, 0755, true);
                            $imgData = explode(',', $inlineSignature, 2);
                            if (count($imgData) === 2) {
                                $decoded = base64_decode($imgData[1]);
                                $sigFilename = 'sig_' . $booking_id . '_' . time() . '.png';
                                $signatureImagePath = $sigDir . '/' . $sigFilename;
                                file_put_contents($signatureImagePath, $decoded);
                            }
                        }
                        $pdo->prepare("UPDATE contracts SET signed = 1, signed_at = ?, signature_typed = ? WHERE id = ?")
                            ->execute([$inlineSignedAt, $signatureImagePath ?? $inlineSignature, $newContractId]);
                    } catch (Exception $signEx) {
                        error_log("Could not save contract signature: " . $signEx->getMessage());
                    }
                    unset($_SESSION['contract_signature'], $_SESSION['contract_signed_at']);
                }
            } catch (Exception $contractError) {
                error_log("Contract creation error (non-fatal): " . $contractError->getMessage());
            }
            
            // 4) SEND EMAILS
            try {
                require_once __DIR__ . '/../includes/email.php';
                
                $emailBookingData = [
                    'id' => $booking_id,
                    'customer_name' => $bd['customer_name'],
                    'pickup_date' => $bd['pickup_date'],
                    'pickup_time' => $bd['pickup_time'] ?? '10:00',
                    'return_date' => $bd['return_date'],
                    'return_time' => $bd['return_time'] ?? '10:00',
                    'total_price' => $pending['total_price'],
                    'security_deposit' => $pending['security_deposit'],
                    'currency' => $pending['stripe_settings']['currency'] ?? 'gbp'
                ];
                
                sendBookingConfirmationEmail(
                    $bd['customer_email'],
                    $emailBookingData,
                    $tenant,
                    ['brand' => $pending['vehicle']['brand'], 'model' => $pending['vehicle']['model']]
                );
                
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'];
                $contractUrl = $protocol . $host . '/templates/contract-sign.php?booking_id=' . $booking_id . '&token=' . $signingToken;
                
                sendContractWelcomeEmail(
                    $bd['customer_email'],
                    $bd['customer_name'],
                    $contractUrl,
                    $tenant
                );
                
                if ($customerAccountCreated && $customerPassword) {
                    sendCustomerAccountEmail(
                        $bd['customer_email'],
                        $bd['customer_name'],
                        $customerPassword,
                        $tenant,
                        $booking_id
                    );
                }
            } catch (Exception $e) {
                error_log("Error in post-payment email dispatch: " . $e->getMessage());
            }
            
            // Clear all session data now that payment is confirmed
            unset($_SESSION['booking_data']);
            unset($_SESSION['checkout_step']);
            unset($_SESSION['didit_session_id']);
            unset($_SESSION['stripe_pending_booking']);
            
        } catch (Exception $e) {
            error_log("Failed to create booking after Stripe confirmation: " . $e->getMessage());
            header('Location: /templates/fleet.php');
            exit;
        }
    } else {
        header('Location: /templates/fleet.php');
        exit;
    }
}

// =============================================
// CASH PAYMENT FLOW: Booking already exists in DB
// =============================================
if (!$booking && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $booking_id = intval($_GET['id']);
    $stmt = $pdo->prepare("
        SELECT b.*, v.brand, v.model, v.year, v.category, v.images
        FROM bookings b
        LEFT JOIN vehicles v ON b.vehicle_id = v.id
        WHERE b.id = ? AND b.tenant_id = ?
    ");
    $stmt->execute([$booking_id, $tenant_id]);
    $booking = $stmt->fetch();
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
