<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

echo "<h2>Login Debug Test</h2>";

$email = 'admin@fleetrentalpro.com';
$password = 'admin123';

echo "<p><strong>Testing email:</strong> $email</p>";
echo "<p><strong>Testing password:</strong> $password</p>";

try {
    $pdo = getDB();
    echo "<p style='color:green'>✓ Database connection successful</p>";
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<p style='color:green'>✓ User found in database</p>";
        echo "<p><strong>User ID:</strong> " . $user['id'] . "</p>";
        echo "<p><strong>Email:</strong> " . $user['email'] . "</p>";
        echo "<p><strong>Role:</strong> " . $user['role'] . "</p>";
        echo "<p><strong>Password Hash:</strong> " . substr($user['password'], 0, 30) . "...</p>";
        
        // Test password verification
        $verify_result = password_verify($password, $user['password']);
        
        if ($verify_result) {
            echo "<p style='color:green; font-size:20px'>✓✓✓ PASSWORD VERIFICATION SUCCESSFUL!</p>";
            echo "<p>Login should work with these credentials.</p>";
        } else {
            echo "<p style='color:red; font-size:20px'>✗ PASSWORD VERIFICATION FAILED</p>";
            
            // Try generating a new hash
            $new_hash = password_hash($password, PASSWORD_BCRYPT);
            echo "<p><strong>New hash generated:</strong> " . substr($new_hash, 0, 30) . "...</p>";
            
            // Update the password in database
            $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $update_stmt->execute([$new_hash, $email]);
            echo "<p style='color:orange'>⚠ Password has been reset. Try logging in again.</p>";
        }
    } else {
        echo "<p style='color:red'>✗ User NOT found in database</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error: " . $e->getMessage() . "</p>";
}
?>
