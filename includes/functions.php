<?php
// Helper Functions

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Hash password
function hashPassword($password) {
    return password_hash($password, HASH_ALGO);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Generate random string
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

// Format currency
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

// Format date
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Format datetime
function formatDateTime($datetime) {
    return date('M d, Y H:i', strtotime($datetime));
}

// Calculate rental days
function calculateRentalDays($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = $start->diff($end);
    return $interval->days + 1; // Include both start and end days
}

// Calculate rental price
function calculateRentalPrice($price_per_day, $days) {
    return $price_per_day * $days;
}

// Check if vehicle is available
function isVehicleAvailable($vehicle_id, $start_date, $end_date, $exclude_booking_id = null) {
    $pdo = getDB();
    
    $sql = "SELECT COUNT(*) FROM bookings 
            WHERE vehicle_id = ? 
            AND status IN ('confirmed', 'active')
            AND (
                (start_date <= ? AND end_date >= ?) OR
                (start_date <= ? AND end_date >= ?) OR
                (start_date >= ? AND end_date <= ?)
            )";
    
    $params = [$vehicle_id, $start_date, $start_date, $end_date, $end_date, $start_date, $end_date];
    
    if ($exclude_booking_id) {
        $sql .= " AND id != ?";
        $params[] = $exclude_booking_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchColumn() == 0;
}

// Upload file
function uploadFile($file, $folder = 'general') {
    $upload_dir = UPLOAD_PATH . $folder . '/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return '/uploads/' . $folder . '/' . $new_filename;
    }
    
    return false;
}

// Redirect helper
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

// Flash message system
function setFlash($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

function getFlash($type) {
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return null;
}

function hasFlash($type) {
    return isset($_SESSION['flash'][$type]);
}

// Pagination helper
function paginate($total_records, $records_per_page, $current_page) {
    $total_pages = ceil($total_records / $records_per_page);
    $offset = ($current_page - 1) * $records_per_page;
    
    return [
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'offset' => $offset,
        'limit' => $records_per_page,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages
    ];
}
