<?php
$allowed_roles = ['patient'];
include("../includes/auth_check.php");

$user_id = $_SESSION['user_id'];

$full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
$email = mysqli_real_escape_string($conn, $_POST['email']);
$phone_number = mysqli_real_escape_string($conn, $_POST['phone_number'] ?? $_POST['phone'] ?? '');

$sql = "
UPDATE patients
SET
full_name='$full_name',
email='$email',
phone_number='$phone_number'
WHERE patient_id='$user_id'
";

if(mysqli_query($conn,$sql)){
    $_SESSION['full_name'] = $full_name;
    $_SESSION['name'] = $full_name;
    header("Location: profile.php?success=1");
    exit();
} else {
    header("Location: profile.php?error=1");
    exit();
}
?>
