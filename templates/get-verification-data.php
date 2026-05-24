<?php
require_once __DIR__ . '/../includes/tenant_init.php';
header('Content-Type: application/json');

// Return verification data stored in session by check-verification-status.php
$response = ['success' => true];

$response['session_id'] = $_SESSION['didit_session_id'] ?? null;
$response['dob'] = $_SESSION['customer_dob'] ?? null;
$response['license_number'] = $_SESSION['customer_license_id'] ?? null;
$response['address'] = $_SESSION['customer_address'] ?? null;

// Try to get full name and dates from the customer_verifications table
$didit_session_id = $_SESSION['didit_session_id'] ?? null;

if ($didit_session_id) {
    try {
        $pdo = getDB();
        $tenant_id = getTenantId();
        
        $stmt = $pdo->prepare("
            SELECT first_name, last_name, license_number, dob, address, date_of_issue, expiration_date, verified_at 
            FROM customer_verifications 
            WHERE tenant_id = ? AND session_id = ? 
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$tenant_id, $didit_session_id]);
        $row = $stmt->fetch();
        
        if ($row) {
            $response['full_name'] = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            $response['dob'] = $row['dob'] ?? $response['dob'];
            $response['license_number'] = $row['license_number'] ?? $response['license_number'];
            $response['address'] = $row['address'] ?? $response['address'];
            $response['date_of_issue'] = $row['date_of_issue'] ?? null;
            $response['expiration_date'] = $row['expiration_date'] ?? null;
            $response['verified_at'] = $row['verified_at'] ?? null;
        }
    } catch (Exception $e) {
        error_log("get-verification-data.php error: " . $e->getMessage());
    }
}

echo json_encode($response);
?>
