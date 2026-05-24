<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['name']) || !isset($data['html'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$pdo = getDB();

try {
    // Store the template data as JSON
    $templateData = json_encode([
        'brand_color' => $data['brand_color'] ?? '#FF5733',
        'logo' => $data['logo'] ?? null,
        'contact' => $data['contact'] ?? [],
        'html' => $data['html']
    ]);
    
    // Check if this is an update (template_id provided) or new insert
    if (isset($data['template_id']) && is_numeric($data['template_id'])) {
        // Update existing template
        $stmt = $pdo->prepare("UPDATE contract_templates SET name = ?, content = ? WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$data['name'], $templateData, $data['template_id'], $_SESSION['tenant_id']]);
    } else {
        // Insert new template
        $stmt = $pdo->prepare("INSERT INTO contract_templates (tenant_id, name, content, status) VALUES (?, ?, ?, 'published')");
        $stmt->execute([$_SESSION['tenant_id'], $data['name'], $templateData]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Template saved successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
