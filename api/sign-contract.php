<?php
/**
 * Sign Contract API
 * Handles digital signature submission, PDF generation, and notification emails
 */
require_once __DIR__ . '/../includes/tenant_init.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/security-helper.php';
require_once __DIR__ . '/../includes/email.php';
require_once __DIR__ . '/../includes/pdf-generator.php';

// Apply Rate Limiting: 10 signing attempts per minute per IP
check_rate_limit('sign_contract', 10, 60);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Ensure clean JSON by suppressing inline errors
error_reporting(0);
ini_set('display_errors', 0);

// Increase timeout for PDF generation and SMTP
set_time_limit(120);
ini_set('memory_limit', '256M');

$data = json_decode(file_get_contents('php://input'), true);

$booking_id = intval($data['booking_id'] ?? 0);
$token = $data['token'] ?? '';
$signatureData = trim($data['signature'] ?? '');

if (!$booking_id || !$token || !$signatureData) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields (booking_id, token, signature)']);
    exit;
}

// Handle drawn signature (base64 data URL)
$signatureImagePath = null;
if (strpos($signatureData, 'data:image/') === 0) {
    // Save signature image to uploads
    $sigDir = __DIR__ . '/../uploads/signatures';
    if (!is_dir($sigDir)) mkdir($sigDir, 0755, true);
    
    $imgData = explode(',', $signatureData, 2);
    if (count($imgData) === 2) {
        $decoded = base64_decode($imgData[1]);
        $sigFilename = 'sig_' . $booking_id . '_' . time() . '.png';
        $signatureImagePath = $sigDir . '/' . $sigFilename;
        file_put_contents($signatureImagePath, $decoded);
    }
}

$tenant_id = getTenantId();
$tenant = getTenant();
$pdo = getDB();

