<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/email.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['template_id']) || !isset($data['email'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$pdo = getDB();

// Get template
$stmt = $pdo->prepare("SELECT * FROM contract_templates WHERE id = ? AND tenant_id = ?");
$stmt->execute([$data['template_id'], $_SESSION['tenant_id']]);
$template = $stmt->fetch();

if (!$template) {
    echo json_encode(['success' => false, 'message' => 'Template not found']);
    exit;
}

// Get template content
$content = $template['content'];
$isHtmlTemplate = false;

// Check if content is JSON (from visual designer)
$jsonData = json_decode($content, true);
if (json_last_error() === JSON_ERROR_NONE && isset($jsonData['html'])) {
    // It's a visual designer template - extract the HTML
    $content = $jsonData['html'];
    $isHtmlTemplate = true;
}

// Replace dynamic fields with sample data
$sampleData = [
    '{{vehicle_name}}' => 'Tesla Model 3',
    '{{vehicle_registration}}' => 'ABC-1234',
    '{{renter_full_name}}' => 'John Doe',
    '{{booking_reference}}' => 'BK-2024-001',
    '{{pickup_datetime}}' => '2024-03-20 10:00 AM',
    '{{return_datetime}}' => '2024-03-25 10:00 AM',
    '{{booking_total_price}}' => '$500.00',
    '{{security_deposit}}' => '$200.00',
    '{{included_distance}}' => '500 miles',
    '{{excess_distance_fee}}' => '$0.50',
    '{{deductible_amount}}' => '$1,000',
    '{{current_datetime}}' => date('F j, Y g:i A'),
    '{{signature}}' => '<div style="border-bottom: 2px solid #333; height: 60px; margin: 20px 0;"></div>'
];

foreach ($sampleData as $key => $value) {
    $content = str_replace($key, $value, $content);
}

// Create email HTML
if ($isHtmlTemplate) {
    // For visual designer templates, wrap the HTML content
    $emailContent = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                margin: 0;
                padding: 20px;
                background-color: #f5f5f5;
            }
            .email-wrapper {
                max-width: 800px;
                margin: 0 auto;
                background-color: #fff;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .email-header {
                background-color: #000;
                color: #fff;
                padding: 20px;
                text-align: center;
            }
            .email-body {
                padding: 30px;
            }
            .contract-content {
                margin: 20px 0;
            }
            .button {
                display: inline-block;
                background-color: #000;
                color: #fff;
                padding: 12px 30px;
                text-decoration: none;
                border-radius: 6px;
                margin: 20px 0;
            }
            .footer {
                text-align: center;
                color: #666;
                font-size: 12px;
                padding: 20px;
                background-color: #f9f9f9;
            }
        </style>
        <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>
    <body>
        <div class='email-wrapper'>
            <div class='email-header'>
                <h1>🚗 Test Signature Request</h1>
            </div>
            <div class='email-body'>
                <p>Hello,</p>
                <p>This is a <strong>test signature request</strong> for the contract template: <strong>" . htmlspecialchars($template['name']) . "</strong></p>
                <p>Please review the contract below and click the button to sign:</p>
                
                <div class='contract-content'>
                    " . $content . "
                </div>
                
                <div style='text-align: center;'>
                    <a href='#' class='button'>Sign Now (Test Mode)</a>
                </div>
                
                <p style='color: #666; font-size: 14px;'><em>Note: This is a test signature. In production, renters will complete additional verification steps including phone verification.</em></p>
            </div>
            <div class='footer'>
                <p>This is an automated test email from " . SITE_NAME . "</p>
            </div>
        </div>
    </body>
    </html>
    ";
} else {
    // For plain text templates, use the original format
    $emailContent = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
            }
            .header {
                background-color: #000;
                color: #fff;
                padding: 20px;
                text-align: center;
                border-radius: 8px 8px 0 0;
            }
            .content {
                background-color: #f9f9f9;
                padding: 30px;
                border: 1px solid #ddd;
                border-radius: 0 0 8px 8px;
            }
            .contract {
                background-color: #fff;
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 8px;
                margin: 20px 0;
                white-space: pre-wrap;
            }
            .button {
                display: inline-block;
                background-color: #000;
                color: #fff;
                padding: 12px 30px;
                text-decoration: none;
                border-radius: 6px;
                margin: 20px 0;
            }
            .footer {
                text-align: center;
                color: #666;
                font-size: 12px;
                margin-top: 30px;
            }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>🚗 Test Signature Request</h1>
        </div>
        <div class='content'>
            <p>Hello,</p>
            <p>This is a <strong>test signature request</strong> for the contract template: <strong>" . htmlspecialchars($template['name']) . "</strong></p>
            <p>Please review the contract below and click the button to sign:</p>
            
            <div class='contract'>" . nl2br(htmlspecialchars($content)) . "</div>
            
            <div style='text-align: center;'>
                <a href='#' class='button'>Sign Now (Test Mode)</a>
            </div>
            
            <p style='color: #666; font-size: 14px;'><em>Note: This is a test signature. In production, renters will complete additional verification steps including phone verification.</em></p>
        </div>
        <div class='footer'>
            <p>This is an automated test email from " . SITE_NAME . "</p>
        </div>
    </body>
    </html>
    ";
}

// Send email
$subject = "Test Signature Request - " . $template['name'];
$result = sendEmail($data['email'], $subject, $emailContent);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Test email sent successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send email']);
}
?>
