<?php
// Tenant Initialization
// This file must be included at the top of all tenant pages

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Get the current host
$host = $_SERVER['HTTP_HOST'] ?? '';

// Remove port from host for parsing
$host_without_port = preg_replace('/:\d+$/', '', $host);

// Extract subdomain
$host_parts = explode('.', $host_without_port);
$subdomain = '';

// Check if it's a subdomain
if (strpos($host_without_port, 'localhost') !== false || strpos($host_without_port, '127.0.0.1') !== false) {
    // For localhost: check if there's a subdomain (e.g., fresh.localhost)
    if (count($host_parts) >= 2 && $host_parts[0] !== 'localhost' && $host_parts[0] !== 'www') {
        $subdomain = $host_parts[0];
    }
    // Fallback: check URL parameter or session for testing
    if (empty($subdomain)) {
        if (isset($_GET['tenant'])) {
            $subdomain = $_GET['tenant'];
            $_SESSION['test_tenant'] = $subdomain;
        } elseif (isset($_SESSION['test_tenant'])) {
            $subdomain = $_SESSION['test_tenant'];
        }
    }
} else {
    // Production: extract subdomain from domain
    if (count($host_parts) >= 3) {
        $subdomain = $host_parts[0];
    } elseif (count($host_parts) == 2) {
        // Direct domain access (e.g., fleetrentalpro.com) - redirect to main site
        header('Location: /');
        exit;
    }
}

// Fetch tenant from database
$pdo = getDB();

// 1. First, try to match by custom domain (exact host match)
$stmt = $pdo->prepare("SELECT * FROM tenants WHERE custom_domain = ? AND status = 'active'");
$stmt->execute([$host_without_port]);
$tenant = $stmt->fetch();

if (!$tenant && !empty($subdomain)) {
    // 2. Fallback to subdomain match
    $stmt = $pdo->prepare("SELECT * FROM tenants WHERE subdomain = ? AND status = 'active'");
    $stmt->execute([$subdomain]);
    $tenant = $stmt->fetch();
}

if (!$tenant) {
    // Tenant not found or inactive
    http_response_code(404);
    require __DIR__ . '/../404.php';
    exit;
}

// Set tenant data globally
$GLOBALS['tenant'] = $tenant;
$GLOBALS['tenant_id'] = $tenant['id'];

// Helper function to get tenant data
function getTenant() {
    return $GLOBALS['tenant'];
}

function getTenantId() {
    return $GLOBALS['tenant_id'];
}

// Check if user is logged in and belongs to this tenant
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['tenant_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['tenant_id']]);
    return $stmt->fetch();
}

function requireLogin($redirect = '/login.php') {
    if (!isLoggedIn()) {
        header('Location: ' . $redirect);
        exit;
    }
}

function requireRole($role) {
    $user = getCurrentUser();
    if (!$user || $user['role'] !== $role) {
        http_response_code(403);
        die('Access denied');
    }
}
