<?php
session_start();
require_once '../config/database.php';

$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';
$is_valid = false;
$error_msg = "";

if (empty($email) || empty($token)) {
    $error_msg = "Invalid or missing reset token.";
} else {
    $email_safe = mysqli_real_escape_string($conn, $email);
    $result = mysqli_query($conn, "SELECT token_hash, expires_at FROM password_resets WHERE email='$email_safe' LIMIT 1");
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        
        // Verify expiration
        if (strtotime($row['expires_at']) < time()) {
            $error_msg = "This password reset link has expired. Please request a new one.";
        } else {
            // Verify token hash
            if (password_verify($token, $row['token_hash'])) {
                $is_valid = true;
            } else {
                $error_msg = "Invalid reset token.";
            }
        }
    } else {
        $error_msg = "Invalid or expired reset token.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password - Qydentra</title>
<link rel="stylesheet" href="../assets/css/auth.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="auth-container">
    <div class="auth-card">
        <div class="logo">Qydentra</div>
        <p class="tagline">Create New Password</p>

        <?php if(isset($_SESSION["error"])): ?>
            <div class="alert-error"><?php echo $_SESSION["error"]; unset($_SESSION["error"]); ?></div>
        <?php endif; ?>

        <?php if (!$is_valid): ?>
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation" style="margin-right:6px;"></i>
                <?php echo htmlspecialchars($error_msg); ?>
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="login.php" style="color:#3b82f6; text-decoration:underline;">Return to Login</a>
            </div>
        <?php else: ?>
            <form action="reset_password_process.php" method="POST">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="input-group">
                    <input type="password" name="password" placeholder="New Password (min 8 chars)" required minlength="8">
                </div>
                <div class="input-group">
                    <input type="password" name="confirm_password" placeholder="Confirm New Password" required minlength="8">
                </div>
                
                <button type="submit" style="margin-top: 10px;">
                    Update Password
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
