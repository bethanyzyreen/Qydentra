<?php

$allowed_roles = ['patient'];
include("../includes/auth_check.php");

$user_id = $_SESSION['user_id'];

$id = $_GET['id'] ?? 0;

$sql = "
UPDATE appointments
SET status='Cancelled'
WHERE id='$id'
AND patient_id='$user_id'
AND status IN ('Pending','Approved')
";

mysqli_query($conn,$sql);

/* notification */

$message = "Appointment cancelled successfully.";

mysqli_query($conn,"
INSERT INTO notifications(user_id,message)
VALUES('$user_id','$message')
");

header("Location: appointments.php");
exit();