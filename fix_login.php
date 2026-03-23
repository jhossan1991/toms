<?php
// fix_login.php
include 'db.php';

$username = 'admin';
$password = 'admin123';
// Generate a fresh hash
$newHash = password_hash($password, PASSWORD_DEFAULT);

try {
    // Delete existing to avoid conflicts
    $pdo->prepare("DELETE FROM users WHERE username = ?")->execute([$username]);

    // Insert fresh admin with correct hash and roles from your ENUM
    $sql = "INSERT INTO users (username, password, full_name, role, branch_id) 
            VALUES (?, ?, 'System Admin', 'SuperAdmin', 1)";
    
    $pdo->prepare($sql)->execute([$username, $newHash]);

    echo "<h1>Success!</h1>";
    echo "<p>User 'admin' has been reset with password 'admin123'</p>";
    echo "<a href='login.php'>Go to Login</a>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>