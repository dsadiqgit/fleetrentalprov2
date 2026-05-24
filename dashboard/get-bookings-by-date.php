<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$date = $_GET['date'] ?? null;
$tenant_id = $_SESSION['tenant_id'];

if (!$date) {
    echo json_encode(['success' => false, 'message' => 'Date is required']);
    exit;
}

$pdo = getDB();

try {
    // We need to find bookings that overlap with this date
    $stmt = $pdo->prepare("
        SELECT b.*, v.brand, v.model, v.category
        FROM bookings b
        JOIN vehicles v ON b.vehicle_id = v.id
        WHERE b.tenant_id = ? 
        AND ? BETWEEN b.pickup_date AND b.return_date
        AND b.status != 'cancelled'
        ORDER BY b.pickup_time ASC
    ");
    $stmt->execute([$tenant_id, $date]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get manually blocked out vehicle dates
    try {
        $stmt_blocks = $pdo->prepare("
            SELECT vbd.*, v.brand, v.model, v.category 
            FROM vehicle_blocked_dates vbd 
            JOIN vehicles v ON vbd.vehicle_id = v.id 
            WHERE vbd.tenant_id = ? AND vbd.blocked_date = ?
        ");
        $stmt_blocks->execute([$tenant_id, $date]);
        $blocked_dates = $stmt_blocks->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($blocked_dates as $bd) {
            $bookings[] = [
                'id' => 'B' . $bd['id'],
                'customer_name' => 'Blocked by ' . $bd['blocked_by'],
                'status' => 'cancelled', // Renders gracefully as grey
                'brand' => $bd['brand'],
                'model' => $bd['model'],
                'pickup_date' => $bd['blocked_date'],
                'return_date' => $bd['blocked_date'],
                'category' => $bd['category']
            ];
        }
    } catch(Exception $e) {}

    echo json_encode(['success' => true, 'bookings' => $bookings]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
