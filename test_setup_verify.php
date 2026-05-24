<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$pdo = getDB();
$stmt = $pdo->prepare("SELECT id, email, role, tenant_id FROM users WHERE email = 'customer@fleetrentalpro.com'");
$stmt->execute();
$user = $stmt->fetch();

echo "User Check:\n";
if ($user) {
    echo "ID: " . $user['id'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    echo "Role: " . $user['role'] . "\n";
    echo "Tenant ID: " . $user['tenant_id'] . "\n";
} else {
    echo "User not found!\n";
}

$stmt = $pdo->prepare("SELECT id, name, subdomain FROM tenants WHERE id = 1");
$stmt->execute();
$tenant = $stmt->fetch();

echo "\nTenant Check:\n";
if ($tenant) {
    echo "ID: " . $tenant['id'] . "\n";
    echo "Name: " . $tenant['name'] . "\n";
    echo "Subdomain: " . $tenant['subdomain'] . "\n";
} else {
    echo "Tenant not found!\n";
}
