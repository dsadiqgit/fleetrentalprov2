<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['booking_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$allowed_statuses = ['pending', 'confirmed', 'active', 'completed', 'cancelled'];
if (!in_array($data['status'], $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

$pdo = getDB();

try {
    // Require pickup condition report with mileage before starting trip
    if ($data['status'] === 'active') {
        $stmt = $pdo->prepare("SELECT id FROM booking_condition_reports WHERE booking_id = ? AND tenant_id = ? AND report_type = 'pickup' AND mileage IS NOT NULL");
        $stmt->execute([$data['booking_id'], $_SESSION['tenant_id']]);
        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Please complete the pickup condition report with mileage before starting the trip.']);
            exit;
        }
    }

    // Update booking status
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET status = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE id = ? AND tenant_id = ?
    ");
    $stmt->execute([$data['status'], $data['booking_id'], $_SESSION['tenant_id']]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Booking status updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Booking not found or no changes made'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
