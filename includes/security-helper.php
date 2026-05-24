<?php
/**
 * Security Helper Functions
 */

/**
 * Sanitize all input values in an array (recursive)
 */
function sanitize_input($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize_input($value);
        }
    } else {
        // Remove null bytes and trim
        $data = trim(str_replace(chr(0), '', (string)$data));
        // Simple HTML sanitization
        $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    return $data;
}

/**
 * Rate limiting using database
 */
function check_rate_limit($key_prefix = 'global', $limit = 60, $seconds = 60, $die = true) {
    $pdo = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = hash('sha256', $key_prefix . ':' . $ip);
    
    $stmt = $pdo->prepare("SELECT requests, reset_at FROM api_rate_limits WHERE key_id = ?");
    $stmt->execute([$key]);
    $limit_data = $stmt->fetch();
    
    $now = new DateTime('now', new DateTimeZone('UTC'));
    
    if (!$limit_data || new DateTime($limit_data['reset_at'], new DateTimeZone('UTC')) <= $now) {
        $reset_at = (clone $now)->modify("+$seconds seconds")->format('Y-m-d H:i:s');
        $stmt = $pdo->prepare("INSERT INTO api_rate_limits (key_id, requests, reset_at) 
                              VALUES (?, 1, ?) 
                              ON DUPLICATE KEY UPDATE requests = 1, reset_at = ?");
        $stmt->execute([$key, $reset_at, $reset_at]);
        return true;
    }
    
    if ($limit_data['requests'] >= $limit) {
        if ($die) {
            header('HTTP/1.1 429 Too Many Requests');
            $reset_time = new DateTime($limit_data['reset_at'], new DateTimeZone('UTC'));
            header('Retry-After: ' . ($reset_time->getTimestamp() - $now->getTimestamp()));
            die(json_encode(['error' => 'Rate limit exceeded. Please try again later.']));
        }
        return false;
    }
    
    $stmt = $pdo->prepare("UPDATE api_rate_limits SET requests = requests + 1 WHERE key_id = ?");
    $stmt->execute([$key]);
    return true;
}

/**
 * Validate CSRF token
 */
function validate_csrf($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token) || $token !== $_SESSION['csrf_token']) {
        header('HTTP/1.1 403 Forbidden');
        die(json_encode(['error' => 'Invalid CSRF token']));
    }
    return true;
}

/**
 * Generate CSRF token
 */
function get_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
