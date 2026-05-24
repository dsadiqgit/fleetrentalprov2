<?php
require_once __DIR__ . '/../includes/tenant_init.php';

header('Content-Type: application/json');
// Session already started in config.php

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Save booking data to session
$_SESSION['booking_data'] = $data;
$_SESSION['customer_email'] = $data['customer_email'] ?? null;

// Calculate total days and price
$pickupDate = new DateTime($data['pickup_date']);
$returnDate = new DateTime($data['return_date']);
$days = $pickupDate->diff($returnDate)->days;

$_SESSION['booking_data']['total_days'] = $days;

if (isset($data['skip_verification']) && $data['skip_verification'] === true) {
    echo json_encode([
        'success' => true,
        'skipped' => true,
        'total_days' => $days
    ]);
    exit;
}

// Didit API configuration
$didit_api_key = 'lqcFsMLeyMP1c9_7mCT1zOZU-xAd1l-0qbQnasmjpEM'; // Get from Didit dashboard
$didit_workflow_id = '4442c712-03c2-475a-9f3f-bf645d786c85';

// Create Didit verification session via API v1
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$callback_url = $protocol . $host . '/templates/didit-callback.php';

$didit_payload = [
    'workflow_id' => $didit_workflow_id,
    'vendor_data' => 'booking_' . uniqid(),
    'callback' => $callback_url
];

$ch = curl_init("https://verification.didit.me/v1/session/");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($didit_payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
    'x-api-key: ' . $didit_api_key
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// Log the full request and response for debugging
error_log("Didit API Request: " . json_encode($didit_payload));
error_log("Didit API Response Code: " . $http_code);
error_log("Didit API Response: " . $response);

if ($curl_error) {
    error_log("Didit cURL Error: " . $curl_error);
    echo json_encode([
        'success' => false,
        'message' => 'Connection error: ' . $curl_error
    ]);
    exit;
}

if ($http_code !== 200 && $http_code !== 201) {
    $error_data = json_decode($response, true);
    
    // Try to extract error message from various possible formats
    $error_message = 'Unknown error';
    if (isset($error_data['message'])) {
        $error_message = $error_data['message'];
    } elseif (isset($error_data['error'])) {
        if (is_string($error_data['error'])) {
            $error_message = $error_data['error'];
        } elseif (is_array($error_data['error']) && isset($error_data['error']['message'])) {
            $error_message = $error_data['error']['message'];
        }
    } elseif (isset($error_data['detail'])) {
        $error_message = is_string($error_data['detail']) ? $error_data['detail'] : json_encode($error_data['detail']);
    } elseif (isset($error_data['workflow_id'])) {
        $error_message = 'Workflow ID Error: ' . (is_string($error_data['workflow_id']) ? $error_data['workflow_id'] : json_encode($error_data['workflow_id']));
    } elseif (is_array($error_data) && !empty($error_data)) {
        // Handle case where keys are field names with error messages
        $error_messages = [];
        foreach ($error_data as $field => $msg) {
            if (is_string($msg)) {
                $error_messages[] = "$field: $msg";
            }
        }
        if (!empty($error_messages)) {
            $error_message = implode(', ', $error_messages);
        }
    }
    
    error_log("Didit API Error (HTTP $http_code): " . $response);
    echo json_encode([
        'success' => false,
        'message' => 'Didit API Error: ' . $error_message,
        'error_details' => $error_data,
        'http_code' => $http_code,
        'raw_response' => $response
    ]);
    exit;
}

$didit_response = json_decode($response, true);
$session_id = $didit_response['session_id'] ?? null;
$verification_url = $didit_response['url'] ?? null;

if (!$session_id || !$verification_url) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid response from Didit API: ' . $response
    ]);
    exit;
}

// Save Didit session ID
$_SESSION['didit_session_id'] = $session_id;

// Store session mapping in database so webhook can find it later
// (webhook may not have access to PHP session)
try {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDB();
    $tenant_id = getTenantId();
    $customer_email = $_SESSION['customer_email'] ?? null;
    
    if ($customer_email && $tenant_id) {
        $stmt = $pdo->prepare("
            INSERT INTO customer_verifications 
            (tenant_id, customer_email, session_id, verification_status, verified_at)
            VALUES (?, ?, ?, 'pending', NULL)
            ON DUPLICATE KEY UPDATE 
            session_id = VALUES(session_id),
            verification_status = 'pending'
        ");
        $stmt->execute([$tenant_id, $customer_email, $session_id]);
        error_log("Pre-stored session mapping: {$session_id} -> {$customer_email}");
    }
} catch (Exception $e) {
    error_log("Error pre-storing session mapping: " . $e->getMessage());
}

echo json_encode([
    'success' => true,
    'session_id' => $session_id,
    'verification_url' => $verification_url,
    'total_days' => $days
]);
?>
