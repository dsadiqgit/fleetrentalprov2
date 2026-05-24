<?php
require_once __DIR__ . '/../includes/tenant_init.php';

header('Content-Type: application/json');

// Log that webhook was called
error_log("=== DIDIT WEBHOOK CALLED === " . date('Y-m-d H:i:s'));

// Get the webhook payload
$payload = file_get_contents('php://input');
error_log("Webhook raw payload: " . $payload);

$data = json_decode($payload, true);

// Get tenant info for logging
$tenant_id = getTenantId();
$tenant = getTenant();

// Log webhook for debugging (optional)
error_log("Didit Webhook for tenant {$tenant_id} ({$tenant['subdomain']}): " . $payload);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

// Verify webhook signature for security
// Get the webhook secret from Didit dashboard (API & Webhooks section)
$webhook_secret = get_env_var('DIDIT_WEBHOOK_SECRET', '');

$signature = $_SERVER['HTTP_X_DIDIT_SIGNATURE'] ?? '';

// Verify the signature if webhook secret is configured
if (!empty($webhook_secret)) {
    $expected_signature = hash_hmac('sha256', $payload, $webhook_secret);
    
    if (!hash_equals($expected_signature, $signature)) {
        error_log("Didit Webhook: Invalid signature for tenant {$tenant_id}");
        http_response_code(401);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }
} else {
    error_log('Didit Webhook: DIDIT_WEBHOOK_SECRET not configured; signature verification skipped.');
}

// Handle verification events
$event_type = $data['event_type'] ?? $data['type'] ?? '';
$session_id = $data['session_id'] ?? $data['id'] ?? '';
$status = $data['status'] ?? '';

// Get database connection
require_once __DIR__ . '/../config/database.php';
$pdo = getDB();

// Session already started in config.php (if available)

