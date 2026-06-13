<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $token = trim($_POST['token'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($email) || empty($token) || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: reset_password.php?email=" . urlencode($email) . "&token=" . urlencode($token));
        exit;
    }

    if (strlen($password) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters.";
        header("Location: reset_password.php?email=" . urlencode($email) . "&token=" . urlencode($token));
        exit;
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: reset_password.php?email=" . urlencode($email) . "&token=" . urlencode($token));
        exit;
    }

    $email_safe = mysqli_real_escape_string($conn, $email);

    // Re-validate token
    $result = mysqli_query($conn, "SELECT token_hash, expires_at FROM password_resets WHERE email='$email_safe' LIMIT 1");
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        
        if (strtotime($row['expires_at']) < time()) {
            $_SESSION['error'] = "This password reset link has expired.";
            header("Location: reset_password.php?email=" . urlencode($email) . "&token=" . urlencode($token));
            exit;
        }

        if (password_verify($token, $row['token_hash'])) {
            // Token is valid! Find the user table and update password.
            $tables = ['staffs', 'dentists', 'patients'];
            $updated = false;

            $new_password_hash = password_hash($password, PASSWORD_DEFAULT);

            foreach ($tables as $table) {
                // Check if user exists in this table
                $check = mysqli_query($conn, "SELECT email FROM `$table` WHERE email='$email_safe' LIMIT 1");
                if ($check && mysqli_num_rows($check) > 0) {
                    // Update password
                    mysqli_query($conn, "UPDATE `$table` SET password='$new_password_hash' WHERE email='$email_safe'");
                    $updated = true;
                    break;
                }
            }

            if ($updated) {
                // Delete the used token
                mysqli_query($conn, "DELETE FROM password_resets WHERE email='$email_safe'");

                $_SESSION['reset_success'] = "Your password has been successfully reset. You can now log in.";
                header("Location: login.php");
                exit;
            } else {
                $_SESSION['error'] = "Error updating password. User not found.";
                header("Location: reset_password.php?email=" . urlencode($email) . "&token=" . urlencode($token));
                exit;
            }

        } else {
            $_SESSION['error'] = "Invalid reset token.";
            header("Location: reset_password.php?email=" . urlencode($email) . "&token=" . urlencode($token));
            exit;
        }
    } else {
        $_SESSION['error'] = "Invalid or expired reset token.";
        header("Location: reset_password.php?email=" . urlencode($email) . "&token=" . urlencode($token));
        exit;
    }
}

header("Location: login.php");
exit;
