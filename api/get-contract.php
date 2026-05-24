<?php
/**
 * Get Contract API
 * Returns contract content with template variables replaced for a given booking
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Get tenant context - either from session (admin/logged-in) or subdomain (anonymous signature)
$tenant_id = $_SESSION['tenant_id'] ?? null;

if (!$tenant_id) {
    // Try to get from subdomain (basic extraction for anonymous signing context)
    $host = $_SERVER['HTTP_HOST'] ?? '';
    // Remove port if present
    $host = preg_replace('/:\d+$/', '', $host);
    $host_parts = explode('.', $host);
    
    // Subdomain is usually the first part if we have at least 3 parts (e.g. fresh.fleet.com)
    // or if we're on localhost (fresh.localhost)
    if (count($host_parts) >= 2) {
        $subdomain = $host_parts[0];
        if ($subdomain !== 'www' && $subdomain !== 'localhost') {
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT id FROM tenants WHERE subdomain = ? AND status = 'active'");
            $stmt->execute([$subdomain]);
            $tenant_id = $stmt->fetchColumn();
        }
    }
}

// Fallback helper if merchant logic calls it
if (!function_exists('getTenantId')) {
    $GLOBALS['api_tenant_id'] = $tenant_id;
    function getTenantId() { return $GLOBALS['api_tenant_id']; }
}

header('Content-Type: application/json');

$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
$token = $_GET['token'] ?? '';

if (!$booking_id || !$token) {
    echo json_encode(['success' => false, 'message' => 'Missing booking_id or token']);
    exit;
}

$tenant_id = getTenantId();
$pdo = getDB();

try {
    // Get contract with token validation
    $stmt = $pdo->prepare("
        SELECT c.*, b.customer_name, b.customer_email, b.customer_phone, b.customer_license,
               b.pickup_date, b.pickup_time, b.return_date, b.return_time,
               b.total_days, b.total_price, b.security_deposit, b.price_per_day,
               v.brand, v.model, v.year, v.category, v.mileage_limit,
               t.name as tenant_name
        FROM contracts c
        JOIN bookings b ON c.booking_id = b.id
        JOIN tenants t ON c.tenant_id = t.id
        LEFT JOIN vehicles v ON b.vehicle_id = v.id
        WHERE c.booking_id = ? AND c.signing_token = ? AND c.tenant_id = ?
    ");
    $stmt->execute([$booking_id, $token, $tenant_id]);
    $data = $stmt->fetch();
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Contract not found or invalid token']);
        exit;
    }
    
    // Determine if it's a visual template (JSON content)
    $rawContent = $data['content'] ?? '';
    $contentJson = json_decode($rawContent, true);
    $isVisualTemplate = ($contentJson && isset($contentJson['html']));

    // Extract the working content
    $content = $isVisualTemplate ? $contentJson['html'] : $rawContent;
    $vehicleName = trim(($data['brand'] ?? '') . ' ' . ($data['model'] ?? ''));
    
    // Fetch a team member of this tenant to act as the Witness / Rental Provider signer
    $witness_name = 'Authorized Witness';
    $witness_signature_html = '';
    
    $stmt_w = $pdo->prepare("SELECT full_name, signature_data FROM users WHERE tenant_id = ? AND role IN ('admin', 'staff') AND signature_data IS NOT NULL AND signature_data != '' LIMIT 1");
    $stmt_w->execute([$tenant_id]);
    $witness = $stmt_w->fetch();
    
    if (!$witness) {
        // Fallback to any admin of the tenant
        $stmt_w = $pdo->prepare("SELECT full_name, signature_data FROM users WHERE tenant_id = ? AND role = 'admin' LIMIT 1");
        $stmt_w->execute([$tenant_id]);
        $witness = $stmt_w->fetch();
    }
    
    if ($witness) {
        $witness_name = $witness['full_name'] ?? 'Authorized Representative';
        if (!empty($witness['signature_data'])) {
            $witness_sig = $witness['signature_data'];
            if (strpos($witness_sig, 'data:image/') === 0) {
                $witness_signature_html = '<img src="' . htmlspecialchars($witness_sig) . '" style="max-height: 50px; height: 50px;" />';
            } else {
                $witness_signature_html = '<i>' . htmlspecialchars($witness_sig) . '</i>';
            }
        }
    }
    
    $replacements = [
        '{{vehicle_name}}' => $vehicleName,
        '{{vehicle_registration}}' => 'N/A',
        '{{renter_full_name}}' => $data['customer_name'],
        '{{tenant_name}}' => $data['tenant_name'],
        '{{booking_reference}}' => '#' . str_pad($booking_id, 5, '0', STR_PAD_LEFT),
        '{{pickup_datetime}}' => date('M d, Y', strtotime($data['pickup_date'])) . ' ' . date('g:i A', strtotime($data['pickup_time'] ?? '10:00')),
        '{{return_datetime}}' => date('M d, Y', strtotime($data['return_date'])) . ' ' . date('g:i A', strtotime($data['return_time'] ?? '10:00')),
        '{{booking_total_price}}' => '£' . number_format($data['total_price'], 2),
        '{{security_deposit}}' => '£' . number_format($data['security_deposit'] ?? 0, 2),
        '{{included_distance}}' => ($data['mileage_limit'] ?? 'Unlimited') . ' miles',
        '{{excess_distance_fee}}' => '£0.50',
        '{{deductible_amount}}' => '£500',
        '{{current_datetime}}' => date('F j, Y h:i A'),
        '{{signature}}' => '',
        '{{witness_signature}}' => $witness_signature_html,
        '{{user_name}}' => $witness_name,
    ];
    
    foreach ($replacements as $key => $value) {
        $content = str_replace($key, $value, $content);
    }

    // Direct translations requested by the user
    $content = str_ireplace('business Owner', 'Witness', $content);
    $content = str_ireplace('Car Rental', $witness_name, $content);
    
    $isHtml = $isVisualTemplate;
    if ($isHtml) {
        // Clean up editing tools for the signing view
        $content = preg_replace('/<div[^>]*class="[^"]*absolute -left-12[^"]*"[^>]*>.*?<\/div>/is', '', $content);
        $content = preg_replace('/<div[^>]*class="[^"]*no-print[^"]*"[^>]*>.*?<\/div>/is', '', $content);
        $content = preg_replace('/<button[^>]*onclick="(?:removeSection|moveSection)\(\d+,?\s*\'?\w*\'?\)"[^>]*>.*?<\/button>/is', '', $content);
        
        // Remove contenteditable attributes
        $content = preg_replace('/\s*contenteditable(?:\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+))?/i', '', $content);
    } else {
        // Check if plain content actually contains HTML manually
        if ($content !== strip_tags($content)) {
            $isHtml = true;
        }
    }

    
    // Build signature image URL for frontend display
    $signatureImageUrl = null;
    $signatureTyped = $data['signature_typed'] ?? null;
    if ($signatureTyped && file_exists($signatureTyped)) {
        // It's a file path to a saved signature image — convert to web URL
        $signatureImageUrl = str_replace(
            realpath(__DIR__ . '/..'),
            '',
            realpath($signatureTyped)
        );
    } elseif ($signatureTyped && strpos($signatureTyped, 'data:image/') === 0) {
        // It's a base64 data URL — pass it directly
        $signatureImageUrl = $signatureTyped;
    }
    
    echo json_encode([
        'success' => true,
        'contract' => [
            'id' => $data['id'],
            'content' => $content,
            'is_html' => $isHtml,
            'contract_status' => $data['contract_status'] ?? ($data['signed'] ? 'signed' : 'pending'),
            'signed_at' => $data['signed_at'],
            'signature_typed' => $signatureTyped,
            'signature_image_url' => $signatureImageUrl,
        ],
        'booking' => [
            'id' => $booking_id,
            'customer_name' => $data['customer_name'],
            'vehicle' => $vehicleName,
            'pickup_date' => $data['pickup_date'],
            'return_date' => $data['return_date'],
            'total_price' => $data['total_price'],
            'total_days' => $data['total_days'],
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Get contract error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
