<?php
$allowed_roles = ['receptionist'];
include("../includes/auth_check.php");

$date = mysqli_real_escape_string($conn, $_GET['date'] ?? date('Y-m-d'));

// Only count non-cancelled appointments to get next available queue number
$row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(MAX(queue_number),0)+1 AS next_q
     FROM appointments
     WHERE appointment_date='$date'
     AND status NOT IN ('Cancelled')"
));

header('Content-Type: application/json');
echo json_encode(['next_q' => (int)$row['next_q']]);
