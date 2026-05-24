<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Check if we have Google user data in session
if (isset($_SESSION['google_user_data'])) {
    echo json_encode([
        'success' => true,
        'google_user' => $_SESSION['google_user_data']
    ]);
    
    // Clear the session data after reading
    unset($_SESSION['google_user_data']);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No Google user data found'
    ]);
}
?>
