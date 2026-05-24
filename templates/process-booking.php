<?php
require_once __DIR__ . '/../includes/tenant_init.php';

header('Content-Type: application/json');
// Session already started in config.php

$tenant_id = getTenantId();
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get booking data from POST (fallback to session if needed)
$booking_data = [
    'vehicle_id' => $_POST['vehicle_id'] ?? ($_SESSION['booking_data']['vehicle_id'] ?? null),
    'pickup_date' => $_POST['pickup_date'] ?? ($_SESSION['booking_data']['pickup_date'] ?? null),
    'pickup_time' => $_POST['pickup_time'] ?? ($_SESSION['booking_data']['pickup_time'] ?? null),
    'return_date' => $_POST['return_date'] ?? ($_SESSION['booking_data']['return_date'] ?? null),
    'return_time' => $_POST['return_time'] ?? ($_SESSION['booking_data']['return_time'] ?? null),
    'customer_name' => $_POST['customer_name'] ?? ($_SESSION['booking_data']['customer_name'] ?? null),
    'customer_email' => $_POST['customer_email'] ?? ($_SESSION['booking_data']['customer_email'] ?? null),
    'customer_phone' => $_POST['customer_phone'] ?? ($_SESSION['booking_data']['customer_phone'] ?? null),
    'customer_license' => $_POST['customer_license'] ?? ($_SESSION['booking_data']['customer_license'] ?? null),
    'notes' => $_POST['notes'] ?? ($_SESSION['booking_data']['notes'] ?? ''),
    'total_days' => $_POST['total_days'] ?? ($_SESSION['booking_data']['total_days'] ?? 0),
    'price_per_day' => $_POST['price_per_day'] ?? ($_SESSION['booking_data']['price_per_day'] ?? 0),
];

if (!$booking_data['vehicle_id'] || !$booking_data['pickup_date'] || !$booking_data['return_date']) {
    error_log("Missing booking data. POST: " . json_encode($_POST));
    echo json_encode(['success' => false, 'message' => 'No booking data found. Please ensure all required fields are filled.']);
    exit;
}

// Get payment method and intent flag
$booking_id = $_POST['booking_id'] ?? null;
$payment_method = $_POST['payment_method'] ?? 'cash';
$intent_only = ($_POST['intent_only'] ?? '0') === '1';

