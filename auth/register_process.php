<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}
require_once(__DIR__ . "/../config/database.php");

$name     = mysqli_real_escape_string($conn, trim($_POST['full_name'] ?? ''));
$email    = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
$password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);

if(empty($name) || empty($email)){
    $_SESSION['error'] = "Please fill in all fields.";
    header("Location: register.php");
    exit();
}

// Check if email already exists in patients table
$check = mysqli_query($conn, "SELECT patient_id FROM patients WHERE email='$email'");
if(mysqli_num_rows($check) > 0){
    $_SESSION['error'] = "Email is already registered.";
    header("Location: register.php");
    exit();
}

$sql = "INSERT INTO patients(full_name, email, password, role)
        VALUES('$name', '$email', '$password', 'patient')";

if(mysqli_query($conn, $sql)){
    $_SESSION['success'] = "Registration successful! Please log in.";
    header("Location: login.php");
    exit();
} else {
    $_SESSION['error'] = "Registration failed. Please try again.";
    header("Location: register.php");
    exit();
}
?>
