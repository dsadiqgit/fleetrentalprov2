<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
    exit;
}

$pdo = getDB();
$booking_id = intval($_GET['id']);

try {
    // Get booking details with vehicle information
    $stmt = $pdo->prepare("
        SELECT b.*, v.brand, v.model, v.year, v.category
        FROM bookings b
        LEFT JOIN vehicles v ON b.vehicle_id = v.id
        WHERE b.id = ? AND b.tenant_id = ?
    ");
    $stmt->execute([$booking_id, $_SESSION['tenant_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }
    
    
    // Get condition reports
    $stmt = $pdo->prepare("SELECT * FROM booking_condition_reports WHERE booking_id = ? AND tenant_id = ?");
    $stmt->execute([$booking_id, $_SESSION['tenant_id']]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $condition_reports = [
        'pickup' => null,
        'return' => null
    ];
    
    foreach ($reports as $report) {
        if ($report['report_type'] === 'pickup') $condition_reports['pickup'] = $report;
        if ($report['report_type'] === 'return') $condition_reports['return'] = $report;
    }
    
    // Get contract details
    $stmt = $pdo->prepare("SELECT id, contract_status, signed_at, signing_token FROM contracts WHERE booking_id = ? AND tenant_id = ? LIMIT 1");
    $stmt->execute([$booking_id, $_SESSION['tenant_id']]);
    $contract = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get available templates if no contract exists
    $available_templates = [];
    if (!$contract) {
        $stmt = $pdo->prepare("SELECT id, name FROM contract_templates WHERE tenant_id = ? AND status = 'published' ORDER BY is_default DESC, created_at DESC");
        $stmt->execute([$_SESSION['tenant_id']]);
        $available_templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'booking' => $booking,
        'condition_reports' => $condition_reports,
        'contract' => $contract,
        'available_templates' => $available_templates
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
