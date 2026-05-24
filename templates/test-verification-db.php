<?php
require_once __DIR__ . '/../includes/tenant_init.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $pdo = getDB();
    $tenant_id = getTenantId();
    
    // Get all recent verifications for this tenant
    $stmt = $pdo->prepare("
        SELECT * FROM customer_verifications 
        WHERE tenant_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$tenant_id]);
    $verifications = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'tenant_id' => $tenant_id,
        'session_data' => [
            'didit_session_id' => $_SESSION['didit_session_id'] ?? null,
            'customer_email' => $_SESSION['customer_email'] ?? null,
            'verification_status' => $_SESSION['verification_status'] ?? null
        ],
        'recent_verifications' => $verifications
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
