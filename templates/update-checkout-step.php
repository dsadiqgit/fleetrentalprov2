<?php
header('Content-Type: application/json');
// Session already started in config.php

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['step'])) {
    $_SESSION['checkout_step'] = intval($data['step']);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid step']);
}
?>
