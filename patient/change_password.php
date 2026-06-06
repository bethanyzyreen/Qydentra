<?php
$allowed_roles = ['patient'];
include("../includes/auth_check.php");

$user_id = $_SESSION['user_id'];

$current_password = $_POST['current_password'];
$new_password = $_POST['new_password'];
$confirm_password = $_POST['confirm_password'];

$sql = "SELECT password FROM patients WHERE patient_id='$user_id'";
$result = mysqli_query($conn,$sql);
$user = mysqli_fetch_assoc($result);

if(!password_verify($current_password,$user['password'])){
    header("Location: profile.php?error=wrong_password");
    exit();
}

if($new_password != $confirm_password){
    header("Location: profile.php?error=password_mismatch");
    exit();
}

$newHash = password_hash($new_password, PASSWORD_DEFAULT);

mysqli_query($conn,"
UPDATE patients
SET password='$newHash'
WHERE patient_id='$user_id'
");

header("Location: profile.php?success=password");
exit();
?>
