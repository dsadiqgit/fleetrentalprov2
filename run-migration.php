<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

echo "Running Google OAuth migration...\n";

try {
    $pdo = getDB();
    
    // Add google_id column
    $sql1 = "ALTER TABLE users ADD COLUMN google_id VARCHAR(100) NULL AFTER driving_license_url";
    $pdo->exec($sql1);
    echo "✓ Added google_id column\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "- google_id column already exists\n";
    } else {
        echo "✗ Error adding google_id: " . $e->getMessage() . "\n";
    }
}

try {
    $pdo = getDB();
    
    // Make password nullable
    $sql2 = "ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NULL";
    $pdo->exec($sql2);
    echo "✓ Made password column nullable\n";
} catch (PDOException $e) {
    echo "- Password column already nullable or error: " . $e->getMessage() . "\n";
}

try {
    $pdo = getDB();
    
    // Add index
    $sql3 = "ALTER TABLE users ADD INDEX idx_google_id (google_id)";
    $pdo->exec($sql3);
    echo "✓ Added google_id index\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
        echo "- google_id index already exists\n";
    } else {
        echo "✗ Error adding index: " . $e->getMessage() . "\n";
    }
}

echo "\nMigration completed!\n";
echo "Check your users table structure to verify.\n";
?>