try {
    // Get contract with token validation
    $stmt = $pdo->prepare("
        SELECT c.*, b.customer_name, b.customer_email, b.customer_phone, b.customer_license,
               b.pickup_date, b.pickup_time, b.return_date, b.return_time,
               b.total_days, b.total_price, b.security_deposit, b.price_per_day, b.vehicle_id,
               v.brand, v.model, v.year, v.category, v.mileage_limit, v.license_plate
        FROM contracts c
        JOIN bookings b ON c.booking_id = b.id
        LEFT JOIN vehicles v ON b.vehicle_id = v.id
        WHERE c.booking_id = ? AND c.signing_token = ? AND c.tenant_id = ?
    ");
    $stmt->execute([$booking_id, $token, $tenant_id]);
    $contractData = $stmt->fetch();
    
    if (!$contractData) {
        echo json_encode(['success' => false, 'message' => 'Contract not found or invalid token']);
        exit;
    }
    
    // Check if already signed
    $currentStatus = $contractData['contract_status'] ?? ($contractData['signed'] ? 'signed' : 'pending');
    if ($currentStatus === 'signed') {
        echo json_encode(['success' => false, 'message' => 'Contract has already been signed']);
        exit;
    }
    
    // Update contract as signed
    $updateStmt = $pdo->prepare("
        UPDATE contracts 
        SET signed = 1, 
            signed_at = NOW(), 
            contract_status = 'signed',
            signature_typed = ?,
            updated_at = NOW()
        WHERE booking_id = ? AND signing_token = ? AND tenant_id = ?
    ");
    $updateStmt->execute([$signatureImagePath ?? $signatureData, $booking_id, $token, $tenant_id]);
    
    if ($updateStmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Failed to update contract']);
        exit;
    }
    
    // Refresh the contract data for PDF generation
    $stmt->execute([$booking_id, $token, $tenant_id]);
    $updatedContract = $stmt->fetch();
    
    // Generate signed PDF
    $pdfPath = null;
    try {
        $contractForPdf = [
            'id' => $updatedContract['id'],
            'content' => $updatedContract['content'],
            'signature_typed' => $signatureData,
            'signature_image_path' => $signatureImagePath,
            'signed_at' => date('Y-m-d H:i:s'),
            'signing_token' => $token,
        ];
        
        $bookingForPdf = [
            'id' => $booking_id,
            'customer_name' => $updatedContract['customer_name'],
            'customer_email' => $updatedContract['customer_email'],
            'customer_phone' => $updatedContract['customer_phone'],
            'customer_license' => $updatedContract['customer_license'],
            'pickup_date' => $updatedContract['pickup_date'],
            'pickup_time' => $updatedContract['pickup_time'],
            'return_date' => $updatedContract['return_date'],
            'return_time' => $updatedContract['return_time'],
            'total_days' => $updatedContract['total_days'],
            'total_price' => $updatedContract['total_price'],
            'security_deposit' => $updatedContract['security_deposit'],
        ];
        
        $vehicleForPdf = [
            'brand' => $updatedContract['brand'],
            'model' => $updatedContract['model'],
            'year' => $updatedContract['year'],
            'category' => $updatedContract['category'],
            'mileage_limit' => $updatedContract['mileage_limit'],
            'registration' => $updatedContract['license_plate'] ?? 'N/A',
        ];
        
        $pdfPath = generateSignedContractPDF($contractForPdf, $bookingForPdf, $tenant, $vehicleForPdf);
        
        // Store PDF path
        if ($pdfPath) {
            $pdfStmt = $pdo->prepare("UPDATE contracts SET signed_pdf_path = ? WHERE booking_id = ? AND tenant_id = ?");
            $pdfStmt->execute([$pdfPath, $booking_id, $tenant_id]);
        }
        
        error_log("Signed contract PDF generated: {$pdfPath}");
    } catch (Exception $pdfError) {
        error_log("PDF generation error (non-fatal): " . $pdfError->getMessage());
    }
    
    // Send confirmation email to customer with PDF
    try {
        if ($pdfPath && file_exists($pdfPath)) {
            sendSignedContractEmail(
                $updatedContract['customer_email'],
                $updatedContract['customer_name'],
                $pdfPath,
                $tenant
            );
        }
    } catch (Exception $emailError) {
        error_log("Customer email error (non-fatal): " . $emailError->getMessage());
    }
    
    // Send notification to tenant admin
    try {
        $adminStmt = $pdo->prepare("SELECT email FROM users WHERE tenant_id = ? AND role = 'admin' LIMIT 1");
        $adminStmt->execute([$tenant_id]);
        $admin = $adminStmt->fetch();
        
        if ($admin) {
            $bookingRef = str_pad($booking_id, 5, '0', STR_PAD_LEFT);
            sendContractSignedNotification(
                $admin['email'],
                $updatedContract['customer_name'],
                $bookingRef,
                $tenant
            );
        }
    } catch (Exception $notifError) {
        error_log("Admin notification error (non-fatal): " . $notifError->getMessage());
    }
    
    // ONBOARDING: Log the user in so they are redirected to dashboard smoothly
    if (!isset($_SESSION['user_id'])) {
        $stmtUser = $pdo->prepare("SELECT id, role, email, password, full_name FROM users WHERE email = ? AND tenant_id = ?");
        $stmtUser->execute([$updatedContract['customer_email'], $tenant_id]);
        $user = $stmtUser->fetch();
        
        if ($user) {
            // If user has no password, generate one now to ensure they can log in later
            if (empty($user['password'])) {
                require_once __DIR__ . '/../includes/functions.php';
                $newPassword = substr(bin2hex(random_bytes(5)), 0, 8);
                $hashed = hashPassword($newPassword);
                $updateUser = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updateUser->execute([$hashed, $user['id']]);
                error_log("Set missing password for user {$user['id']} during contract signing.");
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['tenant_id'] = $tenant_id;
            $_SESSION['role'] = $user['role'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'];
        }
    }

    // Always succeed if we got this far, even if emails had issues earlier
    // (Existing try/catch blocks around emails handled their own errors)
    echo json_encode([
        'success' => true,
        'message' => 'Contract signed successfully',
        'signed_at' => date('F j, Y \a\t h:i A'),
        'redirect_url' => '/dashboard/index.php'
    ]);
    
} catch (PDOException $e) {
    error_log("Sign contract error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
