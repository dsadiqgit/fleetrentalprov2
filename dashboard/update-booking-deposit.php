<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$booking_id = intval($input['booking_id'] ?? 0);
$amount = floatval($input['amount'] ?? 0);
$status = $input['status'] ?? 'unpaid';
$method = $input['method'] ?? null;

if (!$booking_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
    exit;
}

$pdo = getDB();

try {
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET security_deposit = ?, 
            security_deposit_status = ?, 
            security_deposit_method = ? 
        WHERE id = ? AND tenant_id = ?
    ");
    $stmt->execute([$amount, $status, $method, $booking_id, $_SESSION['tenant_id']]);

    echo json_encode(['success' => true, 'message' => 'Security deposit updated successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
