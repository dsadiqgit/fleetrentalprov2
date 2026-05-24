<?php
require_once 'config/config.php';
require_once 'config/database.php';

$db = getDB();
$tables = $db->query("SHOW TABLES LIKE 'blog_posts'")->fetchAll();

if ($tables) {
    echo "Found blog_posts table.\n";
    $columns = $db->query("DESCRIBE blog_posts")->fetchAll();
    print_r($columns);
} else {
    echo "NO blog_posts table found.\n";
}
