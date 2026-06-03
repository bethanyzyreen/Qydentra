<?php

session_start();
include("../config/database.php");

$user_id = $_SESSION['user_id'];

$current_password = $_POST['current_password'];
$new_password = $_POST['new_password'];
$confirm_password = $_POST['confirm_password'];

$sql = "SELECT password FROM users WHERE id='$user_id'";
$result = mysqli_query($conn,$sql);

$user = mysqli_fetch_assoc($result);

if(!password_verify($current_password,$user['password'])){

    die("Current password is incorrect.");

}

if($new_password != $confirm_password){

    die("Passwords do not match.");

}

$newHash = password_hash($new_password,PASSWORD_DEFAULT);

mysqli_query($conn,"
UPDATE users
SET password='$newHash'
WHERE id='$user_id'
");

header("Location: profile.php");

?>