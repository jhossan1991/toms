<?php
require 'config.php';
if(isset($_POST['mobile'])) {
    $mobile = $_POST['mobile'];
    $stmt = $pdo->prepare("SELECT name FROM clients WHERE mobile_primary = ? OR mobile_secondary = ? LIMIT 1");
    $stmt->execute([$mobile, $mobile]);
    $row = $stmt->fetch();
    
    if($row) {
        echo "<span class='text-danger'>⚠️ Number belongs to: " . htmlspecialchars($row['name']) . "</span>";
    } else {
        echo "<span class='text-success'>✓ Unique Number</span>";
    }
}
?>