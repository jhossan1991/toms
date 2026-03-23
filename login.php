<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_input = $_POST['username'];
    $pass_input = $_POST['password'];

    // Query based on your users table structure
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$user_input]);
    $user = $stmt->fetch();

    // Verify password (assumes you used password_hash when creating users)
    if ($user && password_verify($pass_input, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['branch_id'] = $user['branch_id'];
        
        // NEW: Convert "Admin, Translator" string into array ['Admin', 'Translator']
        $role_list = array_map('trim', explode(',', $user['role']));
        $_SESSION['roles'] = $role_list; 
        
        // Set a primary role for simple display purposes
        $_SESSION['role'] = $role_list[0];
        
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid credentials. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | ALHAYIKI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-box { width: 100%; max-width: 400px; background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="login-box">
        <h3 class="text-center fw-bold text-success mb-4">ALHAYIKI LOGIN</h3>
        <?php if(isset($error)): ?>
            <div class="alert alert-danger py-2 small"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Enter username" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-success w-100 py-2 fw-bold">Sign In</button>
        </form>
    </div>
</body>
</html>