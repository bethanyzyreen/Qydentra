<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['reset_email'] ?? '');

    // Always redirect with "success" so we don't reveal whether the email exists
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email_safe = mysqli_real_escape_string($conn, $email);

        // Search across all user tables (staffs, dentists, patients)
        $found = false;

        $tables = [
            ['staffs',   'full_name'],
            ['dentists', 'full_name'],
            ['patients', 'full_name'],
        ];

        foreach ($tables as [$table, $name_col]) {
            $result = mysqli_query($conn, "SELECT $name_col AS full_name FROM `$table` WHERE email='$email_safe' LIMIT 1");
            if ($result && mysqli_num_rows($result) > 0) {
                $found = true;
                break;
            }
        }

        if ($found) {
            // TODO: generate a reset token, store it, and email the link.
            // Example (requires a password_resets table and mail setup):
            //   $token = bin2hex(random_bytes(32));
            //   $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            //   mysqli_query($conn, "INSERT INTO password_resets (email, token, expires_at)
            //       VALUES ('$email_safe','$token','$expires')
            //       ON DUPLICATE KEY UPDATE token=VALUES(token), expires_at=VALUES(expires_at)");
            //   mail($email, 'Reset your Qydentra password',
            //       "Click here: https://yoursite.com/auth/reset_password.php?token=$token");
        }
    }

    header('Location: login.php?reset=1');
    exit;
}

header('Location: login.php');
exit;
