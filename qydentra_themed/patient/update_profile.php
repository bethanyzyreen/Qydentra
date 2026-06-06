<?php
$allowed_roles = ['patient'];
include("../includes/auth_check.php");

$user_id = $_SESSION['user_id'];

$full_name = $_POST['full_name'];
$email = $_POST['email'];
$phone = $_POST['phone'];

$sql = "
UPDATE users
SET
full_name='$full_name',
email='$email',
phone='$phone'
WHERE user_id='$user_id'
";

if(mysqli_query($conn,$sql)){

    $_SESSION['full_name'] = $full_name;

    header("Location: profile.php");

}else{

    echo "Profile update failed.";

}

?>