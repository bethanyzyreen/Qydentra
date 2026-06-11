<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['reset_email'] ?? '');

    // Check if email exists (silently succeed either way for security)
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Generate a secure token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store token in DB (you may need a password_resets table)
            // $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)
            //                ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)")
            //     ->execute([$email, $token, $expires]);

            // In production: send email with reset link
            // mail($email, 'Reset your Qydentra password',
            //     "Click to reset: https://yoursite.com/auth/reset_password.php?token=$token");
        }
    }

    // Always redirect with success (security: don't reveal if email exists)
    header('Location: login.php?reset=1');
    exit;
}

header('Location: login.php');
exit;
