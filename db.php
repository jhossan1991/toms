<?php
// db.php
$host = 'localhost';
$db   = 'alhayiki_db';
$user = 'root';
$pass = '';

try {
    // We prefix with \ to ensure it hits the global PHP namespace
    $pdo = new \PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    
    // Set error mode to exception
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    
} catch (\PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
?>