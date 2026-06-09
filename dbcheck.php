<?php
// DB Diagnostic - DELETE AFTER USE
$socket = '/Applications/MAMP/tmp/mysql/mysql.sock';
echo "<pre>";
echo "Socket exists: " . (file_exists($socket) ? 'YES' : 'NO') . "\n";
echo "Socket is socket: " . (isset($socket) && file_exists($socket) && filetype($socket) === 'fifo' ? 'YES (fifo)' : (file_exists($socket) ? 'YES' : 'NO')) . "\n";

// Try socket
if (file_exists($socket)) {
    try {
        $pdo = new PDO("mysql:unix_socket=$socket;dbname=fleet_rental_pro;charset=utf8mb4", 'root', 'root');
        echo "Socket connection: SUCCESS\n";
    } catch (Exception $e) {
        echo "Socket connection failed: " . $e->getMessage() . "\n";
    }
}

// Try TCP ports
foreach ([3306, 8889, 3307] as $port) {
    foreach (['127.0.0.1', 'localhost'] as $host) {
        try {
            $pdo = new PDO("mysql:host=$host;port=$port;dbname=fleet_rental_pro;charset=utf8mb4", 'root', 'root', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            echo "TCP $host:$port: SUCCESS\n";
            break 2;
        } catch (Exception $e) {
            echo "TCP $host:$port: " . $e->getMessage() . "\n";
        }
    }
}

// Check .env loading via Dotenv
$envFile = __DIR__ . '/.env';
echo "\n.env file exists: " . (file_exists($envFile) ? 'YES' : 'NO') . "\n";
$autoload = __DIR__ . '/vendor/autoload.php';
echo "vendor/autoload.php exists: " . (file_exists($autoload) ? 'YES' : 'NO') . "\n";
if (file_exists($autoload) && file_exists($envFile)) {
    require_once $autoload;
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    echo "DB_HOST from .env: " . ($_ENV['DB_HOST'] ?? 'not set') . "\n";
    echo "DB_PORT from .env: " . ($_ENV['DB_PORT'] ?? 'not set') . "\n";
} else {
    echo "DB_HOST from env: " . (getenv('DB_HOST') ?: 'not set') . "\n";
}
echo "MySQL pid running: " . (file_exists('/Applications/MAMP/tmp/mysql/mysql.pid') ? 'YES' : 'NO') . "\n";
echo "MySQL socket: " . (file_exists('/Applications/MAMP/tmp/mysql/mysql.sock') ? 'EXISTS' : 'MISSING') . "\n";
echo "</pre>";