// Get Stripe and Deposit settings for this tenant
$stmt = $pdo->prepare("SELECT stripe_publishable_key, stripe_secret_key, stripe_test_mode, deposit_amount, deposit_payment_mode, currency FROM tenant_settings WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$stripe_settings = $stmt->fetch();

try {
    // Validate dates
    $pickupDate = new DateTime($booking_data['pickup_date']);
    $returnDate = new DateTime($booking_data['return_date']);
    $now = new DateTime();
    $now->setTime(0, 0, 0);
    
    if ($pickupDate < $now) {
        echo json_encode(['success' => false, 'message' => 'Pickup date cannot be in the past']);
        exit;
    }
    
    if ($returnDate < $pickupDate) {
        echo json_encode(['success' => false, 'message' => 'Return date cannot be before pickup date']);
        exit;
    }
    
    // Verify vehicle exists and is available
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ? AND tenant_id = ? AND availability = 1");
    $stmt->execute([$booking_data['vehicle_id'], $tenant_id]);
    $vehicle = $stmt->fetch();
    
    if (!$vehicle) {
        echo json_encode(['success' => false, 'message' => 'Vehicle not available']);
        exit;
    }

    // Check minimum days
    $min_days = intval($vehicle['min_days'] ?? 1);
    if ($booking_data['total_days'] < $min_days) {
        echo json_encode(['success' => false, 'message' => "This vehicle requires a minimum booking of $min_days " . ($min_days == 1 ? 'day' : 'days') . "."]);
        exit;
    }
    
    // Calculate total price using packages / daily pricing / fallback
    $total_days = $booking_data['total_days'];
    $matched_package_name = null;
    
    // Parse pricing data from vehicle
    $pricing_packages = !empty($vehicle['pricing_packages']) ? json_decode($vehicle['pricing_packages'], true) : [];
    $daily_pricing = !empty($vehicle['daily_pricing']) ? json_decode($vehicle['daily_pricing'], true) : [];
    
    // ISO day-of-week map for daily_pricing keys
    $iso_day_map = [1 => 'mon', 2 => 'tue', 3 => 'wed', 4 => 'thu', 5 => 'fri', 6 => 'sat', 7 => 'sun'];
    
    // 1) Check for matching fixed_price package with day-of-week constraints
    $package_matched = false;
    $total_price = 0;
    
    if (!empty($pricing_packages)) {
        $pickup_iso_day = (int)$pickupDate->format('N'); // 1=Mon, 7=Sun
        $return_iso_day = (int)$returnDate->format('N');
        
        foreach ($pricing_packages as $pkg) {
            if (($pkg['type'] ?? '') !== 'fixed_price') continue;
            if (empty($pkg['fixed_price'])) continue;
            
            $min_days = intval($pkg['min_days'] ?? 0);
            $pkg_start = intval($pkg['start_day'] ?? 0);
            $pkg_end = intval($pkg['end_day'] ?? 0);
            
            // Check if booking meets minimum days
            if ($min_days > 0 && $total_days < $min_days) continue;
            
            // Check day-of-week match (if set)
            if ($pkg_start > 0 && $pkg_start !== $pickup_iso_day) continue;
            if ($pkg_end > 0 && $pkg_end !== $return_iso_day) continue;
            
            // Package matches!
            $total_price = floatval($pkg['fixed_price']);
            $matched_package_name = $pkg['name'] ?? 'Package Deal';
            $package_matched = true;
            break;
        }
    }
    
    // 2) No package matched — calculate per-day pricing
    if (!$package_matched) {
        $current = clone $pickupDate;
        for ($d = 0; $d < $total_days; $d++) {
            $day_key = $iso_day_map[(int)$current->format('N')];
            if (!empty($daily_pricing[$day_key])) {
                $total_price += floatval($daily_pricing[$day_key]);
            } else {
                $total_price += floatval($vehicle['price_per_day']);
            }
            $current->modify('+1 day');
        }
        
        // 3) Apply discount/free day packages
        foreach ($pricing_packages as $pkg) {
            $pkg_type = $pkg['type'] ?? '';
            $min_days = intval($pkg['min_days'] ?? 0);
            if ($min_days > 0 && $total_days < $min_days) continue;
            
            if ($pkg_type === 'discount_target_day' && !empty($pkg['target_day']) && $total_days >= intval($pkg['target_day'])) {
                $discount_amount = floatval($pkg['discount_amount'] ?? 0);
                if (($pkg['discount_type'] ?? 'fixed') === 'percentage') {
                    $total_price -= $total_price * ($discount_amount / 100);
                } else {
                    $total_price -= $discount_amount;
                }
                $matched_package_name = $pkg['name'] ?? 'Discount Deal';
            } elseif ($pkg_type === 'free_target_day' && !empty($pkg['target_day']) && $total_days >= intval($pkg['target_day'])) {
                // Subtract one day's rate for the free day
                $free_day_index = intval($pkg['target_day']) - 1;
                $free_date = clone $pickupDate;
                $free_date->modify("+{$free_day_index} days");
                $free_day_key = $iso_day_map[(int)$free_date->format('N')];
                $free_day_rate = !empty($daily_pricing[$free_day_key]) ? floatval($daily_pricing[$free_day_key]) : floatval($vehicle['price_per_day']);
                $total_price -= $free_day_rate;
                $matched_package_name = $pkg['name'] ?? 'Free Day Deal';
            }
        }
    }
    
    // Ensure price is not negative
    if ($total_price < 0) $total_price = 0;
    
    $security_deposit = floatval($stripe_settings['deposit_amount'] ?? 0);
    $deposit_mode = $stripe_settings['deposit_payment_mode'] ?? 'collection';
    
    // If deposit is 'online', add it to the total booking price for the payment gateway
    if ($deposit_mode === 'online') {
        $total_price += $security_deposit;
    }
    
    // Determine initial status and payment status
    $initial_status = ($payment_method === 'card') ? 'pending' : 'confirmed';
    $payment_status = 'unpaid';
    $stripe_client_secret = null;

    if ($payment_method === 'card' && !empty($stripe_settings['stripe_secret_key'])) {
        try {
            // Include Stripe PHP Library (assuming it's available via composer or manual include)
            // If not available, we'll use a simple CURL request to Stripe API
            $stripe_sk = $stripe_settings['stripe_secret_key'];
            
            $ch = curl_init('https://api.stripe.com/v1/payment_intents');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $stripe_sk . ':');
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'amount' => round($total_price * 100), // Amount in cents
                'currency' => strtolower($stripe_settings['currency'] ?? 'gbp'),
                'payment_method_types' => ['card'],
                'metadata' => [
                    'tenant_id' => $tenant_id,
                    'vehicle_id' => $booking_data['vehicle_id'],
                    'customer_email' => $booking_data['customer_email']
                ],
                'description' => "Booking for " . $vehicle['brand'] . " " . $vehicle['model']
            ]));
            
            $stripe_res = json_decode(curl_exec($ch), true);
            curl_close($ch);
            
            if (isset($stripe_res['error'])) {
                error_log("Stripe error: " . $stripe_res['error']['message']);
                throw new Exception("Stripe initialization failed: " . $stripe_res['error']['message']);
            }
            
            $stripe_client_secret = $stripe_res['client_secret'];
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Stripe error: ' . $e->getMessage()]);
            exit;
        }
    }
    
    // Insert or Update booking
    if ($booking_id) {
        $stmt = $pdo->prepare("
            UPDATE bookings SET 
                customer_name = ?, customer_email = ?, customer_phone = ?,
                customer_license = ?, pickup_date = ?, return_date = ?, 
                pickup_time = ?, return_time = ?, total_days = ?, 
                price_per_day = ?, total_price = ?, security_deposit = ?,
                stripe_payment_id = ?, notes = ?
            WHERE id = ? AND tenant_id = ?
        ");
        $stmt->execute([
            $booking_data['customer_name'],
            $booking_data['customer_email'],
            $booking_data['customer_phone'] ?? null,
            $booking_data['customer_license'] ?? null,
            $booking_data['pickup_date'],
            $booking_data['return_date'],
            $booking_data['pickup_time'] ?? '10:00',
            $booking_data['return_time'] ?? '10:00',
            $booking_data['total_days'],
            $booking_data['price_per_day'],
            $total_price,
            $security_deposit,
            $stripe_res['id'] ?? null,
            $booking_data['notes'] ?? '',
            $booking_id,
            $tenant_id
        ]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO bookings (
                tenant_id, vehicle_id, customer_name, customer_email, customer_phone,
                customer_license, pickup_date, return_date, pickup_time, return_time,
                total_days, price_per_day, total_price, security_deposit, status,
                payment_status, stripe_payment_id, notes, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $tenant_id,
            $booking_data['vehicle_id'],
            $booking_data['customer_name'],
            $booking_data['customer_email'],
            $booking_data['customer_phone'] ?? null,
            $booking_data['customer_license'] ?? null,
            $booking_data['pickup_date'],
            $booking_data['return_date'],
            $booking_data['pickup_time'] ?? '10:00',
            $booking_data['return_time'] ?? '10:00',
            $booking_data['total_days'],
            $booking_data['price_per_day'],
            $total_price,
            $security_deposit,
            'pending',
            $payment_status,
            $stripe_res['id'] ?? null,
            $booking_data['notes'] ?? ''
        ]);
        
        $booking_id = $pdo->lastInsertId();
    }

    // If only intent requested, return now (but with booking_id)
    if ($intent_only) {
        echo json_encode([
            'success' => true, 
            'client_secret' => $stripe_client_secret,
            'booking_id' => $booking_id
        ]);
        exit;
    }
    
    // =============================================
    // CUSTOMER ACCOUNT: Create customer profile so they can log in
    // =============================================
    $customerAccountCreated = false;
    $customerPassword = null;
    try {
        require_once __DIR__ . '/../includes/functions.php';
        
        if (!empty($booking_data['customer_email'])) {
            // Check if customer already has an account
            $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ? AND tenant_id = ?");
            $stmt->execute([$booking_data['customer_email'], $tenant_id]);
            $existingUser = $stmt->fetch();
            
            if (!$existingUser) {
                // Generate a random 8-character password
                $customerPassword = substr(bin2hex(random_bytes(5)), 0, 8);
                $hashedPassword = hashPassword($customerPassword);
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (tenant_id, role, email, password, full_name, phone, created_at)
                    VALUES (?, 'customer', ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $tenant_id,
                    $booking_data['customer_email'],
                    $hashedPassword,
                    $booking_data['customer_name'],
                    $booking_data['customer_phone'] ?? null
                ]);
                $customerAccountCreated = true;
                error_log("Customer account created for: {$booking_data['customer_email']}");
            } elseif (empty($existingUser['password'])) {
                // Existing user has no password (created by verification flow) — set one
                $customerPassword = substr(bin2hex(random_bytes(5)), 0, 8);
                $hashedPassword = hashPassword($customerPassword);
                
                $stmt = $pdo->prepare("UPDATE users SET password = ?, full_name = COALESCE(NULLIF(full_name, ''), ?), phone = COALESCE(NULLIF(phone, ''), ?) WHERE id = ?");
                $stmt->execute([
                    $hashedPassword,
                    $booking_data['customer_name'],
                    $booking_data['customer_phone'] ?? null,
                    $existingUser['id']
                ]);
                $customerAccountCreated = true;
                error_log("Customer account password set for: {$booking_data['customer_email']}");
            }
        }
    } catch (Exception $accountError) {
        error_log("Customer account creation error (non-fatal): " . $accountError->getMessage());
    }
    // =============================================
    
    // =============================================
    // CONTRACT ONBOARDING: Create contract & send welcome email
    // =============================================
    try {
        // Ensure contracts table has the new columns (MySQL-compatible)
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
        
        // Get tenant's default contract template
        $tmplStmt = $pdo->prepare("SELECT * FROM contract_templates WHERE tenant_id = ? AND is_default = 1 LIMIT 1");
        $tmplStmt->execute([$tenant_id]);
        $defaultTemplate = $tmplStmt->fetch();
        
        // Fallback to any published template
        if (!$defaultTemplate) {
            $tmplStmt = $pdo->prepare("SELECT * FROM contract_templates WHERE tenant_id = ? ORDER BY created_at ASC LIMIT 1");
            $tmplStmt->execute([$tenant_id]);
            $defaultTemplate = $tmplStmt->fetch();
        }
        
        $contractContent = $defaultTemplate ? $defaultTemplate['content'] : 'Default rental agreement for booking #' . $booking_id;
        $templateId = $defaultTemplate ? $defaultTemplate['id'] : null;
        
        // Generate unique signing token
        $signingToken = bin2hex(random_bytes(32));
        
        // Create contract record
        $contractStmt = $pdo->prepare("
            INSERT INTO contracts (tenant_id, booking_id, template_id, content, contract_status, signing_token, created_at)
            VALUES (?, ?, ?, ?, 'pending', ?, NOW())
        ");
        $contractStmt->execute([$tenant_id, $booking_id, $templateId, $contractContent, $signingToken]);
        
        // Send welcome & sign email
        require_once __DIR__ . '/../includes/email.php';
        
        // Get tenant info for branding
        $tenantStmt = $pdo->prepare("SELECT * FROM tenants WHERE id = ?");
        $tenantStmt->execute([$tenant_id]);
        $tenantInfo = $tenantStmt->fetch();
        
        if ($tenantInfo && !empty($booking_data['customer_email'])) {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'];
            $contractUrl = $protocol . $host . '/templates/contract-sign.php?booking_id=' . $booking_id . '&token=' . $signingToken;
            
            $res1 = sendContractWelcomeEmail(
                $booking_data['customer_email'],
                $booking_data['customer_name'],
                $contractUrl,
                $tenantInfo
            );
            error_log("Contract welcome email sent to {$booking_data['customer_email']}: " . ($res1 ? 'SUCCESS' : 'FAILED'));
            
            // Send customer account credentials email
            if ($customerAccountCreated && $customerPassword && $tenantInfo) {
                $res2 = sendCustomerAccountEmail(
                    $booking_data['customer_email'],
                    $booking_data['customer_name'],
                    $customerPassword,
                    $tenantInfo,
                    $booking_id
                );
                error_log("Account credentials email sent to {$booking_data['customer_email']}: " . ($res2 ? 'SUCCESS' : 'FAILED'));
            } else if (!$customerAccountCreated && $tenantInfo) {
                // If account already existed, send a simple login reminder
                // You could implement sendLoginReminderEmail here
                error_log("Customer already has account, skipping credentials email.");
            }
        }
        
        error_log("Contract created for booking #{$booking_id} with token: {$signingToken}");
    } catch (Exception $contractError) {
        // Don't fail the booking if contract creation fails
        error_log("Contract creation error (non-fatal): " . $contractError->getMessage());
    }
    // =============================================
    
    // Clear session data
    unset($_SESSION['booking_data']);
    unset($_SESSION['checkout_step']);
    unset($_SESSION['didit_session_id']);
    
    echo json_encode([
        'success' => true,
        'booking_id' => $booking_id,
        'message' => 'Booking initialized',
        'account_created' => $customerAccountCreated,
        'client_secret' => $stripe_client_secret,
        'status' => $initial_status
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
