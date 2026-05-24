<?php
require_once __DIR__ . '/../includes/tenant_init.php';

$tenant_id = getTenantId();
$pdo = getDB();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$vehicle_id = $_POST['vehicle_id'] ?? '';
$unavailable_dates = $_POST['unavailable_dates'] ?? '';

if (!$vehicle_id) {
    echo json_encode(['success' => false, 'message' => 'Missing vehicle ID']);
    exit;
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS vehicle_blocked_dates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL,
        vehicle_id INT NOT NULL,
        blocked_date DATE NOT NULL,
        blocked_by VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY (vehicle_id, blocked_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

try {
    $stmt = $pdo->prepare("UPDATE vehicles SET unavailable_dates = ? WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$unavailable_dates, (int)$vehicle_id, $tenant_id]);

    $new_dates = [];
    if (!empty($unavailable_dates)) {
        $parts = explode(',', $unavailable_dates);
        foreach ($parts as $p) {
            $d = trim($p);
            if ($d) $new_dates[] = $d;
        }
    }
    
    $user_name = trim($_SESSION['full_name'] ?? 'Admin');
    if (empty($new_dates)) {
        $stmt = $pdo->prepare("DELETE FROM vehicle_blocked_dates WHERE vehicle_id = ?");
        $stmt->execute([(int)$vehicle_id]);
    } else {
        $placeholders = implode(',', array_fill(0, count($new_dates), '?'));
        $params = array_merge([(int)$vehicle_id], $new_dates);
        $stmt = $pdo->prepare("DELETE FROM vehicle_blocked_dates WHERE vehicle_id = ? AND blocked_date NOT IN ($placeholders)");
        $stmt->execute($params);
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO vehicle_blocked_dates (tenant_id, vehicle_id, blocked_date, blocked_by) VALUES (?, ?, ?, ?)");
        foreach ($new_dates as $d) {
            $stmt->execute([$tenant_id, (int)$vehicle_id, $d, $user_name]);
        }
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
