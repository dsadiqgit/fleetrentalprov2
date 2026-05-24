<?php
require_once __DIR__ . '/../includes/tenant_init.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Get session data - try URL parameter first, then PHP session
$didit_session_id = $_GET['session_id'] ?? $_SESSION['didit_session_id'] ?? null;
$customer_email = $_GET['email'] ?? $_SESSION['customer_email'] ?? null;

// Default status
$status = 'pending';
$verified_at = null;

// If only email is provided (from contract signing page), check DB directly
if (!$didit_session_id && $customer_email) {
    try {
        $pdo = getDB();
        $tenant_id = getTenantId();
        $stmt = $pdo->prepare("SELECT verification_status, verified_at FROM customer_verifications WHERE tenant_id = ? AND customer_email = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$tenant_id, $customer_email]);
        $result = $stmt->fetch();
        if ($result) {
            $status = $result['verification_status'];
            $verified_at = $result['verified_at'];
        }
        echo json_encode([
            'status' => $status,
            'verified' => ($status === 'approved'),
            'verified_at' => $verified_at
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['status' => 'approved', 'verified' => true]);
        exit;
    }
}


error_log("check-verification-status.php - Session ID: " . ($didit_session_id ?? 'null'));

// Since webhook isn't working, ALWAYS check Didit API directly
if ($didit_session_id) {
    try {
        error_log("Checking Didit API directly for session: {$didit_session_id}");
        
        $didit_api_key = 'lqcFsMLeyMP1c9_7mCT1zOZU-xAd1l-0qbQnasmjpEM';
        
        // Try different API endpoints
        $endpoints = [
            "https://verification.didit.me/v1/session/{$didit_session_id}",
            "https://verification.didit.me/v1/sessions/{$didit_session_id}",
            "https://api.didit.me/v1/sessions/{$didit_session_id}",
            "https://verification.didit.me/api/v1/sessions/{$didit_session_id}"
        ];
        
        $api_result = null;
        $http_code = 0;
        
        foreach ($endpoints as $endpoint) {
            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Authorization: Bearer ' . $didit_api_key,
                'x-api-key: ' . $didit_api_key
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            error_log("Tried endpoint {$endpoint}: HTTP {$http_code}");
            
            if ($http_code === 200) {
                $api_result = json_decode($response, true);
                error_log("Success! Didit API response: " . $response);
                break;
            }
        }
        
        if ($http_code === 200 && $api_result) {
            $api_status = strtolower($api_result['status'] ?? '');
            
            // Map Didit status to our status
            if ($api_status === 'approved' || $api_status === 'completed') {
                $status = 'approved';
                $_SESSION['verification_status'] = 'approved';
                
                // Extract verification data
                $extracted_data = $api_result['document'] ?? $api_result['extracted_data'] ?? $api_result['data'] ?? $api_result['kyc_data'] ?? [];
                
                // If nested kyc_data
                if (empty($extracted_data) && isset($api_result['kyc'])) {
                    $extracted_data = $api_result['kyc'];
                }
                
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
                
                $date_of_issue = $extracted_data['date_of_issue'] ?? $extracted_data['issue_date'] ?? null;
                $expiration_date = $extracted_data['expiration_date'] ?? $extracted_data['expiry_date'] ?? null;
                
                // Save DOB to session for age check in checkout
                if ($dob) $_SESSION['customer_dob'] = $dob;
                if ($license_number) $_SESSION['customer_license_id'] = $license_number;
                if ($address) $_SESSION['customer_address'] = $address;
                
                // Store in database
                $pdo = getDB();
                $tenant_id = getTenantId();
                
                if ($customer_email && $tenant_id) {
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
                        $tenant_id, $customer_email, $didit_session_id,
                        $first_name, $last_name, $license_number, $dob, $address,
                        $date_of_issue, $expiration_date
                    ]);
                    
                    $verified_at = date('Y-m-d H:i:s');
                    
                    // Create customer in users table
                    $booking_data = $_SESSION['booking_data'] ?? [];
                    if (!empty($booking_data)) {
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND tenant_id = ?");
                        $stmt->execute([$customer_email, $tenant_id]);
                        
                        if (!$stmt->fetch()) {
                            $stmt = $pdo->prepare("
                                INSERT INTO users (tenant_id, role, email, full_name, phone, password, created_at)
                                VALUES (?, 'customer', ?, ?, ?, '', NOW())
                            ");
                            $stmt->execute([
                                $tenant_id, $customer_email,
                                $booking_data['customer_name'] ?? ($first_name . ' ' . $last_name),
                                $booking_data['customer_phone'] ?? null
                            ]);
                            error_log("Customer created: {$customer_email}");
                        }
                    }
                }
            } else if ($api_status === 'in_review' || $api_status === 'in review') {
                $status = 'in_review';
                $_SESSION['verification_status'] = 'in_review';
            } else if ($api_status === 'declined' || $api_status === 'rejected') {
                $status = 'declined';
                $_SESSION['verification_status'] = 'declined';
            }
        }
    } catch (Exception $e) {
        error_log("Error checking Didit API: " . $e->getMessage());
    }
}

// Log for debugging
error_log("Check verification status - Status: {$status}, Session ID: {$didit_session_id}, Email: {$customer_email}");

// Only return safe, non-PII data
$response = [
    'status' => $status,
    'session_id' => $didit_session_id,
    'age_eligible' => true // Default to true
];

// If we have DOB and vehicle_id, check age limit
$dob = $_SESSION['customer_dob'] ?? null;
$vehicle_id = $_SESSION['booking_data']['vehicle_id'] ?? $_GET['vehicle_id'] ?? null;

if ($dob && $vehicle_id) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT min_age FROM vehicles WHERE id = ?");
        $stmt->execute([$vehicle_id]);
        $min_age = $stmt->fetchColumn();
        
        if ($min_age) {
            $birthDate = new DateTime($dob);
            $today = new DateTime();
            $age = $today->diff($birthDate)->y;
            
            $response['age_eligible'] = ($age >= $min_age);
            $response['age'] = $age;
            $response['age_limit'] = $min_age;
            
            if (!$response['age_eligible']) {
                error_log("Age check failed: Customer age {$age}, required {$min_age}");
            }
        }
    } catch (Exception $e) {
        error_log("Age check error: " . $e->getMessage());
    }
}

// Add timestamp if verification is complete
if ($verified_at) {
    $response['verified_at'] = date('Y-m-d H:i:s', strtotime($verified_at));
}

echo json_encode($response);
?>
