<?php

session_start();
include("../config/database.php");

$email = mysqli_real_escape_string(
$conn,
$_POST['email']
);

$password = $_POST['password'];

$sql = "SELECT * FROM users
WHERE email='$email'";
$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) > 0){

    $user = mysqli_fetch_assoc($result);

    if(password_verify($password, $user['password'])){

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];

        if($user['role'] == 'patient'){
            header("Location: ../patient/dashboard.php");
        }

        elseif($user['role'] == 'admin'){
            echo "Admin dashboard soon.";
        }

        elseif($user['role'] == 'dentist'){
            echo "Dentist dashboard soon.";
        }

        elseif($user['role'] == 'receptionist'){
            echo "Receptionist dashboard soon.";
        }

    } else {
        $_SESSION['error'] = "Wrong password.";
        header("Location: login.php");
        exit();
    }

} else {
    $_SESSION['error'] = "Account not found.";
    header("Location: login.php");
    exit();
}

?>