<?php
// Database constants
define('DB_HOST', get_env_var('DB_HOST', 'localhost:8889'));
define('DB_NAME', get_env_var('DB_NAME', 'fleet_rental_pro'));
define('DB_USER', get_env_var('DB_USER', 'root'));
define('DB_PASS', get_env_var('DB_PASS', 'root'));
define('DB_CHARSET', get_env_var('DB_CHARSET', 'utf8mb4'));

// PDO Database Connection
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
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
