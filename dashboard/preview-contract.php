<?php
/**
 * Preview Contract PDF
 * Generates a PDF preview of the contract for the dashboard
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/pdf-generator.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['tenant_id'])) {
    http_response_code(401);
    die('Unauthorized');
}

$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
$template_id = isset($_GET['template_id']) ? intval($_GET['template_id']) : 0;
$debug = isset($_GET['debug']) ? true : false;
$tenant_id = $_SESSION['tenant_id'];
$pdo = getDB();

if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // Suppress errors and warnings to prevent PDF corruption
    error_reporting(0);
    ini_set('display_errors', 0);
}

function getTenant() {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM tenants WHERE id = ?");
    $stmt->execute([$_SESSION['tenant_id']]);
    return $stmt->fetch();
}

if (!$booking_id && !$template_id) {
    die('Invalid request');
}

$tenant = getTenant();
if (!$tenant) {
    die('Tenant not found');
}

$contractForPdf = null;
$bookingForPdf = [
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

$vehicleForPdf = [
    'brand' => 'Sample',
    'model' => 'Vehicle',
    'year' => date('Y'),
    'category' => 'Premium',
    'mileage_limit' => 300,
    'registration' => 'PR3V13W',
];

if ($booking_id) {
    // Fetch real booking and contract data
    $stmt = $pdo->prepare("
        SELECT c.*, b.customer_name, b.customer_email, b.customer_phone, b.customer_license,
               b.pickup_date, b.pickup_time, b.return_date, b.return_time,
               b.total_days, b.total_price, b.security_deposit, b.price_per_day, b.vehicle_id,
               v.brand, v.model, v.year, v.category, v.mileage_limit, v.license_plate
        FROM contracts c
        JOIN bookings b ON c.booking_id = b.id
        LEFT JOIN vehicles v ON b.vehicle_id = v.id
        WHERE c.booking_id = ? AND c.tenant_id = ?
    ");
    $stmt->execute([$booking_id, $tenant['id']]);
    $contractData = $stmt->fetch();

    if ($contractData) {
        if ($_SESSION['role'] === 'customer' && $contractData['customer_email'] !== $_SESSION['user_email']) {
            http_response_code(403);
            die('Access denied - You can only view your own contracts.');
        }
        $contractForPdf = $contractData;
        $bookingForPdf = [
            'id' => $booking_id,
            'customer_name' => $contractData['customer_name'],
            'customer_email' => $contractData['customer_email'],
            'customer_phone' => $contractData['customer_phone'],
            'customer_license' => $contractData['customer_license'],
            'pickup_date' => $contractData['pickup_date'],
            'pickup_time' => $contractData['pickup_time'],
            'return_date' => $contractData['return_date'],
            'return_time' => $contractData['return_time'],
            'total_days' => $contractData['total_days'],
            'total_price' => $contractData['total_price'],
            'security_deposit' => $contractData['security_deposit'],
        ];
        
        $vehicleForPdf = [
            'brand' => $contractData['brand'],
            'model' => $contractData['model'],
            'year' => $contractData['year'],
            'category' => $contractData['category'],
            'mileage_limit' => $contractData['mileage_limit'],
            'registration' => $contractData['license_plate'] ?? 'N/A',
        ];
    } else {
        // Find template for this booking if no contract record exists
        $stmt = $pdo->prepare("SELECT content FROM contract_templates WHERE tenant_id = ? AND is_default = 1 LIMIT 1");
        $stmt->execute([$tenant['id']]);
        $template = $stmt->fetch();
        if ($template) {
            $contractForPdf = ['content' => $template['content']];
            // Reuse sample booking but try to get real names if booking exists
            $stmt_b = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND tenant_id = ?");
            $stmt_b->execute([$booking_id, $tenant['id']]);
            $real_b = $stmt_b->fetch();
            if ($real_b) {
                $bookingForPdf = $real_b;
                $stmt_v = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
                $stmt_v->execute([$real_b['vehicle_id']]);
                $real_v = $stmt_v->fetch();
                if ($real_v) {
                    $vehicleForPdf = $real_v;
                    $vehicleForPdf['registration'] = $real_v['license_plate'] ?? 'N/A';
                }
            }
        }
    }
} elseif ($template_id) {
    // Fetch template content
    $stmt = $pdo->prepare("SELECT * FROM contract_templates WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$template_id, $tenant['id']]);
    $template = $stmt->fetch();
    
    if ($template) {
        $contractForPdf = ['content' => $template['content']];
    }
}

if (!$contractForPdf) {
    die('Contract content not found');
}

$rawContent = $contractForPdf['content'] ?? '';
$contentJson = json_decode($rawContent, true);
$isVisualTemplate = ($contentJson && isset($contentJson['html']));
$contractText = $isVisualTemplate ? $contentJson['html'] : $rawContent;

$vehicleNameStr = trim(($vehicleForPdf['brand'] ?? '') . ' ' . ($vehicleForPdf['model'] ?? ''));

// Fetch a team member of this tenant to act as the Witness / Rental Provider signer
$witness_name = 'Authorized Witness';
$witness_signature_html = '';

$witness = null;

if (!empty($_SESSION['user_id'])) {
    $stmt_w = $pdo->prepare("SELECT full_name, signature_data FROM users WHERE id = ? AND tenant_id = ?");
    $stmt_w->execute([$_SESSION['user_id'], $tenant['id']]);
    $sessionWitness = $stmt_w->fetch();
    if ($sessionWitness && !empty($sessionWitness['signature_data'])) {
        $witness = $sessionWitness;
    }
}

if (!$witness) {
    $stmt_w = $pdo->prepare("
        SELECT full_name, signature_data FROM users
        WHERE tenant_id = ? AND signature_data IS NOT NULL AND signature_data != ''
        ORDER BY CASE role
            WHEN 'owner' THEN 1
            WHEN 'admin' THEN 2
            WHEN 'staff' THEN 3
            ELSE 4 END,
            id ASC
        LIMIT 1
    ");
    $stmt_w->execute([$tenant['id']]);
    $witness = $stmt_w->fetch();
}

if (!$witness) {
    $stmt_w = $pdo->prepare("
        SELECT full_name, signature_data FROM users
        WHERE tenant_id = ?
        ORDER BY CASE role
            WHEN 'owner' THEN 1
            WHEN 'admin' THEN 2
            WHEN 'staff' THEN 3
            ELSE 4 END,
            id ASC
        LIMIT 1
    ");
    $stmt_w->execute([$tenant['id']]);
    $witness = $stmt_w->fetch();
}

if ($witness) {
    $witness_name = $witness['full_name'] ?? 'Authorized Representative';
    if (!empty($witness['signature_data'])) {
        $witness_sig = $witness['signature_data'];
        if (strpos($witness_sig, 'data:image/') === 0) {
            $witness_signature_html = '<img src="' . htmlspecialchars($witness_sig) . '" style="max-height: 50px; display: inline-block;" alt="Witness Signature" />';
        } else {
            $witness_signature_html = '<i>' . htmlspecialchars($witness_sig) . '</i>';
        }
    }
}

$replacements = [
    '{{vehicle_name}}' => trim($vehicleNameStr) ?: 'NOT SPECIFIED',
    '{{vehicle_registration}}' => $vehicleForPdf['registration'] ?? 'N/A',
    '{{renter_full_name}}' => $bookingForPdf['customer_name'] ?? 'PREVIEW CUSTOMER',
    '{{tenant_name}}' => $tenant['name'],
    '{{booking_reference}}' => '#' . str_pad($bookingForPdf['id'] ?? 0, 5, '0', STR_PAD_LEFT),
    '{{pickup_datetime}}' => date('M d, Y', strtotime($bookingForPdf['pickup_date'] ?? 'now')),
    '{{return_datetime}}' => date('M d, Y', strtotime($bookingForPdf['return_date'] ?? 'now')),
    '{{booking_total_price}}' => '£' . number_format($bookingForPdf['total_price'] ?? 0, 2),
    '{{security_deposit}}' => '£' . number_format($bookingForPdf['security_deposit'] ?? 0, 2),
    '{{included_distance}}' => ($vehicleForPdf['mileage_limit'] ?? 'Unlimited') . ' miles',
    '{{excess_distance_fee}}' => '£0.50',
    '{{deductible_amount}}' => '£500',
    '{{witness_signature}}' => $witness_signature_html,
    '{{user_name}}' => $witness_name,
];
    $signatureHtml = '<span style="color:#999;font-style:italic;font-weight:bold;">DIGITAL PREVIEW</span>';
    if (!empty($contractForPdf['contract_status']) && $contractForPdf['contract_status'] === 'signed') {
        $sigValue = $contractForPdf['signature_typed'] ?? '';
        $sigImagePath = $contractForPdf['signature_image_path'] ?? '';

        if (strpos($sigValue, 'data:image/') === 0) {
            $signatureHtml = '<img src="' . htmlspecialchars($sigValue) . '" style="max-height:60px; display:inline-block;" alt="Signature" />';
        } elseif (!empty($sigImagePath) && file_exists($sigImagePath)) {
            $mime = mime_content_type($sigImagePath);
            $imgData = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($sigImagePath));
            $signatureHtml = '<img src="' . $imgData . '" style="max-height:60px; display:inline-block;" alt="Signature" />';
        } elseif (!empty($sigValue) && strlen($sigValue) < 255 && file_exists($sigValue)) {
            $mime = mime_content_type($sigValue);
            $imgData = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($sigValue));
            $signatureHtml = '<img src="' . $imgData . '" style="max-height:60px; display:inline-block;" alt="Signature" />';
        } elseif (!empty($sigValue)) {
            $signatureHtml = '<span style="font-family: \'Brush Script MT\', cursive, italic; font-size: 24px; color: #111;">' . htmlspecialchars($sigValue) . '</span>';
        }
    }
    
    $replacements['{{current_datetime}}'] = date('F j, Y h:i A');
    $replacements['{{signature}}'] = $signatureHtml;


foreach ($replacements as $key => $value) {
    $pattern = '/\{\{\s*(?:<[^>]+>)*' . preg_quote(trim($key, '{} '), '/') . '(?:\s*<[^>]+>)*\s*\}\}/i';
    $contractText = preg_replace($pattern, $value, $contractText);
    $contractText = str_ireplace($key, $value, $contractText);
}

// Direct translations requested by the user
$contractText = str_ireplace('business Owner', 'Witness', $contractText);
$contractText = str_ireplace('Car Rental', $witness_name, $contractText);

// Remove designer controls to ensure the preview is a clean, static document
$contractText = preg_replace('/<button[^>]*>.*?<\/button>/is', '', $contractText);
$contractText = preg_replace('/<div[^>]*class="[^"]*group-hover:opacity-100[^"]*"[^>]*>.*?<\/div>/is', '', $contractText);
$contractText = str_ireplace('contenteditable="true"', '', $contractText);
$contractText = str_ireplace('contenteditable', '', $contractText);

// Clear any accidental output buffers
if (ob_get_length()) ob_clean();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract Preview - <?= htmlspecialchars($tenant['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { background: white !important; margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .page-break-before { page-break-before: always; }
        }
        body { 
            background: #f3f4f6; 
            padding-top: 2rem; 
            padding-bottom: 2rem; 
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .page-container {
            background: white;
            max-width: 800px;
            margin: 0 auto;
            min-height: 1122px; /* A4 aspect ratio approximation */
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <div class="fixed top-4 right-4 z-50 flex gap-2 no-print">
        <button onclick="window.close()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium text-sm transition-all shadow-sm">
            Close Preview
        </button>
        <button onclick="window.print()" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-black font-medium text-sm transition-all shadow-lg flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Print / Save PDF
        </button>
    </div>

    <!-- The actual document container -->
    <div class="page-container" id="contract-document">
        <?php if ($isVisualTemplate): ?>
            <!-- In rendering a visual template, we just inject the HTML directly so Tailwind handles it natively -->
            <div class="w-full text-gray-800">
                <?= $contractText ?>
            </div>
        <?php else: ?>
            <!-- For legacy generic templates, we render standard A4 borders -->
            <div class="p-10 text-gray-800 prose max-w-none">
                <?= htmlspecialchars_decode($contractText) ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Auto-print trigger for direct PDF downloads -->
    <?php if (isset($_GET['autoprint'])): ?>
    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 500); // Short delay ensures assets and styles finish computing
        });
    </script>
    <?php endif; ?>
</body>
</html>
