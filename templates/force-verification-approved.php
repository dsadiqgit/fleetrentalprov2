<?php
require_once __DIR__ . '/../includes/tenant_init.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// This endpoint is called when the frontend iframe detects a successful completion
// It serves as a secondary fallback if the webhook and API polling fail

$didit_session_id = $_SESSION['didit_session_id'] ?? null;
$customer_email = $_SESSION['customer_email'] ?? null;
$booking_data = $_SESSION['booking_data'] ?? [];

error_log("Force verification approved called for session: {$didit_session_id}, email: {$customer_email}");

if ($didit_session_id && $customer_email) {
    try {
        $pdo = getDB();
        $tenant_id = getTenantId();
        
        // 1. Update session (treat both approved and in_review as success to continue to payment)
        $status = isset($_POST['status']) && $_POST['status'] === 'in_review' ? 'in_review' : 'approved';
        $_SESSION['verification_status'] = $status;
        
        // 2. Fetch data from Didit API to ensure we have the real data
        $didit_api_key = 'lqcFsMLeyMP1c9_7mCT1zOZU-xAd1l-0qbQnasmjpEM';
        $ch = curl_init("https://verification.didit.me/v1/session/{$didit_session_id}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'x-api-key: ' . $didit_api_key
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $first_name = null;
        $last_name = null;
        $date_of_issue = null;
        $expiration_date = null;
        $api_status = $status; // Default to the passed in status
        
        if ($http_code === 200) {
            $api_result = json_decode($response, true);
            $extracted_data = $api_result['document'] ?? $api_result['extracted_data'] ?? [];
            
            $first_name = $extracted_data['first_name'] ?? $extracted_data['given_names'] ?? null;
            $last_name = $extracted_data['last_name'] ?? $extracted_data['surname'] ?? null;
            $date_of_issue = $extracted_data['date_of_issue'] ?? $extracted_data['issue_date'] ?? null;
            $expiration_date = $extracted_data['expiration_date'] ?? $extracted_data['expiry_date'] ?? null;
            
            if (isset($api_result['status'])) {
                $raw_api_status = strtolower($api_result['status']);
                if ($raw_api_status === 'approved' || $raw_api_status === 'completed') {
                    $api_status = 'approved';
                } else if ($raw_api_status === 'declined' || $raw_api_status === 'rejected') {
                    $api_status = 'declined';
                } else if ($raw_api_status === 'in_review') {
                    $api_status = 'in_review';
                }
            }
        }
        
        // 3. Store in customer_verifications table
        $stmt = $pdo->prepare("
            INSERT INTO customer_verifications 
            (tenant_id, customer_email, session_id, verification_status, first_name, last_name, date_of_issue, expiration_date, verified_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            verification_status = ?,
            first_name = COALESCE(VALUES(first_name), first_name),
            last_name = COALESCE(VALUES(last_name), last_name),
            date_of_issue = COALESCE(VALUES(date_of_issue), date_of_issue),
            expiration_date = COALESCE(VALUES(expiration_date), expiration_date),
            verified_at = NOW()
        ");
        $stmt->execute([
            $tenant_id, 
            $customer_email, 
            $didit_session_id,
            $api_status,
            $first_name,
            $last_name,
            $date_of_issue,
            $expiration_date,
            $api_status // for ON DUPLICATE KEY UPDATE
        ]);
        
        // 4. Create customer in users table if needed
        if (!empty($booking_data)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND tenant_id = ?");
            $stmt->execute([$customer_email, $tenant_id]);
            $existing_customer = $stmt->fetch();
            
            if (!$existing_customer) {
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
                error_log("Force API: Customer created in users table for {$customer_email}");
            }
        }
        
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        error_log("Force verification error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Missing session data']);
}
?>
