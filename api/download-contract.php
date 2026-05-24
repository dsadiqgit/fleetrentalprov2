<?php
/**
 * Download Signed Contract PDF
 * Allows authenticated customers to download their signed contract PDF
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('Unauthorized');
}

$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

if (!$booking_id) {
    http_response_code(400);
    die('Missing booking_id');
}

$pdo = getDB();
$tenant_id = $_SESSION['tenant_id'];
$user_email = $_SESSION['user_email'];

// Get contract with ownership validation
$stmt = $pdo->prepare("
    SELECT c.signed_pdf_path, b.customer_email
    FROM contracts c
    JOIN bookings b ON c.booking_id = b.id
    WHERE c.booking_id = ? AND c.tenant_id = ? AND c.contract_status = 'signed'
");
$stmt->execute([$booking_id, $tenant_id]);
$contract = $stmt->fetch();

if (!$contract) {
    http_response_code(404);
    die('Contract not found');
}

// Verify the logged-in user owns this booking (customer can only download their own)
if ($_SESSION['role'] === 'customer' && $contract['customer_email'] !== $user_email) {
    http_response_code(403);
    die('Access denied');
}

// Instead of delivering the legacy TCPDF output which drops styling, we redirect 
// directly to our new high-fidelity HTML contract engine and flag it to auto-print.
$redirectUrl = '/dashboard/preview-contract.php?booking_id=' . $booking_id . '&autoprint=true';

if (ob_get_length()) ob_clean();
header('Location: ' . $redirectUrl);
exit;
