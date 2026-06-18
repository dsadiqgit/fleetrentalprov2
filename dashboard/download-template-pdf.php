<?php
/**
 * Download/Preview Template PDF
 * Generates a PDF from a contract template for inline viewing
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/pdf-generator.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['tenant_id'])) {
    http_response_code(401);
    die('Unauthorized');
}

$template_id = isset($_GET['template_id']) ? intval($_GET['template_id']) : 0;
if (!$template_id) {
    die('Template ID required');
}

$pdo = getDB();
$tenant_id = $_SESSION['tenant_id'];

// Fetch template
$stmt = $pdo->prepare("SELECT * FROM contract_templates WHERE id = ? AND tenant_id = ?");
$stmt->execute([$template_id, $tenant_id]);
$template = $stmt->fetch();

if (!$template) {
    die('Template not found');
}

// Fetch tenant
$stmt = $pdo->prepare("SELECT * FROM tenants WHERE id = ?");
$stmt->execute([$tenant_id]);
$tenant = $stmt->fetch();

// Dummy data for preview
$contractData = ['content' => $template['content']];
$booking = [
    'id' => 0,
    'customer_name' => 'John Renter (Preview)',
    'customer_email' => 'john.renter@example.com',
    'customer_phone' => '+44 7700 900000',
    'customer_license' => 'ABC123456789 (Preview)',
    'pickup_date' => date('Y-m-d'),
    'pickup_time' => '10:00',
    'return_date' => date('Y-m-d', strtotime('+3 days')),
    'return_time' => '10:00',
    'total_days' => 3,
    'total_price' => 150.00,
    'security_deposit' => 500.00,
];
$vehicle = [
    'brand' => 'Sample',
    'model' => 'Vehicle',
    'year' => date('Y'),
    'category' => 'Premium',
    'mileage_limit' => 300,
    'registration' => 'PR3V13W',
];

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
renderContractToPDF($pdf, $contractData, $booking, $tenant, $vehicle, false);

$pdf->Output($template['name'] . ' - Preview.pdf', 'I');
