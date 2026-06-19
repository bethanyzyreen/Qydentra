<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['reset_email'] ?? '');

    // Always set the exact same success message
    $_SESSION['reset_success'] = "Password reset request processed. If an account with that email exists, reset instructions have been generated.";

    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email_safe = mysqli_real_escape_string($conn, $email);

        // Search across all user tables (staffs, dentists, patients)
        $found = false;

        $tables = ['staffs', 'dentists', 'patients'];

        foreach ($tables as $table) {
            $result = mysqli_query($conn, "SELECT email FROM `$table` WHERE email='$email_safe' LIMIT 1");
            if ($result && mysqli_num_rows($result) > 0) {
                $found = true;
                break;
            }
        }

        if ($found) {
            // Generate a secure raw token
            $raw_token = bin2hex(random_bytes(32));
            
            // Hash the token for storage
            $token_hash = password_hash($raw_token, PASSWORD_DEFAULT);
            
            // 1 hour expiration
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Invalidate any previous active tokens for this email
            mysqli_query($conn, "DELETE FROM password_resets WHERE email='$email_safe'");

            // Store the hashed token in the database
            $stmt = $conn->prepare("INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sss", $email, $token_hash, $expires_at);
                $stmt->execute();
                $stmt->close();
                
                // Build the reset URL dynamically (avoids hard-coded project folder)
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $base = rtrim(dirname($_SERVER['REQUEST_URI']), '/\\');
                $reset_link = $protocol . '://' . $host . $base . '/reset_password.php?email=' . urlencode($email) . '&token=' . urlencode($raw_token);
                $_SESSION['dev_reset_link'] = $reset_link;
                
                // TODO: When SMTP is configured, send the $reset_link via email here.
            }
        }
    }

    header('Location: login.php');
    exit;
}

header('Location: login.php');
exit;
