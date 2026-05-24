<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['booking_ids']) || !is_array($data['booking_ids'])) {
    echo json_encode(['success' => false, 'message' => 'No bookings selected']);
    exit;
}

$booking_ids = $data['booking_ids'];
if (empty($booking_ids)) {
    echo json_encode(['success' => false, 'message' => 'No bookings selected']);
    exit;
}

$pdo = getDB();

try {
    $placeholders = implode(',', array_fill(0, count($booking_ids), '?'));
    $stmt = $pdo->prepare("UPDATE bookings SET is_deleted = 0 WHERE id IN ($placeholders) AND tenant_id = ?");
    $params = array_merge($booking_ids, [$_SESSION['tenant_id']]);
    $stmt->execute($params);
    
    $count = $stmt->rowCount();
    if ($count > 0) {
        echo json_encode([
            'success' => true,
            'message' => $count . ' booking(s) restored successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No bookings found or unauthorized'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
