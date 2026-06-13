<?php
require_once __DIR__ . '/../includes/tenant_init.php';
header('Content-Type: application/json');

// Session already started in tenant_init/config
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['signature'])) {
    echo json_encode(['success' => false, 'message' => 'Signature is required']);
    exit;
}

$signature = trim($data['signature']);
if (strlen($signature) < 2) {
    echo json_encode(['success' => false, 'message' => 'Please type your full name as signature']);
    exit;
}

// Save signature and signed timestamp to session for use in process-booking.php
$_SESSION['contract_signature'] = $signature;
$_SESSION['contract_signed_at'] = date('Y-m-d H:i:s');

echo json_encode(['success' => true]);
?>
