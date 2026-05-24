<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    echo json_encode(['success' => false, 'message' => 'The uploaded file(s) exceed the server\'s upload limit (post_max_size). Please try uploading smaller images or fewer at a time.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$booking_id = intval($_POST['booking_id'] ?? 0);
$report_type = $_POST['report_type'] ?? ''; // 'pickup' or 'return'
$mileage = intval($_POST['mileage'] ?? 0);

if (!$booking_id || !in_array($report_type, ['pickup', 'return'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking or report type']);
    exit;
}

if ($mileage <= 0) {
    echo json_encode(['success' => false, 'message' => 'Mileage is required and must be greater than zero']);
    exit;
}

$pdo = getDB();

try {
    // Check if booking exists and belongs to tenant
    $stmt = $pdo->prepare("SELECT id, status FROM bookings WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$booking_id, $_SESSION['tenant_id']]);
    $booking = $stmt->fetch();

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }

    $upload_dir = __DIR__ . '/../uploads/condition_reports/';
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
             echo json_encode(['success' => false, 'message' => 'Failed to create upload directory. Please check file permissions.']);
             exit;
        }
    }

    if (!is_writable($upload_dir)) {
        echo json_encode(['success' => false, 'message' => 'Upload directory is not writable. Please check permissions for: ' . realpath($upload_dir)]);
        exit;
    }

    $photo_columns = [
        'photo_front', 'photo_back', 'photo_left', 'photo_right', 
        'photo_rim1', 'photo_rim2', 'photo_rim3', 'photo_rim4'
    ];

    // Check if all mandatory photos are present for pickup
    if ($report_type === 'pickup') {
        // Get existing report if any
        $stmt_existing = $pdo->prepare("SELECT * FROM booking_condition_reports WHERE booking_id = ? AND report_type = 'pickup'");
        $stmt_existing->execute([$booking_id]);
        $existing = $stmt_existing->fetch();

        foreach ($photo_columns as $col) {
            $has_new = isset($_FILES[$col]) && $_FILES[$col]['error'] === UPLOAD_ERR_OK;
            $has_existing = $existing && !empty($existing[$col]);
            if (!$has_new && !$has_existing) {
                echo json_encode(['success' => false, 'message' => "Mandatory photo missing: " . str_replace(['photo_', '_'], ['', ' '], $col)]);
                exit;
            }
        }
    }

    // Get existing report if any to preserve photos
    $stmt_existing = $pdo->prepare("SELECT * FROM booking_condition_reports WHERE booking_id = ? AND report_type = ?");
    $stmt_existing->execute([$booking_id, $report_type]);
    $existing = $stmt_existing->fetch();

    $uploaded_paths = [];
    foreach ($photo_columns as $col) {
        if (isset($_FILES[$col]) && $_FILES[$col]['error'] === UPLOAD_ERR_OK) {
            $file_ext = strtolower(pathinfo($_FILES[$col]['name'], PATHINFO_EXTENSION));
            $new_name = $booking_id . '_' . $report_type . '_' . $col . '_' . time() . '.' . $file_ext;
            if (move_uploaded_file($_FILES[$col]['tmp_name'], $upload_dir . $new_name)) {
                $uploaded_paths[$col] = '/uploads/condition_reports/' . $new_name;
            }
        }
    }

    // Handle misc photos - only replace if new ones are uploaded
    $misc_paths = [];
    $has_new_misc = false;
    if (isset($_FILES['misc_photos'])) {
        $files = $_FILES['misc_photos'];
        $count = is_array($files['name']) ? count($files['name']) : 0;
        for ($i = 0; $i < min($count, 5); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $has_new_misc = true;
                $file_ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                $new_name = $booking_id . '_' . $report_type . '_misc_' . $i . '_' . time() . '.' . $file_ext;
                if (move_uploaded_file($files['tmp_name'][$i], $upload_dir . $new_name)) {
                    $misc_paths[] = '/uploads/condition_reports/' . $new_name;
                }
            }
        }
    }

    $final_misc_photos = $has_new_misc ? json_encode($misc_paths) : ($existing['misc_photos'] ?? '[]');

    // Insert or update report
    $sql = "INSERT INTO booking_condition_reports 
            (tenant_id, booking_id, report_type, mileage, photo_front, photo_back, photo_left, photo_right, photo_rim1, photo_rim2, photo_rim3, photo_rim4, misc_photos) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            mileage = VALUES(mileage),
            photo_front = COALESCE(NULLIF(VALUES(photo_front), ''), photo_front),
            photo_back = COALESCE(NULLIF(VALUES(photo_back), ''), photo_back),
            photo_left = COALESCE(NULLIF(VALUES(photo_left), ''), photo_left),
            photo_right = COALESCE(NULLIF(VALUES(photo_right), ''), photo_right),
            photo_rim1 = COALESCE(NULLIF(VALUES(photo_rim1), ''), photo_rim1),
            photo_rim2 = COALESCE(NULLIF(VALUES(photo_rim2), ''), photo_rim2),
            photo_rim3 = COALESCE(NULLIF(VALUES(photo_rim3), ''), photo_rim3),
            photo_rim4 = COALESCE(NULLIF(VALUES(photo_rim4), ''), photo_rim4),
            misc_photos = VALUES(misc_photos)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_SESSION['tenant_id'],
        $booking_id,
        $report_type,
        $mileage,
        $uploaded_paths['photo_front'] ?? '',
        $uploaded_paths['photo_back'] ?? '',
        $uploaded_paths['photo_left'] ?? '',
        $uploaded_paths['photo_right'] ?? '',
        $uploaded_paths['photo_rim1'] ?? '',
        $uploaded_paths['photo_rim2'] ?? '',
        $uploaded_paths['photo_rim3'] ?? '',
        $uploaded_paths['photo_rim4'] ?? '',
        $final_misc_photos
    ]);

    // Optional: Auto-update booking status
    if ($report_type === 'pickup' && ($booking['status'] === 'confirmed' || $booking['status'] === 'pending')) {
        $pdo->prepare("UPDATE bookings SET status = 'active' WHERE id = ?")->execute([$booking_id]);
    } elseif ($report_type === 'return' && $booking['status'] === 'active') {
        $pdo->prepare("UPDATE bookings SET status = 'completed' WHERE id = ?")->execute([$booking_id]);
    }

    echo json_encode(['success' => true, 'message' => 'Condition report saved successfully']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
