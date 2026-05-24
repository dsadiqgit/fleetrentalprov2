<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$booking_id = intval($_GET['booking_id'] ?? 0);

if (!$booking_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
    exit;
}

$pdo = getDB();

try {
    // Get contract details
    $stmt = $pdo->prepare("SELECT content, contract_status FROM contracts WHERE booking_id = ? AND tenant_id = ?");
    $stmt->execute([$booking_id, $_SESSION['tenant_id']]);
    $contract = $stmt->fetch();

    if (!$contract) {
        echo json_encode(['success' => false, 'message' => 'Contract not found']);
        exit;
    }

    // Convert markdown/plain text to HTML if needed (simple version)
    $content = $contract['content'];
    
    // If it doesn't look like HTML, add line breaks
    if (strip_tags($content) === $content) {
        $content = nl2br(htmlspecialchars($content));
    }

    echo json_encode([
        'success' => true,
        'content' => $content,
        'status' => $contract['contract_status']
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
