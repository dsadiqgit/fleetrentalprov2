<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = getDB();

$stmt = $pdo->prepare("SELECT * FROM contract_templates WHERE id = ? AND tenant_id = ?");
$stmt->execute([$_GET['id'], $_SESSION['tenant_id']]);
$template = $stmt->fetch();

if ($template) {
    $content = $template['content'];
    
    // Check if content is JSON (from visual designer)
    $jsonData = json_decode($content, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($jsonData['html'])) {
        // It's a visual designer template - extract the HTML
        $content = $jsonData['html'];
        
        // Clean up editing tools for preview
        $content = preg_replace('/<div[^>]*class="[^"]*absolute -left-12[^"]*"[^>]*>.*?<\/div>/is', '', $content);
        $content = preg_replace('/<div[^>]*class="[^"]*no-print[^"]*"[^>]*>.*?<\/div>/is', '', $content);
        $content = preg_replace('/<button[^>]*onclick="(?:removeSection|moveSection)\(\d+,?\s*\'?\w*\'?\)"[^>]*>.*?<\/button>/is', '', $content);
        
        // Remove contenteditable attributes
        $content = preg_replace('/\s*contenteditable(?:\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+))?/i', '', $content);
    }
    // Otherwise it's a plain text template from the text editor
    
    echo json_encode([
        'success' => true,
        'content' => $content,
        'name' => $template['name'],
        'is_html' => isset($jsonData['html'])
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Template not found']);
}
?>
