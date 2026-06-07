<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . "/../config/database.php");
require_once(__DIR__ . "/../includes/id_helper.php");

$name     = mysqli_real_escape_string($conn, trim($_POST['full_name'] ?? ''));
$email    = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
$password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);

if (empty($name) || empty($email)) {
    $_SESSION['error'] = "Please fill in all fields.";
    header("Location: register.php");
    exit();
}

// Block registration if email already exists in staffs
$staffCheck = mysqli_query($conn, "SELECT staff_id FROM staffs WHERE email='$email' LIMIT 1");
if (mysqli_num_rows($staffCheck) > 0) {
    $_SESSION['error'] = "This email is already registered as a staff account. Please log in directly.";
    header("Location: login.php");
    exit();
}

// Block duplicate patient registration
$patientCheck = mysqli_query($conn, "SELECT patient_id FROM patients WHERE email='$email' LIMIT 1");
if (mysqli_num_rows($patientCheck) > 0) {
    $_SESSION['error'] = "Email is already registered.";
    header("Location: register.php");
    exit();
}

// Trigger auto-assigns patient_id (e.g. PT001)
$sql = "INSERT INTO patients (full_name, email, password, role)
        VALUES ('$name', '$email', '$password', 'patient')";

if (mysqli_query($conn, $sql)) {
    $_SESSION['success'] = "Registration successful! Please log in.";
    header("Location: login.php");
    exit();
} else {
    $_SESSION['error'] = "Registration failed: " . mysqli_error($conn);
    header("Location: register.php");
    exit();
}
