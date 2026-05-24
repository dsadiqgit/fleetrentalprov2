<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/email.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = getDB();
$tenant_id = $_SESSION['tenant_id'];

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$booking_id = $data['booking_id'] ?? null;
$template_id = $data['template_id'] ?? null;

if (!$booking_id || !$template_id) {
    echo json_encode(['success' => false, 'message' => 'Missing required information']);
    exit;
}

try {
    // 1. Verify booking ownership
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$booking_id, $tenant_id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }

    // 2. Fetch template
    $stmt = $pdo->prepare("SELECT * FROM contract_templates WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$template_id, $tenant_id]);
    $template = $stmt->fetch();

    if (!$template) {
        echo json_encode(['success' => false, 'message' => 'Template not found']);
        exit;
    }

    // 3. Remove existing pending contracts for this booking
    $stmt = $pdo->prepare("DELETE FROM contracts WHERE booking_id = ? AND tenant_id = ? AND contract_status = 'pending'");
    $stmt->execute([$booking_id, $tenant_id]);

    // 4. Generate unique signing token
    $signingToken = bin2hex(random_bytes(32));

    // 5. Create new contract
    $stmt = $pdo->prepare("
        INSERT INTO contracts (tenant_id, booking_id, template_id, content, contract_status, signing_token, created_at)
        VALUES (?, ?, ?, ?, 'pending', ?, NOW())
    ");
    $stmt->execute([$tenant_id, $booking_id, $template_id, $template['content'], $signingToken]);
    $contract_id = $pdo->lastInsertId();

    // 6. Send email to customer
    $stmt = $pdo->prepare("SELECT * FROM tenants WHERE id = ?");
    $stmt->execute([$tenant_id]);
    $tenantInfo = $stmt->fetch();

    if ($tenantInfo && !empty($booking['customer_email'])) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $contractUrl = $protocol . $host . '/templates/contract-sign.php?booking_id=' . $booking_id . '&token=' . $signingToken;
        
        $emailSent = sendContractWelcomeEmail(
            $booking['customer_email'],
            $booking['customer_name'],
            $contractUrl,
            $tenantInfo
        );
        
        if ($emailSent) {
            echo json_encode(['success' => true, 'message' => 'Contract generated and sent successfully']);
        } else {
            echo json_encode(['success' => true, 'message' => 'Contract generated, but failed to send email. You can copy the link manually.']);
        }
    } else {
        echo json_encode(['success' => true, 'message' => 'Contract generated successfully.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
