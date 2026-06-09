<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . "/../config/database.php");

$email    = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['error'] = "Please enter email and password.";
    header("Location: login.php");
    exit();
}

// ---------------------------------------------------------------
// 1. Check staffs table FIRST (receptionist, admin, dentist)
// ---------------------------------------------------------------
$sql    = "SELECT staff_id AS user_id, full_name, password, role, profile_photo
           FROM staffs WHERE email='$email' LIMIT 1";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);

    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id']       = $user['user_id'];   // e.g. RE001 or ST001
        $_SESSION['role']          = $user['role'];
        $_SESSION['name']          = $user['full_name'];
        $_SESSION['full_name']     = $user['full_name'];
        $_SESSION['profile_photo'] = $user['profile_photo'] ?? '';

        switch ($user['role']) {
            case 'receptionist':
                header("Location: ../receptionist/dashboard.php");
                exit();
            case 'admin':
                header("Location: ../admin/dashboard.php");
                exit();
            case 'dentist':
                header("Location: ../dentist/dashboard.php");
                exit();
            default:
                $_SESSION['error'] = "No dashboard available for this role.";
                header("Location: login.php");
                exit();
        }
    } else {
        $_SESSION['error'] = "Incorrect password.";
        header("Location: login.php");
        exit();
    }
}

// ---------------------------------------------------------------
// 2. Check dentists table SECOND
// ---------------------------------------------------------------
$sql    = "SELECT dentist_id AS user_id, full_name, password, role, profile_photo, status
           FROM dentists WHERE email='$email' LIMIT 1";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);

    if (password_verify($password, $user['password'])) {
        // Block inactive/resigned dentists from logging in
        if (($user['status'] ?? 'active') === 'inactive') {
            $_SESSION['error'] = "Your account has been deactivated. Please contact the administrator.";
            header("Location: login.php");
            exit();
        }
        $_SESSION['user_id']       = $user['user_id'];
        $_SESSION['role']          = $user['role'];
        $_SESSION['name']          = $user['full_name'];
        $_SESSION['full_name']     = $user['full_name'];
        $_SESSION['profile_photo'] = $user['profile_photo'] ?? '';

        header("Location: ../dentist/dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Incorrect password.";
        header("Location: login.php");
        exit();
    }
}

// ---------------------------------------------------------------
// 3. Check patients table THIRD
// ---------------------------------------------------------------
$sql    = "SELECT patient_id AS user_id, full_name, password, role, profile_photo
           FROM patients WHERE email='$email' LIMIT 1";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);

    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id']       = $user['user_id'];   // e.g. PT001
        $_SESSION['role']          = 'patient';
        $_SESSION['name']          = $user['full_name'];
        $_SESSION['full_name']     = $user['full_name'];
        $_SESSION['profile_photo'] = $user['profile_photo'] ?? '';
        header("Location: ../patient/dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Incorrect password.";
        header("Location: login.php");
        exit();
    }
}

$_SESSION['error'] = "No account found with that email.";
header("Location: login.php");
exit();
