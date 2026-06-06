<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDB();
    
    // Check if columns already exist
    $stmt = $pdo->query("SHOW COLUMNS FROM tenant_settings LIKE 'pickup_location'");
    $pickupExists = $stmt->fetch();
    
    $stmt = $pdo->query("SHOW COLUMNS FROM tenant_settings LIKE 'dropoff_location'");
    $dropoffExists = $stmt->fetch();
    
    $stmt = $pdo->query("SHOW COLUMNS FROM tenant_settings LIKE 'min_booking_notice'");
    $minNoticeExists = $stmt->fetch();
    
    $stmt = $pdo->query("SHOW COLUMNS FROM tenant_settings LIKE 'booking_notice_unit'");
    $noticeUnitExists = $stmt->fetch();
    
    $stmt = $pdo->query("SHOW COLUMNS FROM tenant_settings LIKE 'buffer_time_hours'");
    $bufferExists = $stmt->fetch();
    
    $stmt = $pdo->query("SHOW COLUMNS FROM tenant_settings LIKE 'max_booking_advance_days'");
    $maxAdvanceExists = $stmt->fetch();
    
    $stmt = $pdo->query("SHOW COLUMNS FROM tenant_settings LIKE 'opening_time'");
    $openingTimeExists = $stmt->fetch();
    
    $stmt = $pdo->query("SHOW COLUMNS FROM tenant_settings LIKE 'closing_time'");
    $closingTimeExists = $stmt->fetch();
    
    $stmt = $pdo->query("SHOW COLUMNS FROM tenant_settings LIKE 'currency'");
    $currencyExists = $stmt->fetch();
    
    // Add columns if they don't exist
    if (!$pickupExists) {
        $pdo->exec("ALTER TABLE tenant_settings ADD COLUMN pickup_location TEXT");
        echo "Added pickup_location column\n";
    } else {
        echo "pickup_location column already exists\n";
    }
    
    if (!$dropoffExists) {
        $pdo->exec("ALTER TABLE tenant_settings ADD COLUMN dropoff_location TEXT");
        echo "Added dropoff_location column\n";
    } else {
        echo "dropoff_location column already exists\n";
    }
    
    if (!$minNoticeExists) {
        $pdo->exec("ALTER TABLE tenant_settings ADD COLUMN min_booking_notice INT DEFAULT 0");
        echo "Added min_booking_notice column\n";
    } else {
        echo "min_booking_notice column already exists\n";
    }
    
    if (!$noticeUnitExists) {
        $pdo->exec("ALTER TABLE tenant_settings ADD COLUMN booking_notice_unit ENUM('hours', 'days') DEFAULT 'hours'");
        echo "Added booking_notice_unit column\n";
    } else {
        echo "booking_notice_unit column already exists\n";
    }
    
    if (!$bufferExists) {
        $pdo->exec("ALTER TABLE tenant_settings ADD COLUMN buffer_time_hours INT DEFAULT 0");
        echo "Added buffer_time_hours column\n";
    } else {
        echo "buffer_time_hours column already exists\n";
    }
    
    if (!$maxAdvanceExists) {
        $pdo->exec("ALTER TABLE tenant_settings ADD COLUMN max_booking_advance_days INT DEFAULT 30");
        echo "Added max_booking_advance_days column\n";
    } else {
        echo "max_booking_advance_days column already exists\n";
    }
    
    if (!$openingTimeExists) {
        $pdo->exec("ALTER TABLE tenant_settings ADD COLUMN opening_time TIME DEFAULT '09:00:00'");
        echo "Added opening_time column\n";
    } else {
        echo "opening_time column already exists\n";
    }
    
    if (!$closingTimeExists) {
        $pdo->exec("ALTER TABLE tenant_settings ADD COLUMN closing_time TIME DEFAULT '18:00:00'");
        echo "Added closing_time column\n";
    } else {
        echo "closing_time column already exists\n";
    }
    
    if (!$currencyExists) {
        $pdo->exec("ALTER TABLE tenant_settings ADD COLUMN currency VARCHAR(3) DEFAULT 'GBP'");
        echo "Added currency column\n";
    } else {
        echo "currency column already exists\n";
    }
    
    echo "\nMigration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
