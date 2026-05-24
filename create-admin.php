<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$pdo = getDB();

// Check if super admin already exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute(['admin@fleetrentalpro.com']);
$existing = $stmt->fetch();

if ($existing) {
    echo "Super admin already exists!<br>";
    echo "Email: admin@fleetrentalpro.com<br>";
    echo "Updating password to: admin123<br><br>";
    
    // Update password
    $hashed = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->execute([$hashed, 'admin@fleetrentalpro.com']);
    echo "Password updated successfully!<br>";
} else {
    echo "Creating super admin user...<br>";
    
    // Create super admin
    $hashed = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (tenant_id, role, email, password, full_name, created_at) VALUES (NULL, 'super_admin', ?, ?, 'Super Admin', NOW())");
    $stmt->execute(['admin@fleetrentalpro.com', $hashed]);
    
    echo "Super admin created successfully!<br>";
}

echo "<br><strong>Login Credentials:</strong><br>";
echo "URL: <a href='/auth/login.php'>http://localhost:8888/auth/login.php</a><br>";
echo "Email: admin@fleetrentalpro.com<br>";
echo "Password: admin123<br><br>";
echo "<a href='/auth/login.php'>Go to Login Page</a>";
?>