switch ($event_type) {
    case 'verification.completed':
    case 'verification.approved':
        // Verification successful - extract data from Didit response
        // First try to get from PHP session
        $customer_email = $_SESSION['customer_email'] ?? null;
        $booking_data = $_SESSION['booking_data'] ?? [];
        
        // If not in session, try to find from database by session_id
        if (!$customer_email && $session_id) {
            $stmt = $pdo->prepare("SELECT customer_email FROM customer_verifications WHERE session_id = ? LIMIT 1");
            $stmt->execute([$session_id]);
            $existing = $stmt->fetch();
            if ($existing) {
                $customer_email = $existing['customer_email'];
            }
        }
        
        error_log("Verification approved - Session data: " . json_encode([
            'customer_email' => $customer_email,
            'has_booking_data' => !empty($booking_data),
            'session_id' => $session_id,
            'php_session_id' => session_id()
        ]));
        
        // Extract verification data from the result
        $result = $data['result'] ?? $data['document'] ?? $data['data'] ?? $data['kyc_data'] ?? $data['kyc'] ?? [];
        $extracted_data = $result['extracted_data'] ?? $result['document'] ?? $result['data'] ?? $result['kyc_data'] ?? $result['kyc'] ?? $result;
        
        // Final fallback to raw data if still empty
        if (empty($extracted_data) && is_array($data)) {
            $extracted_data = $data;
        }
        
        // Get name fields
        $first_name = $extracted_data['first_name'] ?? $extracted_data['given_names'] ?? null;
        $last_name = $extracted_data['last_name'] ?? $extracted_data['surname'] ?? null;
        
        // Get identity/license and DOB
        $license_number = $extracted_data['document_number'] ?? $extracted_data['doc_number'] ?? $extracted_data['id_number'] ?? null;
        $dob = $extracted_data['date_of_birth'] ?? $extracted_data['dob'] ?? $extracted_data['birth_date'] ?? null;
        
        // Format address if available
        $address = $extracted_data['address'] ?? null;
        if (is_array($address)) {
            $addrParts = [];
            if (!empty($address['street'])) $addrParts[] = $address['street'];
            if (!empty($address['city'])) $addrParts[] = $address['city'];
            if (!empty($address['state'])) $addrParts[] = $address['state'];
            if (!empty($address['postcode'])) $addrParts[] = $address['postcode'];
            if (!empty($address['country'])) $addrParts[] = $address['country'];
            $address = implode(', ', $addrParts);
        } else if (!$address && isset($extracted_data['residence'])) {
            $address = $extracted_data['residence'];
        }
        
        // Get document dates
        $date_of_issue = $extracted_data['date_of_issue'] ?? $extracted_data['issue_date'] ?? null;
        $expiration_date = $extracted_data['expiration_date'] ?? $extracted_data['expiry_date'] ?? null;
        
        error_log("Extracted verification data: " . json_encode([
            'first_name' => $first_name,
            'last_name' => $last_name,
            'license_number' => $license_number,
            'dob' => $dob,
            'date_of_issue' => $date_of_issue,
            'expiration_date' => $expiration_date
        ]));
        
        // Update session
        if (isset($_SESSION['didit_session_id']) && $_SESSION['didit_session_id'] === $session_id) {
            $_SESSION['verification_status'] = 'approved';
            error_log("Session verification_status set to 'approved'");
        }
        
        // Store in database if we have customer email
        if ($customer_email) {
            try {
                // Store verification data
                $stmt = $pdo->prepare("
                    INSERT INTO customer_verifications 
                    (tenant_id, customer_email, session_id, verification_status, first_name, last_name, license_number, dob, address, date_of_issue, expiration_date, verified_at)
                    VALUES (?, ?, ?, 'approved', ?, ?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                    verification_status = 'approved',
                    first_name = VALUES(first_name),
                    last_name = VALUES(last_name),
                    license_number = VALUES(license_number),
                    dob = VALUES(dob),
                    address = VALUES(address),
                    date_of_issue = VALUES(date_of_issue),
                    expiration_date = VALUES(expiration_date),
                    verified_at = NOW()
                ");
                $stmt->execute([
                    $tenant_id,
                    $customer_email,
                    $session_id,
                    $first_name,
                    $last_name,
                    $license_number,
                    $dob,
                    $address,
                    $date_of_issue,
                    $expiration_date
                ]);
                
                error_log("Verification data stored for customer: {$customer_email}");
                
                // Create or update customer in users table if we have booking data
                if (!empty($booking_data)) {
                    try {
                        // Check if customer already exists
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND tenant_id = ?");
                        $stmt->execute([$customer_email, $tenant_id]);
                        $existing_customer = $stmt->fetch();
                        
                        if (!$existing_customer) {
                            // Create new customer
                            $stmt = $pdo->prepare("
                                INSERT INTO users (tenant_id, role, email, full_name, phone, password, created_at)
                                VALUES (?, 'customer', ?, ?, ?, '', NOW())
                            ");
                            $stmt->execute([
                                $tenant_id,
                                $customer_email,
                                $booking_data['customer_name'] ?? ($first_name . ' ' . $last_name),
                                $booking_data['customer_phone'] ?? null
                            ]);
                            error_log("Customer created in users table: {$customer_email}");
                        } else {
                            error_log("Customer already exists: {$customer_email}");
                        }
                    } catch (Exception $e) {
                        error_log("Error creating customer: " . $e->getMessage());
                    }
                }
            } catch (Exception $e) {
                error_log("Error storing verification data: " . $e->getMessage());
            }
        } else {
            error_log("No customer_email in session - cannot store verification data");
        }
        break;
        
    case 'verification.declined':
        // Verification failed
        $customer_email = $_SESSION['customer_email'] ?? null;
        
        if (isset($_SESSION['didit_session_id']) && $_SESSION['didit_session_id'] === $session_id) {
            $_SESSION['verification_status'] = 'declined';
        }
        
        // Store declined status in database
        if ($customer_email) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO customer_verifications 
                    (tenant_id, customer_email, session_id, verification_status, verified_at)
                    VALUES (?, ?, ?, 'declined', NOW())
                    ON DUPLICATE KEY UPDATE
                    verification_status = 'declined',
                    verified_at = NOW()
                ");
                $stmt->execute([$tenant_id, $customer_email, $session_id]);
            } catch (Exception $e) {
                error_log("Error storing declined verification: " . $e->getMessage());
            }
        }
        break;
        
    case 'verification.in_review':
        // Manual review required
        $customer_email = $_SESSION['customer_email'] ?? null;
        
        if (isset($_SESSION['didit_session_id']) && $_SESSION['didit_session_id'] === $session_id) {
            $_SESSION['verification_status'] = 'in_review';
        }
        
        // Store in_review status in database
        if ($customer_email) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO customer_verifications 
                    (tenant_id, customer_email, session_id, verification_status)
                    VALUES (?, ?, ?, 'in_review')
                    ON DUPLICATE KEY UPDATE
                    verification_status = 'in_review'
                ");
                $stmt->execute([$tenant_id, $customer_email, $session_id]);
            } catch (Exception $e) {
                error_log("Error storing in_review verification: " . $e->getMessage());
            }
        }
        break;
}

// Return success response
http_response_code(200);
echo json_encode(['success' => true, 'received' => true]);
?>
