<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SESSION['role'] === 'super_admin') {
     header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Super admins cannot issue refunds directly']);
    exit;
}

$pdo = getDB();

// Handle Refund Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'refund' && isset($_POST['booking_id'])) {
    $r_booking_id = intval($_POST['booking_id']);
    
    // Get booking and Stripe keys
    $stmt = $pdo->prepare("
        SELECT b.*, ts.stripe_secret_key 
        FROM bookings b
        JOIN tenant_settings ts ON b.tenant_id = ts.tenant_id
        WHERE b.id = ? AND b.tenant_id = ?
    ");
    $stmt->execute([$r_booking_id, $_SESSION['tenant_id']]);
    $booking_to_refund = $stmt->fetch();
    
    if ($booking_to_refund && $booking_to_refund['stripe_payment_id'] && $booking_to_refund['payment_status'] === 'paid') {
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            \Stripe\Stripe::setApiKey($booking_to_refund['stripe_secret_key']);
            
            // Issue full refund
            \Stripe\Refund::create([
                'payment_intent' => $booking_to_refund['stripe_payment_id'],
            ]);
            
            // Update local DB
            $stmt = $pdo->prepare("UPDATE bookings SET payment_status = 'refunded', status = 'cancelled' WHERE id = ?");
            $stmt->execute([$r_booking_id]);
            
            $_SESSION['success_message'] = "Refund processed successfully for booking #$r_booking_id";
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Refund failed: " . $e->getMessage();
        }
        
        // Redirect back to referring page
        $return_to = $_SERVER['HTTP_REFERER'] ?? 'bookings.php';
        header("Location: " . $return_to);
        exit;
    } elseif ($action === 'manual_refund') {
        // Manual tracking for legacy or external refunds
        $stmt = $pdo->prepare("UPDATE bookings SET payment_status = 'refunded' WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$booking_id, $_SESSION['tenant_id']]);
        
        $_SESSION['success_message'] = "Booking marked as refunded manually. (Note: No automated funds were reversed via Stripe).";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }
}

header("Location: bookings.php");
exit;
