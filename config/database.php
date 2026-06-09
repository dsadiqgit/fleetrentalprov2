<?php
// Parse DB_HOST which may be "host:port" or just "host"
$_db_host_raw = get_env_var('DB_HOST', '127.0.0.1');
if (strpos($_db_host_raw, ':') !== false) {
    [$_db_host_parsed, $_db_port_parsed] = explode(':', $_db_host_raw, 2);
} else {
    $_db_host_parsed = $_db_host_raw;
    $_db_port_parsed = get_env_var('DB_PORT', '8889');
}

// Database constants
define('DB_HOST', $_db_host_parsed);
define('DB_PORT', $_db_port_parsed);
define('DB_NAME', get_env_var('DB_NAME', 'fleet_rental_pro'));
define('DB_USER', get_env_var('DB_USER', 'root'));
define('DB_PASS', get_env_var('DB_PASS', 'root'));
define('DB_CHARSET', get_env_var('DB_CHARSET', 'utf8mb4'));
define('DB_SOCKET', get_env_var('DB_SOCKET', '/Applications/MAMP/tmp/mysql/mysql.sock'));

// PDO Database Connection
function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            // Use Unix socket if available (most reliable on MAMP/macOS)
            if (DB_SOCKET && file_exists(DB_SOCKET)) {
                $dsn = "mysql:unix_socket=" . DB_SOCKET . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            } else {
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            }
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $pdo->exec("SET time_zone = '+00:00'");
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    return $pdo;
}
