<?php
$allowed_roles = ['patient'];
include("../includes/auth_check.php");

$user_id = $_SESSION['user_id'];

$full_name_raw    = trim($_POST['full_name'] ?? '');
$email_raw        = trim($_POST['email'] ?? '');
$phone_number_raw = trim($_POST['phone_number'] ?? $_POST['phone'] ?? '');

// --- Input validation ---

// Full name: required
if ($full_name_raw === '') {
    header("Location: profile.php?error=invalid_name");
    exit();
}

// Email: required and must be a valid format
if ($email_raw === '' || !filter_var($email_raw, FILTER_VALIDATE_EMAIL)) {
    header("Location: profile.php?error=invalid_email");
    exit();
}

// Phone number: optional, but if provided must be exactly 11 digits (e.g. 09XXXXXXXXX)
if ($phone_number_raw !== '' && !preg_match('/^\d{11}$/', $phone_number_raw)) {
    header("Location: profile.php?error=invalid_phone");
    exit();
}

$full_name    = mysqli_real_escape_string($conn, $full_name_raw);
$email        = mysqli_real_escape_string($conn, $email_raw);
$phone_number = mysqli_real_escape_string($conn, $phone_number_raw);

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
