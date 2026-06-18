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
    // When starting a trip, require mileage and create pickup condition report
    if ($data['status'] === 'active') {
        $mileage = isset($data['mileage']) ? intval($data['mileage']) : 0;
        if ($mileage <= 0) {
            echo json_encode(['success' => false, 'message' => 'Pickup mileage is required to start the trip.']);
            exit;
        }

        // Create or update pickup condition report with mileage
        $stmt = $pdo->prepare("
            INSERT INTO booking_condition_reports (tenant_id, booking_id, report_type, mileage)
            VALUES (?, ?, 'pickup', ?)
            ON DUPLICATE KEY UPDATE mileage = VALUES(mileage), updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$_SESSION['tenant_id'], $data['booking_id'], $mileage]);
    }

    // When completing a trip, require mileage and create return condition report
    if ($data['status'] === 'completed') {
        $mileage = isset($data['mileage']) ? intval($data['mileage']) : 0;
        if ($mileage <= 0) {
            echo json_encode(['success' => false, 'message' => 'Return mileage is required to complete the trip.']);
            exit;
        }

        // Create or update return condition report with mileage
        $stmt = $pdo->prepare("
            INSERT INTO booking_condition_reports (tenant_id, booking_id, report_type, mileage)
            VALUES (?, ?, 'return', ?)
            ON DUPLICATE KEY UPDATE mileage = VALUES(mileage), updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$_SESSION['tenant_id'], $data['booking_id'], $mileage]);
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
