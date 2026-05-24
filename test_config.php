<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config/config.php';

try {
    echo json_encode(['status' => 'ok', 'message' => 'Config loaded successfully', 'site_name' => SITE_NAME]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
