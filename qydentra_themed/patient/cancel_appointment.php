<?php
$allowed_roles = ['patient'];
include("../includes/auth_check.php");

$user_id = $_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);

// Get appointment + patient name before cancelling
$apptRow = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT a.*, u.full_name AS patient_name
     FROM appointments a
     JOIN users u ON a.patient_id = u.user_id
     WHERE a.appointment_id='$id' AND a.patient_id='$user_id'
     AND a.status IN ('Pending','Approved')"
));

if($apptRow){
    mysqli_query($conn,"
        UPDATE appointments SET status='Cancelled'
        WHERE appointment_id='$id' AND patient_id='$user_id' AND status IN ('Pending','Approved')
    ");

    $service      = mysqli_real_escape_string($conn, $apptRow['service_type']);
    $fmt_date     = date("F d, Y", strtotime($apptRow['appointment_date']));
    $fmt_time     = date("g:i A",  strtotime($apptRow['appointment_time']));
    $patient_name = mysqli_real_escape_string($conn, $apptRow['patient_name']);

    // Notify patient (self)
    $pat_msg = mysqli_real_escape_string($conn,
        "You cancelled your $service appointment on $fmt_date at $fmt_time.");
    mysqli_query($conn,"INSERT INTO notifications(user_id,message) VALUES('$user_id','$pat_msg')");

    // Notify all receptionists
    $r_title = mysqli_real_escape_string($conn, "Appointment Cancelled by Patient");
    $r_msg   = mysqli_real_escape_string($conn,
        "Patient $patient_name cancelled their $service appointment on $fmt_date at $fmt_time.");
    $rr = mysqli_query($conn,"SELECT user_id FROM users WHERE role='receptionist'");
    while($rrow = mysqli_fetch_assoc($rr)){
        $rid = $rrow['user_id'];
        mysqli_query($conn,"INSERT INTO receptionist_notifications
            (receptionist_id, title, message, type, status)
            VALUES ('$rid','$r_title','$r_msg','Appointment','Unread')");
    }
}

header("Location: appointments.php");
exit();
