<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}
require_once(__DIR__ . "/../config/database.php");

$email    = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';

if(empty($email) || empty($password)){
    $_SESSION['error'] = "Please enter email and password.";
    header("Location: login.php");
    exit();
}

$sql    = "SELECT user_id, full_name, password, role, profile_photo FROM users WHERE email='$email'";
$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) > 0){

    $user = mysqli_fetch_assoc($result);

    if(password_verify($password, $user['password'])){

        $_SESSION['user_id']       = $user['user_id'];
        $_SESSION['role']          = $user['role'];
        $_SESSION['name']          = $user['full_name'];
        $_SESSION['full_name']     = $user['full_name'];
        $_SESSION['profile_photo'] = $user['profile_photo'] ?? '';

        if($user['role'] === 'patient'){
            header("Location: ../patient/dashboard.php");
            exit();
        } elseif($user['role'] === 'receptionist'){
            header("Location: ../receptionist/dashboard.php");
            exit();
        } else {
            $_SESSION['error'] = "No dashboard is available for this account role.";
            header("Location: login.php");
            exit();
        }

    } else {
        $_SESSION['error'] = "Incorrect password.";
        header("Location: login.php");
        exit();
    }

} else {
    $_SESSION['error'] = "No account found with that email.";
    header("Location: login.php");
    exit();
}
?>
