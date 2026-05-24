<?php
require_once __DIR__ . '/../includes/tenant_init.php';
header('Content-Type: application/json');

// Session already started in config.php
$didit_api_key = 'lqcFsMLeyMP1c9_7mCT1zOZU-xAd1l-0qbQnasmjpEM';
$didit_workflow_id = '4442c712-03c2-475a-9f3f-bf645d786c85';

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
curl_close($ch);

if ($http_code === 200 || $http_code === 201) {
    $data = json_decode($response, true);
    $_SESSION['didit_session_id'] = $data['session_id'] ?? null;
    
    // Also store mapping if we have email (though verify-booking might not have it yet)
    // In vehicle-booking.php email is entered in step 2.
    
    echo $response;
} else {
    http_response_code($http_code);
    echo $response;
}
